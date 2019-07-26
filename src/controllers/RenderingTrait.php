<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

trait RenderingTrait
{
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
                    '{{u}}.[[user_id]]',
                    'is_done' => new Expression("$if({{t}}.[[status]] = 0, 1, 0)")
                ])
                    ->from(['t' => Task::tableName()])
                    ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
                    ->innerJoin(['b' => Bucket::tableName()], '{{b}}.[[id]] = {{t}}.[[bucket_id]]')
                    ->innerJoin(['p' => Board::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
                    ->where(['{{p}}.[[id]]' => $board->id]);

                if ($readonly) {
                    $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                }

                $tasks = ArrayHelper::index($query->all(), null, ['user_id', 'is_done']);

                $bucketContent = $this->renderPartial('/bucket/_group_assignee', [
                    'model' => $board,
                    'tasksByUser' => $tasks,
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
}
