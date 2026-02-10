<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Menu;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    /**
     * Cache key for built menu tree.
     */
    protected const CACHE_KEY = 'blb.menu.tree';

    /**
     * Build hierarchical menu tree from flat items.
     *
     * @param  Collection  $items  Flat collection of MenuItem objects
     * @param  string|null  $currentRoute  Current route name for active marking
     */
    public function build(Collection $items, ?string $currentRoute = null): array
    {
        // Build tree structure
        $tree = $this->buildTree($items, null);

        // Mark active items
        if ($currentRoute) {
            $tree = $this->markActive($tree, $currentRoute);
        }

        return $tree;
    }

    /**
     * Build tree recursively.
     *
     * @param  Collection  $items  All menu items
     * @param  string|null  $parentId  Current parent ID (null = root level)
     */
    protected function buildTree(Collection $items, ?string $parentId): array
    {
        $children = $items
            ->filter(fn(MenuItem $item) => $item->parent === $parentId)
            ->sortBy(fn(MenuItem $item) => $item->position)
            ->values();

        return $children->map(function (MenuItem $item) use ($items) {
            return [
                'item' => $item,
                'is_active' => false,
                'has_active_child' => false,
                'children' => $this->buildTree($items, $item->id),
            ];
        })->all();
    }

    /**
     * Mark active item and parent chain.
     *
     * @param  array  $tree  Menu tree
     * @param  string  $currentRoute  Current route name
     */
    protected function markActive(array $tree, string $currentRoute): array
    {
        foreach ($tree as &$node) {
            // Check if this item is active
            if ($node['item']->route === $currentRoute) {
                $node['is_active'] = true;
                return $tree;  // Found active, parent chain will be marked by caller
            }

            // Check children recursively
            if (!empty($node['children'])) {
                $node['children'] = $this->markActive($node['children'], $currentRoute);

                // If any child is active or has active child, mark this node
                foreach ($node['children'] as $child) {
                    if ($child['is_active'] || $child['has_active_child']) {
                        $node['has_active_child'] = true;
                        break;
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * Build and cache menu tree.
     *
     * @param  Collection  $items  Flat collection of MenuItem objects
     * @param  string|null  $currentRoute  Current route name
     */
    public function buildAndCache(Collection $items, ?string $currentRoute = null): array
    {
        $cacheKey = self::CACHE_KEY . ($currentRoute ? ".{$currentRoute}" : '');

        return Cache::remember($cacheKey, now()->addHour(), function () use ($items, $currentRoute) {
            return $this->build($items, $currentRoute);
        });
    }

    /**
     * Clear menu tree cache.
     */
    public function clearCache(): void
    {
        // Clear all menu tree cache keys (pattern matching not available in all cache drivers)
        // So we clear the base key; route-specific keys expire naturally
        Cache::forget(self::CACHE_KEY);
    }
}
