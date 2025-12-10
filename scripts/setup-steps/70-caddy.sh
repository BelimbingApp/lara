#!/usr/bin/env bash
# scripts/setup-steps/70-caddy.sh
# Title: Reverse Proxy (Caddy)
# Purpose: Install and configure Caddy reverse proxy for Belimbing
# Usage: ./scripts/setup-steps/70-caddy.sh [local|staging|production|testing]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Detects existing reverse proxies
# - Installs Caddy if selected
# - Configures Caddyfile
# - Sets PROXY_TYPE and SKIP_CADDY variables

set -euo pipefail

# Get script directory and project root
SETUP_STEPS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"  # Points to scripts/setup-steps/
SCRIPTS_DIR="$(cd "$SETUP_STEPS_DIR/.." && pwd)"  # Points to scripts/
PROJECT_ROOT="$(cd "$SCRIPTS_DIR/.." && pwd)"  # Points to project root

# Source shared utilities (order matters: config.sh before validation.sh)
# shellcheck source=../shared/colors.sh
source "$SCRIPTS_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=../shared/runtime.sh
source "$SCRIPTS_DIR/shared/runtime.sh" 2>/dev/null || true
# shellcheck source=../shared/config.sh
source "$SCRIPTS_DIR/shared/config.sh"
# shellcheck source=../shared/validation.sh
source "$SCRIPTS_DIR/shared/validation.sh"
# shellcheck source=../shared/interactive.sh
source "$SCRIPTS_DIR/shared/interactive.sh"
# shellcheck source=../shared/caddy.sh
source "$SCRIPTS_DIR/shared/caddy.sh"

# Environment (default to local if not provided, using Laravel standard)
APP_ENV="${1:-local}"

# Prompt user for custom domains with defaults
# Returns: frontend_domain|backend_domain
prompt_for_domains() {
    local default_domains
    default_domains=$(get_default_domains "$APP_ENV")
    local default_frontend
    local default_backend
    default_frontend=$(echo "$default_domains" | cut -d'|' -f1)
    default_backend=$(echo "$default_domains" | cut -d'|' -f2)

    if [ -t 0 ]; then
        echo -e "${CYAN}Domain Configuration${NC}"
        echo ""
        local custom_frontend
        custom_frontend=$(ask_input "Frontend domain" "$default_frontend")
        # Use default if empty (shouldn't happen since default is provided, but safety check)
        [ -z "$custom_frontend" ] && custom_frontend="$default_frontend"

        echo ""
        local custom_backend
        custom_backend=$(ask_input "Backend domain" "$default_backend")
        # Use default if empty (shouldn't happen since default is provided, but safety check)
        [ -z "$custom_backend" ] && custom_backend="$default_backend"

        # Validate domains
        if ! is_valid_domain "$custom_frontend"; then
            echo -e "${YELLOW}⚠${NC} Frontend domain format may be invalid: ${CYAN}$custom_frontend${NC}"
        fi
        if ! is_valid_domain "$custom_backend"; then
            echo -e "${YELLOW}⚠${NC} Backend domain format may be invalid: ${CYAN}$custom_backend${NC}"
        fi

        echo "${custom_frontend}|${custom_backend}"
    else
        # Non-interactive: use defaults
        echo "${default_frontend}|${default_backend}"
    fi
}

# Find available HTTPS port, starting from preferred port
# Returns first available port in range [preferred, preferred+10]
find_available_https_port() {
    local preferred_port=$1
    local max_port=$((preferred_port + 10))
    local port=$preferred_port

    while [ $port -le $max_port ]; do
        if is_port_available "$port"; then
            echo "$port"
            return 0
        fi
        ((port++))
    done

    # If no port found, return preferred anyway (caller will handle error)
    echo "$preferred_port"
    return 1
}

# Extract domains from existing Belimbing config in Caddyfile
# Returns: frontend_domain|backend_domain or empty if not found
extract_belimbing_domains() {
    local caddyfile=$1

    if [ ! -f "$caddyfile" ]; then
        return 1
    fi

    # Check if Belimbing block exists
    if ! grep -q "# Belimbing configuration" "$caddyfile" 2>/dev/null; then
        return 1
    fi

    # Extract domains from https:// blocks in Belimbing section
    # Look for lines like "https://domain:port {" after Belimbing comment
    local in_belimbing_block=false
    local frontend_domain=""
    local backend_domain=""

    while IFS= read -r line; do
        # Check if we're entering Belimbing block
        if [[ "$line" =~ "# Belimbing configuration" ]]; then
            in_belimbing_block=true
            continue
        fi

        # Check if we've left the Belimbing block (next # comment that's not part of Belimbing)
        if [ "$in_belimbing_block" = true ] && [[ "$line" =~ ^# ]] && [[ ! "$line" =~ "Belimbing" ]]; then
            break
        fi

        # Extract domain from https:// lines
        if [ "$in_belimbing_block" = true ] && [[ "$line" =~ ^https:// ]]; then
            # Extract domain:port from "https://domain:port {"
            local domain_port
            domain_port=$(echo "$line" | sed -n 's|^https://\([^:]*\):.*|\1|p')
            if [ -n "$domain_port" ]; then
                if [ -z "$frontend_domain" ]; then
                    frontend_domain="$domain_port"
                elif [ -z "$backend_domain" ]; then
                    backend_domain="$domain_port"
                    break
                fi
            fi
        fi
    done < "$caddyfile"

    if [ -n "$frontend_domain" ] && [ -n "$backend_domain" ]; then
        echo "${frontend_domain}|${backend_domain}"
        return 0
    fi

    return 1
}

# Auto-configure existing Caddy by adding Belimbing config block
# Adds configuration to existing Caddyfile or creates new one
configure_existing_caddy() {
    local frontend_domain=$1
    local backend_domain=$2
    local frontend_port=$3
    local backend_port=$4
    local https_port=$5

    echo -e "${CYAN}Auto-configuring existing Caddy installation...${NC}"

    # Find Caddyfile location (common locations)
    local caddyfile_locations=(
        "/etc/caddy/Caddyfile"
        "$HOME/.config/caddy/Caddyfile"
        "$PROJECT_ROOT/Caddyfile"
        "/usr/local/etc/caddy/Caddyfile"
    )

    local caddyfile=""
    local is_system_file=false
    local is_project_file=false

    # Find Caddyfile location
    for location in "${caddyfile_locations[@]}"; do
        if [ -f "$location" ]; then
            caddyfile="$location"
            # Check if it's a system file (never touch these)
            if [[ "$caddyfile" =~ ^/(etc|usr/local/etc)/ ]]; then
                is_system_file=true
            elif [ "$caddyfile" = "$PROJECT_ROOT/Caddyfile" ]; then
                is_project_file=true
            fi
            break
        fi
    done

    # If no Caddyfile found, try to detect from Caddy process
    if [ -z "$caddyfile" ] && command_exists caddy; then
        local caddy_pid
        caddy_pid=$(pgrep -x caddy | head -1)
        if [ -n "$caddy_pid" ]; then
            echo -e "${YELLOW}⚠${NC} Caddy is running but Caddyfile location not detected"
        fi
    fi

    # Determine target Caddyfile location
    # Never touch system Caddyfiles - always use project-specific one
    if [ "$is_system_file" = true ]; then
        echo -e "${CYAN}ℹ${NC} System Caddyfile detected: ${CYAN}$caddyfile${NC}"
        echo -e "${CYAN}ℹ${NC} Creating project-specific Caddyfile: ${CYAN}$PROJECT_ROOT/Caddyfile.blb${NC}"
        caddyfile="$PROJECT_ROOT/Caddyfile.blb"
    elif [ "$is_project_file" = true ]; then
        echo -e "${CYAN}ℹ${NC} Found project Caddyfile: ${CYAN}$caddyfile${NC}"
        # Will check and update if needed below
    elif [ -z "$caddyfile" ]; then
        # No Caddyfile found - create project-specific one
        caddyfile="$PROJECT_ROOT/Caddyfile.blb"
        echo -e "${CYAN}ℹ${NC} No existing Caddyfile found, creating: ${CYAN}$caddyfile${NC}"
    else
        # Other location (e.g., $HOME/.config/caddy/Caddyfile) - treat as project-level
        echo -e "${CYAN}ℹ${NC} Found Caddyfile: ${CYAN}$caddyfile${NC}"
        is_project_file=true
    fi

    # Check if file exists before we start
    local file_existed=false
    if [ -f "$caddyfile" ]; then
        file_existed=true
    fi

    # Check if Belimbing block exists and compare domains
    local needs_update=false
    local belimbing_exists=false

    if [ "$file_existed" = true ] && grep -q "# Belimbing configuration" "$caddyfile" 2>/dev/null; then
        belimbing_exists=true
        # Extract existing domains
        local existing_domains
        existing_domains=$(extract_belimbing_domains "$caddyfile")

        if [ -n "$existing_domains" ]; then
            local existing_frontend existing_backend
            existing_frontend=$(echo "$existing_domains" | cut -d'|' -f1)
            existing_backend=$(echo "$existing_domains" | cut -d'|' -f2)

            # Compare domains
            if [ "$existing_frontend" != "$frontend_domain" ] || [ "$existing_backend" != "$backend_domain" ]; then
                needs_update=true
                echo -e "${CYAN}ℹ${NC} Domains changed:"
                echo -e "  Old: ${CYAN}$existing_frontend${NC} / ${CYAN}$existing_backend${NC}"
                echo -e "  New: ${CYAN}$frontend_domain${NC} / ${CYAN}$backend_domain${NC}"
                echo -e "${CYAN}ℹ${NC} Updating Caddyfile configuration..."
            else
                echo -e "${GREEN}✓${NC} Domains match existing configuration"
                echo -e "${CYAN}ℹ${NC} Caddyfile already configured correctly"
                # Still need to ensure certificates exist
            fi
        else
            # Belimbing block exists but couldn't parse domains - update anyway
            needs_update=true
            echo -e "${YELLOW}⚠${NC} Belimbing block found but couldn't parse domains, updating..."
        fi
    else
        # No Belimbing block - will append
        needs_update=true
    fi

    # Create certs directory if it doesn't exist
    local certs_dir="$PROJECT_ROOT/certs"
    mkdir -p "$certs_dir"

    # Generate self-signed certificates if they don't exist
    if [ ! -f "$certs_dir/${frontend_domain}.pem" ]; then
        echo -e "${CYAN}Generating self-signed certificates...${NC}"
        if command_exists mkcert; then
            # Use mkcert for trusted local certificates
            if [ ! -f "$certs_dir/${frontend_domain}.pem" ]; then
                mkcert -cert-file "$certs_dir/${frontend_domain}.pem" \
                       -key-file "$certs_dir/${frontend_domain}-key.pem" \
                       "$frontend_domain" "$backend_domain" 2>/dev/null || true
            fi
        else
            # Fallback: generate basic self-signed cert (will show browser warning)
            echo -e "${YELLOW}⚠${NC} mkcert not found, generating basic self-signed certificate"
            echo -e "${CYAN}ℹ${NC} Install mkcert for trusted local certificates: ${CYAN}https://github.com/FiloSottile/mkcert${NC}"
        fi
    fi

    # Generate Belimbing configuration block
    local belimbing_config
    belimbing_config=$(cat << EOF

# Belimbing configuration - Auto-generated
# Environment: $APP_ENV
# Project: $(basename "$PROJECT_ROOT")

https://$frontend_domain:$https_port {
    tls $certs_dir/${frontend_domain}.pem $certs_dir/${frontend_domain}-key.pem
    reverse_proxy 127.0.0.1:$frontend_port
}

https://$backend_domain:$https_port {
    tls $certs_dir/${frontend_domain}.pem $certs_dir/${frontend_domain}-key.pem
    reverse_proxy 127.0.0.1:$backend_port
}
EOF
)

    # Update or append Caddyfile
    if [ "$needs_update" = true ]; then
        if [ "$belimbing_exists" = true ]; then
            # Replace existing Belimbing block
            # Find the start and end of Belimbing block
            local start_line end_line
            start_line=$(grep -n "# Belimbing configuration" "$caddyfile" | head -1 | cut -d: -f1)

            if [ -n "$start_line" ]; then
                # Find the end of the block (next non-indented line or end of file)
                end_line=$(awk -v start="$start_line" 'NR > start && /^[^[:space:]]/ && !/^# Belimbing/ {print NR-1; exit}' "$caddyfile")
                if [ -z "$end_line" ]; then
                    # No end found, use end of file
                    end_line=$(wc -l < "$caddyfile")
                fi

                # Create temporary file with updated content
                local temp_file
                temp_file=$(mktemp)

                # Copy lines before Belimbing block
                if [ "$start_line" -gt 1 ]; then
                    head -n $((start_line - 1)) "$caddyfile" > "$temp_file"
                fi

                # Add new Belimbing block
                echo "$belimbing_config" >> "$temp_file"

                # Copy lines after Belimbing block
                if [ "$end_line" -lt "$(wc -l < "$caddyfile")" ]; then
                    tail -n +$((end_line + 1)) "$caddyfile" >> "$temp_file"
                fi

                # Replace original file
                mv "$temp_file" "$caddyfile"
                echo -e "${GREEN}✓${NC} Updated Belimbing configuration in: ${CYAN}$caddyfile${NC}"
            else
                # Fallback: append if we can't find the block
                echo "$belimbing_config" >> "$caddyfile"
                echo -e "${GREEN}✓${NC} Added Belimbing configuration to: ${CYAN}$caddyfile${NC}"
            fi
        else
            # Append new Belimbing block
            echo "$belimbing_config" >> "$caddyfile"
            if [ "$file_existed" = false ]; then
                echo -e "${GREEN}✓${NC} Created Caddyfile with Belimbing configuration: ${CYAN}$caddyfile${NC}"
            else
                echo -e "${GREEN}✓${NC} Added Belimbing configuration to: ${CYAN}$caddyfile${NC}"
            fi
        fi
    fi

    # Provide instructions based on Caddyfile location and action taken
    echo ""
    if [ "$is_system_file" = true ]; then
        echo -e "${CYAN}ℹ${NC} Project-specific Caddyfile created: ${CYAN}$caddyfile${NC}"
        echo -e "${CYAN}ℹ${NC} To use this Caddyfile, run Caddy with: ${CYAN}caddy run --config $caddyfile${NC}"
        echo -e "${CYAN}ℹ${NC} Or include it in your system Caddyfile: ${CYAN}import $caddyfile${NC}"
    elif [ "$caddyfile" = "$PROJECT_ROOT/Caddyfile.blb" ]; then
        if [ "$file_existed" = false ]; then
            echo -e "${CYAN}ℹ${NC} Project-specific Caddyfile created: ${CYAN}$caddyfile${NC}"
        else
            echo -e "${CYAN}ℹ${NC} Project-specific Caddyfile: ${CYAN}$caddyfile${NC}"
        fi
        echo -e "${CYAN}ℹ${NC} To use this Caddyfile, run Caddy with: ${CYAN}caddy run --config $caddyfile${NC}"
        echo -e "${CYAN}ℹ${NC} Or include it in your main Caddyfile: ${CYAN}import $caddyfile${NC}"
    else
        # Project-level Caddyfile (e.g., $PROJECT_ROOT/Caddyfile)
        if [ "$needs_update" = true ]; then
            if [ "$belimbing_exists" = true ]; then
                echo -e "${CYAN}ℹ${NC} Caddyfile updated: ${CYAN}$caddyfile${NC}"
            else
                echo -e "${CYAN}ℹ${NC} Belimbing configuration added to: ${CYAN}$caddyfile${NC}"
            fi
        fi
        echo -e "${CYAN}ℹ${NC} Reload Caddy to apply changes: ${CYAN}sudo systemctl reload caddy${NC}"
        echo -e "${CYAN}ℹ${NC} Or restart Caddy: ${CYAN}sudo systemctl restart caddy${NC}"
        echo -e "${CYAN}ℹ${NC} Or run Caddy directly: ${CYAN}caddy run --config $caddyfile${NC}"
    fi

    return 0
}

# Main setup function
main() {
    print_section_banner "Reverse Proxy Setup - Belimbing ($APP_ENV)"

    # Load existing configuration
    load_setup_state

    # Detect existing proxy first
    local existing_proxy
    existing_proxy=$(detect_proxy)

    # Check if already configured AND proxy is still running/valid
    if [ -n "${PROXY_TYPE:-}" ]; then
        # Only prompt if the previously chosen proxy is still active/valid
        local should_prompt=false

        if [ "$PROXY_TYPE" = "caddy" ] && [ "$existing_proxy" = "caddy" ]; then
            # Caddy was chosen AND is currently running
            should_prompt=true
        elif [ "$PROXY_TYPE" = "nginx" ] || [ "$PROXY_TYPE" = "apache" ] || [ "$PROXY_TYPE" = "traefik" ]; then
            # Manual proxy was chosen AND matches what's running
            if [ "$existing_proxy" = "$PROXY_TYPE" ]; then
                should_prompt=true
            fi
        elif [ "$PROXY_TYPE" = "manual" ] && [ "$existing_proxy" != "none" ]; then
            # Generic manual choice AND some proxy is running
            should_prompt=true
        elif [ "$PROXY_TYPE" = "none" ]; then
            # "None" is always valid (no proxy needed)
            should_prompt=true
        fi

        if [ "$should_prompt" = true ]; then
            echo -e "${CYAN}ℹ${NC} You previously chose: ${YELLOW}$PROXY_TYPE${NC} as your reverse proxy"
            echo ""

            if [ -t 0 ]; then
                if ask_yes_no "Use the same choice again?" "y"; then
                    echo -e "${GREEN}✓${NC} Keeping your choice: ${CYAN}$PROXY_TYPE${NC}"
                    exit 0
                fi
                echo ""
                echo -e "${YELLOW}OK, let's choose a new option...${NC}"
                echo ""
            else
                echo -e "${GREEN}✓${NC} Using your previous choice: ${CYAN}$PROXY_TYPE${NC}"
                exit 0
            fi
        fi
    fi

    # Display detection results
    echo -e "${CYAN}Detecting existing reverse proxies...${NC}"

    if [ "$existing_proxy" != "none" ]; then
        echo -e "${YELLOW}⚠${NC} Detected existing reverse proxy: ${YELLOW}$existing_proxy${NC}"
        echo ""

        # Special handling for Caddy: auto-configure it
        if [ "$existing_proxy" = "caddy" ]; then
            echo -e "${GREEN}✓${NC} Caddy detected - will auto-configure for Belimbing"
            echo ""
            SKIP_CADDY="true"
            PROXY_TYPE="caddy"

            # Prompt for domains (or use defaults)
            echo ""
            local domains
            domains=$(prompt_for_domains)
            local frontend_domain backend_domain
            frontend_domain=$(echo "$domains" | cut -d'|' -f1)
            backend_domain=$(echo "$domains" | cut -d'|' -f2)

            # Save domains to setup state and .env
            save_to_setup_state "FRONTEND_DOMAIN" "$frontend_domain"
            save_to_setup_state "BACKEND_DOMAIN" "$backend_domain"
            update_env_file "$PROJECT_ROOT/.env" "FRONTEND_DOMAIN" "$frontend_domain"
            update_env_file "$PROJECT_ROOT/.env" "BACKEND_DOMAIN" "$backend_domain"

            # Get default ports
            local default_ports
            default_ports=$(get_default_ports "$APP_ENV")
            local frontend_port backend_port https_port
            frontend_port=$(echo "$default_ports" | cut -d'|' -f1)
            backend_port=$(echo "$default_ports" | cut -d'|' -f2)
            https_port=$(echo "$default_ports" | cut -d'|' -f3)

            # Find available HTTPS port if default is taken
            if ! is_port_available "$https_port"; then
                echo -e "${YELLOW}⚠${NC} Port $https_port is in use, finding alternative..."
                https_port=$(find_available_https_port "$https_port")
                echo -e "${CYAN}ℹ${NC} Using port: ${CYAN}$https_port${NC}"
            fi

            configure_existing_caddy "$frontend_domain" "$backend_domain" "$frontend_port" "$backend_port" "$https_port"

        else
            # Non-Caddy proxy detected - show options since user needs to choose
            cat << EOF
${CYAN}Reverse Proxy Configuration${NC}

A reverse proxy handles HTTPS and forwards requests to your backend/frontend.

Since you already have $existing_proxy running, you have options:

${CYAN}Options:${NC}

  ${GREEN}1. Use Caddy anyway${NC} (will auto-handle port conflicts)
     • Automatic setup, zero configuration
     • Runs as a child process of start-app.sh
     • Stops when you stop the app
     • Best for development and testing

  ${YELLOW}2. Use my existing $existing_proxy${NC} (manual configuration required)
     • We'll generate a config snippet for you
     • You manage the proxy yourself

  ${RED}3. No HTTPS${NC} (HTTP only)
     • No HTTPS, access via http://localhost
     • Some browser features won't work without HTTPS
     • Not recommended for production

EOF

            if [ -t 0 ]; then
                echo -e "${CYAN}What would you like to do?${NC}"
                echo -e "  ${CYAN}1${NC} - Use Caddy anyway (will auto-handle port conflicts)"
                echo -e "  ${CYAN}2${NC} - Use my existing $existing_proxy (manual configuration required)"
                echo -e "  ${CYAN}3${NC} - No HTTPS (HTTP only)"
                echo ""

                local choice
                while true; do
                    choice=$(ask_input "Choice" "1")

                    case "$choice" in
                        1)
                            # Use Caddy anyway - auto-handle conflicts
                            SKIP_CADDY="false"
                            PROXY_TYPE="caddy"

                            # Prompt for domains
                            echo ""
                            local domains
                            domains=$(prompt_for_domains)
                            FRONTEND_DOMAIN=$(echo "$domains" | cut -d'|' -f1)
                            BACKEND_DOMAIN=$(echo "$domains" | cut -d'|' -f2)

                            # Save domains to setup state and .env
                            save_to_setup_state "FRONTEND_DOMAIN" "$FRONTEND_DOMAIN"
                            save_to_setup_state "BACKEND_DOMAIN" "$BACKEND_DOMAIN"
                            update_env_file "$PROJECT_ROOT/.env" "FRONTEND_DOMAIN" "$FRONTEND_DOMAIN"
                            update_env_file "$PROJECT_ROOT/.env" "BACKEND_DOMAIN" "$BACKEND_DOMAIN"

                            # Find available HTTPS port
                            local default_ports
                            default_ports=$(get_default_ports "$APP_ENV")
                            local https_port
                            https_port=$(echo "$default_ports" | cut -d'|' -f3)

                            if ! is_port_available "$https_port"; then
                                echo -e "${CYAN}ℹ${NC} Port $https_port is in use, finding alternative..."
                                https_port=$(find_available_https_port "$https_port")
                                echo -e "${CYAN}ℹ${NC} Caddy will use port: ${CYAN}$https_port${NC}"
                            fi
                            break
                            ;;
                        2)
                            SKIP_CADDY="true"
                            PROXY_TYPE="$existing_proxy"
                            echo ""
                            echo -e "${YELLOW}ℹ${NC} Using $existing_proxy - you'll need to configure it manually"
                            echo -e "${CYAN}ℹ${NC} Configuration snippets will be generated during final setup"
                            break
                            ;;
                        3)
                            SKIP_CADDY="true"
                            PROXY_TYPE="none"
                            echo ""
                            echo -e "${YELLOW}⚠${NC} Running without HTTPS"
                            if ask_yes_no "Continue without HTTPS?" "n"; then
                                break
                            fi
                            ;;
                        *)
                            echo -e "${YELLOW}Invalid choice. Please enter 1, 2, or 3${NC}"
                            ;;
                    esac
                done
            else
                # Non-interactive: default to Caddy with auto-conflict handling
                SKIP_CADDY="false"
                PROXY_TYPE="caddy"
            fi
        fi
    else
        # No existing proxy detected - be opinionated, use Caddy automatically!
        echo -e "${GREEN}✓${NC} No existing reverse proxy detected"
        echo -e "${CYAN}→${NC} Automatically choosing Caddy for HTTPS support"
        echo ""
        SKIP_CADDY="false"
        PROXY_TYPE="caddy"
    fi

    echo ""

    # Handle Caddy installation if selected
    if [ "$PROXY_TYPE" = "caddy" ]; then
        echo -e "${CYAN}Setting up Caddy...${NC}"
        echo ""

        # Prompt for domains if not already set (e.g., from existing Caddy auto-config)
        if [ -z "${FRONTEND_DOMAIN:-}" ] || [ -z "${BACKEND_DOMAIN:-}" ]; then
            echo ""
            local domains
            domains=$(prompt_for_domains)
            FRONTEND_DOMAIN=$(echo "$domains" | cut -d'|' -f1)
            BACKEND_DOMAIN=$(echo "$domains" | cut -d'|' -f2)

            # Save domains to setup state and .env
            save_to_setup_state "FRONTEND_DOMAIN" "$FRONTEND_DOMAIN"
            save_to_setup_state "BACKEND_DOMAIN" "$BACKEND_DOMAIN"
            update_env_file "$PROJECT_ROOT/.env" "FRONTEND_DOMAIN" "$FRONTEND_DOMAIN"
            update_env_file "$PROJECT_ROOT/.env" "BACKEND_DOMAIN" "$BACKEND_DOMAIN"
            echo ""
        fi

        # Check if Caddy is already installed
        if command -v caddy &> /dev/null; then
            local caddy_version
            caddy_version=$(caddy version 2>/dev/null | head -1 || echo "unknown")
            echo -e "${GREEN}✓${NC} Caddy already installed: $caddy_version"
        else
            echo -e "${YELLOW}ℹ${NC} Caddy not found, installing..."
            if install_caddy; then
                echo -e "${GREEN}✓${NC} Caddy installed successfully"
            else
                echo -e "${RED}✗${NC} Failed to install Caddy"
                echo ""
                echo -e "${YELLOW}Options:${NC}"
                echo -e "  1. Install Caddy manually: ${CYAN}https://caddyserver.com/docs/install${NC}"
                echo -e "  2. Re-run this script and choose Manual or None"
                echo ""
                exit 1
            fi
        fi

        # Verify Caddy installation
        if ! command -v caddy &> /dev/null; then
            echo -e "${RED}✗${NC} Caddy installation verification failed"
            exit 1
        fi

        echo ""
        echo -e "${GREEN}✓${NC} Caddy is ready"
        echo -e "  ${CYAN}Frontend: ${FRONTEND_DOMAIN:-$(echo "$(get_default_domains "$APP_ENV")" | cut -d'|' -f1)}${NC}"
        echo -e "  ${CYAN}Backend: ${BACKEND_DOMAIN:-$(echo "$(get_default_domains "$APP_ENV")" | cut -d'|' -f2)}${NC}"
        echo -e "  ${CYAN}Caddyfile will be generated during final setup${NC}"
    elif [ "$PROXY_TYPE" = "manual" ] || [ "$PROXY_TYPE" = "nginx" ] || [ "$PROXY_TYPE" = "apache" ] || [ "$PROXY_TYPE" = "traefik" ]; then
        echo -e "${CYAN}ℹ${NC} Using manual proxy configuration: $PROXY_TYPE"
        echo -e "  ${CYAN}Configuration snippets will be generated during final setup${NC}"
    else
        echo -e "${YELLOW}⚠${NC} No reverse proxy configured (HTTP only mode)"
    fi

    echo ""
    echo -e "${GREEN}✓${NC} Your choice saved: ${CYAN}$PROXY_TYPE${NC}"

    # Save state
    save_to_setup_state "PROXY_TYPE" "$PROXY_TYPE" "$PROJECT_ROOT"
    save_to_setup_state "SKIP_CADDY" "$SKIP_CADDY" "$PROJECT_ROOT"

    # Update .env file with proxy configuration
    echo -n "Updating .env file with proxy settings... "
    update_env_file "$PROJECT_ROOT/.env" "PROXY_TYPE" "$PROXY_TYPE"
    update_env_file "$PROJECT_ROOT/.env" "SKIP_CADDY" "$SKIP_CADDY"
    echo -e "${GREEN}✓${NC}"

    echo ""
    echo -e "Configuration saved to: ${CYAN}$(get_setup_state_file "$PROJECT_ROOT")${NC}"
    echo -e "Proxy settings saved to: ${CYAN}$PROJECT_ROOT/.env${NC}"
    echo ""
    echo -e "${GREEN}✓ Reverse proxy setup complete!${NC}"
}

# Run main function
main "$@"
