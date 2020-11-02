<?php

use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $tasksScheduled array */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */

foreach ($model->buckets as $bucket) {
    $query = $bucket->getTasks()
        ->where(['start_date' => null, 'end_date' => null])
        ->orderBy(['sort' => SORT_ASC]);

    if ($readonly) {
        $query->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
    }

    echo Html::tag('div', $this->render('/bucket/_item', [
        'readonly' => $readonly,
        'statuses' => $statuses,
        'users' => $users,
        'id' => $bucket->id,
        'boardId' => $model->id,
        'title' => $bucket->name,
        'tasks' => $query->all(),
        'completedTasks' => [],
        'keyName' => 'bucketId',
        'action' => 'change-parent',
        'sort' => true,
        'renderContext' => false
    ]), [
        'class' => ['mb-5']
    ]);
}
