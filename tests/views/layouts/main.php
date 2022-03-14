<?php

use yii\helpers\Html;

/** @var $this \yii\web\View */
/** @var $content string */

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language; ?>">
<head>
    <meta charset="<?= Yii::$app->charset; ?>">
    <?= Html::csrfMetaTags(); ?>
    <title><?= $this->title; ?></title>
    <?php $this->head(); ?>
</head>
<body>
<?php $this->beginBody(); ?>
<?= $content; ?>
<?php $this->endBody(); ?>
</body>
</html>
<?php
$this->endPage();
