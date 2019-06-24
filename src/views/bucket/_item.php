<?php

use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $id string|integer */
/* @var $action string */
/* @var $boardId integer */
/* @var $title string */
/* @var $keyName string */
/* @var $tasks \simialbi\yii2\kanban\models\Task[] */
/* @var $statuses array */
/* @var $sort boolean */

?>

<div class="kanban-bucket mr-4 d-flex flex-column" data-id="<?= $id; ?>"
     data-sort="<?= $sort ? 'true' : 'false'; ?>" data-action="<?= $action; ?>"
     data-key-name="<?= \yii\helpers\Inflector::camel2id($keyName, '_'); ?>">
    <h5><?= $title; ?></h5>

    <?php Pjax::begin([
        'id' => 'createTaskPjax' . \yii\helpers\Inflector::slug($title),
        'formSelector' => '#createTaskForm',
        'enablePushState' => false,
        'clientOptions' => ['skipOuterContainers' => true]
    ]); ?>
    <?= Html::a('+', ['task/create', 'boardId' => $boardId, $keyName => $id], [
        'class' => ['btn', 'btn-primary', 'btn-block']
    ]); ?>
    <?php Pjax::end(); ?>

    <div class="kanban-tasks mt-4 flex-grow-1">
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
</div>
