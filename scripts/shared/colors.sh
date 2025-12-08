#!/bin/bash
# Shared color constants for Belimbing scripts
#
# COLOR USAGE GUIDELINES
# =====================
#
# For consistent UX across all scripts, follow these conventions:
#
# 1. LABELS (what we're showing):
#    echo -e "${CYAN}Environment:${NC} value"
#    echo -e "${CYAN}Operating System:${NC} value"
#
# 2. VALUES (informational data):
#    echo -e "${CYAN}Label:${NC} ${GREEN}value${NC}"
#    echo -e "${CYAN}Version:${NC} ${GREEN}1.2.3${NC}"
#
# 3. STATUS INDICATORS:
#    Success:  echo -e "${GREEN}✓${NC} message"
#    Warning:  echo -e "${YELLOW}⚠${NC} message"
#    Error:    echo -e "${RED}✗${NC} message"
#    Info:     echo -e "${CYAN}ℹ${NC} message"
#
# 4. PROMPTS AND NOTES:
#    echo -e "${YELLOW}Note:${NC} informational text"
#    echo -e "${YELLOW}Install with:${NC} command here"
#
# 5. PATHS AND COMMANDS:
#    echo -e "Expected at: ${CYAN}/path/to/file${NC}"
#    echo -e "Run: ${CYAN}cargo build${NC}"
#
# 6. SECTION HEADERS:
#    Use print_section_banner() from runtime.sh (magenta lines)
#    Use print_subsection_header() from runtime.sh (cyan text + lines)
#
# Example:
#   echo -e "${CYAN}Database:${NC} ${GREEN}belimbing_dev${NC}"
#   echo -e "${GREEN}✓ PostgreSQL running${NC}"
#   echo -e "${RED}✗ Connection failed${NC}"
#   echo -e "${YELLOW}Note:${NC} Check your .env file"
#
# WHY LITERAL COLOR NAMES (RED, GREEN) vs SEMANTIC NAMES (COLOR_SUCCESS, COLOR_ERROR)?
# ------------------------------------------------------------------------------------
# We use literal color names (RED, GREEN, YELLOW) because:
#   1. Standard shell convention - 99% of Bash projects use literal names
#   2. Concise and flexible - can use colors for any purpose
#   3. Guidelines provide semantic layer - this document defines WHEN to use each color
#   4. No strong typing in Bash - semantic names don't prevent misuse
#

# shellcheck disable=SC2034

# Text colors
RED=$'\033[0;31m'
GREEN=$'\033[0;32m'
YELLOW=$'\033[1;33m'
BLUE=$'\033[0;34m'
MAGENTA=$'\033[0;35m'
CYAN=$'\033[0;36m'
WHITE=$'\033[1;37m'
GRAY=$'\033[0;90m'

# Background colors
BG_RED=$'\033[41m'
BG_GREEN=$'\033[42m'
BG_YELLOW=$'\033[43m'
BG_BLUE=$'\033[44m'

# Text styles
BOLD=$'\033[1m'
DIM=$'\033[2m'
ITALIC=$'\033[3m'
UNDERLINE=$'\033[4m'

# Reset
NC=$'\033[0m'

# Emoji/symbols (with fallbacks for non-Unicode terminals)
if [[ "${LANG}" =~ UTF-8 ]] || [[ "${LC_ALL}" =~ UTF-8 ]]; then
    CHECK_MARK="✓"
    CROSS_MARK="✗"
    INFO_MARK="ℹ"
    WARNING_MARK="⚠"
    ARROW="→"
    BULLET="•"
else
    CHECK_MARK="+"
    CROSS_MARK="x"
    INFO_MARK="i"
    WARNING_MARK="!"
    ARROW="->"
    BULLET="*"
fi
