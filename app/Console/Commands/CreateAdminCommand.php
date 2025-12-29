<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong

namespace App\Console\Commands;

use App\Modules\Core\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'belimbing:create-admin
                            {email? : Admin email address}
                            {password? : Admin password (min 8 characters)}
                            {--name=Administrator : Admin display name}
                            {--force : Overwrite existing admin user if exists}
                            {--allow-after-install : Allow creating admin after installation is complete (security: use with caution)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user for Belimbing (only during installation by default)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Security check: prevent unauthorized admin creation after installation
        if (!$this->isInstallationPhase() && !$this->option('allow-after-install')) {
            $this->error('Security: This command can only be run during installation.');
            $this->newLine();
            $this->line('The application appears to be already installed.');
            $this->line('If you need to create an admin user after installation, use:');
            $this->line('  <comment>php artisan belimbing:create-admin --allow-after-install</comment>');
            $this->newLine();
            $this->warn('Only use --allow-after-install if you have legitimate administrative access.');
            return Command::FAILURE;
        }

        $this->info('Belimbing Admin User Setup');
        $this->newLine();

        // Get email
        $email = $this->argument('email');
        if (!$email) {
            $email = $this->askForEmail();
            if (!$email) {
                return Command::FAILURE;
            }
        }

        // Validate email format
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email format: ' . $email);
            return Command::FAILURE;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if (!$this->option('force')) {
                $this->warn("User with email '{$email}' already exists.");
                if (!$this->confirm('Do you want to update the password for this user?', false)) {
                    $this->info('Operation cancelled.');
                    return Command::SUCCESS;
                }
            }
        }

        // Get password
        $password = $this->argument('password');
        if (!$password) {
            $password = $this->askForPassword();
            if (!$password) {
                return Command::FAILURE;
            }
        }

        // Validate password
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return Command::FAILURE;
        }

        // Get name
        $name = $this->option('name');

        // Create or update user
        try {
            if ($existingUser) {
                $existingUser->update([
                    'password' => $password, // Will be hashed by cast
                ]);
                $this->info("✓ Password updated for admin user: {$email}");
            } else {
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password, // Will be hashed by cast
                    'email_verified_at' => now(),
                ]);
                $this->info("✓ Admin user created: {$email}");
            }

            $this->newLine();
            $this->line('You can now log in with these credentials.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Interactively ask for email address
     */
    private function askForEmail(): ?string
    {
        // Check environment variable first (for non-interactive mode)
        $envEmail = env('ADMIN_EMAIL');
        if ($envEmail) {
            $this->line("Using email from environment: {$envEmail}");
            return $envEmail;
        }

        // Interactive prompt
        if (!$this->input->isInteractive()) {
            $this->error('Email is required. Provide it as an argument or set ADMIN_EMAIL environment variable.');
            return null;
        }

        $email = $this->ask('Enter admin email address');
        if (!$email) {
            $this->error('Email is required.');
            return null;
        }

        return $email;
    }

    /**
     * Interactively ask for password
     */
    private function askForPassword(): ?string
    {
        // Check environment variable first (for non-interactive mode)
        $envPassword = env('ADMIN_PASSWORD');
        if ($envPassword) {
            $this->line('Using password from environment variable.');
            return $envPassword;
        }

        // Interactive prompt
        if (!$this->input->isInteractive()) {
            $this->error('Password is required. Provide it as an argument or set ADMIN_PASSWORD environment variable.');
            return null;
        }

        $password = $this->secret('Enter admin password (min 8 characters)');
        if (!$password) {
            $this->error('Password is required.');
            return null;
        }

        $confirmPassword = $this->secret('Confirm password');
        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match.');
            return null;
        }

        return $password;
    }

    /**
     * Check if application is in installation phase
     * Returns true if installation is not complete (safe to create admin)
     */
    private function isInstallationPhase(): bool
    {
        // Check if .env exists
        if (!file_exists(base_path('.env'))) {
            return true; // Not installed yet
        }

        // Check if APP_KEY is set
        $envContent = file_get_contents(base_path('.env'));
        if (!preg_match('/^APP_KEY=base64:.+$/m', $envContent)) {
            return true; // Installation not complete
        }

        // Check if database migrations have run (users table exists)
        try {
            if (\Illuminate\Support\Facades\DB::connection()->getPdo()) {
                $hasUsersTable = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasTable('users');
                if (!$hasUsersTable) {
                    return true; // Migrations not run yet
                }

                // If users table exists but no users, still in installation phase
                $userCount = User::count();
                if ($userCount === 0) {
                    return true; // No users yet, still installing
                }
            }
        } catch (\Exception $e) {
            // Database not configured yet, still in installation phase
            return true;
        }

        // Application appears to be fully installed
        return false;
    }
}

