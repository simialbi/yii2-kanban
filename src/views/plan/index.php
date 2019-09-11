<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\widgets\ToDo;
use yii\bootstrap4\Html;
use yii\bootstrap4\Modal;
use yii\bootstrap4\Tabs;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */
/* @var $delegated string */

KanbanAsset::register($this);

$this->title = Yii::t('simialbi/kanban/plan', 'Kanban Hub');
$this->params['breadcrumbs'] = [$this->title];
?>

<div class="kanban-plan-index pb-6 pb-md-0 position-relative">
    <h1><?= Html::encode($this->title); ?></h1>

    <?php $this->beginBlock('tab-hub'); ?>
    <div class="mt-3 d-md-flex flex-row justify-content-start flex-wrap">
        <?php $i = 0; ?>
        <?php foreach ($boards as $board): ?>
            <div class="kanban-board card mb-3<?php if (++$i % 3 !== 0): ?> mr-md-2<?php endif; ?>">
                <div class="row no-gutters flex-nowrap flex-grow-1">
                    <div class="kanban-board-image col-3 col-md-4 d-flex justify-content-center align-items-center">
                        <?php if ($board->image): ?>
                            <?= Html::img($board->image, ['class' => ['img-fluid']]); ?>
                        <?php else: ?>
                            <span class="kanban-visualisation modulo-<?= $board->id % 10; ?>">
                                <?= substr($board->name, 0, 1); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="col col-md-8">
                        <div class="card-body">
                            <h5 class="card-title"><?= Html::encode($board->name); ?></h5>
                            <small class="text-muted"><?=Yii::$app->formatter->asDatetime($board->updated_at);?></small>
                        </div>
                    </div>
                    <?= Html::a('', ['plan/view', 'id' => $board->id], [
                        'class' => ['stretched-link']
                    ]); ?>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="kanban-board card mr-md-2 mb-3 d-none d-md-block">
            <div class="card-body">
                <h5 class="card-title mb-0"><?= Html::encode(Yii::t('simialbi/kanban/plan', 'Create board')); ?></h5>
                <?= Html::a('', ['plan/create'], [
                    'class' => ['stretched-link']
                ]); ?>
            </div>
        </div>
        <?= Html::a(FAS::i('plus'), ['plan/create'], [
            'class' => ['kanban-create-mobile', 'd-md-none', 'rounded-circle', 'bg-secondary', 'text-white', 'p-3'],
            'title' => Html::encode(Yii::t('simialbi/kanban/plan', 'Create board'))
        ]); ?>
    </div>
    <?php $this->endBlock(); ?>
    <?php $this->beginBlock('tab-tasks'); ?>
    <div class="mt-3">
        <?= ToDo::widget(); ?>
    </div>
    <?php $this->endBlock(); ?>
    <?php $this->beginBlock('tab-delegated-tasks'); ?>
    <div class="kanban-plan-view">
        <div class="d-flex flex-column mt-3">
            <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
                <div></div>
            </div>
            <div class="kanban-bottom-scrollbar">
                <div class="d-flex flex-row kanban-plan-sortable">
                    <?= $delegated; ?>
                </div>

                <div class="d-md-none">
                    <div class="kanban-button-prev"><?= FAS::i('caret-left'); ?></div>
                    <div class="kanban-button-next"><?= FAS::i('caret-right'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
    Modal::begin([
        'id' => 'taskModal',
        'options' => [
            'class' => ['modal', 'remote', 'fade']
        ],
        'clientOptions' => [
            'backdrop' => 'static',
            'keyboard' => false
        ],
        'size' => Modal::SIZE_LARGE,
        'title' => null,
        'closeButton' => false
    ]);
    Modal::end();
    ?>
    <?php $this->endBlock(); ?>

    <?= Tabs::widget([
        'options' => [
            'class' => ['bg-light', 'rounded']
        ],
        'id' => 'plan-tabs',
        'navType' => 'nav-kanban',
        'items' => [
            [
                'label' => Yii::t('simialbi/kanban/plan', 'All plans'),
                'content' => $this->blocks['tab-hub'],
                'active' => true
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'My tasks'),
                'content' => $this->blocks['tab-tasks']
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'Delegated tasks'),
                'content' => $this->blocks['tab-delegated-tasks']
            ]
        ]
    ]) ?>
</div>
