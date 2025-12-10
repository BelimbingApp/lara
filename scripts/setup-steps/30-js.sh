#!/usr/bin/env bash
# scripts/setup-steps/30-js.sh
# Title: JavaScript Runtime (Bun/Node.js)
# Purpose: Install and configure Bun or Node.js for Belimbing
# Usage: ./scripts/setup-steps/30-js.sh [local|staging|production|testing]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Checks for Bun installation (preferred)
# - Installs Bun if selected
# - Falls back to Node.js/npm if Bun not available
# - Verifies JavaScript runtime is available

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

# Install Bun
install_bun() {
    local os_type
    os_type=$(detect_os)

    echo -e "${CYAN}Installing Bun...${NC}"
    echo ""

    case "$os_type" in
        macos)
            if command_exists brew; then
                echo -e "${CYAN}Installing Bun via Homebrew...${NC}"
                brew install oven-sh/bun/bun || {
                    echo -e "${RED}✗${NC} Failed to install Bun via Homebrew" >&2
                    return 1
                }
            else
                # Use official installer
                echo -e "${CYAN}Installing Bun via official installer...${NC}"
                curl -fsSL https://bun.sh/install | bash || {
                    echo -e "${RED}✗${NC} Failed to install Bun" >&2
                    return 1
                }
                echo -e "${YELLOW}Note:${NC} You may need to add Bun to your PATH"
                echo -e "  Add to ~/.bashrc or ~/.zshrc: ${CYAN}export PATH=\"\$HOME/.bun/bin:\$PATH\"${NC}"
            fi
            ;;
        linux|wsl2)
            # Use official installer
            echo -e "${CYAN}Installing Bun via official installer...${NC}"
            curl -fsSL https://bun.sh/install | bash || {
                echo -e "${RED}✗${NC} Failed to install Bun" >&2
                return 1
            }
            echo -e "${YELLOW}Note:${NC} You may need to add Bun to your PATH"
            echo -e "  Add to ~/.bashrc or ~/.zshrc: ${CYAN}export PATH=\"\$HOME/.bun/bin:\$PATH\"${NC}"
            ;;
        *)
            echo -e "${RED}✗${NC} OS not supported for auto-install" >&2
            echo -e "  Please install Bun manually: ${CYAN}https://bun.sh${NC}" >&2
            return 1
            ;;
    esac

    # Verify installation
    if command_exists bun; then
        local bun_version
        bun_version=$(bun --version 2>/dev/null || echo "unknown")
        echo ""
        echo -e "${GREEN}✓${NC} Bun installed successfully: $bun_version"
        return 0
    fi

    echo ""
    echo -e "${YELLOW}⚠${NC} Bun installed but not in PATH" >&2
    echo -e "  You may need to restart your shell or add ~/.bun/bin to PATH" >&2
    return 1
}

# Install Node.js and npm
install_nodejs() {
    local os_type
    os_type=$(detect_os)

    echo -e "${CYAN}Installing Node.js and npm...${NC}"
    echo ""

    case "$os_type" in
        macos)
            if command_exists brew; then
                echo -e "${CYAN}Installing Node.js via Homebrew...${NC}"
                brew install node || {
                    echo -e "${RED}✗${NC} Failed to install Node.js" >&2
                    return 1
                }
            else
                echo -e "${RED}✗${NC} Homebrew required for Node.js installation on macOS" >&2
                echo -e "  Install Homebrew: ${CYAN}https://brew.sh${NC}" >&2
                echo -e "  Or install Node.js manually: ${CYAN}https://nodejs.org${NC}" >&2
                return 1
            fi
            ;;
        linux|wsl2)
            if command_exists apt-get; then
                echo -e "${CYAN}Installing Node.js via apt...${NC}"
                curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash - || {
                    echo -e "${YELLOW}Using default repository...${NC}"
                }
                sudo apt-get update -qq
                sudo apt-get install -y -qq nodejs || {
                    echo -e "${RED}✗${NC} Failed to install Node.js" >&2
                    return 1
                }
            elif command_exists yum; then
                echo -e "${CYAN}Installing Node.js via yum...${NC}"
                curl -fsSL https://rpm.nodesource.com/setup_lts.x | sudo bash - || {
                    echo -e "${YELLOW}Using default repository...${NC}"
                }
                sudo yum install -y nodejs npm || {
                    echo -e "${RED}✗${NC} Failed to install Node.js" >&2
                    return 1
                }
            elif command_exists dnf; then
                echo -e "${CYAN}Installing Node.js via dnf...${NC}"
                curl -fsSL https://rpm.nodesource.com/setup_lts.x | sudo bash - || {
                    echo -e "${YELLOW}Using default repository...${NC}"
                }
                sudo dnf install -y nodejs npm || {
                    echo -e "${RED}✗${NC} Failed to install Node.js" >&2
                    return 1
                }
            else
                echo -e "${RED}✗${NC} Package manager not supported" >&2
                echo -e "  Please install Node.js manually: ${CYAN}https://nodejs.org${NC}" >&2
                return 1
            fi
            ;;
        *)
            echo -e "${RED}✗${NC} OS not supported for auto-install" >&2
            echo -e "  Please install Node.js manually: ${CYAN}https://nodejs.org${NC}" >&2
            return 1
            ;;
    esac

    # Verify installation
    if command_exists node && command_exists npm; then
        local node_version npm_version
        node_version=$(node --version 2>/dev/null || echo "unknown")
        npm_version=$(npm --version 2>/dev/null || echo "unknown")
        echo ""
        echo -e "${GREEN}✓${NC} Node.js installed successfully: $node_version"
        echo -e "${GREEN}✓${NC} npm installed successfully: $npm_version"
        return 0
    fi

    echo ""
    echo -e "${RED}✗${NC} Node.js/npm installation verification failed" >&2
    return 1
}

# Main setup function
main() {
    print_section_banner "JavaScript Runtime Setup - Belimbing ($APP_ENV)"

    # Load existing configuration
    load_setup_state

    # Check for existing JavaScript runtime
    local has_bun=false
    local has_node=false

    if command_exists bun; then
        has_bun=true
    fi

    if command_exists node && command_exists npm; then
        has_node=true
    fi

    # If both are available, prefer Bun
    if [ "$has_bun" = true ]; then
        local bun_version
        bun_version=$(bun --version 2>/dev/null || echo "unknown")
        echo -e "${GREEN}✓${NC} Bun already installed: $bun_version"
        if [ "$has_node" = true ]; then
            local node_version
            node_version=$(node --version 2>/dev/null || echo "unknown")
            echo -e "${YELLOW}ℹ${NC} Node.js $node_version is also installed but will not be used"
            echo -e "${CYAN}ℹ${NC} Bun will be used instead (replaces Node.js and npm)"
        else
            echo -e "${CYAN}ℹ${NC} Bun will be used (replaces Node.js and npm)"
        fi
        echo ""
        save_to_setup_state "JS_RUNTIME" "bun"
        save_to_setup_state "BUN_VERSION" "$bun_version"
        print_divider
        echo ""
        echo -e "${GREEN}✓ JavaScript runtime setup complete!${NC}"
        echo ""
        exit 0
    fi

    if [ "$has_node" = true ]; then
        local node_version npm_version
        node_version=$(node --version 2>/dev/null || echo "unknown")
        npm_version=$(npm --version 2>/dev/null || echo "unknown")
        echo -e "${GREEN}✓${NC} Node.js already installed: $node_version"
        echo -e "${GREEN}✓${NC} npm already installed: $npm_version"
        echo ""
        echo -e "${YELLOW}ℹ${NC} Note: Bun is the preferred runtime for this project"
        echo -e "${CYAN}ℹ${NC} Node.js will be used, but consider installing Bun for better performance"
        echo -e "  ${CYAN}Install Bun:${NC} curl -fsSL https://bun.sh/install | bash"
        echo ""
        save_to_setup_state "JS_RUNTIME" "node"
        save_to_setup_state "NODE_VERSION" "$node_version"
        save_to_setup_state "NPM_VERSION" "$npm_version"
        print_divider
        echo ""
        echo -e "${GREEN}✓ JavaScript runtime setup complete!${NC}"
        echo ""
        exit 0
    fi

    # No JavaScript runtime found - offer to install
    print_subsection_header "JavaScript Runtime Installation"
    echo -e "${YELLOW}ℹ${NC} No JavaScript runtime found"
    echo ""
    local latest_bun_version
    latest_bun_version=$(get_latest_bun_version_with_prefix)
    echo -e "${CYAN}Options:${NC}"
    echo ""
    echo -e "  ${GREEN}1. Bun (Recommended - Latest: ${latest_bun_version})${NC}"
    echo -e "     • Faster than Node.js"
    echo -e "     • Built-in package manager (replaces npm)"
    echo -e "     • Modern JavaScript runtime"
    echo -e "     • ${YELLOW}Note:${NC} If you have Node.js installed, Bun will replace it for this project"
    echo ""
    echo -e "  ${YELLOW}2. Node.js + npm${NC}"
    echo -e "     • Traditional JavaScript runtime"
    echo -e "     • Widely supported"
    echo -e "     • More established ecosystem"
    echo -e "     • ${YELLOW}Note:${NC} Bun is preferred and can be installed later"
    echo ""

    if [ -t 0 ]; then
        local choice
        choice=$(ask_input "Choose JavaScript runtime (1 for Bun, 2 for Node.js)" "1")

        case "$choice" in
            1|bun|Bun)
                if [ "$has_node" = true ]; then
                    local node_version
                    node_version=$(node --version 2>/dev/null || echo "unknown")
                    echo -e "${YELLOW}⚠${NC} Node.js $node_version is currently installed"
                    echo -e "${CYAN}ℹ${NC} Bun will be used instead of Node.js for this project"
                    echo -e "${CYAN}ℹ${NC} Node.js will remain installed but won't be used by Belimbing"
                    echo ""
                fi
                if install_bun; then
                    local bun_version
                    bun_version=$(bun --version 2>/dev/null || echo "unknown")
                    save_to_setup_state "JS_RUNTIME" "bun"
                    save_to_setup_state "BUN_VERSION" "$bun_version"
                else
                    echo -e "${RED}✗${NC} Bun installation failed"
                    echo ""
                    echo -e "${YELLOW}Please install Bun manually:${NC}"
                    echo -e "  ${CYAN}https://bun.sh${NC}"
                    exit 1
                fi
                ;;
            2|node|Node.js|npm)
                if install_nodejs; then
                    local node_version npm_version
                    node_version=$(node --version 2>/dev/null || echo "unknown")
                    npm_version=$(npm --version 2>/dev/null || echo "unknown")
                    save_to_setup_state "JS_RUNTIME" "node"
                    save_to_setup_state "NODE_VERSION" "$node_version"
                    save_to_setup_state "NPM_VERSION" "$npm_version"
                else
                    echo -e "${RED}✗${NC} Node.js installation failed"
                    echo ""
                    echo -e "${YELLOW}Please install Node.js manually:${NC}"
                    echo -e "  ${CYAN}https://nodejs.org${NC}"
                    exit 1
                fi
                ;;
            *)
                echo -e "${RED}✗${NC} Invalid choice" >&2
                exit 1
                ;;
        esac
    else
        # Non-interactive mode - default to Bun
        if [ "$has_node" = true ]; then
            local node_version
            node_version=$(node --version 2>/dev/null || echo "unknown")
            echo -e "${YELLOW}ℹ${NC} Node.js $node_version detected"
            echo -e "${CYAN}ℹ${NC} Installing Bun (will replace Node.js for this project)...${NC}"
        else
            echo -e "${CYAN}Non-interactive mode: Installing Bun (default)...${NC}"
        fi
        if install_bun; then
            local bun_version
            bun_version=$(bun --version 2>/dev/null || echo "unknown")
            save_to_setup_state "JS_RUNTIME" "bun"
            save_to_setup_state "BUN_VERSION" "$bun_version"
        else
            echo -e "${YELLOW}Falling back to Node.js...${NC}"
            if install_nodejs; then
                local node_version npm_version
                node_version=$(node --version 2>/dev/null || echo "unknown")
                npm_version=$(npm --version 2>/dev/null || echo "unknown")
                save_to_setup_state "JS_RUNTIME" "node"
                save_to_setup_state "NODE_VERSION" "$node_version"
                save_to_setup_state "NPM_VERSION" "$npm_version"
            else
                exit 1
            fi
        fi
    fi

    print_divider
    echo ""
    echo -e "${GREEN}✓ JavaScript runtime setup complete!${NC}"
    echo ""
    if command_exists bun; then
        echo -e "${CYAN}Installed:${NC}"
        echo -e "  • Bun: $(bun --version 2>/dev/null || echo "unknown")"
    elif command_exists node && command_exists npm; then
        echo -e "${CYAN}Installed:${NC}"
        echo -e "  • Node.js: $(node --version 2>/dev/null || echo "unknown")"
        echo -e "  • npm: $(npm --version 2>/dev/null || echo "unknown")"
    fi
    echo ""
}

# Run main function
main "$@"
