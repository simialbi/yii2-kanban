<?php

use kartik\select2\Select2;
use simialbi\yii2\kanban\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Connection */
/* @var $buckets array */

?>
<div class="card panel-default">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'connection-form'
        ]); ?>
        <div class="row g-3">
            <?= $form->field($model, 'bucket_id', [
                'options' => [
                    'class' => ['col-12', 'col-lg-6']
                ]
            ])->widget(Select2::class, [
                'data' => $buckets
            ]); ?>
            <div class="w-100 m-0"></div>

            <?= $form->field($model, 'export_time_windows', [
                'options' => [
                    'class' => ['col-12', 'col-lg-6']
                ]
            ])->checkbox(); ?>

            <div class="w-100 m-0"></div>

            <?= $form->field($model, 'import_tasks', [
                'options' => [
                    'class' => ['col-12', 'col-lg-6', 'mt-0']
                ]
            ])->checkbox(); ?>

            <div class="w-100 m-0"></div>

            <?= $form->field($model, 'delete_tasks_after_import', [
                'options' => [
                    'class' => ['col-12', 'col-lg-6', 'mt-0']
                ]
            ])->checkbox(); ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<?php

$importTasks = Html::getInputId($model, 'import_tasks');
$deleteTasksAfterImport = Html::getInputId($model, 'delete_tasks_after_import');

$js = <<<JS
jQuery('#$importTasks').on('change', function () {
    if (!jQuery(this).is(':checked')) {
        jQuery('#$deleteTasksAfterImport').prop('checked', false).prop('disabled', true);
    } else {
        jQuery('#$deleteTasksAfterImport').prop('disabled', false);
    }
}).trigger('change');
JS;


$this->registerJs($js);

