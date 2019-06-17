<?php

use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */

$this->title = Yii::t('simialbi/kanban/plan', 'Welcome');
$this->params['breadcrumbs'] = [$this->title];
?>

<div class="kanban-plan-index">
    <h1><?= Html::encode($this->title); ?></h1>

    <div class="mt-3 d-flex flex-row justify-content-start flex-wrap">
        <?php foreach ($boards as $board): ?>
            <div class="card mr-2" style="max-width: 32%;">
                <div class="row no-gutters">
                    <div class="col-3 col-md-4">
                        <?= Html::img($board->image, [
                            'style' => [
                                'height' => '100%',
                                'width' => '100%',
                                'object-fit' => 'cover',
                                'object-position' => 'center'
                            ]
                        ]); ?>
                    </div>
                    <div class="col-9 col-md-8">
                        <div class="card-body">
                            <h5 class="card-title"><?= Html::encode($board->name); ?></h5>
                            <?= Html::a('', ['plan/view', 'id' => $board->id], [
                                'class' => ['stretched-link']
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="card mr-2" style="max-width: 32%;">
            <div class="card-body">
                <h5 class="card-title"><?= Html::encode(Yii::t('simialbi/kanban/plan', 'Create board')); ?></h5>
                <?= Html::a('', ['plan/create'], [
                    'class' => ['stretched-link']
                ]); ?>
            </div>
        </div>
    </div>
</div>
