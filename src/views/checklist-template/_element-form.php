<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\ChecklistTemplateElement;
use yii\bootstrap5\ActiveForm;
use yii\web\View;
use yii\widgets\MaskedInput;
use yii\widgets\Pjax;

/* @var $this View */
/* @var $model ChecklistTemplateElement */
/* @var $title string */

Pjax::begin([
    'id' => 'createChecklistTemplateElementPjax',
    'formSelector' => '#checklistTemplateElementForm',
    'enablePushState' => false,
    'timeout' => 3000
]);
    $form = ActiveForm::begin([
        'id' => 'checklistTemplateElementForm',
    ]);
        ?>
        <div class="modal-header">
            <h5 class="modal-title"><?= $title ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <?= $form->field($model, 'name')->textInput(); ?>
            <?= $form->field($model, 'dateOffset', [
                'template' => '{label}
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">' . FAS::i('plus') . '</span>
                        {input}
                        <span class="input-group-text">' . Yii::t('simialbi/kanban/checklist-template', 'Day(s)') . '</span>
                        {error}
                    </div>
                    {hint}',
            ])->widget(MaskedInput::class, [
                'clientOptions' => [
                    'alias' => 'numeric',
                    'digits' => 0,
                    'min' => 0,
                    'max' => 365,
                ]
            ]); ?>
        </div>
        <div class="modal-footer">
            <?= Html::submitButton(Yii::t('hq-base', 'Save'), [
                'class' => ['btn', 'btn-success'],
            ]) ?>
        </div>
        <?php
    ActiveForm::end();
Pjax::end();
