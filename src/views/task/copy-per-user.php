<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Task */
/* @var $users array */
/* @var $group string */

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
$options = [
    'id' => 'sa-kanban-task-modal-form',
    'fieldConfig' => [
        'labelOptions' => [
            'class' => ['col-form-label-sm', 'py-0']
        ],
        'inputOptions' => [
            'class' => ['form-control', 'form-control-sm']
        ]
    ]
];
if ($group !== 'assignee') {
    $options['validateOnSubmit'] = false;

    if ($group === 'bucket') {
        $options['options'] = [
            'data' => [
                'turbo-frame' => 'bucket-' . $model->bucket_id . '-frame'
            ]
        ];
    } elseif ($group === 'status') {
        $options['options'] = [
            'data' => [
                'turbo-frame' => 'bucket-status-' . $model->status . '-frame'
            ]
        ];
    }
}
?>
    <div class="kanban-task-modal">
        <?php $form = ActiveForm::begin($options); ?>
        <div class="modal-header">
            <h5 class="modal-title"><?= Yii::t('simialbi/kanban/task', 'Create task per each user'); ?></h5>
            <?= Html::button('<span aria-hidden="true">' . FAS::i('times') . '</span>', [
                'type' => 'button',
                'class' => ['close'],
                'data' => [
                    'dismiss' => 'modal'
                ],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Close')
                ]
            ]); ?>
        </div>
        <div class="modal-body">
            <p>
            <?= Yii::t(
                'simialbi/kanban/task',
                'Create an individual copy of this task for each of the selected users and assign it.'
            ); ?>
            </p>
            <div class="row">
                <div class="col-12">
                    <div class="kanban-task-assignees">
                        <div class="dropdown">
                            <?php
                            $options = [
                                'href' => 'javascript:;',
                                'data' => ['toggle' => 'dropdown'],
                                'class' => ['dropdown-toggle', 'text-decoration-none', 'text-reset', 'd-flex', 'flex-row']
                            ];
                            if ($model->created_by != Yii::$app->user->id) {
                                Html::addCssClass($options, 'disabled');
                                $options['aria']['disabled'] = 'true';
                            }
                            ?>
                            <?= Html::tag('a', '', $options); ?>
                            <?php
                            $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
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
                            $items[] = '-';
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
                                    'id' => 'kanban-copy-task-per-user',
                                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                                ],
                                'clientOptions' => [
                                    'list' => '.kanban-assignees',
                                    'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                                ]
                            ]));
                            ?>
                            <?= Dropdown::widget([
                                'id' => 'dropdown-copy-per-user',
                                'items' => $items,
                                'encodeLabels' => false,
                                'options' => [
                                    'id' => 'kanban-copy-task-per-user-dropdown',
                                    'class' => ['kanban-assignees', 'w-100']
                                ],
                                'clientEvents' => [
                                    'shown.bs.dropdown' => new JsExpression('function(e) {
                                        $(e.target).closest(".dropdown").find(".search-field input").trigger("focus");
                                    }'),
                                ]
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <?= Html::button(Yii::t('simialbi/kanban', 'Close'), [
                'type' => 'button',
                'class' => ['btn', 'btn-dark'],
                'data' => [
                    'dismiss' => 'modal'
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
Frame::end();
