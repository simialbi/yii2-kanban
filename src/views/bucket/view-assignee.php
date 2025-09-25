<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $tasks Task[] */
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
        'class' => ['kanban-bucket', 'me-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
        'data' => ['id' => $id, 'action' => 'change-assignee', 'key-name' => 'user_id', 'sort' => 'false']
    ]
]);

echo $this->render('_header', [
    'id' => $id,
    'title' => empty($id)
        ? '<span class="kanban-user">' . Yii::t('simialbi/kanban', 'Not assigned') . '</span>'
        : $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
            'assigned' => false,
            'user' => $user
        ]),
    'renderButtons' => false,
    'readonly' => $readonly,
]);
?>

    <div class="kanban-tasks flex-grow-1 mt-4">
        <?php
        /** @var Task $task */
        foreach ($tasks as $task) {
            echo $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/item.php'), [
                'boardId' => $boardId,
                'model' => $task,
                'statuses' => $statuses,
                'users' => $users,
                'closeModal' => false,
                'group' => 'assignee',
                'readonly' => $readonly
            ]);
        }

        if ($finishedTasks): ?>
            <?= Html::a(Yii::t('simialbi/kanban', 'Show done ({cnt,number,integer})', [
                'cnt' => $finishedTasks
            ]), '#bucket-' . $id . '-finished-collapse', [
                'class' => ['d-block', 'p-2'],
                'data' => [
                    'bs-toggle' => 'collapse'
                ]
            ]); ?>

            <div class="collapse finished-tasks" id="bucket-<?= $id; ?>-finished-collapse">
                <?php
                $url = Url::to([
                    'bucket/view-assignee-finished',
                    'id' => $id,
                    'boardId' => $boardId,
                    'readonly' => $readonly
                ]);
                $scrollSelector = '#bucket-assignee-' . $id . '-frame .kanban-tasks';
                $containerSelector = '#bucket-' . $id . '-finished-collapse';
                ?>
            </div>
            <div class="py-1"></div>
            <script>
                window.sa.kanban.initDoneLazyLoading('<?= $url ?>', '<?= $scrollSelector ?>', '<?= $containerSelector ?>');
            </script>
        <?php endif; ?>
    </div>
    <script>
        window.sa.kanban.updateSortable();
    </script>

<?php
Frame::end();
