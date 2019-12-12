<?php

use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\ButtonDropdown;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $delegated string */
/* @var $view string|null */

?>
<?php Pjax::begin([
    'id' => 'delegatedTasksPjax',
    'enablePushState' => false
]); ?>
<div class="mt-3">
    <?= ButtonDropdown::widget([
        'label'         => ($view === 'list')
            ? Yii::t('simialbi/kanban/plan', 'List view')
            : Yii::t('simialbi/kanban/plan', 'Task view'),
        'id'            => 'delegatedTasksView',
        'buttonOptions' => [
            'class' => ['btn-outline-secondary']
        ],
        'dropdown'      => [
            'items' => [
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Task view'),
                    'url' => ['task/view-delegated', 'view' => 'task']
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'List view'),
                    'url' => ['task/view-delegated', 'view' => 'list']
                ]
            ]
        ]
    ]); ?>
    <?php if ($view === 'list'): ?>
        <div class="card mt-2">
            <?= $delegated; ?>
        </div>
    <?php else: ?>
        <div class="kanban-plan-view">
            <div class="d-flex flex-column">
                <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
                    <div></div>
                </div>
                <div class="kanban-bottom-scrollbar">
                    <div class="d-flex flex-row kanban-plan-sortable">
                        <?= $delegated; ?>
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
