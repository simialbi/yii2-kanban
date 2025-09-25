<?php

use simialbi\yii2\chart\models\axis\CategoryAxis;
use simialbi\yii2\chart\models\axis\ValueAxis;
use simialbi\yii2\chart\models\Legend;
use simialbi\yii2\chart\models\series\ColumnSeries;
use simialbi\yii2\chart\models\series\PieSeries;
use simialbi\yii2\chart\models\style\Color;
use simialbi\yii2\chart\widgets\LineChart;
use simialbi\yii2\chart\widgets\PieChart;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\models\UserInterface;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this View */
/* @var $model Board */
/* @var $users UserInterface[] */
/* @var $statuses array */
/* @var $byStatus array */
/* @var $byBucket array */
/* @var $byAssignee array */
/* @var $colors array */
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

$pieSeries = new PieSeries([
    'dataFields' => [
        'value' => 'value',
        'category' => 'status'
    ]
]);
$js = <<<JS
{$pieSeries->varName}.labels.template.disabled = true;
{$pieSeries->varName}.ticks.template.disabled = true;
{$pieSeries->varName}.slices.template.propertyFields.fill = 'color';
{$pieSeries->varName}.slices.template.propertyFields.stroke = 'color';
JS;
$pieSeries->appendix = new JsExpression($js);

$columnSeries = [];
foreach ($statuses as $status => $label) {
    $series = new ColumnSeries([
        'dataFields' => [
            'valueY' => 'status_' . $status,
            'categoryX' => 'bucket'
        ],
        'stacked' => true,
        'name' => $label,
        'fill' => new Color(['hex' => ArrayHelper::getValue($colors, $status)]),
        'stroke' => new Color(['hex' => ArrayHelper::getValue($colors, $status)])
    ]);
    $series->appendix = new JsExpression(
        "{$series->varName}.columns.template.tooltipText = \"{name}: [bold]{valueY}[/]\";\n"
    );
    $columnSeries[] = $series;
}

$barSeries = [];
foreach ($statuses as $status => $label) {
    $series = new ColumnSeries([
        'dataFields' => [
            'valueX' => 'status_' . $status,
            'categoryY' => 'user'
        ],
        'stacked' => true,
        'name' => $label,
        'fill' => new Color(['hex' => ArrayHelper::getValue($colors, $status)]),
        'stroke' => new Color(['hex' => ArrayHelper::getValue($colors, $status)])
    ]);
    $series->appendix = new JsExpression("{$series->varName}.columns.template.tooltipText = \"{name}: [bold]{valueX}[/]\";");
    $barSeries[] = $series;
}
?>
<div class="kanban-plan-schedule">
    <?= $this->render('_navigation', [
        'boards' => [],
        'model' => $model,
        'users' => $users,
        'readonly' => $readonly
    ]); ?>
    <div class="mt-5 row">
        <div class="col-12 col-md-5 col-lg-4">
            <div class="card">
                <h5 class="card-header">
                    <?= Yii::t('simialbi/kanban/chart', 'Status'); ?>
                </h5>
                <div class="card-body">
                    <?= PieChart::widget([
                        'options' => [
                            'style' => [
                                'width' => '100%',
                                'height' => '400px'
                            ]
                        ],
                        'clientOptions' => [
                            'innerRadius' => '60%',
                            'legend' => new Legend([
                                'position' => Legend::POSITION_RIGHT
                            ])
                        ],
                        'series' => $pieSeries,
                        'data' => $byStatus
                    ]); ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-7 col-lg-8">
            <div class="card">
                <h5 class="card-header">
                    <?= Yii::t('simialbi/kanban/chart', 'Bucket'); ?>
                </h5>
                <div class="card-body">
                    <?= LineChart::widget([
                        'options' => [
                            'style' => [
                                'width' => '100%',
                                'height' => '400px'
                            ]
                        ],
                        'series' => $columnSeries,
                        'axes' => [
                            new CategoryAxis([
                                'dataFields' => [
                                    'category' => 'bucket'
                                ]
                            ]),
                            new ValueAxis()
                        ],
                        'data' => $byBucket
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">
                    <?= Yii::t('simialbi/kanban/chart', 'Assignee'); ?>
                </h5>
                <div class="card-body">
                    <?php
                    $height = 100;
                    $height += count($byAssignee) * 50;
                    ?>
                    <?= LineChart::widget([
                        'options' => [
                            'style' => [
                                'width' => '100%',
                                'height' => $height . 'px'
                            ]
                        ],
                        'series' => $barSeries,
                        'axes' => [
                            'x' => new ValueAxis(),
                            'y' => new CategoryAxis([
                                'dataFields' => [
                                    'category' => 'user'
                                ]
                            ])
                        ],
                        'data' => $byAssignee
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
