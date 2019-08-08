<?php

use simialbi\yii2\kanban\KanbanAsset;
use yii\bootstrap4\Modal;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $buckets string */
/* @var $users \simialbi\yii2\kanban\models\UserInterface[] */
/* @var $readonly boolean */

KanbanAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'),
        'url' => ['index']
    ],
    $this->title
];
?>
    <div class="kanban-plan-view">
        <?= $this->render('_navigation', [
            'model' => $model,
            'users' => $users,
            'readonly' => $readonly
        ]); ?>
        <div class="d-flex flex-column mt-3">
            <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
                <div></div>
            </div>
            <div class="kanban-bottom-scrollbar">
                <div class="d-flex flex-row kanban-plan-sortable">
                    <?= $buckets; ?>
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
