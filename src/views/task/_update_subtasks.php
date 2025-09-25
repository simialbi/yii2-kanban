<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\web\View;

/* @var $this View */
/* @var $model Task */
/* @var $statuses array */

$hideSub = $model->isRecurrentInstance() ||
    $model->recurrence_parent_id > 0 ||
    $model->is_recurring > 0 ||
    $model->ticket_id > 0 ||
    !empty($model->sync_id);
?>
<div class="px-3 pb-3 <?php if ($hideSub): ?>d-none<?php endif; ?>">
    <div class="row g-3 mt-0">
        <div class="col-12">
            <?php
            $create = Html::a(
                FAS::i('plus')->fixedWidth(),
                ['task/create-sub-task', 'id' => $model->id],
                [
                    'title' => Yii::t('simialbi/kanban', 'Create sub task'),
                    'data' => [
                        'turbo-frame' => 'task-modal-frame'
                    ],
                ]
            );
            echo Html::label(
                Yii::t('simialbi/kanban/task', 'Subtasks') . ' ' . $create,
                'comment',
                [
                    'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
                ]);
            ?>
            <div>
                <?php
                if ($model->children || $model->parent_id) {
                    $tree = $model->getTree();
                    echo $model->createHtmlTree($tree, $statuses, $model);
                }
                ?>
            </div>
        </div>
    </div>
</div>
