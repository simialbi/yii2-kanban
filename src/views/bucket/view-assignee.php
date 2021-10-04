<?php

use simialbi\yii2\turbo\Frame;
use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $tasks \simialbi\yii2\kanban\models\Task[]  */
/* @var $user array|null */
/* @var $users array */
/* @var $statuses array */

$id = ArrayHelper::getValue($user, 'id', '');

Frame::begin([
    'options' => [
        'id' => 'bucket-assignee-' . $id . '-frame',
        'class' => ['kanban-bucket', 'mr-md-4', 'pb-6', 'pb-md-0', 'd-flex', 'flex-column', 'flex-shrink-0'],
        'data' => ['id' => $id, 'action' => 'change-parent', 'key-name' => 'bucket_id', 'sort' => 'true']
    ]
]);

echo $this->render('_header', [
    'id' => $id,
    'title' => empty($id)
        ? '<span class="kanban-user">' . Yii::t('simialbi/kanban', 'Not assigned') . '</span>'
        : $this->render('/task/_user', [
            'assigned' => false,
            'user' => $user
        ])
]);
?>

<div class="kanban-tasks flex-grow-1 mt-4">
    <?php
    /** @var \simialbi\yii2\kanban\models\Task $task */
    foreach ($tasks as $task) {
        echo $this->render('/task/item', [
            'boardId' => $task->bucket->board_id,
            'model' => $task,
            'statuses' => $statuses,
            'users' => $users,
            'closeModal' => false
        ]);
    }
    ?>
</div>
<script>
    window.sa.kanban.updateSortable();
</script>
<?php

Frame::end();
