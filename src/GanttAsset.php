<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\web\AssetBundle;

class GanttAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $css = [
        'css/gantt.css'
    ];

    public $js = [
        'js/ganttDependencies.min.js',
        'js/ganttUtilities.js',
        'js/ganttTask.js',
        'js/ganttDrawerSVG.js',
        'js/ganttZoom.js',
        'js/ganttGridEditor.js',
        'js/ganttMaster.js'
    ];

    /**
     * {@inheritDoc}
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\jui\JuiAsset'
    ];
}
