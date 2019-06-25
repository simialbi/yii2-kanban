<?php

use simialbi\yii2\chart\models\series\PieSeries;
use simialbi\yii2\chart\widgets\PieChart;
use simialbi\yii2\kanban\KanbanAsset;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $users \simialbi\yii2\kanban\models\UserInterface[] */
/* @var $byStatus array */

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
<div class="kanban-plan-schedule">
    <?= $this->render('_navigation', [
        'model' => $model,
        'users' => $users
    ]); ?>
    <div class="mt-5 row">
        <div class="col-12 col-md-4 col-lg-3">
            <?= PieChart::widget([
                'options' => [
                    'style' => [
                        'width' => '100%',
                        'height' => '200px'
                    ]
                ],
                'clientOptions' => [
                    'innerRadius' => '80%'
                ],
                'series' => new PieSeries([
                    'dataFields' => [
                        'value' => 'value',
                        'category' => 'status'
                    ]
                ]),
                'data' => $byStatus
            ]); ?>
        </div>
        <div class="col-12 col-md-8 col-lg-9"></div>
    </div>
</div>
