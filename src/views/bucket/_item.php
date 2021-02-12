<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap4\Html;
use yii\helpers\Inflector;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $id string|integer */
/* @var $action string */
/* @var $boardId integer */
/* @var $title string */
/* @var $keyName string */
/* @var $tasks Task[] */
/* @var $completedTasks integer|Task[] */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $sort boolean */
/* @var $renderContext boolean */
/* @var $readonly boolean */

?>

<div class="kanban-bucket mr-md-4 pb-6 pb-md-0 d-flex flex-column flex-shrink-0" data-id="<?= $id; ?>"
     data-sort="<?= $sort ? 'true' : 'false'; ?>" data-action="<?= $action; ?>"
     data-key-name="<?= Inflector::camel2id($keyName, '_'); ?>">
    <?php if ($renderContext): ?>
        <?= $this->render('_header', [
            'id' => $id,
            'title' => $title
        ]); ?>
    <?php else: ?>
        <h5 class="m-0 mx-auto mx-md-0"><?= $title; ?></h5>
    <?php endif; ?>

    <?php Pjax::begin([
        'id' => 'createTaskPjax' . Inflector::slug($title),
        'options' => [
            'class' => ['d-none', 'd-md-block']
        ],
        'formSelector' => '#createTaskForm',
        'enablePushState' => false,
        'clientOptions' => ['skipOuterContainers' => true]
    ]); ?>
    <?php if (!$readonly): ?>
    <?= Html::a(FAS::i('plus'), ['task/create', 'boardId' => $boardId, $keyName => $id], [
        'class' => ['btn', 'btn-primary', 'btn-block']
    ]); ?>
    <?php endif; ?>
    <?php Pjax::end(); ?>

    <div class="kanban-tasks mt-4 flex-grow-1">
        <?php foreach ($tasks as $task): ?>
            <?php if (is_array($task)): ?>
                <?php $t = new Task(); ?>
                <?php $t->setAttributes($task); ?>
                <?php $task = $t; ?>
            <?php endif; ?>
            <?= $this->render('/task/item', [
                'model' => $task,
                'statuses' => $statuses,
                'users' => $users
            ]); ?>
        <?php endforeach; ?>
    </div>

    <?php Pjax::begin([
        'id' => 'createTaskPjaxMobile' . Inflector::slug($title),
        'options' => [
            'class' => ['d-md-none']
        ],
        'formSelector' => '#createTaskForm',
        'enablePushState' => false,
        'clientOptions' => ['skipOuterContainers' => true]
    ]); ?>
    <?php if (!$readonly): ?>
        <?= Html::a(FAS::i('plus'), ['task/create', 'boardId' => $boardId, $keyName => $id, 'mobile' => true], [
            'class' => ['kanban-create-mobile', 'd-md-none', 'rounded-circle', 'bg-secondary', 'text-white', 'p-3'],
        ]); ?>
    <?php endif; ?>
    <?php Pjax::end(); ?>

    <?php if ($completedTasks): ?>
        <?php if (is_numeric($completedTasks)): ?>
            <?php Pjax::begin([
                'id' => 'completedTasksPjax' . $id,
                'enablePushState' => false,
                'clientOptions' => ['skipOuterContainers' => true]
            ]); ?>
            <?= Html::a(Yii::t('simialbi/kanban', 'Show done ({cnt,number,integer})', [
                'cnt' => $completedTasks
            ]), ['task/view-completed', 'boardId' => $boardId, $keyName => $id], []); ?>
            <?php Pjax::end(); ?>
        <?php elseif (is_array($completedTasks)): ?>
            <a href="#collapse-<?= $id; ?>" data-toggle="collapse" aria-controls="collapse-<?= $id; ?>"
               aria-expanded="true">
                <?= Yii::t('simialbi/kanban', 'Show done ({cnt,number,integer})', [
                    'cnt' => count($completedTasks)
                ]) ?>
            </a>
            <div class="kanban-tasks-mt-4 collapse show flex-grow-0" id="collapse-<?= $id; ?>">
                <?php foreach ($completedTasks as $task): ?>
                    <?php if (is_array($task)): ?>
                        <?php $t = new Task(); ?>
                        <?php $t->setAttributes($task); ?>
                        <?php $task = $t; ?>
                    <?php endif; ?>
                    <?= $this->render('/task/item', [
                        'model' => $task,
                        'statuses' => $statuses,
                        'users' => $users
                    ]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
