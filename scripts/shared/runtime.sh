#!/bin/bash
# Runtime directory management utilities for Belimbing scripts
# Manages script runtime files within Laravel's storage/ directory structure

# Source colors if not already loaded
RUNTIME_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -z "$RED" ]; then
    source "$RUNTIME_SCRIPT_DIR/colors.sh"
fi

# detect_os is expected to be available from validation.sh
# If not loaded, define a minimal version
if ! command -v detect_os >/dev/null 2>&1; then
    detect_os() {
        if [[ "$OSTYPE" == "darwin"* ]]; then
            echo "macos"
        elif grep -qEi "(Microsoft|WSL)" /proc/version 2>/dev/null; then
            echo "wsl2"
        else
            echo "linux"
        fi
    }
fi

# Ensure storage directory structure exists for script runtime files
# Uses Laravel's standard storage/ directory structure for consistency
# - storage/app/.devops/: Script runtime files (PIDs, setup state)
# - storage/logs/scripts/: Script logs (devops/deployment scripts)
# - storage/app/backups/: Database/backup files (script-managed)
# This simplifies drive mounting (one directory) and follows Laravel conventions
ensure_storage_dirs() {
    local project_root=$1

    if [ -z "$project_root" ]; then
        echo -e "${RED}${CROSS_MARK} Project root not provided${NC}" >&2
        return 1
    fi

    local storage_dir="$project_root/storage"
    # Script-specific subdirectories within Laravel storage/
    local dirs=(
        "app/.devops"          # PID files, setup state, script temp files
        "logs/scripts"         # Script/deployment logs
        "app/backups"          # Database/script-managed backups
    )

    for dir in "${dirs[@]}"; do
        mkdir -p "$storage_dir/$dir"
    done

    return 0
}

# Get Laravel storage directory path
get_storage_dir() {
    local project_root=$1
    echo "$project_root/storage"
}

# Get script logs directory path (devops/script logs)
# Laravel application logs are in storage/logs/laravel.log
get_logs_dir() {
    local project_root=$1
    echo "$project_root/storage/logs/scripts"
}

# Get backups directory path (script-managed backups)
get_backups_dir() {
    local project_root=$1
    echo "$project_root/storage/app/backups"
}

# Get tmp directory path (script runtime files: PIDs, setup state)
get_tmp_dir() {
    local project_root=$1
    echo "$project_root/storage/app/.devops"
}

# Get Laravel application logs directory path
get_laravel_logs_dir() {
    local project_root=$1
    echo "$project_root/storage/logs"
}

# Clean script runtime directories (removes only script files, not Laravel app files)
clean_script_dirs() {
    local project_root=$1
    local storage_dir="$project_root/storage"

    if [ ! -d "$storage_dir" ]; then
        echo -e "${YELLOW}${INFO_MARK} storage/ directory does not exist${NC}"
        return 0
    fi

    echo -e "${YELLOW}${INFO_MARK} Cleaning script runtime directories...${NC}"

    # Remove only script-specific directories, preserve Laravel app files
    [ -d "$storage_dir/app/.devops" ] && rm -rf "${storage_dir}/app/.devops"/*
    [ -d "$storage_dir/logs/scripts" ] && rm -rf "${storage_dir}/logs/scripts"/*
    [ -d "$storage_dir/app/backups" ] && rm -rf "${storage_dir}/app/backups"/*

    echo -e "${GREEN}${CHECK_MARK}${NC} Cleaned"
    return 0
}

# Show Belimbing ASCII art banner
show_banner() {
    echo -e -n "${MAGENTA}"
    cat << 'EOF' | head -c -1
   ██████╗ ███████╗██╗     ██╗███╗   ███╗██████╗ ██╗███╗   ██╗ ██████╗
   ██╔══██╗██╔════╝██║     ██║████╗ ████║██╔══██╗██║████╗  ██║██╔════╝
   ██████╔╝█████╗  ██║     ██║██╔████╔██║██████╔╝██║██╔██╗ ██║██║  ███╗
   ██╔══██╗██╔══╝  ██║     ██║██║╚██╔╝██║██╔══██╗██║██║╚██╗██║██║   ██║
   ██████╔╝███████╗███████╗██║██║ ╚═╝ ██║██████╔╝██║██║ ╚████║╚██████╔╝
   ╚═════╝ ╚══════╝╚══════╝╚═╝╚═╝     ╚═╝╚═════╝ ╚═╝╚═╝  ╚═══╝ ╚═════╝
EOF
    echo -e "${NC}"
}

# Print a section banner with magenta top and bottom lines
# Usage: print_section_banner "Title Text" [color]
#   or:  print_section_banner "${GREEN}✨ Ready!${NC} ${WHITE}Ctrl+C to stop"
#
# The title text supports inline color codes and formatting.
# Optional second parameter sets the line color (default: magenta)
print_section_banner() {
    local title="$1"
    local color="${2:-$MAGENTA}"  # Default to magenta if no color specified

    echo ""
    echo -e "${color}════════════════════════════════════════════════════════════${NC}"
    echo -e "${color}${BOLD}  ${title}${NC}"
    echo -e "${color}════════════════════════════════════════════════════════════${NC}"
    echo ""
}

# Print a subsection header with horizontal lines
# Usage: print_subsection_header "Subsection Title"
#   or:  print_subsection_header "${CYAN}Step 1/4:${NC} Environment Setup"
#
# The title supports inline color codes and formatting.
print_subsection_header() {
    local title="$1"

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "  ${title}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}

# Print a simple horizontal divider line
# Usage: print_divider
print_divider() {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

# Launch browser helper (WSL2-friendly)
launch_browser() {
    local url=$1
    local os_type
    os_type="$(detect_os)"

    case "$os_type" in
        macos)
            if command -v open >/dev/null 2>&1; then
                open "$url" >/dev/null 2>&1 &
                return 0
            fi
            ;;
        linux)
            if command -v xdg-open >/dev/null 2>&1; then
                xdg-open "$url" >/dev/null 2>&1 &
                return 0
            fi
            ;;
        wsl2)
            if command -v chromium-browser >/dev/null 2>&1; then
                chromium-browser "$url" >/dev/null 2>&1 &
                return 0
            fi
            if command -v chromium >/dev/null 2>&1; then
                chromium "$url" >/dev/null 2>&1 &
                return 0
            fi
            if command -v google-chrome >/dev/null 2>&1; then
                google-chrome "$url" >/dev/null 2>&1 &
                return 0
            fi
            if command -v firefox >/dev/null 2>&1; then
                firefox "$url" >/dev/null 2>&1 &
                return 0
            fi
            if command -v xdg-open >/dev/null 2>&1; then
                xdg-open "$url" >/dev/null 2>&1 &
                return 0
            fi
            if command -v sensible-browser >/dev/null 2>&1; then
                sensible-browser "$url" >/dev/null 2>&1 &
                return 0
            fi
            ;;
    esac

    echo -e "${YELLOW}${INFO_MARK} Couldn't auto-launch browser. Open manually: ${CYAN}$url${NC}"
    return 1
}
