#!/usr/bin/env bash
set -xe
      
SWAP_FILE=/swapfile1

if [ ! -f ${SWAP_FILE} ]; then
    dd if=/dev/zero of=${SWAP_FILE} bs=1M count=1024
    chmod 600 ${SWAP_FILE}
    mkswap ${SWAP_FILE}
    swapon ${SWAP_FILE}
elif [ "0" = `swapon -s | wc -l` ]; then
    swapon ${SWAP_FILE}
fi