<?php

use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\GanttAsset;
use simialbi\yii2\turbo\Frame;
use simialbi\yii2\turbo\Modal;
use yii\helpers\Json;

/* @var $this \yii\web\View */
/* @var $board \simialbi\yii2\kanban\models\Board */
/* @var $users array */

GanttAsset::register($this);

?>
    <div class="kanban-plan-gantt pb-6 pb-md-0 position-relative">
        <div id="gantt"></div>
    </div>
<?php
echo $this->render('_gantt-templates');

$jsonUsers = Json::encode($users);
$worker = Yii::t('simialbi/kanban/model/monitoring-member', 'User');

$js = <<<JS
window.gantt = new GanttMaster();
window.gantt.init(jQuery('#gantt'));
window.gantt.loadProject({
    tasks: [],
    resources: $jsonUsers,
    roles: [
        {id: "tmp_1", name: "$worker"}
    ],
    canWrite: true,
    canWriteOnParent: true
});
JS;

$this->registerJs($js);
