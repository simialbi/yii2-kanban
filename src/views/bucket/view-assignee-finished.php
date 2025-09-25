<?php

use simialbi\yii2\kanban\models\Task;
use yii\web\View;

/* @var $this View */
/* @var $tasks Task[]  */
/* @var $id integer|null */
/* @var $boardId integer */
/* @var $user array|null */
/* @var $users array */
/* @var $statuses array */
/* @var $readonly boolean */

foreach ($tasks as $task) {
    echo $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/item.php'), [
        'boardId' => $boardId,
        'model' => $task,
        'statuses' => $statuses,
        'users' => $users,
        'closeModal' => false,
        'readonly' => $readonly
    ]);
}
