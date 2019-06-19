<?php

use simialbi\yii2\kanban\KanbanAsset;
use yii\bootstrap4\Modal;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $buckets string */

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
                <?= $buckets; ?>
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
