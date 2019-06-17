<?php

use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */

$this->title = Yii::t('simialbi/kanban/plan', 'Create plan')
?>

<div class="kanban-plan-create">
    <h1><?= Html::encode($this->title); ?></h1>

    <div class="mt-3">
        <?php $form = ActiveForm::begin([]); ?>
        <?= $form->field($model, 'name')->textInput(); ?>
        <?= $form->field($model, 'image')->fileInput(); ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('simialbi/kanban', 'Create'), [
                'class' => ['btn', 'btn-primary']
            ]) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
