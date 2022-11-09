<?php

use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $users \simialbi\yii2\models\UserInterface[] */
/* @var $readonly boolean */

Frame::begin([
    'options' => [
        'id' => 'plan-' . $model->id . '-assignees'
    ]
]);
?>
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
                            'class' => ['align-items-center', 'remove-assignee', 'is-assigned'],
                            'data' => [
                                'turbo' => 'true',
                                'turbo-frame' => 'plan-' . $model->id . '-assignees'
                            ]
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
                            'class' => ['align-items-center', 'add-assignee'],
                            'data' => [
                                'turbo' => 'true',
                                'turbo-frame' => 'plan-' . $model->id . '-assignees'
                            ]
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
                    'fieldTemplate' => '<div class="search-field px-3 my-3 flex-grow-1">{input}</div>',
                    'options' => [
                        'id' => 'kanban-view-plan-assignees',
                        'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                    ],
                    'clientOptions' => [
                        'list' => '.kanban-plan-assignees-dropdown',
                        'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                    ]
                ]));

                echo Dropdown::widget([
                    'id' => 'dropdown-plan-assignees-' . $model->id,
                    'items' => $items,
                    'encodeLabels' => false,
                    'options' => [
                        'class' => ['kanban-plan-assignees-dropdown', 'w-100']
                    ],
                    'clientEvents' => [
                        'shown.bs.dropdown' => new JsExpression('function(e) {
                            $(e.target).closest(".dropdown").find(".search-field input").trigger("focus");
                        }'),
                    ]
                ]);
            }
            ?>
        </div>
    </div>
<?php

Frame::end();
