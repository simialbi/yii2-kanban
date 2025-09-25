<?php
namespace simialbi\yii2\kanban;

use yii\web\AssetBundle;

/**
 * Class SortableAsset
 * *
 * @author Simon Karlen <karlen@tonic.ag>
 */
class SortableAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $css = [
        'css/sortable.css'
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\jui\JuiAsset'
    ];
}
