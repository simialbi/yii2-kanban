<?php

use Recurr\Rule;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $form ActiveForm */
/* @var $this View */
/* @var $model Task */

$hideRecur = $model->isRecurrentInstance() ||
    $model->recurrence_parent_id > 0 ||
    $model->ticket_id > 0 ||
    !empty($model->sync_id);
?>
<div class="row g-3 mt-0 <?php if ($hideRecur): ?>d-none<?php endif; ?>">
    <div class="col-12">
        <?= $form->field($model, 'is_recurring', [
            'options' => [
                'class' => ['recurring-wrapper']
            ]
        ])->checkbox(); ?>

        <?php
        $freq = 'WEEKLY';
        $interval = 1;
        $byDay = null;
        $byDayInt = null;
        $byDayString = null;
        $byMonthDay = date('j');
        $byMonth = date('n');
        if ($model->recurrence_pattern instanceof Rule) {
            $freq = $model->recurrence_pattern->getFreqAsText();
            $interval = $model->recurrence_pattern->getInterval();
            $byDay = $model->recurrence_pattern->getByDay();
            $byMonthDay = $model->recurrence_pattern->getByMonthDay();
            $byMonth = $model->recurrence_pattern->getByMonth();
            if ($byDay !== null) {
                $byDayInt = preg_replace('#[^\-\d]#', '', $byDay);
                $byDayString = preg_replace('#[\-\d]#', '', $byDay);
            }
            if (is_array($byMonthDay)) {
                $byMonthDay = $byMonthDay[0];
            }
        }
        ?>

        <div class="collapse recurring-collapse mt-3 mb-3<?php if ($model->is_recurring): ?> show<?php endif; ?>">
            <h6><?= Yii::t('simialbi/kanban/recurrence', 'Recurrence Pattern'); ?></h6>
            <div class="row">
                <div class="col-12 col-sm-5 col-md-4 col-lg-3 border-end">
                    <?= Html::radioList(Html::getInputName($model, 'recurrence_pattern[FREQ]'), $freq, [
                        'DAILY' => Yii::t('simialbi/kanban/recurrence', 'Daily'),
                        'WEEKLY' => Yii::t('simialbi/kanban/recurrence', 'Weekly'),
                        'MONTHLY' => Yii::t('simialbi/kanban/recurrence', 'Monthly'),
                        'YEARLY' => Yii::t('simialbi/kanban/recurrence', 'Yearly')
                    ], [
                        'id' => Html::getInputId($model, 'recurrence_pattern[FREQ]')
                    ]); ?>
                </div>
                <div class="col-12 col-sm-7 col-md-8 col-lg-9">
                    <div id="recurrence-daily"<?php if ($freq !== 'DAILY'): ?> style="display: none;"<?php endif; ?>>
                        <?= Yii::t('simialbi/kanban/recurrence', 'Recur every {input} day(s)', [
                            'input' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[INTERVAL]'), $interval, [
                                'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                'style' => [
                                    'display' => 'inline-block',
                                    'vertical-algin' => 'middle',
                                    'width' => '3rem'
                                ],
                                'disabled' => $freq !== 'DAILY'
                            ])
                        ]); ?>
                    </div>
                    <div id="recurrence-weekly"<?php if ($freq !== 'WEEKLY'): ?> style="display: none;"<?php endif; ?>>
                        <?= Yii::t('simialbi/kanban/recurrence', 'Recur every {input} week(s) on:', [
                            'input' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[INTERVAL]'), $interval, [
                                'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                'style' => [
                                    'display' => 'inline-block',
                                    'vertical-algin' => 'middle',
                                    'width' => '3rem'
                                ],
                                'disabled' => $freq !== 'WEEKLY'
                            ])
                        ]); ?>

                        <?= Html::checkboxList(Html::getInputName($model, 'recurrence_pattern[BYDAY]'), $byDay, [
                            'MO' => Yii::t('simialbi/kanban/recurrence', 'Monday'),
                            'TU' => Yii::t('simialbi/kanban/recurrence', 'Tuesday'),
                            'WE' => Yii::t('simialbi/kanban/recurrence', 'Wednesday'),
                            'TH' => Yii::t('simialbi/kanban/recurrence', 'Thursday'),
                            'FR' => Yii::t('simialbi/kanban/recurrence', 'Friday'),
                            'SA' => Yii::t('simialbi/kanban/recurrence', 'Saturday'),
                            'SU' => Yii::t('simialbi/kanban/recurrence', 'Sunday')
                        ], [
                            'class' => ['justify-content-around', 'flex-wrap', 'mt-3'],
                            'inline' => true,
                            'itemOptions' => [
                                'disabled' => $freq !== 'WEEKLY'
                            ]
                        ]); ?>
                    </div>
                    <div id="recurrence-monthly"<?php if ($freq !== 'MONTHLY'): ?> style="display: none;"<?php endif; ?>>
                        <?= Html::radioList('pseudo1', ($byDay !== null) ? 1 : 0, [
                            Yii::t('simialbi/kanban/recurrence', 'Day {input1} of every {input2} month(s).', [
                                'input1' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[BYMONTHDAY]'), $byMonthDay, [
                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                    'style' => [
                                        'width' => '3rem'
                                    ],
                                    'disabled' => $byDay !== null || $freq !== 'MONTHLY'
                                ]),
                                'input2' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[INTERVAL]'), $interval, [
                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                    'style' => [
                                        'width' => '3rem'
                                    ],
                                    'disabled' => $byDay !== null || $freq !== 'MONTHLY'
                                ])
                            ]),
                            Yii::t('simialbi/kanban/recurrence', 'The {input1} {input2} of every {input3} month(s)', [
                                'input1' => Html::dropDownList(
                                    Html::getInputName($model, 'recurrence_pattern[BYDAY][int]'),
                                    $byDayInt,
                                    [
                                        '1' => Yii::t('simialbi/kanban/recurrence', 'first'),
                                        '2' => Yii::t('simialbi/kanban/recurrence', 'second'),
                                        '3' => Yii::t('simialbi/kanban/recurrence', 'third'),
                                        '4' => Yii::t('simialbi/kanban/recurrence', 'fourth'),
                                        '5' => Yii::t('simialbi/kanban/recurrence', 'fifth'),
                                        '-1' => Yii::t('simialbi/kanban/recurrence', 'last')
                                    ],
                                    [
                                        'class' => ['form-select', 'form-select-sm', 'mx-1', 'w-auto'],
                                        'disabled' => $byDay === null || $freq !== 'MONTHLY'
                                    ]
                                ),
                                'input2' => Html::dropDownList(
                                    Html::getInputName($model, 'recurrence_pattern[BYDAY][string]'),
                                    $byDayString,
                                    [
                                        'MO' => Yii::t('simialbi/kanban/recurrence', 'Monday'),
                                        'TU' => Yii::t('simialbi/kanban/recurrence', 'Tuesday'),
                                        'WE' => Yii::t('simialbi/kanban/recurrence', 'Wednesday'),
                                        'TH' => Yii::t('simialbi/kanban/recurrence', 'Thursday'),
                                        'FR' => Yii::t('simialbi/kanban/recurrence', 'Friday'),
                                        'SA' => Yii::t('simialbi/kanban/recurrence', 'Saturday'),
                                        'SU' => Yii::t('simialbi/kanban/recurrence', 'Sunday')
                                    ],
                                    [
                                        'class' => ['form-select', 'form-select-sm', 'mx-1', 'w-auto'],
                                        'disabled' => $byDay === null || $freq !== 'MONTHLY'
                                    ]
                                ),
                                'input3' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[INTERVAL]'), $interval, [
                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                    'style' => [
                                        'width' => '3rem'
                                    ],
                                    'disabled' => $byDay === null || $freq !== 'MONTHLY'
                                ])
                            ])
                        ], [
                            'class' => ['multiple-choices'],
                            'encode' => false,
                            'itemOptions' => [
                                'labelOptions' => [
                                    'class' => ['form-check-label', 'mb-3', 'd-flex', 'row-cols-auto', 'align-items-center']
                                ]
                            ]
                        ]); ?>
                    </div>
                    <div id="recurrence-yearly"<?php if ($freq !== 'YEARLY'): ?> style="display: none;"<?php endif; ?>>
                        <?= Yii::t('simialbi/kanban/recurrence', 'Recur every {input} year(s)', [
                            'input' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[INTERVAL]'), $interval, [
                                'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                'style' => [
                                    'display' => 'inline-block',
                                    'width' => '3rem',
                                    'vertical-align' => 'middle'
                                ],
                                'disabled' => $freq !== 'YEARLY',
                            ])
                        ]); ?>
                        <?= Html::radioList('pseudo2', $byMonthDay === null ? 1 : 0, [
                            Yii::t('simialbi/kanban/recurrence', 'On: {input1} {input2}', [
                                'input1' => Html::textInput(Html::getInputName($model, 'recurrence_pattern[BYMONTHDAY]'), $byMonthDay, [
                                    'class' => ['form-control', 'form-control-sm', 'mx-1'],
                                    'style' => [
                                        'width' => '3rem'
                                    ],
                                    'disabled' => $byMonthDay == null || $freq !== 'YEARLY',
                                ]),
                                'input2' => Html::dropDownList(
                                    Html::getInputName($model, 'recurrence_pattern[BYMONTH]'),
                                    $byMonth,
                                    [
                                        '1' => Yii::t('simialbi/kanban/recurrence', 'January'),
                                        '2' => Yii::t('simialbi/kanban/recurrence', 'February'),
                                        '3' => Yii::t('simialbi/kanban/recurrence', 'March'),
                                        '4' => Yii::t('simialbi/kanban/recurrence', 'April'),
                                        '5' => Yii::t('simialbi/kanban/recurrence', 'May'),
                                        '6' => Yii::t('simialbi/kanban/recurrence', 'June'),
                                        '7' => Yii::t('simialbi/kanban/recurrence', 'July'),
                                        '8' => Yii::t('simialbi/kanban/recurrence', 'August'),
                                        '9' => Yii::t('simialbi/kanban/recurrence', 'September'),
                                        '10' => Yii::t('simialbi/kanban/recurrence', 'October'),
                                        '11' => Yii::t('simialbi/kanban/recurrence', 'November'),
                                        '12' => Yii::t('simialbi/kanban/recurrence', 'December')
                                    ],
                                    [
                                        'class' => ['form-select', 'form-select-sm', 'mx-1', 'w-auto'],
                                        'disabled' => $byMonthDay == null || $freq !== 'YEARLY',
                                    ]
                                )
                            ]),
                            Yii::t('simialbi/kanban/recurrence', 'On the: {input1} {input2} of {input3}', [
                                'input1' => Html::dropDownList(
                                    Html::getInputName($model, 'recurrence_pattern[BYDAY][int]'),
                                    $byDayInt,
                                    [
                                        '1' => Yii::t('simialbi/kanban/recurrence', 'first'),
                                        '2' => Yii::t('simialbi/kanban/recurrence', 'second'),
                                        '3' => Yii::t('simialbi/kanban/recurrence', 'third'),
                                        '4' => Yii::t('simialbi/kanban/recurrence', 'fourth'),
                                        '5' => Yii::t('simialbi/kanban/recurrence', 'fifth'),
                                        '-1' => Yii::t('simialbi/kanban/recurrence', 'last')
                                    ],
                                    [
                                        'class' => ['form-select', 'form-select-sm', 'mx-1', 'w-auto'],
                                        'disabled' => $byMonthDay !== null || $freq !== 'YEARLY',
                                    ]
                                ),
                                'input2' => Html::dropDownList(
                                    Html::getInputName($model, 'recurrence_pattern[BYDAY][string]'),
                                    $byDayString,
                                    [
                                        'MO' => Yii::t('simialbi/kanban/recurrence', 'Monday'),
                                        'TU' => Yii::t('simialbi/kanban/recurrence', 'Tuesday'),
                                        'WE' => Yii::t('simialbi/kanban/recurrence', 'Wednesday'),
                                        'TH' => Yii::t('simialbi/kanban/recurrence', 'Thursday'),
                                        'FR' => Yii::t('simialbi/kanban/recurrence', 'Friday'),
                                        'SA' => Yii::t('simialbi/kanban/recurrence', 'Saturday'),
                                        'SU' => Yii::t('simialbi/kanban/recurrence', 'Sunday')
                                    ],
                                    [
                                        'class' => ['form-select', 'form-select-sm', 'mx-1', 'w-auto'],
                                        'disabled' => $byMonthDay !== null || $freq !== 'YEARLY',
                                    ]
                                ),
                                'input3' => Html::dropDownList(
                                    Html::getInputName($model, 'recurrence_pattern[BYMONTH]'),
                                    $byMonth,
                                    [
                                        '1' => Yii::t('simialbi/kanban/recurrence', 'January'),
                                        '2' => Yii::t('simialbi/kanban/recurrence', 'February'),
                                        '3' => Yii::t('simialbi/kanban/recurrence', 'March'),
                                        '4' => Yii::t('simialbi/kanban/recurrence', 'April'),
                                        '5' => Yii::t('simialbi/kanban/recurrence', 'May'),
                                        '6' => Yii::t('simialbi/kanban/recurrence', 'June'),
                                        '7' => Yii::t('simialbi/kanban/recurrence', 'July'),
                                        '8' => Yii::t('simialbi/kanban/recurrence', 'August'),
                                        '9' => Yii::t('simialbi/kanban/recurrence', 'September'),
                                        '10' => Yii::t('simialbi/kanban/recurrence', 'October'),
                                        '11' => Yii::t('simialbi/kanban/recurrence', 'November'),
                                        '12' => Yii::t('simialbi/kanban/recurrence', 'December')
                                    ],
                                    [
                                        'class' => ['form-select', 'form-select-sm', 'mx-1', 'w-auto'],
                                        'disabled' => $byMonthDay !== null || $freq !== 'YEARLY',
                                    ]
                                )
                            ])
                        ], [
                            'encode' => false,
                            'class' => ['multiple-choices', 'mt-3'],
                            'itemOptions' => [
                                'labelOptions' => [
                                    'class' => ['form-check-label', 'mb-3', 'd-flex', 'row-cols-auto', 'align-items-center']
                                ]
                            ]
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$recurrenceId = Html::getInputId($model, 'is_recurring');
$recurrenceFreqId = Html::getInputId($model, 'recurrence_pattern[FREQ]');

$js = <<<JS
// recurrence collapse
jQuery('#$recurrenceId').on('change', function () {
    var \$this = jQuery(this),
        \$collapse = \$this.closest('.recurring-wrapper').nextAll('.recurring-collapse'),
        \$checklistElementDateInputs = jQuery('.kanban-task-checklist-element input[id*=end-date]');

    if (\$this.is(':checked')) {
        bootstrap.Collapse.getOrCreateInstance(\$collapse).show();
        \$checklistElementDateInputs.val('');
    } else {
        bootstrap.Collapse.getOrCreateInstance(\$collapse).hide();
    }

    \$checklistElementDateInputs.prop('disabled', \$this.is(':checked'));
});


// recurrence frequency enabling / disabling
jQuery('#$recurrenceFreqId input[type=radio]').on('change', function () {
    var \$this = jQuery(this),
        els = {
        DAILY: jQuery('#recurrence-daily'),
        WEEKLY: jQuery('#recurrence-weekly'),
        MONTHLY: jQuery('#recurrence-monthly'),
        YEARLY: jQuery('#recurrence-yearly')
    };
    jQuery.each(els, function () {
        this.hide();
        this.find('input,select').prop('disabled', true);
    });
    els[\$this.val()].show();
    els[\$this.val()].find('input,select').prop('disabled', false);
    els[\$this.val()].find('.multiple-choices > .form-check > .form-check-input:not(:checked)').next().find('input,select').prop('disabled', true);
});

// recurrence multiple choices enabling / disabling
jQuery('.multiple-choices > .form-check > .form-check-input').on('change', function () {
    var \$this = jQuery(this),
        \$parent = \$this.closest('.multiple-choices');
    \$parent.find('input:not([type="radio"]),select').prop('disabled', true);
    \$this.next().find('input,select').prop('disabled', false);
});

// validate is_recurrence on settings change
jQuery('.recurring-collapse input, .recurring-collapse select').on('change', function () {
    jQuery('#sa-kanban-task-modal-form').yiiActiveForm('validateAttribute', 'task-is_recurring')
});
JS;

$this->registerJs($js);
