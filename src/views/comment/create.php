<?php

use sandritsch91\yii2\froala\FroalaEditor;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model \simialbi\yii2\kanban\models\Comment */
/* @var $users array */

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
?>
    <div class="kanban-task-modal">
        <?php
        $form = ActiveForm::begin([
            'id' => 'sa-kanban-task-modal-form',
            'fieldConfig' => [
                'labelOptions' => [
                    'class' => ['py-0']
                ],
                'inputOptions' => [
                    'class' => ['form-control']
                ]
            ],
            'validateOnSubmit' => false,
            'options' => [
                'data' => [
                    'turbo-frame' => 'task-' . $model->task_id . '-frame'
                ]
            ]
        ]); ?>
        <div class="modal-header">
            <h5 class="modal-title"><?= Yii::t('simialbi/kanban', 'Add comment'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <?= $form->field($model, 'text', [])->widget(FroalaEditor::class); ?>
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
        <?php
        ActiveForm::end(); ?>
    </div>
<?php
Frame::end();
