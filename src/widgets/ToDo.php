<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\widgets;

use rmrevin\yii\fontawesome\FAR;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\widgets\Widget;
use Yii;
use yii\bootstrap4\Html;
use yii\db\Expression;
use yii\helpers\Url;

/**
 *
 * @package simialbi\yii2\kanban\widgets
 */
class ToDo extends Widget
{
    /**
     * @var array the HTML attributes (name-value pairs) for the container tag.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     */
    public $options = [
        'class' => ['sa-todo']
    ];

    /**
     * @var array the HTML attributes (name-value pairs) for the items tags.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     */
    public $itemOptions = [
        'class' => ['rounded-0', 'p-2', 'd-flex',]
    ];

    /**
     * {@inheritDoc}
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $tasks = Task::find()
            ->cache(10)
            ->alias('t')
            ->with('bucket', 'board')
            ->innerJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{t}}.[[id]] = {{u}}.[[task_id]]')
            ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
            ->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id])
            ->addOrderBy(new Expression('-{{t}}.[[end_date]] DESC'))
            ->addOrderBy(new Expression('-{{t}}.[[start_date]] DESC'))
            ->addOrderBy(['{{t}}.[[created_at]]' => SORT_ASC]);

        $options = $this->options;
        Html::addCssClass($options, ['widget' => 'list-group']);
        $html = Html::beginTag('div', $options);

        foreach ($tasks->all() as $task) {
            $options = $this->itemOptions;
            Html::addCssClass($options, ['widget' => 'list-group-item list-group-item-action']);
            $options['href'] = Url::to(['/schedule/plan/view', 'id' => $task->board->id, 'showTask' => $task->id]);

            $content = Html::tag('h6', $task->subject, ['class' => ['m-0']]);
            $small = $task->board->name;
            if ($task->getChecklistElements()->count()) {
                $small .= '&nbsp;&bull;&nbsp;' . $task->getChecklistStats();
            }
            if ($task->end_date) {
                if ($task->end_date < time()) {
                    Html::addCssClass($options, 'list-group-item-danger');
                }
                $small .= '&nbsp;&bull;&nbsp;' . FAR::i('calendar') . ' ';
                $small .= Yii::$app->formatter->asDate($task->end_date, 'short');
            }
            if ($task->getComments()->count()) {
                $small .= '&nbsp;&bull;&nbsp;' . FAR::i('sticky-note');
            }
            $content .= Html::tag('small', $small);

            $html .= Html::beginTag('a', $options);
            $html .= Html::beginTag('div', [
                'class' => ['form-check']
            ]);
            $html .= Html::checkbox("check[{$task->id}]", false, [
                'value' => Task::STATUS_DONE,
                'class' => ['form-check-input'],
                'id' => 'sa-kanban-status-' . $task->id,
                'data' => [
                    'task-id' => $task->id
                ]
            ]);

            $html .= Html::label($content, 'sa-kanban-status-' . $task->id, [
                'class' => ['form-check-label']
            ]);
            $html .= Html::endTag('div');
            $html .= Html::endTag('a');
        }

        $html .= Html::endTag('div');

        $this->registerPlugin();

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    protected function registerPlugin($pluginName = null)
    {
        $id = $this->options['id'];
        $url = Url::to(['/schedule/task/set-status']);
        $js = <<<JS
jQuery('#$id label').on('click', function (e) {
    e.preventDefault();
    window.location.replace(jQuery(this).closest('a').prop('href'));
});
jQuery('#$id input[type="checkbox"]').on('click', function (e) {
    e.stopImmediatePropagation();
    e.stopPropagation();
    e.preventDefault();
	var that = jQuery(this);
	var id = that.data('taskId');
	that.prop('checked', true);
	$.get('$url?id=' + id + '&status=0', function () {
		that.closest('.list-group-item').remove();
	});
});
JS;

        $this->view->registerJs($js);
    }
}
