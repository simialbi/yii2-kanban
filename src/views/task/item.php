<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap4\ButtonDropdown;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model Task */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */

Pjax::begin([
    'id' => 'taskPjax' . $model->hash,
    'enablePushState' => false,
    'options' => [
        'class' => ['kanban-sortable'],
        'data' => [
            'id' => $model->id,
            'event' => Json::encode([
                'id' => $model->id,
                'title' => $model->subject,
                'allDay' => true,
                'classNames' => ['border-0'],
                'url' => Url::to(['task/update', 'id' => $model->id])
            ])
        ]
    ],
    'clientOptions' => [
        'skipOuterContainers' => false
    ]
]);
?>
    <div class="kanban-task card mb-2 status-<?= $model->status; ?>">
        <?php foreach ($model->attachments as $attachment): ?>
            <?php /* @var $attachment \simialbi\yii2\kanban\models\Attachment */ ?>
            <?php if ($attachment->card_show && preg_match('#^image/#', $attachment->mime_type)): ?>
                <?= Html::img($attachment->path, [
                    'class' => ['card-img-top'],
                    'alt' => $attachment->name
                ]); ?>
                <?php break; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="kanban-task-content card-body">
            <div class="d-flex justify-content-between">
                <h6 class="card-title">
                    <?= Html::encode($model->subject); ?>
                </h6>
                <?= Html::a(
                    FAR::i('check-circle', ['class' => 'd-block']),
                    ['task/set-status', 'id' => $model->id, 'status' => Task::STATUS_DONE],
                    [
                        'class' => ['h5', 'kanban-task-done-link', 'd-block', 'text-decoration-none']
                    ]
                ); ?>
            </div>
            <?php if ($model->card_show_description && $model->description): ?>
                <div class="kanban-task-description"><?= $model->description; ?></div>
            <?php endif; ?>
            <?php if ($model->card_show_checklist && count($model->checklistElements)): ?>
                <?php foreach ($model->checklistElements as $checklistElement): ?>
                    <?php if ($checklistElement->is_done): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <a class="kanban-task-checkbox custom-control custom-checkbox text-reset" href="<?= Url::to([
                        'checklist-element/set-done',
                        'id' => $checklistElement->id
                    ]); ?>">
                        <?= Html::checkbox('checklist[' . $checklistElement->id . ']', false, [
                            'class' => ['custom-control-input'],
                            'id' => 'checklistElement-' . $model->id . '-' . $checklistElement->id
                        ]); ?>
                        <?= Html::label(
                            $checklistElement->label,
                            'checklistElement-' . $model->id . '-' . $checklistElement->id,
                            [
                                'class' => ['custom-control-label']
                            ]
                        ); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($model->card_show_links && count($model->links)): ?>
                <?php foreach ($model->links as $link): ?>
                    <a class="kanban-task-link d-block text-truncate" href="<?= $link->url ?>" target="_blank">
                        <?= $link->url; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php foreach ($model->attachments as $attachment): ?>
                <?php if ($attachment->card_show): ?>
                    <?= Html::a(FAR::i($attachment->icon, ['class' => 'fa-fw']) . ' ' . $attachment->name, $attachment->path, [
                        'class' => ['d-block', 'text-muted', 'text-truncate'],
                        'style' => [
                            'max-width' => '100%'
                        ],
                        'data' => ['pjax' => '0'],
                        'target' => '_blank'
                    ]); ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="kanban-task-info d-flex flex-row align-items-center">
                <?php if ($model->status === Task::STATUS_IN_PROGRESS): ?>
                    <small class="dropdown text-muted mr-3">
                        <a href="javascript:;" data-toggle="dropdown"
                           class="dropdown-toggle text-decoration-none text-reset">
                            <?= FAS::i('star-half-alt'); ?>
                        </a>

                        <?php
                        $items = [];
                        foreach ($statuses as $status => $label) {
                            if ($status === Task::STATUS_LATE) {
                                continue;
                            }
                            $items[] = [
                                'label' => $label,
                                'url' => ['task/set-status', 'id' => $model->id, 'status' => $status]
                            ];
                        }
                        echo Dropdown::widget([
                            'items' => $items
                        ]);
                        ?>
                    </small>
                <?php endif; ?>
                <?php if ($model->endDate): ?>
                    <?php $options = [
                        'label' => FAR::i('calendar-alt') . ' ' . Yii::$app->formatter->asDate(
                            $model->endDate,
                            'short'
                        ),
                        'class' => ['btn', 'btn-sm', 'mr-3', 'px-0']
                    ]; ?>
                    <?php if ($model->endDate < time() && $model->status !== $model::STATUS_DONE): ?>
                        <?php Html::addCssClass($options, ['btn-danger', 'px-1']); ?>
                        <?php Html::removeCssClass($options, 'px-0'); ?>
                    <?php elseif ($model->start_date && $model->start_date <= time()): ?>
                        <?php Html::addCssClass($options, ['btn-info', 'px-1']); ?>
                        <?php Html::removeCssClass($options, 'px-0'); ?>
                    <?php endif; ?>
                    <?= DatePicker::widget([
                        'model' => $model,
                        'options' => [
                            'id' => 'task-end_date-' . $model->id
                        ],
                        'attribute' => 'end_date',
                        'bsVersion' => '4',
                        'type' => DatePicker::TYPE_BUTTON,
                        'buttonOptions' => $options,
                        'pluginOptions' => [
                            'todayHighlight' => true
                        ],
                        'pluginEvents' => [
                            'changeDate' => new JsExpression('function (e) {
                                var event = jQuery.Event(\'click\');
                                var container = \'#\' + jQuery(this).closest(\'[data-pjax-container]\').prop(\'id\');

                                event.currentTarget = document.createElement(\'a\');
                                event.currentTarget.href = \'' . Url::to([
                                        'task/set-end-date',
                                        'id' => $model->id
                                    ]) . '&date=\' + (e.date.getTime() / 1000)
                                jQuery.pjax.click(event, container, {
                                    push: false,
                                    replace: false,
                                    skipOuterContainers: true,
                                    timeout: 0
                                });
                                jQuery(this).kvDatepicker(\'hide\');
                            }')
                        ]
                    ]); ?>
                <?php endif; ?>
                <?php if ($model->ticket_id): ?>
                    <small class="text-muted mr-3">
                        <?= FAS::i('headset'); ?>
                    </small>
                <?php endif; ?>
                <?php if (count($model->comments)): ?>
                    <small class="text-muted mr-3">
                        <?= FAR::i('comment-alt'); ?>
                    </small>
                <?php endif; ?>
                <?php if (count($model->attachments)): ?>
                    <small class="text-muted mr-3">
                        <?= FAS::i('paperclip'); ?>
                        <?= count($model->attachments); ?>
                    </small>
                <?php endif; ?>
                <?php if (count($model->checklistElements)): ?>
                    <small class="text-muted mr-3">
                        <?= FAR::i('check-square'); ?>
                        <?= $model->checklistStats; ?>
                    </small>
                <?php endif; ?>
                <?= Html::a(FAS::i('edit'), [
                    'task/update',
                    'id' => $model->id,
                    'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                ], [
                    'class' => ['btn', 'btn-sm', 'ml-auto', 'kanban-task-update-link'],
                    'data' => [
                        'pjax' => '0',
                        'toggle' => 'modal',
                        'target' => '#taskModal'
                    ]
                ]); ?>
                <?php
                $items = [
                    [
                        'label' => FAS::i('edit', ['class' => ['mr-1']])->fixedWidth() . ' ' . Yii::t('yii', 'Update'),
                        'url' => [
                            'task/update',
                            'id' => $model->id,
                            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                        ],
                        'linkOptions' => [
                            'data' => [
                                'toggle' => 'modal',
                                'target' => '#taskModal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('clone', ['class' => ['mr-1']])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Copy task'),
                        'url' => [
                            'task/copy',
                            'id' => $model->id,
                            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                        ],
                        'linkOptions' => [
                            'data' => [
                                'toggle' => 'modal',
                                'target' => '#taskModal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('user-plus', ['class' => ['mr-1']])->fixedWidth() . ' ' . Yii::t('simialbi/kanban/task', 'Create task per each user'),
                        'url' => [
                            'task/copy-per-user',
                            'id' => $model->id,
                            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                        ],
                        'disabled' => $model->created_by != Yii::$app->user->id,
                        'linkOptions' => [
                            'data' => [
                                'toggle' => 'modal',
                                'target' => '#taskModal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('link', ['class' => ['mr-1']])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Copy link'),
                        'url' => 'javascript:;',
                        'linkOptions' => [
                            'onclick' => 'window.sa.kanban.copyTextToClipboard(\'' . Url::to([
                                    'plan/view',
                                    'id' => $model->board->id,
                                    'showTask' => $model->id,
                                    'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                                ], true) . '\')'
                        ]
                    ],
                    [
                        'label' => FAS::i('trash-alt', ['class' => ['mr-1']])->fixedWidth() . ' ' . Yii::t('yii', 'Delete'),
                        'url' => [
                            'task/delete',
                            'id' => $model->id,
                            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                        ],
                        'disabled' => $model->created_by != Yii::$app->user->id,
                        'linkOptions' => [
                            'data' => [
                                'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?')
                            ]
                        ]
                    ]
                ];
                if ($model->ticket_id) {
                    array_unshift($items, [
                        'label' => Yii::t('simialbi/kanban', 'Go to ticket'),
                        'url' => ['/ticket/ticket/view', 'id' => $model->ticket_id],
                        'linkOptions' => [
                            'target' => '_blank'
                        ]
                    ]);
                }
                ?>
                <?= ButtonDropdown::widget([
                    'label' => FAS::i('ellipsis-h'),
                    'encodeLabel' => false,
                    'direction' => ButtonDropdown::DIRECTION_RIGHT,
                    'buttonOptions' => [
                        'class' => ['toggle' => '', 'btn' => 'btn btn-sm']
                    ],
                    'dropdown' => [
                        'items' => $items,
                        'encodeLabels' => false
                    ]
                ]); ?>
            </div>
        </div>
        <?php if (count($model->assignees)): ?>
            <div class="kanban-task-assignees kanban-assignees card-footer">
                <div class="dropdown">
                    <a href="javascript:;" data-toggle="dropdown"
                       class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">
                        <?php foreach ($model->assignees as $assignee): ?>
                            <span class="kanban-user">
                            <?php if ($assignee->image): ?>
                                <?= Html::img($assignee->image, [
                                    'class' => ['rounded-circle', 'mr-1'],
                                    'title' => Html::encode($assignee->name),
                                    'data' => [
                                        'toggle' => 'tooltip'
                                    ]
                                ]); ?>
                            <?php else: ?>
                                <span class="kanban-visualisation mr-1" title="<?= Html::encode($assignee->name); ?>"
                                      data-toggle="tooltip">
                                    <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                                </span>
                            <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </a>
                    <?php
                    $assignees = [];
                    $newUsers = [];
                    foreach ($model->assignees as $assignee) {
                        $assignees[] = [
                            'label' => $this->render('_user', [
                                'user' => $assignee,
                                'assigned' => true
                            ]),
                            'linkOptions' => [
                                'class' => ['align-items-center', 'remove-assignee', 'is-assigned']
                            ],
                            'disabled' => $model->created_by != Yii::$app->user->id,
                            'url' => ['task/expel-user', 'id' => $model->id, 'userId' => $assignee->getId()]
                        ];
                    }

                    foreach ($users as $user) {
                        foreach ($model->assignees as $assignee) {
                            if ($user->getId() === $assignee->getId()) {
                                continue 2;
                            }
                        }
                        $newUsers[] = [
                            'label' => $this->render('_user', [
                                'user' => $user,
                                'assigned' => false
                            ]),
                            'linkOptions' => [
                                'class' => ['align-items-center', 'add-assignee']
                            ],
                            'disabled' => $model->created_by != Yii::$app->user->id,
                            'url' => ['task/assign-user', 'id' => $model->id, 'userId' => $user->getId()]
                        ];
                    }

                    $items = [];
                    if (!empty($assignees)) {
                        $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
                    }
                    $items = array_merge($items, $assignees);
                    if (!empty($assignees) && !empty($newUsers)) {
                        $items[] = '-';
                    }
                    if (!empty($newUsers)) {
                        $items[] = ['label' => Yii::t('simialbi/kanban', 'Not assigned')];
                    }
                    $items = array_merge($items, $newUsers);
                    array_unshift($items, HideSeek::widget([
                        'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                        'options' => [
                            'id' => 'kanban-footer-task-assignees-' . $model->hash,
                            'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                        ],
                        'clientOptions' => [
                            'list' => '.kanban-footer-task-assignees-' . $model->hash,
                            'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                        ]
                    ]));
                    ?>
                    <?= Dropdown::widget([
                        'items' => $items,
                        'encodeLabels' => false,
                        'options' => [
                            'class' => ['kanban-footer-task-assignees-' . $model->hash, 'w-100']
                        ]
                    ]); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php
Pjax::end();
