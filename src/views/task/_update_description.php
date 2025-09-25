<?php

use sandritsch91\yii2\froala\FroalaEditor;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $form ActiveForm */
/* @var $this View */
/* @var $model Task */

?>

<div class="px-3 pb-3 bg-lighter">
    <div class="row g-3 mt-0">
        <?php $showDescription = $form->field($model, 'card_show_description', [
            'options' => ['class' => ''],
            'labelOptions' => [
                'class' => 'form-label m-0'
            ],
        ])->checkbox(); ?>
        <?= $form->field($model, 'description', [
            'template' => "<div class=\"d-flex justify-content-between\">{label}$showDescription</div>\n{input}\n{hint}\n{error}",
            'inputOptions' => ['id' => 'taskModalFroala-description'],
            'options' => [
                'class' => ['col-12']
            ]
        ])->widget(FroalaEditor::class, [
            'clientOptions' => [
                'pluginsEnabled' => ['link'],
                'toolbarButtons' => [
                    '|', 'insertLink'
                ],
                'linkEditButtons' => []
            ]
        ]); ?>
    </div>
</div>
