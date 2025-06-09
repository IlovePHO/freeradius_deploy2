#!/bin/bash -xe

SCRIPT_DIR=$(cd $(dirname $0); pwd)
USER_KEY_TABLE_PATH=${SCRIPT_DIR}/user_key_table.csv
PUBKEY_DIR=${SCRIPT_DIR}/pubkeys
OS=$(grep "^ID=" /etc/os-release | sed -e "s/ID=//")

if [ ! -e "${USER_KEY_TABLE_PATH}" ]; then
    exit
fi

while read line; do
    user=`echo ${line} | cut -d , -f 1`
    pubkey=`echo ${line} | cut -d , -f 2`
    echo ${user}
    echo ${pubkey}

    if [ -z "${user}" ] || [ -z "${pubkey}" ] || [ ! -e "${PUBKEY_DIR}/${pubkey}" ]; then
        continue
    fi

    set +e
    id ${user} >/dev/null 2>&1
    ret_id=$?
    set -e
    if [ ${ret_id} -eq 0 ]; then
        continue
    fi

    case "${OS}" in
        ubuntu*)
            adduser --disabled-password --gecos "" ${user}
            echo "${user}:${user}" | chpasswd
            gpasswd -a "${user}" sudo
            ;;
        *rhel*)
            adduser ${user}
            echo "${user}:${user}" | chpasswd
            gpasswd -a "${user}" wheel
            ;;
        *)
            break
            ;;
    esac

    sudo -u ${user} mkdir -p /home/${user}/.ssh
    sudo -u ${user} chmod 700 /home/${user}/.ssh
    sudo -u ${user} touch /home/${user}/.ssh/authorized_keys
    sudo -u ${user} chmod 600 /home/${user}/.ssh/authorized_keys
    cat "${PUBKEY_DIR}/${pubkey}" >> /home/${user}/.ssh/authorized_keys
done < ${USER_KEY_TABLE_PATH}

