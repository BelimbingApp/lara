#!/bin/bash
# Interactive input utilities for Belimbing scripts
# Functions for prompting user input during setup

# Source colors if not already loaded
INTERACTIVE_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -z "$RED" ]; then
    source "$INTERACTIVE_SCRIPT_DIR/colors.sh"
fi

# Ask yes/no question
ask_yes_no() {
    local prompt=$1
    local default=${2:-y}

    local choices
    if [ "$default" = "y" ]; then
        choices="[Y/n]"
    else
        choices="[y/N]"
    fi

    while true; do
        read -r -p "$(echo -e "${prompt} ${choices}: ")" response
        response=${response:-$default}
        case "$response" in
            [Yy]*) return 0 ;;
            [Nn]*) return 1 ;;
            *) echo -e "${YELLOW}Please answer y or n${NC}" >&2 ;;
        esac
    done
}

# Ask for input with default and optional validator
ask_input() {
    local prompt=$1
    local default=$2
    local validator=${3:-}

    while true; do
        if [ -n "$default" ]; then
            read -r -p "$(echo -e "${prompt} ${DIM}[${default}]${NC}: ")" response
            response=${response:-$default}
        else
            read -r -p "$(echo -e "${prompt}: ")" response
        fi

        if [ -z "$response" ]; then
            echo -e "${YELLOW}This field is required${NC}" >&2
            continue
        fi

        if [ -n "$validator" ]; then
            if $validator "$response"; then
                echo "$response"
                return 0
            else
                echo -e "${YELLOW}Invalid input. Please try again.${NC}" >&2
                continue
            fi
        fi

        echo "$response"
        return 0
    done
}
