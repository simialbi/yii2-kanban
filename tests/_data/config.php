<?php

use yii\base\InvalidConfigException;

if (!getenv('YII2_BASE_PATH')) {
    throw new InvalidConfigException('Missing environmentvariable YII2_BASE_PATH');
}

return [
    'id' => 'testapp',
    'language' => 'de-CH',
    'sourceLanguage' => 'en-US',
    'basePath' => dirname(__DIR__) . getenv('YII2_BASE_PATH'),
    'vendorPath' => VENDOR_PATH,
    'layout' => '', // todo
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'bootstrap' => ['kanban'],
    'modules' => [
        'gridview' => [
            'class' => 'kartik\grid\Module',
            'exportEncryptSalt' => 'ror_HTbRh0Ad7K7DqhAtZOp50GKyia4c',
            'i18n' => [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@kvgrid/messages',
                'forceTranslation' => true
            ]
        ],
        'kanban' => [
            'class' => '\simialbi\yii2\kanban\Module'
        ]
    ],
    'components' => [
        'assetManager' => [
            'basePath' => dirname(__DIR__) . '/_output',
            'baseUrl' => 'http://127.0.0.1',
            'bundles' => [
                'rmrevin\yii\fontawesome\AssetBundle' => [
                    'js' => [
                        'js/brands.min.js',
                        'js/light.min.js',
                        'js/regular.min.js',
                        'js/solid.min.js',
                        'js/fontawesome.min.js'
                    ]
                ],
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => []
                ],
                'yii\jui\JuiAsset' => [
                    'css' => [],
                    'js' => [
                        'ui/data.js',
                        'ui/scroll-parent.js',
                        'ui/widget.js',
                        'ui/widgets/mouse.js',
                        'ui/widgets/sortable.js',
                    ],
                    'publishOptions' => [
                        'only' => [
                            'ui/*',
                            'ui/widgets/*'
                        ]
                    ]
                ]
            ]
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager'
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
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'dd.MM.yyyy',
            'datetimeFormat' => 'dd.MM.yyyy HH:mm:ss',
            'decimalSeparator' => '.',
            'thousandSeparator' => '\'',
            'currencyCode' => 'CHF',
            'defaultTimeZone' => 'Europe/Zurich'
        ],
        'request' => [
            'cookieValidationKey' => 'FeVWXG3y1casdJGdGbQacuQt6ZBHLk3W',
            'enableCsrfValidation' => false
        ],
        'user' => [ // todo

        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'baseUrl' => '/',
            'enablePrettyUrl' => true,
            'showScriptName' => false
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => dirname(__DIR__) . '/_output/cache'
        ],
        'params' => [
            'bsVersion' => 5
        ]
    ]
];
