<?php

use simialbi\yii2\kanban\models\ChecklistTemplateElement;
use yii\web\View;

/* @var $this View */
/* @var $model ChecklistTemplateElement */

$title = Yii::t('simialbi/kanban/checklist-template', 'New element');

echo $this->render('_element-form', [
    'model' => $model,
    'title' => $title
]);
