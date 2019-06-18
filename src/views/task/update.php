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
                    'user' => $model->updater->name
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
                'options' => [
                    'autocomplete' => 'off'
                ],
                'pluginOptions' => [
                    'autoclose' => true,
                    'startDate' => Yii::$app->formatter->asDate('now')
                ]
            ]); ?>
            <?= $form->field($model, 'end_date', [
                'options' => [
                    'class' => ['form-group', 'col-6', 'col-md-3']
                ]
            ])->widget(DatePicker::class, [
                'bsVersion' => '4',
                'type' => DatePicker::TYPE_INPUT,
                'options' => [
                    'autocomplete' => 'off'
                ],
                'pluginOptions' => [
                    'autoclose' => true,
                    'startDate' => Yii::$app->formatter->asDate('now')
                ]
            ]); ?>
        </div>
        <div class="row">
            <?php $showDescription = $form->field($model, 'card_show_description', [
                'options' => ['class' => ''],
                'labelOptions' => [
                    'class' => 'custom-control-label'
                ],
                'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
            ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
            <?= $form->field($model, 'description', [
                'template' => "<div class=\"d-flex justify-content-between\">{label}$showDescription</div>\n{input}\n{hint}\n{error}",
                'options' => [
                    'class' => ['form-group', 'col-12']
                ]
            ])->textarea();?>
        </div>
        <div class="row">
            <div class="form-group col-12 checklist">
                <div class="d-flex justify-content-between">
                    <?= Html::label(Yii::t('simialbi/kanban/task', 'Checklist')); ?>
                    <?= $form->field($model, 'card_show_checklist', [
                        'options' => ['class' => ''],
                        'labelOptions' => [
                            'class' => 'custom-control-label'
                        ],
                        'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                    ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
                </div>
                <?php foreach ($model->checklistElements as $checklistElement): ?>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <?= Html::hiddenInput('checklist[' . $checklistElement->id . '][is_done]', 0); ?>
                                <?= Html::checkbox(
                                    'checklist[' . $checklistElement->id . '][is_done]',
                                    $checklistElement->is_done
                                ); ?>
                            </div>
                        </div>
                        <?= Html::input(
                            'text',
                            'checklist[' . $checklistElement->id . '][name]',
                            Html::encode($checklistElement->name),
                            [
                                'class' => ['form-control'],
                                'style' => [
                                    'text-decoration' => $checklistElement->is_done ? 'line-through' : 'none'
                                ]
                            ]
                        ); ?>
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger remove-checklist-element">
                                <?= FAS::i('trash-alt'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="input-group add-checklist-element mb-3">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <?= Html::checkbox('checklist[new][][is_done]', false); ?>
                        </div>
                    </div>
                    <?= Html::input('text', 'checklist[new][][name]', null, [
                        'class' => ['form-control']
                    ]); ?>
                </div>
            </div>
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
