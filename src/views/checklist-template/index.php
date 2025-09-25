<?php

use kartik\grid\GridView;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\SearchChecklistTemplate;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $searchModel SearchChecklistTemplate */
/* @var $dataProvider ActiveDataProvider */
/* @var $board Board */
/* @var $users array */

$this->title = Yii::t('simialbi/kanban/plan', 'Checklist templates');
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'All plans'),
        'url' => ['plan/index']
    ],
    $board->name . ' - ' . $this->title
];

$buttons = [
    'create' => [
        'label' => Yii::t('simialbi/kanban/checklist-template', 'New template'),
        'url' => '#hqDynamicModal',
        'icon' => 'plus',
        'options' => [
            'class' => ['btn', 'btn-primary'],
            'data' => [
                'bs-toggle' => 'modal',
                'src' => Url::toRoute(['checklist-template/create', 'boardId' => $board->id])
            ]
        ]
    ]
];
?>

<div class="checklist-templates">
    <?= GridView::widget([
        'id' => 'checklistTemplates',
        'filterModel' => $searchModel,
        'dataProvider' => $dataProvider,
        'title' => $this->title,
        'toolbar' => Html::tag(
            'div',
            Html::responsiveButtons($buttons, true, false, true, 'xxl', true)
        ),
        'pjaxSettings' => [
            'options' => [
                'id' => 'checklistTemplatePjax',
                'timeout' => 3000
            ]
        ],
        'columns' => [
            [
                'attribute' => 'name',
                'responsiveHeaderColumn' => true
            ],
            [
                'attribute' => 'created_by',
                'value' => 'creator.fullname',
                'filter' => $users,
                'filterType' => GridView::FILTER_SELECT2,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => ''],
                    'pluginOptions' => ['allowClear' => true]
                ],
                'responsiveHeaderColumn' => true
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime'
            ],
            [
                'attribute' => 'elementCount',
            ],
            [
                'class' => \kartik\grid\ActionColumn::class,
                'buttons' => [
                    'update' => function ($url) {
                        return Html::a(FAS::i('pencil-alt'), '#hqDynamicModal', [
                            'title' => Yii::t('yii', 'Update'),
                            'data' => [
                                'bs-toggle' => 'modal',
                                'src' => $url
                            ]
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        $url .= '&boardId=' . $model->board_id;
                        return Html::a(FAS::i('trash-alt'), $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'data' => [
                                'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                'method' => 'post'
                            ]
                        ]);
                    }
                ],
                'visibleButtons' => [
                    'update' => function ($model) {
                        return Yii::$app->user->id === $model->created_by;
                    },
                    'delete' => function ($model) {
                        return Yii::$app->user->id === $model->created_by;
                    }
                ]
            ]
        ]
    ]);
    ?>
</div>
