<?php

use simialbi\yii2\hideseek\HideSeek;
use yii\bootstrap4\ButtonDropdown;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */

$group = Yii::$app->request->getQueryParam('group', 'bucket');
$action = Yii::$app->controller->action->id;
?>
<div class="row">
    <div class="col-8 col-md-4 d-flex flex-row align-items-center">
        <?= Html::img($model->visual); ?>
        <div class="ml-3">
            <h2 class="mb-0"><?= Html::encode($model->name); ?></h2>
            <small class="text-muted"><?= Yii::$app->formatter->asDatetime($model->updated_at); ?></small>
        </div>
    </div>
    <div class="d-none d-md-flex col-md-4 col-lg-3 flex-row align-items-center">
        <?= Nav::widget([
            'items' => [
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Board'),
                    'url' => ['view', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'view'
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Charts'),
                    'url' => ['chart', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'chart'
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Schedule'),
                    'url' => ['schedule', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'schedule'
                ]
            ],
            'options' => [
                'class' => 'nav-pills'
            ]
        ]); ?>
    </div>
    <div class="col-4 col-lg-5 d-flex flex-row align-items-center justify-content-end">
        <?= HideSeek::widget([
            'options' => [
                'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
            ],
            'clientOptions' => [
                'list' => '.kanban-tasks'
            ]
        ]);?>
        <?php if ($action === 'view'): ?>
            <?= ButtonDropdown::widget([
                'label' => Yii::t('simialbi/kanban/plan', 'Group by <b>{group}</b>', ['group' => $group]),
                'encodeLabel' => false,
                'dropdown' => [
                    'items' => [
                        [
                            'label' => Yii::t('simialbi/kanban/plan', 'Bucket'),
                            'url' => ['plan/view', 'id' => $model->id, 'group' => 'bucket'],
                            'active' => ($group === 'bucket')
                        ],
                        [
                            'label' => Yii::t('simialbi/kanban/plan', 'Assigned to'),
                            'url' => ['plan/view', 'id' => $model->id, 'group' => 'assignee'],
                            'active' => ($group === 'assignee')
                        ],
                        [
                            'label' => Yii::t('simialbi/kanban/plan', 'Status'),
                            'url' => ['plan/view', 'id' => $model->id, 'group' => 'status'],
                            'active' => ($group === 'status')
                        ],
                        [
                            'label' => Yii::t('simialbi/kanban/plan', 'End date'),
                            'url' => ['plan/view', 'id' => $model->id, 'group' => 'date'],
                            'active' => ($group === 'date')
                        ]
                    ]
                ]
            ]); ?>
        <?php endif; ?>
    </div>
</div>
