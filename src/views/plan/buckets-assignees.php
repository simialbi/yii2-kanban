<?php

use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $readonly boolean */


echo Html::beginTag('div', ['class' => ['d-flex', 'flex-row', 'sw-wrapper']]);

$query = Task::find()
    ->alias('t')
    ->distinct(true)
    ->joinWith('assignments u')
    ->innerJoinWith('board b')
    ->select(['{{u}}.[[user_id]]'])
    ->where(['{{b}}.[[id]]' => $model->id])
    ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
    ->asArray(true);

if ($readonly) {
    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
}

foreach ($query->column() as $id) {
    echo Frame::widget([
        'options' => [
            'id' => 'bucket-assignee-' . $id . '-frame',
            'src' => Url::to(['bucket/view-assignee', 'id' => $id, 'boardId' => $model->id, 'readonly' => $readonly]),
            'class' => ['kanban-bucket', 'mr-md-4', 'd-flex', 'flex-column', 'flex-shrink-0'],
            'data' => ['id' => $id, 'action' => 'change-assignee', 'key-name' => 'user_id', 'sort' => 'false']
        ]
    ]);
}
echo Html::endTag('div');
