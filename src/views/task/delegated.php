<?php

use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ButtonDropdown;
use yii\helpers\Html;
use yii\helpers\Url;

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
    <?= ButtonDropdown::widget([
        'label' => ($view === 'list')
            ? Yii::t('simialbi/kanban/plan', 'List view')
            : Yii::t('simialbi/kanban/plan', 'Task view'),
        'id' => 'delegatedTasksView',
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
    <?php if ($view === 'list'): ?>
        <div class="card mt-2">
            <?php foreach ($tasks as $userId => $userTasks): ?>
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

                        <a href="<?= Url::to(['task/update', 'id' => $task->id]); ?>" data-toggle="modal" data-target="#taskModal"
                           class="list-group-item list-group-item-action<?php if ($task->end_date && $task->end_date < time()) { echo " list-group-item-danger"; } ?>">
                            <h6 class="m-0"><?= Html::encode($task->subject); ?></h6>
                            <small>
                                <?= $task->board->name; ?>
                                <?php if ($count = count($task->checklistElements)): ?>
                                    &nbsp;&bull;&nbsp;<?= $task->getChecklistStats(); ?>
                                <?php endif; ?>
                                <?php if ($task->end_date): ?>
                                    &nbsp;&bull;&nbsp; <?= FAR::i('calendar'); ?> <?= Yii::$app->formatter->asDate($task->end_date, 'short'); ?>
                                <?php endif; ?>
                                <?php if (count($task->comments)): ?>
                                    &nbsp;&bull;&nbsp; <?= FAR::i('sticky-note'); ?>
                                <?php endif; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="kanban-plan-view">
            <div class="d-flex flex-column">
                <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
                    <div></div>
                </div>
                <div class="kanban-bottom-scrollbar">
                    <div class="d-flex flex-row kanban-plan-sortable">
                        <?php foreach ($tasks as $userId => $userTasks): ?>
                            <div class="kanban-bucket mr-md-4 pb-6 pb-md-0 d-flex flex-column flex-shrink-0">
                                <?= $this->render('/bucket/_header', [
                                    'id' => $userId,
                                    'title' => $this->render('_user', [
                                        'assigned' => false,
                                        'user' => $users[$userId]
                                    ])
                                ]); ?>
                                <div class="kanban-tasks flex-grow-1 mt-4">
                                    <?php /** @var \simialbi\yii2\kanban\models\Task $task */ ?>
                                    <?php foreach ($userTasks as $task): ?>
                                        <?= $this->render('/task/item', [
                                            'boardId' => $task->bucket->board_id,
                                            'model' => $task,
                                            'statuses' => $statuses,
                                            'users' => $users,
                                            'closeModal' => false
                                        ]); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
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
