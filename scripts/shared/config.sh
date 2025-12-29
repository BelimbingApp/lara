#!/bin/bash
# Belimbing Configuration Variables
# Single source of truth for configuration defaults and helper functions
#
# === Configuration Variables ===
# These are loaded from .env file at runtime by start-app.sh
# and from storage/app/.devops/setup.env during setup steps.
#
# Expected variables:
# - APP_ENV: local|staging|production|testing
# - DATABASE_URL: PostgreSQL connection string
# - JWT_SECRET, JWT_EXPIRATION_HOURS, JWT_REFRESH_EXPIRATION_DAYS
# - FRONTEND_DOMAIN, BACKEND_DOMAIN
# - BACKEND_PORT, FRONTEND_PORT, HTTPS_PORT
# - SKIP_CADDY, PROXY_TYPE
# - BACKEND_URL

# Source version constants (must be sourced before using version-related functions)
# Determine script directory from this file's location
_CONFIG_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Source versions.sh if it exists
# shellcheck source=versions.sh
if [ -f "$_CONFIG_DIR/versions.sh" ]; then
    source "$_CONFIG_DIR/versions.sh" 2>/dev/null || true
fi
unset _CONFIG_DIR

# === Setup Defaults ===
# shellcheck disable=SC2034
# Used by setup scripts to provide sensible defaults during interactive setup
DEFAULT_DB_USER="belimbing_app"
DEFAULT_DB_PASSWORD="v3ryL0ngP@55w0rd"
DEFAULT_DB_PORT="5432"
DEFAULT_PROXY_TYPE="caddy"
DEFAULT_SKIP_CADDY="false"

# Get default ports for an environment
# Returns: FRONTEND_PORT|BACKEND_PORT|HTTPS_PORT
get_default_ports() {
    local env=$1
    case "$env" in
        local)
            echo "5173|8000|443"
            ;;
        staging)
            echo "5174|8001|444"
            ;;
        production)
            echo "5175|8002|445"
            ;;
        testing)
            echo "5176|8003|446"
            ;;
        *)
            echo "5173|8000|443"
            ;;
    esac
}

# Get backend port for environment (reads from .env or uses default)
get_backend_port() {
    local env=$1
    local project_root=${2:-$PROJECT_ROOT}

    # Try to read from .env file
    if [ -f "$project_root/.env" ]; then
        local port
        port=$(grep -E "^APP_PORT=" "$project_root/.env" | cut -d '=' -f2 | tr -d '[:space:]' 2>/dev/null || echo "")
        if [ -n "$port" ] && [[ "$port" =~ ^[0-9]+$ ]]; then
            echo "$port"
            return 0
        fi
    fi

    # Use default based on environment
    local defaults
    defaults=$(get_default_ports "$env")
    echo "$defaults" | cut -d'|' -f2
}

# Get frontend port for environment (reads from .env or uses default)
get_frontend_port() {
    local env=$1
    local project_root=${2:-$PROJECT_ROOT}

    # Try to read from .env file
    if [ -f "$project_root/.env" ]; then
        local port
        port=$(grep -E "^VITE_PORT=" "$project_root/.env" | cut -d '=' -f2 | tr -d '[:space:]' 2>/dev/null || echo "")
        if [ -n "$port" ] && [[ "$port" =~ ^[0-9]+$ ]]; then
            echo "$port"
            return 0
        fi
    fi

    # Use default based on environment
    local defaults
    defaults=$(get_default_ports "$env")
    echo "$defaults" | cut -d'|' -f1
}

# Get HTTPS port for environment (reads from .env or uses default)
get_https_port() {
    local env=$1
    local project_root=${2:-$PROJECT_ROOT}

    # Try to read from .env file
    if [ -f "$project_root/.env" ]; then
        local port
        port=$(grep -E "^HTTPS_PORT=" "$project_root/.env" | cut -d '=' -f2 | tr -d '[:space:]' 2>/dev/null || echo "")
        if [ -n "$port" ] && [[ "$port" =~ ^[0-9]+$ ]]; then
            echo "$port"
            return 0
        fi
    fi

    # Use default based on environment
    local defaults
    defaults=$(get_default_ports "$env")
    echo "$defaults" | cut -d'|' -f3
}

# Get default domains for an environment
# Returns: frontend_domain|backend_domain
# Pattern: ${env}.blb.lara for frontend, ${env}.api.blb.lara for backend
get_default_domains() {
    local env=$1
    case "$env" in
        production)
            echo "app.blb.lara|api.blb.lara"
            ;;
        *)
            echo "${env}.blb.lara|${env}.api.blb.lara"
            ;;
    esac
}

# Get default JWT expiration values
get_default_jwt_expiration() {
    echo "24|30"
}

# Get default database name for an environment
get_default_database_name() {
    local env=$1
    echo "belimbing_${env}"
}

# === Environment Validation ===
# Normalize and validate APP_ENV variable
# Usage: normalize_and_validate_env "$APP_ENV"
# Returns: Normalized environment name (Laravel standard: local, staging, production, testing)
# Exits with code 1 if invalid
normalize_and_validate_env() {
    local env=$1

    # Normalize environment name (support old values for backward compatibility)
    case "$env" in
        dev|development) env="local" ;;
        stage) env="staging" ;;
        prod) env="production" ;;
        test) env="testing" ;;
    esac

    # Validate environment type (Laravel standard values)
    if [[ ! "$env" =~ ^(local|staging|production|testing)$ ]]; then
        echo -e "${RED}✗${NC} Invalid environment: $env" >&2
        echo -e "  Valid options: local, staging, production, testing" >&2
        exit 1
    fi

    echo "$env"
}

# === State Management ===
# Setup state file location
get_setup_state_file() {
    if [ -z "${PROJECT_ROOT:-}" ]; then
        echo "Error: PROJECT_ROOT not set" >&2
        return 1
    fi
    echo "$PROJECT_ROOT/storage/app/.devops/setup.env"
}

# Initialize setup state file with header if it doesn't exist
init_setup_state_file() {
    local state_file
    state_file=$(get_setup_state_file)

    if [ ! -f "$state_file" ]; then
        mkdir -p "$(dirname "$state_file")"
        cat > "$state_file" << 'EOF'
# Belimbing Setup State
# This file is generated by the setup scripts
# Do not edit manually - changes may be overwritten
EOF
    fi
}

# Load setup state from storage/app/.devops/setup.env
# Preserves CLI-provided APP_ENV if set before calling this function
# Automatically normalizes and validates APP_ENV after loading
load_setup_state() {
    local state_file
    state_file=$(get_setup_state_file)

    # Preserve CLI-provided APP_ENV (takes precedence over state files)
    local cli_app_env="${APP_ENV:-}"

    if [ -f "$state_file" ]; then
        # shellcheck disable=SC1090
        source "$state_file" 2>/dev/null || true
    fi

    # Also try loading from .env, make .env variables available
    if [ -f "$PROJECT_ROOT/.env" ]; then
        set -a
        source "$PROJECT_ROOT/.env" 2>/dev/null || true
        set +a
    fi

    # Restore CLI argument if it was set (CLI takes precedence)
    if [ -n "$cli_app_env" ]; then
        APP_ENV="$cli_app_env"
    fi

    # Normalize and validate APP_ENV (from CLI, state file, or .env)
    APP_ENV=$(normalize_and_validate_env "$APP_ENV")
}

# Save a key-value pair to setup state file
# Usage: save_to_setup_state "KEY" "value"
save_to_setup_state() {
    local key=$1
    local value=$2
    local state_file
    state_file=$(get_setup_state_file)

    # Ensure state file exists with header
    init_setup_state_file

    # Remove existing key if present
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "/^${key}=/d" "$state_file" 2>/dev/null || true
    else
        sed -i "/^${key}=/d" "$state_file" 2>/dev/null || true
    fi

    # Append new value
    echo "${key}=\"${value}\"" >> "$state_file"
}

# Escape special characters for sed replacement string
# In sed replacement, we need to escape: &, \, and the delimiter #
escape_sed_replacement() {
    local value="$1"
    # Escape backslashes first (must be first)
    value="${value//\\/\\\\}"
    # Escape ampersands
    value="${value//&/\\&}"
    # Escape the delimiter # (we use # instead of | for better compatibility)
    value="${value//#/\\#}"
    printf '%s' "$value"
}

# Update or add a variable to the .env file
# Usage: update_env_file "/path/to/.env" "VARIABLE_NAME" "value"
update_env_file() {
    local env_file=$1
    local var_name=$2
    local var_value=$3

    if [ ! -f "$env_file" ]; then
        echo "$var_name=\"$var_value\"" > "$env_file"
        return 0
    fi

    # Check if variable exists in file
    if grep -q "^${var_name}=" "$env_file"; then
        # Escape special characters in the replacement string
        local escaped_value
        escaped_value=$(escape_sed_replacement "$var_value")

        # Escape variable name for use in sed pattern (escape special regex chars: . * ^ $ [ ] ( ) + ? { |)
        local escaped_var_name="${var_name}"
        escaped_var_name="${escaped_var_name//\./\\.}"
        escaped_var_name="${escaped_var_name//\*/\\*}"
        escaped_var_name="${escaped_var_name//\^/\\^}"
        escaped_var_name="${escaped_var_name//\$/\\$}"
        escaped_var_name="${escaped_var_name//\[/\\[}"
        escaped_var_name="${escaped_var_name//\]/\\]}"
        escaped_var_name="${escaped_var_name//\(/\\(}"
        escaped_var_name="${escaped_var_name//\)/\\)}"
        escaped_var_name="${escaped_var_name//\+/\\+}"
        escaped_var_name="${escaped_var_name//\?/\\?}"
        escaped_var_name="${escaped_var_name//\{/\\{}"
        escaped_var_name="${escaped_var_name//\|/\\|}"

        # Use # as delimiter (unlikely to appear in variable names or values)
        local sed_pattern="s#^${escaped_var_name}=.*#${var_name}=\"${escaped_value}\"#"

        # Update existing variable (handle both quoted and unquoted values)
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS sed syntax
            sed -i '' "$sed_pattern" "$env_file" || return 1
        else
            # Linux sed syntax
            sed -i "$sed_pattern" "$env_file" || return 1
        fi
    else
        # Append new variable
        echo "$var_name=\"$var_value\"" >> "$env_file"
    fi
}
