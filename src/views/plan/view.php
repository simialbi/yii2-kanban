<?php

use simialbi\yii2\kanban\KanbanAsset;
use yii\bootstrap4\Html;
use yii\bootstrap4\Modal;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */

KanbanAsset::register($this);

$this->title = Yii::t('simialbi/kanban/plan', $model->name);
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'),
        'url' => ['index']
    ],
    $this->title
];
?>
    <div class="kanban-plan-view">
        <?= $this->render('_navigation', ['model' => $model]); ?>
        <div class="overflow-auto mt-5">
            <div class="d-flex flex-row">
                <?php foreach ($model->buckets as $bucket): ?>
                    <?= $this->render('/bucket/item', ['model' => $bucket]); ?>
                <?php endforeach; ?>
                <?php Pjax::begin([
                    'id' => 'createBucketPjax',
                    'formSelector' => '#createBucketForm',
                    'enablePushState' => false,
                    'clientOptions' => ['skipOuterContainers' => true]
                ]); ?>
                <div class="kanban-bucket">
                    <h5>
                        <?= Html::a(Yii::t('simialbi/kanban/plan', 'Create bucket'), [
                            'bucket/create',
                            'boardId' => $model->id
                        ]); ?>
                    </h5>
                </div>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
<?php
Modal::begin([
    'id' => 'taskModal',
    'options' => [
        'class' => ['modal', 'remote', 'fade']
    ],
    'size' => Modal::SIZE_LARGE,
    'title' => null,
    'closeButton' => false
]);
Modal::end();
