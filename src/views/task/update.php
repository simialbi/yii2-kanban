<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Dropdown;
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
        'id' => 'taskModalForm',
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
        <?= $form->field($model, 'subject', [
            'options' => [
                'class' => ['my-0', 'w-100']
            ],
            'labelOptions' => [
                'class' => ['sr-only']
            ],
            'inputOptions' => [
                'class' => new \yii\helpers\ReplaceArrayValue(['form-control'])
            ]
        ])->textInput()->hint(Yii::t('simialbi/kanban/task', 'Last modified {date,date} {date,time} by {user}', [
            'date' => $model->updated_at,
            'user' => $model->updater->name
        ])); ?>
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
                <div class="kanban-task-assignees">
                    <div class="dropdown">
                        <a href="javascript:;" data-toggle="dropdown"
                           class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">
                            <?php foreach ($model->assignees as $assignee): ?>
                                <span class="kanban-user" data-id="<?= $assignee->getId(); ?>">
                                    <?= Html::hiddenInput('assignees[]', $assignee->getId()); ?>
                                    <?php if ($assignee->image): ?>
                                        <?= Html::img($assignee->image, [
                                            'class' => ['rounded-circle', 'mr-1'],
                                            'title' => Html::encode($assignee->name),
                                            'data' => [
                                                'toggle' => 'tooltip'
                                            ]
                                        ]); ?>
                                    <?php else: ?>
                                        <span class="kanban-visualisation mr-1"
                                              title="<?= Html::encode($assignee->name); ?>"
                                              data-toggle="tooltip">
                                            <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </a>
                        <?php
                        $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
                        foreach ($model->board->assignees as $user) {
                            $linkOptions = [
                                'class' => ['align-items-center', 'd-none', 'remove-assignee'],
                                'onclick' => sprintf(
                                    'window.sa.kanban.removeAssignee.call(this, %u);',
                                    $user->getId()
                                ),
                                'data' => [
                                    'id' => $user->getId(),
                                    'name' => $user->name,
                                    'image' => $user->image
                                ]
                            ];
                            foreach ($model->assignees as $assignee) {
                                if ($assignee->getId() === $user->getId()) {
                                    Html::removeCssClass($linkOptions, 'd-none');
                                    Html::addCssClass($linkOptions, 'd-flex');
                                    break;
                                }
                            }

                            $items[] = [
                                'label' => $this->render('_user', [
                                    'user' => $user,
                                    'assigned' => true
                                ]),
                                'linkOptions' => $linkOptions,
                                'url' => 'javascript:;'
                            ];
                        }
                        $items[] = ['label' => Yii::t('simialbi/kanban', 'Not assigned')];
                        foreach ($model->board->assignees as $user) {
                            $linkOptions = [
                                'class' => ['align-items-center', 'd-flex', 'add-assignee'],
                                'onclick' => sprintf(
                                    'window.sa.kanban.addAssignee.call(this, %u);',
                                    $user->getId()
                                ),
                                'data' => [
                                    'id' => $user->getId(),
                                    'name' => $user->name,
                                    'image' => $user->image
                                ]
                            ];
                            foreach ($model->assignees as $assignee) {
                                if ($assignee->getId() === $user->getId()) {
                                    Html::addCssClass($linkOptions, 'd-none');
                                    Html::removeCssClass($linkOptions, 'd-flex');
                                    break;
                                }
                            }

                            $items[] = [
                                'label' => $this->render('_user', [
                                    'user' => $user,
                                    'assigned' => false
                                ]),
                                'linkOptions' => $linkOptions,
                                'url' => 'javascript:;'
                            ];
                        }

//                        $items = [];
//                        if (!empty($assignees)) {
//                            $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
//                        }
//                        $items = array_merge($items, $assignees);
//                        if (!empty($assignees) && !empty($newUsers)) {
//                            $items[] = '-';
//                        }
//                        if (!empty($newUsers)) {
//                            $items[] = ['label' => Yii::t('simialbi/kanban', 'Not assigned')];
//                        }
//                        $items = array_merge($items, $newUsers);
                        ?>
                        <?= Dropdown::widget([
                            'items' => $items,
                            'encodeLabels' => false,
                            'options' => [
                                'class' => ['w-100']
                            ]
                        ]); ?>
                    </div>
                </div>
            </div>
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
                    <?= Html::label(Yii::t('simialbi/kanban/task', 'Checklist'), null, [
                        'class' => ['col-form-label-sm', 'py-0']
                    ]); ?>
                    <?= $form->field($model, 'card_show_checklist', [
                        'options' => ['class' => ''],
                        'labelOptions' => [
                            'class' => 'custom-control-label'
                        ],
                        'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                    ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
                </div>
                <?php foreach ($model->checklistElements as $checklistElement): ?>
                    <div class="input-group input-group-sm mb-1">
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
                                ],
                                'placeholder' => Html::encode($checklistElement->name)
                            ]
                        ); ?>
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger remove-checklist-element">
                                <?= FAS::i('trash-alt'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="input-group input-group-sm add-checklist-element mb-1">
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
            <div class="form-group col-12">
                <?= Html::label(Yii::t('simialbi/kanban/task', 'Comments'), 'comment', [
                    'class' => ['col-form-label-sm', 'py-0']
                ]); ?>
                <?= Html::textarea('comment', null, [
                    'class' => ['form-control', 'form-control-sm']
                ]); ?>
            </div>
            <?php if (count($model->comments)): ?>
                <div class="kanban-task-comments col-12">
                    <?php foreach ($model->comments as $comment): ?>
                        <div class="kanban-task-comment media">
                            <div class="kanban-user mr-3">
                                <?php if ($comment->author->image): ?>
                                    <?= Html::img($comment->author->image, [
                                        'class' => ['rounded-circle'],
                                        'title' => Html::encode($comment->author->name),
                                        'data' => [
                                            'toggle' => 'tooltip'
                                        ]
                                    ]); ?>
                                <?php else: ?>
                                    <span class="kanban-visualisation" data-toggle="tooltip"
                                          title="<?= Html::encode($comment->author->name); ?>">
                                        <?= strtoupper(substr($comment->author->name, 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="media-body">
                                <span class="text-muted d-flex flex-row justify-content-between">
                                    <span><?= Html::encode($comment->author->name); ?></span>
                                    <span><?= Yii::$app->formatter->asRelativeTime($comment->created_at); ?></span>
                                </span>
                                <?= Yii::$app->formatter->asParagraphs($comment->text); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
