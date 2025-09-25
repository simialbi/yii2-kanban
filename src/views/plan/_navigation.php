<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\models\UserInterface;
use yii\bootstrap5\ButtonDropdown;
use yii\bootstrap5\Nav;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this View */
/* @var $boards Board[] */
/* @var $model Board */
/* @var $users UserInterface[] */
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
        <div class="ms-3">
            <h2 class="mb-0">
                <?php if (empty($boards)): ?>
                    <?= Html::encode($model->name); ?>
                <?php else: ?>
                    <?php $items = []; ?>
                    <?php foreach ($boards as $board): ?>
                        <?php $items[] = [
                            'label' => $board->fullname,
                            'url' => ['plan/view', 'id' => $board->id]
                        ]; ?>
                    <?php endforeach; ?>
                    <?php echo ButtonDropdown::widget([
                        'label' => Html::tag('span', $model->fullName, [
                            'class' => ['d-inline-block', 'text-truncate'],
                            'style' => [
                                'max-width' => '200px'
                            ],
                            'title' => $model->fullName
                        ]),
                        'encodeLabel' => false,
                        'id' => 'boardSwitchDropdown',
                        'buttonOptions' => [
                            'class' => ['widget' => 'bg-transparent', 'h2', 'm-0', 'p-0', 'border-0']
                        ],
                        'dropdown' => [
                            'items' => $items,
                            'encodeLabels' => false
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
                'class' => ['nav-pills', 'ms-5']
            ]
        ]); ?>
    </div>
    <?php if ($action !== 'chart'): ?>
        <div class="col-12 col-md-4 col-lg-5 d-flex flex-row align-items-center justify-content-end">
            <?php if ($action === 'view'): ?>
                <?= $this->render('assignees', [
                    'model' => $model,
                    'readonly' => $readonly,
                    'users' => $users
                ]); ?>
            <?php endif; ?>
            <?php if ($action === 'view'): ?>
                <?= HideSeek::widget([
                    'fieldTemplate' => '<div class="search-field me-auto me-md-0 flex-grow-1">{input}</div>',
                    'options' => [
                        'id' => 'search-tasks-widget',
                        'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                        'style' => [
                            'min-width' => '150px'
                        ]
                    ],
                    'clientOptions' => [
                        'list' => '.kanban-tasks, .finished-tasks',
                        'ignore' => '.finished-tasks, a, script',
                        'attribute' => 'alt'
                    ],
                ]); ?>
            <?php elseif ($action === 'schedule'): ?>
                <?= HideSeek::widget([
                    'fieldTemplate' => '<div class="search-field me-auto me-md-0 flex-grow-1">{input}</div>',
                    'options' => [
                        'id' => 'search-tasks-widget',
                        'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                        'style' => [
                            'min-width' => '150px'
                        ]
                    ],
                    'clientOptions' => [
                        'list' => '.kanban-tasks'
                    ],
                    'clientEvents' => [
                        '_after' => new JsExpression('function () {
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
            <?php endif; ?>
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
                    'buttonOptions' => [
                        'class' => ['btn-outline-secondary', 'ms-2']
                    ],
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
//                            ],
//                            [
//                                'label' => Yii::t('simialbi/kanban/plan', 'End date'),
//                                'url' => ['plan/view', 'id' => $model->id, 'group' => 'date'],
//                                'active' => ($group === 'date')
                            ]
                        ]
                    ]
                ]); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
