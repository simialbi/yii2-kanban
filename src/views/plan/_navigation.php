<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use yii\bootstrap4\ButtonDropdown;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */

$group = Yii::$app->request->getQueryParam('group', 'bucket');
$action = Yii::$app->controller->action->id;
?>
<div class="row">
    <div class="col-md-8 col-lg-7 d-none d-md-flex flex-row align-items-center">
        <div class="kanban-board-image align-self-stretch d-flex justify-content-center align-items-center">
            <?php if ($model->image): ?>
                <?= Html::img($model->image, ['class' => ['img-fluid']]); ?>
            <?php else: ?>
                <span class="kanban-visualisation modulo-<?= $model->id % 10; ?>">
                    <?= substr($model->name, 0, 1); ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="ml-3">
            <h2 class="mb-0">
                <?php if (empty($boards)): ?>
                    <?= Html::encode($model->name); ?>
                <?php else: ?>
                    <?php $items = []; ?>
                    <?php foreach ($boards as $board): ?>
                        <?php $items[] = [
                            'label' => $board->name,
                            'url' => ['plan/view', 'id' => $board->id]
                        ]; ?>
                    <?php endforeach; ?>
                    <?php echo ButtonDropdown::widget([
                        'label' => $model->name,
                        'id' => 'boardSwitchDropdown',
                        'buttonOptions' => [
                            'class' => ['widget' => 'bg-transparent', 'h2', 'm-0', 'p-0', 'border-0']
                        ],
                        'dropdown' => [
                            'items' => $items
                        ]
                    ]); ?>
                <?php endif; ?>
            </h2>
            <small class="text-muted"><?= Yii::$app->formatter->asDatetime($model->updated_at); ?></small>
        </div>
        <?= Nav::widget([
            'items' => [
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Board'),
                    'url' => ['view', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'view'
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Charts'),
                    'url' => ['chart', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'chart',
                    'visible' => !$readonly
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Schedule'),
                    'url' => ['schedule', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'schedule'
                ]
            ],
            'options' => [
                'class' => ['nav-pills', 'ml-5']
            ]
        ]); ?>
    </div>
    <?php if ($action !== 'chart'): ?>
        <div class="col-12 col-md-4 col-lg-5 d-flex flex-row align-items-center justify-content-end">
            <?php if ($action === 'view'): ?>
                <div class="kanban-plan-assignees kanban-assignees d-none d-md-block">
                    <div class="dropdown mr-auto">
                        <a href="javascript:;"<?php if (!$readonly): ?> data-toggle="dropdown"<?php endif; ?>
                           class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">
                            <?php $i = 0; ?>
                            <?php foreach ($model->assignees as $assignee): ?>
                                <span class="kanban-user<?php if (++$i > 2): ?> d-md-none d-lg-block<?php endif; ?>">
                                    <?php if ($assignee->image): ?>
                                        <?= Html::img($assignee->image, [
                                            'class' => ['rounded-circle', 'mr-1'],
                                            'title' => Html::encode($assignee->name),
                                            'data' => [
                                                'toggle' => 'tooltip'
                                            ]
                                        ]); ?>
                                    <?php else: ?>
                                        <span class="kanban-visualisation mr-1"
                                              title="<?= Html::encode($assignee->name); ?>"
                                              data-toggle="tooltip">
                                            <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($i > 3): ?>
                                    <?php break; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (($cnt = count($model->assignees)) > 2): ?>
                                <span class="d-none d-md-block d-lg-none kanban-user-more">
                                    + <?= $cnt - 2; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (($cnt = count($model->assignees)) > 4): ?>
                                <span class="d-none d-lg-block kanban-user-more">
                                    + <?= $cnt - 4; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php
                        if (!$readonly) {
                            $assignees = [];
                            $newUsers = [];
                            foreach ($model->assignees as $assignee) {
                                $assignees[] = [
                                    'label' => $this->render('/task/_user', [
                                        'user' => $assignee,
                                        'assigned' => true
                                    ]),
                                    'linkOptions' => [
                                        'class' => ['align-items-center', 'remove-assignee', 'is-assigned']
                                    ],
                                    'url' => ['plan/expel-user', 'id' => $model->id, 'userId' => $assignee->getId()]
                                ];
                            }

                            foreach ($users as $user) {
                                foreach ($model->assignees as $assignee) {
                                    if ($user->getId() === $assignee->getId()) {
                                        continue 2;
                                    }
                                }
                                $newUsers[] = [
                                    'label' => $this->render('/task/_user', [
                                        'user' => $user,
                                        'assigned' => false
                                    ]),
                                    'linkOptions' => [
                                        'class' => ['align-items-center', 'add-assignee']
                                    ],
                                    'url' => ['plan/assign-user', 'id' => $model->id, 'userId' => $user->getId()]
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

                            array_unshift($items, HideSeek::widget([
                                'fieldTemplate' => '<div class="search-field px-3 my-3">{input}</div>',
                                'options' => [
                                    'id' => 'kanban-view-plan-assignees',
                                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                                ],
                                'clientOptions' => [
                                    'list' => '.kanban-plan-assignees',
                                    'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                                ]
                            ]));

                            echo Dropdown::widget([
                                'items' => $items,
                                'encodeLabels' => false,
                                'options' => [
                                    'class' => ['kanban-plan-assignees', 'w-100']
                                ]
                            ]);
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            <?= HideSeek::widget([
                'fieldTemplate' => '<div class="search-field mr-auto mr-md-0">{input}</div>',
                'options' => [
                    'id' => 'search-tasks-widget',
                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                ],
                'clientOptions' => [
                    'list' => '.kanban-tasks'
                ],
                'clientEvents' => [
                    '_after' => new \yii\web\JsExpression('function () {
                        if (jQuery(\'.d-none.d-md-block:first\').is(\':visible\')) {
                            return;
                        }
                        var value = jQuery(this).val();
                        jQuery(\'.kanban-bucket\').each(function () {
                            if (jQuery(\'.kanban-sortable\', this).length || value === \'\') {
                                jQuery(this).addClass(\'d-flex\').removeClass(\'d-none\');
                            } else {
                                jQuery(this).addClass(\'d-none\').removeClass(\'d-flex\');
                            }
                        });
                        window.sa.kanban.getSwiper().update();
                    }')
                ]
            ]); ?>
            <?php if ($action === 'view'): ?>
                <?= ButtonDropdown::widget([
                    'label' => sprintf(
                        '<span class="d-md-none">%s</span><span class="d-none d-md-inline">%s</span>',
                        FAS::i('layer-group'),
                        Yii::t('simialbi/kanban/plan', 'Group by <b>{group}</b>', [
                            'group' => Yii::t('simialbi/kanban/plan', $group)
                        ])
                    ),
                    'encodeLabel' => false,
                    'dropdown' => [
                        'options' => [
                            'class' => ['dropdown-menu-right']
                        ],
                        'items' => [
                            [
                                'label' => Yii::t('simialbi/kanban/plan', 'Bucket'),
                                'url' => ['plan/view', 'id' => $model->id, 'group' => 'bucket'],
                                'active' => ($group === 'bucket')
                            ],
                            [
                                'label' => Yii::t('simialbi/kanban/plan', 'Assigned to'),
                                'url' => ['plan/view', 'id' => $model->id, 'group' => 'assignee'],
                                'active' => ($group === 'assignee')
                            ],
                            [
                                'label' => Yii::t('simialbi/kanban/plan', 'Status'),
                                'url' => ['plan/view', 'id' => $model->id, 'group' => 'status'],
                                'active' => ($group === 'status')
                            ],
                            [
                                'label' => Yii::t('simialbi/kanban/plan', 'End date'),
                                'url' => ['plan/view', 'id' => $model->id, 'group' => 'date'],
                                'active' => ($group === 'date')
                            ]
                        ]
                    ]
                ]); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
