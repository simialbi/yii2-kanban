<?php

use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $tasks \simialbi\yii2\kanban\models\Task[] */
/* @var $id integer|null */
/* @var $boardId integer */
/* @var $user array|null */
/* @var $users array */
/* @var $statuses array */
/* @var $finishedTasks int */
/* @var $readonly boolean */

Frame::begin([
    'options' => [
        'id' => 'bucket-assignee-' . $id . '-frame',
        'class' => ['kanban-bucket', 'mr-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
        'data' => ['id' => $id, 'action' => 'change-assignee', 'key-name' => 'user_id', 'sort' => 'false']
    ]
]);

echo $this->render('_header', [
    'id' => $id,
    'title' => empty($id)
        ? '<span class="kanban-user">' . Yii::t('simialbi/kanban', 'Not assigned') . '</span>'
        : $this->render('/task/_user', [
            'assigned' => false,
            'user' => $user
        ]),
    'renderButtons' => false,
    'readonly' => $readonly,
]);
?>

    <div class="kanban-tasks flex-grow-1 mt-4">
        <?php
        /** @var \simialbi\yii2\kanban\models\Task $task */
        foreach ($tasks as $task) {
            echo $this->render('/task/item', [
                'boardId' => $boardId,
                'model' => $task,
                'statuses' => $statuses,
                'users' => $users,
                'closeModal' => false,
                'group' => 'assignee',
                'readonly' => $readonly
            ]);
        }
        ?>
    </div>
    <script>
        window.sa.kanban.updateSortable();
    </script>
<?php if ($finishedTasks): ?>
    <?= Html::a(Yii::t('simialbi/kanban', 'Show done ({cnt,number,integer})', [
        'cnt' => $finishedTasks
    ]), '#bucket-' . $id . '-finished-collapse', [
        'data' => [
            'toggle' => 'collapse'
        ]
    ]); ?>

    <div class="collapse" id="bucket-<?= $id; ?>-finished-collapse">
        <?= Frame::widget([
            'options' => [
                'id' => 'bucket-' . $id . '-finished-frame',
                'class' => [],
                'src' => Url::to([
                    'bucket/view-assignee-finished',
                    'id' => $id,
                    'boardId' => $boardId,
                    'readonly' => $readonly
                ])
            ],
            'lazyLoading' => true,
            'autoscroll' => true
        ]); ?>
    </div>
<?php endif;

Frame::end();
