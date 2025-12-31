<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'belimbing:backup
                            {--type=full : Backup type: full, database, files, config}
                            {--destination= : Custom backup destination path}
                            {--compress : Compress backup files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create backup of Belimbing application (database, files, configuration)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Belimbing Backup System');
        $this->newLine();

        $type = $this->option('type');
        $destination = $this->option('destination');
        $compress = $this->option('compress');

        // Determine backup destination
        if ($destination) {
            $backupPath = $destination;
        } else {
            $backupDir = storage_path('app/backups');
            File::ensureDirectoryExists($backupDir);
            $timestamp = date('Y-m-d_His');
            $backupPath = "{$backupDir}/backup-{$timestamp}";
        }

        File::ensureDirectoryExists($backupPath);

        $this->info("Backup destination: {$backupPath}");
        $this->newLine();

        $success = true;

        // Backup based on type
        switch ($type) {
            case 'database':
                $success = $this->backupDatabase($backupPath) && $success;
                break;
            case 'files':
                $success = $this->backupFiles($backupPath) && $success;
                break;
            case 'config':
                $success = $this->backupConfig($backupPath) && $success;
                break;
            case 'full':
            default:
                $success = $this->backupDatabase($backupPath) && $success;
                $success = $this->backupFiles($backupPath) && $success;
                $success = $this->backupConfig($backupPath) && $success;
                break;
        }

        // Compress if requested
        if ($compress && $success) {
            $this->newLine();
            $this->info('Compressing backup...');
            $this->compressBackup($backupPath);
        }

        if ($success) {
            $this->newLine();
            $this->info("✓ Backup completed successfully!");
            $this->line("Location: {$backupPath}");
            return Command::SUCCESS;
        } else {
            $this->newLine();
            $this->error('✗ Backup completed with errors');
            return Command::FAILURE;
        }
    }

    /**
     * Backup database
     *
     * Creates a full PostgreSQL dump of all tables, data, and schema.
     * This is essential even if code is in git, as git doesn't track database state.
     */
    private function backupDatabase(string $backupPath): bool
    {
        $this->info('Backing up database...');

        try {
            $dbName = config('database.connections.' . config('database.default') . '.database');
            $dbUser = config('database.connections.' . config('database.default') . '.username');
            $dbHost = config('database.connections.' . config('database.default') . '.host');
            $dbPort = config('database.connections.' . config('database.default') . '.port');

            $dumpFile = "{$backupPath}/database.sql";

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

                $result = Process::run($command);
                if ($result->successful()) {
                    $this->line("  ✓ Database backup created: " . File::size($dumpFile) . " bytes");
                    return true;
                } else {
                    $this->error("  ✗ Database backup failed: " . $result->errorOutput());
                    return false;
                }
            } else {
                $this->warn("  ⚠ Database type not supported for backup");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Database backup failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Backup files (storage, uploads, etc.)
     *
     * Backs up user-generated content that isn't in git:
     * - User uploads (documents, images, etc.)
     * - Application logs
     * - Generated files and cache
     * - Session files
     *
     * Git tracks code, but not runtime/user data - this backup protects business-critical files.
     * Only backs up files that have changed since the last backup (delta-based).
     */
    private function backupFiles(string $backupPath): bool
    {
        $this->info('Backing up files...');

        try {
            // Find the most recent previous backup for delta comparison
            $previousBackup = $this->findPreviousBackup(dirname($backupPath));

            // Backup storage/app (user uploads, backups, etc.)
            if (File::exists(storage_path('app'))) {
                $targetPath = "{$backupPath}/storage-app";
                if ($previousBackup && File::exists("{$previousBackup}/storage-app")) {
                    $this->backupDirectoryIncremental(
                        storage_path('app'),
                        $targetPath,
                        "{$previousBackup}/storage-app"
                    );
                } else {
                    // First backup or no previous backup - do full copy (excluding backups dir)
                    $this->copyDirectoryExcluding(storage_path('app'), $targetPath, ['backups']);
                }
                $this->line("  ✓ Storage directory backed up");
            }

            // Backup public storage if it exists
            if (File::exists(public_path('storage'))) {
                $targetPath = "{$backupPath}/public-storage";
                if ($previousBackup && File::exists("{$previousBackup}/public-storage")) {
                    $this->backupDirectoryIncremental(
                        public_path('storage'),
                        $targetPath,
                        "{$previousBackup}/public-storage"
                    );
                } else {
                    // First backup or no previous backup - do full copy
                    File::copyDirectory(public_path('storage'), $targetPath);
                }
                $this->line("  ✓ Public storage backed up");
            }

            return true;
        } catch (\Exception $e) {
            $this->error("  ✗ Files backup failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find the most recent previous backup directory
     */
    private function findPreviousBackup(string $backupDir): ?string
    {
        if (!File::exists($backupDir)) {
            return null;
        }

        $directories = glob("{$backupDir}/backup-*", GLOB_ONLYDIR);
        if (empty($directories)) {
            return null;
        }

        // Sort by modification time, most recent first
        usort($directories, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $directories[0];
    }

    /**
     * Backup directory incrementally - only copy files that have changed
     * Uses rsync if available (with hardlink deduplication), otherwise uses PHP-based comparison
     */
    private function backupDirectoryIncremental(string $source, string $target, string $previousBackup): void
    {
        // Exclude backups directory to prevent recursion
        $excludeBackups = '--exclude=backups';

        // Try rsync first (most efficient with hardlink deduplication)
        $rsyncResult = Process::run(sprintf(
            'rsync -a %s --link-dest=%s %s %s 2>&1',
            $excludeBackups,
            escapeshellarg($previousBackup),
            escapeshellarg(rtrim($source, '/') . '/'),
            escapeshellarg($target)
        ));

        if ($rsyncResult->successful()) {
            return; // rsync succeeded
        }

        // Fall back to PHP-based incremental backup
        $this->backupDirectoryIncrementalPhp($source, $target, $previousBackup);
    }

    /**
     * PHP-based incremental backup - only copy files that are new or modified
     */
    private function backupDirectoryIncrementalPhp(string $source, string $target, string $previousBackup): void
    {
        File::ensureDirectoryExists($target);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $copiedCount = 0;
        $skippedCount = 0;
        $backupsDirName = 'backups';

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $sourcePath);

            // Skip backups directory to prevent recursion
            if (strpos($relativePath, $backupsDirName . DIRECTORY_SEPARATOR) === 0 ||
                $relativePath === $backupsDirName) {
                continue;
            }

            $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;
            $previousPath = $previousBackup . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                File::ensureDirectoryExists($targetPath);
                continue;
            }

            // Check if file needs to be copied
            $needsCopy = true;
            if (File::exists($previousPath)) {
                // File exists in previous backup - check if it changed
                $sourceMtime = filemtime($sourcePath);
                $previousMtime = filemtime($previousPath);
                $sourceSize = filesize($sourcePath);
                $previousSize = filesize($previousPath);

                // Only copy if modified time or size changed
                if ($sourceMtime === $previousMtime && $sourceSize === $previousSize) {
                    // Create hardlink to previous backup (saves space)
                    if (@link($previousPath, $targetPath)) {
                        $needsCopy = false;
                        $skippedCount++;
                    }
                }
            }

            if ($needsCopy) {
                File::ensureDirectoryExists(dirname($targetPath));
                File::copy($sourcePath, $targetPath);
                $copiedCount++;
            }
        }

        if ($copiedCount > 0 || $skippedCount > 0) {
            $this->line(sprintf("    Copied: %d files, Linked: %d files (unchanged)", $copiedCount, $skippedCount));
        }
    }

    /**
     * Copy directory while excluding specified subdirectories
     */
    private function copyDirectoryExcluding(string $source, string $target, array $excludeDirs): void
    {
        File::ensureDirectoryExists($target);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $sourcePath);

            // Skip excluded directories
            $shouldExclude = false;
            foreach ($excludeDirs as $excludeDir) {
                if (strpos($relativePath, $excludeDir . DIRECTORY_SEPARATOR) === 0 ||
                    $relativePath === $excludeDir) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                File::ensureDirectoryExists($targetPath);
            } else {
                File::ensureDirectoryExists(dirname($targetPath));
                File::copy($sourcePath, $targetPath);
            }
        }
    }

    /**
     * Backup configuration
     *
     * Backs up environment variables (.env) and configuration files.
     * .env contains secrets and is git-ignored, so it must be backed up separately.
     * Config files may have customizations not in version control.
     */
    private function backupConfig(string $backupPath): bool
    {
        $this->info('Backing up configuration...');

        try {
            // Backup .env file
            if (File::exists(base_path('.env'))) {
                File::copy(base_path('.env'), "{$backupPath}/.env");
                $this->line("  ✓ .env file backed up");
            }

            // Backup config directory (if custom configs exist)
            if (File::exists(config_path())) {
                File::copyDirectory(config_path(), "{$backupPath}/config");
                $this->line("  ✓ Config directory backed up");
            }

            return true;
        } catch (\Exception $e) {
            $this->error("  ✗ Configuration backup failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compress backup directory
     */
    private function compressBackup(string $backupPath): void
    {
        $parentDir = dirname($backupPath);
        $backupName = basename($backupPath);
        $archivePath = "{$parentDir}/{$backupName}.tar.gz";

        $result = Process::run("tar -czf " . escapeshellarg($archivePath) . " -C " . escapeshellarg($parentDir) . " " . escapeshellarg($backupName));

        if ($result->successful()) {
            $this->line("  ✓ Backup compressed: {$archivePath}");
            $this->line("  Size: " . File::size($archivePath) . " bytes");

            // Optionally remove uncompressed directory
            if ($this->confirm('Remove uncompressed backup directory?', false)) {
                File::deleteDirectory($backupPath);
                $this->line("  ✓ Uncompressed directory removed");
            }
        } else {
            $this->warn("  ⚠ Compression failed: " . $result->errorOutput());
        }
    }
}
