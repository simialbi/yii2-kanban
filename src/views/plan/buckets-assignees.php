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

$query = Task::find()
    ->alias('t')
    ->distinct()
    ->joinWith('assignments u')
    ->innerJoinWith('board b')
    ->select(['{{u}}.[[user_id]]'])
    ->where(['{{b}}.[[id]]' => $model->id])
    ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
    ->asArray();

if ($readonly) {
    $query->andWhere([
        'or',
        ['{{u}}.[[user_id]]' => Yii::$app->user->id],
        ['{{t}}.[[responsible_id]]' => Yii::$app->user->id]
    ]);
}

foreach ($query->column() as $id) {
    echo Frame::widget([
        'options' => [
            'id' => 'bucket-assignee-' . $id . '-frame',
            'src' => Url::to(['bucket/view-assignee', 'id' => $id, 'boardId' => $model->id, 'readonly' => $readonly]),
            'class' => ['kanban-bucket', 'me-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
            'data' => ['id' => $id, 'action' => 'change-assignee', 'key-name' => 'user_id', 'sort' => 'false']
        ]
    ]);
}
echo Html::endTag('div');
