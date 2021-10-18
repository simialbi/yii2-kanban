<?php

use kartik\file\FileInput;
use marqu3s\summernote\Summernote;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\datedropper\Datedropper;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Dropdown;
use yii\bootstrap4\Html;
use yii\helpers\ReplaceArrayValue;
use yii\helpers\Url;
//use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Task */
/* @var $buckets array */
/* @var $statuses array */
/* @var $users \simialbi\yii2\models\UserInterface[] */

Frame::begin([
    'options' => [
        'id' => 'task-' . $model->id . '-update-frame'
    ]
]);

//Pjax::begin([
//    'id' => 'taskUpdatePjax',
//    'formSelector' => '#taskModalForm',
//    'enablePushState' => false,
//    'clientOptions' => [
//        'skipOuterContainers' => true
//    ]
//]);
?>

    <div class="kanban-task-modal">
        <?php $form = ActiveForm::begin([
            'id' => 'taskModalForm',
            'action' => ['task/update', 'id' => $model->id],
            'validateOnSubmit' => false,
            'fieldConfig' => [
                'labelOptions' => [
                    'class' => ['col-form-label-sm', 'py-0']
                ],
                'inputOptions' => [
                    'class' => ['form-control', 'form-control-sm']
                ]
            ],
            'options' => [
                'enctype' => 'multipart/form-data',
                'data' => [
                    'turbo-frame' => 'task-' . $model->id . '-frame'
                ]
            ]
        ]); ?>
        <div class="modal-header">
            <?php $hint = ($model->status === $model::STATUS_DONE)
                ? Yii::t(
                    'simialbi/kanban/task',
                    'Finished at {finished,date} {finished,time} by {finisher}',
                    [
                        'finished' => $model->finished_at ? $model->finished_at : $model->updated_at,
                        'finisher' => $model->finisher ? $model->finisher->name : Yii::t('yii', '(not set)')
                    ]
                )
                : Yii::t(
                    'simialbi/kanban/task',
                    'Created at {created,date} {created,time} by {creator}, last modified {updated,date} {updated,time} by {modifier}',
                    [
                        'created' => $model->created_at,
                        'creator' => $model->author->name,
                        'updated' => $model->updated_at,
                        'modifier' => $model->updater->name
                    ]
                ); ?>
            <?= $form->field($model, 'subject', [
                'options' => [
                    'class' => ['my-0', 'w-100']
                ],
                'labelOptions' => [
                    'class' => ['sr-only']
                ],
                'inputOptions' => [
                    'class' => new ReplaceArrayValue(['form-control'])
                ]
            ])->textInput()->hint($hint); ?>
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
            <div class="row">
                <div class="col-12">
                    <div class="kanban-task-assignees">
                        <div class="dropdown">
                            <?php
                            $options = [
                                'href' => 'javascript:;',
                                'data' => ['toggle' => 'dropdown'],
                                'class' => [
                                    'dropdown-toggle',
                                    'text-decoration-none',
                                    'text-reset',
                                    'd-flex',
                                    'flex-row'
                                ]
                            ];
                            ?>
                            <?= Html::beginTag('a', $options); ?>
                            <?php foreach ($model->assignees as $assignee): ?>
                                <span class="kanban-user" data-id="<?= $assignee->getId(); ?>"
                                      data-name="<?= $assignee->name; ?>" data-image="<?= $assignee->image; ?>">
                                    <?= Html::hiddenInput('assignees[]', $assignee->getId()); ?>
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
                            <?php endforeach; ?>
                            <?= Html::endTag('a'); ?>
                            <?php
                            $items[] = ['label' => Yii::t('simialbi/kanban', 'Assigned')];
                            foreach ($users as $user) {
                                $item = [
                                    'label' => $this->render('_user', [
                                        'user' => $user,
                                        'assigned' => true
                                    ]),
                                    'linkOptions' => [
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
                                    ],
                                    'disabled' => $model->created_by != Yii::$app->user->id,
                                    'url' => 'javascript:;'
                                ];
                                foreach ($model->assignees as $assignee) {
                                    if ($assignee->getId() === $user->getId()) {
                                        Html::removeCssStyle($item['linkOptions'], ['display']);
                                        Html::addCssClass($item['linkOptions'], 'is-assigned');
                                        break;
                                    }
                                }

                                $items[] = $item;
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
                                foreach ($model->assignees as $assignee) {
                                    if ($assignee->getId() === $user->getId()) {
                                        Html::addCssStyle($linkOptions, ['display' => 'none']);
                                        Html::addCssClass($linkOptions, 'is-assigned');
//                                    Html::removeCssClass($linkOptions, 'd-flex');
                                        break;
                                    }
                                }

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
                                    'id' => 'kanban-update-task-assignees',
                                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword')
                                ],
                                'clientOptions' => [
                                    'list' => '.kanban-assignees',
                                    'ignore' => '.search-field,.dropdown-header,.dropdown-divider'
                                ]
                            ]));
                            ?>
                            <?= Dropdown::widget([
                                'items' => $items,
                                'encodeLabels' => false,
                                'options' => [
                                    'class' => ['kanban-assignees', 'w-100']
                                ]
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <?= $form->field($model, 'bucket_id', [
                    'options' => [
                        'class' => ['form-group', 'col-6', 'col-md-3']
                    ]
                ])->dropDownList($buckets); ?>
                <?= $form->field($model, 'status', [
                    'options' => [
                        'class' => ['form-group', 'col-6', 'col-md-3']
                    ]
                ])->dropDownList($statuses); ?>
                <?php if ($model->start_date) {
                    $model->start_date =  Yii::$app->formatter->asDate($model->start_date, 'dd.MM.yyyy');
                } ?>
                <?php if ($model->end_date) {
                    $model->end_date =  Yii::$app->formatter->asDate($model->end_date, 'dd.MM.yyyy');
                } ?>
                <?= $form->field($model, 'start_date', [
                    'options' => [
                        'class' => ['form-group', 'col-6', 'col-md-3']
                    ]
                ])->widget(Datedropper::class, [
                    'clientOptions' => [
                        'format' => 'd.m.Y',
                        'large' => true
                    ]
                ]); ?>
                <?= $form->field($model, 'end_date', [
                    'options' => [
                        'class' => ['form-group', 'col-6', 'col-md-3']
                    ]
                ])->widget(Datedropper::class, [
                    'options' => [
                        'placeholder' => (null === $model->endDate) ? '' : Yii::$app->formatter->asDate($model->endDate, 'dd.MM.yyyy')
                    ],
                    'clientOptions' => [
                        'format' => 'd.m.Y',
                        'large' => true
                    ]
                ]); ?>
            </div>
            <div class="row">
                <?php $showDescription = $form->field($model, 'card_show_description', [
                    'options' => ['class' => ''],
                    'labelOptions' => [
                        'class' => 'custom-control-label'
                    ],
                    'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
                <?= $form->field($model, 'description', [
                    'template' => "<div class=\"d-flex justify-content-between\">{label}$showDescription</div>\n{input}\n{hint}\n{error}",
                    'inputOptions' => ['id' => 'taskModalSummernote-description'],
                    'options' => [
                        'class' => ['form-group', 'col-12']
                    ]
                ])->widget(Summernote::class, [
                    'clientOptions' => [
                        'styleTags' => [
                            'p',
                            [
                                'title' => 'blockquote',
                                'tag' => 'blockquote',
                                'className' => 'blockquote',
                                'value' => 'blockquote'
                            ],
                            'pre'
                        ],
                        'toolbar' => new ReplaceArrayValue([
                            ['style', ['style']],
                            ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                            ['script', ['subscript', 'superscript']],
                            ['list', ['ol', 'ul']],
                            ['clear', ['clear']]
                        ])
                    ]
                ]); ?>
            </div>
            <div class="row">
                <div class="form-group col-12 checklist">
                    <div class="d-flex justify-content-between">
                        <?= Html::label(Yii::t('simialbi/kanban/task', 'Checklist'), null, [
                            'class' => ['col-form-label-sm', 'py-0']
                        ]); ?>
                        <?= $form->field($model, 'card_show_checklist', [
                            'options' => ['class' => ''],
                            'labelOptions' => [
                                'class' => 'custom-control-label'
                            ],
                            'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                        ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
                    </div>
                    <?php foreach ($model->checklistElements as $checklistElement): ?>
                        <div class="kanban-task-checklist-element input-group input-group-sm mb-1"
                             data-id="<?= $checklistElement->id; ?>">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <a href="javascript:;" class="kanban-task-checklist-sort text-body">
                                        <?= FAS::i('grip-lines'); ?>
                                    </a>
                                </div>
                                <div class="input-group-text">
                                    <?= Html::hiddenInput('checklist[' . $checklistElement->id . '][is_done]', 0); ?>
                                    <?= Html::checkbox(
                                        'checklist[' . $checklistElement->id . '][is_done]',
                                        $checklistElement->is_done
                                    ); ?>
                                </div>
                            </div>
                            <?= Html::input(
                                'text',
                                'checklist[' . $checklistElement->id . '][name]',
                                $checklistElement->name,
                                [
                                    'class' => ['form-control'],
                                    'style' => [
                                        'text-decoration' => $checklistElement->is_done ? 'line-through' : 'none'
                                    ],
                                    'placeholder' => Html::encode($checklistElement->name)
                                ]
                            ); ?>
                            <?= Datedropper::widget([
                                'name' => 'checklist[' . $checklistElement->id . '][end_date]',
                                'value' => $checklistElement->end_date ? Yii::$app->formatter->asDate($checklistElement->end_date, 'dd.MM.yyyy') : null,
                                'id' => 'task-' . $model->id . '-ce-' . $checklistElement->id . '-end-date',
                                'options' => [
                                    'autocomplete' => 'off',
                                    'class' => ['form-control'],
                                    'tabindex' => '-1',
                                    'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'End date')
                                ],
                                'clientOptions' => [
                                    'format' => 'd.m.Y',
                                    'large' => true,
                                    'autofill' => false,
                                    'minDate' => Yii::$app->formatter->asDate('now', 'MM/dd/yyyy'),
                                    'maxDate' => ($model->end_date)
                                        ? Yii::$app->formatter->asDate($model->end_date, 'MM/dd/yyyy')
                                        : null
                                ]
                            ]); ?>
                            <div class="input-group-append">
                                <button class="btn btn-outline-danger remove-checklist-element">
                                    <?= FAS::i('trash-alt'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="kanban-task-checklist-element input-group input-group-sm add-checklist-element mb-1">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <?= Html::checkbox('checklist[new][0][is_done]', false); ?>
                            </div>
                        </div>
                        <?= Html::input('text', 'checklist[new][0][name]', null, [
                            'class' => ['form-control'],
                            'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'Name')
                        ]); ?>
                        <?= Datedropper::widget([
                            'name' => 'checklist[new][0][end_date]',
                            'value' => null,
                            'id' => 'task-' . $model->id . '-ce-new-end-date-1',
                            'options' => [
                                'autocomplete' => 'off',
                                'class' => ['form-control'],
                                'tabindex' => '-1',
                                'placeholder' => Yii::t('simialbi/kanban/model/checklist-element', 'End date'),
                                'data' => [
                                    'lang' => preg_replace('/^([a-z]{2})(?:-[a-z]{2})?/i', '$1', Yii::$app->language)
                                ],
                            ],
                            'clientOptions' => [
                                'format' => 'd.m.Y',
                                'large' => true,
                                'autofill' => false,
                                'minDate' => Yii::$app->formatter->asDate('now', 'MM/dd/yyyy'),
                                'maxDate' => ($model->end_date) ? Yii::$app->formatter->asDate($model->end_date,
                                    'MM/dd/yyyy') : null
                            ]
                        ]); ?>
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger remove-checklist-element disabled" disabled>
                                <?= FAS::i('trash-alt'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12">
                    <?= Html::label(Yii::t('simialbi/kanban/task', 'Attachments'), 'task-attachments', [
                        'class' => ['col-form-label-sm', 'py-0']
                    ]); ?>
                    <?= FileInput::widget([
                        'name' => 'attachments[]',
                        'options' => [
                            'id' => 'task-attachments',
                            'multiple' => true
                        ],
                        'pluginOptions' => [
                            'mainClass' => 'file-caption-main input-group-sm',
                            'showPreview' => false,
                            'showUpload' => false
                        ],
                        'bsVersion' => '4'
                    ]); ?>
                </div>
            </div>
            <?php if ($model->attachments): ?>
        </div>
        <div class="list-group list-group-flush kanban-task-attachments">
            <?php $i = 0; ?>
            <?php foreach ($model->attachments as $attachment): ?>
                <div class="list-group-item list-group-item-action d-flex flex-row justify-content-between">
                    <a href="<?= $attachment->path; ?>" target="_blank"
                       data-pjax="0"><?= Html::encode($attachment->name); ?></a>
                    <?= $form->field($attachment, "[$i]card_show", [
                        'options' => ['class' => 'ml-auto mr-3 kanban-attachment-show'],
                        'labelOptions' => [
                            'class' => 'custom-control-label'
                        ],
                        'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                    ])->checkbox([
                        'inline' => true,
                        'class' => 'custom-control-input'
                    ]); ?>
                    <?= Html::a(FAS::i('trash-alt'), ['attachment/delete', 'id' => $attachment->id], [
                        'class' => ['remove-attachment'],
                        'data' => [
                            'turbo' => 'true',
                            'turbo-frame' => 'task-' . $model->id . '-update-frame'
                        ]
                    ]); ?>
                    <?php $i++; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-body">
            <?php endif; ?>
            <div class="row">
                <div class="form-group col-12 linklist">
                    <div class="d-flex justify-content-between">
                        <?= Html::label(Yii::t('simialbi/kanban/task', 'Links'), 'add-link', [
                            'class' => ['col-form-label-sm', 'py-0']
                        ]); ?>
                        <?= $form->field($model, 'card_show_links', [
                            'options' => ['class' => ''],
                            'labelOptions' => [
                                'class' => 'custom-control-label'
                            ],
                            'checkTemplate' => "<div class=\"custom-control custom-checkbox\">\n{input}\n{label}\n</div>"
                        ])->checkbox(['inline' => true, 'class' => 'custom-control-input']); ?>
                    </div>
                    <?php foreach ($model->links as $link): ?>
                        <div class="input-group input-group-sm mb-1">
                            <?= Html::input(
                                'text',
                                'link[' . $link->id . '][url]',
                                $link->url,
                                [
                                    'class' => ['form-control'],
                                    'placeholder' => Html::encode($link->url)
                                ]
                            ); ?>
                            <div class="input-group-append">
                                <a href="<?= $link->url; ?>" class="btn btn-outline-secondary" target="_blank">
                                    <?= FAS::i('external-link-alt') ?>
                                </a>
                            </div>
                            <div class="input-group-append">
                                <button class="btn btn-outline-danger remove-linklist-element">
                                    <?= FAS::i('trash-alt'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="input-group input-group-sm add-linklist-element mb-1">
                        <?= Html::input('text', 'link[new][][url]', null, [
                            'id' => 'add-link',
                            'class' => ['form-control']
                        ]); ?>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12">
                    <?= Html::label(Yii::t('simialbi/kanban/task', 'Comments'), 'comment', [
                        'class' => ['col-form-label-sm', 'py-0']
                    ]); ?>
                    <?= Summernote::widget([
                        'id' => 'taskModalSummernote-comment',
                        'name' => 'comment',
                        'value' => '',
                        'options' => ['form-control', 'form-control-sm'],
                        'clientOptions' => [
                            'styleTags' => [
                                'p',
                                [
                                    'title' => 'blockquote',
                                    'tag' => 'blockquote',
                                    'className' => 'blockquote',
                                    'value' => 'blockquote'
                                ],
                                'pre'
                            ],
                            'toolbar' => new ReplaceArrayValue([
                                ['style', ['style']],
                                ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                                ['script', ['subscript', 'superscript']],
                                ['list', ['ol', 'ul']],
                                ['clear', ['clear']]
                            ])
                        ]
                    ]); ?>
                </div>
                <?php if (count($model->comments)): ?>
                    <div class="kanban-task-comments mt-4 col-12">
                        <?php $i = 0; ?>
                        <?php foreach ($model->comments as $comment): ?>
                            <div class="kanban-task-comment media<?php if ($i++ !== 0): ?> mt-3<?php endif; ?>">
                                <div class="kanban-user mr-3">
                                    <?php if ($comment->author->image): ?>
                                        <?= Html::img($comment->author->image, [
                                            'class' => ['rounded-circle'],
                                            'title' => Html::encode($comment->author->name),
                                            'data' => [
                                                'toggle' => 'tooltip'
                                            ]
                                        ]); ?>
                                    <?php else: ?>
                                        <span class="kanban-visualisation" data-toggle="tooltip"
                                              title="<?= Html::encode($comment->author->name); ?>">
                                        <?= strtoupper(substr($comment->author->name, 0, 1)); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="media-body">
                                <span class="text-muted d-flex flex-row justify-content-between">
                                    <span><?= Html::encode($comment->author->name); ?></span>
                                    <span>
                                        <?= Yii::$app->formatter->asDatetime($comment->created_at, 'medium'); ?>
                                    </span>
                                </span>
                                    <?= $comment->text; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

$baseUrl = Url::to(['/' . $this->context->module->id . '/sort']);
$js = <<<JS
$('.checklist').sortable({
    items: '> .kanban-task-checklist-element',
    handle: '.kanban-task-checklist-sort',
    stop: function (event, ui) {
        var \$element = ui.item;
        var \$before = \$element.prev('.kanban-task-checklist-element');
        var action = 'move-after';
        var pk = null;

        if (!\$before.length) {
            action = 'move-as-first';
        } else {
            pk = \$before.data('id');
        }

        $.post('$baseUrl/' + action, {
            modelClass: 'simialbi\\\\yii2\\\\kanban\\\\models\\\\ChecklistElement',
            modelPk: \$element.data('id'),
            pk: pk
        }, function (data) {
            console.log(data);
        });
    }
});
JS;

$this->registerJs($js);
