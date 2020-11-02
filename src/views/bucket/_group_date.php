<?php

use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $tasksByDate array */
/* @var $doneTasksByDate array */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */

foreach ($tasksByDate as $date => $tasks) {
    if (empty($date)) {
        $date = null;
    }

    echo $this->render('/bucket/_item', [
        'readonly' => $readonly,
        'statuses' => $statuses,
        'users' => $users,
        'id' => $date === null ? '' : $date,
        'boardId' => $model->id,
        'title' => Yii::$app->formatter->asDate($date),
        'tasks' => $tasks,
        'completedTasks' => ArrayHelper::getValue($doneTasksByDate, Yii::$app->formatter->asDate($date, 'yyyy-MM-dd'), 0),
        'keyName' => 'date',
        'action' => 'change-date',
        'sort' => false,
        'renderContext' => false
    ]);
}
