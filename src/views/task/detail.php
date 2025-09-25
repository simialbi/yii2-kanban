<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Task */
/* @var $statuses array */
?>

<style type="text/css">
    .kv-expand-detail-row > td {
        overflow: hidden;
    }
</style>

<div class="task-detail">
    <div class="title mb-3">
        <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'Subject') ?></small>
        <?= Html::encode($model->subject) ?>
    </div>
    <div class="assignees mb-3">
        <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/plan', 'assignee') ?></small>
        <?php foreach ($model->assignees as $assignee): ?>
            <span class="kanban-user d-inline" data-id="<?= $assignee->getId(); ?>"
                  data-name="<?= Html::encode($assignee->name); ?>" data-image="<?= $assignee->photo; ?>">
                <?= Html::hiddenInput('assignees[]', $assignee->getId()); ?>
                <?php if ($assignee->photo): ?>
                    <?= Html::img($assignee->photo, [
                        'class' => ['rounded-circle', 'me-1'],
                        'title' => Html::encode($assignee->name),
                        'data' => [
                            'toggle' => 'tooltip'
                        ]
                    ]); ?>
                <?php else: ?>
                    <span class="kanban-visualisation me-1"
                          title="<?= Html::encode($assignee->name); ?>"
                          data-bs-toggle="tooltip">
                        <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </span>
        <?php endforeach; ?>
    </div>
    <div class="infos mb-3">
        <div class="row">
            <div class="col-md-3">
                <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'Board') ?></small>
                <?= Html::a(
                    $model->board->name,
                    Url::to(['plan/view', 'id' => $model->board->id]),
                    [
                        'data' => [
                            'pjax' => 0
                        ],
                        'target' => '_blank',
                    ]
                ) ?>
            </div>
            <div class="col-md-3">
                <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'Bucket') ?></small>
                <?= Html::encode($model->bucket->name) ?>
            </div>
            <div class="col-md-2">
                <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'Status') ?></small>
                <?= Html::encode($statuses[$model->status]) ?>
            </div>
            <div class="col-md-2">
                <small class="fw-bold d-block">
                    <?= Yii::t('simialbi/kanban/model/task', 'Start date') ?>
                </small>
                <?= Yii::$app->formatter->asDate($model->start_date, 'php:d.m.Y') ?>
            </div>
            <div class="col-md-2">
                <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'End date') ?></small>
                <?= Yii::$app->formatter->asDate($model->end_date, 'php:d.m.Y') ?>
            </div>
        </div>
    </div>
    <div class="description mb-3">
        <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'Description') ?></small>
        <?= Html::stripTags($model->description); ?>
    </div>
    <div class="checklist mb-3">
        <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/model/task', 'Checklist') ?></small>
        <?php foreach ($model->checklistElements as $checklistElement): ?>
            <div class="row">
                <div class="col-md-9"><?= Html::encode($checklistElement->name) ?></div>
                <div class="col-md-3">
                    <?= Yii::$app->formatter->asDate($checklistElement->end_date, 'php:d.m.Y') ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="attachments mb-3">
        <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/task', 'Attachments') ?></small>
        <?php foreach ($model->attachments as $attachment): ?>
            <a href="<?= $attachment->path; ?>" class="d-block" target="_blank" data-pjax="0">
                <?= Html::encode($attachment->name); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="links mb-3">
        <small class="fw-bold d-block"><?= Yii::t('simialbi/kanban/task', 'Links') ?></small>
        <?php foreach ($model->links as $link): ?>
            <a href="<?= $link->url; ?>" class="d-block" target="_blank" data-pjax="0">
                <?= $link->url ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script type="text/javascript">
    jQuery('.task-detail [data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        boundary: 'body'
    });
</script>
