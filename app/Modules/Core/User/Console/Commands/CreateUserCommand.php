<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Console\Commands;

use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Create a user (email, password, optional name, company, and role).
 *
 * Used by setup scripts for the first admin (e.g. --role=core_admin) or to create
 * any user. Password can be passed via STDIN for non-interactive use.
 */
#[AsCommand(name: 'blb:user:create')]
class CreateUserCommand extends Command
{
    protected $description = 'Create a user (email, password, optional name, company, role). Use --stdin for scripted password.';

    protected $signature = 'blb:user:create
                            {email : User email address}
                            {--stdin : Read password from STDIN (for scripts)}
                            {--name= : Display name (default: derived from email)}
                            {--company=1 : Company ID (default: licensee)}
                            {--role= : System role code to assign (e.g. core_admin)}';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (! is_string($email) || $email === '') {
            $this->components->error('Email is required.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        $companyId = (int) $this->option('company');
        $company = Company::query()->find($companyId);
        if ($company === null) {
            $this->components->error("Company with id [{$companyId}] does not exist.");

            return self::FAILURE;
        }

        $password = $this->option('stdin')
            ? trim((string) file_get_contents('php://stdin'))
            : $this->secret('Password (min 8 chars)');

        if (strlen($password) < 8) {
            $this->components->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $name = $this->option('name');
        if (! is_string($name) || $name === '') {
            $name = $this->deriveNameFromEmail($email);
        }

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $roleCode = $this->option('role');
        if (is_string($roleCode) && $roleCode !== '') {
            $this->assignRole($user, $roleCode);
        }

        $this->components->info("User created: {$email}");

        return self::SUCCESS;
    }

    private function assignRole(User $user, string $roleCode): void
    {
        $role = Role::query()
            ->whereNull('company_id')
            ->where('is_system', true)
            ->where('code', $roleCode)
            ->first();

        if ($role === null) {
            $this->components->warn("System role [{$roleCode}] not found; user created without role assignment.");

            return;
        }

        PrincipalRole::query()->firstOrCreate([
            'company_id' => $user->company_id,
            'principal_type' => PrincipalType::HUMAN_USER->value,
            'principal_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    private function deriveNameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true);

        if ($local === false || $local === '') {
            return 'User';
        }

        return ucfirst(str_replace(['.', '_', '-'], ' ', $local));
    }
}
