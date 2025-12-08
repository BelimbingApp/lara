#!/usr/bin/env bash
# scripts/setup-steps/00-environment.sh
# Title: Environment & Prerequisites
# Purpose: Validate environment and prepare basic structure for Belimbing
# Usage: ./scripts/setup-steps/00-environment.sh [dev|stage|prod]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Detects operating system
# - Validates project structure (.env.example, Cargo.toml)
# - Creates var/ directories (logs, data, tmp)
# - Creates .env file from .env.example template
# - Generates secure JWT_SECRET
# - Saves APP_ENV and setup date to state

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

# Environment (default to dev if not provided)
APP_ENV="${1:-dev}"

# Generate a secure random JWT secret
generate_jwt_secret() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 32 2>/dev/null
    else
        # Fallback: use /dev/urandom
        local secret
        secret=$(head -c 32 /dev/urandom 2>/dev/null | xxd -p -c 32 | head -1 || echo "")
        if [ -z "$secret" ]; then
            # Last resort: use date + random
            secret="$(date +%s | sha256sum | cut -d' ' -f1)$(head -c 16 /dev/urandom 2>/dev/null | xxd -p | head -1 || echo '')"
        fi
        echo "$secret"
    fi
}

# Create required directories
create_directories() {
    echo -e "${CYAN}Creating required directories...${NC}"

    local dirs=(
        "var/logs"
        "var/data"
        "var/tmp"
    )

    for dir in "${dirs[@]}"; do
        local full_path="$PROJECT_ROOT/$dir"
        if [ ! -d "$full_path" ]; then
            mkdir -p "$full_path"
            echo -e "  ${GREEN}✓${NC} Created: $dir"
        else
            echo -e "  ${CYAN}ℹ${NC} Exists: $dir"
        fi
    done

    echo -e "${GREEN}✓${NC} Directories ready"
}

# Create .env file from .env.example if it doesn't exist
create_env_file() {
    if [ -f "$PROJECT_ROOT/.env" ]; then
        echo -e "${CYAN}ℹ${NC} .env file already exists"
        return 0
    fi

    echo -n "Creating .env file from .env.example... "
    cp "$PROJECT_ROOT/.env.example" "$PROJECT_ROOT/.env"
    echo -e "${GREEN}✓${NC}"

    # Generate and set JWT_SECRET
    echo -n "Generating secure JWT_SECRET... "
    local jwt_secret
    jwt_secret=$(generate_jwt_secret)

    if [ -n "$jwt_secret" ]; then
        update_env_file "$PROJECT_ROOT/.env" "JWT_SECRET" "$jwt_secret"
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}⚠${NC} Failed to generate JWT_SECRET, using placeholder"
    fi

    echo -e "${YELLOW}Note:${NC} Review and update .env with your configuration"
}

# Main setup function
# 1. Validate project structure first - fail fast if not in correct directory
# 2. Create var directories
# 3. Create .env from .env.example if it doesn't exist
# 4. Save state
main() {
    print_section_banner "Environment Setup - Belimbing ($APP_ENV)"

    # Load existing configuration
    load_setup_state

    echo -e "${CYAN}Environment:${NC} ${GREEN}$APP_ENV${NC}"

    # Detect OS
    local os_type
    os_type=$(detect_os)
    echo -e "${CYAN}Operating System:${NC} ${GREEN}$os_type${NC}"
    echo ""

    # 1. Validate project structure
    print_subsection_header "Validating Project Structure"

    if [ -f "$PROJECT_ROOT/.env.example" ]; then
        echo -e "${GREEN}✓${NC} .env.example found"
    else
        echo -e "${RED}✗${NC} .env.example not found"
        echo -e "  Expected at: ${CYAN}$PROJECT_ROOT/.env.example${NC}"
        exit 1
    fi

    if [ -f "$PROJECT_ROOT/Cargo.toml" ]; then
        echo -e "${GREEN}✓${NC} Cargo.toml found"
    else
        echo -e "${RED}✗${NC} Cargo.toml not found"
        echo -e "  This doesn't appear to be a Rust project"
        exit 1
    fi

    echo ""

    # 2. Create var directories
    print_subsection_header "Creating Directories"
    create_directories
    echo ""

    # 3. Create .env file
    create_env_file
    echo ""

    # 4. Save state
    save_to_setup_state "APP_ENV" "$APP_ENV"
    save_to_setup_state "SETUP_DATE" "$(date +"%Y-%m-%dT%H:%M:%S%z")"

    print_divider
    echo ""
    echo -e "${GREEN}✓ Environment setup complete!${NC}"
    echo ""
    echo -e "Configuration saved to: ${CYAN}$(get_setup_state_file)${NC}"
    echo ""
}

# Run main function
main "$@"
