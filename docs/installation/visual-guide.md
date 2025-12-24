# Visual Installation Guide - Belimbing

Step-by-step installation guide with decision trees and visual aids.

## Installation Flow

```
┌─────────────────────────────────────┐
│   Choose Installation Method        │
└──────────────┬──────────────────────┘
               │
       ┌───────┴────────┐
       │                │
   ┌───▼───┐      ┌─────▼─────┐
   │  CLI  │      │   Docker  │
   │ Setup │      │  Compose  │
   └───┬───┘      └─────┬─────┘
       │                │
       └───────┬────────┘
               │
    ┌──────────▼─────────┐
    │  Pre-flight Check  │
    │  (Requirements)    │
    └──────────┬─────────┘
               │
    ┌──────────▼───────────┐
    │  Install Dependencies│
    │  (PHP, DB, Redis)    │
    └──────────┬───────────┘
               │
    ┌──────────▼──────────┐
    │  Configure App      │
    │  (.env, keys)       │
    └──────────┬──────────┘
               │
    ┌──────────▼──────────┐
    │  Start Services     │
    │  (Server, Vite)     │
    └──────────┬──────────┘
               │
    ┌──────────▼─────────┐
    │  ✓ Ready!          │
    └────────────────────┘
```

## Decision Tree: Which Installation Method?

```
                    Start Installation
                           │
                           ▼
              ┌────────────────────────┐
              │  Do you have Docker?   │
              └────────┬───────────────┘
                       │
          ┌────────────┴─────────────┐
          │                          │
         YES                        NO
          │                          │
          ▼                          ▼
  ┌──────────────┐          ┌──────────────┐
  │ Use Docker   │          │ Use CLI      │
  │ Method       │          │ Method       │
  └──────┬───────┘          └──────┬───────┘
         │                          │
         ▼                          ▼
  docker-compose up          ./scripts/setup.sh
```

## Step-by-Step: CLI Installation

### Step 1: Clone Repository

```bash
git clone <repository-url> belimbing
cd belimbing
```

**Expected output:**
```
Cloning into 'belimbing'...
remote: Enumerating objects: X, done.
✓ Repository cloned
```

### Step 2: Run Setup Script

```bash
./scripts/setup.sh local
```

**What happens:**
1. System requirements check
2. Dependency installation prompts
3. Database setup
4. Application configuration
5. Service startup

**Expected output:**
```
Belimbing Environment Setup (local)
====================================

Setup Steps:
  1. Environment & Prerequisites
  2. Git Version Control
  3. PHP & Composer
  4. Laravel Application
  5. JavaScript Runtime
  6. Database & Redis
  7. Caddy Reverse Proxy

Press Enter to begin...
```

### Step 3: Verify Installation

```bash
# Check health
curl http://localhost:8000/health

# Or visit in browser
open https://local.blb.lara
```

**Expected response:**
```json
{
  "status": "healthy",
  "checks": {
    "database": {"status": "ok"},
    "redis": {"status": "ok"}
  }
}
```

## Step-by-Step: Docker Installation

### Step 1: Install Docker (if needed)

```bash
# Check if Docker is installed
docker --version

# If not, run installer
./scripts/install-docker.sh local
```

### Step 2: Start Services

```bash
# Development
docker compose -f docker/docker-compose.yml up -d

# Production
docker compose -f docker-compose.prod.yml up -d
```

### Step 3: Verify Services

```bash
docker compose -f docker/docker-compose.yml ps
```

**Expected output:**
```
NAME                  STATUS          PORTS
belimbing-app         Up              0.0.0.0:8000->8000/tcp
belimbing-postgres    Up (healthy)    0.0.0.0:5432->5432/tcp
belimbing-redis       Up (healthy)    0.0.0.0:6379->6379/tcp
belimbing-caddy       Up              0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
```

## Common Scenarios

### Scenario 1: Fresh Server Installation

**Steps:**
1. SSH into server
2. Install Git: `sudo apt-get install git`
3. Clone repository
4. Run: `./scripts/setup.sh local --auto-install`
5. Wait for completion (~10 minutes)
6. Access: `https://your-domain.com`

### Scenario 2: Development Machine

**Steps:**
1. Ensure Docker Desktop is running
2. Clone repository
3. Run: `./scripts/install-docker.sh local`
4. Access: `https://local.blb.lara`

### Scenario 3: Existing Laravel Server

**Steps:**
1. Check requirements: `./scripts/check-requirements.sh local`
2. Run only missing steps:
   - `./scripts/setup-steps/40-database.sh local` (if DB not configured)
   - `./scripts/setup-steps/70-caddy.sh local` (if reverse proxy needed)
3. Start app: `./scripts/start-app.sh`

## Verification Checklist

After installation, verify:

- [ ] Health endpoint returns 200: `curl http://localhost:8000/health`
- [ ] Database connection works: `php artisan tinker` → `DB::connection()->getPdo()`
- [ ] Redis connection works: `redis-cli ping` → `PONG`
- [ ] Web interface loads: Visit `https://local.blb.lara`
- [ ] Logs directory exists: `ls storage/logs/`
- [ ] .env file configured: `cat .env | grep APP_KEY` (should not be empty)

## Next Steps After Installation

1. **Create Admin Account**
   ```bash
   php artisan tinker
   >>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('secure-password')]);
   ```

2. **Run Migrations** (if not done automatically)
   ```bash
   php artisan migrate
   ```

3. **Configure Email** (optional)
   ```bash
   # Edit .env with your mail settings
   nano .env
   ```

4. **Set Up Backups**
   ```bash
   # Test backup
   php artisan belimbing:backup
   ```

## Troubleshooting Quick Reference

| Problem | Quick Fix |
|---------|-----------|
| Port in use | Change port in `.env` or stop conflicting service |
| Database error | Run `./scripts/setup-steps/40-database.sh local` |
| Permission denied | `sudo chown -R www-data:www-data storage` |
| APP_KEY missing | `php artisan key:generate` |
| Docker won't start | Check logs: `docker compose logs` |

For detailed troubleshooting, see [Troubleshooting Guide](troubleshooting.md).

---

**Visual guides with screenshots will be added as the project matures.**
