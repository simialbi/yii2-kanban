<?php

use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\turbo\Frame;

/* @var \yii\web\View $this */
/* @var \simialbi\yii2\kanban\models\Board[] $boards */
/* @var \simialbi\yii2\kanban\Module $module */

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
                <div class="card mb-3" alt="<?= $board->name ?>">
                    <div class="card-header">
                        <h4 class="m-0"
                            style="cursor: pointer; font-weight: bold;"
                            data-toggle="collapse"
                            data-target="#board_<?= $board->id ?>"
                            aria-expanded="true"
                            aria-controls="collapseOne">
                            <?= $board->name ?>
                        </h4>
                    </div>

                    <div id="board_<?= $board->id ?>" class="collapse show">
                        <div class="card-body border-bottom border-bottom-1">
                            <?php foreach ($board->buckets as $bucket): ?>
                                <h5><?= $bucket->name ?></h5>
                                <div class="list-group">
                                    <?php
                                    $tasks = $bucket->tasks;
                                    $module::sortTasks($tasks);
                                    ?>
                                    <?php foreach ($tasks as $task): ?>
                                        <?= $this->render('list-item', [
                                            'task' => $task
                                        ]); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
Frame::end();
