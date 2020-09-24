<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\KanbanAsset;
use yii\bootstrap4\Modal;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $boards \simialbi\yii2\kanban\models\Board[] */
/* @var $model \simialbi\yii2\kanban\models\Board */
/* @var $buckets string */
/* @var $users \simialbi\yii2\models\UserInterface[] */
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
                <div class="d-flex flex-row kanban-plan-sortable">
                    <?= $buckets; ?>
                </div>

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
var link = jQuery('<a href="$link" data-toggle="modal" data-target="#taskModal" />');
link.appendTo('body').trigger('click').remove();
JS;

    $this->registerJs($js);
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
Modal::begin([
    'id' => 'taskModal',
    'options' => [
        'class' => ['modal', 'remote', 'fade']
    ],
    'clientOptions' => [
        'backdrop' => 'static',
        'keyboard' => false
    ],
    'clientEvents' => [
        'hidden.bs.modal' => new \yii\web\JsExpression($js)
    ],
    'size' => Modal::SIZE_LARGE,
    'title' => null,
    'closeButton' => false
]);
Modal::end();
