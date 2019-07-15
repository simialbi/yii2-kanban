<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\widgets;


use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\widgets\Widget;
use Yii;
use yii\bootstrap4\Html;

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
     */
    public function run()
    {
        $tasks = Task::find()
            ->alias('t')
            ->innerJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{t}}.[[id]] = {{u}}.[[task_id]]')
            ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
            ->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id])
            ->orderBy(['{{t}}.[[end_date]]', '{{t}}.[[start_date]]', '{{t}}.[[created_at]]']);

        $options = $this->options;
        Html::addCssClass($options, ['widget' => 'list-group']);
        $html = Html::beginTag('div', $options);

        $options = $this->itemOptions;
        Html::addCssClass($options, ['widget' => 'list-group-item list-group-item-action']);
        foreach ($tasks->all() as $task) {
            $options['href'] = ['plan/view', 'id' => $task->id];
            $html .= Html::beginTag('a', $options);
            $html .= Html::beginTag('div', [
                'class' => ['custom-control', 'custom-checkbox']
            ]);
            $html .= Html::checkbox("check[{$task->id}]", false, [
                'value' => Task::STATUS_DONE,
                'class' => ['custom-control-input'],
                'id' => 'sa-kanban-status-' . $task->id,
                'data' => [
                    'task-id' => $task->id
                ]
            ]);
            $html .= Html::label($task->subject, 'sa-kanban-status-' . $task->id);
            $html .= Html::endTag('div');
            $html .= Html::endTag('a');
        }

        $html .= Html::endTag('div');

        return $html;
    }
}
