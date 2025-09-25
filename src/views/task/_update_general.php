<?php

use sandritsch91\yii2\flatpickr\Flatpickr;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ActiveForm;
use yii\web\JsExpression;
use yii\web\View;

/* @var $buckets array */
/* @var $this View */
/* @var $model Task */
/* @var $flatPickrOnReady JsExpression */
/* @var $form ActiveForm */
/* @var $statuses array */
/* @var $readonly bool */

?>
<div class="px-3 bg-lighter">
    <div class="row g-3">
        <?= $form->field($model, 'bucket_id', [
            'options' => [
                'class' => ['col-6', 'col-md-3']
            ]
        ])->dropDownList($buckets, [
            'class' => ['form-select', 'form-select-sm'],
            'disabled' => $readonly
        ]); ?>
        <?= $form->field($model, 'status', [
            'options' => [
                'class' => ['col-6', 'col-md-3']
            ]
        ])->dropDownList($statuses, [
            'class' => ['form-select', 'form-select-sm']
        ]); ?>
        <?php if ($model->start_date) {
            $model->start_date = Yii::$app->formatter->asDate($model->start_date, 'dd.MM.yyyy');
        } ?>
        <?php if ($model->end_date) {
            $model->end_date = Yii::$app->formatter->asDate($model->end_date, 'dd.MM.yyyy');
        } ?>
        <?= $form->field($model, 'start_date', [
            'options' => [
                'class' => ['col-6', 'col-md-3', 'position-relative']
            ]
        ])->widget(Flatpickr::class, [
            'clientOptions' => [
                'maxDate' => $model->end_date ? Yii::$app->formatter->asTimestamp($model->end_date) * 1000 : null,
                'static' => true,
                'onReady' => $flatPickrOnReady
            ]
        ]); ?>
        <?= $form->field($model, 'end_date', [
            'options' => [
                'class' => ['col-6', 'col-md-3', 'position-relative']
            ]
        ])->widget(Flatpickr::class, [
            'options' => [
                'placeholder' => (null === $model->endDate) ? '' : Yii::$app->formatter->asDate($model->endDate, 'dd.MM.yyyy')
            ],
            'clientOptions' => [
                'minDate' => $model->start_date ? Yii::$app->formatter->asTimestamp($model->start_date) * 1000 : null,
                'static' => true,
                'onReady' => $flatPickrOnReady
            ]
        ]);
        ?>
    </div>
</div>
<?php
$startDateId = Html::getInputId($model, 'start_date');
$endDateId = Html::getInputId($model, 'end_date');

$js = <<<JS
var inputStart = jQuery('#$startDateId');
var inputEnd = jQuery('#$endDateId');

// on end date change
inputEnd.on('change', function() {
    let val = jQuery(this).val();
    let end = moment(val, 'DD.MM.YYYY');
    end.endOf('day');

    // modify start_date
    inputStart.prop('_flatpickr').set('maxDate', end.valueOf());
});
JS;

$this->registerJs($js);
