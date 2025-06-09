#!/bin/bash -xe

SCRIPT_DIR=$(cd $(dirname $0); pwd)

install_softether() {
    local softether_tag=$1
    local softether_tar_bz2_name=$2
    local cwd=`pwd`

    if [ ! -e /home/ec2-user/src/SoftEtherVPN ]; then
        mkdir -p /home/ec2-user/src
        cd /home/ec2-user/src

        # Get SoftEther source from github.
        # Note: Required for git submodule command
        if [ "${softether_tag}" != "" ]; then
             -b ${softether_tag} --depth 1
             git clone https://github.com/SoftEtherVPN/SoftEtherVPN.git
        else
            git clone https://github.com/SoftEtherVPN/SoftEtherVPN.git --depth 1
        fi

        # Overwrite source with tarball.
        local tmp_dir=`mktemp -d`
        tar xfj /home/ec2-user/src/${softether_tar_bz2_name} -C ${tmp_dir}
        mv ${tmp_dir}/softether* /home/ec2-user/src/SoftEtherVPN2
        rmdir --ignore-fail-on-non-empty ${tmp_dir}
        rsync -a SoftEtherVPN2/ SoftEtherVPN/
        rm -rf SoftEtherVPN2/

        cd /home/ec2-user/src/SoftEtherVPN
        sudo git submodule init
    fi

    # Build and install SoftEther source.
    cd /home/ec2-user/src/SoftEtherVPN
    sudo git submodule update
    sudo ./configure
    sudo make -C build
    sudo make -C build install

    # Generate cache of shared object.
    echo "/usr/local/lib64" > /etc/ld.so.conf.d/softether.conf
    ldconfig

    cd ${cwd}
}

install_softether $@

exit 0

