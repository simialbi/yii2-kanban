<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\widgets\ToDo;
use yii\bootstrap4\Html;
use yii\bootstrap4\Modal;
use yii\bootstrap4\Tabs;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */
/* @var $delegated string */
/* @var $activeTab string */

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
            <?php $options = ['class' => ['kanban-board', 'card', 'mb-3', 'mr-md-2']]; ?>
            <?php if (++$i % 3 === 0 && $i % 2 !== 0): ?>
                <?php Html::addCssClass($options, 'mr-xl-0'); ?>
            <?php endif; ?>
            <?= Html::beginTag('div', $options); ?>
            <div class="kanban-board-inner row no-gutters flex-nowrap flex-grow-1">
                <a href="<?= Url::to(['plan/view', 'id' => $board->id]); ?>"
                   class="kanban-board-image col-3 col-md-4 d-flex justify-content-center align-items-center text-decoration-none">
                    <?php if ($board->image): ?>
                        <?= Html::img($board->image, ['class' => ['img-fluid']]); ?>
                    <?php else: ?>
                        <span class="kanban-visualisation modulo-<?= $board->id % 10; ?>">
                                <?= substr($board->name, 0, 1); ?>
                            </span>
                    <?php endif; ?>
                </a>
                <div class="col col-md-8">
                    <div class="card-body d-flex align-items-stretch h-100">
                        <a href="<?= Url::to(['plan/view', 'id' => $board->id]); ?>"
                           class="flex-grow-1 text-body text-decoration-none">
                            <h5 class="pt-0"><?= Html::encode($board->name); ?></h5>
                            <small
                                class="text-muted"><?= Yii::$app->formatter->asDatetime($board->updated_at); ?></small>
                        </a>
                        <?php if (Yii::$app->user->id == $board->created_by): ?>
                            <span class="d-flex flex-column justify-content-around">
                                    <?= Html::a(FAS::i('edit'), ['plan/update', 'id' => $board->id], [
                                        'class' => ['text-body'],
                                        'title' => Yii::t('simialbi/kanban/plan', 'Update plan')
                                    ]); ?>
                                    <?= Html::a(FAS::i('trash-alt'), ['plan/delete', 'id' => $board->id], [
                                        'class' => ['text-body'],
                                        'title' => Yii::t('simialbi/kanban/plan', 'Delete plan'),
                                        'data' => [
                                            'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                            'method' => 'post'
                                        ]
                                    ]); ?>
                                </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?= Html::endTag('div'); ?>
        <?php endforeach; ?>
        <div class="kanban-board card mr-md-2 mb-3 d-none d-md-block">
            <div class="card-body">
                <h5 class="mb-0 pt-0"><?= Html::encode(Yii::t('simialbi/kanban/plan', 'Create board')); ?></h5>
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
        <?= ToDo::widget([
            'addBoardFilter' => true
        ]); ?>
    </div>
    <?php $this->endBlock(); ?>
    <?php $this->beginBlock('tab-delegated-tasks'); ?>
    <?= $this->render('/task/delegated', [
        'delegated' => $delegated,
        'view' => null
    ]); ?>
    <?php $this->endBlock(); ?>
    <?php
    $js = <<<JS
function onHide() {
    jQuery('.note-editor', this).each(function () {
        var summernote = jQuery(this).prev().data('summernote');
        if (summernote) {
            summernote.destroy();
        }
    });
}
JS;
    Modal::begin([
        'id' => 'taskModal',
        'options' => [
            'class' => ['modal', 'remote', 'fade']
        ],
        'clientOptions' => [
            'backdrop' => 'static',
            'keyboard' => false
        ],
        'clientEvents' => [
            'hidden.bs.modal' => new JsExpression($js)
        ],
        'size' => Modal::SIZE_LARGE,
        'title' => null,
        'closeButton' => false
    ]);
    Modal::end();
    ?>

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
                'active' => ($activeTab === 'plan')
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
                'label' => Yii::t('simialbi/kanban/plan', 'Monitoring'),
                'linkOptions' => [
                    'data' => ['src' => Url::to(['monitoring/index'])]
                ],
                'content' => '',
                'active' => ($activeTab === 'monitoring'),
                'visible' => Yii::$app->user->can('monitorKanbanTasks')
            ]
        ]
    ]) ?>
</div>
