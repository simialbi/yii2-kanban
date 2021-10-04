<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $statuses array */
/* @var $users array */

Frame::begin([
    'options' => [
        'id' => 'bucket-' . $model->id . '-frame',
        'class' => ['kanban-bucket', 'mr-md-4', 'pb-6', 'pb-md-0', 'd-flex', 'flex-column', 'flex-shrink-0'],
        'data' => ['id' => $model->id, 'action' => 'change-parent', 'key-name' => 'bucket_id', 'sort' => 'true']
    ]
]);
?>
    <?= $this->render('_header', [
        'id' => $model->id,
        'title' => $model->name
    ]) ?>
    <?= Html::a(FAS::i('plus'), '#bucket-' . $model->id . '-create-task', [
        'class' => ['btn', 'btn-primary', 'btn-block'],
        'role' => 'button',
        'aria' => [
            'expanded' => 'false',
            'controls' => 'bucket-' . $model->id . '-create-task'
        ],
        'data' => [
            'toggle' => 'collapse'
        ]
    ]); ?>
    <div class="collapse" id="bucket-<?= $model->id; ?>-create-task">
        <?= $this->renderAjax('/task/create', [
            'board' => $model->board,
            'id' => $model->id,
            'keyName' => 'bucketId',
            'bucketName' => $model->name,
            'mobile' => false,
            'task' => new \simialbi\yii2\kanban\models\Task(),
            'buckets' => [],
            'statuses' => $statuses,
            'users' => $users
        ]); ?>
    </div>

    <div class="kanban-tasks flex-grow-1 mt-4">
        <?php
        /** @var \simialbi\yii2\kanban\models\Task $task */
        foreach ($model->openTasks as $task) {
            echo $this->render('/task/item', [
                'boardId' => $model->board_id,
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
