<?php

namespace simialbi\yii2\kanban;

use yii\helpers\Json;
use yii\validators\Validator;

class RecurrenceValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function clientValidateAttribute($model, $attribute, $view): string
    {
        $message = Json::encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<JS
var freq = \$form.find('input[name="Task[recurrence_pattern][FREQ]"]:checked').val();
var interval = \$form.find('input[name="Task[recurrence_pattern][INTERVAL]"]:not(":disabled")').val();

if (interval === '' || interval < 0) {
    messages.push($message);
}

switch (freq) {
    case 'DAILY':
        // only interval has to be validated
        break;
    case 'WEEKLY':
        var checked = \$form.find('input[name="Task[recurrence_pattern][BYDAY][]"]:checked').length;
        if (checked === 0) {
            messages.push($message);
        }
        break;
    case 'MONTHLY':
        if (\$form.find('input[name="pseudo1"][value="0"]').is(':checked')) {
            var byMonthDay = \$form.find('input[name="Task[recurrence_pattern][BYMONTHDAY]"]:not(":disabled")').val();
            if (byMonthDay === '' || byMonthDay == 0 || byMonthDay < -31 || byMonthDay > 31) {
                messages.push($message);
            }
        }
        break;
    case 'YEARLY':
        if (\$form.find('input[name="pseudo2"][value="0"]').is(':checked')) {
            var byMonthDay = \$form.find('input[name="Task[recurrence_pattern][BYMONTHDAY]"]:not(":disabled")').val();
            if (byMonthDay === '' || byMonthDay < 0) {
                messages.push($message);
            }
        }
        break;
}
JS;
    }
}
