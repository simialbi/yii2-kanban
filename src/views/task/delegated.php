<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ButtonDropdown;

/* @var $this \yii\web\View */
/* @var $tasks array */
/* @var $view string|null */
/* @var $users array */
/* @var $statuses array */

Frame::begin([
    'options' => [
        'id' => 'delegated-tasks-frame'
    ]
]);
?>
<div class="mt-3">
    <div class="d-flex justify-content-between">
        <?= ButtonDropdown::widget([
            'label' => ($view === 'list')
                ? Yii::t('simialbi/kanban/plan', 'List view')
                : Yii::t('simialbi/kanban/plan', 'Task view'),
            'id' => 'delegatedTasksView',
            'options' => [
                'class' => ['mb-3']
            ],
            'buttonOptions' => [
                'class' => ['btn-outline-secondary']
            ],
            'dropdown' => [
                'items' => [
                    [
                        'label' => Yii::t('simialbi/kanban/plan', 'Task view'),
                        'url' => ['task/view-delegated', 'view' => 'task'],
                        'linkOptions' => [
                            'data' => [
                                'turbo' => 'true',
                                'turbo-frame' => 'delegated-tasks-frame'
                            ]
                        ]
                    ],
                    [
                        'label' => Yii::t('simialbi/kanban/plan', 'List view'),
                        'url' => ['task/view-delegated', 'view' => 'list'],
                        'linkOptions' => [
                            'data' => [
                                'turbo' => 'true',
                                'turbo-frame' => 'delegated-tasks-frame'
                            ]
                        ]
                    ]
                ]
            ]
        ]); ?>

        <?php
        if ($view === 'list') {
            $clientOptions = [
                'list' => '#delegated-tasks-frame .list-group',
                'attribute' => 'alt'
            ];
        }
        else {
            $clientOptions = [
                'list' => '.kanban-tasks',
                'attribute' => 'alt'
            ];
        }
        echo HideSeek::widget([
            'fieldTemplate' => '<div class="search-field mb-3">{input}</div>',
            'options' => [
                'id' => 'search-widget-delegated',
                'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                'autocomplete' => 'off'
            ],
            'clientOptions' => $clientOptions
        ]);
        ?>
    </div>

    <?php if ($view === 'list'): ?>
        <?php foreach ($tasks as $userId => $userTasks): ?>
            <?php if (isset($users[$userId])): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="card-title m-0">
                            <?= $this->render('_user', [
                                'assigned' => false,
                                'user' => $users[$userId]
                            ]); ?>
                        </h4>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php /** @var \simialbi\yii2\kanban\models\Task $task */ ?>
                        <?php foreach ($userTasks as $task): ?>
                            <?= $this->render('list-item', [
                                'task' => $task
                            ]); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="kanban-plan-view">
            <div class="d-flex flex-column">
                <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
                    <div></div>
                </div>
                <div class="kanban-bottom-scrollbar">
                    <div class="d-flex flex-row kanban-plan-sortable">
                        <?php foreach ($tasks as $userId => $userTasks): ?>
                            <?php if (isset($users[$userId])): ?>
                                <div class="kanban-bucket mr-md-4 d-flex flex-column flex-shrink-0">
                                    <?= $this->render('/bucket/_header', [
                                        'id' => $userId,
                                        'title' => $this->render('_user', [
                                            'assigned' => false,
                                            'user' => $users[$userId],
                                        ]),
                                        'renderButtons' => false,
                                        'readonly' => false
                                    ]); ?>
                                    <div class="kanban-tasks flex-grow-1 mt-4">
                                        <?php /** @var \simialbi\yii2\kanban\models\Task $task */ ?>
                                        <?php foreach ($userTasks as $task): ?>
                                            <?= $this->render('/task/item', [
                                                'boardId' => $task->bucket->board_id,
                                                'model' => $task,
                                                'statuses' => $statuses,
                                                'users' => $users,
                                                'closeModal' => false,
                                                'group' => 'bucket',
                                                'readonly' => false
                                            ]); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-md-none">
                        <div class="kanban-button-prev"><?= FAS::i('caret-left'); ?></div>
                        <div class="kanban-button-next"><?= FAS::i('caret-right'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
Frame::end();
