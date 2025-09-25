<?php

use simialbi\yii2\kanban\models\ChecklistTemplate;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\DetailView;
use yii\widgets\Pjax;

/* @var $this View */
/* @var $model ChecklistTemplate */

$this->title = Yii::t('simialbi/kanban/checklist-template', 'Update template');
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'All plans'),
        'url' => ['plan/index']
    ],
    [
        'label' => $model->board->name . ' - ' . Yii::t('simialbi/kanban/plan', 'Checklist templates'),
        'url' => ['checklist-template/index', 'boardId' => $model->board_id]
    ],
    $this->title
];
if (Yii::$app->user->id === $model->created_by) {
    $this->params['links'] = [
        [
            'label' => Yii::t('simialbi/kanban/checklist-template', 'New element'),
            'icon' => 'plus',
            'url' => '#hqDynamicModal',
            'options' => [
                'class' => ['btn', 'btn-primary'],
                'data' => [
                    'bs-toggle' => 'modal',
                    'src' => Url::toRoute(['checklist-template/create-element', 'templateId' => $model->id])
                ]
            ]
        ]
    ];
}
?>

<div class="checklist-template-update">
    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="card panel-default">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <?= Yii::t('simialbi/kanban/checklist-template', 'Informations'); ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => [
                            'class' => ['table', 'table-striped', 'detail-view', 'm-0']
                        ],
                        'attributes' => [
                            'name',
                            [
                                'attribute' => 'created_by',
                                'value' => $model->creator->fullname
                            ],
                            'created_at:datetime',
                        ]
                    ]); ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <?php
            Pjax::begin([
                'id' => 'checklistTemplateElementsPjax',
                'formSelector' => '#checklistTemplateElementForm',
                'enablePushState' => false,
                'timeout' => 3000
            ]);
            echo $this->render('_elements', [
                'model' => $model,
            ]);
            Pjax::end();
            ?>
        </div>
    </div>
</div>
