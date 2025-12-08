#!/bin/bash

# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong

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

cd "$PROJECT_ROOT"

# Ensure storage directory structure exists
ensure_storage_dirs "$PROJECT_ROOT"

# Setup logging
LOG_DIR=$(get_logs_dir "$PROJECT_ROOT")
LOG_FILE="$LOG_DIR/start-app.log"
mkdir -p "$LOG_DIR"

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

log "Starting BLB Development Environment..."

echo -e "${GREEN}Starting BLB Development Environment...${NC}"

# Read APP_ENV from .env file, default to 'local' if not found
if [ -f .env ]; then
    APP_ENV=$(grep -E "^APP_ENV=" .env | cut -d '=' -f2 | tr -d '[:space:]' || echo "local")
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

# Construct domain based on APP_ENV
DOMAIN="${APP_ENV}.blb.lara"

log "Using environment: $APP_ENV"
log "Domain: https://$DOMAIN"

echo -e "${GREEN}Using environment: ${APP_ENV}${NC}"
echo -e "${GREEN}Domain: https://${DOMAIN}${NC}"

# Check if Caddy is installed
if ! command -v caddy &> /dev/null; then
    echo -e "${YELLOW}Caddy is not installed. Please install it first.${NC}"
    echo "Visit: https://caddyserver.com/docs/install"
    exit 1
fi

# Set environment variables
export APP_ENV
export VITE_PORT=5173
export APP_PORT=8000

# Check if services are already running and stop them
# Add retry loop to ensure ports are free
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
            "$SCRIPT_DIR/stop-app.sh" "$APP_ENV"
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

# Check and stop services if needed
check_and_stop_services 8000

# Store PID file path for cleanup
PID_FILE="$PROJECT_ROOT/storage/app/.devops/start-app.pid"
mkdir -p "$(dirname "$PID_FILE")"

# Set up cleanup handler (before starting processes)
cleanup() {
    echo ""
    echo -e "${YELLOW}Stopping services...${NC}"
    log "Stopping services (cleanup triggered)"
    if [ -f "$PID_FILE" ]; then
        local dev_pid
        dev_pid=$(cat "$PID_FILE" 2>/dev/null || echo "")
        if [ -n "$dev_pid" ] && kill -0 "$dev_pid" 2>/dev/null; then
            log "Stopping process $dev_pid"
            kill "$dev_pid" 2>/dev/null || true
            wait "$dev_pid" 2>/dev/null || true
        fi
        rm -f "$PID_FILE"
    fi
    # Also kill by process group to catch concurrently subprocesses
    pkill -f "concurrently" 2>/dev/null || true
    log "Services stopped"
    echo -e "${GREEN}Services stopped.${NC}"
    exit 0
}

trap cleanup INT TERM

# Start all services using concurrently
echo -e "${GREEN}Starting Laravel server, Vite, queue worker, and log watcher...${NC}"

# Start services in background and track the PID
composer run dev &
DEV_PID=$!

# Store PID for cleanup
echo "$DEV_PID" > "$PID_FILE"

# Wait for services to start (with health check)
wait_for_service() {
    local url=$1
    local service_name=$2
    local max_attempts=30
    local attempt=1

    echo -e "${CYAN}Waiting for $service_name to be ready...${NC}"
    log "Waiting for $service_name to be ready at $url"

    while [ $attempt -le $max_attempts ]; do
        if curl -s -f "$url" >/dev/null 2>&1 || curl -s -f -k "https://$url" >/dev/null 2>&1; then
            echo -e "${GREEN}✓${NC} $service_name is ready"
            log "$service_name is ready after $attempt attempt(s)"
            return 0
        fi
        sleep 1
        attempt=$((attempt + 1))
    done

    echo -e "${YELLOW}⚠${NC} $service_name may not be fully ready, continuing anyway..."
    log "WARNING: $service_name may not be fully ready after $max_attempts attempts"
    return 1
}

# Wait for Laravel to be ready
wait_for_service "http://127.0.0.1:8000" "Laravel server" || true

# Generate Caddyfile dynamically for the environment
generate_caddyfile() {
    local env=$1
    local domain=$2
    local vite_port=${VITE_PORT:-5173}
    local app_port=${APP_PORT:-8000}
    local caddyfile="$PROJECT_ROOT/Caddyfile"

    log "Generating Caddyfile for environment: $env"

    cat > "$caddyfile" << EOF
# Auto-generated Caddyfile for $env environment
# Generated by start-app.sh - do not edit manually

$domain {
	# Enable automatic HTTPS for .blb.lara domains
	tls internal

	# Logging
	log {
		output file .caddy/logs/${env}-access.log
		format console
	}

	# Proxy Vite assets to Vite dev server
	handle /build/* {
		reverse_proxy http://127.0.0.1:$vite_port {
			header_up Host {host}
			header_up X-Real-IP {remote_host}
			header_up X-Forwarded-Proto {scheme}
		}
	}

	handle /assets/* {
		reverse_proxy http://127.0.0.1:$vite_port {
			header_up Host {host}
			header_up X-Real-IP {remote_host}
			header_up X-Forwarded-Proto {scheme}
		}
	}

	# Proxy all other requests to Laravel
	reverse_proxy http://127.0.0.1:$app_port {
		header_up Host {host}
		header_up X-Real-IP {remote_host}
		header_up X-Forwarded-Proto {scheme}
		header_up X-Forwarded-Port {server_port}
	}
}
EOF

    # Ensure .caddy/logs directory exists
    mkdir -p "$PROJECT_ROOT/.caddy/logs"

    log "Caddyfile generated at $caddyfile"
    echo -e "${GREEN}✓${NC} Caddyfile generated for $env environment"
}

# Generate Caddyfile for current environment
generate_caddyfile "$APP_ENV" "$DOMAIN"

# Check if Caddy is already running
if ! pgrep -x "caddy" > /dev/null; then
    echo -e "${GREEN}Starting Caddy reverse proxy...${NC}"
    log "Starting Caddy reverse proxy"
    caddy start --config Caddyfile 2>&1 | tee -a "$LOG_FILE"
    CADDY_EXIT_CODE=${PIPESTATUS[0]}
    if [ "$CADDY_EXIT_CODE" -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Caddy started successfully"
        log "Caddy started successfully"
    else
        echo -e "${RED}✗${NC} Failed to start Caddy (exit code: $CADDY_EXIT_CODE)" >&2
        log "ERROR: Failed to start Caddy (exit code: $CADDY_EXIT_CODE)"
        cleanup
        exit 1
    fi
else
    echo -e "${YELLOW}Caddy is already running. Reloading configuration...${NC}"
    log "Caddy is already running, reloading configuration"
    caddy reload --config Caddyfile 2>&1 | tee -a "$LOG_FILE"
    CADDY_EXIT_CODE=${PIPESTATUS[0]}
    if [ "$CADDY_EXIT_CODE" -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Caddy configuration reloaded"
        log "Caddy configuration reloaded successfully"
    else
        echo -e "${YELLOW}⚠${NC} Caddy reload may have failed (exit code: $CADDY_EXIT_CODE)" >&2
        echo -e "${YELLOW}Continuing anyway...${NC}" >&2
        log "WARNING: Caddy reload may have failed (exit code: $CADDY_EXIT_CODE)"
    fi
fi

log "Development environment is ready!"
echo -e "${GREEN}✓ Development environment is ready!${NC}"
echo -e "${GREEN}Access your application at: https://${DOMAIN}${NC}"
echo ""
echo "Services running:"
echo "  - Laravel: http://127.0.0.1:8000"
echo "  - Vite: http://127.0.0.1:5173"
echo "  - Caddy: https://${DOMAIN}"
echo ""
echo -e "${CYAN}Log file: ${LOG_FILE}${NC}"
echo ""
echo "Press Ctrl+C to stop all services"

# Wait for background process
wait "$DEV_PID" 2>/dev/null || true

