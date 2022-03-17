<?php

use marqu3s\summernote\Summernote;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\helpers\ReplaceArrayValue;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Comment */
/* @var $users array */

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
            <?= $form->field($model, 'text', [])->widget(Summernote::class, [
                'id' => 'taskModalSummernote-comment',
                'options' => ['form-control'],
                'clientOptions' => [
                    'styleTags' => ['p', [
                        'title' => 'blockquote',
                        'tag' => 'blockquote',
                        'className' => 'blockquote',
                        'value' => 'blockquote'
                    ], 'pre'],
                    'toolbar' => new ReplaceArrayValue([
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                        ['script', ['subscript', 'superscript']],
                        ['list', ['ol', 'ul']],
                        ['clear', ['clear']]
                    ])
                ]
            ]); ?>
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
