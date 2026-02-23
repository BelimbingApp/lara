<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Services;

use App\Base\Authz\DTO\Actor;
use App\Base\Authz\DTO\AuthorizationDecision;
use App\Base\Authz\Enums\AuthorizationReasonCode;
use App\Base\Authz\Models\PrincipalCapability;
use App\Base\Authz\Models\PrincipalRole;

/**
 * Pre-loaded permission set for an actor.
 *
 * Loads all direct grants and role-derived capabilities in a fixed
 * number of queries, then evaluates checks in memory.
 */
final class EffectivePermissions
{
    /**
     * @param  array<string, true>  $directDenies  Capability keys explicitly denied
     * @param  array<string, true>  $directAllows  Capability keys explicitly allowed
     * @param  array<string, true>  $roleGrants  Capability keys granted via roles
     */
    private function __construct(
        private readonly array $directDenies,
        private readonly array $directAllows,
        private readonly array $roleGrants,
    ) {}

    /**
     * Load all effective permissions for an actor.
     *
     * Executes a fixed number of queries regardless of how many
     * capabilities or roles the actor has.
     */
    public static function forActor(Actor $actor): self
    {
        $companyScope = static function ($query) use ($actor): void {
            $query->where('company_id', $actor->companyId)
                ->orWhereNull('company_id');
        };

        $directEntries = PrincipalCapability::query()
            ->where('principal_type', $actor->type->value)
            ->where('principal_id', $actor->id)
            ->where($companyScope)
            ->get(['capability_key', 'is_allowed']);

        $directDenies = [];
        $directAllows = [];

        foreach ($directEntries as $entry) {
            if ($entry->is_allowed === false) {
                $directDenies[$entry->capability_key] = true;
            } elseif ($entry->is_allowed === true) {
                $directAllows[$entry->capability_key] = true;
            }
        }

        $roleGrantKeys = PrincipalRole::query()
            ->join(
                'base_authz_role_capabilities',
                'base_authz_role_capabilities.role_id',
                '=',
                'base_authz_principal_roles.role_id'
            )
            ->where('base_authz_principal_roles.principal_type', $actor->type->value)
            ->where('base_authz_principal_roles.principal_id', $actor->id)
            ->where(static function ($query) use ($actor): void {
                $query->where('base_authz_principal_roles.company_id', $actor->companyId)
                    ->orWhereNull('base_authz_principal_roles.company_id');
            })
            ->distinct()
            ->pluck('base_authz_role_capabilities.capability_key');

        $roleGrants = [];

        foreach ($roleGrantKeys as $key) {
            $roleGrants[$key] = true;
        }

        return new self($directDenies, $directAllows, $roleGrants);
    }

    /**
     * Evaluate whether the actor has the given capability.
     *
     * Priority: explicit deny > explicit allow > role grant > deny.
     */
    public function evaluate(string $capability): AuthorizationDecision
    {
        if (isset($this->directDenies[$capability])) {
            return AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_EXPLICITLY,
                ['direct_capability']
            );
        }

        if (isset($this->directAllows[$capability])) {
            return AuthorizationDecision::allow(['direct_capability']);
        }

        if (isset($this->roleGrants[$capability])) {
            return AuthorizationDecision::allow(['role_capability']);
        }

        return AuthorizationDecision::deny(
            AuthorizationReasonCode::DENIED_MISSING_CAPABILITY,
            ['role_capability']
        );
    }
}
