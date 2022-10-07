<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $statuses array */
/* @var $users array */
/* @var $closeModal boolean */
/* @var $finishedTasks int */
/* @var $readonly boolean */

if (!isset($closeModal)) {
    $closeModal = false;
}

Frame::begin([
    'options' => [
        'id' => 'bucket-' . $model->id . '-frame',
        'class' => ['kanban-bucket', 'mr-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
        'data' => ['id' => $model->id, 'action' => 'change-parent', 'key-name' => 'bucket_id', 'sort' => 'true']
    ]
]);
?>
<?= $this->render('_header', [
    'id' => $model->id,
    'title' => Html::tag('span', $model->name, [
        'class' => ['d-block', 'text-truncate']
    ]),
    'renderButtons' => true,
    'readonly' => $readonly,
]);
if (!$readonly):
    ?>
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
        <?= $this->render('/task/create', [
            'board' => $model->board,
            'id' => $model->id,
            'keyName' => 'bucketId',
            'task' => new \simialbi\yii2\kanban\models\Task(),
            'buckets' => [],
            'users' => $users
        ]); ?>
    </div>
    <?php
endif;
?>

    <div class="kanban-tasks flex-grow-1 mt-4">
        <?php
        /** @var \simialbi\yii2\kanban\models\Task $task */
        foreach ($model->openTasks as $task) {
            echo $this->render('/task/item', [
                'boardId' => $model->board_id,
                'model' => $task,
                'statuses' => $statuses,
                'users' => $users,
                'closeModal' => false,
                'group' => null,
                'readonly' => $readonly
            ]);
        }
        ?>
    </div>
    <script>
        <?php if ($closeModal) : ?>
        jQuery('#task-modal').modal('hide');
        <?php endif; ?>
        window.sa.kanban.updateSortable();
    </script>

<?php if ($finishedTasks): ?>
    <?= Html::a(Yii::t('simialbi/kanban', 'Show done ({cnt,number,integer})', [
        'cnt' => $finishedTasks
    ]), '#bucket-' . $model->id . '-finished-collapse', [
        'data' => [
            'toggle' => 'collapse'
        ]
    ]); ?>

    <div class="collapse" id="bucket-<?= $model->id; ?>-finished-collapse">
        <?= Frame::widget([
            'options' => [
                'id' => 'bucket-' . $model->id . '-finished-frame',
                'class' => [],
                'src' => Url::to(['bucket/view-finished', 'id' => $model->id, 'readonly' => $readonly])
            ],
            'lazyLoading' => true,
            'autoscroll' => true
        ]); ?>
    </div>
<?php endif; ?>
<?php
Frame::end();
