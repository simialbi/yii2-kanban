<?php

use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\widgets\Calendar;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $calendarTasks array */

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
    <div class="overflow-auto mt-5">
        <div class="d-flex flex-row">
            <?= Calendar::widget([
                'events' => $calendarTasks
            ]); ?>
        </div>
    </div>
</div>
