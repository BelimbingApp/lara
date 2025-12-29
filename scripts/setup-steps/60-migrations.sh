#!/usr/bin/env bash
# scripts/setup-steps/60-migrations.sh
# Title: Database Migrations
# Purpose: Run Laravel migrations and create initial admin user
# Usage: ./scripts/setup-steps/60-migrations.sh [local|staging|production|testing]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Runs database migrations
# - Creates admin user (interactive or from env vars)
# - Clears and rebuilds application caches
#
# Prerequisites:
# - PHP and Composer installed (20-php.sh)
# - Laravel configured with APP_KEY (25-laravel.sh)
# - Database configured and accessible (40-database.sh)

set -euo pipefail

# Get script directory and project root
SETUP_STEPS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPTS_DIR="$(cd "$SETUP_STEPS_DIR/.." && pwd)"
PROJECT_ROOT="$(cd "$SCRIPTS_DIR/.." && pwd)"

# Source shared utilities
# shellcheck source=../shared/colors.sh
source "$SCRIPTS_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=../shared/runtime.sh
source "$SCRIPTS_DIR/shared/runtime.sh" 2>/dev/null || true
# shellcheck source=../shared/config.sh
source "$SCRIPTS_DIR/shared/config.sh" 2>/dev/null || true
# shellcheck source=../shared/validation.sh
source "$SCRIPTS_DIR/shared/validation.sh" 2>/dev/null || true
# shellcheck source=../shared/interactive.sh
source "$SCRIPTS_DIR/shared/interactive.sh" 2>/dev/null || true

# Environment (default to local if not provided, using Laravel standard)
APP_ENV="${1:-local}"

# Run database migrations
run_migrations() {
    echo -e "${CYAN}Running database migrations...${NC}"

    # Run migrations
    if php artisan migrate --force; then
        echo -e "${GREEN}✓${NC} Database migrations completed"
        return 0
    else
        echo -e "${RED}✗${NC} Migration failed" >&2
        echo -e "  Run ${CYAN}php artisan migrate${NC} manually" >&2
        return 1
    fi
}

# Create admin user via artisan command
create_admin_user() {
    echo -e "${CYAN}Creating admin user...${NC}"

    # Check if users table exists
    if ! php artisan tinker --execute="Schema::hasTable('users');" 2>/dev/null | grep -q "true"; then
        echo -e "${YELLOW}⚠${NC} Users table not found, skipping admin creation"
        echo -e "  Run migrations first, then create admin with: ${CYAN}php artisan belimbing:create-admin${NC}"
        return 0
    fi

    # Check for email from setup state
    local admin_email admin_password
    admin_email=$(grep -E "^ADMIN_EMAIL=" "$(get_setup_state_file)" 2>/dev/null | cut -d '=' -f2 | tr -d '"' || echo "")

    if [ -t 0 ]; then
        # Interactive mode: prompt for credentials
        if [ -z "$admin_email" ]; then
            admin_email=$(ask_input "Admin email address" "admin@example.com")
        else
            echo -e "  Using email from setup state: ${CYAN}$admin_email${NC}"
        fi

        admin_password=$(ask_password "Admin password (min 8 chars)")
        if [ -z "$admin_password" ]; then
            echo -e "${YELLOW}⚠${NC} No password provided, skipping admin creation"
            echo -e "  Create admin later with: ${CYAN}php artisan belimbing:create-admin${NC}"
            return 0
        fi

        # Use STDIN for password (secure - not visible in process list)
        # Command will skip if users already exist (returns success)
        if echo "$admin_password" | php artisan belimbing:create-admin "$admin_email" --stdin; then
            return 0
        else
            echo -e "${RED}✗${NC} Failed to create admin user" >&2
            return 1
        fi
    else
        # Non-interactive mode: check for password file or skip
        local password_file="${ADMIN_PASSWORD_FILE:-}"

        if [ -z "$admin_email" ]; then
            echo -e "${YELLOW}⚠${NC} Non-interactive mode: ADMIN_EMAIL not found in setup state"
            echo -e "  Set ADMIN_EMAIL in setup state or create admin later with: ${CYAN}php artisan belimbing:create-admin${NC}"
            return 0
        fi

        if [ -n "$password_file" ] && [ -f "$password_file" ]; then
            # Read password from file (more secure than env var)
            admin_password=$(cat "$password_file" | tr -d '\n')
            if [ -n "$admin_password" ]; then
                # Command will skip if users already exist (returns success)
                if echo "$admin_password" | php artisan belimbing:create-admin "$admin_email" --stdin; then
                    return 0
                else
                    echo -e "${RED}✗${NC} Failed to create admin user" >&2
                    return 1
                fi
            fi
        fi

        echo -e "${YELLOW}⚠${NC} Non-interactive mode: set ADMIN_PASSWORD_FILE environment variable"
        echo -e "  Example: ${CYAN}ADMIN_PASSWORD_FILE=/secure/path/to/password.txt${NC}"
        echo -e "  Or create admin later with: ${CYAN}php artisan belimbing:create-admin${NC}"
        return 0
    fi
}

# Rebuild application caches
rebuild_caches() {
    echo -e "${CYAN}Rebuilding application caches...${NC}"

    # Only cache in production/staging environments
    if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
        php artisan config:cache 2>/dev/null || true
        php artisan route:cache 2>/dev/null || true
        php artisan view:cache 2>/dev/null || true
        echo -e "${GREEN}✓${NC} Caches rebuilt"
    else
        # Clear caches in development
        php artisan config:clear 2>/dev/null || true
        php artisan route:clear 2>/dev/null || true
        php artisan view:clear 2>/dev/null || true
        echo -e "${GREEN}✓${NC} Caches cleared (development mode)"
    fi
}

# Main setup function
main() {
    print_section_banner "Database Migrations - Belimbing ($APP_ENV)"

    # Load existing configuration
    load_setup_state

    # Check prerequisites
    print_subsection_header "Prerequisites"

    if ! command_exists php; then
        echo -e "${RED}✗${NC} PHP not found" >&2
        echo -e "  Run ${CYAN}./scripts/setup-steps/20-php.sh${NC} first" >&2
        exit 1
    fi
    echo -e "${GREEN}✓${NC} PHP available"

    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        echo -e "${RED}✗${NC} Laravel artisan not found" >&2
        echo -e "  Run ${CYAN}./scripts/setup-steps/25-laravel.sh${NC} first" >&2
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Laravel artisan available"

    # Check database connection
    if ! php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
        echo -e "${RED}✗${NC} Database connection failed" >&2
        echo -e "  Run ${CYAN}./scripts/setup-steps/40-database.sh${NC} first" >&2
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Database connection available"
    echo ""

    # Run migrations
    print_subsection_header "Database Migrations"
    if ! run_migrations; then
        exit 1
    fi
    echo ""

    # Create admin user
    print_subsection_header "Admin User"
    create_admin_user
    echo ""

    # Rebuild caches
    print_subsection_header "Application Caches"
    rebuild_caches
    echo ""

    # Save state
    save_to_setup_state "MIGRATIONS_RUN" "true"

    print_divider
    echo ""
    echo -e "${GREEN}✓ Database migrations complete!${NC}"
    echo ""
    echo -e "${CYAN}Next steps:${NC}"
    echo -e "  • Start development: ${CYAN}./scripts/start-app.sh${NC}"
    echo ""
}

# Run main function
main "$@"
