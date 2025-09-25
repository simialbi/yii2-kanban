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
