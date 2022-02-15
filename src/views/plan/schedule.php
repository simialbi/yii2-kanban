<?php

use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\widgets\Calendar;
use simialbi\yii2\turbo\Modal;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $calendarTasks array */
/* @var $otherTasks \simialbi\yii2\kanban\models\Task[] */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $statuses array */
/* @var $readonly boolean */

KanbanAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'),
        'url' => ['index']
    ],
    $this->title
];
?>
<div class="kanban-plan-schedule">
    <?= $this->render('_navigation', [
        'boards' => [],
        'model' => $model,
        'users' => $users,
        'readonly' => $readonly
    ]); ?>
    <div class="mt-5 row">
        <div class="col-12 col-lg-9">
            <?= Calendar::widget([
                'events' => $calendarTasks,
                'draggable' => [
                    'selector' => new JsExpression('jQuery(\'#kanban-buckets\').get(0)'),
                    'itemSelector' => '.kanban-sortable'
                ],
                'clientOptions' => [
                    'plugins' => ['interaction'],
                    'editable' => true,
                    'droppable' => true,
                    'buttonText' => [
                        'next' => '→',
                        'prev' => '←'
                    ],
                    'eventRender' => new JsExpression('function (info) {
                        jQuery(info.el).attr({
                            \'data-turbo-frame\': \'task-modal-frame\',
                            \'data-toggle\': \'modal\',
                            \'data-target\': \'#task-modal\'
                        });
                    }'),
                    'eventDrop' => new JsExpression('function (info) {
                        var start = (info.event.start instanceof Date)
                            ? info.event.start.getTime() / 1000
                            : null;
                        var end = (info.event.end instanceof Date)
                            ? info.event.end.getTime() / 1000
                            : null;
                        jQuery.post(\'' . Url::to(['task/set-dates']) . '?id=\' + info.event.id, {
                            startDate: start,
                            endDate: end
                        });
                    }'),
                    'eventResize' =>  new JsExpression('function (info) {
                        var start = (info.event.start instanceof Date)
                            ? info.event.start.getTime() / 1000
                            : null;
                        var end = (info.event.end instanceof Date)
                            ? info.event.end.getTime() / 1000
                            : null;
                        jQuery.post(\'' . Url::to(['task/set-dates']) . '?id=\' + info.event.id, {
                            startDate: start,
                            endDate: end
                        });
                    }'),
                    'eventReceive' => new JsExpression('function (info) {
                        var start = (info.event.start instanceof Date)
                            ? info.event.start.getTime() / 1000
                            : null;
                        var end = (info.event.end instanceof Date)
                            ? info.event.end.getTime() / 1000
                            : null;
                        jQuery.post(\'' . Url::to(['task/set-dates']) . '?id=\' + info.event.id, {
                            startDate: start,
                            endDate: end
                        }).done(function () {
                            jQuery(info.draggedEl).remove();
                        });
                    }')
                ]
            ]); ?>
        </div>
        <div class="d-none d-lg-block col-lg-3" id="kanban-buckets">
            <?php $lastBucket = null; ?>
            <?php foreach ($otherTasks as $task): ?>
                <?php if ($lastBucket !== $task->bucket_id): ?>
                    <?php if ($lastBucket !== null): ?>
                        <?= '</div>'; ?>
                    <?php endif; ?>
            <div class="kanban-bucket mb-4 w-100">
                <h5><?= $task->bucket->name; ?></h5>
                <?php endif; ?>
                <?= $this->render('/task/item', [
                    'statuses' => $statuses,
                    'boardId' => $model->id,
                    'closeModal' => false,
                    'model' => $task,
                    'users' => $users,
                    'readonly' => $readonly
                ]); ?>
                <?php $lastBucket = $task->bucket_id; ?>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
Modal::begin([
    'options' => [
        'id' => 'task-modal',
        'options' => [
        'class' => ['modal', 'remote', 'fade']
            ],
        'clientOptions' => [
            'backdrop' => 'static',
            'keyboard' => false
        ],
        'size' => \yii\bootstrap4\Modal::SIZE_EXTRA_LARGE,
        'title' => null,
        'closeButton' => false
    ]
]);
Modal::end();
