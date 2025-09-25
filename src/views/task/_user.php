<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\models\UserInterface;
use yii\web\View;

/* @var $this View */
/* @var $user UserInterface */
/* @var $assigned boolean */

?>
<span class="kanban-user text-truncate">
    <?php if ($user->photo): ?>
        <?= Html::img($user->photo, ['class' => ['rounded-circle', 'me-3']]); ?>
    <?php else: ?>
        <span class="kanban-visualisation me-3"><?= strtoupper(substr($user->name, 0, 1)); ?></span>
    <?php endif; ?>
    <?= Html::encode($user->name); ?>
</span>
<?php if ($assigned): ?>
    <?= FAS::i('times', ['class' => 'ms-auto']); ?>
<?php endif; ?>
