<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\widgets;


use simialbi\yii2\web\AssetBundle;

class CalendarAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@bower/fullcalendar/dist';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'core/main.css',
        'daygrid/main.css',
        'timegrid/main.css',
        'list/main.css',
        'bootstrap/main.css'
    ];

    /**
     * {@inheritDoc}
     */
    public $js = [
        'core/main.js',
        'daygrid/main.js',
        'timegrid/main.js',
        'list/main.js',
        'bootstrap/main.js',
        'core/locales-all.js'
    ];

    /**
     * {@inheritDoc}
     */
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
