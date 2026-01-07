#!/usr/bin/env bash
# scripts/setup-steps/75-ssl-trust.sh
# Title: SSL Certificate Trust Setup
# Purpose: Set up SSL certificate trust for self-signed HTTPS certificates (TLS_MODE=internal)
# Usage: ./scripts/setup-steps/75-ssl-trust.sh [local|staging|production|testing]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Waits for Caddy to generate SSL certificates
# - Exports Caddy root CA to project storage
# - Installs certificate to system trust store (one-time)
# - Provides instructions for manual installation if needed

set -euo pipefail

# Get script directory and project root
SETUP_STEPS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"  # Points to scripts/setup-steps/
SCRIPTS_DIR="$(cd "$SETUP_STEPS_DIR/.." && pwd)"  # Points to scripts/
PROJECT_ROOT="$(cd "$SCRIPTS_DIR/.." && pwd)"  # Points to project root

# Source shared utilities
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

# Environment (default to local if not provided)
APP_ENV="${1:-local}"

# Main setup function
main() {
    print_section_banner "SSL Certificate Trust Setup - Belimbing ($APP_ENV)"

    # Load existing configuration
    load_setup_state

    # Check if Caddy is being used
    if [ "${PROXY_TYPE:-}" != "caddy" ]; then
        echo -e "${YELLOW}ℹ${NC} Caddy is not configured as the reverse proxy"
        echo -e "  Current proxy type: ${CYAN}${PROXY_TYPE:-none}${NC}"
        echo ""
        echo -e "${CYAN}This step is only needed if you're using Caddy for HTTPS.${NC}"
        echo -e "Skip this step or run ${CYAN}./scripts/setup-steps/70-caddy.sh${NC} first."
        exit 0
    fi

    echo -e "${CYAN}This step configures your system to trust Caddy's SSL certificates.${NC}"
    echo ""
    echo -e "${YELLOW}Note:${NC} This is a one-time setup. Once configured, you won't see"
    echo -e "browser warnings when accessing ${CYAN}https://*.blb.lara${NC}"
    echo ""

    # Check if Caddy is running (needed to generate certificates)
    if ! pgrep -x "caddy" > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠${NC} Caddy is not currently running"
        echo ""
        echo -e "${CYAN}To generate SSL certificates, Caddy must be running at least once.${NC}"
        echo -e "Options:"
        echo -e "  1. Start the app: ${CYAN}./scripts/start-app.sh${NC}"
        echo -e "  2. Let it run for ~5 seconds (so Caddy generates certificates)"
        echo -e "  3. Stop the app (Ctrl+C)"
        echo -e "  4. Re-run this script: ${CYAN}./scripts/setup-steps/75-ssl-trust.sh${NC}"
        echo ""

        if [ -t 0 ]; then
            if ask_yes_no "Try to set up SSL trust anyway? (may fail if certificates don't exist yet)" "n"; then
                echo ""
            else
                echo -e "${YELLOW}Skipping SSL trust setup${NC}"
                exit 0
            fi
        else
            echo -e "${YELLOW}Non-interactive mode: Skipping SSL trust setup${NC}"
            echo -e "Run the app first to generate certificates, then re-run this script."
            exit 0
        fi
    else
        echo -e "${GREEN}✓${NC} Caddy is running"
        echo ""
    fi

    # Wait a moment for Caddy to generate certificates if just started
    echo -e "${CYAN}Checking for SSL certificates...${NC}"
    sleep 2

    # Use the shared setup_ssl_trust function
    if type setup_ssl_trust >/dev/null 2>&1; then
        if setup_ssl_trust "$PROJECT_ROOT"; then
            echo ""
            echo -e "${GREEN}✓ SSL certificate trust setup complete!${NC}"
            echo ""
            echo -e "${CYAN}Next steps:${NC}"
            echo -e "  • Start the app: ${CYAN}./scripts/start-app.sh${NC}"
            echo -e "  • Access: ${CYAN}https://local.blb.lara${NC} (or your configured domain)"
            echo -e "  • No browser warnings! 🎉"
            echo ""
        else
            echo ""
            echo -e "${YELLOW}⚠${NC} SSL trust setup incomplete"
            echo ""
            echo -e "${CYAN}This is OK for self-signed certificates!${NC}"
            echo -e "You can:"
            echo -e "  • Accept the browser warning when accessing your app (safe for self-signed certs)"
            echo -e "  • Manually install the certificate from: ${CYAN}storage/app/ssl/caddy-root-ca.crt${NC}"
            echo -e "  • Re-run this script later: ${CYAN}./scripts/setup-steps/75-ssl-trust.sh${NC}"
            echo ""
        fi
    else
        echo -e "${RED}✗${NC} SSL trust setup function not available" >&2
        echo -e "  This shouldn't happen. Check that ${CYAN}scripts/shared/caddy.sh${NC} is present."
        exit 1
    fi
}

# Run main function
main "$@"
