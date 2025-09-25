<?php

use kartik\grid\GridView;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\SearchMonitoringList;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Frame;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\View;

/* @var $this View */
/* @var $searchModel SearchMonitoringList */
/* @var $dataProvider ActiveDataProvider */
/* @var $users UserInterface[] */

Frame::begin([
    'options' => [
        'id' => 'monitoring-frame'
    ]
]);

?>
    <div class="kanban-monitoring-index mt-3">
        <?= GridView::widget([
            'filterModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'pjaxSettings' => [
                'options' => [
                    'enablePushState' => false,
                    'clientOptions' => [
                        'skipOuterContainers' => true
                    ]
                ]
            ],
            'panelBeforeTemplate' => '{pager}{summary}{toolbar}',
            'toolbar' => [
                [
                    'content' => Html::a(FAS::i('plus'), ['monitoring/create'], [
                        'class' => ['btn', 'btn-success'],
                        'data' => [
                            'bs-toggle' => 'modal',
                            'pjax' => '0',
                            'turbo-frame' => 'task-modal-frame',
                            'bs-target' => '#task-modal'
                        ]
                    ])
                ]
            ],
            'options' => [
                'id' => 'monitoringPanel'
            ],
            'columns' => [
                ['class' => '\kartik\grid\SerialColumn'],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'name',
                    'vAlign' => GridView::ALIGN_MIDDLE,
                    'responsiveHeaderColumn' => true
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'member_id',
                    'value' => function ($model) {
                        return implode(', ', ArrayHelper::getColumn($model->members, 'user.name'));
                    },
                    'filter' => $users,
                    'filterType' => GridView::FILTER_SELECT2,
                    'filterWidgetOptions' => [
                        'options' => ['placeholder' => '', 'multiple' => true],
                        'pluginOptions' => ['allowClear' => true]
                    ],
                    'vAlign' => GridView::ALIGN_MIDDLE,
                    'responsiveHeaderColumn' => true
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
                            'bs-toggle' => 'modal',
                            'pjax' => '0',
                            'turbo-frame' => 'task-modal-frame',
                            'bs-target' => '#task-modal'
                        ]
                    ],
                    'deleteOptions' => [
                        'icon' => (string)FAS::i('trash-alt')
                    ],
                    'buttons' => [
                        'csv' => function ($url) {
                            return Html::a(FAS::i('file-excel'), $url, [
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
    </div>
<?php
Frame::end();
