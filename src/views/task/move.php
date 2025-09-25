<?php

use kartik\select2\Select2;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\turbo\Frame;
use yii\base\DynamicModel;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model DynamicModel */
/* @var $buckets array */

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
?>
<div class="kanban-task-modal">
    <?php $form = ActiveForm::begin([
        'id' => 'sa-kanban-task-modal-form',
        'fieldConfig' => [
            'labelOptions' => [
                'class' => ['form-label', 'col-form-label-sm', 'py-0']
            ],
            'inputOptions' => [
                'class' => ['form-control', 'form-control-sm']
            ]
        ]
    ]); ?>
    <div class="modal-header">
        <h5 class="modal-title"><?=Yii::t('simialbi/kanban', 'Move task');?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'bucketId')->widget(Select2::class, [
            'data' => $buckets,
            'size' => Select2::SIZE_SMALL,
            'pluginOptions' => [
                'allowClear' => false,
                'dropdownParent' => Yii::createObject('\yii\web\JsExpression', ['$(".kanban-task-modal")'])
            ]
        ]); ?>
    </div>
    <div class="modal-footer">
        <?= Html::button(Yii::t('simialbi/kanban', 'Close'), [
            'type' => 'button',
            'class' => ['btn', 'btn-dark'],
            'data' => [
                'bs-dismiss' => 'modal'
            ],
            'aria' => [
                'label' => Yii::t('simialbi/kanban', 'Close')
            ]
        ]); ?>
        <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
            'type' => 'button',
            'class' => ['btn', 'btn-success'],
            'aria' => [
                'label' => Yii::t('simialbi/kanban', 'Save')
            ]
        ]); ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
<?php
Frame::end();
