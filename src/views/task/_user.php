<?php

use rmrevin\yii\fontawesome\FAS;
use yii\bootstrap4\Html;

/* @var $this \yii\web\View */
/* @var $user \simialbi\yii2\models\UserInterface */
/* @var $assigned boolean */

?>
<span class="kanban-user text-truncate">
    <?php if ($user->image): ?>
        <?= Html::img($user->image, ['class' => ['rounded-circle', 'mr-3']]); ?>
    <?php else: ?>
        <span class="kanban-visualisation mr-3"><?= strtoupper(substr($user->name, 0, 1)); ?></span>
    <?php endif; ?>
    <?= Html::encode($user->name); ?>
</span>
<?php if ($assigned): ?>
    <?= FAS::i('times', ['class' => 'ml-auto']); ?>
<?php endif; ?>
