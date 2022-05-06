<?php

use simialbi\yii2\kanban\widgets\ToDo;
use simialbi\yii2\turbo\Frame;

/* @var $this \yii\web\View */
/* @var $kanbanModuleName string */

echo ToDo::widget([
    'kanbanModuleName' => $kanbanModuleName,
    'renderModal' => false,
    'addBoardFilter' => true,
    'cacheDuration' => -1
]);
