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
    public $sourcePath = '@bower/fullcalendar/dist/core';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'main.css'
    ];

    /**
     * {@inheritDoc}
     */
    public $js = [
        'main.js',
        'locales-all.js'
    ];

    /**
     * {@inheritDoc}
     */
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
