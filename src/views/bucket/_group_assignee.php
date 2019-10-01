<?php

use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board|null */
/* @var $tasksByUser array */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */


foreach ($tasksByUser as $userId => $tasks) {
    /* @var $user \simialbi\yii2\models\UserInterface */
    $user = ArrayHelper::getValue($users, $userId);

    echo $this->render('/bucket/_item', [
        'readonly' => $readonly,
        'statuses' => $statuses,
        'users' => $users,
        'id' => $userId,
        'boardId' => $model ? $model->id : null,
        'title' => empty($userId)
            ? '<span class="kanban-user">' . Yii::t('simialbi/kanban', 'Not assigned') . '</span>'
            : $this->render('/task/_user', [
                'assigned' => false,
                'user' => $user
            ]),
        'tasks' => ArrayHelper::getValue($tasks, 0, []),
        'completedTasks' => ArrayHelper::getValue($tasks, 1, []),
        'keyName' => 'userId',
        'action' => 'change-assignee',
        'sort' => false,
        'renderContext' => false
    ]);
}
