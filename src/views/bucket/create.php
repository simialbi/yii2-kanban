<?php

use yii\bootstrap4\ActiveForm;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */

?>

<?php Pjax::begin([
    'id' => 'createBucketPjax',
    'formSelector' => '#createBucketForm',
    'enablePushState' => false,
    'clientOptions' => ['skipOuterContainers' => true]
]); ?>
<div class="kanban-bucket">
    <?php $form = ActiveForm::begin([
        'action' => ['bucket/create', 'boardId' => $model->board_id],
        'id' => 'createBucketForm',
        'fieldConfig' => function ($model, $attribute) {
            /* @var $model \yii\base\Model */
            return [
                'labelOptions' => ['class' => 'sr-only'],
                'inputOptions' => [
                    'placeholder' => $model->getAttributeLabel($attribute)
                ]
            ];
        }
    ]); ?>
    <?= $form->field($model, 'name')->textInput(); ?>
    <?php ActiveForm::end(); ?>
</div>
<?php Pjax::end(); ?>
