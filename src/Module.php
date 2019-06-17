<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\models\Task;
use Yii;

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
     * @var array Different progress possibilities
     */
    public $statuses = [];

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();

        if (empty($this->statuses)) {
            $this->statuses = [
                Task::STATUS_NOT_BEGUN => Yii::t('simialbi/kanban/task', 'Not started'),
                Task::STATUS_IN_PROGRESS => Yii::t('simialbi/kanban/task', 'In progress'),
                Task::STATUS_DONE => Yii::t('simialbi/kanban/task', 'Done')
            ];
        }
    }
}
