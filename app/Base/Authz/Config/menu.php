<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    'items' => [
        [
            'id' => 'admin.roles',
            'label' => 'Role Management',
            'icon' => 'heroicon-o-shield-check',
            'route' => 'admin.roles.index',
            'permission' => 'admin.role.list',
            'parent' => 'admin',
            'position' => 210,
        ],
    ],
];
