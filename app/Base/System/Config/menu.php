<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    'items' => [
        [
            'id' => 'system.info',
            'label' => 'System Info',
            'icon' => 'heroicon-o-information-circle',
            'route' => 'admin.system.info.index',
            'permission' => 'admin.system_info.view',
            'parent' => 'system',
            'position' => 90,
        ],
    ],
];
