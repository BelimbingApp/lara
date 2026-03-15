<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Controllers;

use App\Base\Menu\Services\PinMetadataNormalizer;
use App\Modules\Core\User\Models\UserPin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages the authenticated user's pinned sidebar items.
 *
 * Pins are unique by normalized URL. Called from Alpine
 * components via fetch(). All endpoints return JSON and are protected
 * by the 'auth' middleware.
 */
class PinController
{
    public function __construct(
        private readonly PinMetadataNormalizer $pinMetadataNormalizer,
    ) {}

    /**
     * Toggle a pin for the current user.
     *
     * Pins are unique by normalized URL. If a pin with the same
     * normalized URL already exists, it is removed. Otherwise a
     * new pin is created.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'url' => ['required', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $urlHash = UserPin::hashUrl($request->input('url'));

        $existing = UserPin::query()
            ->where('user_id', $user->id)
            ->where('url_hash', $urlHash)
            ->first();

        if ($existing) {
            $existing->delete();
            $user->unsetRelation('pins');

            return response()->json([
                'pinned' => false,
                'pins' => $user->getPins(),
            ]);
        }

        $maxOrder =
            UserPin::query()->where('user_id', $user->id)->max('sort_order') ??
            -1;

        UserPin::query()->create([
            'user_id' => $user->id,
            'label' => $this->pinMetadataNormalizer->normalizeLabel(
                $request->input('label'),
            ),
            'url' => $request->input('url'),
            'url_hash' => $urlHash,
            'icon' => $request->input('icon'),
            'sort_order' => $maxOrder + 1,
        ]);

        $user->unsetRelation('pins');

        return response()->json([
            'pinned' => true,
            'pins' => $user->getPins(),
        ]);
    }

    /**
     * Reorder the current user's pinned items.
     *
     * Accepts an ordered array of pin IDs. Each pin's sort_order
     * is updated to match its array index.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'pins' => ['required', 'array', 'min:1'],
            'pins.*.id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $pins = $request->input('pins');

        foreach ($pins as $index => $pin) {
            UserPin::query()
                ->where('user_id', $user->id)
                ->where('id', $pin['id'])
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'pins' => $user->getPins(),
        ]);
    }
}
