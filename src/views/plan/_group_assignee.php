<?php

use simialbi\yii2\kanban\models\Task;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $tasksByUser array */
/* @var $statuses array */

foreach ($tasksByUser as $userId => $tasks) {
    /* @var $user \simialbi\yii2\kanban\models\UserInterface */
    $user = call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $userId);

    echo $this->render('/bucket/_item', [
        'statuses' => $statuses,
        'id' => $userId,
        'title' => $user->name,
        'tasks' => $tasks,
        'keyName' => 'userId'
    ]);
}
