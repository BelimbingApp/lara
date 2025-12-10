#!/usr/bin/env bash
# scripts/setup-steps/00-environment.sh
# Title: Environment & Prerequisites
# Purpose: Validate environment and prepare basic structure for Belimbing
# Usage: ./scripts/setup-steps/00-environment.sh [local|staging|production|testing]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Detects operating system
# - Validates project structure (.env.example, composer.json)
# - Creates storage/ directories (logs, app/.devops, etc.)
# - Creates .env file from .env.example template
# - Saves APP_ENV and setup date to state
#
# Note: Laravel APP_KEY generation happens in 25-laravel.sh
# after PHP and Composer dependencies are installed.

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

# Environment (default to local if not provided, using Laravel standard)
APP_ENV="${1:-local}"


# Create required directories
create_directories() {
    echo -e "${CYAN}Creating required directories...${NC}"

    # Use ensure_storage_dirs from runtime.sh if available
    if command -v ensure_storage_dirs >/dev/null 2>&1; then
        ensure_storage_dirs "$PROJECT_ROOT"
        echo -e "${GREEN}✓${NC} Storage directories ready"
    else
        # Fallback: create directories manually
    local dirs=(
            "storage/app/.devops"
            "storage/logs/scripts"
            "storage/app/backups"
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
    fi
}

# Detect app name from git repository or directory
detect_app_name() {
    if command_exists git && [ -d "$PROJECT_ROOT/.git" ]; then
        local repo_name
        repo_name=$(git remote get-url origin 2>/dev/null | sed 's/.*\///' | sed 's/\.git$//' || echo "")
        if [ -n "$repo_name" ]; then
            echo "$repo_name"
            return 0
        fi
    fi

    # Fallback: use directory name
    basename "$PROJECT_ROOT"
}

# Detect admin email from git config
detect_admin_email() {
    if command_exists git; then
        git config user.email 2>/dev/null || echo ""
    fi
}

# Detect app URL based on environment
detect_app_url() {
    case "$APP_ENV" in
        local)
            echo "https://local.blb.lara"
            ;;
        staging)
            echo "https://staging.blb.lara"
            ;;
        production)
            echo "https://app.blb.lara"
            ;;
        testing)
            echo "https://testing.blb.lara"
            ;;
        *)
            echo "https://local.blb.lara"
            ;;
    esac
}

# Create .env file from .env.example with smart defaults
create_env_file() {
    if [ -f "$PROJECT_ROOT/.env" ]; then
        echo -e "${CYAN}ℹ${NC} .env file already exists"
        return 0
    fi

    echo -n "Creating .env file from .env.example... "
    cp "$PROJECT_ROOT/.env.example" "$PROJECT_ROOT/.env"
    echo -e "${GREEN}✓${NC}"

    # Auto-detect and set values
    local app_name detected_email app_url

    app_name=$(detect_app_name)
    detected_email=$(detect_admin_email)
    app_url=$(detect_app_url)

    echo -e "${CYAN}Auto-detecting configuration values...${NC}"

    # Set APP_NAME
    if [ -n "$app_name" ]; then
        update_env_file "$PROJECT_ROOT/.env" "APP_NAME" "$app_name"
        echo -e "  ${GREEN}✓${NC} APP_NAME: $app_name (from git repository)"
    fi

    # Set APP_ENV
    update_env_file "$PROJECT_ROOT/.env" "APP_ENV" "$APP_ENV"
    echo -e "  ${GREEN}✓${NC} APP_ENV: $APP_ENV"

    # Set APP_URL
    update_env_file "$PROJECT_ROOT/.env" "APP_URL" "$app_url"
    echo -e "  ${GREEN}✓${NC} APP_URL: $app_url (based on environment)"

    # Set APP_DEBUG based on environment
    if [ "$APP_ENV" = "production" ]; then
        update_env_file "$PROJECT_ROOT/.env" "APP_DEBUG" "false"
        echo -e "  ${GREEN}✓${NC} APP_DEBUG: false (production)"
    else
        update_env_file "$PROJECT_ROOT/.env" "APP_DEBUG" "true"
        echo -e "  ${GREEN}✓${NC} APP_DEBUG: true (development)"
    fi

    # Pre-populate database settings (will be updated by 40-database.sh if needed)
    # But set sensible defaults
    if ! grep -q "^DB_HOST=" "$PROJECT_ROOT/.env" || grep -q "^DB_HOST=$" "$PROJECT_ROOT/.env"; then
        update_env_file "$PROJECT_ROOT/.env" "DB_HOST" "127.0.0.1"
    fi
    if ! grep -q "^DB_PORT=" "$PROJECT_ROOT/.env" || grep -q "^DB_PORT=$" "$PROJECT_ROOT/.env"; then
        update_env_file "$PROJECT_ROOT/.env" "DB_PORT" "5432"
    fi

    # Pre-populate Redis settings
    if ! grep -q "^REDIS_HOST=" "$PROJECT_ROOT/.env" || grep -q "^REDIS_HOST=$" "$PROJECT_ROOT/.env"; then
        update_env_file "$PROJECT_ROOT/.env" "REDIS_HOST" "127.0.0.1"
    fi
    if ! grep -q "^REDIS_PORT=" "$PROJECT_ROOT/.env" || grep -q "^REDIS_PORT=$" "$PROJECT_ROOT/.env"; then
        update_env_file "$PROJECT_ROOT/.env" "REDIS_PORT" "6379"
    fi

    # Interactive prompts for critical settings (only if not detected and interactive)
    echo ""
    if [ -z "$detected_email" ] && [ -t 0 ]; then
        echo -e "${CYAN}Admin email not detected from git config${NC}"
        local admin_email
        admin_email=$(ask_input "Enter admin email address (optional, can be set later)" "")
        if [ -n "$admin_email" ]; then
            # Basic email validation
            if [[ "$admin_email" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
                save_to_setup_state "ADMIN_EMAIL" "$admin_email"
                echo -e "  ${GREEN}✓${NC} Admin email saved: $admin_email"
            else
                echo -e "  ${YELLOW}⚠${NC} Email format may be invalid, but saved anyway"
                save_to_setup_state "ADMIN_EMAIL" "$admin_email"
            fi
        fi
    elif [ -n "$detected_email" ]; then
        save_to_setup_state "ADMIN_EMAIL" "$detected_email"
        echo -e "  ${GREEN}✓${NC} Admin email: $detected_email (from git config)"
    fi

    echo ""
    echo -e "${YELLOW}Note:${NC} Review and update .env with your configuration"
    echo -e "${CYAN}ℹ${NC} APP_KEY will be generated in the Laravel setup step (after PHP/Composer installation)"
    echo -e "${CYAN}ℹ${NC} Database credentials will be configured in the database setup step"
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

    if [ -f "$PROJECT_ROOT/composer.json" ]; then
        echo -e "${GREEN}✓${NC} composer.json found"
    else
        echo -e "${RED}✗${NC} composer.json not found"
        echo -e "  This doesn't appear to be a Laravel project"
        exit 1
    fi

    echo ""

    # 2. Create storage directories
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
