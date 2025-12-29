# Quick Start Guide - Belimbing

This guide will help you get Belimbing up and running in minutes.

## Prerequisites

Before you begin, ensure you have:
- A Linux server (Ubuntu 22.04+, Debian 12+, or similar)
- At least 2GB RAM and 10GB disk space
- Internet connection
- Root or sudo access

## Installation Methods

### Method 1: One-Command Installation (Recommended)

The fastest way to get started:

```bash
# Clone the repository
git clone <repository-url> belimbing
cd belimbing

# Run the setup script
./scripts/setup.sh local
```

The setup script will:
- Check system requirements
- Install missing dependencies (PHP, Composer, PostgreSQL, Redis, etc.)
- Configure the database
- Generate application keys
- Set up all services

**Time:** ~5-10 minutes

### Method 2: Docker Installation

For containerized deployment:

```bash
# Clone the repository
git clone <repository-url> belimbing
cd belimbing

# Run Docker installer
./scripts/install-docker.sh local

# Or manually with Docker Compose
docker compose -f docker/docker-compose.yml up -d
```

**Time:** ~3-5 minutes (if Docker is pre-installed)

### Method 3: Manual Installation

For advanced users who want full control:

```bash
# 1. Check requirements
./scripts/check-requirements.sh local

# 2. Run setup steps individually
./scripts/setup-steps/00-environment.sh local
./scripts/setup-steps/10-git.sh local
./scripts/setup-steps/20-php.sh local
./scripts/setup-steps/25-laravel.sh local
./scripts/setup-steps/30-js.sh local
./scripts/setup-steps/40-database.sh local
./scripts/setup-steps/60-migrations.sh local
./scripts/setup-steps/70-caddy.sh local
```

**Time:** ~15-30 minutes

## Post-Installation

### 1. Start the Application

```bash
./scripts/start-app.sh
```

This will start:
- Laravel development server (port 8000)
- Vite development server (port 5173)
- Queue worker
- Log monitor
- Caddy reverse proxy (HTTPS on port 443)

### 2. Access the Application

- **Web Interface:** https://local.blb.lara (or the domain configured for your environment)
- **API:** https://local.blb.lara/api

### 3. Create Admin Account

The admin account is automatically created during installation (step 60-migrations.sh). The command only runs when no users exist:

```bash
# Interactive mode (will prompt for email and password)
php artisan belimbing:create-admin

# Non-interactive mode with STDIN (secure)
echo "secure-password" | php artisan belimbing:create-admin admin@example.com --stdin
```

> **Note:** This command only creates the first admin user. Once any user exists, the command will skip. Use the application interface to manage users after installation.

## Common Commands

### Development

```bash
# Start development environment
./scripts/start-app.sh

# Stop all services
./scripts/stop-app.sh

# Run tests
composer run test

# Code quality
composer run lint
```

### Maintenance

```bash
# Update application
php artisan belimbing:update

# Create backup
php artisan belimbing:backup

# Check system health
curl http://localhost:8000/health
```

### Docker Commands

```bash
# View logs
docker compose -f docker/docker-compose.yml logs -f

# Stop services
docker compose -f docker/docker-compose.yml down

# Restart services
docker compose -f docker/docker-compose.yml restart

# Rebuild images
docker compose -f docker/docker-compose.yml build
```

## Troubleshooting

### Port Already in Use

If you get port conflicts:

```bash
# Check what's using the port
sudo lsof -i :8000
sudo lsof -i :5432

# Stop conflicting services or change ports in .env
```

### Database Connection Failed

```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection
psql -h localhost -U belimbing_app -d blb
```

### Permission Errors

```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Next Steps

- Read the [Architecture Documentation](../architecture/)
- Explore the [Module Documentation](../modules/)
- Check the [Troubleshooting Guide](troubleshooting.md) for common issues

## Getting Help

- **Documentation:** Check `docs/` directory
- **Issues:** Open an issue on GitHub
- **Community:** Join our community discussions

---

**Installation complete!** You're ready to start building with Belimbing. 🎉
