<?php

use simialbi\yii2\kanban\widgets\ToDo;
use yii\web\View;

/* @var $this View */
/* @var $kanbanModuleName string */
/* @var $renderModal null|boolean */
/* @var $addBoardFilter null|boolean */
/* @var $cacheDuration null|integer */

echo ToDo::widget([
    'kanbanModuleName' => $kanbanModuleName,
    'renderModal' => $renderModal ?? false,
    'addBoardFilter' => $addBoardFilter ?? true,
    'cacheDuration' => $cacheDuration ?? -1
]);
