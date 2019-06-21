<?php

use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $tasksByStatus array */
/* @var $statuses array */

foreach ($tasksByStatus as $status => $tasks) {
    echo $this->render('/bucket/_item', [
        'statuses' => $statuses,
        'id' => $status,
        'boardId' => $model->id,
        'title' => ArrayHelper::getValue($statuses, $status, $status),
        'tasks' => $tasks,
        'keyName' => 'status',
        'action' => 'change-status',
        'sort' => false
    ]);
}
