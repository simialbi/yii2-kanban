<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\View;

class Module extends \simialbi\yii2\base\Module implements BootstrapInterface
{
    public const EVENT_BOARD_CREATED = 'boardCreated';
    public const EVENT_BUCKET_CREATED = 'bucketCreated';
    public const EVENT_TASK_CREATED = 'taskCreated';
    public const EVENT_TASK_UPDATED = 'taskUpdated';
    public const EVENT_TASK_ASSIGNED = 'taskAssigned';
    public const EVENT_TASK_UNASSIGNED = 'taskUnassigned';
    public const EVENT_TASK_STATUS_CHANGED = 'taskStatusChanged';
    public const EVENT_TASK_COMPLETED = 'taskCompleted';
    public const EVENT_CHECKLIST_CREATED = 'checklistCreated';
    public const EVENT_COMMENT_CREATED = 'commentCreated';
    public const EVENT_ATTACHMENT_ADDED = 'attachmentAdded';

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
    public array $statuses = [];

    /**
     * @var array Colors for statuses
     */
    public array $statusColors = [];
    /**
     * @var array|\Closure User groups
     */
    public \Closure|array $groups = [];
    /**
     * @var string Path where uploaded attachments are stored. This folder will contain subfolders for each task-id
     */
    public string $uploadWebRoot = '@uploadwebroot/kanban';
    /**
     * @var string Webpath where uploaded attachments are stored. This folder will contain subfolders for each task-id
     */
    public string $uploadWeb = '@uploadweb/kanban';
    /**
     * @var array User-cache
     */
    protected array $users = [];
    /**
     * @var array Role-cache
     */
    protected array $roles = [];

    /**
     * Get users boards
     *
     * @param integer|null $userId Id of user to get boards for
     *
     * @return array|Board[]|ActiveRecord[]
     */
    public static function getUserBoards(?int $userId = null): array
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
     *
     * @return void
     */
    public static function sortTasks(array &$tasks): void
    {
        usort($tasks, function ($a, $b) {
            /** @var Task $a */
            /** @var Task $b */
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

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException|Exception
     */
    public function init(): void
    {
        parent::init();

        $this->registerTranslations();
        $this->registerEventListeners();

        FileHelper::createDirectory(Yii::getAlias($this->uploadWebRoot));

        $identity = Yii::createObject([
            'class' => Yii::$app->user->identityClass,
        ]);
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

        Yii::$app->view->registerJs(
            "var kanbanBaseUrl = '" . Url::to(['/' . $this->id], '') . "';",
            View::POS_HEAD
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bootstrap($app): void
    {
        if ($app instanceof Application) {
            $this->controllerNamespace = 'simialbi\yii2\kanban\commands';
            $this->defaultRoute = 'import';
        }
    }

    /**
     * Getter function of {@see $users}
     * @return array
     */
    public function getUsers(): array
    {
        if (empty($this->users)) {
            $this->users = ArrayHelper::index(Yii::$app->user->identityClass::findIdentities(), 'id');
        }

        return $this->users;
    }

    /**
     * Getter function of {@see $roles}
     * @return array
     */
    public function getRoles(): array
    {
        if (empty($this->roles) && Yii::$app->has('authManager')) {
            $this->roles = ArrayHelper::getColumn(
                ArrayHelper::index(Yii::$app->authManager->getRoles(), 'name'),
                'description'
            );
        }

        return $this->roles;
    }

    /**
     * Register various event-listeners
     * @return void
     */
    protected function registerEventListeners(): void
    {
        $this->on(self::EVENT_TASK_COMPLETED, function (TaskEvent $event): void {
            // set all children's status to done if parent is done
            foreach ($event->task->children as $child) {
                $child->status = Task::STATUS_DONE;

                // do not send notifications again if task was already completed
                if ($child->isAttributeChanged('status')) {
                    $child->save();
                    $this->trigger(Module::EVENT_TASK_STATUS_CHANGED, new TaskEvent([
                        'task' => $child,
                        'data' => Task::STATUS_DONE
                    ]));
                }

                $this->trigger(Module::EVENT_TASK_COMPLETED, new TaskEvent([
                    'task' => $child,
                    'data' => Task::STATUS_DONE
                ]));
            }
        });
    }
}
