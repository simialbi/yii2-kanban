<?php

use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\helpers\ArrayHelper;
use yii\web\View;

/* @var $this View */
/* @var $tasks Task[]  */
/* @var $status integer */
/* @var $users array */
/* @var $statuses array */
/* @var $closeModal boolean */
/* @var $readonly boolean */

if (!isset($closeModal)) {
    $closeModal = false;
}

Frame::begin([
    'options' => [
        'id' => 'bucket-status-' . $status . '-frame',
        'class' => ['kanban-bucket', 'me-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
        'data' => ['id' => $status, 'action' => 'change-status', 'key-name' => 'status', 'sort' => 'false']
    ]
]);

echo $this->render('_header', [
    'id' => $status,
    'title' => ArrayHelper::getValue($statuses, $status, $status),
    'renderButtons' => false,
    'readonly' => $readonly,
]);
?>

<div class="kanban-tasks flex-grow-1 mt-4">
    <?php
    /** @var Task $task */
    foreach ($tasks as $task) {
        echo $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/item.php'), [
            'boardId' => $task->bucket->board_id,
            'model' => $task,
            'statuses' => $statuses,
            'users' => $users,
            'closeModal' => false,
            'group' => 'status',
            'readonly' => $readonly
        ]);
    }
    ?>
</div>
<script>
    <?php if ($closeModal): ?>
        jQuery('#task-modal').modal('hide');
    <?php endif; ?>
    window.sa.kanban.updateSortable();
</script>
<?php

Frame::end();
