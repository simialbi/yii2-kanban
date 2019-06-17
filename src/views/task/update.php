<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Task */
/* @var $buckets array */
/* @var $statuses array */

Pjax::begin([
    'id' => 'taskUpdatePjax',
    'formSelector' => '#taskModalForm',
    'enablePushState' => false,
    'clientOptions' => [
        'skipOuterContainers' => true
    ]
]);
?>

<div class="kanban-task-modal">
    <?php $form = ActiveForm::begin([
        'id' => 'taskModalForm'
    ]); ?>
    <div class="modal-header">
        <h5 class="modal-title">
            <?= Html::encode($model->subject); ?>
            <small class="d-block text-muted">
                <?= Yii::t('simialbi/kanban/task', 'Last modified {date,date} {date,time} by {user}', [
                    'date' => $model->updated_at,
                    'user' => $model->updater->getId()
                ]); ?>
            </small>
        </h5>
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
            <!-- Todo Assignees -->
        </div>
        <div class="row">
            <?= $form->field($model, 'bucket_id', [
                'options' => [
                    'class' => ['form-group', 'col-6', 'col-md-3']
                ]
            ])->dropDownList($buckets); ?>
            <?= $form->field($model, 'status', [
                'options' => [
                    'class' => ['form-group', 'col-6', 'col-md-3']
                ]
            ])->dropDownList($statuses); ?>
            <?= $form->field($model, 'start_date', [
                'options' => [
                    'class' => ['form-group', 'col-6', 'col-md-3']
                ]
            ])->widget(DatePicker::class, [
                'bsVersion' => '4',
                'type' => DatePicker::TYPE_INPUT,
                'pluginOptions' => [
                    'autoclose' => true
                ]
            ]); ?>
            <?= $form->field($model, 'end_date', [
                'options' => [
                    'class' => ['form-group', 'col-6', 'col-md-3']
                ]
            ])->widget(DatePicker::class, [
                'bsVersion' => '4',
                'type' => DatePicker::TYPE_INPUT,
                'pluginOptions' => [
                    'autoclose' => true
                ]
            ]); ?>
        </div>
        <div class="row">
            <?php $descriptionInline = $form->field($model, 'card_show_checklist', [
                'options' => ['class' => '']
            ])->checkbox([
                'inline' => true
            ]); ?>
            <?= $form->field($model, 'description', [
                'template' => "<div class=\"d-flex justify-content-between\">{label}$descriptionInline</div>\n{input}\n{hint}\n{error}",
                'options' => [
                    'class' => ['form-group', 'col-12']
                ]
            ])->textarea();?>
        </div>
        <div class="row">
            <!-- TODO: Checklist -->
        </div>
        <div class="row">
            <!-- TODO: Attachments -->
        </div>
        <div class="row">
            <!-- TODO: Comments -->
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
            'data' => [
                'dismiss' => 'modal'
            ],
            'aria' => [
                'label' => Yii::t('simialbi/kanban', 'Save')
            ]
        ]); ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<?php
Pjax::end();
?>
