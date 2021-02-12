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
use yii\caching\TagDependency;
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
                'model' => null,
                'tasksByUser' => $tasks,
                'doneTasksByUser' => $doneTaskCounts,
                'statuses' => $this->module->statuses,
                'users' => $this->module->users,
                'readonly' => true,
                'isFiltered' => false
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
     * @param array $filters
     * @return string rendered content
     */
    public function renderBucketContent($board, $group = 'bucket', $readonly = false, $filters = [])
    {
//        $if = 'IF';
//        if (Task::getDb()->driverName === 'mssql' || Task::getDb()->driverName === 'sqlsrv' ||
//            Task::getDb()->driverName === 'dblib'
//        ) {
//            $if = 'IIF';
//        }

        switch ($group) {
            case 'assignee':
                $query = $board->getTasks()
                    ->alias('t')
                    ->select(['{{t}}.*', '{{u}}.[[user_id]]'])
                    ->distinct(true)
                    ->joinWith('assignments u')
                    ->joinWith('checklistElements')
                    ->joinWith('comments co')
                    ->innerJoinWith('bucket b')
                    ->with(['attachments', 'links'])
                    ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
                    ->andFilterWhere($filters)
                    ->asArray(true);

                if (!empty($filters)) {
                    $doneQuery = clone $query;
                    $completedTasks = ArrayHelper::index(
                        $doneQuery->where(['{{t}}.[[status]]' => Task::STATUS_DONE])->all(),
                        null,
                        'user_id'
                    );
                } else {
                    $completedTasks = $board->getTasks()
                        ->cache(60, Yii::createObject([
                            'class' => TagDependency::class,
                            'tags' => md5(serialize($filters))
                        ]))
                        ->alias('t')
                        ->select([
                            'cnt' => 'COUNT({{t}}.[[id]])',
                            'user_id'
                        ])
                        ->joinWith('assignments u')
                        ->innerJoinWith('bucket b')
                        ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
                        ->groupBy('user_id')
                        ->indexBy('user_id')
                        ->andFilterWhere($filters)
                        ->column();
                }
                if ($readonly) {
                    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                }
                $tasks = ArrayHelper::index($query->all(), null, 'user_id');

                foreach (array_keys($completedTasks) as $userId) {
                    if (!ArrayHelper::keyExists($userId, $tasks)) {
                        $tasks[$userId] = [];
                    }
                }

                $bucketContent = $this->renderPartial('/bucket/_group_assignee', [
                    'model' => $board,
                    'tasksByUser' => $tasks,
                    'doneTasksByUser' => $completedTasks,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly,
                    'isFiltered' => !empty($filters)
                ]);
                break;

            case 'status':
                $query = $board->getTasks()
                    ->alias('t')
                    ->joinWith('assignments u')
                    ->joinWith('checklistElements')
                    ->joinWith('comments co')
                    ->innerJoinWith('bucket b')
                    ->with(['attachments', 'links'])
                    ->andFilterWhere($filters)
                    ->orderBy(['status' => SORT_DESC]);
                if ($readonly) {
                    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                }

                $bucketContent = $this->renderPartial('/bucket/_group_status', [
                    'model' => $board,
                    'tasksByStatus' => ArrayHelper::index($query->all(), null, 'status'),
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly,
                    'isFiltered' => !empty($filters)
                ]);
                break;

            case 'date':
                $query = $board->getTasks()
                    ->alias('t')
                    ->select([
                        '{{t}}.*',
                        'end_date' => new Expression('DATE_FORMAT(FROM_UNIXTIME({{t}}.[[end_date]]), \'%Y-%m-%d\')')
                    ])
                    ->distinct(true)
                    ->joinWith('assignments u')
                    ->joinWith('checklistElements')
                    ->joinWith('comments co')
                    ->innerJoinWith('bucket b')
                    ->with(['attachments', 'links'])
                    ->andFilterWhere($filters)
                    ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
                    ->orderBy(['{{t}}.[[end_date]]' => SORT_ASC])
                    ->asArray(true);

                if (!empty($filters)) {
                    $doneQuery = clone $query;
                    $completedTasks = ArrayHelper::index(
                        $doneQuery->where(['{{t}}.[[status]]' => Task::STATUS_DONE])->all(),
                        null,
                        'end_date'
                    );
                } else {
                    $completedTasks = $board->getTasks()
                        ->cache(60, Yii::createObject([
                            'class' => TagDependency::class,
                            'tags' => md5(serialize($filters))
                        ]))
                        ->alias('t')
                        ->select([
                            'cnt' => 'COUNT({{t}}.[[id]])',
                            'end_date' => new Expression('DATE_FORMAT(FROM_UNIXTIME({{t}}.[[end_date]]), \'%Y-%m-%d\')')
                        ])
                        ->joinWith('assignments u')
                        ->innerJoinWith('bucket b')
                        ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
                        ->groupBy('user_id')
                        ->indexBy('end_date')
                        ->andFilterWhere($filters)
                        ->column();
                }
                if ($readonly) {
                    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                }
                $tasks = ArrayHelper::index($query->all(), null, 'end_date');

                foreach (array_keys($completedTasks) as $date) {
                    if (!ArrayHelper::keyExists($date, $tasks)) {
                        $tasks[$date] = [];
                    }
                }
                if (isset($completedTasks[''])) {
                    $completedTasks[Yii::$app->formatter->asDate(null)] = $completedTasks[''];
                    unset($completedTasks['']);
                }

                $bucketContent = $this->renderPartial('/bucket/_group_date', [
                    'model' => $board,
                    'tasksByDate' => $tasks,
                    'doneTasksByDate' => $completedTasks,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly,
                    'isFiltered' => !empty($filters)
                ]);
                break;

            case 'schedule':
                $bucketContent = $this->renderPartial('/bucket/_group_schedule', [
                    'model' => $board,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly,
                    'isFiltered' => !empty($filters)
                ]);
                break;

            case 'bucket':
            default:
                $query = $board->getTasks()
                    ->alias('t')
                    ->joinWith('assignments u')
                    ->joinWith('checklistElements')
                    ->joinWith('comments co')
                    ->innerJoinWith('bucket b')
                    ->with(['attachments', 'links'])
                    ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
                    ->andFilterWhere($filters)
                    ->orderBy(['{{t}}.[[status]]' => SORT_DESC]);

                if (!empty($filters)) {
                    $doneQuery = clone $query;
                    $completedTasks = ArrayHelper::index(
                        $doneQuery->where(['{{t}}.[[status]]' => Task::STATUS_DONE])->all(),
                        null,
                        'bucket_id'
                    );
                } else {
                    $completedTasks = $board->getTasks()
                        ->cache(60, Yii::createObject([
                            'class' => TagDependency::class,
                            'tags' => md5(serialize($filters))
                        ]))
                        ->alias('t')
                        ->select([
                            'cnt' => 'COUNT({{t}}.[[id]])',
                            'bucket_id'
                        ])
                        ->innerJoinWith('bucket b')
                        ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
                        ->groupBy('bucket_id')
                        ->indexBy('bucket_id')
                        ->andFilterWhere($filters)
                        ->column();
                }
                if ($readonly) {
                    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                }
                $tasks = ArrayHelper::index($query->all(), null, 'bucket_id');

                $bucketContent = $this->renderPartial('/bucket/_group_bucket', [
                    'model' => $board,
                    'tasks' => $tasks,
                    'completedTasks' => $completedTasks,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'readonly' => $readonly,
                    'isFiltered' => !empty($filters)
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
     * @param array $filters
     * @return string rendered content
     */
    public function renderCompleted($bucketId = null, $userId = null, $date = null, $readonly = false, $filters = [])
    {
        $query = Task::find()
            ->alias('t')
            ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
            ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
            ->andFilterWhere($filters);

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
