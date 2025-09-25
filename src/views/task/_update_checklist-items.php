<?php

use rmrevin\yii\fontawesome\FAS;
use sandritsch91\yii2\flatpickr\Flatpickr;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\ButtonDropdown;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/* @var $endDateChecklist float|int|null */
/* @var $form ActiveForm */
/* @var $this View */
/* @var $model Task */
/* @var $checklistTemplates array */
/* @var $flatPickrOnReady JsExpression */

$endDateChecklist = null;
if ($model->end_date) {
    $tmp = (new \DateTime())->setTimestamp(Yii::$app->formatter->asTimestamp($model->end_date));
    $endDateChecklist = $tmp->format('U');

    // if the end date is in a previous year, we need to set the end date to yesterday
    // otherwise chrome throws an error because of flatpickr
    if ($tmp->format('Y') < (new \DateTime('now'))->format('Y')) {
        $endDateChecklist = (new \DateTime('yesterday'))->format('U');
    }
    $endDateChecklist = $endDateChecklist * 1000;
}

$endDateDisabled = $model->is_recurring || $model->isRecurrentInstance();

?>
<div class="px-3 pb-3">
    <div class="row g-3 mt-0">
        <div class="col-12 checklist">
            <div class="d-flex justify-content-between">
                <?php
                $addChecklistTemplate = '';
                if ($model->board->checklistTemplates) {
                    $addChecklistTemplate = ButtonDropdown::widget([
                        'label' => FAS::i('ballot-check')->fixedWidth() . ' ' .
                            Yii::t('simialbi/kanban/task', 'Templates'),
                        'encodeLabel' => false,
                        'buttonOptions' => [
                            'class' => ['btn', 'btn-sm', 'shadow-none', 'border-0', 'text-primary', 'ms-2']
                        ],
                        'dropdown' => [
                            'items' => $checklistTemplates,
                        ]
                    ]);
                }
                ?>
                <?= Html::label(
                    Yii::t('simialbi/kanban/task', 'Checklist') . $addChecklistTemplate,
                    null,
                    [
                        'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
                    ]
                ); ?>
                <?= $form->field($model, 'card_show_checklist', [
                    'options' => ['class' => ''],
                    'labelOptions' => [
                        'class' => 'form-label m-0'
                    ],
                ])->checkbox(); ?>
            </div>
            <?php foreach ($model->checklistElements as $checklistElement): ?>
                <div class="kanban-task-checklist-element input-group input-group-sm mb-1"
                     data-id="<?= $checklistElement->id; ?>">
                    <div class="input-group-text">
                        <a href="javascript:;" class="kanban-task-checklist-sort text-body">
                            <?= FAS::i('grip-dots'); ?>
                        </a>
                    </div>
                    <div class="input-group-text">
                        <?= Html::hiddenInput('checklist[' . $checklistElement->id . '][is_done]', 0); ?>
                        <?= Html::checkbox(
                            'checklist[' . $checklistElement->id . '][is_done]',
                            $checklistElement->is_done
                        ); ?>
                    </div>
                    <?= Html::input(
                        'text',
                        'checklist[' . $checklistElement->id . '][name]',
                        $checklistElement->name,
                        [
                            'class' => ['form-control'],
                            'style' => [
                                'text-decoration' => $checklistElement->is_done ? 'line-through' : 'none'
                            ],
                            'placeholder' => Html::encode($checklistElement->name)
                        ]
                    ); ?>
                    <div class="d-flex input-group-addon input-group-sm position-relative flex-grow-1">
                        <?= Flatpickr::widget([
                            'name' => 'checklist[' . $checklistElement->id . '][end_date]',
                            'value' => $checklistElement->end_date ? Yii::$app->formatter->asDate(
                                $checklistElement->end_date,
                                'dd.MM.yyyy'
                            ) : null,
                            'id' => 'task-' . $model->id . '-ce-' . $checklistElement->id . '-end-date',
                            'options' => [
                                'autocomplete' => 'off',
                                'class' => ['form-control', 'flatpickr-ce', 'rounded-0'],
                                'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'End date'),
                                'disabled' => $endDateDisabled
                            ],
                            'clientOptions' => [
                                'minDate' => 'today',
                                'maxDate' => $endDateChecklist,
                                'static' => true,
                                'onReady' => $flatPickrOnReady
                            ]
                        ]); ?>
                    </div>
                    <button type="button" class="btn btn-outline-danger remove-checklist-element">
                        <?= FAS::i('trash-alt'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
            <div class="kanban-task-checklist-element input-group input-group-sm add-checklist-element mb-1">
                <div class="input-group-text">
                    <?= Html::checkbox('checklist[new][0][is_done]'); ?>
                </div>
                <?= Html::input('text', 'checklist[new][0][name]', null, [
                    'class' => ['form-control'],
                    'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'Name')
                ]); ?>
                <div class="d-flex input-group-addon input-group-sm position-relative flex-grow-1">
                    <?= Flatpickr::widget([
                        'name' => 'checklist[new][0][end_date]',
                        'value' => null,
                        'id' => 'task-' . $model->id . '-ce-new-end-date-1',
                        'options' => [
                            'autocomplete' => 'off',
                            'class' => ['form-control', 'flatpickr-ce', 'rounded-0'],
                            'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'End date'),
                            'data' => [
                                'lang' => preg_replace('/^([a-z]{2})(?:-[a-z]{2})?/i', '$1', Yii::$app->language)
                            ],
                            'disabled' => $endDateDisabled
                        ],
                        'clientOptions' => [
                            'minDate' => 'today',
                            'maxDate' => $endDateChecklist,
                            'static' => true,
                            'onReady' => $flatPickrOnReady
                        ]
                    ]); ?>
                </div>
                <button type="button" class="btn btn-outline-danger remove-checklist-element disabled" disabled>
                    <?= FAS::i('trash-alt'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$baseUrl = Url::to(['/' . $this->context->module->id . '/sort']);
$endDateId = Html::getInputId($model, 'end_date');

$js = <<<JS
var inputEnd = $('#$endDateId');
inputEnd.on('change', function() {
    let end = moment(inputEnd.val(), 'DD.MM.YYYY').endOf('day');

    // modify checklists
    end.startOf('day');
    $('.flatpickr-ce').each(function() {
        let thisDate = $(this).val();
        $(this).prop('_flatpickr').set('maxDate', end.valueOf());
        // preserve date if it is in the past
        $(this).val(thisDate);
    });
});

// Checklist sortable
jQuery('.checklist').sortable({
    items: '> .kanban-task-checklist-element',
    handle: '.kanban-task-checklist-sort',
    stop: function (event, ui) {
        var \$element = ui.item;
        var \$before = \$element.prev('.kanban-task-checklist-element');
        var action = 'move-after';
        var pk = null;

        if (!\$before.length) {
            action = 'move-as-first';
        } else {
            pk = \$before.data('id');
        }

        jQuery.post('$baseUrl/' + action, {
            modelClass: 'simialbi\\\\yii2\\\\kanban\\\\models\\\\ChecklistElement',
            modelPk: \$element.data('id'),
            pk: pk
        }, function (data) {
            // console.log(data);
        });
    }
});
JS;

$this->registerJs($js);
