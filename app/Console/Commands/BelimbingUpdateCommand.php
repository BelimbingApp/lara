<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BelimbingUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'belimbing:update
                            {--dry-run : Preview changes without applying them}
                            {--force : Skip confirmation prompts}
                            {--backup : Create backup before update (default: true)}
                            {--no-backup : Skip backup creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Belimbing application with automated backup and rollback';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Belimbing Update System');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $backup = ! $this->option('no-backup') && ($this->option('backup') || ! $this->option('no-backup'));

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be applied');
            $this->newLine();
        }

        // Check for updates
        $this->info('Checking for updates...');
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestVersion();

        $this->line("Current version: <comment>{$currentVersion}</comment>");
        $this->line("Latest version: <comment>{$latestVersion}</comment>");

        if ($currentVersion === $latestVersion) {
            $this->info('✓ Already up to date!');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info("Update available: {$currentVersion} → {$latestVersion}");

        if (! $force && ! $dryRun) {
            if (! $this->confirm('Do you want to proceed with the update?', true)) {
                $this->info('Update cancelled.');

                return Command::SUCCESS;
            }
        }

        // Create backup
        if ($backup && ! $dryRun) {
            $this->newLine();
            $this->info('Creating backup...');
            $backupPath = $this->createBackup();
            if ($backupPath) {
                $this->info("✓ Backup created: {$backupPath}");
            } else {
                $this->error('✗ Backup failed');
                if (! $this->confirm('Continue without backup?', false)) {
                    return Command::FAILURE;
                }
            }
        }

        // Perform update
        $this->newLine();
        $this->info('Updating application...');

        try {
            if (! $dryRun) {
                $this->performUpdate();
            } else {
                $this->line('Would execute: git pull origin main');
                $this->line('Would run: composer install --no-dev --optimize-autoloader');
                $this->line('Would run: bun install --production');
                $this->line('Would run: php artisan migrate --force');
                $this->line('Would run: php artisan config:cache');
                $this->line('Would run: php artisan route:cache');
                $this->line('Would run: php artisan view:cache');
            }

            $this->newLine();
            $this->info('✓ Update completed successfully!');

            if ($backup && ! $dryRun && isset($backupPath)) {
                $this->line("Backup location: {$backupPath}");
                $this->line('To rollback, restore from the backup directory.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Update failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            if ($backup && ! $dryRun && isset($backupPath)) {
                $this->newLine();
                $this->warn('Rollback available from backup: '.$backupPath);
                if ($this->confirm('Would you like to rollback?', false)) {
                    $this->rollback($backupPath);
                }
            }

            return Command::FAILURE;
        }
    }

    /**
     * Get current version from git or composer.json
     */
    private function getCurrentVersion(): string
    {
        // Try git tag first
        $result = Process::run('git describe --tags --abbrev=0 2>/dev/null || echo "unknown"');
        $version = trim($result->output());

        if ($version !== 'unknown' && $version !== '') {
            return $version;
        }

        // Fallback to git commit hash
        $result = Process::run('git rev-parse --short HEAD 2>/dev/null || echo "unknown"');
        $hash = trim($result->output());

        if ($hash !== 'unknown' && $hash !== '') {
            return "dev-{$hash}";
        }

        // Fallback to composer.json version
        if (File::exists(base_path('composer.json'))) {
            $composer = json_decode(File::get(base_path('composer.json')), true);

            return $composer['version'] ?? 'unknown';
        }

        return 'unknown';
    }

    /**
     * Get latest version from git remote or GitHub API
     */
    private function getLatestVersion(): string
    {
        // Try to fetch latest tag from remote
        $result = Process::run('git ls-remote --tags origin 2>/dev/null | tail -1 | sed "s/.*\\///" || echo ""');
        $latestTag = trim($result->output());

        if ($latestTag !== '') {
            return $latestTag;
        }

        // Fallback: try GitHub API (if repository is on GitHub)
        try {
            $repo = $this->getGitHubRepo();
            if ($repo) {
                $response = file_get_contents("https://api.github.com/repos/{$repo}/releases/latest");
                if ($response) {
                    $data = json_decode($response, true);

                    return $data['tag_name'] ?? 'unknown';
                }
            }
        } catch (\Exception $e) {
            // Ignore API errors
        }

        // Fallback: assume current is latest
        return $this->getCurrentVersion();
    }

    /**
     * Get GitHub repository name from git remote
     */
    private function getGitHubRepo(): ?string
    {
        $result = Process::run('git remote get-url origin 2>/dev/null || echo ""');
        $url = trim($result->output());

        if (preg_match('/github\.com[:\/]([^\/]+\/[^\/]+)(?:\.git)?$/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Create backup of database and files
     */
    private function createBackup(): ?string
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = date('Y-m-d_His');
        $backupPath = "{$backupDir}/update-{$timestamp}";
        File::ensureDirectoryExists($backupPath);

        // Backup database
        try {
            $dbName = config('database.connections.'.config('database.default').'.database');
            $dbUser = config('database.connections.'.config('database.default').'.username');
            $dbHost = config('database.connections.'.config('database.default').'.host');
            $dbPort = config('database.connections.'.config('database.default').'.port');

            $dumpFile = "{$backupPath}/database.sql";

            // Use pg_dump for PostgreSQL
            if (config('database.default') === 'pgsql') {
                $command = sprintf(
                    'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s > %s',
                    escapeshellarg(config('database.connections.pgsql.password')),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbName),
                    escapeshellarg($dumpFile)
                );

                Process::run($command);
            }

            $this->line('  ✓ Database backup created');
        } catch (\Exception $e) {
            $this->warn('  ⚠ Database backup failed: '.$e->getMessage());
        }

        // Backup .env file
        if (File::exists(base_path('.env'))) {
            File::copy(base_path('.env'), "{$backupPath}/.env");
            $this->line('  ✓ .env file backed up');
        }

        // Backup storage/app (user uploads, etc.)
        if (File::exists(storage_path('app'))) {
            File::copyDirectory(storage_path('app'), "{$backupPath}/storage-app");
            $this->line('  ✓ Storage directory backed up');
        }

        return $backupPath;
    }

    /**
     * Perform the actual update
     */
    private function performUpdate(): void
    {
        // Pull latest changes
        $this->line('  Pulling latest changes...');
        $result = Process::run('git pull origin main');
        if (! $result->successful()) {
            throw new \RuntimeException('Git pull failed: '.$result->errorOutput());
        }
        $this->line('  ✓ Code updated');

        // Update Composer dependencies
        $this->line('  Updating Composer dependencies...');
        $result = Process::run('composer install --no-dev --optimize-autoloader');
        if (! $result->successful()) {
            throw new \RuntimeException('Composer update failed: '.$result->errorOutput());
        }
        $this->line('  ✓ Composer dependencies updated');

        // Update bun dependencies
        if (File::exists(base_path('package.json'))) {
            $this->line('  Updating bun dependencies...');
            $result = Process::run('bun install --production');
            if (! $result->successful()) {
                throw new \RuntimeException('Bun install failed: '.$result->errorOutput());
            }
            $this->line('  ✓ bun dependencies updated');
        }

        // Run migrations
        $this->line('  Running database migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line('  ✓ Migrations completed');

        // Clear and rebuild caches
        $this->line('  Rebuilding caches...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        $this->line('  ✓ Caches rebuilt');
    }

    /**
     * Rollback from backup
     */
    private function rollback(string $backupPath): void
    {
        $this->info('Rolling back from backup...');

        // Restore database
        if (File::exists("{$backupPath}/database.sql")) {
            try {
                $dbName = config('database.connections.'.config('database.default').'.database');
                $dbUser = config('database.connections.'.config('database.default').'.username');
                $dbHost = config('database.connections.'.config('database.default').'.host');
                $dbPort = config('database.connections.'.config('database.default').'.port');

                if (config('database.default') === 'pgsql') {
                    $command = sprintf(
                        'PGPASSWORD=%s psql -h %s -p %s -U %s -d %s < %s',
                        escapeshellarg(config('database.connections.pgsql.password')),
                        escapeshellarg($dbHost),
                        escapeshellarg($dbPort),
                        escapeshellarg($dbUser),
                        escapeshellarg($dbName),
                        escapeshellarg("{$backupPath}/database.sql")
                    );

                    Process::run($command);
                    $this->line('  ✓ Database restored');
                }
            } catch (\Exception $e) {
                $this->error('  ✗ Database restore failed: '.$e->getMessage());
            }
        }

        // Restore .env
        if (File::exists("{$backupPath}/.env")) {
            File::copy("{$backupPath}/.env", base_path('.env'));
            $this->line('  ✓ .env file restored');
        }

        // Restore storage
        if (File::exists("{$backupPath}/storage-app")) {
            File::copyDirectory("{$backupPath}/storage-app", storage_path('app'));
            $this->line('  ✓ Storage directory restored');
        }

        $this->info('✓ Rollback completed');
    }
}
