<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use rmrevin\yii\fontawesome\FAR;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\bootstrap4\Html;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Trait RenderingTrait
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
trait RenderingTrait
{
    /**
     * Render delegated tasks "board" (it's virtual, does not really exists)
     *
     * @param string $view
     *
     * @return string
     */
    public function renderDelegatedTasks($view = 'task')
    {
        $query = new Query();
        $query->select([
            '{{t}}.*',
            '{{u}}.[[user_id]]'
        ])
            ->from(['t' => Task::tableName()])
            ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
            ->innerJoin(['b' => Bucket::tableName()], '{{b}}.[[id]] = {{t}}.[[bucket_id]]')
            ->innerJoin(['p' => Board::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->where(['{{t}}.[[created_by]]' => Yii::$app->user->id])
            ->andWhere(['not', ['{{u}}.[[user_id]]' => Yii::$app->user->id]]);

        $doneQuery = clone $query;
        $query->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
        $doneQuery->select([
            'cnt' => 'COUNT({{t}}.[[id]])',
            '{{u}}.[[user_id]]'
        ])->andWhere(['{{t}}.[[status]]' => Task::STATUS_DONE])->groupBy([
            '{{u}}.[[user_id]]'
        ])->indexBy('user_id');
        $doneTaskCounts = $doneQuery->column();

        $tasks = ArrayHelper::index($query->all(), null, 'user_id');

        foreach (array_keys($doneTaskCounts) as $userId) {
            if (!ArrayHelper::keyExists($userId, $tasks)) {
                $tasks[$userId] = [];
            }
        }

        $tasks = ArrayHelper::index($query->all(), null, 'user_id');

        if ($view === 'task') {
            return $this->renderPartial('/bucket/_group_assignee', [
                'model'           => null,
                'tasksByUser'     => $tasks,
                'doneTasksByUser' => $doneTaskCounts,
                'statuses'        => $this->module->statuses,
                'users'           => $this->module->users,
                'readonly'        => true
            ]);
        }

        $html = '';
        foreach ($tasks as $userId => $userTasks) {
            /* @var $user \simialbi\yii2\models\UserInterface */
            $user = ArrayHelper::getValue($this->module, ['users', $userId]);
            if ($user) {
                $html .= Html::beginTag('div', ['class' => 'card-header']);
                $html .= Html::beginTag('h4', ['class' => ['card-title', 'm-0']]);
                $html .= $this->renderPartial('/task/_user', [
                    'assigned' => false,
                    'user' => $user
                ]);
                $html .= Html::endTag('h4');
                $html .= Html::endTag('div');
            }
            $html .= Html::beginTag('div', ['class' => ['list-group', 'list-group-flush']]);
            foreach ($userTasks as $taskData) {
                $task = new Task();
                $task->setAttributes($taskData);
                $options = [
                    'class' => ['list-group-item', 'list-group-item-action'],
                    'href' => Url::to(['task/update', 'id' => $task->id]),
                    'data' => [
                        'pjax' => '0',
                        'toggle' => 'modal',
                        'target' => '#taskModal'
                    ]
                ];
                $content = Html::tag('h6', $task->subject, ['class' => ['m-0']]);
                $small = $task->board->name;
                if ($task->getChecklistElements()->count()) {
                    $small .= '&nbsp;&bull;&nbsp;' . $task->getChecklistStats();
                }
                if ($task->end_date) {
                    if ($task->end_date < time()) {
                        Html::addCssClass($options, 'list-group-item-danger');
                    }
                    $small .= '&nbsp;&bull;&nbsp;' . FAR::i('calendar') . ' ';
                    $small .= Yii::$app->formatter->asDate($task->end_date, 'short');
                }
                if ($task->getComments()->count()) {
                    $small .= '&nbsp;&bull;&nbsp;' . FAR::i('sticky-note');
                }
                $content .= Html::tag('small', $small);

                $html .= Html::tag('a', $content, $options);
            }
            $html .= Html::endTag('div');
        }

        return $html;
    }

    /**
     * Render bucket content by board and group
     *
     * @param Board $board
     * @param string $group
     * @param boolean $readonly
     * @return string rendered content
     */
    public function renderBucketContent($board, $group = 'bucket', $readonly = false)
    {
        $if = 'IF';
        if (Task::getDb()->driverName === 'mssql' || Task::getDb()->driverName === 'sqlsrv' ||
            Task::getDb()->driverName === 'dblib'
        ) {
            $if = 'IIF';
        }

        switch ($group) {
            case 'assignee':
                $query = new Query();
                $query->select([
                    '{{t}}.*',
                    '{{u}}.[[user_id]]'
                ])
                    ->from(['t' => Task::tableName()])
                    ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
                    ->innerJoin(['b' => Bucket::tableName()], '{{b}}.[[id]] = {{t}}.[[bucket_id]]')
                    ->innerJoin(['p' => Board::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
                    ->where(['{{p}}.[[id]]' => $board->id]);

                if ($readonly) {
                    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                }
                $doneQuery = clone $query;
                $query->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
                $doneQuery->select([
                    'cnt' => 'COUNT({{t}}.[[id]])',
                    '{{u}}.[[user_id]]'
                ])->andWhere(['{{t}}.[[status]]' => Task::STATUS_DONE])->groupBy([
                    '{{u}}.[[user_id]]'
                ])->indexBy('user_id');
                $doneTaskCounts = $doneQuery->column();

                $tasks = ArrayHelper::index($query->all(), null, 'user_id');

                foreach (array_keys($doneTaskCounts) as $userId) {
                    if (!ArrayHelper::keyExists($userId, $tasks)) {
                        $tasks[$userId] = [];
                    }
                }

                $bucketContent = $this->renderPartial('/bucket/_group_assignee', [
                    'model' => $board,
                    'tasksByUser' => $tasks,
                    'doneTasksByUser' => $doneTaskCounts,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly
                ]);
                break;

            case 'status':
                $tasks = ArrayHelper::index(
                    $board->getTasks()->orderBy(['status' => SORT_DESC])->all(),
                    null,
                    'status'
                );

                $bucketContent = $this->renderPartial('/bucket/_group_status', [
                    'model' => $board,
                    'tasksByStatus' => $tasks,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly
                ]);
                break;

            case 'date':
                $query = new Query();
                $query->select([
                    '{{t}}.*',
                    '{{t}}.[[end_date]]',
                    'is_done' => new Expression("$if({{t}}.[[status]] = 0, 1, 0)")
                ])
                    ->from(['t' => Task::tableName()])
                    ->innerJoin(['b' => Bucket::tableName()], '{{b}}.[[id]] = {{t}}.[[bucket_id]]')
                    ->innerJoin(['p' => Board::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
                    ->where(['{{p}}.[[id]]' => $board->id])
                    ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
                    ->orderBy(['{{t}}.[[end_date]]' => SORT_ASC]);
                $tasks = ArrayHelper::index($query->all(), null, ['end_date', 'is_done']);

                $bucketContent = $this->renderPartial('/bucket/_group_date', [
                    'model' => $board,
                    'tasksByDate' => $tasks,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly
                ]);
                break;

            case 'schedule':
                $bucketContent = $this->renderPartial('/bucket/_group_schedule', [
                    'model' => $board,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly
                ]);
                break;

            case 'bucket':
            default:
                $bucketContent = $this->renderPartial('/bucket/_group_bucket', [
                    'model' => $board,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly
                ]);
                break;
        }

        return $bucketContent;
    }

    /**
     * Render bucket content of completed tasks by board and group
     *
     * @param integer|null $bucketId
     * @param integer|null $userId
     * @param integer|null $date
     * @param boolean $readonly
     * @return string rendered content
     */
    public function renderCompleted($bucketId = null, $userId = null, $date = null, $readonly = false)
    {
        $query = Task::find()
            ->alias('t')
            ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
            ->where(['{{t}}.[[status]]' => Task::STATUS_DONE]);

        if ($bucketId) {
            $query->andWhere(['{{t}}.[[bucket_id]]' => $bucketId]);
        } elseif ($userId) {
            $query->andWhere(['{{u}}.[[user_id]]' => $userId]);
        } elseif ($date) {
            $query->andWhere(['{{t}}.[[end_date]]' => $date]);
        }

        if ($readonly) {
            $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
        }

        $content = '';
        foreach ($query->all() as $task) {
            $content .= $this->renderPartial('/task/item', [
                'model' => $task,
                'statuses' => $this->module->statuses,
                'users' => $this->module->users,
            ]);
        }

        return $content;
    }
}
