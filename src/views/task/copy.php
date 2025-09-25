<?php

use kartik\select2\Select2;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\TaskCopyForm;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Alert;
use yii\web\View;

/* @var $this View */
/* @var $model TaskCopyForm */
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
        <h5 class="modal-title"><?=Yii::t('simialbi/kanban', 'Copy task');?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <?php if (count($buckets) > 0): ?>
            <?= $form->field($model, 'subject')->textInput(); ?>
            <?= $form->field($model, 'bucketId')->widget(Select2::class, [
                'data' => $buckets,
                'size' => Select2::SIZE_SMALL,
                'pluginOptions' => [
                    'allowClear' => false,
                    'dropdownParent' => Yii::createObject('\yii\web\JsExpression', ['$(".kanban-task-modal")'])
                ]
            ]); ?>
            <div class="col-12">
                <label class="form-label col-form-label-sm py-0"><?= Yii::t('simialbi/kanban', 'Include'); ?></label>
                <?= $form->field($model, 'copyAssignment', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
                <?= $form->field($model, 'copyStatus', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
                <?= $form->field($model, 'copyDates', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
                <?= $form->field($model, 'copyDescription', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
                <?= $form->field($model, 'copyChecklist', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
                <?= $form->field($model, 'copyAttachments', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
                <?= $form->field($model, 'copyLinks', ['options' => ['class' => ['mb-0']]])->checkbox(); ?>
            </div>
        <?php else: ?>
            <?= Alert::widget([
                'body' => Yii::t('simialbi/kanban', 'No buckets to copy task to'),
                'closeButton' => false,
                'options' => [
                    'class' => 'alert-warning',
                ],
            ]) ?>
        <?php endif; ?>
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
        <?php if (count($buckets) > 0): ?>
            <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
                'type' => 'button',
                'class' => ['btn', 'btn-success'],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Save')
                ]
            ]); ?>
        <?php endif; ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
<?php
Frame::end();
