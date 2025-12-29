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
     */
    private function backupFiles(string $backupPath): bool
    {
        $this->info('Backing up files...');

        try {
            // Backup storage/app (user uploads, backups, etc.)
            if (File::exists(storage_path('app'))) {
                File::copyDirectory(storage_path('app'), "{$backupPath}/storage-app");
                $this->line("  ✓ Storage directory backed up");
            }

            // Backup public storage if it exists
            if (File::exists(public_path('storage'))) {
                File::copyDirectory(public_path('storage'), "{$backupPath}/public-storage");
                $this->line("  ✓ Public storage backed up");
            }

            return true;
        } catch (\Exception $e) {
            $this->error("  ✗ Files backup failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Backup configuration
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
