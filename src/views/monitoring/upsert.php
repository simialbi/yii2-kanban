<?php

use kartik\select2\Select2;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\MonitoringForm;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ReplaceArrayValue;
use yii\web\View;

/* @var $this View */
/* @var $model MonitoringForm */
/* @var $users UserInterface[] */

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
?>
<div class="kanban-monitoring-modal">
    <?php $form = ActiveForm::begin([
        'id' => 'monitoringModalForm',
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
        <?= $form->field($model, 'name', [
            'options' => [
                'class' => ['my-0', 'w-100']
            ],
            'labelOptions' => [
                'class' => ['visually-hidden']
            ],
            'inputOptions' => [
                'class' => new ReplaceArrayValue(['form-control']),
                'placeholder' => $model->getAttributeLabel('name')
            ]
        ])->textInput(); ?>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <?= $form->field($model, 'members')->widget(Select2::class, [
                    'data' => $users,
                    'theme' => Select2::THEME_BOOTSTRAP,
                    'options' => [
                        'multiple' => true,
                        'placeholder' => ''
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ]
                ]); ?>
            </div>
        </div>
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
