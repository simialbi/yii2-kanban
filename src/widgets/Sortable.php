<?php

namespace simialbi\yii2\kanban\widgets;

use simialbi\yii2\kanban\helpers\Html;
use simialbi\yii2\kanban\SortableAsset;
use simialbi\yii2\widgets\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class Sortable
 *
 * @package simialbi\yii2\kanban\widgets
 */
class Sortable extends Widget
{
    /**
     * @var array options the HTML attributes (name-value pairs) for the field container tag.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     */
    public $options = [];

    /**
     * @var array the sortable items configuration for rendering elements within the sortable
     * list / grid. You can set the following properties:
     * - content: string, the list item content (this is not HTML encoded)
     * - disabled: bool, whether the list item is disabled
     * - options: array, the HTML attributes for the list item.
     */
    public array $items = [];

    /**
     * @var boolean whether to show handle for each sortable item
     */
    public bool $showHandle = false;

    /**
     * @var string the handle label, this is not HTML encoded
     */
    public string $handleLabel = '<i class="glyphicon glyphicon-move"></i>';

    /**
     * @var array the HTML attributes to be applied to the handle (if exists)
     */
    public array $handleOptions = [];

    /**
     * @var string the HTML template
     */
    public string $itemTemplate = "{handle}{content}{append}\n";

    /**
     * @var array the HTML attributes to be applied to all items.
     * This will be overridden by the [[options]] property within [[$items]].
     */
    public array $itemOptions = [];

    /**
     * @var array the options for the underlying jQuery UI widget.
     * Please refer to the corresponding jQuery UI widget Web page for possible options.
     * For example, [this page](http://api.jqueryui.com/accordion/) shows
     * how to use the "Accordion" widget and the supported options (e.g. "header").
     */
    public $clientOptions = [
        'items' => '> li',
        'placeholder' => 'sortable-placeholder',
        'forcePlaceholderSize' => true
    ];

    /**
     * @var array the event handlers for the underlying jQuery UI widget.
     * Please refer to the corresponding jQuery UI widget Web page for possible events.
     * For example, [this page](http://api.jqueryui.com/accordion/) shows
     * how to use the "Accordion" widget and the supported events (e.g. "create").
     * Keys are the event names and values are javascript code that is passed to the `.on()` function
     * as the event handler.
     *
     * For example you could write the following in your widget configuration:
     *
     * ```php
     * 'clientEvents' => [
     *     'change' => 'function () { alert('event "change" occured.'); }'
     * ],
     * ```
     */
    public $clientEvents = [];

    /**
     * @var array
     */
    protected array $ids = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if ($this->showHandle) {
            $this->clientOptions['handle'] = '.handle';
            Html::addCssClass($this->handleOptions, 'handle');
        }

        $this->ids[] = $this->options['id'];

        parent::init();
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function run(): void
    {
        Html::addCssClass($this->options, 'hq-sortable');
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'ul');
        Html::addCssClass($options, 'hq-sortable-root');
        echo Html::beginTag($tag, $options);
        echo $this->renderItems();
        echo Html::endTag($tag);
        $this->registerAssets();
    }

    /**
     * Registers sortable widget asset bundle, initializes it with client options and registers related events
     */
    public function registerAssets(): void
    {
        $view = $this->getView();
        SortableAsset::register($view);

        $id = 'jQuery(\'#' . implode(',#', $this->ids) . '\')';
        $js = ["{$id}.sortable(" . Json::encode($this->clientOptions) . ')'];

        foreach ($this->clientEvents as $eventName => $handler) {
            $js[] = "{$id}.on('$eventName', $handler);";
        }

        $view->registerJs(implode(PHP_EOL, $js));
    }

    /**
     * Render the list items for the sortable widget
     *
     * @param array|null $items
     *
     * @return string
     * @throws \Exception
     */
    protected function renderItems(?array $items = null): string
    {
        $html = '';
        $handle = ($this->showHandle) ? Html::tag('span', $this->handleLabel, $this->handleOptions) : '';
        if (!is_array($items)) {
            $items = $this->items;
        }
        foreach ($items as $item) {
            $children = ArrayHelper::remove($item, 'children', []);
            $options = ArrayHelper::remove($item, 'options', []);
            $append = ArrayHelper::remove($item, 'append', '');
            $content = ArrayHelper::remove($item, 'content', '');
            $options = ArrayHelper::merge($this->itemOptions, $options);
            $tag = ArrayHelper::remove($options, 'tag', 'li');
            if (ArrayHelper::getValue($item, 'disabled', false)) {
                Html::addCssClass($options, 'disabled');
            }
            // $handle . Html::tag('span', ArrayHelper::getValue($item, 'content', '')) . PHP_EOL
            $content = strtr($this->itemTemplate, [
                '{handle}' => $handle,
                '{content}' => $content,
                '{append}' => $append
            ]);
            if (!empty($children)) {
                $id = static::$autoIdPrefix . static::$counter++;
                $this->ids[] = $id;
                $content .= Html::tag('ul', $this->renderItems($children) . PHP_EOL, ArrayHelper::merge(
                    $this->options,
                    ['id' => $id]
                ));
            }
            $html .= Html::tag($tag, $content, $options) . PHP_EOL;
        }

        return $html;
    }
}
