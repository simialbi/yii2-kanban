<?php

use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\widgets\Calendar;
use yii\bootstrap4\Modal;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $calendarTasks array */
/* @var $otherTasks string */

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
<div class="kanban-plan-view">
    <?= $this->render('_navigation', ['model' => $model]); ?>
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
                    'eventRender' => new JsExpression('function (info) {
                        jQuery(info.el).attr({
                            \'data-toggle\': \'modal\',
                            \'data-target\': \'#taskModal\'
                        });
                    }'),
                    'eventDrop' => new JsExpression('function (info) {
                        var start = (info.event.start instanceof Date)
                            ? info.event.start.getTime() / 1000
                            : null;
                        var end = (info.event.end instanceof Date)
                            ? info.event.end.getTime() / 1000
                            : null;
                        jQuery.post(\'' . \yii\helpers\Url::to(['task/set-dates']) . '?id=\' + info.event.id, {
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
                        jQuery.post(\'' . \yii\helpers\Url::to(['task/set-dates']) . '?id=\' + info.event.id, {
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
                        jQuery.post(\'' . \yii\helpers\Url::to(['task/set-dates']) . '?id=\' + info.event.id, {
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
            <?= $otherTasks; ?>
        </div>
    </div>
</div>
<?php
Modal::begin([
    'id' => 'taskModal',
    'options' => [
        'class' => ['modal', 'remote', 'fade']
    ],
    'clientOptions' => [
        'backdrop' => 'static',
        'keyboard' => false
    ],
    'size' => Modal::SIZE_LARGE,
    'title' => null,
    'closeButton' => false
]);
Modal::end();
