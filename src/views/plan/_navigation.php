<?php

use simialbi\yii2\hideseek\HideSeek;
use yii\bootstrap4\ButtonDropdown;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $users \simialbi\yii2\kanban\models\UserInterface[] */

$group = Yii::$app->request->getQueryParam('group', 'bucket');
$action = Yii::$app->controller->action->id;
?>
<div class="row">
    <div class="col-8 col-lg-7 d-flex flex-row align-items-center">
        <?= Html::img($model->visual); ?>
        <div class="ml-3">
            <h2 class="mb-0"><?= Html::encode($model->name); ?></h2>
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
                    'active' => $action === 'chart'
                ],
                [
                    'label' => Yii::t('simialbi/kanban/plan', 'Schedule'),
                    'url' => ['schedule', 'id' => $model->id],
                    'linkOptions' => [],
                    'active' => $action === 'schedule'
                ]
            ],
            'options' => [
                'class' => ['nav-pills', 'ml-5', 'd-none', 'd-md-flex']
            ]
        ]); ?>
    </div>
    <?php if ($action !== 'chart'): ?>
        <div class="col-4 col-lg-5 d-flex flex-row align-items-center justify-content-end">
            <?php if ($action === 'view'): ?>
                <div class="kanban-plan-assignees d-none d-md-block">
                    <div class="dropdown mr-auto">
                        <a href="javascript:;" data-toggle="dropdown"
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
                        $assignees = [];
                        $newUsers = [];
                        foreach ($model->assignees as $assignee) {
                            $assignees[] = [
                                'label' => $this->render('/task/_user', [
                                    'user' => $assignee,
                                    'assigned' => true
                                ]),
                                'linkOptions' => [
                                    'class' => ['d-flex', 'align-items-center']
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
                                    'class' => ['d-flex', 'align-items-center']
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
            <?= HideSeek::widget([
                'options' => [
                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                ],
                'clientOptions' => [
                    'list' => '.kanban-tasks'
                ]
            ]); ?>
            <?php if ($action === 'view'): ?>
                <?= ButtonDropdown::widget([
                    'label' => Yii::t('simialbi/kanban/plan', 'Group by <b>{group}</b>', [
                        'group' => Yii::t('simialbi/kanban/plan', $group)
                    ]),
                    'encodeLabel' => false,
                    'dropdown' => [
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
