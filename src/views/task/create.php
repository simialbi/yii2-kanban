<?php

use sandritsch91\yii2\flatpickr\Flatpickr;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\models\UserInterface;
use yii\base\Model;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $board Board */
/* @var $task Task */
/* @var $id integer|string */
/* @var $keyName string */
/* @var $users UserInterface[] */
/* @var $buckets Bucket[] */

$form = ActiveForm::begin([
    'action' => ['task/create', 'boardId' => $board->id, $keyName => $id],
    'options' => [
        'class' => ['mt-2', 'mt-md-4'],
        'data' => [
            'turbo-frame' => 'bucket-' . $id . '-frame'
        ]
    ],
    'id' => 'sa-kanban-create-task-form',
    'validateOnSubmit' => false,
    'fieldConfig' => function ($model, $attribute) {
        /* @var $model Model */
        return [
            'labelOptions' => ['class' => 'visually-hidden'],
            'inputOptions' => [
                'placeholder' => $model->getAttributeLabel($attribute)
            ]
        ];
    }
]);
?>
<div class="card">
    <?= Html::button('', [
        'type' => 'button',
        'class' => ['btn-close', 'position-absolute'],
        'style' => [
            'font-size' => '0.65rem',
            'right' => '0'
        ],
        'data' => [
            'bs-target' => '#bucket-' . $id . '-create-task',
            'bs-toggle' => 'collapse'
        ],
        'aria' => [
            'label' => Yii::t('simialbi/kanban', 'Close')
        ]
    ]); ?>
    <div class="card-body">
        <?= $form->field($task, 'subject')->textInput(); ?>
        <?php if (!empty($buckets)): ?>
            <?= $form->field($task, 'bucket_id')->dropDownList($buckets); ?>
        <?php endif; ?>
        <?= $form->field($task, 'end_date', [
            'options' => [
                'class' => ['mb-3']
            ]
        ])->widget(Flatpickr::class, [
            'options' => [
                'id' => 'flatpickr-create-task-' . $id,
            ]
        ]); ?>
        <?php if ($keyName !== 'userId'): ?>
            <?= $this->render('_user-dropdown', [
                'users' => $users,
                'id' => $id,
                'assignees' => [Yii::$app->user->identityClass::findIdentity(Yii::$app->user->id)],
                'enableAddRemoveAll' => false
            ]) ?>
        <?php endif; ?>
    </div>
    <div class="list-group list-group-flush">
        <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
            'class' => ['list-group-item', 'list-group-item-success', 'list-group-item-action']
        ]) ?>
    </div>
</div>
<?php
ActiveForm::end();
