<?php

use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $tasksByStatus array */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */

foreach ($tasksByStatus as $status => $tasks) {
    echo $this->render('/bucket/_item', [
        'readonly' => $readonly,
        'statuses' => $statuses,
        'users' => $users,
        'id' => $status,
        'boardId' => $model->id,
        'title' => ArrayHelper::getValue($statuses, $status, $status),
        'tasks' => $tasks,
        'completedTasks' => [],
        'keyName' => 'status',
        'action' => 'change-status',
        'sort' => false,
        'renderContext' => false
    ]);
}
