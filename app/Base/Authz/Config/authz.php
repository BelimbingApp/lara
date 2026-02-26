<?php

return [
    'domains' => [
        'core' => 'Core platform modules',
        'workflow' => 'Workflow and state transitions',
        'ai' => 'AI and Digital Worker capabilities',
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
    // and are auto-discovered by App\Base\Authz\ServiceProvider.
    'capabilities' => [
        'ai.digital_worker.view',
        'ai.digital_worker.execute',
        'admin.user.impersonate',
        'admin.role.list',
        'admin.role.view',
        'admin.role.create',
        'admin.role.update',
        'admin.role.delete',
        'admin.principal_role.list',
        'admin.principal_capability.list',
        'admin.decision_log.list',
    ],

    'decision_log_retention_days' => 90,

    // System roles that aggregate capabilities across modules.
    // Module-scoped roles may also be declared in module Config/authz.php.
    'roles' => [
        'core_admin' => [
            'name' => 'Core Administrator',
            'description' => 'System role with full core and Digital Worker baseline capabilities.',
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
                'core.employee_type.list',
                'core.employee_type.create',
                'core.employee_type.update',
                'core.employee_type.delete',
                'core.address.view',
                'core.address.list',
                'core.address.create',
                'core.address.update',
                'core.address.delete',
                'core.geonames.view',
                'core.geonames.list',
                'ai.digital_worker.view',
                'ai.digital_worker.execute',
                'admin.user.impersonate',
                'admin.role.list',
                'admin.role.view',
                'admin.role.create',
                'admin.role.update',
                'admin.role.delete',
                'admin.principal_role.list',
                'admin.principal_capability.list',
                'admin.decision_log.list',
                'admin.system_migration.list',
                'admin.system_seeder.list',
                'admin.system_log.list',
                'admin.system_job.list',
                'admin.system_failed_job.list',
                'admin.system_job_batch.list',
                'admin.system_scheduled_task.list',
                'admin.system_info.view',
                'admin.system_session.list',
                'admin.system_cache.view',
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
