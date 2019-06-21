<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\helpers\Url;
use yii\web\View;

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
     *
     * > Notice: At least "Not started" and "Done" must be defined and "Not started" must
     *   be mapped on key 10 and "Done" on key 0
     */
    public $statuses = [];

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
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
        } else {
            if (!isset($this->statuses[Task::STATUS_NOT_BEGUN])) {
                $this->statuses[Task::STATUS_NOT_BEGUN] = Yii::t('simialbi/kanban/task', 'Not started');
            }
            if (!isset($this->statuses[Task::STATUS_DONE])) {
                $this->statuses[Task::STATUS_DONE] = Yii::t('simialbi/kanban/task', 'Done');
            }
        }
        Yii::$app->assetManager->getBundle('yii\jui\JuiAsset')->js = [
            'ui/data.js',
            'ui/scroll-parent.js',
            'ui/widget.js',
            'ui/widgets/mouse.js',
            'ui/widgets/sortable.js'
        ];
        Yii::$app->view->registerJs(
            "var kanbanBaseUrl = '" . Url::to(['/' . $this->id], '') . "';",
            View::POS_HEAD
        );
    }
}
