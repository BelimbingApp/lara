#!/bin/bash

# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong

# Stop Belimbing Development Environment Services
#
# Usage: ./scripts/stop-app.sh [ENVIRONMENT]
#
# Arguments:
#   ENVIRONMENT   Environment to stop (local|staging|production|testing)
#                 Default: local
#
# Examples:
#   ./scripts/stop-app.sh              # Stop local environment
#   ./scripts/stop-app.sh local        # Stop local environment
#   ./scripts/stop-app.sh staging      # Stop staging environment
#
# This script:
#   - Stops processes running on configured ports for the environment
#   - Kills Laravel server (port 8000 for local, 8001 for staging, etc.)
#   - Kills Vite dev server (port 5173 for local, 5174 for staging, etc.)
#   - Kills concurrently processes
#
# Note: This script uses kill -9, so services are forcefully terminated.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Source shared utilities for consistent output
# shellcheck source=shared/colors.sh
source "$SCRIPT_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=shared/config.sh
source "$SCRIPT_DIR/shared/config.sh" 2>/dev/null || true

# Global variables
ENVIRONMENT=""
BACKEND_PORT=""
FRONTEND_PORT=""

# Get ports from configuration
get_ports() {
    if command -v get_backend_port >/dev/null 2>&1 && command -v get_frontend_port >/dev/null 2>&1; then
        BACKEND_PORT=$(get_backend_port "$ENVIRONMENT" "$PROJECT_ROOT")
        FRONTEND_PORT=$(get_frontend_port "$ENVIRONMENT" "$PROJECT_ROOT")
    else
        # Fallback to defaults
        case "$ENVIRONMENT" in
            local) BACKEND_PORT=8000; FRONTEND_PORT=5173 ;;
            staging) BACKEND_PORT=8001; FRONTEND_PORT=5174 ;;
            production) BACKEND_PORT=8002; FRONTEND_PORT=5175 ;;
            testing) BACKEND_PORT=8003; FRONTEND_PORT=5176 ;;
            *) BACKEND_PORT=8000; FRONTEND_PORT=5173 ;;
        esac
    fi
}

# Stop processes on a specific port
stop_port() {
    local port=$1
    local service_name=$2

    if lsof -ti:"$port" >/dev/null 2>&1; then
        echo -e "${CYAN}Stopping $service_name (port $port)...${NC}"
        lsof -ti:"$port" | xargs kill -9 2>/dev/null || true
    fi
}

# Stop concurrently processes
stop_concurrently() {
    if pgrep -f "concurrently" >/dev/null 2>&1; then
        echo -e "${CYAN}Stopping concurrently processes...${NC}"
        pkill -f "concurrently" || true
    fi
}

# Main orchestration function
main() {
ENVIRONMENT=${1:-local}

    echo -e "${YELLOW}Stopping $ENVIRONMENT environment services...${NC}"

    # Get ports for the environment
    get_ports

    # Stop services by port
    stop_port "$BACKEND_PORT" "Laravel server"
    stop_port "$FRONTEND_PORT" "Vite dev server"

    # Stop concurrently processes
    stop_concurrently

    echo -e "${GREEN}✓ Services stopped.${NC}"
}

# Run main function
main "$@"
