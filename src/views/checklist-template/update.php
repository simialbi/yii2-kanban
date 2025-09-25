<?php

use simialbi\yii2\kanban\models\ChecklistTemplate;
use yii\web\View;

/* @var $this View */
/* @var $model ChecklistTemplate */

$title = Yii::t('simialbi/kanban/checklist-template', 'Update template');

echo $this->render('_form', [
    'model' => $model,
    'title' => $title,
]);
