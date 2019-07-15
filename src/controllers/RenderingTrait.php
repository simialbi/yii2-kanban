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
use yii\db\Query;
use yii\helpers\ArrayHelper;

trait RenderingTrait
{
    /**
     * Render bucket content by board and group
     *
     * @param Board $board
     * @param string $group
     * @return string rendered content
     */
    public function renderBucketContent($board, $group = 'bucket')
    {
        switch ($group) {
            case 'assignee':
                $query = new Query();
                $query->select(['{{t}}.*', '{{u}}.[[user_id]]'])
                    ->from(['t' => Task::tableName()])
                    ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
                    ->innerJoin(['b' => Bucket::tableName()], '{{b}}.[[id]] = {{t}}.[[bucket_id]]')
                    ->innerJoin(['p' => Board::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
                    ->where(['{{p}}.[[id]]' => $board->id]);
//                    ->groupBy(['{{u}}.[[user_id]]', '{{t}}.[[id]]']);
                $tasks = ArrayHelper::index($query->all(), null, 'user_id');

                $bucketContent = $this->renderPartial('/bucket/_group_assignee', [
                    'model' => $board,
                    'tasksByUser' => $tasks,
                    'statuses' => $this->module->statuses
                ]);
                break;

            case 'status':
                $tasks = ArrayHelper::index($board->getTasks()->all(), null, 'status');

                $bucketContent = $this->renderPartial('/bucket/_group_status', [
                    'model' => $board,
                    'tasksByStatus' => $tasks,
                    'statuses' => $this->module->statuses
                ]);
                break;

            case 'date':
                $tasks = ArrayHelper::index(
                    $board->getTasks()->orderBy(['end_date' => SORT_ASC])->all(),
                    null,
                    'end_date'
                );

                $bucketContent = $this->renderPartial('/bucket/_group_date', [
                    'model' => $board,
                    'tasksByDate' => $tasks,
                    'statuses' => $this->module->statuses
                ]);
                break;

            case 'schedule':
                $bucketContent = $this->renderPartial('/bucket/_group_schedule', [
                    'model' => $board,
                    'statuses' => $this->module->statuses
                ]);
                break;

            case 'bucket':
            default:
                $bucketContent = $this->renderPartial('/bucket/_group_bucket', [
                    'model' => $board,
                    'statuses' => $this->module->statuses
                ]);
                break;
        }

        return $bucketContent;
    }
}
