<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\ButtonDropdown;

/* @var $this \yii\web\View */
/* @var $title string */
/* @var $id integer */
/** @var $renderButtons boolean */
/** @var $readonly boolean */

Frame::begin([
    'options' => [
        'id' => 'update-bucket-' . $id . '-frame'
    ]
]);
?>
<div class="kanban-bucket-header d-flex flex-row align-items-center mb-2 mb-md-0 <?php if ($renderButtons && !$readonly) : ?>draggable<?php endif; ?>">
    <h5 class="m-0 mx-auto mx-md-0"><?= $title; ?></h5>
    <?=FAS::i('arrows-alt', [
        'class' => ['ml-auto', 'kanban-bucket-sort-handle', 'd-none', 'd-md-block']
    ])?>
    <?php if ($renderButtons && !$readonly) : ?>
        <?= ButtonDropdown::widget([
            'label' => FAS::i('ellipsis-h'),
            'encodeLabel' => false,
            'direction' => ButtonDropdown::DIRECTION_RIGHT,
            'buttonOptions' => [
                'class' => ['toggle' => '', 'btn' => 'btn btn-sm']
            ],
            'options' => [
                'id' => 'bucket-dropdown-' . $id,
                'class' => ['d-none', 'd-md-block', 'ml-auto', 'ml-md-2', 'kanban-bucket-more']
            ],
            'dropdown' => [
                'items' => [
                    [
                        'label' => Yii::t('yii', 'Update'),
                        'url' => [
                            'bucket/update',
                            'id' => $id,
                            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                        ],
                        'linkOptions' => [
                            'data' => [
                                'turbo' => 'true',
                                'turbo-frame' => "update-bucket-$id-frame"
                            ]
                        ]
                    ],
                    [
                        'label' => Yii::t('yii', 'Delete'),
                        'url' => [
                            'bucket/delete',
                            'id' => $id,
                            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
                        ],
                        'linkOptions' => [
                            'data' => [
                                'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?')
                            ]
                        ]
                    ]
                ]
            ]
        ]); ?>
    <?php endif; ?>
</div>
<?php
Frame::end();
?>
