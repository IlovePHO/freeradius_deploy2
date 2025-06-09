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

get_lsconfig() {
    local key=$1
    local key2=$2

    local value
    local lsconfig_path=`get_lsconfig_path`
    if [ -n "${lsconfig_path}" ] && [ -e ${lsconfig_path} ]; then
        if [ -n "${key2}" ]; then
            value=`cat ${lsconfig_path} | jq -r ".[\"${key}\"][\"${key2}\"]"`
        else
            value=`cat ${lsconfig_path} | jq -r ".[\"${key}\"]"`
        fi
    fi
    if [ "null" = "${value}" ]; then
        value=""
    fi
    echo ${value}
}

sync_hmac_key_dir() {
    local direction=$1

    local s3_mount_point=`get_lsconfig 's3_mount_point'`
    if [ ! -d /etc/hmac ] || [ ! -d "${s3_mount_point}/hmac" ]; then
        echo "target directory not found"
        exit 1
    fi

    if [ "down" = "${direction}" ]; then
        rsync -a "${s3_mount_point}/hmac/" /etc/hmac/
    else
        rsync -a /etc/hmac/ "${s3_mount_point}/hmac/"
    fi
}

sync_hmac_key_dir $@

exit 0

