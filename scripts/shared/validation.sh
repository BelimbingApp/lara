#!/bin/bash
# Shared validation utilities for Belimbing scripts
#
# REQUIREMENTS:
# - Callers using validate_required_tools() MUST source interactive.sh first
#   (provides ask_yes_no and ask_input functions)

# Get script directory
VALIDATION_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Source config.sh for defaults (if not already loaded)
if [ -z "$DEFAULT_DB_USER" ]; then
    source "$VALIDATION_SCRIPT_DIR/config.sh" 2>/dev/null || true
fi

# Source colors if not already loaded
if [ -z "$RED" ]; then
    source "$SCRIPT_DIR/colors.sh"
fi

# Check if a command exists
command_exists() {
    command -v "$1" &> /dev/null
}

# Check if a port is available
is_port_available() {
    local port=$1
    ! nc -z 127.0.0.1 "$port" 2>/dev/null
}

# Check if a port is valid (1-65535)
is_valid_port() {
    local port=$1
    [[ "$port" =~ ^[0-9]+$ ]] && [ "$port" -ge 1 ] && [ "$port" -le 65535 ]
}

# Check if a domain is valid format
is_valid_domain() {
    local domain=$1
    [[ "$domain" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$ ]]
}

# Check if running as root
is_root() {
    [ "$(id -u)" -eq 0 ]
}

# Check if sudo is available and working
can_sudo() {
    sudo -n true 2>/dev/null
}

# Install mkcert (requires cargo)
install_mkcert() {
    local os_type=$1

    echo -e "${YELLOW}${INFO_MARK} Installing mkcert...${NC}"

    case "$os_type" in
        macos)
            if command_exists brew; then
                brew install mkcert
            else
                echo -e "${RED}${CROSS_MARK} Homebrew required${NC}"
                echo -e "  Install mkcert manually or install Homebrew from: https://brew.sh"
                return 1
            fi
            ;;
        linux|wsl2)
            if command_exists apt-get; then
                sudo apt-get update -qq
                sudo apt-get install -y -qq mkcert || {
                    echo -e "${RED}${CROSS_MARK} Failed to install mkcert${NC}"
                    return 1
                }
            elif command_exists yum; then
                sudo yum install -y mkcert || {
                    echo -e "${RED}${CROSS_MARK} Failed to install mkcert${NC}"
                    return 1
                }
            elif command_exists dnf; then
                sudo dnf install -y mkcert || {
                    echo -e "${RED}${CROSS_MARK} Failed to install mkcert${NC}"
                    return 1
                }
            else
                echo -e "${RED}${CROSS_MARK} Package manager not supported${NC}"
                echo -e "  Please install mkcert manually from: https://github.com/FiloSottile/mkcert"
                return 1
            fi
            ;;
        *)
            echo -e "${RED}${CROSS_MARK} OS not supported for auto-install${NC}"
            echo -e "  Please install mkcert manually from: https://github.com/FiloSottile/mkcert"
            return 1
            ;;
    esac

    # Verify installation
    if command_exists mkcert; then
        echo -e "${GREEN}${CHECK_MARK}${NC} mkcert installed"
        return 0
    else
        echo -e "${YELLOW}${WARNING_MARK} mkcert installed but not in PATH${NC}"
        return 1
    fi
}

# Install Chromium
install_chromium() {
    local os_type=$1

    echo -e "${YELLOW}${INFO_MARK} Installing Chromium...${NC}"

    case "$os_type" in
        macos)
            if command_exists brew; then
                brew install --cask chromium
            else
                echo -e "${RED}${CROSS_MARK} Homebrew required. Install from https://brew.sh${NC}"
                return 1
            fi
            ;;
        linux|wsl2)
            if command_exists apt-get; then
                sudo apt-get update -qq
                sudo apt-get install -y -qq chromium-browser || {
                    echo -e "${RED}${CROSS_MARK} Failed to install Chromium${NC}"
                    return 1
                }
            elif command_exists yum; then
                sudo yum install -y chromium || {
                    echo -e "${RED}${CROSS_MARK} Failed to install Chromium${NC}"
                    return 1
                }
            elif command_exists dnf; then
                sudo dnf install -y chromium || {
                    echo -e "${RED}${CROSS_MARK} Failed to install Chromium${NC}"
                    return 1
                }
            else
                echo -e "${RED}${CROSS_MARK} Package manager not supported${NC}"
                echo -e "  Please install Chromium manually or another browser of your choice."
                return 1
            fi
            ;;
        *)
            echo -e "${RED}${CROSS_MARK} OS not supported for auto-install${NC}"
            echo -e "  Please install Chromium manually or another browser of your choice."
            return 1
            ;;
    esac

    local os_type
    os_type=$(detect_os)
    if [ "$os_type" = "macos" ]; then
        if has_macos_app "Google Chrome" || has_macos_app "Chromium" || has_macos_app "Firefox"; then
            echo -e "${GREEN}${CHECK_MARK}${NC} Browser installed"
            return 0
        else
            echo -e "${YELLOW}${WARNING_MARK} Browser installed but not detected. Please ensure a browser is available in /Applications or via Homebrew Cask.${NC}"
            return 1
        fi
    else
        if command_exists chromium || command_exists google-chrome || command_exists firefox; then
            echo -e "${GREEN}${CHECK_MARK}${NC} Chromium installed"
            return 0
        else
            echo -e "${YELLOW}${WARNING_MARK} Chromium installed but not in PATH, or another browser is already present.${NC}"
            return 0 # Still consider it a success if another browser is present
        fi
    fi
}

# Validate required tools are installed (with optional auto-install)
validate_required_tools() {
    local auto_install=${1:-false}
    local missing=()
    local install_needed=()
    local tools_checked=()

    # Define tools to check: name, description, install_function, dependencies
    # Check Git first (no dependencies)
    if ! command_exists git; then
        missing+=("git")
        install_needed+=("git")
    else
        tools_checked+=("git")
    fi

    # Check cargo (no dependencies)
    if ! command_exists cargo; then
        missing+=("cargo (Rust toolchain)")
        install_needed+=("cargo")
    else
        tools_checked+=("cargo")
    fi

    # Check trunk (requires cargo)
    if ! command_exists trunk; then
        missing+=("trunk (cargo install trunk)")
        if command_exists cargo; then
            install_needed+=("trunk")
        else
            # Can't install trunk without cargo
            install_needed+=("trunk_needs_cargo")
        fi
    else
        tools_checked+=("trunk")
    fi

    # Check mkcert (no dependencies)
    if ! command_exists mkcert; then
        missing+=("mkcert (local HTTPS certificates)")
        install_needed+=("mkcert")
    else
        tools_checked+=("mkcert")
    fi

    # Check for a browser (only suggest install if none found)
    local has_browser=false
    local os_type
    os_type=$(detect_os)

    if [ "$os_type" = "macos" ]; then
        if has_macos_app "Google Chrome"; then
            tools_checked+=("google-chrome")
            has_browser=true
        elif has_macos_app "Chromium"; then
            tools_checked+=("chromium")
            has_browser=true
        elif has_macos_app "Firefox"; then
            tools_checked+=("firefox")
            has_browser=true
        fi
    else
        if command_exists google-chrome; then
            tools_checked+=("google-chrome")
            has_browser=true
        elif command_exists chromium-browser; then
            tools_checked+=("chromium-browser")
            has_browser=true
        elif command_exists firefox; then
            tools_checked+=("firefox")
            has_browser=true
        fi
    fi

    if ! $has_browser; then
        missing+=("A web browser (for the browser wizard)")
        # Only offer to install Chromium if no other browser is found
        install_needed+=("chromium")
    fi

    # Show what's already installed
    for tool in "${tools_checked[@]}"; do
        case "$tool" in
            git) echo -e "${GREEN}${CHECK_MARK}${NC} Git installed" ;;
            cargo) echo -e "${GREEN}${CHECK_MARK}${NC} Rust/Cargo installed" ;;
            trunk) echo -e "${GREEN}${CHECK_MARK}${NC} Trunk installed" ;;
            mkcert) echo -e "${GREEN}${CHECK_MARK}${NC} mkcert installed" ;;
            google-chrome) echo -e "${GREEN}${CHECK_MARK}${NC} Google Chrome installed" ;;
            chromium-browser) echo -e "${GREEN}${CHECK_MARK}${NC} Chromium installed" ;;
            firefox) echo -e "${GREEN}${CHECK_MARK}${NC} Firefox installed" ;;
        esac
    done

    if [ ${#missing[@]} -eq 0 ]; then
        return 0  # All tools present
    fi

    # Report missing tools
    echo ""
    echo -e "${RED}${CROSS_MARK} Missing required tools:${NC}"
    for tool_desc in "${missing[@]}"; do
        echo -e "  ${BULLET} $tool_desc"
    done

    # Offer to install if auto_install is true or interactive mode
    if [ "$auto_install" = "true" ] || [ -t 0 ]; then
        echo ""

        if ! ask_yes_no "Install missing tools?" "n"; then
            return 1
        fi

        # Convert array to string for pattern matching (shellcheck SC2199)
        local install_needed_str=" ${install_needed[*]} "

        # Install git first (no dependencies)
        if [[ "$install_needed_str" =~ " git " ]]; then
            echo -e "${YELLOW}${INFO_MARK} Git installation required${NC}"
            echo -e "  Please run: ${CYAN}./scripts/setup-steps/01-git.sh${NC}"
            echo -e "  Or install manually from: ${CYAN}https://git-scm.com${NC}"
            return 1
        fi

        # Install cargo (no dependencies, but needed for trunk)
        if [[ "$install_needed_str" =~ " cargo " ]]; then
            echo -e "${YELLOW}${INFO_MARK} Rust/Cargo installation required${NC}"
            echo -e "  Please run: ${CYAN}./scripts/setup-steps/03-rust.sh${NC}"
            echo -e "  Or install manually from: ${CYAN}https://rustup.rs${NC}"
            return 1
        fi

        # Install trunk (requires cargo and Rust)
        if [[ "$install_needed_str" =~ " trunk " ]] || [[ "$install_needed_str" =~ " trunk_needs_cargo " ]]; then
            echo -e "${YELLOW}${INFO_MARK} Trunk installation required${NC}"
            echo -e "  Please run: ${CYAN}./scripts/setup-steps/03-rust.sh${NC}"
            echo -e "  Or install manually: ${CYAN}cargo install trunk${NC}"
            return 1
        fi

        # Install mkcert
        if [[ "$install_needed_str" =~ " mkcert " ]]; then
            if ! install_mkcert "$os_type"; then
                return 1
            fi
        fi

        # Install Chromium if needed
        if [[ "$install_needed_str" =~ " chromium " ]]; then
            echo ""
            if ask_yes_no "A web browser is recommended for the setup wizard. Install Chromium?" "y"; then
                if ! install_chromium "$os_type"; then
                    return 1
                fi
            else
                echo -e "${YELLOW}Skipping browser installation. Please ensure you have a browser available.${NC}"
            fi
        fi

        # Verify all tools are now available
        if command_exists git && command_exists cargo && command_exists trunk && command_exists mkcert; then
            # Check browser separately for macOS
            if [ "$os_type" = "macos" ]; then
                if ! ($has_browser || has_macos_app "Google Chrome" || has_macos_app "Chromium" || has_macos_app "Firefox"); then
                    return 1
                fi
            else
                if ! ($has_browser || command_exists google-chrome || command_exists chromium-browser || command_exists firefox); then
                    return 1
                fi
            fi
            echo -e "\n${GREEN}${CHECK_MARK} All tools installed successfully!${NC}"
            return 0
        else
            echo -e "\n${YELLOW}${WARNING_MARK} Some tools may need a shell restart${NC}"
            return 1
        fi
    else
        # Non-interactive mode, just report
        return 1
    fi
}

# Check if .env file has required variables
validate_env_file() {
    local env_file=${1:-.env}

    if [ ! -f "$env_file" ]; then
        echo -e "${RED}${CROSS_MARK} File not found: $env_file${NC}"
        return 1
    fi

    local required_vars=(
        "APP_ENV"
        "DATABASE_URL"
        "JWT_SECRET"
        "FRONTEND_DOMAIN"
        "BACKEND_DOMAIN"
        "BACKEND_PORT"
        "FRONTEND_PORT"
        "HTTPS_PORT"
    )

    local missing=()
    for var in "${required_vars[@]}"; do
        # Check if variable is set and not commented out
        # Look for uncommented line (starts with optional whitespace, then var=, no # before it)
        local uncommented_line
        uncommented_line=$(grep -E "^[[:space:]]*$var=" "$env_file" | grep -vE "^[[:space:]]*#" | head -1)

        if [ -n "$uncommented_line" ]; then
            # Variable exists and is not commented - check if it has a value
            local value
            value=$(echo "$uncommented_line" | cut -d'=' -f2- | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
            if [ -z "$value" ]; then
                missing+=("$var (empty)")
            fi
        else
            # Variable is missing or commented out
            if grep -qE "^[[:space:]]*#.*$var=" "$env_file"; then
                missing+=("$var (commented out)")
            else
                missing+=("$var")
            fi
        fi
    done

    if [ ${#missing[@]} -gt 0 ]; then
        echo -e "${RED}${CROSS_MARK} Missing required variables in $env_file:${NC}"
        for var in "${missing[@]}"; do
            echo -e "  ${BULLET} $var"
        done
        return 1
    fi

    return 0
}

# Detect OS type
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        grep -qi microsoft /proc/version 2>/dev/null && echo "wsl2" || echo "linux"
    else
        echo "unknown"
    fi
}

# Detect existing reverse proxy
detect_proxy() {
    if systemctl is-active --quiet nginx 2>/dev/null || pgrep nginx >/dev/null 2>&1; then
        echo "nginx"
    elif systemctl is-active --quiet apache2 2>/dev/null || pgrep apache2 >/dev/null 2>&1; then
        echo "apache"
    elif systemctl is-active --quiet traefik 2>/dev/null || pgrep traefik >/dev/null 2>&1; then
        echo "traefik"
    elif pgrep caddy >/dev/null 2>&1; then
        echo "caddy"
    else
        echo "none"
    fi
}

# Validate manual proxy configuration
validate_manual_proxy() {
    local proxy_type=$1
    local frontend_domain=$2
    local backend_domain=$3
    local https_port=$4

    # Check if proxy is running
    local detected_proxy
    detected_proxy=$(detect_proxy)
    if [ "$detected_proxy" = "none" ]; then
        echo -e "${YELLOW}${WARNING_MARK} No reverse proxy detected as running${NC}"
        echo -e "  Expected: $proxy_type"
        return 1
    fi

    # Check if detected proxy matches expected type
    if [ "$proxy_type" != "manual" ] && [ "$detected_proxy" != "$proxy_type" ]; then
        echo -e "${YELLOW}${WARNING_MARK} Proxy type mismatch${NC}"
        echo -e "  Expected: $proxy_type"
        echo -e "  Detected: $detected_proxy"
        return 1
    fi

    # Check if domains are in /etc/hosts
    if ! domain_in_hosts "$frontend_domain"; then
        echo -e "${YELLOW}${WARNING_MARK} Frontend domain not in /etc/hosts: $frontend_domain${NC}"
        return 1
    fi

    if ! domain_in_hosts "$backend_domain"; then
        echo -e "${YELLOW}${WARNING_MARK} Backend domain not in /etc/hosts: $backend_domain${NC}"
        return 1
    fi

    # Check if HTTPS port is accessible (basic check)
    if [ "$https_port" -lt 1024 ] && ! is_root; then
        # Can't check privileged ports without root, but warn
        echo -e "${CYAN}${INFO_MARK} Using privileged port $https_port${NC}"
        echo -e "  Ensure $proxy_type is configured to listen on this port"
    fi

    return 0
}

# Check if domain is in /etc/hosts
domain_in_hosts() {
    local domain=$1
    grep -q "^127.0.0.1.*\s${domain}\(\s\|$\)" /etc/hosts 2>/dev/null
}

# Check if running in a Git repository
is_git_repo() {
    git rev-parse --git-dir &>/dev/null
}

# Get Git repository root
get_git_root() {
    git rev-parse --show-toplevel 2>/dev/null
}

# Validate APP_ENV value
is_valid_app_env() {
    local env=$1
    [[ "$env" == "dev" ]] || [[ "$env" == "stage" ]] || [[ "$env" == "prod" ]]
}

# Check if a macOS application exists
has_macos_app() {
    local app_name=$1
    local check_path="/Applications/$app_name.app"
    local check_user_path="$HOME/Applications/$app_name.app"

    if [ -d "$check_path" ] || [ -d "$check_user_path" ]; then
        return 0
    fi
    # Check if installed via homebrew cask
    if command_exists brew && brew list --cask | grep -qi "^$(echo "$app_name" | tr '[:upper:]' '[:lower:]')$" &> /dev/null; then
        return 0
    fi
    return 1
}

# Test PostgreSQL connection from DATABASE_URL
# This MUST test with the actual credentials that will be used by the application
test_database_connection() {
    local database_url=$1

    if [ -z "$database_url" ]; then
        return 1
    fi

    # Parse DATABASE_URL: postgresql://user:password@host:port/database
    # NOTE: Password may contain special characters including @ and :
    # Strategy: Work from right to left to avoid ambiguity
    local user password host port database

    # Remove postgresql:// prefix
    local url_without_protocol="${database_url#postgresql://}"

    # Split on the LAST @ to separate credentials from host info
    # Format: user:password @ host:port/database
    if [[ "$url_without_protocol" =~ ^(.+)@([^@]+)$ ]]; then
        local credentials="${BASH_REMATCH[1]}"
        local host_info="${BASH_REMATCH[2]}"

        # Parse credentials: split on FIRST : only
        # user:password (password may contain :)
        user="${credentials%%:*}"
        password="${credentials#*:}"

        # Parse host info: host:port/database
        if [[ "$host_info" =~ ^([^:]+):([^/]+)/(.+)$ ]]; then
            host="${BASH_REMATCH[1]}"
            port="${BASH_REMATCH[2]}"
            database="${BASH_REMATCH[3]}"
        else
            return 1
        fi
    else
        return 1
    fi

    # Test connection using the EXACT credentials from DATABASE_URL
    # This is what the application will use, so it must work
    export PGPASSWORD="$password"

    # Method 1: Standard password authentication with -h flag (this is what the app uses)
    if psql -h "$host" -p "$port" -U "$user" -d "$database" -c "SELECT 1;" >/dev/null 2>&1; then
        return 0
    fi

    # Method 2: Explicit connection string format (some PostgreSQL configurations require this)
    if PGPASSWORD="$password" psql "postgresql://$user:$password@$host:$port/$database" -c "SELECT 1;" >/dev/null 2>&1; then
        return 0
    fi

    return 1
}
