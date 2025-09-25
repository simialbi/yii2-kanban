<?php

use simialbi\yii2\kanban\models\Bucket;
use yii\web\View;

/* @var $this View */
/* @var $model Bucket */
/* @var $statuses array */
/* @var $users array */
/* @var $readonly boolean */

foreach ($model->finishedTasks as $task) {
    echo $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/item.php'), [
        'boardId' => $model->board_id,
        'model' => $task,
        'statuses' => $statuses,
        'users' => $users,
        'closeModal' => false,
        'readonly' => $readonly,
        'group' => 'bucket'
    ]);
}
