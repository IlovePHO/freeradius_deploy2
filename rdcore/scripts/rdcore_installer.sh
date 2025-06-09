#!/usr/bin/env bash
set -xe

SCRIPT_DIR=$(cd $(dirname $0); pwd)

RDCORE_TAR_BZ2_NAME=$1
FREERADIUS_CONF_TAR_BZ2_NAME=$2
COMPOSER_UPDATE_TAR_BZ2_NAME=$3

RDS_SUBNET_IFNAME=$4
RDS_HOSTNAME=$5
RDS_PORT=$6
RDS_DB_NAME=$7
RDS_MASTERUSER_NAME=$8
RDS_MASTERUSER_PASSWORD=********
set +x
RDS_MASTERUSER_PASSWORD=$9
set -x
RDS_USER_NAME=${10}
RDS_USER_PASSWORD=********
set +x
RDS_USER_PASSWORD=${11}
set -x

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

get_radiusdesk_config_path() {
    local user=`get_default_user`
    echo "/home/${user}/.radiusdesk_config"
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

get_radiusdesk_config() {
    local key=$1
    local key2=$2

    local value
    local rdconfig_path=`get_radiusdesk_config_path`
    if [ -n "${rdconfig_path}" ] && [ -e ${rdconfig_path} ]; then
        if [ -n "${key2}" ]; then
            value=`cat ${rdconfig_path} | jq -r ".[\"${key}\"][\"${key2}\"]"`
        else
            value=`cat ${rdconfig_path} | jq -r ".[\"${key}\"]"`
        fi
    fi
    if [ "null" = "${value}" ]; then
        value=""
    fi
    echo ${value}
}

install_rd_core() {
    local rdcore_tar_bz2_name=$1
    local cwd=`pwd`

    if [ -e /var/www/rdcore ]; then
        return
    fi

    tar xfj /home/ubuntu/src/rdcore/${rdcore_tar_bz2_name} -C /var/www
    mv /var/www/rdcore* /var/www/rdcore

    cd /var/www/html
    sudo ln -s ../rdcore/rd ./rd
    sudo ln -s ../rdcore/cake3 ./cake3
    sudo ln -s ../rdcore/login ./login
    sudo ln -s ../rdcore/AmpConf/build/production/AmpConf ./conf_dev
    sudo ln -s ../rdcore/cake3/rd_cake/setup/scripts/reporting ./reporting

    sudo mkdir -p /var/www/html/cake3/rd_cake/logs
    sudo mkdir -p /var/www/html/cake3/rd_cake/webroot/files/imagecache
    sudo mkdir -p /var/www/html/cake3/rd_cake/tmp
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/tmp
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/logs
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/webroot/img/realms
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/webroot/img/dynamic_details
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/webroot/img/dynamic_photos
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/webroot/img/access_providers
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/webroot/img/hardwares
    sudo chown -R www-data. /var/www/html/cake3/rd_cake/webroot/files/imagecache

    sudo cp -R /var/www/html/rd/build/production/Rd/* /var/www/html/
    sudo cp /var/www/html/cake3/rd_cake/setup/cron/cron3 /etc/cron.d/

    cd ${cwd}
}

modify_local_mariadb() {
    mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql
    echo "create database rd;" | mysql -u root
    echo "GRANT ALL PRIVILEGES ON rd.* to 'rd'@'127.0.0.1' IDENTIFIED BY 'rd';" | mysql -u root
    echo "GRANT ALL PRIVILEGES ON rd.* to 'rd'@'localhost' IDENTIFIED BY 'rd';" | mysql -u root
    sudo mysql -u root rd < /var/www/html/cake3/rd_cake/setup/db/rd.sql        
}

modify_rds_mariadb() {
    local subnet_ifname=${RDS_SUBNET_IFNAME}
    local hostname=${RDS_HOSTNAME}
    local port=${RDS_PORT}
    local db_name=${RDS_DB_NAME}
    local masteruser_name=${RDS_MASTERUSER_NAME}
    local masteruser_password=********
    set +x
    masteruser_password=${RDS_MASTERUSER_PASSWORD}
    set -x
    local user_name=${RDS_USER_NAME}
    local user_password=********
    set +x
    user_password=${RDS_USER_PASSWORD}
    set -x

    local tmp_extra_file=`tempfile`
    # Suppresses log output of authentication information.
    echo "[client]" >> ${tmp_extra_file}
    echo "user = ${masteruser_name}" >> ${tmp_extra_file}
    set +x
    echo "password = ${masteruser_password}" >> ${tmp_extra_file}
    set -x
    echo "password = ********"
    echo "host = ${hostname}" >> ${tmp_extra_file}
    echo "port = ${port}" >> ${tmp_extra_file}

    local my_ipaddr=`ip -4 -j a s dev ${subnet_ifname} | jq -r '.[]["addr_info"]|.[]["local"]'`
    local internal_fqdn=`curl http://169.254.169.254/latest/meta-data/hostname`
    local mysql_connect_cmd="mysql --defaults-extra-file=${tmp_extra_file}"

    # Note: The time zone of RDS is UTC.
    #mysql_tzinfo_to_sql /usr/share/zoneinfo | ${mysql_connect_cmd}

    # Note: The database has already been created by RDS.
    #echo "create database ${db_name};" | ${mysql_connect_cmd}

    local check_user_sql="SELECT User FROM mysql.user WHERE mysql.user.Host = \"${my_ipaddr}\""
    local exist_user=`echo "${check_user_sql}" | ${mysql_connect_cmd} | wc -l`

    if [ "0" = "${exist_user}" ]; then
        # Add user for radiusdesk control.
        set +x
        echo "GRANT ALL PRIVILEGES ON ${db_name}.* to '${user_name}'@'${my_ipaddr}' IDENTIFIED BY '${user_password}';" | ${mysql_connect_cmd}
        echo "GRANT ALL PRIVILEGES ON ${db_name}.* to '${user_name}'@'${internal_fqdn}' IDENTIFIED BY '${user_password}';" | ${mysql_connect_cmd}
        set -x
        echo "GRANT ALL PRIVILEGES ON ${db_name}.* to '${user_name}'@'${my_ipaddr}' IDENTIFIED BY '********';"
        echo "GRANT ALL PRIVILEGES ON ${db_name}.* to '${user_name}'@'${internal_fqdn}' IDENTIFIED BY '********';"
    fi

    local check_tables_sql="USE ${db_name}; SHOW TABLES;"
    local exist_tables=`echo "${check_tables_sql}" | ${mysql_connect_cmd} | wc -l`

    if [ "0" = "${exist_tables}" ]; then
        # Add a table for radiusdesk.
        local radiusdesk_db_sql_path='/var/www/html/cake3/rd_cake/setup/db/rd.sql'
        local tmp_sql_file=`tempfile`

        cat ${radiusdesk_db_sql_path} | \
        sed -e "s/DEFINER=\`root\`@\`localhost\` //" > ${tmp_sql_file} 2>/dev/null
        ${mysql_connect_cmd} ${db_name} < ${tmp_sql_file}

        rm -f ${tmp_sql_file}
    fi

    rm -f ${tmp_extra_file}
}

generate_radiusdesk_config_radiusdesk_file_by_template() {
    local template_path=$1
    local output_path=$2

    local aes_key=********
    local freeradius_server_cn=`get_radiusdesk_config 'freeradius' 'server_cn'`
    local mc_pl_uuid=`get_radiusdesk_config 'mobileconfig' 'pl_uuid'`
    local mc_plid_prefix=`get_radiusdesk_config 'mobileconfig' 'plid_prefix'`
    local mc_ca_cert_name=`get_radiusdesk_config 'mobileconfig' 'ca_cert_name'`
    local mc_description=`get_radiusdesk_config 'mobileconfig' 'description'`
    local mc_payload_display_name=`get_radiusdesk_config 'mobileconfig' 'payload_display_name'`
    local win_carrier_id=`get_radiusdesk_config 'windows' 'carrier_id'`
    local win_subscriber_id=`get_radiusdesk_config 'windows' 'subscriber_id'`
    local win_author_id=`get_radiusdesk_config 'windows' 'author_id'`
    local win_trusted_root_ca_hash=`get_radiusdesk_config 'windows' 'trusted_root_ca_hash'`
    local win_signer_cert_pfx_password=********

    set +x
    aes_key=`get_radiusdesk_config 'aes_key'`
    win_signer_cert_pfx_password=`get_radiusdesk_config 'windows' 'signer_cert_pfx_password'`

    AES_KEY=${aes_key} \
    FREERADIUS_SERVER_CN=${freeradius_server_cn} \
    MOBILECONFIG_PL_UUID=${mc_pl_uuid} \
    MOBILECONFIG_PLID_PREFIX=${mc_plid_prefix} \
    MOBILECONFIG_CA_CERT_NAME=${mc_ca_cert_name} \
    MOBILECONFIG_DESCRIPTION=${mc_description} \
    MOBILECONFIG_PAYLOAD_DISPLAY_NAME=${mc_payload_display_name} \
    WINDOWS_CARRIER_ID=${win_carrier_id} \
    WINDOWS_SUBSCRIBER_ID=${win_subscriber_id} \
    WINDOWS_AUTHOR_ID=${win_author_id} \
    WINDOWS_TRUSTED_ROOT_CA_HASH=${win_trusted_root_ca_hash} \
    WINDOWS_SIGNER_CERT_PFX_PASSWORD=${win_signer_cert_pfx_password} \
    envsubst '$AES_KEY $FREERADIUS_SERVER_CN $MOBILECONFIG_PL_UUID $MOBILECONFIG_PLID_PREFIX $MOBILECONFIG_CA_CERT_NAME $MOBILECONFIG_DESCRIPTION $MOBILECONFIG_PAYLOAD_DISPLAY_NAME $WINDOWS_CARRIER_ID $WINDOWS_SUBSCRIBER_ID $WINDOWS_AUTHOR_ID $WINDOWS_TRUSTED_ROOT_CA_HASH $WINDOWS_SIGNER_CERT_PFX_PASSWORD' \
    < ${template_path} > ${output_path}

    set -x
}

generate_radiusdesk_config_app_file_by_template() {
    local template_path=$1
    local output_path=$2

    local hostname=${RDS_HOSTNAME}
    local port=${RDS_PORT}
    local db_name=${RDS_DB_NAME}
    local user_name=${RDS_USER_NAME}
    local user_password=********
    local security_salt=********
    set +x
    user_password=${RDS_USER_PASSWORD}
    security_salt=`get_radiusdesk_config 'security_salt'`

    RDS_HOSTNAME=${hostname} \
    RDS_PORT=${port} \
    RDS_DB_NAME=${db_name} \
    RDS_USER_NAME=${user_name} \
    RDS_USER_PASSWORD=${user_password} \
    SECURITY_SALT=${security_salt} \
    envsubst '$RDS_HOSTNAME $RDS_PORT $RDS_DB_NAME $RDS_USER_NAME $RDS_USER_PASSWORD $SECURITY_SALT' \
    < ${template_path} > ${output_path}
    set -x
}

generate_radiusdesk_config_softether_file_by_template() {
    local template_path=$1
    local output_path=$2

    local se_admin_password=********

    set +x
    se_admin_password=`get_radiusdesk_config 'se_admin_password'`

    SE_ADMIN_PASSWORD=${se_admin_password} \
    envsubst '$SE_ADMIN_PASSWORD' \
    < ${template_path} > ${output_path}

    set -x
}

check_and_replace_config_file() {
    local new_file=$1
    local target_file=$2
    local owner=$3

    # Replace file if necessary.
    set +e
    diff "${new_file}" "${target_file}" > /dev/null 2>&1
    local diff_ret=$?
    set -e
    if [ "0" != "${diff_ret}" ]; then
        chmod 664 ${new_file}
        if [ "" != "${owner}" ]; then
            chown ${owner} ${new_file}
        fi
        mv ${new_file} ${target_file}
    else
        rm -f ${new_file}
    fi
}

modify_radiusdesk_config() {
    local radiusdesk_config_path='/var/www/html/cake3/rd_cake/config/RadiusDesk.php'
    local radiusdesk_template_path=`realpath "${SCRIPT_DIR}/../rdcore/templates/RadiusDesk.php.template"`
    local tmp_config_file=`tempfile`

    # Generate RadiusDesk.php.
    generate_radiusdesk_config_radiusdesk_file_by_template "${radiusdesk_template_path}" "${tmp_config_file}"

    # Replace config if necessary.
    check_and_replace_config_file "${tmp_config_file}" "${radiusdesk_config_path}"

    local softether_config_path='/var/www/html/cake3/rd_cake/config/SoftEther.php'
    local softether_template_path=`realpath "${SCRIPT_DIR}/../rdcore/templates/SoftEther.php.template"`
    tmp_config_file=`tempfile`

    # Generate SoftEther.php.
    generate_radiusdesk_config_softether_file_by_template "${softether_template_path}" "${tmp_config_file}"

    # Replace config if necessary.
    check_and_replace_config_file "${tmp_config_file}" "${softether_config_path}"
}

modify_radiusdesk_db_config() {
    local db_config_path='/var/www/html/cake3/rd_cake/config/app.php'
    local app_php_template_path=`realpath "${SCRIPT_DIR}/../rdcore/templates/app.php.template"`
    local tmp_config_file=`tempfile`

    # Generate app.php with embedded RDS connection information.
    generate_radiusdesk_config_app_file_by_template "${app_php_template_path}" "${tmp_config_file}"

    # Replace app.php if necessary.
    check_and_replace_config_file "${tmp_config_file}" "${db_config_path}"

    local phinx_yml_path='/var/www/html/cake3/rd_cake/phinx.yml'
    local phinx_yml_template_path=`realpath "${SCRIPT_DIR}/../rdcore/templates/phinx.yml.template"`
    local tmp_config_file2=`tempfile`

    # Generate phinx.yml with embedded RDS connection information.
    generate_radiusdesk_config_app_file_by_template "${phinx_yml_template_path}" "${tmp_config_file2}"

    # Replace phinx.yml if necessary.
    check_and_replace_config_file "${tmp_config_file2}" "${phinx_yml_path}"
}

prepare_hmac_key_dir() {
    mkdir -p　/etc/hmac 

    local s3_mount_point=`get_lsconfig 's3_mount_point'`
    if [ -n "${s3_mount_point}" ] &&  [ -d "${s3_mount_point}" ]; then
        if [ ! -d "${s3_mount_point}/hmac" ]; then
            mkdir -p "${s3_mount_point}/hmac"
        else
            rsync -a "${s3_mount_point}/hmac/" /etc/hmac/
        fi
    fi

    chmod 755 /etc/hmac
    chown -R www-data. /etc/hmac
}

modify_radiusdesk_cert() {
    mkdir -p /etc/certs/s3

    local cert_dir=`realpath "${SCRIPT_DIR}/../rdcore/certs"`

    local signer_chain=`get_radiusdesk_config 'mobileconfig' 'signer_chain'`
    local signer_cert=`get_radiusdesk_config 'mobileconfig' 'signer_cert'`
    local signer_key=`get_radiusdesk_config 'mobileconfig' 'signer_key'`
    local signer_cert_pfx=`get_radiusdesk_config 'windows' 'signer_cert_pfx'`

    if [ -n "${signer_chain}" ] && [ -f "${cert_dir}/${signer_chain}" ]; then
        cp ${cert_dir}/${signer_chain} /etc/certs/s3/signer_chain.pem
    fi
    if [ -n "${signer_cert}" ] && [ -f "${cert_dir}/${signer_cert}" ]; then
        cp ${cert_dir}/${signer_cert} /etc/certs/s3/signer_cert.pem
    fi
    if [ -n "${signer_key}" ] && [ -f "${cert_dir}/${signer_key}" ]; then
        cp ${cert_dir}/${signer_key} /etc/certs/s3/signer_privkey.pem
    fi
    if [ -n "${signer_cert_pfx}" ] && [ -f "${cert_dir}/${signer_cert_pfx}" ]; then
        cp ${cert_dir}/${signer_cert_pfx} /etc/certs/s3/signer_cert.pfx
    fi
}

modify_mariadb() {
    modify_rds_mariadb
    modify_radiusdesk_db_config
}

modify_rlm_sql_config() {
    local rlm_sql_path='/etc/freeradius/mods-available/sql'
    local rlm_sql_template_path=`realpath "${SCRIPT_DIR}/../freeradius/templates/sql.template"`
    local tmp_config_file=`tempfile`

    # Generate app.php with embedded RDS connection information.
    generate_radiusdesk_config_app_file_by_template "${rlm_sql_template_path}" "${tmp_config_file}"

    # Replace app.php if necessary.
    check_and_replace_config_file "${tmp_config_file}" "${rlm_sql_path}" "freerad."
}

modify_freeradius() {
    local freeradius_conf_tar_bz2_name=$1
    local cwd=`pwd`

    mv /etc/freeradius /etc/freeradius.orig
    tar xfj /home/ubuntu/src/freeradius/configs/${freeradius_conf_tar_bz2_name} --one-top-level=/etc/freeradius/
    mv /etc/freeradius/freeradius*/* /etc/freeradius/
    rm -rf /etc/freeradius/freeradius*
    cd /etc/freeradius
    chown -R freerad. /etc/freeradius/
    mkdir -p /var/run/freeradius
    chown freerad. /var/run/freeradius

    cd ${cwd}

    if [ "" != "${RDS_HOSTNAME}" ]; then
        modify_rlm_sql_config
    fi
}

modify_freeradius_default_config() {
    local aes_key=********
    set +x
    aes_key=`get_radiusdesk_config 'aes_key'`

    local aes_key_configured=`cat /etc/default/freeradius | grep "AES_KEY" | wc -l`
    if [ "${aes_key_configured}" = "0" ]; then
        echo "AES_KEY=\"${aes_key}\"" >> /etc/default/freeradius
    fi
    set -x
}

modify_freeradius_cert() {
    local cert_dir=`realpath "${SCRIPT_DIR}/../freeradius/certs"`

    local ca_cert=`get_radiusdesk_config 'freeradius' 'ca_cert'`
    local server_cert=`get_radiusdesk_config 'freeradius' 'server_cert'`
    local client_cert=`get_radiusdesk_config 'freeradius' 'client_cert'`
    local dh=`get_radiusdesk_config 'freeradius' 'dh'`

    if [ -n "${ca_cert}" ] && [ -f "${cert_dir}/${ca_cert}" ]; then
        cp ${cert_dir}/${ca_cert} /etc/freeradius/certs/ca.pem 
    fi
    if [ -n "${server_cert}" ] && [ -f "${cert_dir}/${server_cert}" ]; then
        cp ${cert_dir}/${server_cert} /etc/freeradius/certs/server.pem 
    fi
    if [ -n "${client_cert}" ] && [ -f "${cert_dir}/${client_cert}" ]; then
        cp ${cert_dir}/${client_cert} /etc/freeradius/certs/client.pem 
    fi
    if [ -n "${dh}" ] && [ -f "${cert_dir}/${dh}" ]; then
        cp ${cert_dir}/${dh} /etc/freeradius/certs/dh
    fi
}

modify_config() {
    local cwd=`pwd`

    cd /
    patch -p1 < /home/ubuntu/src/modify_config.patch

    cd ${cwd}
}

modify_mariadb_by_phinx() {
    local cwd=`pwd`

    cd /var/www/html/cake3/rd_cake
    vendor/bin/phinx migrate

    cd ${cwd}
}

update_composer_by_tarball() {
    local composer_update_tar_bz2_name=$1
    local cwd=`pwd`

    if [ -n "${composer_update_tar_bz2_name}" ] && [ -e "/home/ubuntu/src/rdcore/composer/${composer_update_tar_bz2_name}" ]; then
        cd /var/www/html/cake3/rd_cake
        tar xfj /home/ubuntu/src/rdcore/composer/${composer_update_tar_bz2_name} --one-top-level=/var/www/html/cake3/rd_cake
        chown -R root. /var/www/html/cake3/rd_cake/composer-package-update-*/
        rsync -a /var/www/html/cake3/rd_cake/composer-package-update-*/ ./
        rm -rf /var/www/html/cake3/rd_cake/composer-package-update-*/
    fi

    cd ${cwd}
}

install_composer_package() {
    local cwd=`pwd`

    cd /var/www/html/cake3/rd_cake

    mkdir -p /var/www/.cache/composer/
    chown www-data. /var/www/.cache/composer/
    chown www-data. /var/www/html/cake3/rd_cake/composer.*
    chown -R www-data. /var/www/html/cake3/rd_cake/vendor/
    sudo -u www-data php vendor/bin/composer require google/apiclient:^2.12.1 --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-bcmath
    sudo -u www-data php vendor/bin/composer require phpseclib/mcrypt_compat --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-bcmath
    chown root. /var/www/html/cake3/rd_cake/composer.*
    chown -R root. /var/www/.cache/composer/
    chown -R root. /var/www/html/cake3/rd_cake/vendor/

    cd ${cwd}
}

generate_nginx_config_file_by_template() {
    local template_path=$1
    local output_path=$2

    local server_name=`get_radiusdesk_config 'nginx' 'server_name'`

    SERVER_NAME=${server_name} \
    envsubst '$SERVER_NAME' \
    < ${template_path} > ${output_path}
}

modify_nginx_config_and_cert() {
    local config_path='/etc/nginx/sites-available/default'
    local template_path=`realpath "${SCRIPT_DIR}/../nginx/templates/default.template"`
    local tmp_config_file=`tempfile`

    local enable_ssl=`get_radiusdesk_config 'nginx' 'enable_ssl'`
    if [ "1" = "${enable_ssl}" ]; then
        local ssl_cert_dir=`realpath "${SCRIPT_DIR}/../nginx/certs"`

        local ssl_cert=`get_radiusdesk_config 'nginx' 'ssl_cert'`
        local ssl_cert_key=`get_radiusdesk_config 'nginx' 'ssl_cert_key'`
        local ssl_dhparam=`get_radiusdesk_config 'nginx' 'ssl_dhparam'`
        local ssl_include=`get_radiusdesk_config 'nginx' 'ssl_include'`
        local ssl_ok=0

        if [ -n "${ssl_cert}" ] && [ -f "${ssl_cert_dir}/${ssl_cert}" ] && \
           [ -n "${ssl_cert_key}" ] && [ -f "${ssl_cert_dir}/${ssl_cert_key}" ] && \
           [ -n "${ssl_dhparam}" ] && [ -f "${ssl_cert_dir}/${ssl_dhparam}" ]; then

            mkdir -p /etc/nginx/certs
            cp ${ssl_cert_dir}/${ssl_cert} /etc/nginx/certs/server.pem
            cp ${ssl_cert_dir}/${ssl_cert_key} /etc/nginx/certs/server.key
            cp ${ssl_cert_dir}/${ssl_dhparam} /etc/nginx/certs/ssl-dhparams.pem

            if [ -n "${ssl_include}" ] && [ -f "${ssl_cert_dir}/${ssl_include}" ]; then
                cp ${ssl_cert_dir}/${ssl_include} /etc/nginx/certs/options-ssl-nginx.conf
            else
                touch /etc/nginx/certs/options-ssl-nginx.conf
            fi
            ssl_ok=1
        fi

        if [ "1" = "${ssl_ok}" ]; then
            template_path=`realpath "${SCRIPT_DIR}/../nginx/templates/default_ssl.template"`
        fi
    fi

    # Generate config
    generate_nginx_config_file_by_template "${template_path}" "${tmp_config_file}"

    # Replace config if necessary.
    check_and_replace_config_file "${tmp_config_file}" "${config_path}"
}

if [ "" = "${RDS_HOSTNAME}" ]; then
    echo "RDS_HOSTNAME not found"
    exit 1
fi

install_rd_core ${RDCORE_TAR_BZ2_NAME}
modify_radiusdesk_config
modify_mariadb
modify_freeradius ${FREERADIUS_CONF_TAR_BZ2_NAME}
modify_config
modify_freeradius_default_config
modify_mariadb_by_phinx
update_composer_by_tarball ${COMPOSER_UPDATE_TAR_BZ2_NAME}
install_composer_package
prepare_hmac_key_dir
modify_radiusdesk_cert
modify_freeradius_cert
modify_nginx_config_and_cert

exit 0

