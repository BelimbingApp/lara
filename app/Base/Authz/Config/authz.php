<?php

return [
    'domains' => [
        'core' => 'Core platform modules',
        'workflow' => 'Workflow and state transitions',
        'ai' => 'AI and personal agent capabilities',
        'admin' => 'Administrative operations',
    ],

    'verbs' => [
        'view',
        'list',
        'create',
        'update',
        'delete',
        'submit',
        'approve',
        'reject',
        'execute',
        'impersonate',
    ],

    // Capabilities owned by the base framework (no module to host them yet).
    // Module-owned capabilities live in each module's Config/authz.php
    // and are auto-discovered by the AuthzServiceProvider.
    'capabilities' => [
        'ai.personal_agent.view',
        'ai.personal_agent.execute',
        'admin.user.impersonate',
        'admin.role.list',
        'admin.role.view',
        'admin.role.create',
        'admin.role.update',
        'admin.role.delete',
    ],

    'decision_log_retention_days' => 90,

    // System roles that aggregate capabilities across modules.
    // Module-scoped roles may also be declared in module Config/authz.php.
    'roles' => [
        'core_admin' => [
            'name' => 'Core Administrator',
            'description' => 'System role with full core and personal agent baseline capabilities.',
            'capabilities' => [
                'core.company.view',
                'core.company.list',
                'core.company.create',
                'core.company.update',
                'core.company.delete',
                'core.user.view',
                'core.user.list',
                'core.user.create',
                'core.user.update',
                'core.user.delete',
                'core.employee.view',
                'core.employee.list',
                'core.employee.create',
                'core.employee.update',
                'core.employee.delete',
                'core.address.view',
                'core.address.list',
                'core.address.create',
                'core.address.update',
                'core.address.delete',
                'core.geonames.view',
                'core.geonames.list',
                'ai.personal_agent.view',
                'ai.personal_agent.execute',
                'admin.user.impersonate',
                'admin.role.list',
                'admin.role.view',
                'admin.role.create',
                'admin.role.update',
                'admin.role.delete',
            ],
        ],
        'user_viewer' => [
            'name' => 'User Viewer',
            'description' => 'Read-only access to user management.',
            'capabilities' => [
                'core.user.list',
                'core.user.view',
            ],
        ],
        'user_editor' => [
            'name' => 'User Editor',
            'description' => 'Read-write access to user management.',
            'capabilities' => [
                'core.user.list',
                'core.user.view',
                'core.user.create',
                'core.user.update',
                'core.user.delete',
            ],
        ],
    ],
];
