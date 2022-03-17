<?php

return [
    'id' => 'kanban-tests',
    'basePath' => dirname(__DIR__),
    'runtimePath' => dirname(__DIR__) . '/_output',
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
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
        'assetManager' => [
            'basePath' => dirname(__DIR__) . '/_output',
            'baseUrl' => 'http://127.0.0.1'
        ],
        'request' => [
            'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php'
        ],
        'user' => [
            'class' => '\yii\web\User',
            'identityClass' => '\simialbi\extensions\kanban\models\User'
        ],
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
        'mailer' => [
            'class' => '\yii\symfonymailer\Mailer',
            'htmlLayout' => '',
            'textLayout' => '',
            'useFileTransport' => true
        ],
        'authManager' => [
            'class' => '\yii\rbac\DbManager',
        ]
    ],
    'params' => [
        'bsVersion' => '4.x'
    ]
];
