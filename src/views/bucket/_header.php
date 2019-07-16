<?php

use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\ButtonDropdown;
use yii\widgets\Pjax;

/* @var $this \yii\web\View */
/* @var $title string */
/* @var $id integer */

?>
<?php Pjax::begin([
    'id' => 'updateBucketPjax' . $id,
    'formSelector' => '#updateBucketForm' . $id,
    'enablePushState' => false,
    'clientOptions' => ['skipOuterContainers' => true]
]); ?>
<div class="kanban-bucket-header d-flex flex-row align-items-center">
    <h5 class="m-0"><?= $title; ?></h5>
    <?= ButtonDropdown::widget([
        'label' => FAS::i('ellipsis-h'),
        'encodeLabel' => false,
        'direction' => ButtonDropdown::DIRECTION_RIGHT,
        'buttonOptions' => [
            'class' => ['toggle' => '', 'btn' => 'btn btn-sm']
        ],
        'options' => [
            'class' => ['ml-auto', 'kanban-bucket-more']
        ],
        'dropdown' => [
            'items' => [
                [
                    'label' => Yii::t('yii', 'Update'),
                    'url' => [
                        'bucket/update',
                        'id' => $id,
                        'group' => Yii::$app->request->getQueryParam('group', 'bucket')
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
</div>
<?php Pjax::end(); ?>
