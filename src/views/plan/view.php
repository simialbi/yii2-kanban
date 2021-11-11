<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\turbo\Modal;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $users \simialbi\yii2\models\UserInterface[] */
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
            <div class="kanban-top-scrollbar mb-2 d-none d-md-block">
                <div></div>
            </div>
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
var link = jQuery('<a href="$link" data-toggle="modal" data-target="#task-modal" data-turbo-frame="task-modal-frame" />');
link.appendTo('body').trigger('click').remove();
JS;

    $this->registerJs($js, $this::POS_LOAD);
}
$js = <<<JS
function onHide() {
    jQuery('.note-editor', this).each(function () {
        var summernote = jQuery(this).prev().data('summernote');
        if (summernote) {
            summernote.destroy();
        }
    });
}
JS;
echo Modal::widget([
    'options' => [
        'id' => 'task-modal',
        'options' => [
            'class' => ['modal', 'remote', 'fade']
        ],
        'clientOptions' => [
            'backdrop' => 'static',
            'keyboard' => false
        ],
        'clientEvents' => ['hidden.bs.modal' => new JsExpression($js)],
        'size' => \yii\bootstrap4\Modal::SIZE_EXTRA_LARGE,
        'title' => null,
        'closeButton' => false
    ],
]);
