<?php

use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $readonly boolean */

echo Html::beginTag('div', ['class' => ['d-flex', 'flex-row', 'sw-wrapper']]);

$query = $model->getTasks()
    ->alias('t')
    ->distinct(true)
    ->select(['{{t}}.[[status]]'])
    ->orderBy(['status' => SORT_DESC])
    ->asArray(true);

foreach ($query->column() as $id) {
    echo Frame::widget([
        'options' => [
            'id' => 'bucket-status-' . $id . '-frame',
            'src' => Url::to(['bucket/view-status', 'status' => $id, 'boardId' => $model->id, 'readonly' => $readonly]),
            'class' => ['kanban-bucket', 'mr-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
            'data' => ['id' => $id, 'action' => 'change-status', 'key-name' => 'status', 'sort' => 'false']
        ]
    ]);
}
echo Html::endTag('div');
