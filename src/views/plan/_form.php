<?php

/* @var $this \yii\web\View */
/* @var $form \yii\bootstrap4\ActiveForm */
/* @var $model \simialbi\yii2\kanban\models\Board */

?>
<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'uploadedFile')->fileInput(); ?>
<?= $form->field($model, 'is_public')->checkbox(); ?>
