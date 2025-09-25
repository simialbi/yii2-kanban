<?php

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\turbo\Frame;
use yii\web\View;

/* @var $this View */
/* @var $model Task */
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
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    'bs-dismiss' => 'modal'
                ],
                'aria' => [
                    'label' => Yii::t('simialbi/kanban', 'Close')
                ]
            ]); ?>
        </div>
    </div>
<?php
Frame::end();
