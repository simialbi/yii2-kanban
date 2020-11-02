<?php

use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board|null */
/* @var $tasksByUser array */
/* @var $doneTasksByUser array */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */
/* @var $isFiltered boolean */

foreach ($tasksByUser as $userId => $tasks) {
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
                'user' => ArrayHelper::getValue($users, $userId)
            ]),
        'tasks' => is_array($tasks) ? $tasks : [],
        'completedTasks' => ArrayHelper::getValue($doneTasksByUser, $userId, $isFiltered ? [] : 0),
        'keyName' => 'userId',
        'action' => 'change-assignee',
        'sort' => false,
        'renderContext' => false
    ]);
}
