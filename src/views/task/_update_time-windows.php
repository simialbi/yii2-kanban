<?php

use rmrevin\yii\fontawesome\FAS;
use sandritsch91\yii2\flatpickr\Flatpickr;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TimeWindow;
use tonic\hq\widgets\DynamicForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\View;

/* @var $form ActiveForm */
/* @var $timeWindows TimeWindow[] */
/* @var $this View */
/* @var $model Task */
/* @var $flatPickrOptions array */
/* @var $today string */

?>
<div class="row g-3 mt-0 <?php if ($model->isRecurrentInstance() || $model->recurrence_parent_id !== null): ?>d-none<?php endif; ?>">
    <div class="col-12 mt-0">
        <div class="form-check time_windows-wrapper">
            <?= Html::checkbox('time_windows', !$timeWindows[0]->isNewRecord, [
                'id' => 'time_windows',
                'class' => ['form-check-input']
            ]) ?>
            <label class="col-form-label-sm py-0 fw-bold form-check-label" for="time_windows">
                <?= Yii::t('simialbi/kanban/time-window', 'Time windows') ?>
            </label>
        </div>
        <div class="collapse time_windows-collapse <?= $timeWindows[0]->isNewRecord ? '' : 'show' ?>">
            <?php
            DynamicForm::begin([
                'widgetContainer' => 'times_wrapper',
                'widgetBody' => '.times',
                'widgetItem' => '.time-item',
                'min' => 0,
                'insertButton' => '.time-add',
                'deleteButton' => '.time-del',
                'labelSelector' => '.panel-title > a',
                'newLabel' => Yii::t('simialbi/kanban/time-window', 'New time window'),
                'model' => $timeWindows[0],
                'formId' => 'sa-kanban-task-modal-form',
                'formFields' => ['time_start', 'time_end'],
            ]); ?>
                <div class="times">
                    <div class="row mb-3">
                        <div class="col-12 text-end">
                            <?= Html::a(FAS::i('plus')->fixedWidth(), 'javascript:;', [
                                'class' => ['btn', 'btn-primary', 'btn-sm', 'time-add']
                            ]) ?>
                        </div>
                    </div>
                    <?php
                    $index = 0;
                    foreach ($timeWindows as $timeWindow):
                        $isOutOfBounds = Yii::$app->formatter->asTimestamp($timeWindow->time_end) < $today;
                        if (!$isOutOfBounds && $model->start_date && $timeWindow->time_start) {
                            $isOutOfBounds = Yii::$app->formatter->asTimestamp($timeWindow->time_start) < Yii::$app->formatter->asTimestamp($model->start_date);
                        }
                        if (!$isOutOfBounds && $model->end_date && $timeWindow->time_end) {
                            $isOutOfBounds = Yii::$app->formatter->asTimestamp($timeWindow->time_end) > Yii::$app->formatter->asTimestamp($model->end_date);
                        }
                        ?>
                            <div class="time-item">
                                <div class="row">
                                    <div class="col-4">
                                        <?php
                                        echo $form
                                            ->field($timeWindow, '[' . $index . ']id', [
                                                'options' => [
                                                    'class' => []
                                                ]
                                            ])
                                            ->hiddenInput()
                                            ->label(false);
                                        if ($isOutOfBounds) {
                                            echo $form->field($timeWindow, '[' . $index . ']time_start', [
                                                'options' => [
                                                    'class' => ['mb-2', 'position-relative']
                                                ]
                                            ])->textInput([
                                                'class' => ['form-control', 'form-control-sm', 'bg-light', 'pe-none'],
                                                'tabindex' => '-1',
                                                'readonly' => true
                                            ])->label(false);
                                        } else {
                                            echo $form
                                                ->field($timeWindow, '[' . $index . ']time_start', [
                                                    'options' => [
                                                        'class' => ['mb-2', 'position-relative']
                                                    ]
                                                ])
                                                ->widget(Flatpickr::class, [
                                                    'options' => [
                                                        'autocomplete' => 'off',
                                                        'class' => ['form-control', 'form-control-sm'],
                                                        'tabindex' => '-1',
                                                        'placeHolder' => $timeWindow->getAttributeLabel('time_start')
                                                    ],
                                                    'clientOptions' => $flatPickrOptions
                                                ])->label(false);
                                        }
                                        ?>
                                    </div>
                                    <div class="col-4">
                                        <?php
                                        if ($isOutOfBounds) {
                                            echo $form->field($timeWindow, '[' . $index . ']time_end', [
                                                'options' => [
                                                    'class' => ['mb-2', 'position-relative']
                                                ]
                                            ])->textInput([
                                                'class' => ['form-control', 'form-control-sm', 'bg-light', 'pe-none'],
                                                'tabindex' => '-1',
                                                'readonly' => true
                                            ])->label(false);
                                        } else {
                                            echo $form
                                                ->field($timeWindow, '[' . $index . ']time_end', [
                                                    'options' => [
                                                        'class' => ['mb-2', 'position-relative']
                                                    ]
                                                ])
                                                ->widget(Flatpickr::class, [
                                                    'options' => [
                                                        'autocomplete' => 'off',
                                                        'class' => ['form-control', 'form-control-sm'],
                                                        'tabindex' => '-1',
                                                        'placeHolder' => $timeWindow->getAttributeLabel('time_end')
                                                    ],
                                                    'clientOptions' => $flatPickrOptions
                                                ])->label(false);
                                        }
                                        ?>
                                    </div>
                                    <div class="col-4 text-end">
                                        <button type="button" class="btn btn-sm btn-danger time-del">
                                            <?= FAS::i('times')->fixedWidth() ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php
                        $index++;
                    endforeach;
                    ?>
                </div>
            <?php DynamicForm::end(); ?>
        </div>
    </div>
</div>

<?php
$startDateId = Html::getInputId($model, 'start_date');
$endDateId = Html::getInputId($model, 'end_date');

$flatPickrOnReady = ArrayHelper::remove($flatPickrOptions, 'onReady');
$jsonFlatpickrOptions = Json::encode($flatPickrOptions);

$js = <<<JS
var inputStart = jQuery('#$startDateId');
var inputEnd = jQuery('#$endDateId');

var flatPickrOptions = JSON.parse('$jsonFlatpickrOptions');
flatPickrOptions.onReady = $flatPickrOnReady;

var today = moment().startOf('day');

// on start date change
inputStart.on('change', function() {
    let val = jQuery(this).val();
    let start = moment(val, 'DD.MM.YYYY');
    let end = moment(inputEnd.val(), 'DD.MM.YYYY').endOf('day');

    // modify time window pickers
    flatPickrOptions.minDate = start.valueOf();
    jQuery('.times [name*="time_start"], .times [name*="time_end"]').each(function(){
        let thisDate = moment(jQuery(this).val(), 'DD.MM.YYYY HH:mm');
        let flatpickr = jQuery(this).prop('_flatpickr');

        if (thisDate.isBefore(today) || thisDate.isBefore(start) || thisDate.isAfter(end)) {
            jQuery(this).addClass('pe-none bg-light');
        } else {
            jQuery(this).removeClass('pe-none bg-light');
            if (!flatpickr) {
                jQuery(this).flatpickr(flatPickrOptions);
            } else {
                flatpickr.set('minDate', start.valueOf());
            }
        }
    });

    // modify end_date
    inputEnd.prop('_flatpickr').set('minDate', start.valueOf());
});

inputEnd.on('change', function() {
    let val = jQuery(this).val();
    let start = moment(inputStart.val(), 'DD.MM.YYYY').startOf('day');
    let end = moment(val, 'DD.MM.YYYY');
    end.endOf('day');

    // modify time window pickers
    flatPickrOptions.maxDate = end.valueOf();
    jQuery('.times [name*="time_start"], .times [name*="time_end"]').each(function(){
        let thisDate = moment(jQuery(this).val(), 'DD.MM.YYYY HH:mm');
        let flatpickr = jQuery(this).prop('_flatpickr');

        if (thisDate.isBefore(today) || thisDate.isBefore(start) || thisDate.isAfter(end)) {
            jQuery(this).addClass('pe-none bg-light');
        } else {
            jQuery(this).removeClass('pe-none bg-light');
            if (!flatpickr) {
                jQuery(this).flatpickr(flatPickrOptions);
            } else {
                flatpickr.set('maxDate', end.valueOf());
            }
        }
    });
});

// time windows collapse
jQuery('#time_windows').on('change', function () {
    var \$this = jQuery(this),
        \$collapse = \$this.closest('.time_windows-wrapper').nextAll('.time_windows-collapse');
    if (\$this.is(':checked')) {
        bootstrap.Collapse.getOrCreateInstance(\$collapse).show();

        // append one directly, if empty
        if (jQuery('.time-item').length === 0) {
            jQuery('.time-add').trigger('click');
        }
    } else {
        bootstrap.Collapse.getOrCreateInstance(\$collapse).hide();
    }
});

// time window dynamic form after insert
jQuery('.times_wrapper').on('afterInsert', function(event, item) {
    jQuery(item).find('input[name*=time_start], input[name*=time_end]')
        .removeClass('pe-none bg-light')
        .prop('readonly', false)
        .flatpickr(flatPickrOptions);
});
JS;

$this->registerJs($js);
