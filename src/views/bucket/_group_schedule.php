<?php

use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $statuses array */

foreach ($model->buckets as $bucket) {
    $tasks = $bucket->getTasks()
        ->where(['start_date' => null, 'end_date' => null])
        ->orderBy(['sort' => SORT_ASC])
        ->all();
    echo Html::tag('div', $this->render('/bucket/_item', [
        'statuses' => $statuses,
        'id' => $bucket->id,
        'boardId' => $model->id,
        'title' => $bucket->name,
        'tasks' => $tasks,
        'keyName' => 'bucketId',
        'action' => 'change-parent',
        'sort' => true
    ]), [
        'class' => ['mb-5']
    ]);
}
