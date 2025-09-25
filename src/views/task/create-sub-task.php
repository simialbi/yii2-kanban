<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\ActiveForm;
use yii\web\JsExpression;
use yii\web\View;

/* @var $model Task */
/* @var $users array */
/* @var $this View */
/* @var $buckets array */
/* @var $statuses array */

// We added option 'static' to the flatpickr so it stays with the input when scrolling.
// however, this wraps the flatpickr in a div with a class 'flatpickr-wrapper' that we need to remove here
$flatPickrOnReady = new JsExpression("function(selectedDates, dateStr, instance) {
    if (jQuery(instance.calendarContainer).parent().hasClass('flatpickr-wrapper')) {
        jQuery(instance.calendarContainer).unwrap();
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
    'minDate' => Yii::$app->formatter->asTimestamp('today') * 1000,
//    'maxDate' => $endDateTimeWindows,
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
        $form = ActiveForm::begin([
            'id' => 'kanban-create-sub-task-form',
            'action' => ['task/create-sub-task', 'id' => $model->parent_id],
            'options' => [
                'data' => [
//                    'turbo' => 'true'
                ]
            ],
            'fieldConfig' => [
                'labelOptions' => [
                    'class' => ['col-form-label-sm', 'py-0', 'fw-bold']
                ],
                'inputOptions' => [
                    'class' => ['form-control', 'form-control-sm']
                ]
            ],
        ]);
        ?>
        <div class="modal-header">
            <h5 class="modal-title"><?= Yii::t('simialbi/kanban', 'Create sub task') ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
            <div class="px-3 pt-3">
                <div class="row g-3">
                    <div class="col-12">
                        <?= $form->field($model, 'subject')->textInput([
                            'class' => ['form-control']
                        ]); ?>
                    </div>
                </div>
            </div>

            <div class="p-2 pb-3-5">
                <div class="row g-3">
                    <div class="col-12">
                        <?= $this->render('_user-dropdown', [
                            'id' => 0,
                            'users' => $users,
                            'assignees' => $model->assignees,
                            'enableAddRemoveAll' => false
                        ]); ?>
                    </div>
                </div>
            </div>

            <div class="px-3 pb-3">
                <div class="row g-3">
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
                    ]);
                    ?>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="position: unset;">
            <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
                'type' => 'button',
                'class' => ['btn', 'btn-primary'],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Save')
                ]
            ]) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
<?php
$js = <<<JS
// Catch the form submit that the yii validator fires and trigger the requestSubmit for turbo
jQuery('#kanban-create-sub-task-form').on('beforeSubmit', function() {
    jQuery(this).off('beforeSubmit');    // prevent infinite loop
    this.requestSubmit();
    return false;
});
JS;

$this->registerJs($js);

Frame::end();
