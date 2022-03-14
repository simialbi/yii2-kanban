<?php
// ensure we get report on all possible php errors
error_reporting(-1);

const YII_ENABLE_ERROR_HANDLER = false;
const YII_DEBUG = true;
const YII_ENV = 'test';

//$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
//$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@simialbi/extensions/kanban', __DIR__);
Yii::setAlias('@simialbi/yii2/kanban', dirname(__DIR__) . '/src');
