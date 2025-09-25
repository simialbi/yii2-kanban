<?php

use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap5\Dropdown;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this View */
/* @var $model Board */
/* @var $users UserInterface[] */
/* @var $readonly boolean */

Frame::begin([
    'options' => [
        'id' => 'plan-' . $model->id . '-assignees'
    ]
]);

$upper = 3;
$lower = 1;

?>
    <div class="kanban-plan-assignees kanban-assignees d-none d-xl-block">
        <div class="dropdown me-auto">
            <a href="javascript:;"<?php if (!$readonly): ?> data-bs-toggle="dropdown"<?php endif; ?>
               class="dropdown-toggle text-decoration-none text-reset d-flex flex-row">
                <?php $i = 0; ?>
                <?php foreach ($model->assignees as $assignee): ?>
                    <span class="kanban-user<?php if ($i++ > ($lower - 1)): ?> d-md-none d-5xl-block<?php endif; ?>">
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
                    <?php if ($i > ($upper - 1)): ?>
                        <?php break; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (($cnt = count($model->assignees)) > $lower): ?>
                    <span class="d-none d-3xl-block d-5xl-none kanban-user-more">
                        + <?= $cnt - $lower ; ?>
                    </span>
                <?php endif; ?>
                <?php if (($cnt = count($model->assignees)) > $upper): ?>
                    <span class="d-none d-5xl-block kanban-user-more">
                        + <?= $cnt - $upper; ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php
            if (!$readonly) {
                $assignees = [];
                $newUsers = [];
                foreach ($model->assignees as $assignee) {
                    $assignees[] = [
                        'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
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
                        'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
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
                    'id' => 'dropdown-plan-' . $model->id,
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
