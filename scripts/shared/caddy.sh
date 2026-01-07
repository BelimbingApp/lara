#!/bin/bash

# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong

# Source colors if not already loaded
CADDY_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -z "$RED" ]; then
    source "$CADDY_SCRIPT_DIR/colors.sh"
fi

# Source validation utilities
if ! command -v command_exists &> /dev/null; then
    source "$CADDY_SCRIPT_DIR/validation.sh"
fi

# Install Caddy if needed
install_caddy() {
    echo -e "${YELLOW}${INFO_MARK} Installing Caddy...${NC}"

    local os_type
    os_type=$(detect_os)

    case "$os_type" in
        macos)
            if command_exists brew; then
                brew install caddy
            else
                echo -e "${RED}${CROSS_MARK} Homebrew required${NC}"
                return 1
            fi
            ;;
        linux|wsl2)
            if command_exists apt-get; then
                sudo apt-get update -qq
                sudo apt-get install -y -qq debian-keyring debian-archive-keyring apt-transport-https curl
                curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' 2>/dev/null | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
                curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' 2>/dev/null | sudo tee /etc/apt/sources.list.d/caddy-stable.list >/dev/null
                sudo apt-get update -qq
                sudo apt-get install -y -qq caddy
            else
                echo -e "${YELLOW}Installing from binary...${NC}"
                curl -o caddy.tar.gz "https://caddyserver.com/api/download?os=linux&arch=amd64" 2>/dev/null
                tar -xzf caddy.tar.gz caddy
                sudo mv caddy /usr/local/bin/
                sudo chmod +x /usr/local/bin/caddy
                rm caddy.tar.gz
            fi
            ;;
        *)
            echo -e "${RED}${CROSS_MARK} OS not supported${NC}"
            return 1
            ;;
    esac

    echo -e "${GREEN}${CHECK_MARK}${NC} Caddy installed"
    return 0
}

# Check for proxy conflicts before starting Caddy
# Automatically finds available port if default is in use
# Returns available port via stdout, returns 1 if no ports available
check_proxy_conflicts() {
    local preferred_https_port=$1
    local existing_proxy
    existing_proxy=$(detect_proxy)

    # Check if preferred port is available
    if is_port_available "$preferred_https_port"; then
        echo "$preferred_https_port"
        return 0
    fi

    # Port is in use - find alternative
    if [ "$existing_proxy" != "none" ]; then
        echo -e "${CYAN}ℹ${NC} Port $preferred_https_port is in use by ${CYAN}$existing_proxy${NC}"
    else
        echo -e "${CYAN}ℹ${NC} Port $preferred_https_port is in use"
    fi

    # Try to find alternative port
    local alternative_port
    alternative_port=$preferred_https_port
    local attempts=0
    while [ $attempts -lt 10 ] && ! is_port_available "$alternative_port"; do
        ((alternative_port++))
        ((attempts++))
    done

    if is_port_available "$alternative_port"; then
        echo -e "${CYAN}ℹ${NC} Using alternative port: ${CYAN}$alternative_port${NC}"
        echo "$alternative_port"
        return 0
    fi

    # No ports available
    echo -e "${YELLOW}${WARNING_MARK} No available ports found in range [$preferred_https_port-$alternative_port]${NC}"
    echo "$preferred_https_port"  # Return preferred anyway for error handling
    return 1
}

# Ensure Caddy binary can bind to privileged ports (e.g., 443)
ensure_caddy_privileges() {
    if ! command -v caddy >/dev/null 2>&1; then
        echo -e "${RED}${CROSS_MARK}${NC} Caddy binary not found"
        return 1
    fi

    local caddy_path
    caddy_path="$(command -v caddy)"

    if command -v getcap >/dev/null 2>&1; then
        local current_caps
        current_caps="$(getcap "$caddy_path" 2>/dev/null || true)"
        if [[ "$current_caps" == *cap_net_bind_service* ]]; then
            return 0
        fi
    fi

    echo -e "${CYAN}${INFO_MARK} Granting Caddy permission to bind privileged ports...${NC}"
    if sudo setcap 'cap_net_bind_service=+ep' "$caddy_path"; then
        echo -e "${GREEN}${CHECK_MARK}${NC} Caddy can now bind to port 443"
        return 0
    fi

    echo -e "${RED}${CROSS_MARK}${NC} Failed to grant Caddy permission. Run:"
    echo -e "  ${YELLOW}sudo setcap 'cap_net_bind_service=+ep' $caddy_path${NC}"
    return 1
}

# Create Caddyfile for environment
create_caddyfile() {
    local project_root=$1
    local app_env=$2
    local frontend_domain=$3
    local backend_domain=$4
    local frontend_port=$5
    local backend_port=$6
    local https_port=$7

    local caddy_file="$project_root/Caddyfile.$app_env"
    local admin_socket="unix//tmp/caddy-blb-$app_env-$$.sock"

    local frontend_addr backend_addr
    if [ "$https_port" = "443" ]; then
        frontend_addr="https://$frontend_domain"
        backend_addr="https://$backend_domain"
    else
        frontend_addr="https://$frontend_domain:$https_port"
        backend_addr="https://$backend_domain:$https_port"
    fi

    cat > "$caddy_file" << EOF
{
    auto_https off
    admin $admin_socket
}

$frontend_addr {
    tls certs/$frontend_domain.pem certs/$frontend_domain-key.pem
    @vite_assets path /build/* /assets/*
    handle @vite_assets {
        reverse_proxy 127.0.0.1:$frontend_port
    }
    handle {
        reverse_proxy 127.0.0.1:$backend_port
    }
}

$backend_addr {
    tls certs/$frontend_domain.pem certs/$frontend_domain-key.pem
    reverse_proxy 127.0.0.1:$backend_port
}
EOF

    echo "$caddy_file"
}

# Clean up Caddy temporary files and sockets
cleanup_caddy_artifacts() {
    local app_env=$1

    # Clean up temporary Caddyfile
    [ -f "Caddyfile.$app_env" ] && rm -f "Caddyfile.$app_env"

    # Clean up Caddy admin sockets
    rm -f /tmp/caddy-blb-$app_env-*.sock 2>/dev/null || true
}

# Check if Caddy proxy is enabled
# Usage: is_caddy_enabled "skip_caddy_value" "proxy_type_value"
is_caddy_enabled() {
    local skip_caddy=${1:-"$SKIP_CADDY"}
    local proxy_type=${2:-"$PROXY_TYPE"}
    [ "$skip_caddy" != "true" ] && [ "$proxy_type" = "caddy" ]
}

# Start Caddy reverse proxy
start_caddy_proxy() {
    local project_root=$1
    local app_env=$2
    local frontend_domain=$3
    local backend_domain=$4
    local frontend_port=$5
    local backend_port=$6
    local https_port=$7
    local logs_dir=$8

    # Install Caddy if needed
    if ! command_exists caddy; then
        install_caddy || return 1
    fi

    ensure_caddy_privileges || return 1

    local caddy_file
    caddy_file=$(create_caddyfile \
        "$project_root" \
        "$app_env" \
        "$frontend_domain" \
        "$backend_domain" \
        "$frontend_port" \
        "$backend_port" \
        "$https_port")

    caddy run --config "$caddy_file" > "$logs_dir/caddy.log" 2>&1 &
    echo $!
}

# Start Caddy with conflict checking and return PID
# This is the main entry point for starting Caddy from start-app.sh
# Automatically handles port conflicts by finding available ports
# Returns PID on success, returns 1 on failure
start_caddy_with_checks() {
    local project_root=$1
    local app_env=$2
    local frontend_domain=$3
    local backend_domain=$4
    local frontend_port=$5
    local backend_port=$6
    local preferred_https_port=$7
    local logs_dir=$8

    echo -e "${BLUE}${ARROW} HTTPS Proxy (Caddy)${NC}"

    # Check for conflicts and get available port (may be different from preferred)
    local actual_https_port
    actual_https_port=$(check_proxy_conflicts "$preferred_https_port") || {
        echo -e "${YELLOW}${WARNING_MARK}${NC} Could not find available HTTPS port"
        echo -e "  Skipping Caddy. App will run without HTTPS proxy."
        return 1
    }

    # Use the actual available port (may differ from preferred)
    local caddy_pid
    caddy_pid=$(start_caddy_proxy \
        "$project_root" \
        "$app_env" \
        "$frontend_domain" \
        "$backend_domain" \
        "$frontend_port" \
        "$backend_port" \
        "$actual_https_port" \
        "$logs_dir") || return 1

    echo -e "   ${GREEN}${CHECK_MARK}${NC} Started (PID: $caddy_pid)"
    echo -e "   ${CYAN}${ARROW}${NC} Port $actual_https_port"
    if [ "$actual_https_port" != "$preferred_https_port" ]; then
        echo -e "   ${CYAN}ℹ${NC} Using port $actual_https_port (preferred $preferred_https_port was in use)"
    fi
    echo ""

    echo "$caddy_pid"
}

# Setup SSL certificate trust for self-signed certificates (TLS_MODE=internal)
# Use in any environment: local, staging, or production behind proxy/internal network
# Works with both native Caddy and Docker Caddy
# Usage: setup_ssl_trust [project_root] [container_name]
#   project_root: Project root directory (default: current directory)
#   container_name: Docker container name if using Docker (optional)
setup_ssl_trust() {
    local project_root="${1:-$(pwd)}"
    local container_name="${2:-}"
    local cert_path="$project_root/storage/app/ssl"
    local root_ca_file="$cert_path/caddy-root-ca.crt"

    echo -e "${CYAN}Setting up SSL certificate trust...${NC}"

    # Create directory for certificates
    mkdir -p "$cert_path"

    local root_ca_source=""
    local is_docker=false

    # Detect if Caddy is in Docker or native
    if [ -n "$container_name" ] && docker ps --format "{{.Names}}" | grep -q "^${container_name}$"; then
        # Docker Caddy
        is_docker=true
        root_ca_source="/data/caddy/pki/authorities/local/root.crt"

        # Wait for Caddy to generate certificates (up to 10 seconds)
        local attempts=0
        local max_attempts=10
        while [ $attempts -lt $max_attempts ]; do
            if docker exec "$container_name" test -f "$root_ca_source" 2>/dev/null; then
                break
            fi
            sleep 1
            attempts=$((attempts + 1))
        done

        # Export root CA from Docker container
        if ! docker exec "$container_name" cat "$root_ca_source" > "$root_ca_file" 2>/dev/null; then
            echo -e "${YELLOW}⚠${NC} Could not export Caddy root CA from container (certificate may not be generated yet)"
            echo -e "  You can manually accept the certificate warning in your browser"
            return 1
        fi
    else
        # Native Caddy - try common locations
        local native_locations=(
            "$HOME/.local/share/caddy/pki/authorities/local/root.crt"
            "$HOME/.config/caddy/pki/authorities/local/root.crt"
            "/root/.local/share/caddy/pki/authorities/local/root.crt"
        )

        for location in "${native_locations[@]}"; do
            if [ -f "$location" ]; then
                root_ca_source="$location"
                break
            fi
        done

        if [ -z "$root_ca_source" ] || [ ! -f "$root_ca_source" ]; then
            echo -e "${YELLOW}⚠${NC} Could not find Caddy root CA (certificate may not be generated yet)"
            echo -e "  Expected locations:"
            for location in "${native_locations[@]}"; do
                echo -e "    ${CYAN}$location${NC}"
            done
            echo -e "  You can manually accept the certificate warning in your browser"
            return 1
        fi

        # Copy from native location
        cp "$root_ca_source" "$root_ca_file" 2>/dev/null || {
            echo -e "${YELLOW}⚠${NC} Could not copy Caddy root CA"
            return 1
        }
    fi

    # Check if certificate was exported successfully
    if [ ! -s "$root_ca_file" ]; then
        echo -e "${YELLOW}⚠${NC} Root CA file is empty"
        return 1
    fi

    echo -e "${GREEN}✓${NC} Exported Caddy root CA to ${CYAN}storage/app/ssl/caddy-root-ca.crt${NC}"

    # Try to install to system trust store (Linux/WSL2)
    local installed=false
    if command_exists update-ca-certificates; then
        # Debian/Ubuntu
        local system_cert_path="/usr/local/share/ca-certificates/caddy-blb-root.crt"

        # Check if already installed and matches current certificate
        if [ -f "$system_cert_path" ]; then
            if diff -q "$root_ca_file" "$system_cert_path" >/dev/null 2>&1; then
                echo -e "${GREEN}✓${NC} Certificate already installed in system trust store"
                installed=true
            else
                echo -e "${YELLOW}ℹ${NC} Certificate changed, updating system trust store..."
                if [ -t 0 ]; then
                    if sudo cp "$root_ca_file" "$system_cert_path" 2>/dev/null && \
                       sudo update-ca-certificates 2>/dev/null; then
                        echo -e "${GREEN}✓${NC} Certificate updated in system trust store"
                        installed=true
                    fi
                fi
            fi
        elif [ -t 0 ]; then
            # Not installed yet - install it
            echo -e "${CYAN}Installing certificate to system trust store...${NC}"
            if sudo cp "$root_ca_file" "$system_cert_path" 2>/dev/null && \
               sudo update-ca-certificates 2>/dev/null; then
                echo -e "${GREEN}✓${NC} Certificate installed to system trust store"
                installed=true
            fi
        fi
    elif command_exists trust; then
        # Fedora/RHEL
        # Check if already in trust store
        if trust list | grep -q "caddy-blb-root" 2>/dev/null; then
            echo -e "${GREEN}✓${NC} Certificate already installed in system trust store"
            installed=true
        elif [ -t 0 ]; then
            echo -e "${CYAN}Installing certificate to system trust store...${NC}"
            if sudo trust anchor --store "$root_ca_file" 2>/dev/null; then
                echo -e "${GREEN}✓${NC} Certificate installed to system trust store"
                installed=true
            fi
        fi
    fi

    # On WSL2, also help with Windows trust
    if is_wsl2; then
        # Try to find Windows username
        local win_user
        win_user=$(cmd.exe /c "echo %USERNAME%" 2>/dev/null | tr -d '\r\n' || echo "")

        if [ -n "$win_user" ] && [ -d "/mnt/c/Users/$win_user" ]; then
            local win_cert_path="/mnt/c/Users/$win_user/Desktop/caddy-blb-root-ca.crt"
            cp "$root_ca_file" "$win_cert_path" 2>/dev/null || true

            if [ -f "$win_cert_path" ]; then
                echo ""
                echo -e "${CYAN}For Windows browser support:${NC}"
                echo -e "  Certificate copied to: ${CYAN}Desktop/caddy-blb-root-ca.crt${NC}"
                echo -e "  To install:"
                echo -e "    1. Double-click the certificate on your Desktop"
                echo -e "    2. Click 'Install Certificate' → 'Local Machine' → Next"
                echo -e "    3. Select 'Place all certificates in the following store'"
                echo -e "    4. Browse → 'Trusted Root Certification Authorities' → OK → Next → Finish"
                echo -e "    5. Restart your browser"
                echo ""
            fi
        else
            echo -e "${YELLOW}⚠${NC} Could not copy certificate to Windows Desktop"
            echo -e "  Manual location: ${CYAN}$root_ca_file${NC}"
        fi
    fi

    if [ "$installed" = false ] && ! is_wsl2; then
        echo -e "${YELLOW}Note:${NC} Certificate not auto-installed to system trust store"
        echo -e "  You can manually install: ${CYAN}$root_ca_file${NC}"
        echo -e "  Or accept the browser warning (safe for self-signed development certificates)"
    fi

    return 0
}

# High-level orchestration to ensure SSL trust is set up if needed
# Checks TLS_MODE and whether cert is already installed before attempting setup
# Usage: ensure_ssl_trust [project_root] [tls_mode] [container_name]
ensure_ssl_trust() {
    local project_root="${1:-$(pwd)}"
    local tls_mode="${2:-internal}"
    local container_name="${3:-}"

    # Production environments with real certificates (TLS_MODE != "internal") don't need this
    if [ "${tls_mode}" != "internal" ]; then
        if command -v log >/dev/null 2>&1; then
            log "Using TLS mode: $tls_mode (real certificates, skipping SSL trust setup)"
        fi
        return 0
    fi

    # Check if we can even run the setup
    if ! command -v setup_ssl_trust >/dev/null 2>&1; then
        return 1
    fi

    # Check if certificate is already in system trust store
    local ssl_already_installed=false

    if command -v update-ca-certificates >/dev/null 2>&1; then
        # Debian/Ubuntu - check if already installed
        if [ -f "/usr/local/share/ca-certificates/caddy-blb-root.crt" ]; then
            ssl_already_installed=true
        fi
    elif command -v trust >/dev/null 2>&1; then
        # Fedora/RHEL - check if already in trust store
        if trust list | grep -q "caddy-blb-root" 2>/dev/null; then
            ssl_already_installed=true
        fi
    fi

    if [ "$ssl_already_installed" = true ]; then
        # Already installed - skip setup
        return 0
    fi

    # Not installed yet - try to set up
    # We wait briefly for Caddy to generate certificates if they don't exist yet
    sleep 2

    echo ""
    echo -e "${CYAN}Setting up SSL certificate trust (one-time, for development)...${NC}"

    if setup_ssl_trust "$project_root" "$container_name"; then
        return 0
    else
        # Setup failed - provide helpful guidance
        echo ""
        echo -e "${YELLOW}Note:${NC} SSL trust setup was skipped or failed (not critical for development)"
        echo -e "To set up SSL trust later, run: ${CYAN}./scripts/setup-steps/75-ssl-trust.sh${NC}"
        echo -e "Or accept the browser warning when accessing your app (safe for self-signed certs)"
        return 1
    fi
}

