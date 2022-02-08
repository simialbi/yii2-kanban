<?php

use simialbi\yii2\turbo\Frame;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $statuses array */
/* @var $users array */
/* @var $readonly boolean */

Frame::begin([
    'options' => [
        'id' => 'bucket-' . $model->id . '-finished-frame'
    ]
]);

/** @var \simialbi\yii2\kanban\models\Task $task */
foreach ($model->finishedTasks as $task) {
    echo $this->render('/task/item', [
        'boardId' => $model->board_id,
        'model' => $task,
        'statuses' => $statuses,
        'users' => $users,
        'closeModal' => false,
        'readonly' => $readonly,
        'group' => 'bucket'
    ]);
}

Frame::end();
