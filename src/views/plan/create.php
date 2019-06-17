<?php

use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */

$this->title = Yii::t('simialbi/kanban/plan', 'Create plan');
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'),
        'url' => ['index']
    ],
    $this->title
];
?>

<div class="kanban-plan-create">
    <h1><?= Html::encode($this->title); ?></h1>

    <div class="mt-3">
        <?php $form = ActiveForm::begin([]); ?>
        <?= $form->field($model, 'name')->textInput(); ?>
        <?= $form->field($model, 'image')->fileInput(); ?>
        <?= $form->field($model, 'is_public')->checkbox(); ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('simialbi/kanban', 'Create'), [
                'class' => ['btn', 'btn-primary']
            ]) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
