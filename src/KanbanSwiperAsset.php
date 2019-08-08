<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;


use simialbi\yii2\web\AssetBundle;

class KanbanSwiperAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@bower/swiper/dist';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'css/swiper.min.css'
    ];

    /**
     * {@inheritDoc}
     */
    public $js = [
        'js/swiper.min.js'
    ];
}
