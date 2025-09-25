<?php

use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\turbo\Frame;
use yii\web\View;

/* @var View $this */
/* @var Board[] $boards */
/* @var Module $module */

Frame::begin([
    'options' => [
        'id' => 'responsible-tasks-frame'
    ]
]);
?>
    <div class="mt-3">

        <?= HideSeek::widget([
            'fieldTemplate' => '<div class="search-field mb-3">{input}</div>',
            'options' => [
                'id' => 'search-widget-responsible',
                'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                'autocomplete' => 'off'
            ],
            'clientOptions' => [
                'list' => '#responsible-tasks-frame .list-group',
                'attribute' => 'alt'
            ],
        ]); ?>

        <div id="boardAccordion" class="boardAccordion">
            <?php foreach ($boards as $index => $board): ?>
                <div class="mb-3" data-alt="<?= Html::encode($board->name); ?>">
                    <div class="bg-white px-3 py-2">
                        <h4 class="m-0"
                            style="cursor: pointer;
                            font-weight: bold;"
                            data-bs-toggle="collapse"
                            data-bs-target="#board_<?= $board->id; ?>"
                            aria-expanded="true">
                            <?= Html::encode($board->name); ?>
                        </h4>
                    </div>

                    <div id="board_<?= $board->id ?>" class="collapse">
                        <?php foreach ($board->buckets as $key => $bucket): ?>
                            <div class="list-group list-group-flush <?php if ($key === 0) echo 'border-top'; ?>">
                                <?php
                                $tasks = $bucket->tasks;
                                Module::sortTasks($tasks);
                                ?>
                                <?php foreach ($tasks as $task): ?>
                                    <?= $this->renderPhpFile(Yii::getAlias('@simialbi/yii2/kanban/views/task/list-item.php'), [
                                        'task' => $task
                                    ]); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php

Frame::end();
