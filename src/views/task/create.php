<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Bucket */
/* @var $task \simialbi\yii2\kanban\models\Task */
/* @var $users \simialbi\yii2\kanban\models\UserInterface[] */
/* @var $statuses array */

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
        <?= Html::button('<span aria-hidden="true">' . FAS::i('times') . '</span>', [
            'type' => 'button',
            'class' => ['close', 'position-absolute'],
            'style' => [
                'font-size' => '1rem',
                'right' => '.25rem'
            ],
            'onclick' => 'jQuery(this).closest(\'form\').remove()',
            'data' => [
                'dismiss' => 'card'
            ],
            'aria' => [
                'label' => Yii::t('simialbi/kanban', 'Close')
            ]
        ]); ?>
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
            <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
                'class' => ['list-group-item', 'list-group-item-success', 'list-group-item-action']
            ]) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?php foreach ($model->tasks as $task): ?>
        <?= $this->render('/task/item', [
            'model' => $task,
            'users' => $users,
            'statuses' => $statuses
        ]); ?>
    <?php endforeach; ?>
</div>
<?php Pjax::end(); ?>
