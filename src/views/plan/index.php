<?php

use simialbi\yii2\kanban\KanbanAsset;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */

KanbanAsset::register($this);

$this->title = Yii::t('simialbi/kanban/plan', 'Kanban Hub');
$this->params['breadcrumbs'] = [$this->title];
?>

<div class="kanban-plan-index">
    <h1><?= Html::encode($this->title); ?></h1>

    <div class="mt-3 d-flex flex-row justify-content-start flex-wrap">
        <?php $i = 0; ?>
        <?php foreach ($boards as $board): ?>
            <div class="kanban-board card mb-3<?php if (++$i % 3 !== 0):?> mr-2<?php endif; ?>">
                <div class="row no-gutters">
                    <div class="col-3 col-md-4">
                        <?= Html::img($board->visual, [
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
                            <small class="text-muted"><?=Yii::$app->formatter->asDatetime($board->updated_at);?></small>
                            <?= Html::a('', ['plan/view', 'id' => $board->id], [
                                'class' => ['stretched-link']
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="kanban-board card mr-2 mb-3">
            <div class="card-body">
                <h5 class="card-title mb-0"><?= Html::encode(Yii::t('simialbi/kanban/plan', 'Create board')); ?></h5>
                <?= Html::a('', ['plan/create'], [
                    'class' => ['stretched-link']
                ]); ?>
            </div>
        </div>
    </div>
</div>
