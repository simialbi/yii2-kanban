<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use yii\bootstrap5\Dropdown;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\web\View;

/** @var $id int */
/** @var $users array */
/** @var $assignees array */
/** @var $enableAddRemoveAll boolean */
/** @var $this View */

$enableAddRemoveAll ??= false;
$idsAssigned = ArrayHelper::getColumn($assignees, 'id');

?>

<div class="p-2 pb-3-5">
    <div class="row g-3">
        <div class="col-12">
            <div class="kanban-task-assignees kanban-assignees">
                <div class="dropdown d-flex">
                    <?php
                    // ==========================
                    // Display
                    // ==========================
                    ?>
                    <a href="javascript:;" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside"
                       class="dropdown-toggle text-decoration-none text-reset d-flex flex-row flex-wrap flex-grow-1 rounded-3 pe-0">
                        <?php foreach ($assignees as $assignee): ?>
                            <span class="kanban-user" data-id="<?= $assignee->getId(); ?>"
                                  data-name="<?= $assignee->name; ?>" data-image="<?= $assignee->photo; ?>">
                                <?= Html::hiddenInput('assignees[]', $assignee->getId()); ?>
                                <?php if ($assignee->photo): ?>
                                    <?= Html::img($assignee->photo, [
                                        'class' => ['rounded-circle', 'me-1'],
                                        'title' => Html::encode($assignee->name),
                                        'data' => [
                                            'bs-toggle' => 'tooltip'
                                        ]
                                    ]); ?>
                                <?php else: ?>
                                    <span class="kanban-visualisation me-1"
                                          title="<?= Html::encode($assignee->name); ?>"
                                          data-bs-toggle="tooltip">
                                        <?= strtoupper(substr($assignee->name, 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </a>
                    <?php

                    // ==========================
                    // Dropdown
                    // ==========================

                    // Add all
                    if ($enableAddRemoveAll) {
                        $items[] = [
                            'label' => Yii::t('simialbi/kanban', 'Select all'),
                            'linkOptions' => [
                                'class' => ['align-items-center', 'add-all-assignees'],
                                'onclick' => 'window.sa.kanban.addAssignee.call(this, 0);'
                            ],
                            'url' => 'javascript:;'
                        ];
                    }

                    // Assigned users
                    /** @var \simialbi\yii2\models\UserInterface $user */
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
                                'image' => $user->photo
                            ]
                        ];

                        // if assigned, show user
                        if (in_array($user->getId(), $idsAssigned)) {
                            Html::removeCssStyle($linkOptions, ['display']);
                            Html::addCssClass($linkOptions, 'is-assigned');
                        }

                        $items[] = [
                            'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
                                'user' => $user,
                                'assigned' => true
                            ]),
                            'linkOptions' => $linkOptions,
                            'url' => 'javascript:;'
                        ];
                    }

                    // Not assigned users
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
                                'image' => $user->photo
                            ]
                        ];

                        // if assigned, hide user
                        if (in_array($user->getId(), $idsAssigned)) {
                            Html::addCssStyle($linkOptions, ['display' => 'none']);
                            Html::addCssClass($linkOptions, 'is-assigned');
                        }

                        $items[] = [
                            'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
                                'user' => $user,
                                'assigned' => false
                            ]),
                            'linkOptions' => $linkOptions,
                            'url' => 'javascript:;'
                        ];
                    }

                    // Search field
                    array_unshift($items, HideSeek::widget([
                        'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                        'options' => [
                            'id' => 'kanban-create-task-assignees-' . $id,
                            'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                        ],
                        'clientOptions' => [
                            'list' => '#dropdown-user-create-task-' . $id,
                            'ignore' => '.search-field,.dropdown-header,.add-all-assignees'
                        ]
                    ]));
                    ?>

                    <?= Dropdown::widget([
                        'id' => 'dropdown-user-create-task-' . $id,
                        'items' => $items,
                        'encodeLabels' => false,
                        'options' => [
                            'class' => ['kanban-create-task-assignees', 'w-100']
                        ],
                        'clientEvents' => [
                            'shown.bs.dropdown' => new JsExpression('function(e) {
                    $(e.target).closest(".dropdown").find(".search-field input").val("").trigger("focus");
                }'),
                        ]
                    ]);
                    if ($enableAddRemoveAll) {
                        ?>
                        <a type="button" class="remove-all-assignees align-items-center d-flex text-body" onclick="window.sa.kanban.removeAssignee.call(this, 0);">
                            <span class="align-items-center d-flex h-100 justify-content-center w-100">
                                <?= FAS::i('xmark') ?>
                            </span>
                        </a>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
