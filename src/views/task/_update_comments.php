<?php

use sandritsch91\yii2\froala\FroalaEditor;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\web\View;

/* @var $this View */
/* @var $model Task */

?>
<div class="px-3 pb-3 bg-lighter">
    <div class="row g-3 mt-0">
        <div class="col-12">
            <?= Html::label(Yii::t('simialbi/kanban/task', 'Comments'), 'comment', [
                'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
            ]); ?>
            <?= FroalaEditor::widget([
                'id' => 'taskModalFroala-comment',
                'name' => 'comment',
                'value' => '',
                'options' => ['form-control', 'form-control-sm'],
                'clientOptions' => [
                    'heightMin' => 200,
                    'pluginsEnabled' => ['link'],
                    'toolbarButtons' => [
                        '|', 'insertLink'
                    ],
                    'linkEditButtons' => []
                ]
            ]); ?>
        </div>
        <?php if (count($model->comments)): ?>
            <div class="kanban-task-comments mt-4 col-12">
                <?php $i = 0; ?>
                <?php foreach ($model->comments as $comment): ?>
                    <div class="kanban-task-comment d-flex align-items-flex-start<?php if ($i++ !== 0): ?> mt-3<?php endif; ?>">
                        <div class="kanban-user me-3">
                            <?php if ($comment->author): ?>
                                <?php if ($comment->author->photo): ?>
                                    <?= Html::img($comment->author->photo, [
                                        'class' => ['rounded-circle'],
                                        'title' => Html::encode($comment->author->name),
                                        'data' => [
                                            'bs-toggle' => 'tooltip'
                                        ]
                                    ]); ?>
                                <?php else: ?>
                                    <span class="kanban-visualisation" data-bs-toggle="tooltip"
                                          title="<?= Html::encode($comment->author->name); ?>">
                                        <?= strtoupper(substr($comment->author->name, 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="kanban-visualisation" data-bs-toggle="tooltip"
                                      title="Unknown">
                                    U
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-muted d-flex flex-row justify-content-between">
                                <?php if ($comment->author): ?>
                                    <span><?= Html::encode($comment->author->name); ?></span>
                                <?php else: ?>
                                    <span>Unknown</span>
                                <?php endif; ?>
                                <span>
                                    <?= Yii::$app->formatter->asDatetime($comment->created_at, 'medium'); ?>
                                </span>
                            </span>
                            <?= Html::stripTags($comment->text, ['a']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
