<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    // Fallback policy when menu items define permission but no authorization adapter is installed.
    // Menu visibility is UI-only; route/middleware authorization remains authoritative.
    'permissioned_items_without_authorizer' => 'allow',

    'items' => [
        [
            'id' => 'admin',
            'label' => 'Administration',
            'icon' => 'heroicon-o-cog-6-tooth',
            'position' => 0,
        ],
        [
            'id' => 'business',
            'label' => 'Business Operations',
            'icon' => 'heroicon-o-building-office',
            'position' => 100,
        ],
    ],
];
