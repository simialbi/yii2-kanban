<?php

use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $readonly boolean */

echo Html::beginTag('div', ['class' => ['d-flex', 'flex-row', 'kanban-plan-sortable', 'sw-wrapper']]);
foreach ($model->buckets as $bucket) {
    echo Frame::widget([
        'options' => [
            'id' => 'bucket-' . $bucket->id . '-frame',
            'src' => Url::to(['bucket/view', 'id' => $bucket->id, 'readonly' => $readonly]),
            'class' => ['kanban-bucket', 'mr-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
            'data' => ['id' => $bucket->id, 'action' => 'change-parent', 'key-name' => 'bucket_id', 'sort' => 'true']
        ]
    ]);
}
if (!$readonly) {
    Frame::begin([
        'options' => [
            'id' => 'create-bucket-frame',
            'class' => ['kanban-bucket', 'mr-md-4', 'd-flex', 'flex-column', 'flex-shrink-0', 'pt-4'],
        ]
    ]);
    ?>
    <div>
        <h5 class="text-center">
            <?= Html::a(Yii::t('simialbi/kanban/plan', 'Create bucket'), [
                'bucket/create',
                'boardId' => $model->id
            ], [
                'data' => [
                    'turbo' => 'true'
                ]
            ]); ?>
        </h5>
    </div>
    <?php
    Frame::end();
}
echo Html::endTag('div');
