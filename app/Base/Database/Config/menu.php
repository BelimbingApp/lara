<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    'items' => [
        [
            'id' => 'system.migrations',
            'label' => 'Migrations',
            'icon' => 'heroicon-o-circle-stack',
            'route' => 'admin.system.migrations.index',
            'parent' => 'system',
            'position' => 10,
        ],
        [
            'id' => 'system.seeders',
            'label' => 'Database Seeders',
            'icon' => 'heroicon-o-arrow-down-on-square-stack',
            'route' => 'admin.system.seeders.index',
            'parent' => 'system',
            'position' => 20,
        ],
    ],
];
