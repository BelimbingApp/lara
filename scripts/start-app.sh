#!/bin/bash

# SPDX-License-Identifier: AGPL-3.0-only
# (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Source shared utilities
# shellcheck source=shared/colors.sh
source "$SCRIPT_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=shared/config.sh
source "$SCRIPT_DIR/shared/config.sh" 2>/dev/null || true
# shellcheck source=shared/validation.sh
source "$SCRIPT_DIR/shared/validation.sh" 2>/dev/null || true
# shellcheck source=shared/runtime.sh
source "$SCRIPT_DIR/shared/runtime.sh" 2>/dev/null || true
# shellcheck source=shared/caddy.sh
source "$SCRIPT_DIR/shared/caddy.sh" 2>/dev/null || true

if ! command -v stop_dev_services >/dev/null 2>&1; then
    echo -e "${RED}✗${NC} stop_dev_services is not available (failed to load shared/runtime.sh)" >&2
    exit 1
fi

# Global variables
LOG_FILE=""
PID_FILE=""
DEV_PID=""
APP_ENV=""
FRONTEND_DOMAIN=""
BACKEND_DOMAIN=""
HTTPS_PORT=""
APP_PORT=""
VITE_PORT=""

# Logging function
log() {
    if [ -n "$LOG_FILE" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S %Z')] $*" >> "$LOG_FILE"
    fi
}

# Check for required dependencies (verification only, no installation)
check_dependencies() {
    local missing=()
    local has_bun=false

    if ! command -v composer &> /dev/null; then
        missing+=("composer")
    fi

    # Check for bun first (replaces both node and npm)
    if command -v bun &> /dev/null; then
        has_bun=true
        echo -e "${CYAN}ℹ${NC} Using Bun (replaces Node.js and npm)"
    else
        # Fall back to node/npm if bun is not available
        if ! command -v node &> /dev/null; then
            missing+=("node")
        fi
        if ! command -v npm &> /dev/null; then
            missing+=("npm")
        fi
    fi

    if ! command -v caddy &> /dev/null; then
        missing+=("caddy")
    fi

    if [ ${#missing[@]} -gt 0 ]; then
        echo -e "${RED}✗${NC} Missing required dependencies:" >&2
        for dep in "${missing[@]}"; do
            echo -e "  ${BULLET} $dep" >&2
        done
        echo "" >&2
        echo -e "${YELLOW}Please run the setup script to install dependencies:${NC}" >&2
        echo -e "  ${CYAN}./scripts/setup.sh $APP_ENV${NC}" >&2
        echo "" >&2
        echo -e "${CYAN}Or install manually:${NC}" >&2
        if [[ " ${missing[*]} " =~ " composer " ]]; then
            echo -e "  • PHP/Composer: ${CYAN}./scripts/setup-steps/20-php.sh${NC}" >&2
        fi
        if [[ " ${missing[*]} " =~ " npm " ]] || [[ " ${missing[*]} " =~ " node " ]] || [[ " ${missing[*]} " =~ " bun " ]]; then
            echo -e "  • JavaScript runtime (Bun recommended): ${CYAN}./scripts/setup-steps/30-js.sh${NC}" >&2
        fi
        if [[ " ${missing[*]} " =~ " caddy " ]]; then
            echo -e "  • Caddy: ${CYAN}./scripts/setup-steps/70-caddy.sh${NC}" >&2
        fi
        log "ERROR: Missing dependencies: ${missing[*]}"
        exit 1
    fi

    if [ "$has_bun" = true ]; then
        echo -e "${GREEN}✓${NC} All dependencies available (using Bun)"
    else
        echo -e "${GREEN}✓${NC} All dependencies available (using Node.js/npm)"
    fi
}

# Read and validate APP_ENV
read_app_env() {
    # Read APP_ENV from .env file, default to 'local' if not found
    if [ -f "$PROJECT_ROOT/.env" ]; then
        APP_ENV=$(grep -E "^APP_ENV=" "$PROJECT_ROOT/.env" | cut -d '=' -f2 | tr -d '[:space:]' || echo "local")
        if [ -z "$APP_ENV" ]; then
            APP_ENV="local"
        fi
    else
        APP_ENV="local"
    fi

    # Validate APP_ENV using config.sh function
    if command -v normalize_and_validate_env >/dev/null 2>&1; then
        APP_ENV=$(normalize_and_validate_env "$APP_ENV")
    else
        # Fallback validation
        if [[ ! "$APP_ENV" =~ ^(local|staging|production|testing)$ ]]; then
            echo -e "${RED}✗${NC} Invalid APP_ENV: $APP_ENV" >&2
            echo -e "  Valid options: local, staging, production, testing" >&2
            log "ERROR: Invalid APP_ENV value: $APP_ENV"
            exit 1
        fi
    fi

    # Read domains from .env or use defaults
    if [ -f "$PROJECT_ROOT/.env" ]; then
        FRONTEND_DOMAIN=$(grep -E "^FRONTEND_DOMAIN=" "$PROJECT_ROOT/.env" | cut -d '=' -f2 | tr -d '[:space:]"'"'" || echo "")
        BACKEND_DOMAIN=$(grep -E "^BACKEND_DOMAIN=" "$PROJECT_ROOT/.env" | cut -d '=' -f2 | tr -d '[:space:]"'"'" || echo "")
    fi

    # Use defaults if not set
    if [ -z "$FRONTEND_DOMAIN" ]; then
        if command -v get_default_domains >/dev/null 2>&1; then
            FRONTEND_DOMAIN=$(get_default_domains "$APP_ENV" | cut -d'|' -f1)
        else
            FRONTEND_DOMAIN="${APP_ENV}.blb.lara"
        fi
    fi
    if [ -z "$BACKEND_DOMAIN" ]; then
        if command -v get_default_domains >/dev/null 2>&1; then
            BACKEND_DOMAIN=$(get_default_domains "$APP_ENV" | cut -d'|' -f2)
        else
            BACKEND_DOMAIN="${APP_ENV}.api.blb.lara"
        fi
    fi

    # Log environment info (important for troubleshooting)
    log "Environment: $APP_ENV, Frontend: $FRONTEND_DOMAIN, Backend: $BACKEND_DOMAIN"

    echo -e "${GREEN}Using environment: ${APP_ENV}${NC}"
}

# Check if domains are in /etc/hosts
check_hosts_entries() {
    local missing_hosts=()
    local result=0
    local hosts_note=""
    if is_wsl2; then
        hosts_note=" (WSL /etc/hosts — separate from Windows hosts)"
    fi

    # Check Linux /etc/hosts (uses shared domain_in_hosts: any IP, POSIX pattern)
    if ! domain_in_hosts "$FRONTEND_DOMAIN"; then
        missing_hosts+=("$FRONTEND_DOMAIN")
    fi

    if ! domain_in_hosts "$BACKEND_DOMAIN"; then
        missing_hosts+=("$BACKEND_DOMAIN")
    fi

    if [ ${#missing_hosts[@]} -gt 0 ]; then
        echo ""
        echo -e "${YELLOW}⚠${NC} The following domains are not in /etc/hosts${hosts_note}:"
        for domain in "${missing_hosts[@]}"; do
            echo -e "  ${BULLET} $domain"
        done
        echo ""
        echo -e "${CYAN}To add them, run:${NC}"
        echo -e "  ${YELLOW}sudo sh -c 'echo \"127.0.0.1 ${missing_hosts[*]}\" >> /etc/hosts'${NC}"
        echo ""
        echo -e "${CYAN}Or run the Caddy setup to add them automatically:${NC}"
        echo -e "  ${YELLOW}./scripts/setup-steps/70-caddy.sh $APP_ENV${NC}"
        echo ""
        log "WARNING: Missing hosts entries: ${missing_hosts[*]}"
        result=1
    else
        echo -e "${GREEN}✓${NC} Domains configured in /etc/hosts"
    fi

    # Check Windows hosts file if running in WSL2
    if is_wsl2; then
        local win_hosts
        win_hosts=$(get_windows_hosts_path)
        local wsl_ip
        wsl_ip=$(get_wsl2_ip)
        local win_missing=()
        local win_wrong_ip=()

        if [ -z "$wsl_ip" ]; then
            echo -e "${YELLOW}⚠${NC} Could not determine WSL2 IP address for Windows hosts file check"
            log "WARNING: Could not determine WSL2 IP address"
            return $result
        fi

        # Check if domains exist in Windows hosts file
        if ! domain_in_windows_hosts "$FRONTEND_DOMAIN"; then
            win_missing+=("$FRONTEND_DOMAIN")
        elif grep -E "^[[:space:]]*127\.0\.0\.1[[:space:]]+.*${FRONTEND_DOMAIN//./\\.}" "$win_hosts" 2>/dev/null | grep -v "^#" > /dev/null; then
            win_wrong_ip+=("$FRONTEND_DOMAIN")
        fi

        if ! domain_in_windows_hosts "$BACKEND_DOMAIN"; then
            win_missing+=("$BACKEND_DOMAIN")
        elif grep -E "^[[:space:]]*127\.0\.0\.1[[:space:]]+.*${BACKEND_DOMAIN//./\\.}" "$win_hosts" 2>/dev/null | grep -v "^#" > /dev/null; then
            win_wrong_ip+=("$BACKEND_DOMAIN")
        fi

        if [ ${#win_missing[@]} -gt 0 ] || [ ${#win_wrong_ip[@]} -gt 0 ]; then
            echo ""
            echo -e "${YELLOW}⚠${NC} Windows hosts file needs configuration (WSL2 detected):"

            if [ ${#win_missing[@]} -gt 0 ]; then
                echo -e "  ${YELLOW}Missing domains:${NC} ${win_missing[*]}"
            fi

            if [ ${#win_wrong_ip[@]} -gt 0 ]; then
                echo -e "  ${YELLOW}Wrong IP address (using 127.0.0.1 instead of WSL2 IP):${NC} ${win_wrong_ip[*]}"
            fi

            echo ""
            echo -e "${CYAN}WSL2 IP address: ${YELLOW}$wsl_ip${NC}"
            echo ""
            echo -e "${CYAN}Add/update this line in Windows hosts file:${NC}"
            echo -e "  ${YELLOW}$wsl_ip $FRONTEND_DOMAIN $BACKEND_DOMAIN${NC}"
            echo ""
            echo -e "${CYAN}Windows hosts file location:${NC}"
            echo -e "  ${YELLOW}C:\\Windows\\System32\\drivers\\etc\\hosts${NC}"
            echo ""
            echo -e "${CYAN}To fix:${NC}"
            echo -e "  1. Open Notepad as Administrator (Win+R → ${YELLOW}notepad${NC} → Ctrl+Shift+Enter)"
            echo -e "  2. Open: ${YELLOW}C:\\Windows\\System32\\drivers\\etc\\hosts${NC}"
            if [ ${#win_wrong_ip[@]} -gt 0 ]; then
                echo -e "  3. Remove/comment lines with ${YELLOW}127.0.0.1${NC} for these domains"
            fi
            echo -e "  4. Add: ${YELLOW}$wsl_ip $FRONTEND_DOMAIN $BACKEND_DOMAIN${NC}"
            echo -e "  5. Save and close"
            echo ""
            echo -e "${CYAN}Or use PowerShell (Run as Administrator):${NC}"
            if [ ${#win_wrong_ip[@]} -gt 0 ]; then
                echo -e "  ${YELLOW}\$content = Get-Content \"C:\\Windows\\System32\\drivers\\etc\\hosts\"; \$content = \$content | Where-Object { \$_ -notmatch \"127\\.0\\.0\\.1.*local\\.blb\\.lara\" -and \$_ -notmatch \"127\\.0\\.0\\.1.*local\\.api\\.blb\\.lara\" }; \$content | Set-Content \"C:\\Windows\\System32\\drivers\\etc\\hosts\"${NC}"
            fi
            echo -e "  ${YELLOW}Add-Content -Path \"C:\\Windows\\System32\\drivers\\etc\\hosts\" -Value \"$wsl_ip $FRONTEND_DOMAIN $BACKEND_DOMAIN\"${NC}"
            echo ""
            log "WARNING: Windows hosts file needs configuration. WSL2 IP: $wsl_ip"
            result=1
        else
            echo -e "${GREEN}✓${NC} Windows hosts file configured correctly (WSL2 IP: $wsl_ip)"
        fi
    fi

    return $result
}

# Get ports from configuration
get_ports() {
    if command -v get_frontend_port >/dev/null 2>&1 && command -v get_backend_port >/dev/null 2>&1; then
        VITE_PORT=$(get_frontend_port "$APP_ENV" "$PROJECT_ROOT")
        APP_PORT=$(get_backend_port "$APP_ENV" "$PROJECT_ROOT")
    else
        # Fallback to defaults
        case "$APP_ENV" in
            local) VITE_PORT=5173; APP_PORT=8000 ;;
            staging) VITE_PORT=5174; APP_PORT=8001 ;;
            production) VITE_PORT=5175; APP_PORT=8002 ;;
            testing) VITE_PORT=5176; APP_PORT=8003 ;;
            *) VITE_PORT=5173; APP_PORT=8000 ;;
        esac
    fi

    # Get HTTPS port
    if command -v get_https_port >/dev/null 2>&1; then
        HTTPS_PORT=$(get_https_port "$APP_ENV" "$PROJECT_ROOT")
    else
        # Fallback to defaults
        case "$APP_ENV" in
            local) HTTPS_PORT=443 ;;
            staging) HTTPS_PORT=444 ;;
            production) HTTPS_PORT=445 ;;
            testing) HTTPS_PORT=446 ;;
            *) HTTPS_PORT=443 ;;
        esac
    fi

    # Set environment variables
    export APP_ENV
    export VITE_PORT
    export APP_PORT

}

# Check if services are already running and stop them
check_and_stop_services() {
    local port=$1
    local max_attempts=5
    local attempt=1

    while [ $attempt -le $max_attempts ]; do
        if lsof -Pi :"$port" -sTCP:LISTEN -t >/dev/null 2>&1; then
            if [ $attempt -eq 1 ]; then
                echo -e "${YELLOW}Port $port is already in use. Stopping existing services...${NC}"
                log "Port $port is in use, stopping existing services (attempt $attempt/$max_attempts)"
            else
                echo -e "${YELLOW}Port $port still in use, retrying... (attempt $attempt/$max_attempts)${NC}"
                log "Port $port still in use, retrying (attempt $attempt/$max_attempts)"
            fi
            stop_dev_services "$APP_ENV" "$APP_PORT" "$VITE_PORT"
            sleep 1
            attempt=$((attempt + 1))
        else
            # Port is free
            return 0
        fi
    done

    # Final check - if still in use, report error
    if lsof -Pi :"$port" -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo -e "${RED}✗${NC} Port $port is still in use after $max_attempts attempts" >&2
        echo -e "${YELLOW}Please manually stop the process using port $port${NC}" >&2
        log "ERROR: Port $port still in use after $max_attempts attempts"
        exit 1
    fi
}

# Stop Caddy (project-specific instance only)
stop_caddy() {
    local caddyfile_path=""
    if [ -f "$PROJECT_ROOT/Caddyfile" ]; then
        caddyfile_path="$PROJECT_ROOT/Caddyfile"
    fi

    if [ -n "$caddyfile_path" ]; then
        # Only stop if it's a project-specific Caddy (not system Caddy)
        if pgrep -af "caddy" | grep -q "$caddyfile_path"; then
            echo -e "${CYAN}Stopping Caddy...${NC}"
            log "Stopping project Caddy instance"
            caddy stop --config "$caddyfile_path" 2>/dev/null || true
        fi
    fi
}

# Set up cleanup handler
cleanup() {
    echo ""
    echo -e "${YELLOW}Stopping services...${NC}"

    local stop_user
    stop_user=$(whoami 2>/dev/null || echo "${USER:-unknown}")
    log "[$stop_user] Stopping services"

    # Stop Caddy first (project-specific instance)
    stop_caddy

    # Use shared function to stop dev services
    if [ -n "${APP_ENV:-}" ]; then
        stop_dev_services "$APP_ENV" "$APP_PORT" "$VITE_PORT"
    else
        stop_dev_services "local"
    fi

    # Clean up PID file
    [ -f "$PID_FILE" ] && rm -f "$PID_FILE"

    log "Services stopped"
    exit 0
}

# Wait for services to start (with health check)
wait_for_service() {
    local url=$1
    local service_name=$2
    local max_attempts=30
    local attempt=1

    echo -e "${CYAN}Waiting for $service_name to be ready...${NC}"
    while [ $attempt -le $max_attempts ]; do
        if curl -s -f "$url" >/dev/null 2>&1 || curl -s -f -k "https://$url" >/dev/null 2>&1; then
            echo -e "${GREEN}✓${NC} $service_name is ready"
            return 0
        fi
        sleep 1
        attempt=$((attempt + 1))
    done

    echo -e "${YELLOW}⚠${NC} $service_name may not be fully ready, continuing anyway..."
    log "WARNING: $service_name may not be fully ready after $max_attempts attempts"
    return 1
}

# Start Caddy reverse proxy
start_caddy() {
    # Export variables for Caddyfile (used by template Caddyfile)
    export APP_DOMAIN="$FRONTEND_DOMAIN"
    export BACKEND_DOMAIN="$BACKEND_DOMAIN"
    export APP_PORT="$APP_PORT"
    export APP_HOST="127.0.0.1"  # Caddy connects to localhost (Laravel server)
    export VITE_PORT="$VITE_PORT"
    export VITE_HOST="127.0.0.1"  # Caddy connects to localhost (Vite server)
    export HTTPS_PORT="$HTTPS_PORT"

    # TLS Mode: environment-aware
    # - local/testing: Always use "internal" (self-signed Caddy certs)
    # - staging/production: Read from .env, default to "internal" for staging
    #   Production should set TLS_MODE in .env to an email (e.g., admin@example.com) for Let's Encrypt
    local tls_mode
    if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "testing" ]; then
        tls_mode="internal"
    else
        # Read from .env if set, otherwise default to "internal"
        if [ -f "$PROJECT_ROOT/.env" ]; then
            tls_mode=$(grep -E "^TLS_MODE=" "$PROJECT_ROOT/.env" | cut -d '=' -f2 | tr -d '[:space:]"'"'" || echo "internal")
            if [ -z "$tls_mode" ]; then
                tls_mode="internal"
            fi
        else
            tls_mode="internal"
        fi
    fi
    export TLS_MODE="$tls_mode"

    # Ensure .caddy/logs directory exists
    mkdir -p "$PROJECT_ROOT/.caddy/logs"

    # validate Caddyfile
    local caddyfile_path=""
    if [ -f "$PROJECT_ROOT/Caddyfile" ]; then
        caddyfile_path="$PROJECT_ROOT/Caddyfile"
    else
        echo -e "${RED}✗${NC} No Caddyfile found. Run setup first:" >&2
        echo -e "  ${CYAN}./scripts/setup-steps/70-caddy.sh $APP_ENV${NC}" >&2
        log "ERROR: No Caddyfile found"
        return 1
    fi

    echo -e "${CYAN}ℹ${NC} Using Caddyfile: ${caddyfile_path}"

    # Check if system Caddy is running (managed by systemd with /etc/caddy/Caddyfile)
    local system_caddy_running=false
    if pgrep -x "caddy" > /dev/null; then
        # Check if it's the system Caddy (uses /etc/caddy/Caddyfile)
        if pgrep -af "caddy" | grep -q "/etc/caddy/Caddyfile"; then
            system_caddy_running=true
            log "System Caddy detected (using /etc/caddy/Caddyfile)"
        fi
    fi

    if [ "$system_caddy_running" = true ]; then
        # System Caddy is running - start a separate instance for the project
        echo -e "${YELLOW}⚠${NC} System Caddy is running. Starting project-specific Caddy instance..."
        log "Starting project-specific Caddy instance (system Caddy detected)"

        # Stop any existing project Caddy first (if any)
        caddy stop --config "$caddyfile_path" 2>/dev/null || true

        # Start with a different admin socket to avoid conflicts
        caddy start --config "$caddyfile_path" --adapter caddyfile > /dev/null 2>&1
        CADDY_EXIT_CODE=${PIPESTATUS[0]}
        if [ "$CADDY_EXIT_CODE" -eq 0 ]; then
            echo -e "${GREEN}✓${NC} Project Caddy started successfully"
        else
            echo -e "${RED}✗${NC} Failed to start project Caddy (exit code: $CADDY_EXIT_CODE)" >&2
            log "ERROR: Failed to start project Caddy (exit code: $CADDY_EXIT_CODE)"
            return 1
        fi
    elif ! pgrep -x "caddy" > /dev/null; then
        # No Caddy running - start fresh
        echo -e "${GREEN}Starting Caddy reverse proxy...${NC}"
        caddy start --config "$caddyfile_path" --adapter caddyfile > /dev/null 2>&1
        CADDY_EXIT_CODE=${PIPESTATUS[0]}
        if [ "$CADDY_EXIT_CODE" -eq 0 ]; then
            echo -e "${GREEN}✓${NC} Caddy started successfully"
        else
            echo -e "${RED}✗${NC} Failed to start Caddy (exit code: $CADDY_EXIT_CODE)" >&2
            log "ERROR: Failed to start Caddy (exit code: $CADDY_EXIT_CODE)"
            cleanup
            # shellcheck disable=SC2317
            exit 1
        fi
    else
        # Non-system Caddy is already running - reload with project config
        echo -e "${YELLOW}Caddy is already running. Reloading configuration...${NC}"
        log "Caddy is already running, reloading configuration"
        caddy reload --config "$caddyfile_path" --adapter caddyfile > /dev/null 2>&1
        CADDY_EXIT_CODE=${PIPESTATUS[0]}
        if [ "$CADDY_EXIT_CODE" -eq 0 ]; then
            echo -e "${GREEN}✓${NC} Caddy configuration reloaded"
        else
            echo -e "${YELLOW}⚠${NC} Caddy reload may have failed (exit code: $CADDY_EXIT_CODE)" >&2
            echo -e "${YELLOW}Continuing anyway...${NC}" >&2
            log "WARNING: Caddy reload may have failed (exit code: $CADDY_EXIT_CODE)"
        fi
    fi
}

# launch_browser is provided by shared/runtime.sh

# Start development services
start_services() {
    echo -e "${GREEN}Starting Laravel server, Vite, queue worker, Reverb, and log watcher...${NC}"

    # Create a separate log file for dev services output
    local dev_log_file
    dev_log_file="$(get_logs_dir "$PROJECT_ROOT")/dev-services.log"

    # Start services in background, redirect output to log file
    # This keeps the terminal clean and prevents confusing termination messages
    composer run dev >> "$dev_log_file" 2>&1 &
    DEV_PID=$!

    # Store PID for cleanup
    echo "$DEV_PID" > "$PID_FILE"

    echo -e "${CYAN}ℹ${NC} Dev services output: ${dev_log_file}"
    echo -e "${CYAN}ℹ${NC} To watch: ${YELLOW}tail -f ${dev_log_file}${NC}"
}

# Main orchestration function
main() {
    cd "$PROJECT_ROOT"

    # Ensure storage directory structure exists
    ensure_storage_dirs "$PROJECT_ROOT"

    # Setup logging
    local log_dir
    log_dir=$(get_logs_dir "$PROJECT_ROOT")
    LOG_FILE="$log_dir/start-app.log"
    mkdir -p "$log_dir"

    local start_user
    start_user=$(whoami 2>/dev/null || echo "${USER:-unknown}")
    log "[$start_user] Starting BLB Development Environment..."
    echo -e "${GREEN}Starting BLB Development Environment...${NC}"

    # Initialize environment
    read_app_env
    get_ports

    # Check hosts entries (warn but don't block)
    check_hosts_entries || true

    # Check dependencies
    check_dependencies

    # Check and stop services if needed
    check_and_stop_services "$APP_PORT"

    # Store PID file path for cleanup
    PID_FILE="$PROJECT_ROOT/storage/app/.devops/start-app.pid"
    mkdir -p "$(dirname "$PID_FILE")"

    # Set up cleanup handler (before starting processes)
    trap cleanup INT TERM

    # Start services
    start_services

    # Wait for Laravel to be ready
    wait_for_service "http://127.0.0.1:$APP_PORT" "Laravel server" || true

    # Build the URL with port (omit port if 443)
    local frontend_url backend_url
    if [ "$HTTPS_PORT" = "443" ]; then
        frontend_url="https://${FRONTEND_DOMAIN}"
        backend_url="https://${BACKEND_DOMAIN}"
    else
        frontend_url="https://${FRONTEND_DOMAIN}:${HTTPS_PORT}"
        backend_url="https://${BACKEND_DOMAIN}:${HTTPS_PORT}"
    fi

    # Inform user that web app is ready (before Caddy starts and takes over terminal)
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✓ Belimbing is ready for access!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${CYAN}Access your application:${NC}"
    echo -e "  ${GREEN}Frontend:${NC} ${YELLOW}${frontend_url}${NC}"
    echo -e "  ${GREEN}Backend:${NC}  ${YELLOW}${backend_url}${NC}"
    echo ""
    echo -e "${CYAN}Internal services:${NC}"
    echo -e "  ${BULLET} Laravel: http://127.0.0.1:$APP_PORT"
    echo -e "  ${BULLET} Vite:    http://127.0.0.1:$VITE_PORT"
    echo -e "  ${BULLET} Reverb:  ws://127.0.0.1:8080"
    echo ""
    echo -e "${CYAN}Starting Caddy reverse proxy...${NC}"
    echo ""

    # Start Caddy
    start_caddy

    # Setup SSL certificate trust
    ensure_ssl_trust "$PROJECT_ROOT" "${TLS_MODE:-internal}" || true

    # Display final success message
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✓ Development environment is ready!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${CYAN}Log file:${NC} ${LOG_FILE}"
    echo ""
    echo -e "Press ${YELLOW}Ctrl+C${NC} to stop all services"

    # Launch browser if available
    launch_browser "$frontend_url" || true

    # Wait for background process
    wait "$DEV_PID" 2>/dev/null || true
}

# Run main function
main "$@"
