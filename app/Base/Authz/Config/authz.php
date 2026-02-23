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
    ],

    // Seed pack for currently available modules; expand incrementally with real modules.
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

        'ai.personal_agent.view',
        'ai.personal_agent.execute',
    ],

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
                'ai.personal_agent.view',
                'ai.personal_agent.execute',
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
