<?php

use kartik\grid\GridView;
use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $searchModel \simialbi\yii2\kanban\models\SearchMonitoringList */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var $users \simialbi\yii2\models\UserInterface[] */

?>

<div class="kanban-monitoring-index">
    <?php Pjax::begin([
        'id' => 'monitoringPjax',
        'enablePushState' => false,
        'clientOptions' => [
            'skipOuterContainers' => true
        ]
    ]); ?>

    <?= GridView::widget([
        'filterModel' => $searchModel,
        'dataProvider' => $dataProvider,
        'pjax' => false,
        'bordered' => false,
        'export' => false,
        'toolbar' => [
            [
                'content' => Html::a(FAS::i('plus'), ['monitoring/create'], [
                    'class' => ['btn', 'btn-success'],
                    'data' => [
                        'toggle' => 'modal',
                        'pjax' => '0',
                        'target' => '#taskModal'
                    ]
                ])
            ]
        ],
        'options' => [
            'id' => 'monitoringPanel'
        ],
        'panel' => [
            'heading' => '',
            'type' => 'light',
            'before' => '{summary}',
            'options' => [
                'class' => ['rounded-0', 'mb-5', 'border-top-0']
            ],
        ],
        'panelTemplate' => '{panelBefore}{items}{panelFooter}',
        'columns' => [
            ['class' => '\kartik\grid\SerialColumn'],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'name',
                'vAlign' => GridView::ALIGN_MIDDLE
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'member_id',
                'value' => function ($model) {
                    /** @var $model \simialbi\yii2\kanban\models\MonitoringList */
                    return implode(', ', ArrayHelper::getColumn($model->members, 'user.name'));
                },
                'filter' => $users,
                'filterType' => GridView::FILTER_SELECT2,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => '', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true]
                ],
                'vAlign' => GridView::ALIGN_MIDDLE
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'format' => 'datetime',
                'attribute' => 'created_at',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'pickerButton' => '<span class="input-group-text kv-date-picker">' . FAS::i('calendar-alt') . '</span>',
                    'removeButton' => false,
                    'pluginOptions' => [
                        'format' => 'dd.mm.yyyy',
                        'todayHighlight' => true
                    ]
                ],
                'vAlign' => GridView::ALIGN_MIDDLE
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'format' => 'datetime',
                'attribute' => 'updated_at',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'pickerButton' => '<span class="input-group-text kv-date-picker">' . FAS::i('calendar-alt') . '</span>',
                    'removeButton' => false,
                    'pluginOptions' => [
                        'format' => 'dd.mm.yyyy',
                        'todayHighlight' => true
                    ]
                ],
                'vAlign' => GridView::ALIGN_MIDDLE
            ],
            [
                'class' => '\kartik\grid\ActionColumn',
                'template' => '{view} {update} {delete} {csv}',
                'viewOptions' => [
                    'icon' => (string)FAS::i('eye')
                ],
                'updateOptions' => [
                    'icon' => (string)FAS::i('pencil-alt'),
                    'data' => [
                        'toggle' => 'modal',
                        'pjax' => '0',
                        'target' => '#taskModal'
                    ]
                ],
                'deleteOptions' => [
                    'icon' => (string)FAS::i('trash-alt')
                ],
                'buttons' => [
                    'csv' => function ($url) {
                        return Html::a(FAS::i('file-csv'), $url, [
                            'target' => '_blank',
                            'data' => [
                                'pjax' => '0'
                            ]
                        ]);
                    }
                ],
                'width' => '120px'
            ]
        ]
    ]); ?>

    <?php Pjax::end(); ?>
</div>
