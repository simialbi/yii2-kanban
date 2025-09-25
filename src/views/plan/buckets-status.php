<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Board */
/* @var $readonly boolean */

echo Html::beginTag('div', ['class' => ['d-flex', 'flex-row', 'h-100', 'sw-wrapper']]);

$query = $model->getTasks()
    ->distinct()
    ->select(['[[status]]'])
    ->orderBy(['[[status]]' => SORT_DESC])
    ->asArray();
if ($readonly) {
    $query
        ->innerJoinWith('assignments u')
        ->andWhere([
            'or',
            ['{{u}}.[[user_id]]' => Yii::$app->user->id],
            [Task::tableName() . '.[[responsible_id]]' => Yii::$app->user->id]
        ]);
}

foreach ($query->column() as $id) {
    echo Frame::widget([
        'options' => [
            'id' => 'bucket-status-' . $id . '-frame',
            'src' => Url::to(['bucket/view-status', 'status' => $id, 'boardId' => $model->id, 'readonly' => $readonly]),
            'class' => ['kanban-bucket', 'me-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
            'data' => ['id' => $id, 'action' => 'change-status', 'key-name' => 'status', 'sort' => 'false']
        ]
    ]);
}
echo Html::endTag('div');
