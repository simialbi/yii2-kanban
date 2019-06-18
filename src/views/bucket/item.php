<?php

use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $statuses array */

?>

<div class="kanban-bucket mr-4 d-flex flex-column">
    <h5><?= Html::encode($model->name); ?></h5>

    <?php Pjax::begin([
        'id' => 'createTaskPjax',
        'formSelector' => '#createTaskForm',
        'enablePushState' => false,
        'options' => ['class' => ['d-flex', 'flex-column', 'flex-grow-1']],
        'clientOptions' => ['skipOuterContainers' => true]
    ]); ?>
    <?= Html::a('+', ['task/create', 'bucketId' => $model->id], [
        'class' => ['btn', 'btn-primary', 'btn-block']
    ]); ?>

    <div class="kanban-tasks mt-5 flex-grow-1">
        <?php foreach ($model->tasks as $task): ?>
            <?= $this->render('/task/item', [
                'model' => $task,
                'statuses' => $statuses
            ]); ?>
        <?php endforeach; ?>
    </div>
    <?php Pjax::end(); ?>
</div>
