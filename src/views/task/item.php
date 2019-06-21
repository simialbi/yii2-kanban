<?php

use kartik\date\DatePicker;
use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap4\ButtonDropdown;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model Task */
/* @var $statuses array */

Pjax::begin([
//    'id' => 'taskPjax' . $model->id,
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
    <div class="kanban-task card mb-2 status-<?= $model->status; ?>">
    <div class="kanban-task-content card-body">
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
            <?= Html::a(FAS::i('edit'), [
                'task/update',
                'id' => $model->id,
                'group' => Yii::$app->request->getQueryParam('group', 'bucket')
            ], [
                'class' => ['btn', 'btn-sm', 'ml-auto', 'kanban-task-update-link'],
                'data' => [
                    'toggle' => 'modal',
                    'target' => '#taskModal'
                ]
            ]); ?>
            <?= ButtonDropdown::widget([
                'label' => FAS::i('ellipsis-h'),
                'encodeLabel' => false,
                'direction' => ButtonDropdown::DIRECTION_RIGHT,
                'buttonOptions' => [
                    'class' => ['toggle' => '', 'btn' => 'btn btn-sm']
                ],
                'dropdown' => [
                    'items' => [
                        [
                            'label' => Yii::t('yii', 'Update'),
                            'url' => [
                                'task/update',
                                'id' => $model->id,
                                'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                            ],
                            'linkOptions' => [
                                'data' => [
                                    'toggle' => 'modal',
                                    'target' => '#taskModal'
                                ]
                            ]
                        ],
                        [
                            'label' => Yii::t('yii', 'Delete'),
                            'url' => [
                                'task/delete',
                                'id' => $model->id,
                                'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                            ],
                            'linkOptions' => [
                                'data' => [
                                    'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?')
                                ]
                            ]
                        ]
                    ]
                ]
            ]); ?>
        </div>
    </div>
    <?php if (count($model->assignees)): ?>
        <div class="kanban-task-assignees card-footer">
            <div class="dropdown">
                <a href="javascript:;" data-toggle="dropdown"
                   class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">
                    <?php foreach ($model->assignees as $assignee): ?>
                        <span class="kanban-user">
                        <?php if ($assignee->image): ?>
                            <?= Html::img($assignee->image, [
                                'class' => ['rounded-circle', 'mr-1'],
                                'title' => Html::encode($assignee->name),
                                'data' => [
                                    'toggle' => 'tooltip'
                                ]
                            ]); ?>
                        <?php else: ?>
                            <span class="kanban-visualisation mr-1" title="<?= Html::encode($assignee->name); ?>"
                                  data-toggle="tooltip">
                                <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                            </span>
                        <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </a>
                <?php
                $assignees = [];
                $newUsers = [];
                foreach ($model->assignees as $assignee) {
                    $assignees[] = [
                        'label' => $this->render('_user', [
                            'user' => $assignee,
                            'assigned' => true
                        ]),
                        'linkOptions' => [
                            'class' => ['d-flex', 'align-items-center']
                        ],
                        'url' => ['task/expel-user', 'id' => $model->id, 'userId' => $assignee->getId()]
                    ];
                }

                foreach ($model->board->assignees as $user) {
                    foreach ($model->assignees as $assignee) {
                        if ($user->getId() === $assignee->getId()) {
                            continue 2;
                        }
                    }
                    $newUsers[] = [
                        'label' => $this->render('_user', [
                            'user' => $user,
                            'assigned' => false
                        ]),
                        'linkOptions' => [
                            'class' => ['d-flex', 'align-items-center']
                        ],
                        'url' => ['task/assign-user', 'id' => $model->id, 'userId' => $user->getId()]
                    ];
                }

                $items = [];
                if (!empty($assignees)) {
                    $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
                }
                $items = array_merge($items, $assignees);
                if (!empty($assignees) && !empty($newUsers)) {
                    $items[] = '-';
                }
                if (!empty($newUsers)) {
                    $items[] = ['label' => Yii::t('simialbi/kanban', 'Not assigned')];
                }
                $items = array_merge($items, $newUsers);
                ?>
                <?= Dropdown::widget([
                    'items' => $items,
                    'encodeLabels' => false,
                    'options' => [
                        'class' => ['w-100']
                    ]
                ]); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
Pjax::end();
