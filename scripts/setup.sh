#!/usr/bin/env bash
# Belimbing Environment Setup
#
# Orchestrates modular setup steps for the Belimbing (BLB) project.
# Each component (PostgreSQL, Caddy, etc.) has its own independent script
# in setup-steps/ that can be run standalone or called by this orchestrator.
#
# Usage: ./scripts/setup.sh [local|staging|production|testing] [--quick]
#
# Options:
#   local|staging|production|testing - Laravel APP_ENV value (default: local)
#   --quick                          - Skip interactive prompts, use defaults
#
# Examples:
#   ./scripts/setup.sh local        # Interactive local setup
#   ./scripts/setup.sh local --quick # Non-interactive local setup

set -euo pipefail

# Get script directory and project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Source shared utilities
# shellcheck source=/shared/colors.sh
source "$SCRIPT_DIR/shared/colors.sh" 2>/dev/null || true
# shellcheck source=/shared/runtime.sh
source "$SCRIPT_DIR/shared/runtime.sh" 2>/dev/null || true
# shellcheck source=/shared/interactive.sh
source "$SCRIPT_DIR/shared/interactive.sh" 2>/dev/null || true

# Parse arguments
APP_ENV="${1:-local}"
QUICK_MODE=false

for arg in "$@"; do
    case "$arg" in
        --quick)
            QUICK_MODE=true
            ;;
        local|staging|production|testing)
            APP_ENV="$arg"
            ;;
    esac
done

# Validate environment value (Laravel standard APP_ENV values)
if [[ ! "$APP_ENV" =~ ^(local|staging|production|testing)$ ]]; then
    echo -e "${RED}✗ Invalid APP_ENV: '$APP_ENV'${NC}"
    echo -e "${YELLOW}Valid values: local, staging, production, testing${NC}"
    exit 1
fi

# Display banner
clear
print_section_banner "Belimbing Environment Setup ($APP_ENV)"

# Auto-discover setup steps from setup-steps/ directory
# Each step file must have a "# Title: ..." line in its header
declare -a STEPS=()

# Find all .sh files in setup-steps/, sorted by name
while IFS= read -r step_file; do
    script_name=$(basename "$step_file")

    # Extract title from "# Title: ..." line in the file
    title=$(grep "^# Title:" "$step_file" | head -1 | sed 's/^# Title: //')

    if [ -z "$title" ]; then
        # Fallback if no Title line found
        title="Setup Step"
    fi

    STEPS+=("$script_name:$title")
done < <(find "$SCRIPT_DIR/setup-steps" -name "*.sh" -type f | sort)

# Verify we found steps
if [ ${#STEPS[@]} -eq 0 ]; then
    echo -e "${RED}✗ No setup steps found in $SCRIPT_DIR/setup-steps/${NC}"
    exit 1
fi

# Track results
declare -a COMPLETED=()
declare -a FAILED=()

# Display setup plan
echo -e "${CYAN}Setup Steps:${NC}"
step_num=1
for step in "${STEPS[@]}"; do
    IFS=':' read -r script description <<< "$step"
    echo -e "  ${step_num}. ${description}"
    ((step_num++))
done
echo ""

if [ "$QUICK_MODE" = false ] && [ -t 0 ]; then
    read -r -p "Press Enter to begin, or Ctrl+C to cancel... "
    echo ""
fi

# Run each step
step_num=1
for step in "${STEPS[@]}"; do
    IFS=':' read -r script description <<< "$step"

    step_script="$SCRIPT_DIR/setup-steps/$script"
    if [ ! -f "$step_script" ]; then
        echo -e "${RED}✗${NC} Step script not found: $step_script"
        FAILED+=("$description")
        ((step_num++))
        continue
    fi

    # Run the step
    if bash "$step_script" "$APP_ENV"; then
        echo ""
        echo -e "${GREEN}✓${NC} ${description} - Complete"
        COMPLETED+=("$description")
    else
        echo ""
        echo -e "${RED}✗${NC} ${description} - Failed"
        FAILED+=("$description")

        # Ask if user wants to continue
        if [ "$QUICK_MODE" = false ] && [ -t 0 ]; then
            echo ""
            if ! ask_yes_no "Continue with remaining steps?" "n"; then
                echo ""
                echo -e "${YELLOW}Setup interrupted${NC}"
                break
            fi
        else
            echo -e "${YELLOW}Stopping due to failure${NC}"
            break
        fi
    fi

    echo ""
    ((step_num++))
done

# Display summary
echo ""
print_section_banner "Setup Summary"

if [ ${#COMPLETED[@]} -gt 0 ]; then
    echo -e "${GREEN}✓ Completed (${#COMPLETED[@]}):${NC}"
    for step in "${COMPLETED[@]}"; do
        echo -e "  • $step"
    done
    echo ""
fi

if [ ${#FAILED[@]} -gt 0 ]; then
    echo -e "${RED}✗ Failed (${#FAILED[@]}):${NC}"
    for step in "${FAILED[@]}"; do
        echo -e "  • $step"
    done
    echo ""
fi

# Final status
if [ ${#FAILED[@]} -eq 0 ] && [ ${#COMPLETED[@]} -eq ${#STEPS[@]} ]; then
    echo -e "${GREEN}✓ Setup Complete! 🎉${NC}"
    echo ""
    echo -e "${CYAN}Configuration:${NC}"
    echo -e "  • Generated: ${CYAN}.env${NC} (you can edit this file to customize settings)"
    echo ""

    # Cleanup temporary setup state file
    # Source config.sh to get get_setup_state_file function
    # shellcheck source=shared/config.sh
    source "$SCRIPT_DIR/shared/config.sh" 2>/dev/null || true
    if command -v get_setup_state_file >/dev/null 2>&1; then
        state_file=$(get_setup_state_file)
        if [ -f "$state_file" ]; then
            rm -f "$state_file"
        fi
    elif [ -f "$PROJECT_ROOT/storage/app/.devops/setup.env" ]; then
        # Fallback to direct path if function not available
        rm -f "$PROJECT_ROOT/storage/app/.devops/setup.env"
    fi

    # Offer to start the app
    if [ -t 0 ]; then
        echo ""
        if read -r -p "Start Belimbing now? (y/n) [y]: " -n 1; then
            echo ""
            if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
                echo ""
                exec "$SCRIPT_DIR/start-app.sh"
            fi
        fi
        echo ""
    else
        echo -e "${CYAN}Start the app:${NC}"
        echo -e "  ${CYAN}./scripts/start-app.sh${NC}"
        echo ""
    fi
    exit 0
else
    echo -e "${YELLOW}⚠ Setup Incomplete${NC}"
    echo ""
    echo -e "${CYAN}Retry failed steps:${NC}"
    for step in "${STEPS[@]}"; do
        IFS=':' read -r script description <<< "$step"
        for failed in "${FAILED[@]}"; do
            if [ "$failed" = "$description" ]; then
                echo -e "  ${CYAN}./scripts/setup-steps/$script $APP_ENV${NC}"
            fi
        done
    done
    echo ""
    echo -e "${CYAN}Or re-run full setup:${NC}"
    echo -e "  ${CYAN}./scripts/setup.sh $APP_ENV${NC}"
    echo ""
    exit 1
fi
