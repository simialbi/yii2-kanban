<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model Task */
/* @var $statuses array */

Pjax::begin([
    'id' => 'taskPjax' . $model->id,
    'enablePushState' => false,
    'options' => [
        'class' => ['kanban-sortable'],
        'data' => [
            'id' => $model->id
        ]
    ],
    'clientOptions' => [
        'skipOuterContainers' => false
    ]
]);
?>
<div class="kanban-task card mt-2 status-<?= $model->status; ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h6 class="card-title">
                <?= Html::encode($model->subject); ?>
            </h6>
            <?= Html::a(
                FAR::i('check-circle', ['class' => 'd-block']),
                ['task/set-status', 'id' => $model->id, 'status' => Task::STATUS_DONE],
                [
                    'class' => ['h5', 'kanban-task-done-link', 'd-block', 'text-decoration-none']
                ]
            ); ?>
        </div>
        <?php if ($model->card_show_description && $model->description): ?>
            <p class="card-text kanban-task-description"><?= Html::encode($model->description); ?></p>
        <?php endif; ?>
        <?php if ($model->card_show_checklist && count($model->checklistElements)): ?>
            <?php foreach ($model->checklistElements as $checklistElement): ?>
                <?php if ($checklistElement->is_done): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a class="kanban-task-checkbox custom-control custom-checkbox text-reset" href="<?= Url::to([
                    'checklist-element/set-done',
                    'id' => $checklistElement->id
                ]); ?>">
                    <?= Html::checkbox('checklist[' . $checklistElement->id . ']', false, [
                        'class' => ['custom-control-input'],
                        'id' => 'checklistElement-' . $model->id . '-' . $checklistElement->id
                    ]); ?>
                    <?= Html::label(
                        Html::encode($checklistElement->name),
                        'checklistElement-' . $model->id . '-' . $checklistElement->id,
                        [
                            'class' => ['custom-control-label']
                        ]
                    ); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="kanban-task-info d-flex flex-row align-items-center">
            <?php if ($model->status === Task::STATUS_IN_PROGRESS): ?>
                <small class="dropdown text-muted mr-3">
                    <a href="javascript:;" data-toggle="dropdown"
                       class="dropdown-toggle text-decoration-none text-reset">
                        <?= FAS::i('star-half-alt'); ?>
                    </a>

                    <?php
                    $items = [];
                    foreach ($statuses as $status => $label) {
                        $items[] = [
                            'label' => $label,
                            'url' => ['task/set-status', 'id' => $model->id, 'status' => $status]
                        ];
                    }
                    echo Dropdown::widget([
                        'items' => $items
                    ]);
                    ?>
                </small>
            <?php endif; ?>
            <?php if ($model->end_date): ?>
                <?php $class = ['btn', 'btn-sm', 'mr-3', 'px-0']; ?>
                <?php if ($model->end_date < time()): ?>
                    <?php $class[] = 'btn-danger'; ?>
                <?php endif; ?>
                <?= DatePicker::widget([
                    'model' => $model,
                    'attribute' => 'end_date',
                    'bsVersion' => '4',
                    'type' => DatePicker::TYPE_BUTTON,
                    'buttonOptions' => [
                        'class' => $class,
                        'label' => FAR::i('calendar-alt') . ' ' . Yii::$app->formatter->asDate(
                                $model->end_date,
                                'short'
                            )
                    ],
                    'pluginEvents' => [
                        'changeDate' => new \yii\web\JsExpression('function (e) {
                        var event = jQuery.Event(\'click\');
                        var container = \'#\' + jQuery(this).closest(\'[data-pjax-container]\').prop(\'id\');
                        
                        event.currentTarget = document.createElement(\'a\');
                        event.currentTarget.href = \'' . Url::to([
                                'task/set-end-date',
                                'id' => $model->id
                            ]) . '&date=\' + (e.date.getTime() / 1000)
                        jQuery.pjax.click(event, container, {
                            replace: false,
                            push: false,
                            skipOuterContainers: true
                        });
                        jQuery(this).kvDatepicker(\'hide\');
                    }')
                    ]
                ]); ?>
            <?php endif; ?>
            <?php if (count($model->comments)): ?>
                <small class="text-muted mr-3">
                    <?= FAR::i('comment-alt'); ?>
                </small>
            <?php endif; ?>
            <?php if (count($model->checklistElements)): ?>
                <small class="text-muted mr-3">
                    <?= FAR::i('check-square'); ?>
                    <?= $model->checklistStats; ?>
                </small>
            <?php endif; ?>
            <?= Html::a(FAS::i('ellipsis-h'), ['task/update', 'id' => $model->id], [
                'class' => ['btn', 'btn-sm', 'ml-auto'],
                'data' => [
                    'toggle' => 'modal',
                    'target' => '#taskModal'
                ]
            ]); ?>
        </div>
    </div>
</div>
<?php
Pjax::end();
