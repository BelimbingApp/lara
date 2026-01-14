#!/bin/bash

# SPDX-License-Identifier: AGPL-3.0-only
# (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

# Stop Belimbing Development Environment Services
# Usage: ./scripts/stop-app.sh [ENVIRONMENT]
#
# Why this script exists (when Ctrl+C in start-app.sh should be enough):
#
# 1. Recovery from crashes: If start-app.sh was killed (SIGKILL, terminal crash,
#    system crash), cleanup() won't run, leaving orphaned processes. This script
#    allows manual cleanup of stuck services.
#
# 2. Stopping from different terminal: If start-app.sh is running in another
#    terminal, Ctrl+C won't work. This script can stop services from any terminal.
#
# 3. Explicit stopping: Better UX than relying on start-app.sh's auto-cleanup
#    when restarting. Users can explicitly stop without starting.
#
# 4. Debugging/cleanup: Useful for force-cleaning stuck processes during
#    development and troubleshooting.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/shared/runtime.sh" 2>/dev/null || true

echo -e "${YELLOW}Stopping ${1:-local} environment services...${NC}"
stop_dev_services "${1:-local}"
echo -e "\n${GREEN}✓ Services stopped.${NC}"

