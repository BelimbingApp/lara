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

    /*
    |--------------------------------------------------------------------------
    | Department Types
    |--------------------------------------------------------------------------
    |
    | Define the default department types available to companies.
    | Each deployment can customize these types to match their organizational structure.
    |
    | Structure:
    | - code: Unique identifier (lowercase, no spaces)
    | - name: Display name
    | - category: Classification (administrative, operational, revenue, support)
    | - description: Human-readable description
    | - is_active: Whether this type is active by default
    | - metadata: Additional configuration
    |
    */

    'department_types' => [
        [
            'code' => 'exec',
            'name' => 'Executive Office',
            'category' => 'administrative',
            'description' => 'Executive leadership and strategic management',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'hr',
            'name' => 'Human Resources',
            'category' => 'administrative',
            'description' => 'HR management, recruitment, employee relations',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'finance',
            'name' => 'Finance & Accounting',
            'category' => 'administrative',
            'description' => 'Financial management, accounting, auditing',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'legal',
            'name' => 'Legal & Compliance',
            'category' => 'administrative',
            'description' => 'Legal affairs, compliance, risk management',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'sales',
            'name' => 'Sales',
            'category' => 'revenue',
            'description' => 'Sales operations and customer acquisition',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'marketing',
            'name' => 'Marketing',
            'category' => 'revenue',
            'description' => 'Marketing, branding, and customer engagement',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'operations',
            'name' => 'Operations',
            'category' => 'operational',
            'description' => 'Day-to-day operations and process management',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'production',
            'name' => 'Production / Manufacturing',
            'category' => 'operational',
            'description' => 'Manufacturing, production, and quality control',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'it',
            'name' => 'Information Technology',
            'category' => 'support',
            'description' => 'IT infrastructure, systems, and support',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'customer_support',
            'name' => 'Customer Support',
            'category' => 'support',
            'description' => 'Customer service and technical support',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'facilities',
            'name' => 'Facilities Management',
            'category' => 'support',
            'description' => 'Facilities, maintenance, and workplace management',
            'is_active' => true,
            'metadata' => [],
        ],
        [
            'code' => 'rnd',
            'name' => 'Research & Development',
            'category' => 'operational',
            'description' => 'Research, development, and innovation',
            'is_active' => true,
            'metadata' => [],
        ],
    ],

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
