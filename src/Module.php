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
     */
    public $statuses = [];

    /**
     * @var string|null Set the user identity field containing the user name
     */
    public $userNameField;

    /**
     * @var string|null Set the user identity field containing the user's profile image
     */
    public $userImageField;

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
