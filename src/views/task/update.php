<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TimeWindow;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ActiveForm;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this View */
/* @var $model Task */
/* @var $buckets array */
/* @var $statuses array */
/* @var $updateSeries boolean */
/* @var $users UserInterface[] */
/* @var $return string */
/* @var $readonly boolean */
/* @var $timeWindows TimeWindow[] */
/* @var $checklistTemplates array */

$today = (new \DateTime())->setTime(0, 0)->format('U');
$startDateTimeWindows = $model->start_date ? Yii::$app->formatter->asTimestamp($model->start_date) : 0;
if ($today > $startDateTimeWindows) {
    $startDateTimeWindows = $today;
}
$startDateTimeWindows = Yii::$app->formatter->asTimestamp($startDateTimeWindows) * 1000;

$endDateTimeWindows = null;
if ($model->end_date) {
    $tmp = (new \DateTime())
        ->setTimestamp(Yii::$app->formatter->asTimestamp($model->end_date))
        ->setTime(23, 59, 59);
    $endDateTimeWindows = $tmp->format('U') * 1000;
}

// We added option 'static' to the flatpickr so it stays with the input when scrolling.
// however, this wraps the flatpickr in a div with a class 'flatpickr-wrapper' that we need to remove here
$flatPickrOnReady = new JsExpression("function(selectedDates, dateStr, instance) {
    if ($(instance.calendarContainer).parent().hasClass('flatpickr-wrapper')) {
        $(instance.calendarContainer).unwrap();
    }
    return;
}");

// flatpickr options
$flatPickrOptions = [
    'locale' => explode('-', Yii::$app->language)[0],
    'dateFormat' => 'd.m.Y H:i',
    'enableTime' => true,
    'time_24hr' => true,
    'defaultHour' => 12,
    'minuteIncrement' => 15,
    'allowInput' => true,
    'minDate' => $startDateTimeWindows,
    'maxDate' => $endDateTimeWindows,
    'static' => true,
    'onReady' => $flatPickrOnReady
];

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
?>
    <div class="kanban-task-modal">
        <?php
        if ($return === 'todo') {
            $dataTurboFrame = 'kanban-todo-frame';
        } elseif ($return === 'calendar') {
            // use this to return javascript
            $dataTurboFrame = 'js-turbo-frame';
        } else {
            if ($model->isRecurrentInstance()) {
                $dataTurboFrame = 'bucket-' . $model->bucket_id . '-frame';
            } else {
                $dataTurboFrame = 'task-' . $model->id . '-frame';
            }
        }

        $form = ActiveForm::begin([
            'id' => 'sa-kanban-task-modal-form',
            'action' => [
                'task/update',
                'id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
                'updateSeries' => $updateSeries,
                'return' => $return,
                'readonly' => $readonly
            ],
//            'validateOnSubmit' => false,
            'fieldConfig' => [
                'labelOptions' => [
                    'class' => ['col-form-label-sm', 'py-0', 'fw-bold']
                ],
                'inputOptions' => [
                    'class' => ['form-control', 'form-control-sm']
                ]
            ],
            'options' => [
                'enctype' => 'multipart/form-data',
                'data' => [
                    'turbo-frame' => $dataTurboFrame
                ]
            ]
        ]); ?>
        <div class="modal-header">
            <?php
            /*
             * Render title and creator / updater information
             */
            echo $this->render('_update_header', [
                'model' => $model,
                'form' => $form,
            ]);
            ?>
            <button type="button" class="btn btn-default bg-light align-self-start ms-3" data-bs-dismiss="modal"
                    aria-label="Close">
                <?= FAS::i('xmark'); ?>
            </button>
        </div>
        <div class="modal-body p-0">
            <?php
            /*
             * Render user dropdown for assignees
             */
            echo $this->render('_user-dropdown', [
                'id' => 0,
                'users' => $users,
                'assignees' => $model->assignees,
                'enableAddRemoveAll' => false
            ]);
            ?>

            <?php
            /*
             * Render general task information:
             * - bucket
             * - status
             * - start and end date
             */
            echo $this->render('_update_general', [
                'form' => $form,
                'model' => $model,
                'buckets' => $buckets,
                'flatPickrOnReady' => $flatPickrOnReady,
                'statuses' => $statuses,
                'readonly' => $readonly,
            ]);
            ?>

            <div class="px-3 pb-3 bg-lighter">
                <?php
                /*
                 * Render recurrence information
                 */
                echo $this->render('_update_recurrence', [
                    'form' => $form,
                    'model' => $model,
                ]);

                /*
                 * Render time windows information
                 */
                echo $this->render('_update_time-windows', [
                    'form' => $form,
                    'model' => $model,
                    'timeWindows' => $timeWindows,
                    'flatPickrOptions' => $flatPickrOptions,
                    'today' => $today,
                ]);
                ?>
            </div>

            <?php
            /*
             * Render client and responsible information
             */
            echo $this->render('_update_responsible_client', [
                'form' => $form,
                'model' => $model,
                'isAutor' => $model->created_by == \Yii::$app->user->id,
                'users' => $users,
            ]);

            /*
             * Render description
             */
            echo $this->render('_update_description', [
                'form' => $form,
                'model' => $model,
            ]);

            /*
             * Render checklist
             */
            echo $this->render('_update_checklist-items', [
                'form' => $form,
                'model' => $model,
                'checklistTemplates' => $checklistTemplates,
                'flatPickrOnReady' => $flatPickrOnReady,
            ]);

            /*
             * Render attachments
             */
            echo $this->render('_update_attachments_links', [
                'form' => $form,
                'model' => $model,
                'readonly' => $readonly,
                'updateSeries' => $updateSeries,
            ]);

            /*
             * Render subtasks
             */
            echo $this->render('_update_subtasks', [
                'model' => $model,
                'statuses' => $statuses,
            ]);

            /*
             * Render comments
             */
            echo $this->render('_update_comments', [
                'model' => $model,
            ]);
            ?>
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
$js = <<<JS
// Catch the form submit that the yii validator fires and trigger the requestSubmit for turbo
jQuery('#sa-kanban-task-modal-form').on('beforeSubmit', function() {
    jQuery(this).off('beforeSubmit');    // prevent infinite loop
    this.requestSubmit();
    return false;
});
JS;

$this->registerJs($js);

Frame::end();
