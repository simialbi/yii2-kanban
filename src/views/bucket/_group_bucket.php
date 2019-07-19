<?php

use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $statuses array */
?>

<?php foreach ($model->buckets as $bucket): ?>
    <?= $this->render('/bucket/_item', [
        'statuses' => $statuses,
        'id' => $bucket->id,
        'boardId' => $model->id,
        'title' => $bucket->name,
        'tasks' => $bucket->openTasks,
        'completedTasks' => $bucket->finishedTasks,
        'keyName' => 'bucketId',
        'action' => 'change-parent',
        'sort' => true,
        'renderContext' => true,
    ]); ?>
<?php endforeach; ?>
<?php Pjax::begin([
    'id' => 'createBucketPjax',
    'formSelector' => '#createBucketForm',
    'enablePushState' => false,
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
