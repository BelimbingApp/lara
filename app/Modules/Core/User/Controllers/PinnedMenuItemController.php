<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Controllers;

use App\Modules\Core\User\Models\UserPinnedMenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages the authenticated user's pinned sidebar menu items.
 *
 * Called from the sidebar Alpine component via fetch(). All endpoints
 * return JSON and are protected by the 'auth' middleware.
 */
class PinnedMenuItemController
{
    /**
     * Toggle a menu item pin for the current user.
     *
     * If the item is pinned, it is unpinned. If not pinned, it is added
     * at the end of the pinned list (highest sort_order + 1).
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'menu_item_id' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $menuItemId = $request->input('menu_item_id');

        $existing = UserPinnedMenuItem::query()
            ->where('user_id', $user->id)
            ->where('menu_item_id', $menuItemId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'pinned' => false,
                'pinnedIds' => $user->getPinnedMenuItemIds(),
            ]);
        }

        // Determine next sort_order (append to end)
        $maxOrder = UserPinnedMenuItem::query()
            ->where('user_id', $user->id)
            ->max('sort_order') ?? -1;

        UserPinnedMenuItem::query()->create([
            'user_id' => $user->id,
            'menu_item_id' => $menuItemId,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'pinned' => true,
            'pinnedIds' => $user->getPinnedMenuItemIds(),
        ]);
    }

    /**
     * Reorder the current user's pinned menu items.
     *
     * Accepts an ordered array of menu item IDs. Each item's sort_order
     * is updated to match its array index.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $ids = $request->input('ids');

        foreach ($ids as $index => $menuItemId) {
            UserPinnedMenuItem::query()
                ->where('user_id', $user->id)
                ->where('menu_item_id', $menuItemId)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'pinnedIds' => $user->getPinnedMenuItemIds(),
        ]);
    }
}
