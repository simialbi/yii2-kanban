<?php

use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $statuses array */
/* @var $users \simialbi\yii2\kanban\models\UserInterface[] */
/* @var $readonly boolean */
?>

<?php foreach ($model->buckets as $bucket): ?>
    <?= $this->render('/bucket/_item', [
        'readonly' => $readonly,
        'statuses' => $statuses,
        'users' => $users,
        'id' => $bucket->id,
        'boardId' => $model->id,
        'title' => $bucket->name,
        'tasks' => $bucket->getOpenTasks($readonly)->all(),
        'completedTasks' => $bucket->getFinishedTasks($readonly)->all(),
        'keyName' => 'bucketId',
        'action' => 'change-parent',
        'sort' => true,
        'renderContext' => true,
    ]); ?>
<?php endforeach; ?>
<?php if (!$readonly): ?>
    <?php Pjax::begin([
        'id' => 'createBucketPjax',
        'formSelector' => '#createBucketForm',
        'enablePushState' => false,
        'options' => [
            'class' => ['d-none', 'd-md-block']
        ],
        'clientOptions' => ['skipOuterContainers' => true]
    ]); ?>
    <div class="kanban-bucket">
        <h5>
            <?= Html::a(Yii::t('simialbi/kanban/plan', 'Create bucket'), [
                'bucket/create',
                'boardId' => $model->id
            ]); ?>
        </h5>
    </div>
    <?php Pjax::end(); ?>
<?php endif; ?>
