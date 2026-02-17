<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    'items' => [
        [
            'id' => 'admin.companies',
            'label' => 'Companies',
            'icon' => 'heroicon-o-building-office',
            'route' => 'admin.companies.index',
            'parent' => 'admin',
            'position' => 220,
        ],
        [
            'id' => 'admin.companies.legal-entity-types',
            'label' => 'Legal Entity Types',
            'icon' => 'heroicon-o-scale',
            'route' => 'admin.companies.legal-entity-types',
            'parent' => 'admin.companies',
            'position' => 10,
        ],
    ],
];
