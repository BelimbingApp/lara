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
# Returns 0 if safe to start Caddy, 1 if conflicts detected
check_proxy_conflicts() {
    local https_port=$1
    local existing_proxy
    existing_proxy=$(detect_proxy)

    if [ "$existing_proxy" != "none" ]; then
        # Check if HTTPS port is already in use
        if ! is_port_available "$https_port"; then
            echo -e "${YELLOW}${WARNING_MARK} Port $https_port is already in use by ${CYAN}$existing_proxy${NC}"
            echo -e "  Skipping Caddy. App will run without HTTPS proxy."
            return 1
        fi

        # Another proxy is running, skip Caddy to avoid routing conflicts
        echo -e "${YELLOW}${WARNING_MARK} Detected existing reverse proxy: ${CYAN}$existing_proxy${NC}"
        echo -e "  Skipping Caddy to avoid routing conflicts. App will run without HTTPS proxy."
        return 1
    fi

    return 0
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

    cat > "$caddy_file" << EOF
{
    auto_https off
    admin $admin_socket
}

https://$frontend_domain:$https_port {
    tls certs/$frontend_domain.pem certs/$frontend_domain-key.pem
    reverse_proxy 127.0.0.1:$frontend_port
}

https://$backend_domain:$https_port {
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
# Returns PID on success, returns 1 on conflict (caller should continue without Caddy)
start_caddy_with_checks() {
    local project_root=$1
    local app_env=$2
    local frontend_domain=$3
    local backend_domain=$4
    local frontend_port=$5
    local backend_port=$6
    local https_port=$7
    local logs_dir=$8

    echo -e "${BLUE}${ARROW} HTTPS Proxy (Caddy)${NC}"

    # Check for conflicts before starting
    if ! check_proxy_conflicts "$https_port"; then
        # Conflict detected - check_proxy_conflicts already printed the warning
        # Return 1 to indicate Caddy should be skipped, but app should continue
        return 1
    fi

    local caddy_pid
    caddy_pid=$(start_caddy_proxy \
        "$project_root" \
        "$app_env" \
        "$frontend_domain" \
        "$backend_domain" \
        "$frontend_port" \
        "$backend_port" \
        "$https_port" \
        "$logs_dir") || return 1

    echo -e "   ${GREEN}${CHECK_MARK}${NC} Started (PID: $caddy_pid)"
    echo -e "   ${CYAN}${ARROW}${NC} Port $https_port"
    echo ""

    echo "$caddy_pid"
}
