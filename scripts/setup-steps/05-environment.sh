#!/usr/bin/env bash
# scripts/setup-steps/05-environment.sh
# Title: Environment & Prerequisites
# Purpose: Prepare storage directories and .env for Belimbing
# Usage: ./scripts/setup-steps/05-environment.sh [local|staging|production]
#
# This script:
# - Creates storage/ directories (logs, app/.devops, etc.)
# - Creates .env file from .env.example with auto-detected defaults
# - Saves APP_ENV and setup date to state
#
# Note: APP_KEY generation happens in 25-laravel.sh after Composer install.

set -euo pipefail

SETUP_STEPS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPTS_DIR="$(cd "$SETUP_STEPS_DIR/.." && pwd)"
PROJECT_ROOT="$(cd "$SCRIPTS_DIR/.." && pwd)"

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

APP_ENV="${1:-local}"

# Detect app name from git remote or directory name.
detect_app_name() {
    if command_exists git && [[ -d "$PROJECT_ROOT/.git" ]]; then
        local repo_name
        repo_name=$(git remote get-url origin 2>/dev/null | sed 's/.*\///' | sed 's/\.git$//' || echo "")
        if [[ -n "$repo_name" ]]; then
            echo "$repo_name"
            return 0
        fi
    fi
    basename "$PROJECT_ROOT"
}

# Detect app URL based on environment.
detect_app_url() {
    case "$APP_ENV" in
        staging)    echo "https://staging.blb.lara" ;;
        production) echo "https://app.blb.lara" ;;
        *)          echo "https://local.blb.lara" ;;
    esac
}

# Detect default APP_DEBUG based on environment.
detect_app_debug() {
    case "$APP_ENV" in
        production) echo "false" ;;
        *)          echo "true" ;;
    esac
}

# Create .env file from .env.example, prompting for each value.
create_env_file() {
    if [[ -f "$PROJECT_ROOT/.env" ]]; then
        echo -e "${CYAN}ℹ${NC} .env file already exists"
        return 0
    fi

    cp "$PROJECT_ROOT/.env.example" "$PROJECT_ROOT/.env"
    echo -e "${GREEN}✓${NC} Created .env from .env.example"
    echo ""

    local app_name app_url app_debug
    app_name=$(ask_input "APP_NAME" "$(detect_app_name)")
    app_url=$(ask_input "APP_URL" "$(detect_app_url)")
    app_debug=$(ask_input "APP_DEBUG" "$(detect_app_debug)")

    update_env_file "APP_NAME" "$app_name"
    update_env_file "APP_ENV" "$APP_ENV"
    update_env_file "APP_URL" "$app_url"
    update_env_file "APP_DEBUG" "$app_debug"

    update_env_file_if_missing "DB_HOST" "127.0.0.1"
    update_env_file_if_missing "DB_PORT" "5432"
    update_env_file_if_missing "REDIS_HOST" "127.0.0.1"
    update_env_file_if_missing "REDIS_PORT" "6379"
}

# === Main ===

print_section_banner "Environment Setup ($APP_ENV)"

echo -e "${CYAN}Creating storage directories...${NC}"
ensure_storage_dirs "$PROJECT_ROOT"
echo -e "${GREEN}✓${NC} Storage directories ready"
echo ""

load_setup_state

create_env_file
echo ""

save_to_setup_state "APP_ENV" "$APP_ENV"
save_to_setup_state "SETUP_DATE" "$(date +"%Y-%m-%dT%H:%M:%S%z")"

echo -e "${GREEN}✓ Environment setup complete!${NC}"
