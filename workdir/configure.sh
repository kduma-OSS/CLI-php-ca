#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP_CA="$(cd "$SCRIPT_DIR/.." && pwd)/src/cli/php-ca"

# ─── Helpers ──────────────────────────────────────────────────

config_for() {
    echo "$SCRIPT_DIR/$1/php-ca-config.json"
}

ensure_config() {
    local dir="$1"

    if [ -f "$(config_for "$dir")" ]; then
        printf "[%s] Config: exists\n" "$dir"
        return 0
    fi

    mkdir -p "$SCRIPT_DIR/$dir"

    cat > "$(config_for "$dir")" <<EOF
{
    "adapter": {
        "type": "directory",
        "path": "$SCRIPT_DIR/$dir/data"
    }
}
EOF
    printf "[%s] Config: created\n" "$dir"
}

ensure_ca_init() {
    local dir="$1"
    local dn="$2"
    local config="$(config_for "$dir")"
    shift 2

    # ca:init checks internally and aborts if already initialized
    local result=0
    $PHP_CA ca:init \
        --ca-config-file="$config" \
        --non-interactive \
        --dn="$dn" \
        "$@" || result=$?

    if [ $result -eq 0 ]; then
        printf "[%s] CA init: done\n" "$dir"
    else
        printf "[%s] CA init: already initialized (skipped)\n" "$dir"
    fi
}

ensure_signed_ca_certificate() {
    local child_dir="$1"
    local parent_dir="$2"
    local template="$3"

    local child_config="$(config_for "$child_dir")"
    local parent_config="$(config_for "$parent_dir")"

    # Check if child already has a CA certificate with id "ca"
    if $PHP_CA ca:show ca --ca-config-file="$child_config" >/dev/null 2>&1; then
        printf "[%s] CA certificate: already installed\n" "$child_dir"
        return 0
    fi

    local tmp_csr=$(mktemp)
    local tmp_cert=$(mktemp)

    printf "[%s] Getting CSR...\n" "$child_dir"
    $PHP_CA ca:csr:get ca --ca-config-file="$child_config" > "$tmp_csr"

    # Check if CSR already imported in parent
    if $PHP_CA csr:show "${child_dir}-csr" --ca-config-file="$parent_config" >/dev/null 2>&1; then
        printf "[%s] CSR from %s: already imported\n" "$parent_dir" "$child_dir"
    else
        printf "[%s] Importing CSR from %s...\n" "$parent_dir" "$child_dir"
        $PHP_CA csr:import "$tmp_csr" --id="${child_dir}-csr" --ca-config-file="$parent_config"
    fi

    printf "[%s] Issuing certificate using template '%s'...\n" "$parent_dir" "$template"
    local cert_id
    cert_id=$($PHP_CA certificate:issue:csr "${child_dir}-csr" \
        --template="$template" \
        --ca-config-file="$parent_config" 2>&1 | tail -1)

    printf "[%s] Exporting certificate %s...\n" "$parent_dir" "$cert_id"
    $PHP_CA certificate:get "$cert_id" --ca-config-file="$parent_config" > "$tmp_cert"

    printf "[%s] Importing signed certificate...\n" "$child_dir"
    $PHP_CA ca:import "$tmp_cert" --id=ca --activate --ca-config-file="$child_config"

    rm -f "$tmp_csr" "$tmp_cert"
    printf "[%s] CA certificate: installed\n" "$child_dir"
}

set_chain() {
    local dir="$1"
    shift
    # Remaining args are ancestor CA dirs in order (immediate parent first, root last)
    local ancestors=("$@")

    local config="$(config_for "$dir")"
    local tmp_chain=$(mktemp)

    # Build chain PEM by concatenating ancestor CA certs
    for ancestor in "${ancestors[@]}"; do
        $PHP_CA ca:get ca --ca-config-file="$(config_for "$ancestor")" >> "$tmp_chain" 2>/dev/null
    done

    printf "[%s] Setting chain (%s)...\n" "$dir" "${ancestors[*]}"
    $PHP_CA ca:set-chain ca "$tmp_chain" --ca-config-file="$config"

    rm -f "$tmp_chain"
}

export_der() {
    local dir="$1"
    local config="$(config_for "$dir")"
    local output="$SCRIPT_DIR/$dir/ca.der"

    printf "[%s] Exporting DER to %s...\n" "$dir" "$output"
    $PHP_CA ca:get:der ca --stdout --ca-config-file="$config" > "$output"
}

# ─── Setup ────────────────────────────────────────────────────

echo "=== Setting up test CA hierarchy ==="
echo ""

# 1. Simple test CA (self-signed, small key, short validity)
echo "--- test-ca ---"
ensure_config "test-ca"
ensure_ca_init "test-ca" "CN=Test CA" --root-ca --validity=P1Y --key-size=1024
export_der "test-ca"
echo ""

# 2. Root CA (self-signed, production-like)
echo "--- root-ca ---"
ensure_config "root-ca"
ensure_ca_init "root-ca" "C=WW, O=PHP PKI CA Project, CN=My Root CA" --root-ca --validity=P25Y --key-size=4096
export_der "root-ca"
echo ""

# 3. Sub CA (signed by root-ca)
echo "--- sub-ca ---"
ensure_config "sub-ca"
ensure_ca_init "sub-ca" "C=WW, O=PHP PKI CA Project, CN=My Sub CA" --key-size=2048
ensure_signed_ca_certificate "sub-ca" "root-ca" "subordinate-ca"
set_chain "sub-ca" "root-ca"
export_der "sub-ca"
echo ""

# 4. Intermediate CA (signed by sub-ca)
echo "--- int-ca ---"
ensure_config "int-ca"
ensure_ca_init "int-ca" "C=WW, O=PHP PKI CA Project, CN=My Intermediate CA" --key-size=1024
ensure_signed_ca_certificate "int-ca" "sub-ca" "intermediate-ca"
set_chain "int-ca" "sub-ca" "root-ca"
export_der "int-ca"
echo ""

echo "=== Done ==="
echo ""
echo "CA hierarchy:"
echo "  test-ca     (self-signed)"
echo "  root-ca     (self-signed root)"
echo "    └── sub-ca    (signed by root-ca)"
echo "          └── int-ca  (signed by sub-ca)"
