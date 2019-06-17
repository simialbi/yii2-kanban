<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */

?>
<div class="row">
    <div class="col-12 col-md-4 d-flex flex-row align-items-center">
        <?= Html::img($model->visual); ?>
        <div class="ml-3">
            <h2 class="mb-0"><?= Html::encode($model->name); ?></h2>
            <small class="text-muted"><?= Yii::$app->formatter->asDatetime($model->updated_at); ?></small>
        </div>
    </div>
    <div class="d-none d-md-flex col-md-4 flex-row align-items-center">
        <?= Nav::widget([
            'items' => [
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Board'),
                    'url' => ['view', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => true
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Charts'),
                    'url' => ['chart', 'id' => $model->id],
                    'linkOptions' => []
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Schedule'),
                    'url' => ['schedule', 'id' => $model->id],
                    'linkOptions' => []
                ]
            ],
            'options' => [
                'class' => 'nav-pills'
            ]
        ]); ?>
    </div>
</div>
