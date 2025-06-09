#!/usr/bin/env bash
set -xe

SCRIPT_DIR=$(cd $(dirname $0); pwd)
CRON_TASK='*/10 * * * * /usr/local/sbin/sync_hmac_key.sh down'

install_scripts() {
    if [ ! -e "/usr/local/sbin/sync_hmac_key.sh" ]; then
        cp ${SCRIPT_DIR}/sync_hmac_key.sh /usr/local/sbin/
        chown root. /usr/local/sbin/sync_hmac_key.sh
    fi
}

install_cron_tasks() {
    local original_ifs=${IFS}
    local temp_file=`mktemp`
    local is_exist=0

    local cron_task=${CRON_TASK}

    set +e
    crontab -l > ${temp_file}
    set -e

    IFS=$'\n'
    for line in `cat ${temp_file}`; do
        if [ "${line}" = "${cron_task}" ]; then
            is_exist=1
        fi
    done

    if [ ${is_exist} -eq 0 ]; then
        echo "${cron_task}" >> ${temp_file}
        bash -c "crontab ${temp_file}"
    fi

    rm -f ${temp_file}
    IFS=${original_ifs}
}

install_scripts
install_cron_tasks
