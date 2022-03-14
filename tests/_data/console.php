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
        '@simialbi/yii2/kanban' => dirname(dirname(__DIR__)) . '/src'
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
                'simialbi\yii2\kanban\migrations'
            ]
        ]
    ],
    'components' => [
        'db' => [
            'class' => '\yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('TEST_MYSQL_HOST') . ';dbname=' . getenv('TEST_MYSQL_DB'),
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'tablePrefix' => 'test_',
            'enableSchemaCache' => false,
            'enableQueryCache' => false
        ]
    ]
];
