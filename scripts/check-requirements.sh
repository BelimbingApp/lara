#!/usr/bin/env bash
# scripts/check-requirements.sh
# Title: Pre-flight System Requirements Check
# Purpose: Validate system requirements before installation
# Usage: ./scripts/check-requirements.sh [local|staging|production|testing]
# Can be run standalone or called by setup.sh
#
# This script:
# - Validates OS version and compatibility
# - Checks disk space and RAM availability
# - Verifies network connectivity
# - Checks required ports availability
# - Validates PHP, Composer, Node.js/Bun, Git availability
# - Provides actionable recommendations

set -euo pipefail

# Get script directory and project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Source shared utilities
# shellcheck source=shared/colors.sh
source "$SCRIPT_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=shared/runtime.sh
source "$SCRIPT_DIR/shared/runtime.sh" 2>/dev/null || true
# shellcheck source=shared/config.sh
source "$SCRIPT_DIR/shared/config.sh" 2>/dev/null || true
# shellcheck source=shared/validation.sh
source "$SCRIPT_DIR/shared/validation.sh" 2>/dev/null || true

# Environment (default to local if not provided)
APP_ENV="${1:-local}"

# Minimum requirements
MIN_DISK_GB=2
MIN_RAM_GB=2
REQUIRED_PORTS=(80 443 5432 6379)

# Check results
declare -a PASSED=()
declare -a WARNINGS=()
declare -a FAILED=()

# Check OS version
check_os() {
    local os_type
    os_type=$(detect_os 2>/dev/null || echo "unknown")

    case "$os_type" in
        linux|wsl2|macos)
            echo -e "${GREEN}✓${NC} OS: $os_type (supported)"
            PASSED+=("OS: $os_type")
            return 0
            ;;
        *)
            echo -e "${RED}✗${NC} OS: $os_type (not officially supported)"
            FAILED+=("OS: $os_type not supported")
            return 1
            ;;
    esac
}

# Check disk space
check_disk_space() {
    local available_gb
    if command_exists df; then
        # Get available space in GB (POSIX compatible)
        available_gb=$(df -BG "$PROJECT_ROOT" 2>/dev/null | awk 'NR==2 {print $4}' | sed 's/G//' || echo "0")

        if [ -z "$available_gb" ] || [ "$available_gb" = "0" ]; then
            # Fallback: try without -BG flag
            available_gb=$(df "$PROJECT_ROOT" 2>/dev/null | awk 'NR==2 {print int($4/1024/1024)}' || echo "0")
        fi

        if [ "$available_gb" -ge "$MIN_DISK_GB" ]; then
            echo -e "${GREEN}✓${NC} Disk Space: ${available_gb}GB available (${MIN_DISK_GB}GB required)"
            PASSED+=("Disk Space: ${available_gb}GB")
            return 0
        else
            echo -e "${RED}✗${NC} Disk Space: ${available_gb}GB available (${MIN_DISK_GB}GB required)"
            FAILED+=("Disk Space: Only ${available_gb}GB available, need ${MIN_DISK_GB}GB")
            return 1
        fi
    else
        echo -e "${YELLOW}⚠${NC} Disk Space: Cannot check (df not available)"
        WARNINGS+=("Disk Space: Cannot verify")
        return 0
    fi
}

# Check RAM
check_ram() {
    local total_ram_gb
    local os_type
    os_type=$(detect_os 2>/dev/null || echo "unknown")

    case "$os_type" in
        linux|wsl2)
            if [ -f /proc/meminfo ]; then
                total_ram_gb=$(awk '/MemTotal/ {print int($2/1024/1024)}' /proc/meminfo 2>/dev/null || echo "0")
            else
                total_ram_gb=0
            fi
            ;;
        macos)
            if command_exists sysctl; then
                total_ram_gb=$(sysctl -n hw.memsize 2>/dev/null | awk '{print int($1/1024/1024/1024)}' || echo "0")
            else
                total_ram_gb=0
            fi
            ;;
        *)
            total_ram_gb=0
            ;;
    esac

    if [ "$total_ram_gb" -ge "$MIN_RAM_GB" ]; then
        echo -e "${GREEN}✓${NC} RAM: ${total_ram_gb}GB available (${MIN_RAM_GB}GB required)"
        PASSED+=("RAM: ${total_ram_gb}GB")
        return 0
    elif [ "$total_ram_gb" -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} RAM: ${total_ram_gb}GB available (${MIN_RAM_GB}GB recommended)"
        WARNINGS+=("RAM: ${total_ram_gb}GB may be insufficient")
        return 0
    else
        echo -e "${YELLOW}⚠${NC} RAM: Cannot check"
        WARNINGS+=("RAM: Cannot verify")
        return 0
    fi
}

# Check network connectivity
check_network() {
    if command_exists curl; then
        if curl -s --max-time 5 https://www.google.com >/dev/null 2>&1; then
            echo -e "${GREEN}✓${NC} Network: Internet connectivity available"
            PASSED+=("Network: Connected")
            return 0
        else
            echo -e "${YELLOW}⚠${NC} Network: Cannot reach internet (may work behind firewall)"
            WARNINGS+=("Network: No internet connectivity")
            return 0
        fi
    else
        echo -e "${YELLOW}⚠${NC} Network: Cannot check (curl not available)"
        WARNINGS+=("Network: Cannot verify")
        return 0
    fi
}

# Check port availability
# Only warns if port being in use is actually a problem
check_port() {
    local port=$1
    local service_name=$2

    if ! command_exists nc && ! command_exists netcat; then
        # Can't check - not critical, skip silently
        return 0
    fi

    if is_port_available "$port"; then
        echo -e "${GREEN}✓${NC} Port $port: Available ($service_name)"
        PASSED+=("Port $port: Available")
        return 0
    else
        # Port is in use - determine if this is a problem or not
        case "$port" in
            5432)
                # PostgreSQL port in use - check if PostgreSQL is actually running
                if command_exists pg_isready 2>/dev/null && pg_isready -h localhost -p 5432 >/dev/null 2>&1; then
                    # PostgreSQL is running - this is GOOD, not a warning
                    echo -e "${GREEN}✓${NC} Port 5432: In use (PostgreSQL is running)"
                    PASSED+=("Port 5432: PostgreSQL running")
                else
                    # Port in use but PostgreSQL not responding - potential conflict
                    echo -e "${YELLOW}⚠${NC} Port 5432: In use (PostgreSQL may not be responding correctly)"
                    WARNINGS+=("Port 5432: In use but PostgreSQL not responding")
                fi
                ;;
            6379)
                # Redis port in use - check if Redis is actually running
                if command_exists redis-cli 2>/dev/null && redis-cli ping >/dev/null 2>&1; then
                    # Redis is running - this is GOOD, not a warning
                    echo -e "${GREEN}✓${NC} Port 6379: In use (Redis is running)"
                    PASSED+=("Port 6379: Redis running")
                else
                    # Port in use but Redis not responding - potential conflict
                    echo -e "${YELLOW}⚠${NC} Port 6379: In use (Redis may not be responding correctly)"
                    WARNINGS+=("Port 6379: In use but Redis not responding")
                fi
                ;;
            80|443)
                # HTTP/HTTPS ports - in use could be good (existing proxy) or conflict
                # Since we auto-handle port conflicts now, just inform (not warn)
                # Check if it's likely a reverse proxy
                local proxy_detected=""
                if systemctl is-active --quiet nginx 2>/dev/null || pgrep nginx >/dev/null 2>&1; then
                    proxy_detected="nginx"
                elif systemctl is-active --quiet apache2 2>/dev/null || pgrep apache2 >/dev/null 2>&1; then
                    proxy_detected="apache"
                elif systemctl is-active --quiet traefik 2>/dev/null || pgrep traefik >/dev/null 2>&1; then
                    proxy_detected="traefik"
                elif pgrep caddy >/dev/null 2>&1; then
                    proxy_detected="caddy"
                fi

                if [ -n "$proxy_detected" ]; then
                    echo -e "${CYAN}ℹ${NC} Port $port: In use ($proxy_detected detected)"
                    echo -e "${CYAN}  ℹ${NC} Conflicts will be handled automatically if using Caddy"
                else
                    echo -e "${CYAN}ℹ${NC} Port $port: In use (setup will handle port conflicts automatically)"
                fi
                # Don't add to warnings - not a problem since we auto-handle it
                ;;
            *)
                # Other ports - assume in use is potentially a problem
                echo -e "${YELLOW}⚠${NC} Port $port: In use ($service_name)"
                WARNINGS+=("Port $port: Already in use")
                ;;
        esac
        return 0
    fi
}

# Check PHP
check_php() {
    local required_php_version
    required_php_version=$(get_required_php_version)

    if command_exists php; then
        local php_version
        php_version=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "unknown")

        if check_php_version_meets_minimum "$php_version"; then
            echo -e "${GREEN}✓${NC} PHP: $php_version (${required_php_version}+ required)"
            PASSED+=("PHP: $php_version")
            return 0
        else
            echo -e "${YELLOW}⚠${NC} PHP: $php_version (${required_php_version}+ required, will upgrade)"
            WARNINGS+=("PHP: Version $php_version too old")
            return 0
        fi
    else
        echo -e "${YELLOW}⚠${NC} PHP: Not found (will install)"
        WARNINGS+=("PHP: Not installed")
        return 0
    fi
}

# Check Composer
check_composer() {
    if command_exists composer; then
        local composer_version
        composer_version=$(composer --version 2>/dev/null | head -1 || echo "unknown")
        echo -e "${GREEN}✓${NC} Composer: $composer_version"
        PASSED+=("Composer: Installed")
        return 0
    else
        echo -e "${YELLOW}⚠${NC} Composer: Not found (will install)"
        WARNINGS+=("Composer: Not installed")
        return 0
    fi
}

# Check JavaScript runtime
check_js_runtime() {
    local has_node=false
    local node_version=""

    if command_exists node && command_exists npm; then
        has_node=true
        node_version=$(node --version 2>/dev/null || echo "unknown")
    fi

    if command_exists bun; then
        local bun_version
        bun_version=$(bun --version 2>/dev/null || echo "unknown")
        echo -e "${GREEN}✓${NC} Bun: $bun_version (preferred)"
        if [ "$has_node" = true ]; then
            echo -e "${YELLOW}  ℹ${NC} Node.js $node_version is also installed but won't be used"
        fi
        PASSED+=("JavaScript Runtime: Bun $bun_version")
        return 0
    elif [ "$has_node" = true ]; then
        local npm_version
        npm_version=$(npm --version 2>/dev/null || echo "unknown")
        echo -e "${GREEN}✓${NC} Node.js: $node_version, npm: $npm_version"
        echo -e "${YELLOW}  ℹ${NC} Bun is preferred - Node.js will be replaced if Bun is installed"
        PASSED+=("JavaScript Runtime: Node.js $node_version")
        return 0
    else
        echo -e "${YELLOW}⚠${NC} JavaScript Runtime: Not found (will install Bun or Node.js)"
        WARNINGS+=("JavaScript Runtime: Not installed")
        return 0
    fi
}

# Check Git
check_git() {
    local latest_git_version
    latest_git_version=$(get_latest_git_version)

    if command_exists git; then
        local git_version
        git_version=$(git --version 2>/dev/null | awk '{print $3}' || echo "unknown")

        # Use version comparison helper from versions.sh
        if [ "$git_version" != "unknown" ]; then
            if check_git_version_meets_latest "$git_version"; then
                echo -e "${GREEN}✓${NC} Git: $git_version (latest: ${latest_git_version})"
                PASSED+=("Git: $git_version")
                return 0
            else
                echo -e "${YELLOW}⚠${NC} Git: $git_version (latest: ${latest_git_version})"
                echo -e "${YELLOW}  ℹ${NC} Git will be upgraded to ${latest_git_version} during setup"
                WARNINGS+=("Git: Version $git_version needs upgrade to ${latest_git_version}")
                return 0
            fi
        else
            echo -e "${GREEN}✓${NC} Git: $git_version"
            PASSED+=("Git: $git_version")
            return 0
        fi
    else
        echo -e "${YELLOW}⚠${NC} Git: Not found (will install latest: ${latest_git_version})"
        WARNINGS+=("Git: Not installed")
        return 0
    fi
}

# Check PostgreSQL
check_postgresql() {
    if command_exists psql; then
        echo -e "${GREEN}✓${NC} PostgreSQL: Client found"
        PASSED+=("PostgreSQL: Client available")
    else
        echo -e "${YELLOW}⚠${NC} PostgreSQL: Client not found (will install)"
        WARNINGS+=("PostgreSQL: Client not installed")
    fi

    # Check if service is running
    if command_exists pg_isready 2>/dev/null; then
        if pg_isready -h localhost -p 5432 >/dev/null 2>&1; then
            echo -e "${GREEN}✓${NC} PostgreSQL: Service running on port 5432"
            PASSED+=("PostgreSQL: Service running")
        else
            echo -e "${YELLOW}⚠${NC} PostgreSQL: Service not running (will start after installation)"
            WARNINGS+=("PostgreSQL: Service not running")
        fi
    fi
}

# Check Redis
check_redis() {
    if command_exists redis-cli; then
        echo -e "${GREEN}✓${NC} Redis: Client found"
        PASSED+=("Redis: Client available")
    else
        echo -e "${YELLOW}⚠${NC} Redis: Client not found (will install)"
        WARNINGS+=("Redis: Client not installed")
    fi

    # Check if service is running
    if command_exists redis-cli; then
        if redis-cli ping >/dev/null 2>&1; then
            echo -e "${GREEN}✓${NC} Redis: Service running"
            PASSED+=("Redis: Service running")
        else
            echo -e "${YELLOW}⚠${NC} Redis: Service not running (will start after installation)"
            WARNINGS+=("Redis: Service not running")
        fi
    fi
}

# Main function
main() {
    print_section_banner "System Requirements Check - Belimbing ($APP_ENV)"
    echo ""

    echo -e "${CYAN}Checking system requirements...${NC}"
    echo ""

    # Run all checks
    check_os
    check_disk_space
    check_ram
    check_network

    echo ""
    echo -e "${CYAN}Checking required ports...${NC}"
    check_port 80 "HTTP"
    check_port 443 "HTTPS"
    check_port 5432 "PostgreSQL"
    check_port 6379 "Redis"

    echo ""
    echo -e "${CYAN}Checking development tools...${NC}"
    check_php
    check_composer
    check_js_runtime
    check_git

    echo ""
    echo -e "${CYAN}Checking database services...${NC}"
    check_postgresql
    check_redis

    echo ""
    print_divider
    echo ""

    # Summary
    echo -e "${CYAN}Summary:${NC}"
    echo -e "  ${GREEN}✓ Passed: ${#PASSED[@]}${NC}"
    echo -e "  ${YELLOW}⚠ Warnings: ${#WARNINGS[@]}${NC}"
    echo -e "  ${RED}✗ Failed: ${#FAILED[@]}${NC}"
    echo ""

    if [ ${#FAILED[@]} -gt 0 ]; then
        echo -e "${RED}Critical issues found:${NC}"
        for issue in "${FAILED[@]}"; do
            echo -e "  ${BULLET} $issue"
        done
        echo ""
        echo -e "${YELLOW}Please resolve these issues before proceeding with installation.${NC}"
        exit 1
    fi

    if [ ${#WARNINGS[@]} -gt 0 ]; then
        echo -e "${YELLOW}Warnings (will be handled automatically):${NC}"
        for warning in "${WARNINGS[@]}"; do
            echo -e "  ${BULLET} $warning"
        done
        echo ""
        echo -e "${CYAN}These will be installed/configured during setup.${NC}"
    fi

    if [ ${#FAILED[@]} -eq 0 ]; then
        echo -e "${GREEN}✓ All critical requirements met!${NC}"
        echo -e "${CYAN}You can proceed with: ${GREEN}./scripts/setup.sh $APP_ENV${NC}"
        exit 0
    fi
}

# Run main function
main "$@"
