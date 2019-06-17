<?php

use kartik\date\DatePicker;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $task \simialbi\yii2\kanban\models\Task */

?>

<?php Pjax::begin([
    'id' => 'createTaskPjax',
    'formSelector' => '#createTaskForm',
    'enablePushState' => false,
    'clientOptions' => ['skipOuterContainers' => true]
]); ?>
<?= Html::a('+', ['task/create', 'bucketId' => $model->id], [
    'class' => ['btn', 'btn-primary', 'btn-block']
]); ?>

<div class="kanban-tasks mt-5">
    <?php $form = ActiveForm::begin([
        'action' => ['task/create', 'bucketId' => $model->id],
        'id' => 'createTaskForm',
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
    <div class="card">
        <div class="card-body">
            <?= $form->field($task, 'subject')->textInput(); ?>
            <?= $form->field($task, 'end_date', [
                'options' => [
                    'class' => ['form-group', 'mb-0']
                ]
            ])->widget(DatePicker::class, [
                'bsVersion' => '4',
                'type' => DatePicker::TYPE_INPUT,
                'pluginOptions' => [
                    'autoclose' => true
                ],
                'options' => [
                    'readonly' => true
                ]
            ]); ?>
        </div>
        <div class="list-group list-group-flush">
            <?=Html::submitButton('Save', ['class' => ['list-group-item', 'list-group-item-dark', 'list-group-item-action']])?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?php foreach ($model->tasks as $task): ?>
        <?= $this->render('/task/item', ['model' => $task]); ?>
    <?php endforeach; ?>
</div>
<?php Pjax::end(); ?>
