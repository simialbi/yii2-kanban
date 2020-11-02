<?php

use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $buckets string */

Pjax::begin([
    'id' => 'plan' . $model->id . 'Pjax',
    'formSelector' => '#searchTasksForm',
    'enablePushState' => false,
    'clientOptions' => ['skipOuterContainers' => true],
    'options' => [
        'class' => ['d-flex', 'flex-row', 'kanban-plan-sortable']
    ]
]);
echo $buckets;
Pjax::end();
