<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\widgets;

use rmrevin\yii\fontawesome\FAL;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\hideseek\HideSeek;
use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\KanbanAsset;
use simialbi\yii2\kanban\KanbanSwiperAsset;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\turbo\Frame;
use simialbi\yii2\turbo\Modal;
use simialbi\yii2\widgets\Widget;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\DbQueryDependency;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\View;

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
    public array $listOptions = ['widget' => 'list-group', 'class' => 'list-group'];

    /**
     * @var array the HTML attributes (name-value pairs) for the items tags.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     */
    public array $itemOptions = [
        'class' => ['widget' => 'list-group-item list-group-item-action', 'rounded-0', 'p-2']
    ];

    /**
     * @var boolean Set this property to *true* to add a board filter on top
     * of the list.
     */
    public bool $addBoardFilter = false;

    /**
     * @var string Kanban module name
     */
    public string $kanbanModuleName = 'kanban';

    /**
     * @var boolean Whether to render the modal or not
     */
    public bool $renderModal = true;

    /**
     * @var int seconds to cache the task-query
     */
    public int $cacheDuration = 0;

    /**
     * @var bool Whether to include checklist tasks or not
     * @deprecated since 2.14.0
     */
    public bool $includeChecklists = false;

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException|\Throwable
     */
    public function run()
    {
        $this->view->registerAssetBundle(KanbanSwiperAsset::class);
        $this->view->registerAssetBundle(KanbanAsset::class);

        $query = Task::find()
            ->alias('t')
            ->select('SUM({{t}}.[[updated_at]])')
            ->innerJoinWith('assignments u', false)
            ->where([
                'and',
                ['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]],
                ['{{u}}.[[user_id]]' => Yii::$app->user->id]
            ]);

        $dependency = new DbQueryDependency([
            'query' => $query,
            'method' => 'scalar'
        ]);

        $tasks = Task::find()
            ->cache($this->cacheDuration, $dependency)
            ->alias('t')
            ->with(['checklistElements', 'comments', 'children'])
            ->innerJoinWith(['bucket.board b', 'assignments u'])
            ->where([
                'and',
                ['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]],
                ['{{u}}.[[user_id]]' => Yii::$app->user->id],
            ]);

        if (!(Yii::$app->user->identity?->settings?->kanban_showChecklistInTodo ?? false)) {
            $tasks->andWhere(['{{b}}.[[is_checklist]]' => false]);
        }

        $results = $tasks->all();
        /** @var Module $module */
        $module = Yii::$app->getModule($this->kanbanModuleName);
        $module::sortTasks($results);

        ob_start();
        if ($this->renderModal) {
            $modal = Modal::widget([
                'modalClass' => '\yii\bootstrap5\Modal',
                'options' => [
                    'id' => 'task-modal',
                    'options' => [
                        'class' => ['modal', 'remote', 'fade']
                    ],
                    'clientOptions' => [
                        'backdrop' => 'static',
                        'keyboard' => false
                    ],
                    'size' => \yii\bootstrap5\Modal::SIZE_EXTRA_LARGE,
                    'title' => null,
                    'closeButton' => false
                ],
            ]);

            // check if modal already exists, if not, append to body
            $js = <<<JS
if (jQuery("#task-modal").length === 0 ) {
    jQuery("body").append(`$modal`);
}
JS;
            $this->view->registerJs($js, View::POS_END);
        }
        Frame::begin(['options' => ['id' => 'kanban-todo-frame', 'class' => 'kanban-todo-frame']]);
        echo Html::beginTag('div', $this->options);
        if ($this->addBoardFilter) {
            echo HideSeek::widget([
                'fieldTemplate' => '<div class="search-field mb-3">{input}</div>',
                'options' => [
                    'id' => 'search-widget-todo',
                    'placeholder' => Yii::t('simialbi/kanban', 'Filter by keyword'),
                    'autocomplete' => 'off'
                ],
                'clientOptions' => [
                    'list' => '#' . $this->id . ' .list-group',
                    'attribute' => 'alt'
                ],
            ]);
        }
        echo Html::beginTag('div', $this->listOptions);

        foreach ($results as $task) {
            $id = $task->isRecurrentInstance() ? $task->recurrence_parent_id : $task->id;
            $options = $this->itemOptions;
            $options['href'] = Url::to([
                "/{$this->kanbanModuleName}/task/update",
                'id' => $id,
                'return' => 'todo',
                'readonly' => false
            ]);
            $options['data'] = [
                'pjax' => '0',
                'turbo-frame' => 'task-modal-frame',
            ];
            $options['alt'] = $task->subject . ' ' . str_replace(
                    ["\r", "\n"],
                    ' ',
                    strip_tags((string)$task->description)
                ) . ' ' . $task->bucket->board->name . ' ' . $task->bucket->name;

            $subject = $task->subject;
            if ($task->isRecurrentInstance()) {
                $subject = FAS::i('infinity', [
                        'data' => [
                            'fa-transform' => 'shrink-4.5',
                            'fa-mask' => 'fas fa-circle'
                        ]
                    ]) . ' ' . $subject;
            }
            $content = Html::tag('h6', $subject, ['class' => ['m-0']]);
            $small = $task->bucket->board->name;

            if (($cnt = count($task->checklistElements)) > 0) {
                $grouped = ArrayHelper::index($task->checklistElements, null, 'is_done');
                $done = count(ArrayHelper::getValue($grouped, '1', []));
                $small .= "&nbsp;&bull;&nbsp;$done/$cnt";
            }
            if ($task->endDate) {
                if ($task->endDate < time()) {
                    Html::addCssClass($options, 'list-group-item-danger');
                } elseif ($task->start_date && $task->start_date <= time()) {
                    Html::addCssClass($options, 'list-group-item-info');
                }
                $small .= '&nbsp;&bull;&nbsp;' . FAL::i('calendar') . ' ';
                $small .= Yii::$app->formatter->asDate($task->endDate, 'short');
            }
            if (!empty($task->ticket_id)) {
                $small .= '&nbsp;&bull;&nbsp;' . FAL::i('headset');
            }
            if (!empty($task->comments)) {
                $small .= '&nbsp;&bull;&nbsp;' . FAL::i('sticky-note');
            }
            if ($task->parent_id || $task->children) {
                $small .= '&nbsp;&bull;&nbsp;' . FAL::i('diagram-subtask');
            }
            $content .= Html::tag('small', $small, [
                'class' => ['d-block', 'lh-1', 'mt-1']
            ]);

            echo Html::beginTag('a', $options);
            echo Html::beginTag('div', [
                'class' => ['form-check']
            ]);
            echo Html::checkbox("check[$id]", false, [
                'value' => Task::STATUS_DONE,
                'class' => ['form-check-input'],
                'id' => 'sa-kanban-status-' . $id,
                'data' => [
                    'task-id' => $id
                ]
            ]);

            echo Html::label($content, 'sa-kanban-status-' . $id, [
                'class' => ['form-check-label']
            ]);
            echo Html::endTag('div');
            echo Html::endTag('a');
        }

        echo Html::endTag('div');
        echo Html::endTag('div');

        $this->view->registerJs('jQuery("#task-modal").modal("hide");');

        Frame::end();

        $this->registerPlugin();

        return ob_get_clean();
    }

    /**
     * {@inheritDoc}
     */
    protected function registerPlugin($pluginName = null, $selector = null): void
    {
        $id = $this->options['id'];
        $url = Url::to(["/{$this->kanbanModuleName}/task/set-status"]);
        $js = <<<JS
jQuery('#$id label').on('click.sa.kanban', function (e) {
    // debugger;
    e.preventDefault();
    // window.location.replace(jQuery(this).closest('a').prop('href'));
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
