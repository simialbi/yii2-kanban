<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $form ActiveForm */
/* @var $this View */
/* @var $model Task */
/* @var $updateSeries bool */
/* @var $readonly bool */

?>
<div class="px-3 pb-3 bg-lighter">
    <div class="row g-3 mt-0">
        <div class="col-12 col-lg-6">
            <?= Html::label(Yii::t('simialbi/kanban/task', 'Attachments'), 'task-attachments', [
                'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
            ]); ?>
            <input class="form-control form-control-sm" type="file" id="task-attachments"
                   name="attachments[]" multiple>


            <?php if ($model->attachments): ?>
                <div class="list-group list-group-flush kanban-task-attachments">
                    <?php $i = 0; ?>
                    <?php foreach ($model->attachments as $attachment): ?>
                        <li class="list-group-item list-group-item-action d-flex flex-row justify-content-between bg-lighter px-0">
                            <a href="<?= $attachment->path; ?>"
                               target="_blank"><?= Html::encode($attachment->name); ?></a>
                            <?= $form->field($attachment, "[$i]card_show", [
                                'options' => ['class' => 'ms-auto me-3 kanban-attachment-show'],
                                'labelOptions' => [
                                    'class' => 'form-label'
                                ]
                            ])->checkbox(); ?>
                            <?= Html::a(FAS::i('trash-alt'), [
                                    'attachment/delete',
                                    'id' => $attachment->id,
                                    'readonly' => $readonly,
                                    'updateSeries' => $updateSeries,
                                ], [
                                'class' => ['remove-attachment'],
                                'data' => [
                                    'turbo' => 'true',
                                    'turbo-frame' => 'task-' . $model->id . '-update-frame'
                                ]
                            ]); ?>
                            <?php $i++; ?>
                        </li>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12 col-lg-6 linklist">
            <div class="d-flex justify-content-between" style="margin-bottom: 2px;">
                <?= Html::label(Yii::t('simialbi/kanban/task', 'Links'), 'add-link', [
                    'class' => ['form-label', 'col-form-label-sm', 'py-0', 'fw-bold']
                ]); ?>
                <?= $form->field($model, 'card_show_links', [
                    'options' => ['class' => ''],
                    'labelOptions' => [
                        'class' => 'form-label'
                    ]
                ])->checkbox(); ?>
            </div>
            <?php foreach ($model->links as $link): ?>
                <div class="input-group input-group-sm mb-1 bg-white">
                    <?= Html::input(
                        'text',
                        'link[' . $link->id . '][url]',
                        $link->url,
                        [
                            'class' => ['form-control', 'form-control-sm', 'sharepoint-input'],
                            'placeholder' => Html::encode($link->url)
                        ]
                    ); ?>
                    <a href="<?= $link->url; ?>" class="btn btn-outline-secondary" target="_blank">
                        <?= FAS::i('external-link-alt') ?>
                    </a>
                    <button class="btn btn-outline-danger remove-linklist-element">
                        <?= FAS::i('trash-alt'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
            <div class="input-group input-group-sm add-linklist-element mb-1">
                <?= Html::input('text', 'link[new][][url]', null, [
                    'id' => 'add-link',
                    'class' => ['form-control', 'form-control-sm', 'sharepoint-input'],
                    'autocomplete' => 'off'
                ]); ?>
            </div>
            <div class="sharepoint dropdown">
                <button class="dropdown-toggle d-none" type="button" data-bs-toggle="dropdown"
                        data-bs-display="static" aria-expanded="false">
                    Dropdown button
                </button>
                <div class="dropdown-menu p-0 m-0 border-0">
                    <ul class="list-group">
                        <li class="list-group-item px-3 py-1"></li>
                    </ul>
                </div>
            </div>
            <div class="invalid-feedback"></div>
        </div>
    </div>
</div>

<?php

$js = <<<JS
// handle pasting of attachments
jQuery(document).off('paste');
jQuery(document).on('paste', paste);
var dataTransfer = new DataTransfer();
function paste(e) {
    var input = document.getElementById('task-attachments');
    for (var i = 0; i < e.originalEvent.clipboardData.items.length; i++) {
        var elem = e.originalEvent.clipboardData.files[i];
        if (typeof elem == 'object' && elem.constructor.name == 'File') {
            dataTransfer.items.add(elem);
        }
    }
    input.files = dataTransfer.files;
}
JS;

$this->registerJs($js);
