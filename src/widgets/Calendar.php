<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\widgets;

use simialbi\yii2\widgets\Widget;
use Yii;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

class Calendar extends Widget
{
    /**
     * @var array options the HTML attributes (name-value pairs) for the field container tag.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     */
    public $options = [
        'class' => 'sa-calendar'
    ];
    /**
     * @var array|string even an array array or a url to json action
     */
    public $events;
    /**
     * @var array client options (full calendar plugin options)
     */
    public $clientOptions = [];
    /**
     * @var array default client options (full calendar plugin options)
     */
    private $_defaultClientOptions = [
        'weekends' => true,
        'editable' => true,
        'selectable' => true,
        'header' => [
            'left' => 'title',
            'center' => 'prev,today,next',
            'right' => 'month,agendaWeek,agendaDay,listYear'
        ],
        'eventLimit' => true
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->events)) {
            $this->events = [];
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        echo Html::beginTag('div', $this->options);
        echo Html::beginTag('div', ['class' => 'fc-loading', 'style' => 'display: none;']);
        echo Html::encode(Yii::t('simialbi/kanban', 'Loading...'));
        echo Html::endTag('div');
        echo Html::endTag('div');
        $this->registerPlugin();
    }

    /**
     * Registers the FullCalendar javascript assets and builds the required js for the widget and the related events
     *
     * @param string $pluginName
     */
    protected function registerPlugin($pluginName = 'fullCalendar')
    {
        $id = $this->options['id'];
        $view = $this->getView();
        CalendarAsset::register($view);
        $js = [
            "var loading_container = jQuery('#$id .fc-loading');",
            "jQuery('#$id').empty().append(loading_container);",
            "jQuery('#$id').$pluginName(" . $this->getClientOptions() . ');'
        ];
        $view->registerJs(implode("\n", $js), View::POS_READY);
    }

    /**
     * Get client options as json encoded string
     *
     * @return string
     */
    protected function getClientOptions()
    {
        $id = $this->options['id'];
        $options = [
            'loading' => new JsExpression('function (isLoading, view) {
				jQuery(\'#' . $id . '\').find(\'.fc-loading\').toggle(isLoading);
			}'),
            'locale' => strtolower(Yii::$app->language),
            'events' => $this->events
        ];
        $options = ArrayHelper::merge($this->_defaultClientOptions, $options, $this->clientOptions);
        return Json::encode($options);
    }
}
