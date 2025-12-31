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

| Method | Best For | Command |
|--------|----------|---------|
| **Native** | Production, performance | `./scripts/setup.sh local` |
| **Docker** | Development, teams | `./scripts/start-docker.sh local` |

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

> Add to `/etc/hosts` if needed: `127.0.0.1 local.blb.lara`

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
| Wrong config in Docker | Ensure `./docker/.env` exists and has Docker-specific values (DB_HOST=postgres, REDIS_HOST=redis) |

## Next Steps

- [Visual Guide](visual-guide.md) - Installation diagrams
- [Architecture](../architecture/) - System design
- [Troubleshooting](troubleshooting.md) - Common issues
