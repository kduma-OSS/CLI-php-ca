#!/usr/bin/env bash

ensure_key() {
    local ca_file="$1"

    printf "[%s] Checking if 'ca' private key exists: " "$ca_file"
    if ! ../php-ca key:exists ca --ca="$ca_file" --quiet; then
        echo "no"
        printf "[%s] Generating new private key: " "$ca_file"
        if ! ../php-ca key:create ca --ca="$ca_file"; then
            echo "failed"
            return 1
        else
            echo "generated"
        fi
    else
        echo "yes"
    fi
}

ensure_key root-ca.json || exit 1



ensure_key sub-ca.json || exit 1



ensure_key int-ca.json || exit 1
