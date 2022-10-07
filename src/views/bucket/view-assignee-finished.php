<?php

use simialbi\yii2\turbo\Frame;

/* @var $this \yii\web\View */
/* @var $tasks \simialbi\yii2\kanban\models\Task[]  */
/* @var $id integer|null */
/* @var $boardId integer */
/* @var $user array|null */
/* @var $users array */
/* @var $statuses array */
/* @var $readonly boolean */

Frame::begin([
    'options' => [
        'id' => 'bucket-' . $id . '-finished-frame'
    ]
]);

/** @var \simialbi\yii2\kanban\models\Task $task */
foreach ($tasks as $task) {
    echo $this->render('/task/item', [
        'boardId' => $boardId,
        'model' => $task,
        'statuses' => $statuses,
        'users' => $users,
        'closeModal' => false,
        'readonly' => $readonly
    ]);
}

Frame::end();
