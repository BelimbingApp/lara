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

    cat << EOF
${CYAN}Reverse Proxy Configuration${NC}

A reverse proxy handles HTTPS and forwards requests to your backend/frontend.

${CYAN}Options:${NC}

  ${GREEN}1. Auto (Caddy)${NC} - Recommended for most users
     • Automatic setup, zero configuration
     • Runs as a child process of start-app.sh
     • Stops when you stop the app
     • Best for development and testing

  ${YELLOW}2. Manual${NC} (existing nginx/apache/traefik)
     • Use if you already have a reverse proxy running
     • We'll generate a config snippet for you
     • You manage the proxy yourself

  ${RED}3. None${NC} (HTTP only)
     • No HTTPS, access via http://localhost
     • Some browser features won't work without HTTPS
     • Not recommended for production

EOF

    # Display detection results
    echo -e "${CYAN}Detecting existing reverse proxies...${NC}"

    if [ "$existing_proxy" != "none" ]; then
        echo -e "${YELLOW}⚠${NC} Detected existing reverse proxy: ${YELLOW}$existing_proxy${NC}"
        echo ""
        echo -e "${YELLOW}Since you already have a proxy running, you have options:${NC}"
        echo ""

        # Only ask when there's a conflict
        if [ -t 0 ]; then
            echo -e "${CYAN}What would you like to do?${NC}"
            echo -e "  ${CYAN}1${NC} - Use Caddy anyway (may cause conflicts)"
            echo -e "  ${CYAN}2${NC} - Use my existing $existing_proxy (you'll configure it)"
            echo -e "  ${CYAN}3${NC} - No HTTPS (HTTP only)"
            echo ""

            local choice
            while true; do
                choice=$(ask_input "Choice" "2")

                case "$choice" in
                    1)
                        echo ""
                        echo -e "${YELLOW}⚠${NC} Warning: Starting Caddy may cause port conflicts"
                        if ask_yes_no "Continue with Caddy anyway?" "n"; then
                            SKIP_CADDY="false"
                            PROXY_TYPE="caddy"
                            break
                        fi
                        ;;
                    2)
                        SKIP_CADDY="true"
                        echo ""
                        manual_proxy=$(ask_input "Which proxy are you using? (nginx/apache/traefik)" "$existing_proxy")
                        case "$manual_proxy" in
                            nginx|apache|traefik)
                                PROXY_TYPE="$manual_proxy"
                                ;;
                            *)
                                PROXY_TYPE="manual"
                                ;;
                        esac
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
            # Non-interactive with conflict - default to manual
            SKIP_CADDY="true"
            PROXY_TYPE="manual"
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
