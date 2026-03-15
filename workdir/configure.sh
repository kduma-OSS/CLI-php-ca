#!/usr/bin/env bash

ensure_key() {
    local ca_file="$1"
    local key_size="$2"

    printf "[%s] Checking if 'ca' private key exists: " "$ca_file"
    if ! ../php-ca key:exists ca --ca="$ca_file" --quiet; then
        echo "no"
        printf "[%s] Generating new private key: " "$ca_file"
        if ! ../php-ca key:create ca --ca="$ca_file" --key-size="$key_size" --decrypted; then
            echo "failed"
            return 1
        else
            echo "generated"
        fi
    else
        echo "yes"
    fi
}

ensure_self_signed_ca() {
    local ca_file="$1"
    local distinguished_name="$2"
    local validity="$3"

    printf "[%s] Checking if CA has a certificate: " "$ca_file"
    if ! ../php-ca authority:certificate:exists --ca="$ca_file" --quiet; then
        echo "no"
        printf "[%s] Generating new self-signed Root CA certificate: " "$ca_file"
        if ! ../php-ca authority:certificate:self-signed ca "$distinguished_name" --ca="$ca_file" --validity="$validity"; then
            echo "failed"
            return 1
        else
            echo "generated"
        fi
    else
        echo "yes"
    fi
}

ensure_csr_for_ca() {
    local ca_file="$1"
    local distinguished_name="$2"

    printf "[%s] Checking if CSR exists: " "$ca_file"
    if ! ../php-ca authority:csr:exists --ca="$ca_file" --quiet; then
        echo "no"
        printf "[%s] Creating new CSR: " "$ca_file"
        if ! ../php-ca authority:csr:create ca "$distinguished_name" --ca="$ca_file" --ignore-existing-certificate; then
            echo "failed"
            return 1
        else
            echo "created"
        fi
    else
        echo "yes"
    fi
}

ensure_signed_ca_certificate() {
    local ca_file="$1"
    local signing_ca_file="$2"
    local template="$3"

    printf "[%s] Checking if CA has a certificate: " "$ca_file"
    if ! ../php-ca authority:certificate:exists --ca="$ca_file" --quiet; then
        echo "no"
        printf "[%s] Issuing certificate signed by [%s] using template [%s]: " "$ca_file" "$signing_ca_file" "$template"
        if ! ../php-ca authority:csr:get --ca="$ca_file" \
            | ../php-ca certificate:issue:csr "$template" --ca="$signing_ca_file" --force \
            | ../php-ca certificate:get --ca="$signing_ca_file" \
            | ../php-ca authority:certificate:import --ca="$ca_file"; then
            echo "failed"
            return 1
        else
            echo "done"
        fi
    else
        echo "yes"
    fi
}

ensure_key php-pki-config.json 1024 || exit 1
ensure_self_signed_ca php-pki-config.json "CN=Test CA" "+1 month" || exit 1

ensure_key root-ca.json 4096 || exit 1
ensure_self_signed_ca root-ca.json "C=WW, O=PHP PKI CA Project, CN=My Root CA" "+25 years" || exit 1

ensure_key sub-ca.json 2048 || exit 1
ensure_csr_for_ca sub-ca.json "C=WW, O=PHP PKI CA Project, CN=My Sub CA" || exit 1
ensure_signed_ca_certificate sub-ca.json root-ca.json subordinary-ca || exit 1

ensure_key int-ca.json 1024 || exit 1
ensure_csr_for_ca int-ca.json "C=WW, O=PHP PKI CA Project, CN=My Intermediate CA" || exit 1
ensure_signed_ca_certificate int-ca.json sub-ca.json intermediate-ca || exit 1
