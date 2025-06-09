#!/usr/bin/env bash
set -xe

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

read_s3_bucket_name() {
    local lsconfig_path=`get_lsconfig_path`
    if [ -n "${lsconfig_path}" ] && [ -e ${lsconfig_path} ]; then
        cat ${lsconfig_path} | jq -r '.["s3_bucket_name"]'
    fi
}

read_s3_mount_point() {
    local lsconfig_path=`get_lsconfig_path`
    if [ -n "${lsconfig_path}" ] && [ -e ${lsconfig_path} ]; then
        cat ${lsconfig_path} | jq -r '.["s3_mount_point"]'
    fi
}

mount_s3_bucket() {
    local s3_bucket_name=`read_s3_bucket_name`
    local s3_mount_point=`read_s3_mount_point`

    if [ -n ${s3_bucket_name} ] && [ -n ${s3_mount_point} ] && \
       [ "null" != "${s3_bucket_name}" ] && [ "null" != "${s3_mount_point}" ]; then
        local s3_mounted=`mount | grep ${s3_mount_point} | wc -l`
        if [ "${s3_mounted}" = "0" ]; then
            if [ ! -d ${s3_mount_point} ]; then
                mkdir -p ${s3_mount_point}
            fi

            fstab_configured=`cat /etc/fstab | grep "s3fs#${s3_bucket_name}" | wc -l`
            if [ "${fstab_configured}" = "0" ]; then
                echo "s3fs#${s3_bucket_name} ${s3_mount_point} fuse defaults 0 0" >> /etc/fstab
            fi

            mount ${s3_mount_point}
        fi
    fi
}

main() {
    mount_s3_bucket
}

main
