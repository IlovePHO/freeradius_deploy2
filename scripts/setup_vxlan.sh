#!/usr/bin/env bash
set -xe

PATH=${PATH}:/usr/local/bin:/usr/bin:/usr/local/sbin:/usr/sbin
IMDS_BASE_URL="http://169.254.169.254/latest"
IMDS_IF_MACS_URL="${IMDS_BASE_URL}/meta-data/network/interfaces/macs"
AWS_EC2_PRIVATE_IP_QUERY='NetworkInterfaces[*].PrivateIpAddresses[*].PrivateIpAddress'

VPN_SUBNET_IF_NAME=eth1
VXLAN_IFNAME=vxlan0
VXLAN_DST_PORT=8472

get_default_user() {
    local os=$(grep "^ID=" /etc/os-release | sed -e "s/ID=//")
    case "${os}" in
        ubuntu*)
            echo "ubuntu"
            ;;
        *rhel*)
            echo "ec2-user"
            ;;
        *)
            exit
            ;;
    esac
}

get_lsconfig_path() {
    local user=`get_default_user`
    echo "/home/${user}/.lsconfig"
}

read_vxlan_skip_set_ipaddr() {
    local lsconfig_path=`get_lsconfig_path`
    if [ -n "${lsconfig_path}" ] && [ -e ${lsconfig_path} ]; then
        cat ${lsconfig_path} | jq -r '.["vxlan_skip_set_ipaddr"]'
    fi
}

read_vxlan_ipaddress_config() {
    local lsconfig_path=`get_lsconfig_path`
    if [ -n "${lsconfig_path}" ] && [ -e ${lsconfig_path} ]; then
        cat ${lsconfig_path} | jq -r '.["vxlan_ip_address"]'
    fi
}

read_vxlan_id_config() {
    local lsconfig_path=`get_lsconfig_path`
    if [ -n "${lsconfig_path}" ] && [ -e ${lsconfig_path} ]; then
        cat ${lsconfig_path} | jq -r '.["vxlan_id"]'
    fi
}

create_vxlan_if() {
    if [ -e "/sys/devices/virtual/net/${VXLAN_IFNAME}" ]; then
        return
    fi

    # Obtain VXLAN ID information from the configuration file.
    local vxlan_id=`read_vxlan_id_config`

    sudo ip link add ${VXLAN_IFNAME} type vxlan id ${vxlan_id} dstport ${VXLAN_DST_PORT} dev ${VPN_SUBNET_IF_NAME}
    sudo ip link set up dev ${VXLAN_IFNAME}
}

set_vxlan_ip_address() {
    # Obtain vxlan_skip_set_ from the configuration file.
    local vxlan_skip_set_ipaddr=`read_vxlan_skip_set_ipaddr`
    if [ "1" = "${vxlan_skip_set_ipaddr}" ]; then
        return
    fi

    # Obtain IP address information from the configuration file.
    local vxlan_ipaddress=`read_vxlan_ipaddress_config`
    local vxlan_ipaddress_local=`echo ${vxlan_ipaddress} | jq -r '.["local"]'`
    local vxlan_ipaddress_prefixlen=`echo ${vxlan_ipaddress} | jq -r '.["prefixlen"]'`
    if [ -z "${vxlan_ipaddress_local}" ] || [ -z "${vxlan_ipaddress_prefixlen}" ]; then
        return
    fi

    # Check the status of ip address settings.
    local exist_ip=`ip -4 -j -f inet a s dev ${VXLAN_IFNAME} | \
                        jq ".[]|select(.addr_info[].local == \"${vxlan_ipaddress_local}\" \
                            and .addr_info[].prefixlen == ${vxlan_ipaddress_prefixlen} )" |
                        wc -l`
    if [ "${exist_ip}" = "0" ]; then
        # Set ip address.
        sudo ip addr replace ${vxlan_ipaddress_local}/${vxlan_ipaddress_prefixlen} dev ${VXLAN_IFNAME}
    fi
}

get_imds_token() {
    curl -X PUT "${IMDS_BASE_URL}/api/token" -H "X-aws-ec2-metadata-token-ttl-seconds: 21600" 2>/dev/null
}

get_imds_network_info() {
    local token=$1
    local mac=$2
    local key=$3

    curl -H ”X-aws-ec2-metadata-token: ${token}” "${IMDS_IF_MACS_URL}/${mac}/${key}" 2>/dev/null
}

list_vpn_subnet_ip_addresses() {
    # Obtain VPC information using AWS IMDS.
    local vpn_subnet_if_mac=`ip -j link show dev ${VPN_SUBNET_IF_NAME} | jq -r '.[]["address"]|.'`
    local imds_token=`get_imds_token`
    local vpc_id=`get_imds_network_info "${imds_token}" "${vpn_subnet_if_mac}" "vpc-id"`
    local vpn_subnet_id=`get_imds_network_info "${imds_token}" "${vpn_subnet_if_mac}" "subnet-id"`
    local vpn_subnet_if_address=`get_imds_network_info "${imds_token}" "${vpn_subnet_if_mac}" "local-ipv4s"`

    # Returns the IP addresses of other EC2 instances belonging to the VPN subnet.
    local user=`get_default_user`
    sudo -u ${user} aws ec2 describe-network-interfaces \
                    --filters Name=vpc-id,Values=${vpc_id} \
                              Name=subnet-id,Values=${vpn_subnet_id} \
                    --query ${AWS_EC2_PRIVATE_IP_QUERY} | \
        jq -r ".[][]|select(. != \"${vpn_subnet_if_address}\")"
}

list_bridge_fdb_ip_addresses() {
    # Returns the IP addresses of other EC2 instances already registered on the VXLAN.
    bridge -j fdb show dev ${VXLAN_IFNAME} | \
        jq -r '.[]|select(.mac == "00:00:00:00:00:00")|.dst' | \
        sort | uniq
}

update_vxlan_bridge_fdb() {
    local vpn_subnet_ip_addresses=`list_vpn_subnet_ip_addresses`
    local bridge_fdb_ip_addresses=`list_bridge_fdb_ip_addresses`
    local vpn_subnet_ip_address=""
    local bridge_fdb_ip_address=""
    local is_exist=0

    # Add IP addresses of other instances to bridge fdb.
    for vpn_subnet_ip_address in ${vpn_subnet_ip_addresses}; do
        is_exist=0
        for bridge_fdb_ip_address in ${bridge_fdb_ip_addresses}; do
            if [ "${bridge_fdb_ip_address}" = "${vpn_subnet_ip_address}" ]; then
                is_exist=1
		break
            fi
        done
	if [ "${is_exist}" == "0" ]; then
            sudo bridge fdb append 00:00:00:00:00:00 dev ${VXLAN_IFNAME} dst ${vpn_subnet_ip_address}
        fi
    done

    # Remove IP addresses of instances that no longer exist from bridge fdb.
    for bridge_fdb_ip_address in ${bridge_fdb_ip_addresses}; do
	is_exist=0
        for vpn_subnet_ip_address in ${vpn_subnet_ip_addresses}; do
            if [ "${bridge_fdb_ip_address}" = "${vpn_subnet_ip_address}" ]; then
                is_exist=1
		break
            fi
        done
	if [ "${is_exist}" == "0" ]; then
            sudo bridge fdb del 00:00:00:00:00:00 dev ${VXLAN_IFNAME} dst ${bridge_fdb_ip_address}
        fi
    done
}

main() {
    create_vxlan_if
    set_vxlan_ip_address
    update_vxlan_bridge_fdb
}

main
