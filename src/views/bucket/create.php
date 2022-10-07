<?php

use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $users \simialbi\yii2\models\UserInterface[] */
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
            /* @var $model \yii\base\Model */
            return [
                'labelOptions' => ['class' => 'sr-only'],
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
