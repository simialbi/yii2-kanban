<?php

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Connection */
/* @var $buckets array */

$this->title = Yii::t('simialbi/kanban/connection', 'Create connection');
$this->params['breadcrumbs'] = [
    ['label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'), 'url' => ['index']],
    $this->title
];

?>

<div>
    <?= $this->render('_form', [
        'model' => $model,
        'buckets' => $buckets
    ]); ?>
</div>
