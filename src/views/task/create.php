<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $board \simialbi\yii2\kanban\models\Board */
/* @var $task \simialbi\yii2\kanban\models\Task */
/* @var $id integer|string */
/* @var $keyName string */
/* @var $users \simialbi\yii2\kanban\models\UserInterface[] */
/* @var $buckets \simialbi\yii2\kanban\models\Bucket[] */
/* @var $statuses array */

?>

<?php Pjax::begin([
    'id' => 'createTaskPjax',
    'formSelector' => '#createTaskForm',
    'enablePushState' => false,
    'clientOptions' => ['skipOuterContainers' => true]
]); ?>
<?= Html::a('+', ['task/create', 'boardId' => $board->id, $keyName => $id], [
    'class' => ['btn', 'btn-primary', 'btn-block']
]); ?>

<?php $form = ActiveForm::begin([
    'action' => ['task/create',  'boardId' => $board->id, $keyName => $id],
    'options' => [
        'class' => 'mt-5'
    ],
    'id' => 'createTaskForm',
    'fieldConfig' => function ($model, $attribute) {
        /* @var $model \yii\base\Model */
        return [
            'labelOptions' => ['class' => 'sr-only'],
            'inputOptions' => [
                'placeholder' => $model->getAttributeLabel($attribute)
            ]
        ];
    }
]); ?>
<div class="card">
    <?= Html::button('<span aria-hidden="true">' . FAS::i('times') . '</span>', [
        'type' => 'button',
        'class' => ['close', 'position-absolute'],
        'style' => [
            'font-size' => '1rem',
            'right' => '.25rem'
        ],
        'onclick' => 'jQuery(this).closest(\'form\').remove()',
        'data' => [
            'dismiss' => 'card'
        ],
        'aria' => [
            'label' => Yii::t('simialbi/kanban', 'Close')
        ]
    ]); ?>
    <div class="card-body">
        <?= $form->field($task, 'subject')->textInput(); ?>
        <?php if (!empty($buckets)): ?>
            <?= $form->field($task, 'bucket_id')->dropDownList($buckets); ?>
        <?php endif; ?>
        <?= $form->field($task, 'end_date', [
            'options' => [
                'class' => ['form-group', 'mb-0']
            ]
        ])->widget(DatePicker::class, [
            'bsVersion' => '4',
            'type' => DatePicker::TYPE_INPUT,
            'pluginOptions' => [
                'autoclose' => true,
                'todayHighlight' => true
            ],
            'options' => [
                'readonly' => true
            ]
        ]); ?>
        <?php if ($keyName !== 'userId'): ?>
            <div class="kanban-task-assignees kanban-assignees mt-3">
                <div class="dropdown">
                    <a href="javascript:;" data-toggle="dropdown"
                       class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">

                    </a>
                    <?php
                    foreach ($users as $user) {
                        $linkOptions = [
                            'class' => ['align-items-center', 'remove-assignee'],
                            'style' => ['display' => 'none'],
                            'onclick' => sprintf(
                                'window.sa.kanban.removeAssignee.call(this, %u);',
                                $user->getId()
                            ),
                            'data' => [
                                'id' => $user->getId(),
                                'name' => $user->name,
                                'image' => $user->image
                            ]
                        ];

                        $items[] = [
                            'label' => $this->render('_user', [
                                'user' => $user,
                                'assigned' => true
                            ]),
                            'linkOptions' => $linkOptions,
                            'url' => 'javascript:;'
                        ];
                    }
                    $items[] = ['label' => Yii::t('simialbi/kanban', 'Not assigned')];
                    foreach ($users as $user) {
                        $linkOptions = [
                            'class' => ['align-items-center', 'add-assignee'],
                            'onclick' => sprintf(
                                'window.sa.kanban.addAssignee.call(this, %u);',
                                $user->getId()
                            ),
                            'data' => [
                                'id' => $user->getId(),
                                'name' => $user->name,
                                'image' => $user->image
                            ]
                        ];

                        $items[] = [
                            'label' => $this->render('_user', [
                                'user' => $user,
                                'assigned' => false
                            ]),
                            'linkOptions' => $linkOptions,
                            'url' => 'javascript:;'
                        ];
                    }

                    array_unshift($items, HideSeek::widget([
                        'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                        'options' => [
                            'id' => 'kanban-create-task-assignees',
                            'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                        ],
                        'clientOptions' => [
                            'list' => '.kanban-create-task-assignees',
                            'ignore' => '.search-field,.dropdown-header'
                        ]
                    ]));
                    ?>
                    <?= Dropdown::widget([
                        'items' => $items,
                        'encodeLabels' => false,
                        'options' => [
                            'class' => ['kanban-create-task-assignees', 'w-100']
                        ]
                    ]); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="list-group list-group-flush">
        <?= Html::submitButton(Yii::t('simialbi/kanban', 'Save'), [
            'class' => ['list-group-item', 'list-group-item-success', 'list-group-item-action']
        ]) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
