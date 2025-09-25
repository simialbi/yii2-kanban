<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model Task */
/* @var $users array */
/* @var $group string */

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
$options = [
    'id' => 'sa-kanban-task-modal-form',
    'fieldConfig' => [
        'labelOptions' => [
            'class' => ['form-label', 'col-form-label-sm', 'py-0']
        ],
        'inputOptions' => [
            'class' => ['form-control', 'form-control-sm']
        ]
    ]
];
if ($group !== 'assignee') {
    $options['validateOnSubmit'] = false;

    if ($group === 'bucket') {
        /*
        $options['options'] = [
            'data' => [
                'turbo-frame' => 'bucket-' . $model->bucket_id . '-frame'
            ]
        ];
        */
    } elseif ($group === 'status') {
        $options['options'] = [
            'data' => [
                'turbo-frame' => 'bucket-status-' . $model->status . '-frame'
            ]
        ];
    }
}
?>
    <div class="kanban-task-modal">
        <?php $form = ActiveForm::begin($options); ?>
        <div class="modal-header">
            <h5 class="modal-title"><?= Yii::t('simialbi/kanban/task', 'Create task per each user'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p>
            <?= Yii::t(
                'simialbi/kanban/task',
                'Create an individual copy of this task for each of the selected users and assign it.'
            ); ?>
            </p>
            <div class="row">
                <div class="col-12">
                    <?= $this->render('_user-dropdown', [
                        'users' => $users,
                        'id' => 0,
                        'assignees' => [],
                        'enableAddRemoveAll' => true
                    ]) ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <?= Html::button(Yii::t('simialbi/kanban', 'Close'), [
                'type' => 'button',
                'class' => ['btn', 'btn-dark'],
                'data' => [
                    'bs-dismiss' => 'modal'
                ],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Close')
                ]
            ]); ?>
            <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
                'type' => 'button',
                'class' => ['btn', 'btn-success'],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Save')
                ]
            ]); ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
<?php
Frame::end();
