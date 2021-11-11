<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\turbo\Frame;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $model \simialbi\yii2\kanban\models\Task */
/* @var $history array */
/* @var $statuses array */

Frame::begin([
    'options' => [
        'id' => 'task-modal-frame'
    ]
]);
?>
    <div class="kanban-task-modal">
        <div class="modal-header">
            <h5 class="modal-title"><?= Yii::t('simialbi/kanban', 'View history'); ?></h5>
            <?= Html::button('<span aria-hidden="true">' . FAS::i('times') . '</span>', [
                'type' => 'button',
                'class' => ['close'],
                'data' => [
                    'dismiss' => 'modal'
                ],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Close')
                ]
            ]); ?>
        </div>
        <div class="modal-body">
            <table class="table table-condensed table-striped">
                <thead>
                    <tr>
                        <th><?= Yii::t('simialbi/kanban', 'Execution date'); ?></th>
                        <th><?= $model->getAttributeLabel('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="2"><?= Yii::t('yii', '(not set)') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= Yii::$app->formatter->asDate($row['date'], 'EEEE, dd. MMMM yyyy'); ?></td>
                                <td><?= $statuses[$row['status']]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <?= Html::button(Yii::t('simialbi/kanban', 'Close'), [
                'type' => 'button',
                'class' => ['btn', 'btn-dark'],
                'data' => [
                    'dismiss' => 'modal'
                ],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Close')
                ]
            ]); ?>
        </div>
    </div>
<?php
Frame::end();
