<?php

use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $id string */
/* @var $title string */
/* @var $keyName string */
/* @var $tasks \simialbi\yii2\kanban\models\Task[] */
/* @var $statuses array */

?>

<div class="kanban-bucket mr-4 d-flex flex-column" data-id="<?= $id; ?>">
    <h5><?= Html::encode($title); ?></h5>

    <?php Pjax::begin([
        'id' => 'createTaskPjax',
        'formSelector' => '#createTaskForm',
        'enablePushState' => false,
        'options' => ['class' => ['d-flex', 'flex-column', 'flex-grow-1']],
        'clientOptions' => ['skipOuterContainers' => true]
    ]); ?>
    <?= Html::a('+', ['task/create', $keyName => $id], [
        'class' => ['btn', 'btn-primary', 'btn-block']
    ]); ?>

    <div class="kanban-tasks mt-5 flex-grow-1">
        <?php foreach ($tasks as $task): ?>
            <?php if (is_array($task)): ?>
                <?php $t = new \simialbi\yii2\kanban\models\Task(); ?>
                <?php $t->setAttributes($task); ?>
                <?php $task = $t; ?>
            <?php endif; ?>
            <?= $this->render('/task/item', [
                'model' => $task,
                'statuses' => $statuses
            ]); ?>
        <?php endforeach; ?>
    </div>
    <?php Pjax::end(); ?>
</div>
