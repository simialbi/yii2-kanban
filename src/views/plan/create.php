<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model Board */

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
        <?php $form = ActiveForm::begin([
            'id' => 'sa-kanban-create-plan-form'
        ]); ?>
        <?= $this->render('_form', [
            'form' => $form,
            'model' => $model
        ]); ?>
        <?= Html::submitButton(Yii::t('simialbi/kanban', 'Create'), [
            'class' => ['btn', 'btn-primary']
        ]) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
