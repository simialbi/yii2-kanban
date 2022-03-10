<?php

/** @var Task $task */

use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\helpers\Html;
use yii\helpers\Url;

$class = ['list-group-item', 'list-group-item-action'];
if ($task->endDate && $task->endDate < time()) {
    $class[] = 'list-group-item-danger';
}

Frame::begin([
    'options' => [
        'class' => $class,
        'href' => Url::to(['task/update', 'id' => ($task->isRecurrentInstance() ? $task->recurrence_parent_id : $task->id), 'return' => 'list-item']),
        'id' => 'task-' . ($task->isRecurrentInstance() ? $task->recurrence_parent_id : $task->id) . '-frame',
        'alt' => $task->subject . ' ' . str_replace(["\r", "\n"], ' ', strip_tags($task->description)),
        'data' => [
            'target' => '#task-modal',
            'toggle' => 'modal',
            'turbo-frame' => 'task-modal-frame'
        ]
    ]
]);
?>

    <h6 class="m-0">
        <?php if ($task->isRecurrentInstance()): ?>
            <?= FAS::i('infinity', [
                'fa-transform' => 'shrink-4.5',
                'fa-mask' => 'fas fa-circle'
            ]); ?>
        <?php endif; ?>
        <?= Html::encode($task->subject); ?></h6>
    <small>
        <?= $task->bucket->name; ?>
        <?php if ($count = count($task->checklistElements)): ?>
            &nbsp;&bull;&nbsp;<?= $task->checklistStats; ?>
        <?php endif; ?>
        <?php if ($task->end_date): ?>
            &nbsp;&bull;&nbsp; <?= FAR::i('calendar'); ?>
            <?= Yii::$app->formatter->asDate($task->end_date, 'short'); ?>
        <?php endif; ?>
        <?php if (count($task->comments)): ?>
            &nbsp;&bull;&nbsp; <?= FAR::i('sticky-note'); ?>
        <?php endif; ?>
    </small>

<script class="ignore">jQuery('#task-modal').modal('hide');</script>
<?php
Frame::end();
