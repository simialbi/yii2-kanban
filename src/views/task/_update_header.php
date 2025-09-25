<?php

use Recurr\Rule;
use Recurr\Transformer\TextTransformer;
use Recurr\Transformer\Translator;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ReplaceArrayValue;
use yii\web\View;

/* @var $form ActiveForm */
/* @var $this View */
/* @var $model Task */

$hint = ($model->status === $model::STATUS_DONE) ? Yii::t(
    'simialbi/kanban/task',
    'Finished at {finished,date} {finished,time} by {finisher}',
    [
        'finished' => $model->finished_at ?: $model->updated_at,
        'finisher' => $model->finisher ? $model->finisher->name : Yii::t('yii', '(not set)')
    ]
) : Yii::t(
    'simialbi/kanban/task',
    'Created at {created,date} {created,time} by {creator}, last modified {updated,date} {updated,time} by {modifier}',
    [
        'created' => $model->created_at,
        'creator' => $model->author ? $model->author->name : Yii::t('yii', '(not set)'),
        'updated' => $model->updated_at,
        'modifier' => $model->updater ? $model->updater->name : Yii::t('yii', '(not set)')
    ]
);

if ($model->is_recurring && $model->recurrence_pattern instanceof Rule) {
    $t = new TextTransformer(new Translator(substr(Yii::$app->language, 0, 2)));
    $hint .= '<br><span class="text-info">' . $t->transform($model->recurrence_pattern) . '</span>';
}

echo $form->field($model, 'subject', [
    'options' => [
        'class' => ['my-0', 'w-100']
    ],
    'labelOptions' => [
        'class' => ['visually-hidden']
    ],
    'inputOptions' => [
        'class' => new ReplaceArrayValue(['form-control'])
    ]
])->textInput([
    'autocomplete' => 'off'
])->hint($hint);
