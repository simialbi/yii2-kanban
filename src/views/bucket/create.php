<?php

use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Frame;
use yii\base\Model;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model Bucket */
/* @var $users UserInterface[] */
/* @var $statuses array */

Frame::begin([
    'options' => [
        'id' => 'create-bucket-frame'
    ]
]); ?>
<div data-id="<?= $model->id; ?>">
    <?php $form = ActiveForm::begin([
        'action' => ['bucket/create', 'boardId' => $model->board_id],
        'id' => 'sa-kanban-create-bucket-form',
        'fieldConfig' => function ($model, $attribute) {
            /* @var $model Model */
            return [
                'labelOptions' => ['class' => 'visually-hidden'],
                'inputOptions' => [
                    'class' => ['form-control', 'form-control-sm'],
                    'placeholder' => $model->getAttributeLabel($attribute)
                ]
            ];
        }
    ]); ?>
    <?= $form->field($model, 'name')->textInput(['autofocus' => true]); ?>
    <?php ActiveForm::end(); ?>
</div>
<?php Frame::end(); ?>
