<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\View;

class Module extends \simialbi\yii2\base\Module
{
    const EVENT_BOARD_CREATED = 'boardCreated';
    const EVENT_BUCKET_CREATED = 'bucketCreated';
    const EVENT_TASK_CREATED = 'taskCreated';
    const EVENT_TASK_UPDATED = 'taskUpdated';
    const EVENT_TASK_ASSIGNED = 'taskAssigned';
    const EVENT_TASK_UNASSIGNED = 'taskUnassigned';
    const EVENT_TASK_STATUS_CHANGED = 'taskStatusChanged';
    const EVENT_TASK_COMPLETED = 'taskCompleted';
    const EVENT_CHECKLIST_CREATED = 'checklistCreated';
    const EVENT_COMMENT_CREATED = 'commentCreated';
    const EVENT_ATTACHMENT_ADDED = 'attachmentAdded';

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
     * @var array Colors for statuses
     */
    public $statusColors = [];

    /**
     * @var array User-cache
     */
    public $users = [];

    /**
     * @var array Role-cache
     */
    public $roles = [];

    /**
     * @var array|\Closure User groups
     */
    public $groups = [];

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();

        $identity = new Yii::$app->user->identityClass;
        if (!($identity instanceof UserInterface)) {
            throw new InvalidConfigException('The "identityClass" must extend "simialbi\yii2\models\UserInterface"');
        }
        if (!Yii::$app->hasModule('gridview')) {
            $this->setModule('gridview', [
                'class' => 'kartik\grid\Module',
                'exportEncryptSalt' => 'ror_HTbRh0Ad7K7DqhAtZOp50GKyia4c',
                'i18n' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@kvgrid/messages',
                    'forceTranslation' => true
                ]
            ]);
        }
        if (empty($this->statuses)) {
            $this->statuses = [
                Task::STATUS_NOT_BEGUN => Yii::t('simialbi/kanban/task', 'Not started'),
                Task::STATUS_IN_PROGRESS => Yii::t('simialbi/kanban/task', 'In progress'),
                Task::STATUS_DONE => Yii::t('simialbi/kanban/task', 'Done'),
                Task::STATUS_LATE => Yii::t('simialbi/kanban/task', 'Late')
            ];
        } else {
            if (!isset($this->statuses[Task::STATUS_NOT_BEGUN])) {
                $this->statuses[Task::STATUS_NOT_BEGUN] = Yii::t('simialbi/kanban/task', 'Not started');
            }
            if (!isset($this->statuses[Task::STATUS_DONE])) {
                $this->statuses[Task::STATUS_DONE] = Yii::t('simialbi/kanban/task', 'Done');
            }
            if (!isset($this->statuses[Task::STATUS_LATE])) {
                $this->statuses[Task::STATUS_LATE] = Yii::t('simialbi/kanban/task', 'Late');
            }
        }
        if (empty($this->statusColors)) {
            $this->statusColors = [
                Task::STATUS_NOT_BEGUN => '#c8c8c8',
                Task::STATUS_IN_PROGRESS => '#408ab7',
                Task::STATUS_DONE => '#64b564',
                Task::STATUS_LATE => '#d63867'
            ];
        }
        // TODO: Cache somehow
        $this->users = ArrayHelper::index(call_user_func([Yii::$app->user->identityClass, 'findIdentities']), 'id');
        if (Yii::$app->has('authManager')) {
            $this->roles = ArrayHelper::getColumn(
                ArrayHelper::index(Yii::$app->authManager->getRoles(), 'name'),
                'description'
            );
        }

        Yii::$app->view->registerJs(
            "var kanbanBaseUrl = '" . Url::to(['/' . $this->id], '') . "';",
            View::POS_HEAD
        );
    }

    /**
     * Get users boards
     * @param integer|null $userId Id of user to get boards for
     * @return array|Board[]|\yii\db\ActiveRecord[]
     */
    public static function getUserBoards($userId = null)
    {
        if (!$userId) {
            $userId = Yii::$app->user->id;
        }

        return Board::find()
            ->alias('b')
            ->with('buckets')
            ->innerJoinWith('assignments a')
            ->where(['{{b}}.[[is_public]]' => true])
            ->orWhere(['{{a}}.[[user_id]]' => $userId])
            ->indexBy('id')
            ->all();
    }

    /**
     * Sorts an array of tasks like this:
     * - End-date ASC,
     * - Subject ASC
     *
     * @param Task[] $tasks
     * @return void
     */
    public static function sortTasks(&$tasks)
    {
        usort($tasks, function ($a, $b) {
            /** @var \simialbi\yii2\kanban\models\Task $a */
            /** @var \simialbi\yii2\kanban\models\Task $b */
            if ($a->endDate === $b->endDate) {
                if ($a->end_date !== null) {
                    return 0;
                } else {
                    return strcasecmp($a->subject, $b->subject);
                }
            }
            if ($a->endDate === null && $b->endDate !== null) {
                return 1;
            }
            if ($a->endDate !== null && $b->endDate === null) {
                return -1;
            }
            return ($a->endDate < $b->endDate) ? -1 : 1;
        });
    }
}
