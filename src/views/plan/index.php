<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\widgets\ToDo;
use simialbi\yii2\turbo\Frame;
use simialbi\yii2\turbo\Modal;
use yii\bootstrap5\Tabs;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $boards Board[] */
/* @var $activeTab string */
/* @var $hiddenCnt integer */

KanbanAsset::register($this);

$this->title = Yii::t('simialbi/kanban/plan', 'Kanban Hub');
$this->params['breadcrumbs'] = [$this->title];
?>

<div class="kanban-plan-index position-relative">
    <h1><?= Html::encode($this->title); ?></h1>

    <?php
    $this->beginBlock('tab-hub'); ?>
    <div class="mt-3 kanban-boards">
        <?= $this->render('_index', [
            'boards' => array_filter($boards, function ($board) {
                return !$board->is_checklist;
            }),
            'type' => 'plans'
        ]); ?>
    </div>
    <?php
    $this->endBlock(); ?>

    <?php
    $this->beginBlock('tab-checklists'); ?>
    <div class="mt-3 kanban-boards">
        <?= $this->render('_index', [
            'boards' => array_filter($boards, function ($board) {
                return $board->is_checklist;
            }),
            'type' => 'checklists'
        ]); ?>
    </div>
    <?php
    $this->endBlock(); ?>

    <?php
    $this->beginBlock('tab-tasks'); ?>
    <div class="mt-3">
        <?= ToDo::widget([
            'addBoardFilter' => true,
            'listOptions' => [
                'class' => ['list-group']
            ],
            'itemOptions' => [
                'class' => ['list-group-item', 'list-group-item-action', 'rounded-0', 'p-2']
            ],
            'kanbanModuleName' => 'schedule'
        ]); ?>
    </div>
    <?php
    $this->endBlock(); ?>

    <?php
    $this->beginBlock('tab-delegated-tasks'); ?>
    <?= Frame::widget([
        'options' => [
            'id' => 'delegated-tasks-frame',
            'src' => Url::to(['task/view-delegated']),
            'loading' => 'lazy'
        ]
    ]); ?>
    <?php
    $this->endBlock(); ?>

    <?php
    $this->beginBlock('tab-responsible-tasks'); ?>
    <?= Frame::widget([
        'options' => [
            'id' => 'responsible-tasks-frame',
            'src' => Url::to(['task/view-responsible', 'Filters' => Yii::$app->request->getBodyParam('Filter', [])]),
            'loading' => 'lazy'
        ]
    ]); ?>
    <?php
    $this->endBlock(); ?>

    <?php
    $this->beginBlock('tab-monitoring'); ?>
    <?= Frame::widget([
        'options' => [
            'id' => 'monitoring-frame',
            'src' => Url::to(['monitoring/index']),
            'loading' => 'lazy'
        ]
    ]); ?>
    <?php
    $this->endBlock(); ?>

    <?php
    $this->beginBlock('tab-calendar'); ?>
    <?= Frame::widget([
        'options' => [
            'id' => 'calendar-frame',
            'src' => Url::to(['calendar/index']),
            'loading' => 'lazy'
        ]
    ]); ?>
    <?php
    $this->endBlock(); ?>

    <?php
    Modal::begin([
        'modalClass' => '\yii\bootstrap5\Modal',
        'options' => [
            'id' => 'task-modal',
            'options' => [
                'class' => ['modal', 'remote', 'fade']
            ],
            'clientOptions' => [
                'backdrop' => 'static',
                'keyboard' => false
            ],
            'size' => \yii\bootstrap5\Modal::SIZE_EXTRA_LARGE,
            'title' => null,
            'closeButton' => false
        ]
    ]);
    Modal::end();
    ?>

    <?= Tabs::widget([
        'options' => [
            'class' => ['bg-light', 'rounded', 'flex-wrap', 'justify-content-start', 'memory',]
        ],
        'id' => 'plan-tabs',
        'navType' => 'nav-kanban',
        'items' => [
            [
                'label' => Yii::t('simialbi/kanban/plan', 'All plans'),
                'content' => $this->blocks['tab-hub'],
                'active' => ($activeTab === 'plan')
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'Checklists'),
                'content' => $this->blocks['tab-checklists'],
                'active' => ($activeTab === 'checklists')
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'My tasks'),
                'content' => $this->blocks['tab-tasks'],
                'active' => ($activeTab === 'todo')
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'Delegated tasks'),
                'content' => $this->blocks['tab-delegated-tasks'],
                'active' => ($activeTab === 'delegated')
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'Responsible'),
                'content' => $this->blocks['tab-responsible-tasks'],
                'active' => ($activeTab === 'responsible')
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'Monitoring'),
                'content' => $this->blocks['tab-monitoring'],
                'active' => ($activeTab === 'monitoring'),
                'visible' => Yii::$app->user->can('monitorKanbanTasks')
            ],
            [
                'label' => Yii::t('simialbi/kanban/plan', 'Calendar'),
                'content' => $this->blocks['tab-calendar'],
                'active' => ($activeTab === 'calendar'),
                'visible' => Yii::$app->user->can('monitorKanbanTasks')
            ],
            [
                'label' => Yii::$app->session->get('kanban.plan.showHiddenBoards', false) ?
                    FAS::i('eye-slash')->fixedWidth() :
                    Html::tag('span', $hiddenCnt, [
                        'class' => [
                            'position-absolute',
                            'start-100',
                            'translate-middle',
                            'badge',
                            'rounded-pill',
                            'bg-danger',
                            ($hiddenCnt > 0 ? 'd-block' : 'd-none')
                        ]
                    ]) . FAS::i('eye', [
                        'class' => ['position-relative']
                    ])->fixedWidth(),
                'encode' => false,
                'url' => Url::to(['toggle-hidden-boards']),
                'active' => ($activeTab === 'settings'),
                'headerOptions' => [
                    'class' => ['ms-auto', 'me-2'],
                    'title' => Yii::t('simialbi/kanban/plan', 'Toggle hidden plans'),
                    'data' => [
                        'bs-toggle' => 'tooltip'
                    ]
                ],
                'linkOptions' => [
                    'class' => ['no-memory', 'position-relative']
                ]
            ]
        ]
    ]) ?>
</div>
