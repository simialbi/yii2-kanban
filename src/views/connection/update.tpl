
{set title="Update connection"|t:"simialbi/kanban/connection"}
{set breadcrumbs=[
    ['label' => "Users"|t:"hq-re/hrm/user", 'url' => ['/hrm/user/index']],
    ['label' => $model->address->fullname, 'url' => ['/hrm/user/view', 'id' => $model->address_id]],
    $this->title
] links=[
    [
        'label' => "Save"|t:"hq-base",
        'url' => '#connectionForm',
        'options' => [
            'class' => ['btn', 'btn-success'],
            'onclick' => 'jQuery(\'#connectionForm\').submit()'
        ]
    ]
]}

<div>
    {$this->render('_form', [
        'model' => $model,
        'buckets' => $buckets
    ])}
</div>
