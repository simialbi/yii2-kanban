<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\ChecklistTemplate;
use yii\bootstrap5\ActiveForm;
use yii\web\View;
use yii\widgets\Pjax;

/* @var $this View */
/* @var $form ActiveForm */
/* @var $model ChecklistTemplate */
/* @var $title string */

Pjax::begin([
    'id' => 'createChecklistTemplatePjax',
    'formSelector' => '#checklistTemplateForm',
    'enablePushState' => false,
]);
    $form = ActiveForm::begin([
        'id' => 'checklistTemplateForm',
    ]);
        ?>
        <div class="modal-header">
            <h5 class="modal-title"><?= $title ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <?= $form->field($model, 'name')->textInput(); ?>
        </div>
        <div class="modal-footer">
            <?= Html::submitButton(Yii::t('hq-base', 'Save'), [
                'class' => ['btn', 'btn-success'],
            ]) ?>
        </div>
        <?php
    ActiveForm::end();
Pjax::end();
?>
