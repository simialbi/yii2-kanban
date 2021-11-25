<?php

use simialbi\yii2\kanban\widgets\ToDo;
use simialbi\yii2\turbo\Frame;

/* @var $this \yii\web\View */
/* @var $kanbanModuleName string */

Frame::begin([
    'options' => [
        'id' => 'kanban-todo-frame'
    ]
]);
echo ToDo::widget([
    'kanbanModuleName' => $kanbanModuleName,
    'renderModal' => false
]);
?>
    <script>jQuery('#task-modal').modal('hide');</script>
<?php
Frame::end();
