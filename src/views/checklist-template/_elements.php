<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\ChecklistTemplate;
use simialbi\yii2\kanban\widgets\Sortable;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model ChecklistTemplate */
?>

<div class="card panel-default">
    <?php
    $items = [];
    foreach ($model->elements as $element) {
        $name = Html::tag('span', $element->name);

        $date = '';
        if ($element->dateOffset !== null) {
            $interval = $element->dateOffset;
            $date = Html::tag(
                'span',
                Yii::t('simialbi/kanban/checklist-template', '+{n,plural,=1{# Day} other{# Days}}', [
                    'n' => $interval
                ]),
                ['class' => ['flex-grow-1', 'text-end', 'me-5']]
            );
        }

        $actions = '';
        if (Yii::$app->user->id === $model->created_by) {
            $actions = Html::a(FAS::i('pencil-alt'), '#hqDynamicModal', [
                    'class' => ['mx-2'],
                    'title' => Yii::t('yii', 'Update'),
                    'data' => [
                        'bs-toggle' => 'modal',
                        'src' => Url::to(['checklist-template/update-element', 'id' => $element->id])
                    ]
                ]) . Html::a(FAS::i('trash-alt'), ['checklist-template/delete-element', 'id' => $element->id], [
                    'class' => ['me-3'],
                    'title' => Yii::t('yii', 'Delete'),
                    'data' => [
                        'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'method' => 'post'
                    ]
                ]);
            $actions = Html::tag('span', $actions);
        }

        $content = Html::tag('span', $name . $date . $actions, [
            'class' => ['d-flex', 'flex-grow-1', 'justify-content-between', 'align-items-center']
        ]);

        $items[] = [
            'content' => $content,
            'options' => [
                'data' => [
                    'id' => $element->id
                ]
            ]
        ];
    }
    $url = Url::toRoute(['sort/move-to-position']);
    echo Sortable::widget([
        'options' => [
            'class' => ['list-group', 'list-group-flush']
        ],
        'items' => $items,
        'itemTemplate' => "{content}{append}{handle}\n",
        'itemOptions' => [
            'class' => ['list-group-item', 'd-flex', 'align-items-center', 'cursor-default']
        ],
        'showHandle' => Yii::$app->user->id === $model->created_by,
        'handleLabel' => FAS::i('grip-dots'),
        'handleOptions' => [
            'class' => ['cursor-move']
        ],
        'clientOptions' => [
            'axis' => 'y'
        ],
        'clientEvents' => [
            'sortstop' => 'function (event, ui) {
                $.post("' . $url . '", {
                    modelClass: "simialbi\\\\yii2\\\\kanban\\\\models\\\\ChecklistTemplateElement",
                    modelPk: ui.item.data("id"),
                    position: ui.item.index() + 1
                }, function (data) {
                    if (data.sort.errors === false) { // TODO find better solution
                        alert(' . Yii::t('simialbi/kanban/checklist-template', 'Element moved successfully') . ');
                    } else {
                        alert(' . Yii::t('simialbi/kanban/checklist-template', 'Element could not be moved') . ');
                    }
                });
            }'
        ]
    ]);
    ?>
</div>
