<?php

use kartik\file\FileInput;
use marqu3s\summernote\Summernote;
use Recurr\Transformer\TextTransformer;
use Recurr\Transformer\Translator;
use rmrevin\yii\fontawesome\FAS;
use sandritsch91\yii2\flatpickr\Flatpickr;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\helpers\ReplaceArrayValue;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\MaskedInput;

/* @var $this \yii\web\View */
/* @var $model Task */
/* @var $buckets array */
/* @var $statuses array */
/* @var $updateSeries boolean */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $return string */
/* @var $readonly boolean */
/* @var $tasks Task[] */

$isAutor = $model->created_by == \Yii::$app->user->id;

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
?>
    <div class="kanban-task-modal">
        <?php $form = ActiveForm::begin([
            'id' => 'sa-kanban-task-modal-form',
            'action' => [
                'task/update',
                'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                'updateSeries' => $updateSeries,
                'return' => $return,
                'readonly' => $readonly
            ],
            'validateOnSubmit' => false,
            'fieldConfig' => [
                'labelOptions' => [
                    'class' => ['col-form-label-sm', 'py-0']
                ],
                'inputOptions' => [
                    'class' => ['form-control', 'form-control-sm']
                ]
            ],
            'options' => [
                'enctype' => 'multipart/form-data',
                'data' => [
                    'turbo-frame' => ($return === 'todo')
                        ? 'kanban-todo-frame'
                        /*
                        : 'task-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-frame'
                        /*/
                        : ($model->isRecurrentInstance() ? 'bucket-' . $model->bucket_id . '-frame' : 'task-' . $model->id . '-frame')
                    //*/
                ]
            ]
        ]); ?>
        <div class="modal-header">
            <?php $hint = ($model->status === $model::STATUS_DONE)
                ? Yii::t(
                    'simialbi/kanban/task',
                    'Finished at {finished,date} {finished,time} by {finisher}',
                    [
                        'finished' => $model->finished_at ?: $model->updated_at,
                        'finisher' => $model->finisher ? $model->finisher->name : Yii::t('yii', '(not set)')
                    ]
                )
                : Yii::t(
                    'simialbi/kanban/task',
                    'Created at {created,date} {created,time} by {creator}, last modified {updated,date} {updated,time} by {modifier}',
                    [
                        'created' => $model->created_at,
                        'creator' => $model->author ? $model->author->name : Yii::t('yii', '(not set)'),
                        'updated' => $model->updated_at,
                        'modifier' => $model->updater ? $model->updater->name : Yii::t('yii', '(not set)')
                    ]
                ); ?>
            <?php if ($model->is_recurring && $model->recurrence_pattern instanceof \Recurr\Rule) {
                $t = new TextTransformer(new Translator(substr(Yii::$app->language, 0, 2)));
                $hint .= '<br><span class="text-info">' . $t->transform($model->recurrence_pattern) . '</span>';
            } ?>
            <?= $form->field($model, 'subject', [
                'options' => [
                    'class' => ['my-0', 'w-100']
                ],
                'labelOptions' => [
                    'class' => ['sr-only']
                ],
                'inputOptions' => [
                    'class' => new ReplaceArrayValue(['form-control'])
                ]
            ])->textInput([
                'autocomplete' => 'off'
            ])->hint($hint); ?>
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
                            <?php
                            $options = [
                                'href' => 'javascript:;',
                                'data' => ['toggle' => 'dropdown'],
                                'class' => [
                                    'dropdown-toggle',
                                    'text-decoration-none',
                                    'text-reset',
                                    'd-flex',
                                    'flex-row'
                                ]
                            ];
                            ?>
                            <?= Html::beginTag('a', $options); ?>
                            <?php foreach ($model->assignees as $assignee): ?>
                                <span class="kanban-user" data-id="<?= $assignee->getId(); ?>"
                                      data-name="<?= $assignee->name; ?>" data-image="<?= $assignee->image; ?>">
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
                            <?= Html::endTag('a'); ?>
                            <?php
                            $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
                            foreach ($users as $user) {
                                $item = [
                                    'label' => $this->render('_user', [
                                        'user' => $user,
                                        'assigned' => true
                                    ]),
                                    'linkOptions' => [
                                        'class' => ['align-items-center', 'remove-assignee'],
                                        'style' => ['display' => 'none'],
                                        'onclick' => sprintf(
                                            'window.sa.kanban.removeAssignee.call(this, %u);',
                                            $user->getId()
                                        ),
                                        'data' => [
                                            'id' => $user->getId(),
                                            'name' => $user->name,
                                            'image' => $user->image
                                        ]
                                    ],
                                    'disabled' => !$isAutor,
                                    'url' => 'javascript:;'
                                ];
                                foreach ($model->assignees as $assignee) {
                                    if ($assignee->getId() === $user->getId()) {
                                        Html::removeCssStyle($item['linkOptions'], ['display']);
                                        Html::addCssClass($item['linkOptions'], 'is-assigned');
                                        break;
                                    }
                                }

                                $items[] = $item;
                            }
                            $items[] = '-';
                            $items[] = ['label' => Yii::t('simialbi/kanban', 'Not assigned')];
                            foreach ($users as $user) {
                                $linkOptions = [
                                    'class' => ['align-items-center', 'add-assignee'],
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
                                        Html::addCssStyle($linkOptions, ['display' => 'none']);
                                        Html::addCssClass($linkOptions, 'is-assigned');
//                                    Html::removeCssClass($linkOptions, 'd-flex');
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

                            array_unshift($items, HideSeek::widget([
                                'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                                'options' => [
                                    'id' => 'kanban-update-task-assignees',
                                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                                    'autocomplete' => 'off'
                                ],
                                'clientOptions' => [
                                    'list' => '.kanban-assignees',
                                    'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                                ]
                            ]));
                            ?>
                            <?= Dropdown::widget([
                                'id' => 'dropdown-assignees-' . $model->id,
                                'items' => $items,
                                'encodeLabels' => false,
                                'options' => [
                                    'class' => ['kanban-assignees', 'w-100']
                                ],
                                'clientEvents' => [
                                    'shown.bs.dropdown' => new JsExpression('function(e) {
                                        $(e.target).closest(".dropdown").find(".search-field input").trigger("focus");
                                    }'),
                                ]
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="row">
                        <?= $form->field($model, 'bucket_id', [
                            'options' => [
                                'class' => ['form-group', 'col-12', 'col-md-6']
                            ]
                        ])->dropDownList($buckets); ?>
                        <?= $form->field($model, 'status', [
                            'options' => [
                                'class' => ['form-group', 'col-12', 'col-md-6']
                            ]
                        ])->dropDownList($statuses); ?>
                        <?php if ($model->start_date) {
                            $model->start_date = Yii::$app->formatter->asDate($model->start_date, 'dd.MM.yyyy');
                        } ?>
                        <?php if ($model->end_date) {
                            $model->end_date = Yii::$app->formatter->asDate($model->end_date, 'dd.MM.yyyy');
                        } ?>
                        <?= $form->field($model, 'start_date', [
                            'options' => [
                                'class' => ['form-group', 'col-12', 'col-md-4']
                            ]
                        ])->widget(Flatpickr::class, [
                            'customAssetBundle' => false
                        ]); ?>
                        <?= $form->field($model, 'end_date', [
                            'options' => [
                                'class' => ['form-group', 'col-12', 'col-md-4']
                            ]
                        ])->widget(Flatpickr::class, [
                            'customAssetBundle' => false,
                            'options' => [
                                'placeholder' => (null === $model->endDate) ? '' : Yii::$app->formatter->asDate($model->endDate,
                                    'dd.MM.yyyy')
                            ]
                        ]); ?>
                        <?= $form->field($model, 'percentage_done', [
                            'options' => [
                                'class' => ['form-group', 'col-12', 'col-md-4']
                            ]
                        ])->widget(MaskedInput::class, [
                            'clientOptions' => [
                                'alias' => 'percentage'
                            ]
                        ]) ?>
                    </div>
                </div>
                <div class="col-12 col-md-6 border-left">
                    <h5><?= Yii::t('simialbi/kanban/task', 'Depends on') ?></h5>
                    <div class="list-group" id="task-dependencies">
                        <?php foreach ($model->dependencies as $dependency): ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                               href="javascript:;" onclick="window.sa.kanban.removeDependency.call(this);"
                                <?php if ($dependency->status === Task::STATUS_DONE): ?> style="text-decoration: line-through;"<?php endif;?>>
                                <input type="hidden" name="dependencies[]" value="<?= $dependency->id; ?>">
                                <?= $dependency->subject; ?><br>
                                <span class="badge badge-light">
                                    <?= Yii::$app->formatter->asDate($dependency->endDate, 'dd.MM.yyyy'); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="dropdown mt-3">
                        <a href="#" class="btn btn-primary" data-toggle="dropdown" role="button" aria-expanded="false">
                            <?= Yii::t('simialbi/kanban/task', 'Add dependency'); ?>
                        </a>
                        <div class="dropdown-menu">
                            <?php foreach ($tasks as $task): ?>
                                <a class="dropdown-item" href="javascript:;" onclick="window.sa.kanban.addDependency.call(this, <?= $task->id; ?>);"
                                   data-subject="<?= $task->subject; ?>" data-end-date='<?= Yii::$app->formatter->asDate($task->endDate, 'dd.MM.yyyy'); ?>'
                                   data-done="<?= $task->status === Task::STATUS_DONE; ?>"
                                   <?php if ($task->status === Task::STATUS_DONE): ?> style="text-decoration: line-through;"<?php endif;?>>
                                    <?= $task->subject; ?> (<?= Yii::$app->formatter->asDate($task->endDate, 'dd.MM.yyyy'); ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3 <?php if ($model->isRecurrentInstance()) : ?>d-none<?php endif; ?>">
                <div class="col-12">
                    <?= $form->field($model, 'is_recurring', [
                        'options' => [
                            'class' => ['form-group']
                        ]
                    ])->checkbox(); ?>

                    <?php
                    $freq = 'WEEKLY';
                    $interval = 1;
                    $byDay = null;
                    $byDayInt = null;
                    $byDayString = null;
                    $byMonthDay = date('j');
                    $byMonth = date('n');
                    if ($model->recurrence_pattern instanceof \Recurr\Rule) {
                        $freq = $model->recurrence_pattern->getFreqAsText();
                        $interval = $model->recurrence_pattern->getInterval();
                        $byDay = $model->recurrence_pattern->getByDay();
                        $byMonthDay = $model->recurrence_pattern->getByMonthDay();
                        $byMonth = $model->recurrence_pattern->getByMonth();
                        if ($byDay !== null) {
                            $byDayInt = preg_replace('#[^\-\d]#', '', $byDay);
                            $byDayString = preg_replace('#[\-\d]#', '', $byDay);
                        }
                        if (is_array($byMonthDay)) {
                            $byMonthDay = $byMonthDay[0];
                        }
                    }
                    ?>

                    <div class="collapse<?php if ($model->is_recurring): ?> show<?php endif; ?>">
                        <h6><?= Yii::t('simialbi/kanban/recurrence', 'Recurrence Pattern'); ?></h6>
                        <div class="row">
                            <div class="col-12 col-sm-5 col-md-4 col-lg-3 border-right">
                                <?= Html::radioList(Html::getInputName($model, 'recurrence_pattern[FREQ]'), $freq, [
                                    'DAILY' => Yii::t('simialbi/kanban/recurrence', 'Daily'),
                                    'WEEKLY' => Yii::t('simialbi/kanban/recurrence', 'Weekly'),
                                    'MONTHLY' => Yii::t('simialbi/kanban/recurrence', 'Monthly'),
                                    'YEARLY' => Yii::t('simialbi/kanban/recurrence', 'Yearly')
                                ], [
                                    'id' => Html::getInputId($model, 'recurrence_pattern[FREQ]')
                                ]); ?>
                            </div>
                            <div class="col-12 col-sm-7 col-md-8 col-lg-9">
                                <div
                                    id="recurrence-daily"<?php if ($freq !== 'DAILY'): ?> style="display: none;"<?php endif; ?>>
                                    <?= Yii::t('simialbi/kanban/recurrence', 'Recur every {input} day(s)', [
                                        'input' => Html::textInput(Html::getInputName($model,
                                            'recurrence_pattern[INTERVAL]'), $interval, [
                                            'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                            'style' => [
                                                'display' => 'inline-block',
                                                'vertical-algin' => 'middle',
                                                'width' => '3rem'
                                            ],
                                            'disabled' => $freq !== 'DAILY'
                                        ])
                                    ]); ?>
                                </div>
                                <div
                                    id="recurrence-weekly"<?php if ($freq !== 'WEEKLY'): ?> style="display: none;"<?php endif; ?>>
                                    <?= Yii::t('simialbi/kanban/recurrence', 'Recur every {input} week(s) on:', [
                                        'input' => Html::textInput(Html::getInputName($model,
                                            'recurrence_pattern[INTERVAL]'), $interval, [
                                            'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                            'style' => [
                                                'display' => 'inline-block',
                                                'vertical-algin' => 'middle',
                                                'width' => '3rem'
                                            ],
                                            'disabled' => $freq !== 'WEEKLY'
                                        ])
                                    ]); ?>

                                    <?= Html::checkboxList(Html::getInputName($model, 'recurrence_pattern[BYDAY]'),
                                        $byDay, [
                                            'MO' => Yii::t('simialbi/kanban/recurrence', 'Monday'),
                                            'TU' => Yii::t('simialbi/kanban/recurrence', 'Tuesday'),
                                            'WE' => Yii::t('simialbi/kanban/recurrence', 'Wednesday'),
                                            'TH' => Yii::t('simialbi/kanban/recurrence', 'Thursday'),
                                            'FR' => Yii::t('simialbi/kanban/recurrence', 'Friday'),
                                            'SA' => Yii::t('simialbi/kanban/recurrence', 'Saturday'),
                                            'SU' => Yii::t('simialbi/kanban/recurrence', 'Sunday')
                                        ], [
                                            'class' => ['form-inline', 'justify-content-around', 'flex-wrap', 'mt-3'],
                                            'itemOptions' => [
                                                'disabled' => $freq !== 'WEEKLY'
                                            ]
                                        ]); ?>
                                </div>
                                <div
                                    id="recurrence-monthly"<?php if ($freq !== 'MONTHLY'): ?> style="display: none;"<?php endif; ?>>
                                    <?= Html::radioList('pseudo', ($byDay !== null) ? 1 : 0, [
                                        Yii::t('simialbi/kanban/recurrence', 'Day {input1} of every {input2} month(s).',
                                            [
                                                'input1' => Html::textInput(Html::getInputName($model,
                                                    'recurrence_pattern[BYMONTHDAY]'), $byMonthDay, [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'style' => [
                                                        'width' => '3rem'
                                                    ],
                                                    'disabled' => $byDay !== null || $freq !== 'MONTHLY'
                                                ]),
                                                'input2' => Html::textInput(Html::getInputName($model,
                                                    'recurrence_pattern[INTERVAL]'), $interval, [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'style' => [
                                                        'width' => '3rem'
                                                    ],
                                                    'disabled' => $byDay !== null || $freq !== 'MONTHLY'
                                                ])
                                            ]),
                                        Yii::t('simialbi/kanban/recurrence',
                                            'The {input1} {input2} of every {input3} month(s)', [
                                                'input1' => Html::dropDownList(
                                                    Html::getInputName($model, 'recurrence_pattern[BYDAY][int]'),
                                                    $byDayInt,
                                                    [
                                                        '1' => Yii::t('simialbi/kanban/recurrence', 'first'),
                                                        '2' => Yii::t('simialbi/kanban/recurrence', 'second'),
                                                        '3' => Yii::t('simialbi/kanban/recurrence', 'third'),
                                                        '4' => Yii::t('simialbi/kanban/recurrence', 'fourth'),
                                                        '5' => Yii::t('simialbi/kanban/recurrence', 'fifth'),
                                                        '-1' => Yii::t('simialbi/kanban/recurrence', 'last')
                                                    ],
                                                    [
                                                        'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                        'disabled' => $byDay === null || $freq !== 'MONTHLY'
                                                    ]
                                                ),
                                                'input2' => Html::dropDownList(
                                                    Html::getInputName($model, 'recurrence_pattern[BYDAY][string]'),
                                                    $byDayString,
                                                    [
                                                        'MO' => Yii::t('simialbi/kanban/recurrence', 'Monday'),
                                                        'TU' => Yii::t('simialbi/kanban/recurrence', 'Tuesday'),
                                                        'WE' => Yii::t('simialbi/kanban/recurrence', 'Wednesday'),
                                                        'TH' => Yii::t('simialbi/kanban/recurrence', 'Thursday'),
                                                        'FR' => Yii::t('simialbi/kanban/recurrence', 'Friday'),
                                                        'SA' => Yii::t('simialbi/kanban/recurrence', 'Saturday'),
                                                        'SU' => Yii::t('simialbi/kanban/recurrence', 'Sunday')
                                                    ],
                                                    [
                                                        'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                        'disabled' => $byDay === null || $freq !== 'MONTHLY'
                                                    ]
                                                ),
                                                'input3' => Html::textInput(Html::getInputName($model,
                                                    'recurrence_pattern[INTERVAL]'), $interval, [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'style' => [
                                                        'width' => '3rem'
                                                    ],
                                                    'disabled' => $byDay === null || $freq !== 'MONTHLY'
                                                ])
                                            ])
                                    ], [
                                        'class' => ['multiple-choices'],
                                        'encode' => false,
                                        'itemOptions' => [
                                            'labelOptions' => [
                                                'class' => ['form-check-label', 'mb-3', 'form-inline']
                                            ]
                                        ]
                                    ]); ?>
                                </div>
                                <div
                                    id="recurrence-yearly"<?php if ($freq !== 'YEARLY'): ?> style="display: none;"<?php endif; ?>>
                                    <?= Yii::t('simialbi/kanban/recurrence', 'Recur every {input} year(s)', [
                                        'input' => Html::textInput(Html::getInputName($model,
                                            'recurrence_pattern[INTERVAL]'), $interval, [
                                            'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                            'style' => [
                                                'display' => 'inline-block',
                                                'width' => '3rem',
                                                'vertical-align' => 'middle'
                                            ],
                                            'disabled' => $freq !== 'YEARLY',
                                        ])
                                    ]); ?>
                                    <?= Html::radioList('pseudo', $byMonth === null ? 0 : 1, [
                                        Yii::t('simialbi/kanban/recurrence', 'On: {input1} {input2}', [
                                            'input1' => Html::textInput(Html::getInputName($model,
                                                'recurrence_pattern[BYMONTHDAY]'), $byMonthDay, [
                                                'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                'style' => [
                                                    'width' => '3rem'
                                                ],
                                                'disabled' => $byMonth !== null || $freq !== 'YEARLY',
                                            ]),
                                            'input2' => Html::dropDownList(
                                                Html::getInputName($model, 'recurrence_pattern[BYMONTH]'),
                                                $byMonth,
                                                [
                                                    '1' => Yii::t('simialbi/kanban/recurrence', 'January'),
                                                    '2' => Yii::t('simialbi/kanban/recurrence', 'February'),
                                                    '3' => Yii::t('simialbi/kanban/recurrence', 'March'),
                                                    '4' => Yii::t('simialbi/kanban/recurrence', 'April'),
                                                    '5' => Yii::t('simialbi/kanban/recurrence', 'May'),
                                                    '6' => Yii::t('simialbi/kanban/recurrence', 'June'),
                                                    '7' => Yii::t('simialbi/kanban/recurrence', 'July'),
                                                    '8' => Yii::t('simialbi/kanban/recurrence', 'August'),
                                                    '9' => Yii::t('simialbi/kanban/recurrence', 'September'),
                                                    '10' => Yii::t('simialbi/kanban/recurrence', 'October'),
                                                    '11' => Yii::t('simialbi/kanban/recurrence', 'November'),
                                                    '12' => Yii::t('simialbi/kanban/recurrence', 'December')
                                                ],
                                                [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'disabled' => $byMonth !== null || $freq !== 'YEARLY',
                                                ]
                                            )
                                        ]),
                                        Yii::t('simialbi/kanban/recurrence', 'On the: {input1} {input2} of {input3}', [
                                            'input1' => Html::dropDownList(
                                                Html::getInputName($model, 'recurrence_pattern[BYDAY][int]'),
                                                $byDayInt,
                                                [
                                                    '1' => Yii::t('simialbi/kanban/recurrence', 'first'),
                                                    '2' => Yii::t('simialbi/kanban/recurrence', 'second'),
                                                    '3' => Yii::t('simialbi/kanban/recurrence', 'third'),
                                                    '4' => Yii::t('simialbi/kanban/recurrence', 'fourth'),
                                                    '5' => Yii::t('simialbi/kanban/recurrence', 'fifth'),
                                                    '-1' => Yii::t('simialbi/kanban/recurrence', 'last')
                                                ],
                                                [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'disabled' => $byMonth === null || $freq !== 'YEARLY',
                                                ]
                                            ),
                                            'input2' => Html::dropDownList(
                                                Html::getInputName($model, 'recurrence_pattern[BYDAY][string]'),
                                                $byDayString,
                                                [
                                                    'MO' => Yii::t('simialbi/kanban/recurrence', 'Monday'),
                                                    'TU' => Yii::t('simialbi/kanban/recurrence', 'Tuesday'),
                                                    'WE' => Yii::t('simialbi/kanban/recurrence', 'Wednesday'),
                                                    'TH' => Yii::t('simialbi/kanban/recurrence', 'Thursday'),
                                                    'FR' => Yii::t('simialbi/kanban/recurrence', 'Friday'),
                                                    'SA' => Yii::t('simialbi/kanban/recurrence', 'Saturday'),
                                                    'SU' => Yii::t('simialbi/kanban/recurrence', 'Sunday')
                                                ],
                                                [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'disabled' => $byMonth === null || $freq !== 'YEARLY',
                                                ]
                                            ),
                                            'input3' => Html::dropDownList(
                                                Html::getInputName($model, 'recurrence_pattern[BYMONTH]'),
                                                $byMonth,
                                                [
                                                    '1' => Yii::t('simialbi/kanban/recurrence', 'January'),
                                                    '2' => Yii::t('simialbi/kanban/recurrence', 'February'),
                                                    '3' => Yii::t('simialbi/kanban/recurrence', 'March'),
                                                    '4' => Yii::t('simialbi/kanban/recurrence', 'April'),
                                                    '5' => Yii::t('simialbi/kanban/recurrence', 'May'),
                                                    '6' => Yii::t('simialbi/kanban/recurrence', 'June'),
                                                    '7' => Yii::t('simialbi/kanban/recurrence', 'July'),
                                                    '8' => Yii::t('simialbi/kanban/recurrence', 'August'),
                                                    '9' => Yii::t('simialbi/kanban/recurrence', 'September'),
                                                    '10' => Yii::t('simialbi/kanban/recurrence', 'October'),
                                                    '11' => Yii::t('simialbi/kanban/recurrence', 'November'),
                                                    '12' => Yii::t('simialbi/kanban/recurrence', 'December')
                                                ],
                                                [
                                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                                    'disabled' => $byMonth === null || $freq !== 'YEARLY',
                                                ]
                                            )
                                        ])
                                    ], [
                                        'encode' => false,
                                        'class' => ['multiple-choices', 'mt-3'],
                                        'itemOptions' => [
                                            'labelOptions' => [
                                                'class' => ['form-check-label', 'mb-3', 'form-inline']
                                            ]
                                        ]
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row kanban-task-responsible">
                <div class="dropdown form-group col-12">
                    <div class="position-relative">
                        <?= Html::label(Yii::t('simialbi/kanban/model/task', 'Responsible'), null, [
                            'class' => [
                                'col-form-label-sm',
                                'py-0'
                            ]
                        ]) ?>

                        <div class="input-group input-group-sm">
                            <?= Html::activeHiddenInput($model, 'responsible_id') ?>
                            <?= Html::textInput('', $model->responsible_id ? $model->responsible->fullname : null, [
                                'id' => 'task-responsible_id-dummy',
                                'class' => [
                                    'form-control',
                                    'form-control-sm'
                                ],
                                'data' => [
                                    'toggle' => 'dropdown'
                                ],
                                'autocomplete' => 'off'
                            ]) ?>
                            <?php
                            $items = [];
                            foreach ($users as $user) {
                                $item = [
                                    'label' => $this->render('_user', [
                                        'user' => $user,
                                        'assigned' => false
                                    ]),
                                    'linkOptions' => [
                                        'class' => ['align-items-center', 'remove-assignee'],
                                        'onclick' => sprintf(
                                            'window.sa.kanban.chooseResponsible.call(this, %u);',
                                            $user->getId()
                                        ),
                                        'data' => [
                                            'id' => $user->getId(),
                                            'name' => $user->name,
                                            'image' => $user->image
                                        ]
                                    ],
                                    'disabled' => !$isAutor,
                                    'url' => 'javascript:;'
                                ];

                                $items[] = $item;
                            }

                            array_unshift($items, HideSeek::widget([
                                'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                                'options' => [
                                    'id' => 'kanban-update-task-responsible',
                                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                                    'autocomplete' => 'off'
                                ],
                                'clientOptions' => [
                                    'list' => '.kanban-responsible',
                                    'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                                ]
                            ]));
                            ?>
                            <?= Dropdown::widget([
                                'id' => 'responsible-dropdown-' . $model->id,
                                'items' => $items,
                                'encodeLabels' => false,
                                'options' => [
                                    'class' => ['kanban-responsible', 'w-100']
                                ],
                                'clientEvents' => [
                                    'shown.bs.dropdown' => new JsExpression('function(e) {
                                        $(e.target).closest(".dropdown").find(".search-field input").trigger("focus");
                                    }'),
                                ]
                            ]); ?>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary"
                                        type="button" <?= ($isAutor ? 'onclick="window.sa.kanban.removeResponsible();"' : 'disabled') ?>>
                                    <?= FAS::i('times') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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
                    'inputOptions' => ['id' => 'taskModalSummernote-description'],
                    'options' => [
                        'class' => ['form-group', 'col-12']
                    ]
                ])->widget(Summernote::class, [
                    'clientOptions' => [
                        'styleTags' => [
                            'p',
                            [
                                'title' => 'blockquote',
                                'tag' => 'blockquote',
                                'className' => 'blockquote',
                                'value' => 'blockquote'
                            ],
                            'pre'
                        ],
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
                        <div class="kanban-task-checklist-element input-group input-group-sm mb-1"
                             data-id="<?= $checklistElement->id; ?>">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <a href="javascript:;" class="kanban-task-checklist-sort text-body">
                                        <?= FAS::i('grip-lines'); ?>
                                    </a>
                                </div>
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
                                $checklistElement->name,
                                [
                                    'class' => ['form-control'],
                                    'style' => [
                                        'text-decoration' => $checklistElement->is_done ? 'line-through' : 'none'
                                    ],
                                    'placeholder' => Html::encode($checklistElement->name)
                                ]
                            ); ?>
                            <?= Flatpickr::widget([
                                'name' => 'checklist[' . $checklistElement->id . '][end_date]',
                                'value' => $checklistElement->end_date ? Yii::$app->formatter->asDate($checklistElement->end_date,
                                    'dd.MM.yyyy') : null,
                                'id' => 'task-' . $model->id . '-ce-' . $checklistElement->id . '-end-date',
                                'customAssetBundle' => false,
                                'options' => [
                                    'autocomplete' => 'off',
                                    'class' => ['form-control'],
                                    'tabindex' => '-1',
                                    'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'End date')
                                ],
                                'clientOptions' => [
                                    'minDate' => 'today',
                                    'maxDate' => ($model->end_date)
                                        ? Yii::$app->formatter->asTimestamp($model->end_date) * 1000
                                        : null
                                ]
                            ]); ?>
                            <div class="input-group-append">
                                <button class="btn btn-outline-danger remove-checklist-element">
                                    <?= FAS::i('trash-alt'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="kanban-task-checklist-element input-group input-group-sm add-checklist-element mb-1">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <?= Html::checkbox('checklist[new][0][is_done]', false); ?>
                            </div>
                        </div>
                        <?= Html::input('text', 'checklist[new][0][name]', null, [
                            'class' => ['form-control'],
                            'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'Name')
                        ]); ?>
                        <?= Flatpickr::widget([
                            'name' => 'checklist[new][0][end_date]',
                            'value' => null,
                            'id' => 'task-' . $model->id . '-ce-new-end-date-1',
                            'customAssetBundle' => false,
                            'options' => [
                                'autocomplete' => 'off',
                                'class' => ['form-control'],
                                'tabindex' => '-1',
                                'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'End date'),
                                'data' => [
                                    'lang' => preg_replace('/^([a-z]{2})(?:-[a-z]{2})?/i', '$1', Yii::$app->language)
                                ],
                            ],
                            'clientOptions' => [
                                'minDate' => 'today',
                                'maxDate' => ($model->end_date)
                                    ? Yii::$app->formatter->asTimestamp($model->end_date) * 1000
                                    : null
                            ]
                        ]); ?>
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger remove-checklist-element disabled" disabled>
                                <?= FAS::i('trash-alt'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12">
                    <?= Html::label(Yii::t('simialbi/kanban/task', 'Attachments'), 'task-attachments', [
                        'class' => ['col-form-label-sm', 'py-0']
                    ]); ?>
                    <?= FileInput::widget([
                        'name' => 'attachments[]',
                        'options' => [
                            'id' => 'task-attachments',
                            'multiple' => true
                        ],
                        'pluginOptions' => [
                            'mainClass' => 'file-caption-main input-group-sm',
                            'showPreview' => false,
                            'showUpload' => false
                        ],
                        'bsVersion' => '4'
                    ]); ?>
                </div>
            </div>
            <?php if ($model->attachments): ?>
        </div>
        <div class="list-group list-group-flush kanban-task-attachments">
            <?php $i = 0; ?>
            <?php foreach ($model->attachments as $attachment): ?>
                <div class="list-group-item list-group-item-action d-flex flex-row justify-content-between">
                    <a href="<?= $attachment->path; ?>" target="_blank"><?= Html::encode($attachment->name); ?></a>
                    <?= $form->field($attachment, "[$i]card_show", [
                        'options' => ['class' => 'ml-auto mr-3 kanban-attachment-show'],
                        'labelOptions' => [
                            'class' => 'custom-control-label'
                        ],
                        'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                    ])->checkbox([
                        'inline' => true,
                        'class' => 'custom-control-input'
                    ]); ?>
                    <?= Html::a(FAS::i('trash-alt'),
                        ['attachment/delete', 'id' => $attachment->id, 'readonly' => $readonly], [
                            'class' => ['remove-attachment'],
                            'data' => [
                                'turbo' => 'true',
                                'turbo-frame' => 'task-' . $model->id . '-update-frame'
                            ]
                        ]); ?>
                    <?php $i++; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-body">
            <?php endif; ?>
            <div class="row">
                <div class="form-group col-12 linklist">
                    <div class="d-flex justify-content-between">
                        <?= Html::label(Yii::t('simialbi/kanban/task', 'Links'), 'add-link', [
                            'class' => ['col-form-label-sm', 'py-0']
                        ]); ?>
                        <?= $form->field($model, 'card_show_links', [
                            'options' => ['class' => ''],
                            'labelOptions' => [
                                'class' => 'custom-control-label'
                            ],
                            'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                        ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
                    </div>
                    <?php foreach ($model->links as $link): ?>
                        <div class="input-group input-group-sm mb-1">
                            <?= Html::input(
                                'text',
                                'link[' . $link->id . '][url]',
                                $link->url,
                                [
                                    'class' => ['form-control'],
                                    'placeholder' => Html::encode($link->url)
                                ]
                            ); ?>
                            <div class="input-group-append">
                                <a href="<?= $link->url; ?>" class="btn btn-outline-secondary" target="_blank">
                                    <?= FAS::i('external-link-alt') ?>
                                </a>
                            </div>
                            <div class="input-group-append">
                                <button class="btn btn-outline-danger remove-linklist-element">
                                    <?= FAS::i('trash-alt'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="input-group input-group-sm add-linklist-element mb-1">
                        <?= Html::input('text', 'link[new][][url]', null, [
                            'id' => 'add-link',
                            'class' => ['form-control']
                        ]); ?>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12">
                    <?= Html::label(Yii::t('simialbi/kanban/task', 'Comments'), 'comment', [
                        'class' => ['col-form-label-sm', 'py-0']
                    ]); ?>
                    <?= Summernote::widget([
                        'id' => 'taskModalSummernote-comment',
                        'name' => 'comment',
                        'value' => '',
                        'options' => ['form-control', 'form-control-sm'],
                        'clientOptions' => [
                            'styleTags' => [
                                'p',
                                [
                                    'title' => 'blockquote',
                                    'tag' => 'blockquote',
                                    'className' => 'blockquote',
                                    'value' => 'blockquote'
                                ],
                                'pre'
                            ],
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
                <?php if (count($model->comments)): ?>
                    <div class="kanban-task-comments mt-4 col-12">
                        <?php $i = 0; ?>
                        <?php foreach ($model->comments as $comment): ?>
                            <div class="kanban-task-comment media<?php if ($i++ !== 0): ?> mt-3<?php endif; ?>">
                                <div class="kanban-user mr-3">
                                    <?php if ($comment->author): ?>
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
                                    <?php else: ?>
                                        <span class="kanban-visualisation" data-toggle="tooltip"
                                              title="Unknown">
                                            U
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="media-body">
                                <span class="text-muted d-flex flex-row justify-content-between">
                                    <?php if ($comment->author): ?>
                                        <span><?= Html::encode($comment->author->name); ?></span>
                                    <?php else: ?>
                                        <span>Unknown</span>
                                    <?php endif; ?>
                                    <span>
                                        <?= Yii::$app->formatter->asDatetime($comment->created_at, 'medium'); ?>
                                    </span>
                                </span>
                                    <?= $comment->text; ?>
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
$baseUrl = Url::to(['/' . $this->context->module->id . '/sort']);
$recurrenceId = Html::getInputId($model, 'is_recurring');
$recurrenceFreqId = Html::getInputId($model, 'recurrence_pattern[FREQ]');
$js = <<<JS
jQuery('#$recurrenceId').on('change', function () {
    var \$this = jQuery(this),
        \$collapse = \$this.closest('.form-group').next();
    if (\$this.is(':checked')) {
        \$collapse.collapse('show');
    } else {
        \$collapse.collapse('hide');
    }
});
jQuery('#$recurrenceFreqId input[type=radio]').on('change', function () {
    var \$this = jQuery(this),
        els = {
        DAILY: jQuery('#recurrence-daily'),
        WEEKLY: jQuery('#recurrence-weekly'),
        MONTHLY: jQuery('#recurrence-monthly'),
        YEARLY: jQuery('#recurrence-yearly')
    };
    jQuery.each(els, function () {
        this.hide();
        this.find('input,select').prop('disabled', true);
    });
    els[\$this.val()].show();
    els[\$this.val()].find('input,select').prop('disabled', false);
    els[\$this.val()].find('.multiple-choices > .form-check > .form-check-input:not(:checked)').next().find('input,select').prop('disabled', true);
});

jQuery('.multiple-choices > .form-check > .form-check-input').on('change', function () {
    var \$this = jQuery(this),
        \$parent = \$this.closest('.multiple-choices');
    \$parent.find('input:not([type="radio"]),select').prop('disabled', true);
    \$this.next().find('input,select').prop('disabled', false);
});

jQuery('.checklist').sortable({
    items: '> .kanban-task-checklist-element',
    handle: '.kanban-task-checklist-sort',
    stop: function (event, ui) {
        var \$element = ui.item;
        var \$before = \$element.prev('.kanban-task-checklist-element');
        var action = 'move-after';
        var pk = null;

        if (!\$before.length) {
            action = 'move-as-first';
        } else {
            pk = \$before.data('id');
        }

        jQuery.post('$baseUrl/' + action, {
            modelClass: 'simialbi\\\\yii2\\\\kanban\\\\models\\\\ChecklistElement',
            modelPk: \$element.data('id'),
            pk: pk
        }, function (data) {
            console.log(data);
        });
    }
});
JS;
$this->registerJs($js);

Frame::end();
