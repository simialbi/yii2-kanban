<?php

use kartik\select2\Select2;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\models\UserInterface;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Dropdown;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/* @var $form ActiveForm */
/* @var $this View */
/* @var $model Task */
/* @var $users array[]|UserInterface[] */
/* @var $isAutor bool */

$clientData = [];
if ($model->client) {
    $clientData = [$model->client->id => $model->client->name];
}

?>
<div class="px-3 pb-3">
    <div class="row g-3 mt-0">
        <div class="kanban-task-responsible col-12 col-sm-6">
            <div class="dropdown col-12">
                <div class="position-relative">
                    <?= Html::label(Yii::t('simialbi/kanban/model/task', 'Responsible'), null, [
                        'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
                    ]) ?>

                    <?= Html::activeHiddenInput($model, 'responsible_id') ?>

                    <div class="input-group input-group-sm">
                        <?= Html::textInput('', $model->responsible_id ? $model->responsible->name : null, [
                            'id' => 'task-responsible_id-dummy',
                            'class' => [
                                'form-control', 'form-control-sm'
                            ],
                            'data' => [
                                'bs-toggle' => 'dropdown',
                                'bs-display' => 'static'
                            ],
                            'autocomplete' => 'off'
                        ]) ?>
                        <?php
                        $items = [];
                        foreach ($users as $user) {
                            $item = [
                                'label' => $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/_user.php'), [
                                    'user' => $user,
                                    'assigned' => false
                                ]),
                                'linkOptions' => [
                                    'class' => ['align-items-center', 'remove-assignee'],
                                    'onclick' => sprintf(
                                        'window.sa.kanban.chooseResponsible.call(this, %u);',
                                        $user->getId()
                                    ),
                                    'data' => [
                                        'id' => $user->getId(),
                                        'name' => $user->name,
                                        'image' => $user->photo
                                    ]
                                ],
                                'disabled' => !$isAutor,
                                'url' => 'javascript:;'
                            ];

                            $items[] = $item;
                        }

                        array_unshift($items, HideSeek::widget([
                            'fieldTemplate' => '<div class="search-field px-3 mb-3">{input}</div>',
                            'options' => [
                                'id' => 'kanban-update-task-responsible',
                                'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                                'autocomplete' => 'off'
                            ],
                            'clientOptions' => [
                                'list' => '.kanban-responsible',
                                'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                            ]
                        ]));
                        ?>
                        <?= Dropdown::widget([
                            'id' => 'responsible-dropdown-' . $model->id,
                            'items' => $items,
                            'encodeLabels' => false,
                            'options' => [
                                'class' => ['kanban-responsible', 'w-100']
                            ],
                            'clientEvents' => [
                                'shown.bs.dropdown' => new JsExpression('function(e) {
                                    $(e.target).closest(".dropdown").find(".search-field input").trigger("focus");
                                }'),
                            ]
                        ]); ?>
                        <button class="btn btn-outline-secondary"
                                type="button" <?= ($isAutor ? 'onclick="window.sa.kanban.removeResponsible();"' : 'disabled') ?>>
                            <?= FAS::i('times') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <?= Html::label($model->getAttributeLabel('client_id'), null, [
                'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
            ]) ?>
            <div class="input-group input-group-sm">
                <?= $form->field($model, 'client_id', [
                    'options' => [
                        'tag' => false
                    ]
                ])->widget(Select2::class, [
                    'data' => $clientData,
                    'size' => Select2::SIZE_SMALL,
                    'options' => [
                        'placeholder' => Yii::t('hq-re/general', 'Search for client')
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'dropdownParent' => Yii::createObject('\yii\web\JsExpression', ['$("#task-modal")']),
                        'minimumInputLength' => 3,
                        'ajax' => [
                            'url' => Url::to(['task/list-clients']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                    ]
                ])->label(false) ?>
                <?php if ($model->client_id !== null): ?>
                    <a href="<?= Url::to(['/crm/crm/view', 'id' => $model->client_id]) ?>"
                       class="btn btn-outline-secondary" target="_blank"
                       title="<?= Yii::t('simialbi/kanban', 'Client management') ?>">
                        <?= FAS::i('users') ?>
                    </a>
                <?php endif; ?>
                <?php if ($model->sharePointUrl): ?>
                    <a href="<?= $model->sharePointUrl ?>" class="btn btn-outline-secondary" target="_blank"
                       title="SharePoint">
                        <?= FAS::i('up-right-from-square') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
