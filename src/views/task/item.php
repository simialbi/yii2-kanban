<?php

use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model Task */

?>

<div class="kanban-task mt-2 card<?php if ($model->status === Task::STATUS_DONE): ?> status-done<?php endif; ?>">
    <div class="card-body">
        <h6 class="card-title"><?= Html::encode($model->subject); ?></h6>
        <?php if ($model->card_show_description && $model->description): ?>
            <p class="card-text kanban-task-description"><?= Html::encode($model->description); ?></p>
        <?php endif; ?>
        <?php if ($model->card_show_checklist && count($model->checklistElements)): ?>
            <!-- TODO -->
        <?php endif; ?>
        <div class="kanban-task-info d-flex flex-row">
            <?php if ($model->status === Task::STATUS_IN_PROGRESS): ?>
            <?php endif; ?>
            <?php if (count($model->checklistElements)): ?>
                <small class="text-light"><?= FAR::i('check-square'); ?> </small>
            <?php endif; ?>
            <?= Html::a(FAS::i('ellipsis-h'), ['task/update', 'id' => $model->id], [
                'class' => ['btn', 'btn-sm', 'ml-auto', 'stretched-link'],
                'data' => [
                    'toggle' => 'modal',
                    'target' => '#taskModal'
                ]
            ]); ?>
        </div>
    </div>
</div>
