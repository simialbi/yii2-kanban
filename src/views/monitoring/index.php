<?php

use kartik\grid\GridView;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $searchModel \simialbi\yii2\kanban\models\SearchMonitoringList */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var $users \simialbi\yii2\models\UserInterface[] */

Frame::begin([
    'options' => [
        'id' => 'monitoring-frame'
    ]
]);

?>
    <div class="kanban-monitoring-index">
        <?= GridView::widget([
            'filterModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'pjaxSettings' => [
                'options' => [
                    'enablePushState' => false,
                    'clientOptions' => [
                        'skipOuterContainers' => true
                    ]
                ]
            ],
            'bordered' => false,
            'export' => false,
            'toolbar' => [
                [
                    'content' => Html::a(FAS::i('plus'), ['monitoring/create'], [
                        'class' => ['btn', 'btn-success'],
                        'data' => [
                            'toggle' => 'modal',
                            'pjax' => '0',
                            'turbo-frame' => 'task-modal-frame',
                            'target' => '#task-modal'
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
                            'turbo-frame' => 'task-modal-frame',
                            'target' => '#task-modal'
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
    </div>
<?php
Frame::end();
