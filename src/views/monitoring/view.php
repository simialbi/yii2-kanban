<?php

use kartik\grid\GridView;
use kartik\select2\Select2;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\chart\models\axis\CategoryAxis;
use simialbi\yii2\chart\models\axis\ValueAxis;
use simialbi\yii2\chart\models\Legend;
use simialbi\yii2\chart\models\series\ColumnSeries;
use simialbi\yii2\chart\models\series\PieSeries;
use simialbi\yii2\chart\models\style\Color;
use simialbi\yii2\chart\widgets\LineChart;
use simialbi\yii2\chart\widgets\PieChart;
use simialbi\yii2\kanban\KanbanAsset;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\MonitoringList */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $boards array */
/* @var $statuses array */
/* @var $byStatus array */
/* @var $byBoard array */
/* @var $byAssignee array */
/* @var $colors array */
/* @var $searchModel \simialbi\yii2\kanban\models\SearchTask */
/* @var $dataProvider \yii\data\ActiveDataProvider */

KanbanAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'),
        'url' => ['plan/index']
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
            'categoryX' => 'board'
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
<div class="kanban-monitoring-view">
    <div class="mt-5 row">
        <div class="col-12">
            <?= GridView::widget([
                'filterModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'pjax' => false,
                'bordered' => false,
                'export' => false,
                'toolbar' => false,
                'options' => [
                    'id' => 'monitoringTaskPanel'
                ],
                'panel' => [
                    'heading' => $model->name,
                    'type' => 'light',
                    'before' => '{summary}',
                    'options' => [
                        'class' => ['rounded-0']
                    ],
                    'headingOptions' => [
                        'class' => ['card-header', 'bg-light']
                    ]
                ],
                'panelHeadingTemplate' => '{title}',
                'panelTemplate' => '{panelHeading}{panelBefore}{items}{panelFooter}',
                'columns' => [
                    ['class' => '\kartik\grid\SerialColumn'],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'subject',
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'board_id',
                        'value' => 'board.name',
                        'filter' => $boards,
                        'filterType' => GridView::FILTER_SELECT2,
                        'filterWidgetOptions' => [
                            'theme' => Select2::THEME_BOOTSTRAP,
                            'options' => ['placeholder' => '', 'multiple' => false],
                            'pluginOptions' => ['allowClear' => true]
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'assignee_id',
                        'value' => function ($model) {
                            /** @var $model \simialbi\yii2\kanban\models\Task */
                            return implode(', ', ArrayHelper::getColumn($model->assignees, 'name'));
                        },
                        'filter' => ArrayHelper::getColumn($users, 'name', true),
                        'filterType' => GridView::FILTER_SELECT2,
                        'filterWidgetOptions' => [
                            'theme' => Select2::THEME_BOOTSTRAP,
                            'options' => ['placeholder' => '', 'multiple' => true],
                            'pluginOptions' => ['allowClear' => true]
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'created_by',
                        'value' => 'author.name',
                        'filter' => ArrayHelper::getColumn($users, 'name', true),
                        'filterType' => GridView::FILTER_SELECT2,
                        'filterWidgetOptions' => [
                            'theme' => Select2::THEME_BOOTSTRAP,
                            'options' => ['placeholder' => '', 'multiple' => false],
                            'pluginOptions' => ['allowClear' => true]
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'status',
                        'filter' => $statuses,
                        'filterType' => GridView::FILTER_SELECT2,
                        'filterWidgetOptions' => [
                            'theme' => Select2::THEME_BOOTSTRAP,
                            'options' => ['placeholder' => ''],
                            'pluginOptions' => ['allowClear' => true]
                        ],
                        'value' => function ($model) use ($statuses) {
                            /** @var $model \simialbi\yii2\kanban\models\Task */
                            return ArrayHelper::getValue($statuses, $model->status);
                        },
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'format' => 'date',
                        'attribute' => 'end_date',
                        'filterType' => '\sandritsch91\yii2\flatpickr\Flatpickr',
                        'filterWidgetOptions' => [
                            'customAssetBundle' => false,
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'format' => 'datetime',
                        'attribute' => 'created_at',
                        'filterType' => '\sandritsch91\yii2\flatpickr\Flatpickr',
                        'filterWidgetOptions' => [
                            'customAssetBundle' => false,
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'format' => 'datetime',
                        'attribute' => 'updated_at',
                        'filterType' => '\sandritsch91\yii2\flatpickr\Flatpickr',
                        'filterWidgetOptions' => [
                            'customAssetBundle' => false,
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ]
                ]
            ]); ?>
        </div>
    </div>
    <div class="mt-4 row">
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
                    <?= Yii::t('simialbi/kanban/chart', 'Board'); ?>
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
                                    'category' => 'board'
                                ]
                            ]),
                            new ValueAxis()
                        ],
                        'data' => $byBoard
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
                    <?= LineChart::widget([
                        'options' => [
                            'style' => [
                                'width' => '100%',
                                'height' => '400px'
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
