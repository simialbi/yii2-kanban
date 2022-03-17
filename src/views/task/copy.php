<?php

use kartik\select2\Select2;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\TaskCopyForm */
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
                'class' => ['col-form-label-sm', 'py-0']
            ],
            'inputOptions' => [
                'class' => ['form-control', 'form-control-sm']
            ]
        ]
    ]); ?>
    <div class="modal-header">
        <h5 class="modal-title"><?=Yii::t('simialbi/kanban', 'Copy task');?></h5>
        <?= Html::button('<span aria-hidden="true">' . FAS::i('times') . '</span>', [
            'type' => 'button',
            'class' => ['close'],
            'data' => [
                'dismiss' => 'modal'
            ],
            'aria' => [
                'label' => Yii::t('simialbi/kanban', 'Close')
            ]
        ]); ?>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'subject')->textInput(); ?>
        <?= $form->field($model, 'bucketId')->widget(Select2::class, [
            'data' => $buckets,
            'theme' => Select2::THEME_DEFAULT,
            'size' => Select2::SIZE_SMALL,
            'pluginOptions' => [
                'allowClear' => false
            ]
        ]); ?>
        <div class="form-group">
            <label><?= Yii::t('simialbi/kanban', 'Include'); ?></label>
            <?= $form->field($model, 'copyAssignment', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
            <?= $form->field($model, 'copyStatus', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
            <?= $form->field($model, 'copyDates', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
            <?= $form->field($model, 'copyDescription', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
            <?= $form->field($model, 'copyChecklist', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
            <?= $form->field($model, 'copyAttachments', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
            <?= $form->field($model, 'copyLinks', ['options' => ['class' => ['form-group', 'mb-0']]])->checkbox(); ?>
        </div>
    </div>
    <div class="modal-footer">
        <?= Html::button(Yii::t('simialbi/kanban', 'Close'), [
            'type' => 'button',
            'class' => ['btn', 'btn-dark'],
            'data' => [
                'dismiss' => 'modal'
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
