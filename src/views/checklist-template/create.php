<?php

use simialbi\yii2\kanban\models\ChecklistTemplate;
use yii\web\View;

/* @var $this View */
/* @var $model ChecklistTemplate */

$title = Yii::t('simialbi/kanban/checklist-template', 'New template');

echo $this->render('_form', [
    'model' => $model,
    'title' => $title,
]);
