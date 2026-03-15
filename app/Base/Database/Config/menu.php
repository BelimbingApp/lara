<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    'items' => [
        [
            'id' => 'system.tables',
            'label' => 'Database Tables',
            'icon' => 'heroicon-o-table-cells',
            'route' => 'admin.system.tables.index',
            'permission' => 'admin.system_table.list',
            'parent' => 'system',
            'position' => 10,
        ],
        [
            'id' => 'system.db-views',
            'label' => 'DB Views',
            'icon' => 'heroicon-o-circle-stack',
            'route' => 'admin.system.db-views.index',
            'permission' => 'admin.system_table.list',
            'parent' => 'system',
            'position' => 11,
        ],
    ],
];
