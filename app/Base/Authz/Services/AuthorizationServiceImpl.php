<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Services;

use App\Base\Authz\Capability\CapabilityRegistry;
use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\DTO\AuthorizationDecision;
use App\Base\Authz\DTO\ResourceContext;
use App\Base\Authz\Enums\AuthorizationReasonCode;
use App\Base\Authz\Exceptions\AuthorizationDeniedException;
use App\Base\Authz\Exceptions\UnknownCapabilityException;
use App\Base\Authz\Models\DecisionLog;
use App\Base\Authz\Models\PrincipalCapability;
use App\Base\Authz\Models\PrincipalRole;
use Illuminate\Support\Collection;
use Throwable;

class AuthorizationServiceImpl implements AuthorizationService
{
    public function __construct(private readonly CapabilityRegistry $capabilityRegistry) {}

    /**
     * Evaluate whether actor can perform capability on resource.
     */
    public function can(
        Actor $actor,
        string $capability,
        ?ResourceContext $resource = null,
        array $context = []
    ): AuthorizationDecision {
        $capability = strtolower($capability);

        try {
            $actorDecision = $this->validateActor($actor);
            if (! $actorDecision->allowed) {
                return $this->persistDecision($actor, $capability, $resource, $actorDecision, $context);
            }

            try {
                $this->capabilityRegistry->assertKnown($capability);
            } catch (UnknownCapabilityException) {
                $decision = AuthorizationDecision::deny(
                    AuthorizationReasonCode::DENIED_UNKNOWN_CAPABILITY,
                    ['capability_registry']
                );

                return $this->persistDecision($actor, $capability, $resource, $decision, $context);
            }

            $scopeDecision = $this->validateCompanyScope($actor, $resource);
            if (! $scopeDecision->allowed) {
                return $this->persistDecision($actor, $capability, $resource, $scopeDecision, $context);
            }

            $grantDecision = $this->evaluateGrant($actor, $capability);

            return $this->persistDecision($actor, $capability, $resource, $grantDecision, $context);
        } catch (Throwable) {
            $decision = AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_POLICY_ENGINE_ERROR,
                ['policy_engine']
            );

            return $this->persistDecision($actor, $capability, $resource, $decision, $context);
        }
    }

    /**
     * Authorize and throw when denied.
     */
    public function authorize(
        Actor $actor,
        string $capability,
        ?ResourceContext $resource = null,
        array $context = []
    ): void {
        $decision = $this->can($actor, $capability, $resource, $context);

        if ($decision->allowed) {
            return;
        }

        throw new AuthorizationDeniedException($decision);
    }

    /**
     * Filter resources by capability.
     */
    public function filterAllowed(
        Actor $actor,
        string $capability,
        iterable $resources,
        array $context = []
    ): Collection {
        $collection = collect($resources);

        return $collection->filter(function ($resource) use ($actor, $capability, $context): bool {
            $resourceContext = $this->toResourceContext($resource);
            $decision = $this->can($actor, $capability, $resourceContext, $context);

            return $decision->allowed;
        })->values();
    }

    /**
     * Validate minimum actor context.
     */
    private function validateActor(Actor $actor): AuthorizationDecision
    {
        if ($actor->id <= 0 || $actor->companyId === null) {
            return AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_INVALID_ACTOR_CONTEXT,
                ['actor_validation']
            );
        }

        if (! in_array($actor->type, ['human_user', 'personal_agent'], true)) {
            return AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_INVALID_ACTOR_CONTEXT,
                ['actor_validation']
            );
        }

        if ($actor->isPersonalAgent() && $actor->actingForUserId === null) {
            return AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_INVALID_ACTOR_CONTEXT,
                ['actor_validation']
            );
        }

        return AuthorizationDecision::allow(['actor_validation']);
    }

    /**
     * Enforce company boundary on resource context.
     */
    private function validateCompanyScope(Actor $actor, ?ResourceContext $resource): AuthorizationDecision
    {
        if ($resource === null || $resource->companyId === null) {
            return AuthorizationDecision::allow(['company_scope']);
        }

        if ($resource->companyId !== $actor->companyId) {
            return AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_COMPANY_SCOPE,
                ['company_scope']
            );
        }

        return AuthorizationDecision::allow(['company_scope']);
    }

    /**
     * Evaluate explicit grants/denies and role-derived grants.
     */
    private function evaluateGrant(Actor $actor, string $capability): AuthorizationDecision
    {
        $principalDirect = PrincipalCapability::query()
            ->where('principal_type', $actor->type)
            ->where('principal_id', $actor->id)
            ->where(function ($query) use ($actor): void {
                $query->where('company_id', $actor->companyId)
                    ->orWhereNull('company_id');
            })
            ->whereHas('capability', function ($query) use ($capability): void {
                $query->where('key', $capability);
            })
            ->orderByDesc('company_id')
            ->get();

        if ($principalDirect->contains(fn (PrincipalCapability $row): bool => $row->is_allowed === false)) {
            return AuthorizationDecision::deny(
                AuthorizationReasonCode::DENIED_EXPLICITLY,
                ['direct_capability']
            );
        }

        if ($principalDirect->contains(fn (PrincipalCapability $row): bool => $row->is_allowed === true)) {
            return AuthorizationDecision::allow(['direct_capability']);
        }

        $roleGrantExists = PrincipalRole::query()
            ->where('principal_type', $actor->type)
            ->where('principal_id', $actor->id)
            ->where(function ($query) use ($actor): void {
                $query->where('company_id', $actor->companyId)
                    ->orWhereNull('company_id');
            })
            ->whereHas('role.capabilities', function ($query) use ($capability): void {
                $query->where('key', $capability);
            })
            ->exists();

        if ($roleGrantExists) {
            return AuthorizationDecision::allow(['role_capability']);
        }

        return AuthorizationDecision::deny(
            AuthorizationReasonCode::DENIED_MISSING_CAPABILITY,
            ['role_capability']
        );
    }

    /**
     * Convert resource to ResourceContext using convention-based extraction.
     */
    private function toResourceContext(mixed $resource): ?ResourceContext
    {
        if ($resource instanceof ResourceContext) {
            return $resource;
        }

        if (is_array($resource)) {
            return new ResourceContext(
                type: (string) ($resource['type'] ?? 'resource'),
                id: $resource['id'] ?? null,
                companyId: isset($resource['company_id']) ? (int) $resource['company_id'] : null,
                attributes: $resource,
            );
        }

        if (is_object($resource)) {
            $type = method_exists($resource, 'getTable') ? (string) $resource->getTable() : 'resource';
            $id = $resource->id ?? null;
            $companyId = isset($resource->company_id) ? (int) $resource->company_id : null;

            return new ResourceContext($type, $id, $companyId, (array) $resource);
        }

        return null;
    }

    /**
     * Persist decision for audit and return the original decision.
     */
    private function persistDecision(
        Actor $actor,
        string $capability,
        ?ResourceContext $resource,
        AuthorizationDecision $decision,
        array $context = []
    ): AuthorizationDecision {
        try {
            DecisionLog::query()->create([
                'company_id' => $actor->companyId,
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'acting_for_user_id' => $actor->actingForUserId,
                'capability' => $capability,
                'resource_type' => $resource?->type,
                'resource_id' => $resource?->id !== null ? (string) $resource?->id : null,
                'allowed' => $decision->allowed,
                'reason_code' => $decision->reasonCode->value,
                'applied_policies' => $decision->appliedPolicies,
                'context' => $context,
                'correlation_id' => isset($context['correlation_id']) ? (string) $context['correlation_id'] : null,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            logger()->error('Authorization decision log persistence failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'capability' => $capability,
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
            ]);
        }

        return $decision;
    }
}
