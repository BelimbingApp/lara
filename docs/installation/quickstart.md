# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong

# Quick Start Guide

Get Belimbing running in minutes.

## Prerequisites

- Linux server (Ubuntu 22.04+, Debian 12+)
- 2GB RAM, 10GB disk
- Internet connection
- Root or sudo access

## Choose Your Method

| Method | Best For | Skill Level | Command |
|--------|----------|-------------|---------|
| **Native** | Production, simple servers | Basic Linux | `./scripts/setup.sh local` |
| **Docker** | Development, keeping OS clean | **Intermediate** (Requires Docker knowledge) | `./scripts/start-docker.sh local` |

> **Recommendation:** If you are unsure or have never used Docker before, use the **Native** method. It is easier to troubleshoot on a standard Linux server.

> **Warning:** Use only ONE method. Running both causes port conflicts.

> **Note:** Docker and Native installations use separate `.env` files:
> - **Native**: Uses root `.env` file
> - **Docker**: Uses `./docker/.env` file
> This allows both methods to coexist with different configurations.

## Native Installation

```bash
git clone <repository-url> belimbing
cd belimbing
./scripts/setup.sh local
```

Installs PHP, Composer, PostgreSQL 18, Redis, and configures everything.

**Start the app:**
```bash
./scripts/start-app.sh
```

**Stop:**
```bash
./scripts/stop-app.sh
```

## Docker Installation

```bash
git clone <repository-url> belimbing
cd belimbing
./scripts/start-docker.sh
```

The script handles Docker installation, service startup, migrations, and admin creation.

**Stop:**
```bash
./scripts/stop-docker.sh
```

**Manual commands** (from `docker/` directory):
```bash
# Development
docker compose --profile dev up -d

# Production
docker compose --profile prod up -d

# Logs
docker compose --profile dev logs -f

# Stop
docker compose --profile dev down

# Run artisan
docker compose --profile dev exec app php artisan <command>
```

## Access

- **Web:** https://local.blb.lara
- **API:** https://local.blb.lara/api

### Hosts File Configuration

**Linux (Native installation):**
```bash
# Add to /etc/hosts
127.0.0.1 local.blb.lara local.api.blb.lara
```

**Windows (WSL2 users):**
If you're running Belimbing in WSL2 and accessing from a Windows browser, you need to use the **WSL2 IP address** instead of `127.0.0.1`.

1. **Find your WSL2 IP address:**
   ```bash
   # In WSL2 terminal
   ip addr show eth0 | grep "inet " | awk '{print $2}' | cut -d/ -f1
   ```

2. **Add to Windows hosts file** (`C:\Windows\System32\drivers\etc\hosts`):
   ```
   172.25.114.176 local.blb.lara local.api.blb.lara
   ```
   *(Replace `172.25.114.176` with your actual WSL2 IP address)*

3. **Edit as Administrator:**
   - Open Notepad as Administrator (Win+R → `notepad` → Ctrl+Shift+Enter)
   - Or use PowerShell as Administrator:
     ```powershell
     Add-Content -Path "C:\Windows\System32\drivers\etc\hosts" -Value "172.25.114.176 local.blb.lara local.api.blb.lara"
     ```

> **Why?** Windows `127.0.0.1` points to Windows localhost, not WSL2. Using the WSL2 IP allows Windows browsers to reach services running in WSL2.

### SSL Certificate Trust (WSL2 users)

When accessing custom domains like `https://local.blb.lara` from a Windows browser, you'll see a certificate warning because Caddy uses self-signed certificates for development. To trust the certificate:

1. **Navigate to the certificate in Windows Explorer:**
   - Open Windows Explorer
   - Navigate to: `<project_root>\storage\app\ssl`

2. **Double-click `caddy-root-ca.crt`**

3. **Install the certificate:**
   - Click "Install Certificate" → "Local Machine" → Next
   - Select "Place all certificates in the following store"
   - Click "Browse" → Select "Trusted Root Certification Authorities" → OK → Next → Finish

4. **Restart your browser**

> **Alternative:** You can also accept the browser warning each time (safe for self-signed development certificates).

## Create Admin

Admin is created during installation. To create manually:

```bash
# Interactive
php artisan belimbing:create-admin

# Non-interactive
echo "password" | php artisan belimbing:create-admin admin@example.com --stdin

# Docker
docker compose --profile dev exec app php artisan belimbing:create-admin
```

## Switching Methods

**Native → Docker:**
```bash
./scripts/stop-app.sh
./scripts/start-docker.sh
```

**Docker → Native:**
```bash
./scripts/stop-docker.sh
./scripts/start-app.sh
```

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Port in use | Stop conflicting service or change port in `.env` (root `.env` for native, `./docker/.env` for Docker) |
| Database error | Check PostgreSQL: `systemctl status postgresql` |
| Docker error | Check Docker Desktop is running |
| Permission error | `sudo chown -R $USER:$USER storage` |
| Docker Permission Denied | Common issue. Ensure your user ID matches or run `sudo chown -R 1000:1000 storage` inside the container context. |
| Wrong config in Docker | Ensure `./docker/.env` exists and has Docker-specific values (DB_HOST=postgres, REDIS_HOST=redis) |
| 502 Bad Gateway from Windows browser (WSL2) | Use WSL2 IP address in Windows hosts file instead of `127.0.0.1`. See [Hosts File Configuration](#hosts-file-configuration) above. |

## Next Steps

- [Visual Guide](visual-guide.md) - Installation diagrams
- [Architecture](../architecture/) - System design
- [Troubleshooting](troubleshooting.md) - Common issues
