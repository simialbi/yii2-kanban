<?php

use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use sandritsch91\yii2\flatpickr\Flatpickr;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Attachment;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ButtonDropdown;
use yii\bootstrap5\Dropdown;
use yii\caching\DbQueryDependency;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this View */
/* @var $boardId integer|null */
/* @var $model Task */
/* @var $statuses array */
/* @var $users UserInterface[] */
/* @var $closeModal boolean */
/* @var $group string|null */
/* @var $readonly boolean */

if (!isset($group)) {
    $group = 'bucket';
}

Frame::begin([
    'options' => [
        'id' => 'task-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-frame',
        'class' => ['kanban-sortable'],
        'data' => [
            'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
            'event' => [
                'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                'title' => $model->subject,
                'allDay' => true,
                'classNames' => ['border-0'],
                'url' => Url::to(['task/update', 'id' => $model->id])
            ]
        ],
        'alt' => $model->subject . ' ' . str_replace(["\r", "\n"], ' ', strip_tags((string)$model->description))
    ]
]);
?>
    <div class="kanban-task card mb-2 status-<?= $model->status; ?>">
        <?php foreach ($model->attachments as $attachment): ?>
            <?php /* @var $attachment Attachment */ ?>
            <?php if ($attachment->card_show && str_starts_with($attachment->mime_type, 'image/')): ?>
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
                    <?php if ($model->isRecurrentInstance()): ?>
                        <?= FAS::i('infinity', [
                            'fa-transform' => 'shrink-4.5',
                            'fa-mask' => 'fas fa-circle'
                        ]); ?>
                    <?php endif; ?>
                    <?= Html::encode($model->subject); ?>
                </h6>
                <?= Html::a(
                    FAR::i('check-circle', ['class' => 'd-block']),
                    [
                        'task/set-status',
                        'id' => ($model->isRecurrentInstance()) ? $model->recurrence_parent_id : $model->id,
                        'status' => Task::STATUS_DONE,
                        'readonly' => $readonly
                    ],
                    [
                        'class' => ['h5', 'kanban-task-done-link', 'd-block', 'text-decoration-none'],
                        'data' => [
                            'turbo-frame' => 'bucket-' . $model->bucket_id . '-frame',
                            'turbo' => 'true'
                        ]
                    ]
                ); ?>
            </div>
            <?php if ($model->card_show_description && $model->description): ?>
                <div class="kanban-task-description"><?= Html::stripTags($model->description); ?></div>
            <?php endif; ?>
            <?php if ($model->card_show_checklist && count($model->checklistElements)): ?>
                <?php foreach ($model->checklistElements as $checklistElement): ?>
                    <?php if ($checklistElement->is_done): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php
                    $text = Html::checkbox('checklist[' . $checklistElement->id . ']', false, [
                            'class' => ['form-check-input'],
                            'id' => 'checklistElement-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-' . $checklistElement->id
                        ]) . ' ' . Html::label(
                            $checklistElement->label,
                            'checklistElement-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-' . $checklistElement->id,
                            [
                                'class' => ['form-check-label']
                            ]
                        );
                    ?>
                    <?= Html::a($text, Url::to([
                        'checklist-element/set-done',
                        'id' => $checklistElement->id,
                        'readonly' => $readonly
                    ]), [
                        'class' => ['kanban-task-checkbox', 'd-block', 'text-reset'],
                        'data' => [
                            'turbo' => 'true',
                            'turbo-frame' => 'task-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-frame',
                        ]
                    ]) ?>
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
                    <?= Html::a(
                        FAR::i($attachment->icon, ['class' => 'fa-fw']) . ' ' . $attachment->name,
                        $attachment->path,
                        [
                            'class' => ['d-block', 'text-muted', 'text-truncate'],
                            'style' => [
                                'max-width' => '100%'
                            ],
                            'data' => ['pjax' => '0'],
                            'target' => '_blank'
                        ]
                    ); ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="kanban-task-info d-flex flex-row align-items-center position-relative">
                <?php if ($model->status < Task::STATUS_NOT_BEGUN && $model->status > Task::STATUS_DONE): ?>
                    <small class="dropdown text-muted me-3">
                        <a href="javascript:;" data-bs-toggle="dropdown"
                           class="dropdown-toggle text-decoration-none text-reset">
                            <?= FAS::i('star-half-alt'); ?>
                        </a>

                        <?php
                        $items = [];
                        foreach ($statuses as $status => $label) {
                            if ($status === Task::STATUS_LATE) {
                                continue;
                            }
                            $active = $status === $model->status ? 'active' : '';
                            $items[] = [
                                'label' => $label,
                                'url' => [
                                    'task/set-status',
                                    'id' => ($model->isRecurrentInstance()) ? $model->recurrence_parent_id : $model->id,
                                    'status' => $status
                                ],
                                'linkOptions' => [
                                    'class' => [$active],
                                    'data' => [
                                        'turbo-frame' => 'task-' . $model->id . '-frame',
                                        'turbo' => 'true'
                                    ]
                                ]
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
                        'class' => ['btn', 'btn-sm', 'me-3', 'px-0', 'position-relative', 'd-none', 'd-md-block'],
                        'style' => ['z-index' => '1'],
                        'onClick' => new JsExpression('document.querySelector(\'#task-end_date-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '\')._flatpickr.open()')
                    ]; ?>
                    <?php if ($model->endDate < time() && $model->status !== $model::STATUS_DONE): ?>
                        <?php Html::addCssClass($options, ['btn-danger', 'px-1']); ?>
                        <?php Html::removeCssClass($options, 'px-0'); ?>
                    <?php elseif ($model->start_date && $model->start_date <= time()): ?>
                        <?php Html::addCssClass($options, ['btn-info', 'px-1']); ?>
                        <?php Html::removeCssClass($options, 'px-0'); ?>
                    <?php endif; ?>
                    <?= Flatpickr::widget([
                        'model' => $model,
                        'options' => [
                            'id' => 'task-end_date-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id),
                            'class' => ['position-absolute', 'border-0'],
                            'style' => [
                                'width' => '59px',
                                'height' => '2rem',
                                'visibility' => 'hidden',
                                'z-index' => '0',
                                'left' => ($model->status === Task::STATUS_IN_PROGRESS) ? '2.75rem' : '0'
                            ]
                        ],
                        'attribute' => 'end_date',
                        'clientOptions' => [
                            'onChange' => new JsExpression('function (selectedDates, dateStr, instance) {
                                var container = jQuery(instance.element).closest(\'#bucket-' . $model->bucket_id . '-frame\').get(0);
                                var date = selectedDates[0];

                                instance.destroy();

                                jQuery.ajax({
                                    url: \'' . Url::to([
                                        'task/set-end-date',
                                        'id' => ($model->isRecurrentInstance()) ? $model->recurrence_parent_id : $model->id
                                    ]) . '&date=\' + ((date.getTime() / 1000) + (date.getTimezoneOffset() * -60))
                                }).done(function () {
                                    container.reload();
                                });
                            }')
                        ]
                    ]); ?>
                    <?= Html::button(
                        FAS::i('calendar-alt') . ' ' . Yii::$app->formatter->asDate($model->endDate, 'short'),
                        $options
                    ); ?>
                <?php endif; ?>
                <?php if ($model->ticket_id): ?>
                    <small class="text-muted me-3">
                        <?= FAS::i('headset'); ?>
                    </small>
                <?php endif; ?>
                <?php if ($model->feedback || $model->proposal): ?>
                    <small class="text-muted me-3">
                        <?= FAS::i('graduation-cap'); ?>
                    </small>
                <?php endif; ?>
                <?php if (count($model->comments)): ?>
                    <small class="text-muted me-3">
                        <?= FAR::i('comment-alt'); ?>
                    </small>
                <?php endif; ?>
                <?php if (count($model->attachments)): ?>
                    <small class="text-muted me-3">
                        <?= FAS::i('paperclip'); ?>
                        <?= count($model->attachments); ?>
                    </small>
                <?php endif; ?>
                <?php if (count($model->checklistElements)): ?>
                    <small class="text-muted me-3">
                        <?= FAR::i('check-square'); ?>
                        <?= $model->checklistStats; ?>
                    </small>
                <?php endif; ?>
                <?php if ($model->client_id): ?>
                    <small class="text-muted me-3">
                        <?= FAS::i('users'); ?>
                    </small>
                <?php endif; ?>
                <?php if ($model->children || $model->parent_id): ?>
                    <small class="text-muted me-3">
                        <?= FAS::i('diagram-subtask'); ?>
                    </small>
                <?php endif; ?>
                <?= Html::a(FAS::i('edit'), [
                    'task/update',
                    'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                    'return' => $model->isRecurrentInstance() ? 'bucket' : 'card',
                    'readonly' => $readonly
                ], [
                    'class' => ['btn', 'btn-sm', 'shadow-none', 'ms-auto', 'kanban-task-update-link', 'rounded-0', 'rounded-start'],
                    'data' => [
                        'pjax' => '0',
                        'turbo-frame' => 'task-modal-frame',
                        'bs-toggle' => 'modal',
                        'bs-target' => '#task-modal'
                    ]
                ]); ?>
                <?php
                $items = [
                    [
                        'label' => FAS::i('comment', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Add comment'),
                        'url' => [
                            'comment/create',
                            'taskId' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                            'group' => $group,
                            'readonly' => $readonly
                        ],
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('history', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'View history'),
                        'url' => [
                            'task/history',
                            'id' => $model->recurrence_parent_id
                        ],
                        'visible' => $model->isRecurrentInstance(),
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    '-',
                    [
                        'label' => FAS::i('edit', ['class' => ['me-1']])->fixedWidth() . ' ' . Yii::t('yii', 'Update'),
                        'url' => [
                            'task/update',
                            'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                            'return' => $model->isRecurrentInstance() ? 'bucket' : 'card',
                            'readonly' => $readonly
                        ],
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('pen-square', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Update series'),
                        'url' => [
                            'task/update',
                            'id' => $model->recurrence_parent_id,
                            'updateSeries' => true
                        ],
                        'visible' => $model->isRecurrentInstance(),
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('diagram-subtask', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Create sub task'),
                        'url' => [
                            'task/create-sub-task',
                            'id' => $model->id
                        ],
                        'visible' => !$model->isRecurrentInstance() && $model->recurrence_parent_id === null,
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    '-',
                    [
                        'label' => FAS::i('clone', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Copy task'),
                        'url' => [
                            'task/copy',
                            'id' => ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id),
                            'group' => $group
                        ],
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('up-down-left-right', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Move task'),
                        'url' => [
                            'task/move',
                            'id' => ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id),
                            'group' => $group
                        ],
                        'disabled' => $model->created_by != Yii::$app->user->id,
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('user-plus', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban/task', 'Create task per each user'),
                        'url' => [
                            'task/copy-per-user',
                            'id' => ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id),
                            'group' => $group,
                            'readonly' => $readonly
                        ],
                        'disabled' => $model->created_by != Yii::$app->user->id,
                        'linkOptions' => [
                            'data' => [
                                'pjax' => '0',
                                'turbo-frame' => 'task-modal-frame',
                                'bs-toggle' => 'modal',
                                'bs-target' => '#task-modal'
                            ]
                        ]
                    ],
                    '-',
                    [
                        'label' => FAS::i('link', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Copy link'),
                        'url' => 'javascript:;',
                        'linkOptions' => [
                            'onclick' => 'window.sa.kanban.copyTextToClipboard(\'' . Url::to([
                                    'plan/view',
                                    'id' => $boardId ?? $model->board->id,
                                    'showTask' => ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id),
                                    'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                                ], true) . '\')'
                        ]
                    ],
                    [
                        'label' => FAS::i('up-right-from-square', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'SharePoint customer dossier'),
                        'url' => $model->sharePointUrl,
                        'disabled' => ($model->sharePointUrl == false),
                        'linkOptions' => [
                            'target' => '_blank'
                        ]
                    ],
                    '-',
                    [
                        'label' => FAS::i('trash-alt', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('yii', 'Delete'),
                        'url' => [
                            'task/delete',
                            'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id
                        ],
                        'disabled' => $model->created_by != Yii::$app->user->id,
                        'linkOptions' => [
                            'data' => [
                                'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                'ajax' => 'true'
                            ]
                        ]
                    ],
                    [
                        'label' => FAS::i('trash', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Delete series'),
                        'url' => [
                            'task/delete',
                            'id' => $model->recurrence_parent_id,
                            'deleteSeries' => true
                        ],
                        'visible' => $model->isRecurrentInstance(),
                        'linkOptions' => [
                            'data' => [
                                'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                'ajax' => 'true'
                            ]
                        ]
                    ]
                ];
                if ($model->ticket_id) {
                    array_unshift($items, [
                        'label' => FAS::i('headset', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Go to ticket'),
                        'url' => ['/ticket/ticket/view', 'id' => $model->ticket_id],
                        'linkOptions' => [
                            'target' => '_blank'
                        ]
                    ]);
                }
                if ($model->feedback) {
                    array_unshift($items, [
                        'label' => FAS::i('graduation-cap', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Go to feedback'),
                        'url' => ['/knowledge/feedback/view', 'id' => $model->feedback->id],
                        'linkOptions' => [
                            'target' => '_blank'
                        ]
                    ]);
                }
                if ($model->proposal) {
                    array_unshift($items, [
                        'label' => FAS::i('graduation-cap', [
                            'class' => ['me-1']
                        ])->fixedWidth() . ' ' . Yii::t('simialbi/kanban', 'Go to proposal'),
                        'url' => ['/knowledge/proposal/view', 'id' => $model->proposal->id],
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
                        'class' => ['toggle' => '', 'btn' => 'btn btn-sm', 'shadow-none', 'rounded-0', 'rounded-end']
                    ],
                    'dropdown' => [
                        'items' => $items,
                        'encodeLabels' => false
                    ]
                ]); ?>
            </div>
        </div>

        <?php
        $responsible = $model->isRecurrentInstance() ? $model->getOriginalRecord()->responsible : $model->responsible;
        $assignees = $model->isRecurrentInstance() ? $model->getOriginalRecord()->assignees : $model->assignees;
        ?>
        <?php if (count($assignees) > 0 || $responsible): ?>
            <div class="kanban-task-assignees kanban-assignees card-footer p-0">
                <div class="d-flex">
                    <?php if ($responsible): ?>
                        <span class="kanban-user responsible border-end">
                        <?php if ($responsible->photo): ?>
                            <?= Html::img($responsible->photo, [
                                'class' => ['rounded-circle'],
                                'title' => Html::encode($responsible->name),
                                'data' => [
                                    'bs-toggle' => 'tooltip'
                                ]
                            ]); ?>
                        <?php else: ?>
                            <span class="kanban-visualisation me-1" title="<?= Html::encode($responsible->name); ?>"
                                  data-bs-toggle="tooltip">
                                <?= strtoupper(substr($responsible->name, 0, 1)); ?>
                            </span>
                        <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <div class="dropdown flex-grow-1">
                        <a href="javascript:;" <?php if ($model->status !== Task::STATUS_DONE){ echo 'data-bs-toggle="dropdown"'; } ?>
                           class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">
                            <?php
                            $showMax = 4;
                            if (!$responsible) {
                                $showMax = 6;
                            }
                            foreach ($assignees as $index => $assignee) {
                                ?>
                                <span class="kanban-user">
                                <?php if ($assignee->photo): ?>
                                    <?= Html::img($assignee->photo, [
                                        'class' => ['rounded-circle', 'me-1'],
                                        'title' => Html::encode($assignee->name),
                                        'data' => [
                                            'bs-toggle' => 'tooltip'
                                        ]
                                    ]); ?>
                                <?php else: ?>
                                    <span class="kanban-visualisation me-1"
                                          title="<?= Html::encode($assignee->name); ?>"
                                          data-bs-toggle="tooltip">
                                        <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                                </span>
                                <?php
                                if ($index === $showMax - 1) {
                                    break;
                                }
                            }
                            if ($showMax < count($model->assignees)) {
                                echo Html::tag('span', '+ ' . (count($model->assignees) - $showMax), [
                                    'class' => ['kanban-user-more', 'flex-grow-1', 'text-center']
                                ]);
                            }
                            ?>
                        </a>
                        <?php
                        if ($model->status !== Task::STATUS_DONE) { // TODO
                            $dependency = new DbQueryDependency([
                                'query' => (new Query())
                                    ->select(['SUM(user_id)'])
                                    ->from(TaskUserAssignment::tableName())
                                    ->where([
                                        'task_id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id
                                    ])
                            ]);

                            $key = 'kanban-users-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id);
    //                        Yii::$app->cache->delete($key);
                            $items = Yii::$app->cache->getOrSet($key, function () use ($model, $users, $readonly) {
                                $assignees = [];
                                $newUsers = [];
                                $assigned = $model->isRecurrentInstance() ? $model->getOriginalRecord()->assignees : $model->assignees;
                                foreach ($assigned as $assignee) {
                                    $assignees[] = [
                                        'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
                                            'user' => $assignee,
                                            'assigned' => true
                                        ]),
                                        'linkOptions' => [
                                            'class' => ['align-items-center', 'remove-assignee', 'is-assigned'],
                                            'data' => [
                                                'turbo-frame' => 'task-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-frame',
                                                'turbo' => 'true'
                                            ]
                                        ],
                                        'disabled' => $model->created_by != Yii::$app->user->id,
                                        'url' => [
                                            'task/expel-user',
                                            'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                                            'userId' => $assignee->getId(),
                                            'readonly' => $readonly
                                        ]
                                    ];
                                }

                                $tmpAssignees = ArrayHelper::index($model->assignees, 'id');
                                $diff = array_diff_key($users, $tmpAssignees);
                                foreach ($diff as $user) {
                                    $newUsers[] = [
                                        'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
                                            'user' => $user,
                                            'assigned' => false
                                        ]),
                                        'linkOptions' => [
                                            'class' => ['align-items-center', 'add-assignee'],
                                            'data' => [
                                                'turbo-frame' => 'task-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id) . '-frame',
                                                'turbo' => 'true'
                                            ]
                                        ],
            //                            'disabled' => $model->created_by != Yii::$app->user->id,
                                        'url' => [
                                            'task/assign-user',
                                            'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                                            'userId' => $user->getId(),
                                            'readonly' => $readonly
                                        ]
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
                                return array_merge($items, $newUsers);
                            }, 3600, $dependency);

                            array_unshift($items, HideSeek::widget([
                                'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                                'options' => [
                                    'id' => 'kanban-footer-task-assignees-' . $group . '-' . $model->hash,
                                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                                ],
                                'clientOptions' => [
                                    'list' => '.kanban-footer-task-assignees-' . $group . '-' . $model->hash,
                                    'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                                ]
                            ]));

                            echo Dropdown::widget([
                                'id' => 'dropdown-item-' . ($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id),
                                'items' => $items,
                                'encodeLabels' => false,
                                'options' => [
                                    'class' => ['kanban-footer-task-assignees-' . $group . '-' . $model->hash, 'w-100']
                                ],
                                'clientEvents' => [
                                    'shown.bs.dropdown' => new JsExpression('function(e) {
                                        $(e.target).closest(".dropdown").find(".search-field input").trigger("focus");
                                    }'),
                                ]
                            ]);
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>
        <?php if ($closeModal): ?>
        jQuery('#task-modal').modal('hide');
        <?php endif; ?>
        if (window.sa && window.sa.kanban) {
            window.sa.kanban.initTask('#task-<?=($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id);?>-frame');
        }
    </script>
<?php
Frame::end();
