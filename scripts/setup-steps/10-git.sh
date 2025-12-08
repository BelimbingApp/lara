#!/usr/bin/env bash
# scripts/setup-steps/01-git.sh
# Title: Git Version Control
# Purpose: Install and configure Git for Belimbing
# Usage: ./scripts/setup-steps/01-git.sh [dev|stage|prod]
# Can be run standalone or called by main setup.sh
#
# This script:
# - Checks for Git installation
# - Installs Git if needed (via Xcode CLI Tools on macOS, package manager on Linux)
# - Verifies Git is available

set -euo pipefail

# Get script directory and project root
SETUP_STEPS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"  # Points to scripts/setup-steps/
SCRIPTS_DIR="$(cd "$SETUP_STEPS_DIR/.." && pwd)"  # Points to scripts/
PROJECT_ROOT="$(cd "$SCRIPTS_DIR/.." && pwd)"  # Points to project root

# Source shared utilities (order matters: config.sh before validation.sh)
# shellcheck source=../shared/colors.sh
source "$SCRIPTS_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=../shared/runtime.sh
source "$SCRIPTS_DIR/shared/runtime.sh" 2>/dev/null || true
# shellcheck source=../shared/config.sh
source "$SCRIPTS_DIR/shared/config.sh"
# shellcheck source=../shared/validation.sh
source "$SCRIPTS_DIR/shared/validation.sh"
# shellcheck source=../shared/interactive.sh
source "$SCRIPTS_DIR/shared/interactive.sh"

# Environment (default to dev if not provided)
APP_ENV="${1:-dev}"

# Install Git if needed
install_git() {
    # Check if Git is already installed
    if command_exists git; then
        local git_version
        git_version=$(git --version | awk '{print $3}')
        echo -e "${GREEN}✓${NC} Git already installed: $git_version"
        return 0
    fi

    local os_type
    os_type=$(detect_os)

    echo -e "${CYAN}Installing Git...${NC}"
    echo ""

    case "$os_type" in
        macos)
            # Xcode Command Line Tools installation (includes Git)
            # Note: This will show a GUI prompt on macOS
            echo -e "${CYAN}Installing Xcode Command Line Tools (includes Git)...${NC}"
            xcode-select --install

            # Wait for user to complete the installation
            echo -e "${YELLOW}Please complete the Xcode Command Line Tools installation in the dialog.${NC}"
            echo -e "${YELLOW}Press Enter when installation is complete...${NC}"
            read -r
            ;;
        linux|wsl2)
            if command_exists apt-get; then
                echo -e "${CYAN}Updating package list...${NC}"
                sudo apt-get update -qq
                echo -e "${CYAN}Installing Git via apt...${NC}"
                sudo apt-get install -y git || {
                    echo -e "${RED}✗ Failed to install Git${NC}"
                    return 1
                }
            elif command_exists yum; then
                echo -e "${CYAN}Installing Git via yum...${NC}"
                sudo yum install -y git || {
                    echo -e "${RED}✗ Failed to install Git${NC}"
                    return 1
                }
            elif command_exists dnf; then
                echo -e "${CYAN}Installing Git via dnf...${NC}"
                sudo dnf install -y git || {
                    echo -e "${RED}✗ Failed to install Git${NC}"
                    return 1
                }
            else
                echo -e "${RED}✗ Package manager not supported${NC}"
                echo -e "  Please install Git manually from: ${CYAN}https://git-scm.com${NC}"
                return 1
            fi
            ;;
        *)
            echo -e "${RED}✗ OS not supported for auto-install${NC}"
            echo -e "  Please install Git manually from: ${CYAN}https://git-scm.com${NC}"
            return 1
            ;;
    esac

    # Verify installation
    if command_exists git; then
        local git_version
        git_version=$(git --version | awk '{print $3}')
        echo ""
        echo -e "${GREEN}✓${NC} Git installed successfully: $git_version"
        return 0
    fi

    echo ""
    echo -e "${RED}✗${NC} Git installation failed"
    return 1
}

# Main setup function
main() {
    print_section_banner "Git Setup - Belimbing ($APP_ENV)"

    # Load existing configuration
    load_setup_state

    # Check if Git is already installed
    if command_exists git; then
        local git_version
        git_version=$(git --version | awk '{print $3}')

        echo -e "${CYAN}ℹ${NC} Git is already installed: $git_version"
        echo ""

        if [ -t 0 ]; then
            if ask_yes_no "Skip this step?" "y"; then
                echo -e "${GREEN}✓${NC} Skipping Git setup"
                exit 0
            fi
            echo ""
            echo -e "${YELLOW}OK, let's verify Git installation...${NC}"
            echo ""
        else
            echo -e "${GREEN}✓${NC} Using existing installation"
            exit 0
        fi
    fi

    # Install Git
    print_subsection_header "Git Installation"
    echo ""

    if install_git; then
        echo -e "${GREEN}✓${NC} Git is ready"
    else
        echo -e "${RED}✗${NC} Git installation failed"
        echo ""
        echo -e "${YELLOW}Please install Git manually:${NC}"
        echo -e "  • macOS: ${CYAN}xcode-select --install${NC}"
        echo -e "  • Linux: ${CYAN}sudo apt-get install git${NC}"
        echo -e "  • Manual: ${CYAN}https://git-scm.com${NC}"
        exit 1
    fi

    echo ""

    # Save state
    save_to_setup_state "GIT_VERSION" "$(git --version | awk '{print $3}')" "$PROJECT_ROOT"

    print_divider
    echo ""
    echo -e "${GREEN}✓ Git setup complete!${NC}"
    echo ""
    echo -e "${CYAN}Installed:${NC}"
    echo -e "  • Git: $(git --version)"
    echo ""
}

# Run main function
main "$@"
