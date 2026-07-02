<?php

declare(strict_types=1);

return [

    'tenant_model' => \App\Models\Tenant::class,

    'id_generator' => null,

    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,

    'base_domain' => env('TENANCY_BASE_DOMAIN', 'safeworsolutions.com'),

    'central_domains' => array_filter([
        env('TENANCY_CENTRAL_DOMAIN', 'realstate.safeworsolutions.com'),
    ]),

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => 'safewors_',
        'suffix' => '',
        'managers' => [
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => ['local', 'public'],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
    ],

    'migration_parameters' => [
        '--path' => [
            database_path('migrations/tenant'),

            base_path('platform/core/acl/database/migrations'),
            base_path('platform/core/base/database/migrations'),
            base_path('platform/core/dashboard/database/migrations'),
            base_path('platform/core/media/database/migrations'),
            base_path('platform/core/setting/database/migrations'),

            base_path('platform/packages/menu/database/migrations'),
            base_path('platform/packages/page/database/migrations'),
            base_path('platform/packages/revision/database/migrations'),
            base_path('platform/packages/slug/database/migrations'),
            base_path('platform/packages/widget/database/migrations'),

            base_path('platform/plugins/audit-log/database/migrations'),
            base_path('platform/plugins/blog/database/migrations'),
            base_path('platform/plugins/career/database/migrations'),
            base_path('platform/plugins/contact/database/migrations'),
            base_path('platform/plugins/language/database/migrations'),
            base_path('platform/plugins/language-advanced/database/migrations'),
            base_path('platform/plugins/location/database/migrations'),
            base_path('platform/plugins/payment/database/migrations'),
            base_path('platform/plugins/real-estate/database/migrations'),
            base_path('platform/plugins/translation/database/migrations'),
        ],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'TenantDatabaseSeeder',
    ],
];
