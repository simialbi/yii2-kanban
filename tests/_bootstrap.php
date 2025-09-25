<?php
// ensure we get report on all possible php errors
error_reporting(-1);

defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('VENDOR_PATH') or define('VENDOR_PATH', __DIR__ . '/../vendor/');

//$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
//$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once(VENDOR_PATH . '/autoload.php');
require_once(VENDOR_PATH . '/yiisoft/yii2/Yii.php');

Yii::setAlias('@simialbi/extensions/kanban', __DIR__);
Yii::setAlias('@simialbi/yii2/kanban', dirname(__DIR__) . '/src');
