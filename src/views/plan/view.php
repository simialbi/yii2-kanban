<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\turbo\Modal;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $boards Board[] */
/* @var $model Board */
/* @var $users UserInterface[] */
/* @var $group string */
/* @var $readonly boolean */
/* @var $showTask integer|null */

KanbanAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('simialbi/kanban/plan', 'Kanban Hub'),
        'url' => ['index']
    ],
    $this->title
];
?>
    <div class="kanban-plan-view">
        <?= $this->render('_navigation', [
            'boards' => $boards,
            'model' => $model,
            'users' => $users,
            'readonly' => $readonly
        ]); ?>
        <div class="d-flex flex-column mt-3">
            <div class="kanban-bottom-scrollbar">
                <?php
                switch ($group) {
                    default:
                    case 'bucket':
                        echo $this->render('buckets', [
                            'model' => $model,
                            'readonly' => $readonly
                        ]);
                        break;
                    case 'assignee':
                        echo $this->render('buckets-assignees', [
                            'model' => $model,
                            'readonly' => $readonly
                        ]);
                        break;
                    case 'status':
                        echo $this->render('buckets-status', [
                            'model' => $model,
                            'readonly' => $readonly
                        ]);
                        break;
                }
                ?>

                <div class="d-md-none">
                    <div class="kanban-button-prev"><?= FAS::i('caret-left'); ?></div>
                    <div class="kanban-button-next"><?= FAS::i('caret-right'); ?></div>
                </div>
            </div>
        </div>
    </div>
<?php
if ($showTask) {
    $link = Url::to(['task/update', 'id' => $showTask]);
    $js = <<<JS
var link = jQuery('<a href="$link" data-bs-toggle="modal" data-bs-target="#task-modal" data-turbo-frame="task-modal-frame" />');
link.appendTo('body').trigger('click').remove();
JS;

    $this->registerJs($js, $this::POS_LOAD);
}
$js = <<<JS
// Calculate and apply height
var el = $('.kanban-bottom-scrollbar');
var height = Math.floor($(window).height() - el.offset().top - ($('.body').css('padding-bottom')||'12px').replace('px', ''));
if (!isNaN(height) && height > 200) {
    el.css('height', height);
}
JS;
$this->registerJs($js, $this::POS_LOAD);

echo Modal::widget([
    'modalClass' => '\yii\bootstrap5\Modal',
    'options' => [
        'id' => 'task-modal',
        'options' => [
            'class' => ['modal', 'remote', 'fade'],
            'tabindex' => ''
        ],
        'clientOptions' => [
            'backdrop' => 'static',
            'keyboard' => false
        ],
        'size' => \yii\bootstrap5\Modal::SIZE_EXTRA_LARGE,
        'title' => null,
        'closeButton' => false
    ],
]);
