#!/usr/bin/env bash
set -xe

SCRIPT_DIR=$(cd $(dirname $0); pwd)
CRON_TASK='* * * * * /usr/local/sbin/update_freeradius_proxy_conf.pl'

install_scripts() {
    if [ ! -e "/usr/local/sbin/update_freeradius_proxy_conf.pl" ]; then
        cp ${SCRIPT_DIR}/update_freeradius_proxy_conf.pl /usr/local/sbin/
        chown root. /usr/local/sbin/update_freeradius_proxy_conf.pl
    fi
}

install_cron_tasks() {
    local original_ifs=${IFS}
    local temp_file=`mktemp`
    local is_exist=0

    set +e
    crontab -l > ${temp_file}
    set -e

    IFS=$'\n'
    for line in `cat ${temp_file}`; do
        if [ "${line}" = "${CRON_TASK}" ]; then
            is_exist=1
        fi
    done

    if [ ${is_exist} -eq 0 ]; then
        echo "${CRON_TASK}" >> ${temp_file}
        bash -c "crontab ${temp_file}"
    fi

    rm -f ${temp_file}
    IFS=${original_ifs}
}

install_scripts
install_cron_tasks
