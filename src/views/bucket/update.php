<?php

use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $statuses array */

Frame::begin([
    'options' => [
        'id' => 'update-bucket-' . $model->id . '-frame'
    ]
]);
?>
<div class="kanban-bucket-header">
    <?php $form = ActiveForm::begin([
        'action' => ['bucket/update', 'id' => $model->id],
        'id' => 'updateBucketForm' . $model->id,
        'validateOnSubmit' => false,
        'options' => [
            'data' => [
                'turbo-frame' => 'update-bucket-' . $model->id . '-frame'
            ]
        ],
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
