<?php

use Database\BaseMigration;

return
    [
        'paths' => [
            'migrations' => __DIR__ . '/Migrations',
            'seeds' => __DIR__ . '/Seeds'
        ],
        'environments' => [
            'default_migration_table' => 'migration',
            'default_environment' => 'live',
            'live' => [
                'adapter' => 'mysql',
                'name' => getenv('DB_NAME'),
                'host' => getenv('DB_HOST'),
                'user' => getenv('DB_USER'),
                'pass' => getenv('DB_PASS'),
                'port' => getenv('DB_PORT'),
                'charset' => 'utf8',
            ]
        ],
        'foreign_keys' => true,
        'mark_generated_migration' => true,
        'version_order' => 'creation',
        'migration_base_class' => BaseMigration::class,
    ];
