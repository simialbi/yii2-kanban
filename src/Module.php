<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

class Module extends \simialbi\yii2\base\Module
{
    /**
     * {@inheritDoc}
     */
    public $controllerNamespace = 'simialbi\yii2\kanban\controllers';

    /**
     * {@inheritDoc}
     */
    public $defaultRoute = 'plan';

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();
    }
}
