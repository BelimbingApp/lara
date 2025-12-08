#!/bin/bash

# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong

set -euo pipefail

ENVIRONMENT=${1:-local}

echo "Stopping $ENVIRONMENT environment services..."

# Kill processes by port
case $ENVIRONMENT in
    local)
        lsof -ti:8000 | xargs kill -9 2>/dev/null || true
        lsof -ti:5173 | xargs kill -9 2>/dev/null || true
        ;;
    staging)
        lsof -ti:8001 | xargs kill -9 2>/dev/null || true
        lsof -ti:5174 | xargs kill -9 2>/dev/null || true
        ;;
esac

# Kill concurrently processes
pkill -f "concurrently" || true

echo "Services stopped."
