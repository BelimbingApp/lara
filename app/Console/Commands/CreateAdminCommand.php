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
                            {password? : Admin password (min 8 characters) - WARNING: visible in process list, use --stdin for security}
                            {--name=Administrator : Admin display name}
                            {--stdin : Read password from STDIN (secure for scripting)}';

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
        // Security: Only create admin if users table is empty (installation phase)
        // This prevents unauthorized admin creation after installation
        try {
            $userCount = User::count();
            if ($userCount > 0) {
                $this->info("✓ Admin user already exists ({$userCount} user(s) found).");
                $this->line('  This command only creates the initial admin during installation.');
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            // Database not ready (migrations not run) - allow command to proceed
            // It will fail later with a more specific error if needed
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

        // Get password
        $password = null;

        // Option 1: Read from STDIN (most secure for scripting)
        if ($this->option('stdin')) {
            $password = $this->readPasswordFromStdin();
            if (!$password) {
                $this->error('Failed to read password from STDIN.');
                return Command::FAILURE;
            }
        }
        // Option 2: CLI argument (insecure but convenient for quick testing)
        elseif ($this->argument('password')) {
            $password = $this->argument('password');
            $this->warn('⚠️  Password provided as CLI argument - visible in process list. Use --stdin for production.');
        }
        // Option 3: Interactive prompt (secure)
        else {
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

        // Create admin user
        try {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password, // Will be hashed by cast
                'email_verified_at' => now(),
            ]);
            $this->info("✓ Admin user created: {$email}");
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
        // Interactive prompt only (email treated as sensitive to prevent enumeration)
        if (!$this->input->isInteractive()) {
            $this->error('Email is required. Provide it as an argument or run interactively.');
            $this->line('  Example: php artisan belimbing:create-admin email@example.com --stdin');
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
     * Read password from STDIN (secure for non-interactive use)
     */
    private function readPasswordFromStdin(): ?string
    {
        $password = trim(fgets(STDIN));
        if (empty($password)) {
            return null;
        }
        return $password;
    }

    /**
     * Interactively ask for password
     */
    private function askForPassword(): ?string
    {
        // Interactive prompt only
        if (!$this->input->isInteractive()) {
            $this->error('Password is required. Provide it as an argument, use --stdin, or run interactively.');
            $this->line('  Examples:');
            $this->line('    php artisan belimbing:create-admin email@example.com "password"');
            $this->line('    echo "password" | php artisan belimbing:create-admin email@example.com --stdin');
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
}

