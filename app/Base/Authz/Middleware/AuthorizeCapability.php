<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Middleware;

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Exceptions\AuthorizationDeniedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCapability
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    /**
     * Authorize request by required capability.
     */
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $companyId = $user->getAttribute('company_id');

        $actor = new Actor(
            type: 'human_user',
            id: (int) $user->getAuthIdentifier(),
            companyId: $companyId !== null ? (int) $companyId : null,
        );

        try {
            $this->authorizationService->authorize($actor, $capability, context: [
                'route' => (string) $request->route()?->getName(),
            ]);
        } catch (AuthorizationDeniedException) {
            abort(403);
        }

        return $next($request);
    }
}
