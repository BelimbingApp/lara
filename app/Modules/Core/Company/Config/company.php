<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

return [

    /*
    |--------------------------------------------------------------------------
    | Relationship Types
    |--------------------------------------------------------------------------
    |
    | Define the default relationship types for company relationships.
    | Each deployment can customize these types to match their business model.
    |
    | Structure:
    | - code: Unique identifier (lowercase, no spaces)
    | - name: Display name
    | - description: Human-readable description
    | - is_external: Whether this relationship type allows external portal access
    | - is_active: Whether this type is active by default
    | - metadata: Additional configuration (permissions, etc.)
    |
    */

    'relationship_types' => [
        [
            'code' => 'internal',
            'name' => 'Internal',
            'description' => 'Internal company relationship within the same group/organization',
            'is_external' => false,
            'is_active' => true,
            'metadata' => [
                'allows_data_sharing' => true,
                'default_permissions' => ['view_all', 'edit_all'],
            ],
        ],
        [
            'code' => 'customer',
            'name' => 'Customer',
            'description' => 'Customer relationship - company purchases from us',
            'is_external' => true,
            'is_active' => true,
            'metadata' => [
                'allows_data_sharing' => true,
                'default_permissions' => ['view_orders', 'view_invoices', 'view_statements'],
            ],
        ],
        [
            'code' => 'supplier',
            'name' => 'Supplier',
            'description' => 'Supplier relationship - we purchase from this company',
            'is_external' => true,
            'is_active' => true,
            'metadata' => [
                'allows_data_sharing' => true,
                'default_permissions' => ['view_purchase_orders', 'submit_invoices'],
            ],
        ],
        [
            'code' => 'partner',
            'name' => 'Partner',
            'description' => 'Business partner relationship - collaborative business relationship',
            'is_external' => true,
            'is_active' => true,
            'metadata' => [
                'allows_data_sharing' => true,
                'default_permissions' => ['view_shared_projects', 'view_shared_documents'],
            ],
        ],
        [
            'code' => 'agency',
            'name' => 'Agency',
            'description' => 'Agency relationship - company acts on our behalf (e.g., customs broker, freight forwarder)',
            'is_external' => true,
            'is_active' => true,
            'metadata' => [
                'allows_data_sharing' => true,
                'default_permissions' => ['view_shipments', 'submit_documents'],
            ],
        ],
    ],

];
