<?php

use rmrevin\yii\fontawesome\FAS;
use rmrevin\yii\fontawesome\FontAwesome;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Task;
use yii\bootstrap5\ButtonDropdown;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $boards Board[] */
/* @var $type string */

$i = 0;
?>
<?php foreach ($boards as $board): ?>
    <?php $options = ['class' => ['kanban-board']]; ?>
    <?= Html::beginTag('div', $options); ?>
    <div class="kanban-board-inner">
        <a href="<?= Url::to(['plan/view', 'id' => $board->id]); ?>"
           class="kanban-board-image d-flex justify-content-center align-items-center text-decoration-none">
            <?php if ($board->image): ?>
                <?= Html::img($board->image, ['class' => ['img-fluid']]); ?>
            <?php else: ?>
                <span class="kanban-visualisation modulo-<?= $board->id % 10; ?>">
                    <?= substr($board->name, 0, 1); ?>
                </span>
            <?php endif; ?>
        </a>
        <div class="kanban-board-meta">
            <div class="d-flex align-items-stretch h-100">
                <a href="<?= Url::to(['plan/view', 'id' => $board->id]); ?>"
                   class="flex-grow-1 text-body text-decoration-none lh-1">
                    <h5 class="pt-0">
                        <?php if ($board->hidden): ?>
                            <?= FAS::i('eye-slash')->size(FontAwesome::SIZE_XS); ?>
                        <?php endif; ?>
                        <?= Html::encode($board->name); ?>
                    </h5>
                    <small class="text-muted">
                        <?php
                        if ($board->is_checklist) {
                            $all = $board->getTasks()->count();
                            $done = $board->getTasks()->andWhere(['status' => Task::STATUS_DONE])->count();
                            echo Html::tag('span', $done . ' / ' . $all, ['class' => ['me-3']]);
                        }
                        echo Yii::$app->formatter->asDatetime($board->updated_at, 'php:d.m.Y H:i');
                        ?>
                    </small>
                </a>
            </div>
        </div>
        <span class="kanban-board-options d-flex flex-column p-3 ps-0">
            <?php
            $items = [];
            if (Yii::$app->user->id == $board->created_by) {
                $items[] = [
                    'label' => FAS::i('edit')->fixedWidth() . ' ' . Yii::t('simialbi/kanban/plan', 'Update plan'),
                    'url' => ['plan/update', 'id' => $board->id],
                    'linkOptions' => [
                        'class' => ['text-body']
                    ],
                ];
                $items[] = [
                    'label' => FAS::i('trash-alt')->fixedWidth() . ' ' . Yii::t('simialbi/kanban/plan', 'Delete plan'),
                    'url' => ['plan/delete', 'id' => $board->id],
                    'linkOptions' => [
                        'class' => ['text-body'],
                        'data' => [
                            'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'method' => 'post'
                        ]
                    ],
                ];
                $items[] = '-';
            }
            $items[] = [
                'label' => FAS::i('ballot-check')->fixedWidth() . ' ' . Yii::t('simialbi/kanban/plan', 'Checklist templates'),
                'url' => ['checklist-template/index', 'boardId' => $board->id],
                'linkOptions' => [
                    'class' => ['text-body']
                ]
            ];
            $items[] = '-';
            $items[] = [
                'label' => $board->hidden ?
                    FAS::i('eye')->fixedWidth() . ' ' . Yii::t('simialbi/kanban/plan', 'Show board again') :
                    FAS::i('eye-slash')->fixedWidth() . ' ' . Yii::t('simialbi/kanban/plan', 'Hide board'),
                'url' => ['plan/toggle-board-visibility', 'id' => $board->id],
                'linkOptions' => [
                    'class' => ['text-body']
                ]
            ];

            echo ButtonDropdown::widget([
                'label' => FAS::i('ellipsis')->fixedWidth(),
                'encodeLabel' => false,
                'buttonOptions' => [
                    'class' => ['btn', 'btn-sm', 'shadow-none']
                ],
                'dropdown' => [
                    'items' => $items,
                    'encodeLabels' => false
                ]
            ]);
            ?>
        </span>
    </div>
    <?= Html::endTag('div'); ?>
<?php endforeach; ?>

<?php if ($type == 'plans'): ?>
    <div class="kanban-board bg-white p-3 d-none d-md-block position-relative">
        <h5 class="mb-0 pt-0"><?= Html::encode(Yii::t('simialbi/kanban/plan', 'Create board')); ?></h5>
        <?= Html::a('', ['plan/create'], [
            'class' => ['stretched-link']
        ]); ?>
    </div>

    <?= Html::a(FAS::i('plus'), ['plan/create'], [
        'class' => ['kanban-create-mobile', 'd-md-none', 'rounded-circle', 'bg-secondary', 'text-white', 'p-3'],
        'title' => Html::encode(Yii::t('simialbi/kanban/plan', 'Create board'))
    ]); ?>
<?php endif; ?>
