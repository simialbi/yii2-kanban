
{use class="kartik\select2\Select2" type="function"}
{use class="yii\bootstrap5\ActiveForm" type="block"}
{use class="yii\bootstrap5\Html"}

<div class="card panel-default">
    <div class="card-body">
        {ActiveForm assign="form" id="connectionForm"}
            <div class="row g-3">
                {$form->field($model, 'bucket_id', [
                    'options' => [
                        'class' => ['col-12', 'col-lg-6']
                    ]
                ])->widget(Select2::class, [
                    'data' => $buckets
                ])}

                <div class="w-100 m-0"></div>

{*                <small class="mt-4 mb-2">*}
{*                    {'For the following options to work, you have to grant access to your inbox to the {0} service user.'|t:'simialbi/kanban/model/connection':[*}
{*                        '<code>'|cat:Yii::$app->get('ews')->username|cat:'</code>'*}
{*                    ]}*}
{*                </small>*}

                {$form->field($model, 'export_time_windows', [
                    'options' => [
                        'class' => ['col-12', 'col-lg-6']
                    ]
                ])->checkbox()}

                <div class="w-100 m-0"></div>

                {$form->field($model, 'import_tasks', [
                    'options' => [
                        'class' => ['col-12', 'col-lg-6', 'mt-0']
                    ]
                ])->checkbox()}

                <div class="w-100 m-0"></div>

                {$form->field($model, 'delete_tasks_after_import', [
                    'options' => [
                        'class' => ['col-12', 'col-lg-6', 'mt-0']
                    ]
                ])->checkbox()}
            </div>
        {/ActiveForm}
    </div>
</div>
{registerJs}
    $('#{Html::getInputId($model, 'import_tasks')}').on('change', function() {
        if (!$(this).is(':checked')) {
            $('#{Html::getInputId($model, 'delete_tasks_after_import')}').prop('checked', false).prop('disabled', true);
        } else {
            $('#{Html::getInputId($model, 'delete_tasks_after_import')}').prop('disabled', false);
        }
    }).trigger('change');
{/registerJs}
