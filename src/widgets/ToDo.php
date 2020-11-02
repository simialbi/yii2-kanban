<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\widgets;

use kartik\select2\Select2;
use rmrevin\yii\fontawesome\FAR;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\widgets\Widget;
use Yii;
use yii\bootstrap4\Html;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 *
 * @package simialbi\yii2\kanban\widgets
 */
class ToDo extends Widget
{
    /**
     * {@inheritDoc}
     */
    public static $autoIdPrefix = 'sa-w';

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
    public $listOptions = ['widget' => 'list-group'];

    /**
     * @var array the HTML attributes (name-value pairs) for the items tags.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     */
    public $itemOptions = [
        'class' => ['widget' => 'list-group-item list-group-item-action', 'rounded-0', 'p-2', 'd-flex']
    ];

    /**
     * @var boolean Set this property to *true* to add a board filter on top
     * of the list.
     */
    public $addBoardFilter = false;

    /**
     * {@inheritDoc}
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $tasks = Task::find()
            ->cache(10)
            ->alias('t')
            ->innerJoinWith('bucket bu')
            ->innerJoinWith('board b')
            ->innerJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{t}}.[[id]] = {{u}}.[[task_id]]')
            ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
            ->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id])
            ->addOrderBy(new Expression('-{{t}}.[[end_date]] DESC'))
            ->addOrderBy(new Expression('-{{t}}.[[start_date]] DESC'))
            ->addOrderBy(['{{t}}.[[created_at]]' => SORT_ASC]);

        $html = Html::beginTag('div', $this->options);
        if ($this->addBoardFilter) {
            $filters = Yii::$app->request->getBodyParam('ToDo', []);
            $boards = ArrayHelper::map(ArrayHelper::getColumn($tasks->all(), 'board'), 'id', 'name');
            if (isset($filters['boardId'])) {
                $tasks->andFilterWhere(['{{b}}.[[id]]' => $filters['boardId']]);
            }
            $html .= Html::beginTag('div', [
                'class' => ['sa-todo-filter', 'mb-3']
            ]);
            $html .= Html::beginForm(['plan/index', 'activeTab' => 'todo'], 'POST', [
                'id' => $this->options['id'] . '-filter-form'
            ]);
            $html .= Select2::widget([
                'name' => 'ToDo[boardId]',
                'value' => ArrayHelper::getValue($filters, 'boardId'),
                'theme' => Select2::THEME_BOOTSTRAP,
                'data' => $boards,
                'options' => [
                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by board'),
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
                'pluginEvents' => [
                    'change' => new JsExpression('function () { jQuery(this).closest(\'form\').submit(); }')
                ]
            ]);
            $html .= Html::endForm();
            $html .= Html::endTag('div');
        }
        $html .= Html::beginTag('div', $this->listOptions);

        foreach ($tasks->all() as $task) {
            /** @var Task $task */
            $options = $this->itemOptions;
            $options['href'] = Url::to(['/schedule/plan/view', 'id' => $task->board->id, 'showTask' => $task->id]);

            $content = Html::tag('h6', $task->subject, ['class' => ['m-0']]);
            $small = $task->board->name;
            if ($task->getChecklistElements()->count()) {
                $small .= '&nbsp;&bull;&nbsp;' . $task->getChecklistStats();
            }
            if ($task->end_date) {
                if ($task->end_date < time()) {
                    Html::addCssClass($options, 'list-group-item-danger');
                } elseif ($task->start_date && $task->start_date <= time()) {
                    Html::addCssClass($options, 'list-group-item-info');
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
