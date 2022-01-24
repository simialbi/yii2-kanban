<?php

/** @var Task $task */

use rmrevin\yii\fontawesome\FAR;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\helpers\Html;
use yii\helpers\Url;

Frame::begin([
    'options' => [
        'class' => 'test',
        'id' => 'task-' . ($task->isRecurrentInstance() ? $task->recurrence_parent_id : $task->id) . '-frame',
        'alt' => $task->subject . ' ' . str_replace(["\r", "\n"], ' ', strip_tags($task->description))
    ]
]);
?>

<a href="<?= Url::to(['task/update', 'id' => $task->id, 'return' => 'list-item']); ?>" data-toggle="modal"
   data-target="#task-modal"
   data-turbo-frame="task-modal-frame"
   class="list-group-item list-group-item-action<?php if ($task->end_date && $task->end_date < time()) {
       echo " list-group-item-danger";
   } ?>">
    <h6 class="m-0"><?= Html::encode($task->subject); ?></h6>
    <small>
        <?= $task->board->name; ?>
        <?php if ($count = count($task->checklistElements)): ?>
            &nbsp;&bull;&nbsp;<?= $task->getChecklistStats(); ?>
        <?php endif; ?>
        <?php if ($task->end_date): ?>
            &nbsp;&bull;&nbsp; <?= FAR::i('calendar'); ?> <?= Yii::$app->formatter->asDate($task->end_date,
                'short'); ?>
        <?php endif; ?>
        <?php if (count($task->comments)): ?>
            &nbsp;&bull;&nbsp; <?= FAR::i('sticky-note'); ?>
        <?php endif; ?>
    </small>
</a>

<script class="ignore">jQuery('#task-modal').modal('hide');</script>
<?php Frame::end(); ?>
