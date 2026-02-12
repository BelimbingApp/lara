<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [
    'items' => [
        [
            'id' => 'admin.geonames',
            'label' => 'Geonames',
            'icon' => 'heroicon-o-globe-alt',
            'parent' => 'admin',
            'position' => 200,
        ],
        [
            'id' => 'admin.geonames.countries',
            'label' => 'Countries',
            'icon' => 'heroicon-o-flag',
            'route' => 'admin.geonames.countries.index',
            'parent' => 'admin.geonames',
            'position' => 10,
        ],
        [
            'id' => 'admin.geonames.admin1',
            'label' => 'Admin1 Divisions',
            'icon' => 'heroicon-o-map',
            'route' => 'admin.geonames.admin1.index',
            'parent' => 'admin.geonames',
            'position' => 15,
        ],
        [
            'id' => 'admin.geonames.postcodes',
            'label' => 'Postcodes',
            'icon' => 'heroicon-o-map-pin',
            'route' => 'admin.geonames.postcodes.index',
            'parent' => 'admin.geonames',
            'position' => 20,
        ],
    ],
];
