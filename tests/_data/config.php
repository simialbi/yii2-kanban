<?php

return [
    'id' => 'kanban-tests',
    'basePath' => __DIR__,
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'language' => 'de-CH',
    'sourceLanguage' => 'en-US',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset'
    ],
    'modules' => [
        'kanban' => [
            'class' => '\simialbi\yii2\kanban\Module'
        ]
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php'
        ],
        'users' => [
            'class' => '\yii\web\User',
            'identityClass' => '\simialbi\extensions\kanban\models\User'
        ],
        'db' => [
            'class' => '\yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('TEST_MYSQL_HOST') . ';dbname=' . getenv('TEST_MYSQL_DB'),
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'tablePrefix' => 'test_',
            'enableSchemaCache' => false,
            'enableQueryCache' => false
        ],
        'mailer' => [
            'class' => '\yii\symfonymailer\Mailer',
            'htmlLayout' => '',
            'textLayout' => '',
            'useFileTransport' => true
        ]
    ]
];