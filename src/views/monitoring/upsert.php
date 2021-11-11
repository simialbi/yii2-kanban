<?php

use kartik\select2\Select2;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\helpers\ReplaceArrayValue;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\MonitoringForm */
/* @var $users \simialbi\yii2\models\UserInterface[] */

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
                'class' => ['sr-only']
            ],
            'inputOptions' => [
                'class' => new ReplaceArrayValue(['form-control']),
                'placeholder' => $model->getAttributeLabel('name')
            ]
        ])->textInput(); ?>
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
