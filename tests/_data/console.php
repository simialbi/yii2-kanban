<?php
return [
    'id' => 'kanban-tests-console',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language' => 'de-CH',
    'sourceLanguage' => 'en-US',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@simialbi/yii2/kanban' => dirname(dirname(__DIR__)) . '/src',
        '@simialbi/extensions/kanban' => dirname(__DIR__)
    ],
    'modules' => [
        'kanban' => [
            'class' => '\simialbi\yii2\kanban\Module'
        ]
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationNamespaces' => [
                'simialbi\yii2\kanban\migrations',
                'simialbi\extensions\kanban\migrations'
            ],
            'migrationPath' => [
                '@yii/rbac/migrations'
            ]
        ]
    ],
    'components' => [
        'db' => [
            'class' => '\yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('TEST_MYSQL_HOST') . ';dbname=' . getenv('TEST_MYSQL_DB'),
            'username' => getenv('TEST_MYSQL_USER'),
            'password' => getenv('TEST_MYSQL_PASS'),
            'charset' => 'utf8mb4',
            'tablePrefix' => 're_',
            'enableSchemaCache' => false,
            'enableQueryCache' => false
        ],
        'authManager' => [
            'class' => '\yii\rbac\DbManager'
        ]
    ]
];
