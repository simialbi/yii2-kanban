<?php

use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\helpers\Url;

/** @var Task $task */
/** @var string $kanbanModuleName */

Frame::begin([
    'options' => [
        'id' => 'js-turbo-frame'
    ]
]);
$url = Url::to(["/$kanbanModuleName/task/todo"]);
?>
    <script class="ignore">
        jQuery('#task-modal').modal('hide');
        window.calendar.refetchEvents();
        var frame = $(".kanban-todo-frame");
        if (frame.length) {
            frame.attr("src", '<?=$url?>');
        }
    </script>
<?php
Frame::end();
