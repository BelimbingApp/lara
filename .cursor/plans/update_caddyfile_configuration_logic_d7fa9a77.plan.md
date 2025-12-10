---
name: Update Caddyfile configuration logic
overview: Refactor configure_existing_caddy to never touch system Caddyfiles and intelligently update project-level Caddyfiles when domains differ.
todos:
  - id: "1"
    content: Simplify system Caddyfile detection - always create Caddyfile.blb for system files
    status: completed
  - id: "2"
    content: Add extract_belimbing_domains() helper function to parse existing Caddyfile
    status: completed
  - id: "3"
    content: Add domain comparison logic to check if update is needed
    status: completed
  - id: "4"
    content: Implement Caddyfile update logic (replace existing block vs append new block)
    status: completed
  - id: "5"
    content: Update instructions based on whether file was created or modified
    status: completed
---

# Update Caddyfile Configuration Logic

## Current Issues

1. System Caddyfiles are detected but then we create `Caddyfile.blb` - this is correct, but logic can be clearer
2. Project-level Caddyfiles are appended to without checking if domains match
3. No logic to detect and update existing domain configurations

## Changes Required

### 1. Simplify System Caddyfile Handling (`scripts/setup-steps/70-caddy.sh`)

- **Location**: `configure_existing_caddy()` function, lines 136-156
- **Change**: Always create `Caddyfile.blb` when system Caddyfile is detected (no need to check if it exists first)
- **Logic**: If Caddyfile path matches `/etc/` or `/usr/local/etc/`, immediately set `caddyfile="$PROJECT_ROOT/Caddyfile.blb"` and skip all existing file checks

### 2. Add Project Caddyfile Domain Detection

- **Location**: `configure_existing_caddy()` function, after detecting project-level Caddyfile
- **New function**: `extract_domains_from_caddyfile()` to parse existing Caddyfile and extract configured domains
- **Logic**: 
  - Search for `https://` blocks in Caddyfile
  - Extract domain names from those blocks
  - Return list of domains found

### 3. Add Domain Comparison Logic

- **Location**: `configure_existing_caddy()` function
- **Logic**:
  - If project-level Caddyfile exists and Belimbing config block exists:
    - Extract current domains from Belimbing block
    - Compare with new domains (`$frontend_domain`, `$backend_domain`)
    - If domains match → skip (already configured correctly)
    - If domains differ → update the Belimbing block with new domains
  - If project-level Caddyfile exists but no Belimbing block:
    - Append new Belimbing configuration block

### 4. Update Caddyfile Instead of Append

- **Location**: `configure_existing_caddy()` function, lines 179-195
- **Change**: Instead of always appending, check if Belimbing block exists:
  - If exists: Use `sed` or similar to replace the existing block
  - If not exists: Append new block
- **Pattern**: Match the Belimbing configuration block (between `# Belimbing configuration` comments) and replace it

## Implementation Details

### New Helper Function

```bash
# Extract domains from existing Belimbing config in Caddyfile
extract_belimbing_domains() {
    local caddyfile=$1
    # Parse Caddyfile to find Belimbing block and extract domains
    # Return: frontend_domain|backend_domain or empty if not found
}
```

### Updated Logic Flow

1. Detect Caddyfile location
2. If system file → always use `$PROJECT_ROOT/Caddyfile.blb`
3. If project file:

   - Check if Belimbing block exists
   - If exists: Extract current domains, compare with new domains
   - If domains differ: Update the block
   - If domains match: Skip (already configured)
   - If no block: Append new block

4. Generate certificates
5. Provide usage instructions

## Files to Modify

- `scripts/setup-steps/70-caddy.sh`: Update `configure_existing_caddy()` function