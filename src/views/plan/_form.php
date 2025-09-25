<?php

use simialbi\yii2\kanban\models\Board;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $form ActiveForm */
/* @var $model Board */

?>
<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'uploadedFile')->fileInput(); ?>
<?= $form->field($model, 'is_public')->checkbox(); ?>
