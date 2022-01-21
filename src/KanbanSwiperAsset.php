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
    public $sourcePath = '@npm/swiper';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'swiper-bundle.min.css'
    ];

    /**
     * {@inheritDoc}
     */
    public $js = [
        'swiper-bundle.min.js'
    ];
}
