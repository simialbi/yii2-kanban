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
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\models\MonitoringList;
use simialbi\yii2\kanban\models\SearchTask;
use simialbi\yii2\models\UserInterface;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this View */
/* @var $model MonitoringList */
/* @var $users UserInterface[] */
/* @var $boards array */
/* @var $statuses array */
/* @var $byStatus array */
/* @var $byBoard array */
/* @var $byAssignee array */
/* @var $colors array */
/* @var $searchModel SearchTask */
/* @var $dataProvider ActiveDataProvider */

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
    <div class="row">
        <div class="col-12">
            <?= GridView::widget([
                'title' => $model->name,
                'filterModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'bordered' => false,
                'export' => false,
                'toolbar' => Html::tag(
                    'div',
                    Html::responsiveButtons([
                        Html::a(
                            FAS::i('file-excel'),
                            Url::to(['csv', ...Yii::$app->request->queryParams]),
                            [
                                'target' => '_blank',
                                'class' => ['btn', 'btn-secondary'],
                                'data' => [
                                    'pjax' => '0'
                                ]
                            ]
                        )
                    ])
                ),
                'options' => [
                    'id' => 'monitoringTaskPanel'
                ],
                'columns' => [
                    ['class' => '\kartik\grid\SerialColumn'],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'subject',
                        'vAlign' => GridView::ALIGN_MIDDLE,
                        'responsiveHeaderColumn' => true
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
                        'attribute' => 'responsible_id',
                        'value' => 'responsible.name',
                        'filter' => ArrayHelper::getColumn($users, 'name'),
                        'filterType' => GridView::FILTER_SELECT2,
                        'filterWidgetOptions' => [
                            'theme' => Select2::THEME_BOOTSTRAP,
                            'options' => ['placeholder' => '', 'multiple' => true],
                            'pluginOptions' => ['allowClear' => true]
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE,
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'assignee_id',
                        'value' => function ($model) {
                            return implode(', ', ArrayHelper::getColumn($model->assignees, 'name'));
                        },
                        'filter' => ArrayHelper::getColumn($users, 'name'),
                        'filterType' => GridView::FILTER_SELECT2,
                        'filterWidgetOptions' => [
                            'theme' => Select2::THEME_BOOTSTRAP,
                            'options' => ['placeholder' => '', 'multiple' => true],
                            'pluginOptions' => ['allowClear' => true]
                        ],
                        'vAlign' => GridView::ALIGN_MIDDLE,
                        'responsiveHeaderColumn' => true
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'created_by',
                        'value' => 'author.name',
                        'filter' => ArrayHelper::getColumn($users, 'name'),
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
                            return ArrayHelper::getValue($statuses, $model->status);
                        },
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'format' => 'date',
                        'attribute' => 'end_date',
                        'filterType' => '\sandritsch91\yii2\flatpickr\Flatpickr',
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'format' => 'datetime',
                        'attribute' => 'created_at',
                        'filterType' => '\sandritsch91\yii2\flatpickr\Flatpickr',
                        'vAlign' => GridView::ALIGN_MIDDLE
                    ],
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'format' => 'datetime',
                        'attribute' => 'updated_at',
                        'filterType' => '\sandritsch91\yii2\flatpickr\Flatpickr',
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
