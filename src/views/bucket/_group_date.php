<?php

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $tasksByDate array */
/* @var $statuses array */

foreach ($tasksByDate as $date => $tasks) {
    if (empty($date)) {
        $date = null;
    }

    echo $this->render('/bucket/_item', [
        'statuses' => $statuses,
        'id' => $date === null ? '' : $date,
        'boardId' => $model->id,
        'title' => Yii::$app->formatter->asDate($date),
        'tasks' => $tasks,
        'keyName' => 'date',
        'action' => 'change-date',
        'sort' => false,
        'renderContext' => false
    ]);
}
