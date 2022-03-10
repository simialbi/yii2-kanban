<?php

use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\turbo\Frame;

/* @var \yii\web\View $this */
/* @var \simialbi\yii2\kanban\models\Board[] $boards */
/* @var Module $module */

Frame::begin([
    'options' => [
        'id' => 'responsible-tasks-frame'
    ]
]);
?>
    <div class="mt-3">
        <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
            <div></div>
        </div>

        <?= HideSeek::widget([
            'fieldTemplate' => '<div class="search-field mb-3">{input}</div>',
            'options' => [
                'id' => 'search-widget-responsible',
                'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                'autocomplete' => 'off'
            ],
            'clientOptions' => [
                'list' => '#responsible-tasks-frame .list-group',
                'attribute' => 'data-alt'
            ],
        ]); ?>

        <div id="boardAccordion" class="boardAccordion">
            <?php foreach ($boards as $index => $board): ?>
                <div class="card mb-3" data-alt="<?= $board->name; ?>">
                    <div class="card-header">
                        <h4 class="m-0"
                            style="cursor: pointer; font-weight: bold;"
                            data-toggle="collapse"
                            data-target="#board_<?= $board->id; ?>"
                            aria-expanded="true"
                            aria-controls="collapseOne">
                            <?= $board->name; ?>
                        </h4>
                    </div>

                    <div id="board_<?= $board->id ?>" class="collapse show">
                        <?php foreach ($board->buckets as $bucket): ?>
                            <div class="list-group list-group-flush">
                                <?php
                                $tasks = $bucket->tasks;
                                Module::sortTasks($tasks);
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
            <?php endforeach; ?>
        </div>
    </div>
<?php

$js = <<<JS
var bottomScrollBar = $('.kanban-bottom-scrollbar');
if (bottomScrollBar.is(':visible')) {
    window.sa.kanban.initScrollBars();
}
JS;
$this->registerJs('window.sa.kanban.initScrollBars();');

Frame::end();
