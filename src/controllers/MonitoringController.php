<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\MonitoringForm;
use simialbi\yii2\kanban\models\MonitoringList;
use simialbi\yii2\kanban\models\SearchMonitoringList;
use simialbi\yii2\kanban\models\SearchTask;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class MonitoringController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class MonitoringController extends Controller
{
    /**
     * {@inheritDoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'permissions' => ['monitorKanbanTasks']
                    ]
                ]
            ]
        ];
    }

    /**
     * List monitoring lists
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SearchMonitoringList();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Yii::$app->user->id);

        return $this->renderAjax('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'users' => ArrayHelper::getColumn($this->module->users, 'name', true)
        ]);
    }

    /**
     * Create a new list
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new MonitoringForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveList()) {
            return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
        }

        return $this->renderAjax('upsert', [
            'model' => $model,
            'users' => ArrayHelper::getColumn($this->module->users, 'name', true)
        ]);
    }

    /**
     * Update an existing list
     * @param integer|string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $list = $this->findModel($id);
        $model = new MonitoringForm([
            'id' => $list->id,
            'name' => $list->name,
            'members' => ArrayHelper::getColumn($list->members, 'id')
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveList()) {
            return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
        }

        return $this->renderAjax('upsert', [
            'model' => $model,
            'users' => ArrayHelper::getColumn($this->module->users, 'name', true)
        ]);
    }

    /**
     * Delete a list
     * @param integer $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $list = $this->findModel($id);

        $list->delete();

        return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
    }

    /**
     * View monitoring list details
     * @param integer $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $list = $this->findModel($id);

        $allUsers = $this->module->users;
        $userIds = ArrayHelper::getColumn($list->members, 'user_id');

        $query = new Query();
        $query->select([
            'value' => new Expression('COUNT({{t}}.[[id]])'),
            '{{t}}.[[status]]'
        ])
            ->from(['p' => Board::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->innerJoin(['ua' => TaskUserAssignment::tableName()], '{{t}}.[[id]] = {{ua}}.[[task_id]]')
            ->groupBy(['{{t}}.[[status]]'])
            ->where(['{{ua}}.[[user_id]]' => $userIds])
            ->andWhere([
                'or',
                ['{{t}}.[[end_date]]' => null],
                ['>=', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')],
                ['{{t}}.[[status]]' => Task::STATUS_DONE]
            ]);
        $query2 = clone $query;
        $query2
            ->select([
                'value' => new Expression('COUNT({{t}}.[[id]])'),
                'status' => new Expression('15')
            ])
            ->where(['{{ua}}.[[user_id]]' => ArrayHelper::getColumn($list->members, 'user_id')])
            ->andWhere(['not', ['{{t}}.[[end_date]]' => null]])
            ->andWhere(['<', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')])
            ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
        $query->union($query2);
        $byStatus = $query->all();
        foreach ($byStatus as &$item) {
            $item['color'] = ArrayHelper::getValue($this->module->statusColors, $item['status']);
            $item['status'] = ArrayHelper::getValue($this->module->statuses, $item['status'], $item['status']);
        }

        $query = new Query();
        $query->select([
            'value' => new Expression('COUNT({{t}}.[[id]])'),
            'board' => '{{p}}.[[name]]',
            '{{t}}.[[status]]'
        ])
            ->from(['p' => Board::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->innerJoin(['ua' => TaskUserAssignment::tableName()], '{{t}}.[[id]] = {{ua}}.[[task_id]]')
            ->groupBy(['{{b}}.[[id]]', '{{b}}.[[name]]', '{{t}}.[[status]]'])
            ->where(['{{ua}}.[[user_id]]' => $userIds])
            ->andWhere([
                'or',
                ['{{t}}.[[end_date]]' => null],
                ['>=', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')],
                ['{{t}}.[[status]]' => Task::STATUS_DONE]
            ]);
        $query2 = clone $query;
        $query2
            ->select([
                'value' => new Expression('COUNT({{t}}.[[id]])'),
                'board' => '{{p}}.[[name]]',
                'status' => new Expression('15')
            ])
            ->where(['{{ua}}.[[user_id]]' => ArrayHelper::getColumn($list->members, 'user_id')])
            ->andWhere(['not', ['{{t}}.[[end_date]]' => null]])
            ->andWhere(['<', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')])
            ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
        $query->union($query2);
        $rows = $query->all();
        $byBoard = [];
        foreach ($rows as $row) {
            ArrayHelper::setValue($byBoard, [$row['board'], 'board'], $row['board']);
            ArrayHelper::setValue($byBoard, [$row['board'], 'status_' . $row['status']], $row['value']);
        }
        $byBoard = array_values($byBoard);

        $query = new Query();
        $query->select([
            'value' => new Expression('COUNT({{t}}.[[id]])'),
            '{{ua}}.[[user_id]]',
            '{{t}}.[[status]]'
        ])
            ->from(['p' => Board::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->innerJoin(['ua' => TaskUserAssignment::tableName()], '{{t}}.[[id]] = {{ua}}.[[task_id]]')
            ->groupBy(['{{ua}}.[[user_id]]', '{{t}}.[[status]]'])
            ->where(['{{ua}}.[[user_id]]' => $userIds])
            ->andWhere([
                'or',
                ['{{t}}.[[end_date]]' => null],
                ['>=', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')],
                ['{{t}}.[[status]]' => Task::STATUS_DONE]
            ]);
        $query2 = clone $query;
        $query2
            ->select([
                'value' => new Expression('COUNT({{t}}.[[id]])'),
                '{{ua}}.[[user_id]]',
                'status' => new Expression('15')
            ])
            ->where(['{{ua}}.[[user_id]]' => ArrayHelper::getColumn($list->members, 'user_id')])
            ->andWhere(['not', ['{{t}}.[[end_date]]' => null]])
            ->andWhere(['<', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')])
            ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
        $query->union($query2);
        $rows = $query->all();
        $byAssignee = [];
        foreach ($rows as $row) {
            $userId = $row['user_id'] ?: '0';
            $userName = ($row['user_id'])
                ? ArrayHelper::getValue(
                    ArrayHelper::getValue($allUsers, $row['user_id'], []),
                    'name',
                    Yii::t('simialbi/kanban', 'Not assigned')
                )
                : Yii::t('simialbi/kanban', 'Not assigned');
            ArrayHelper::setValue($byAssignee, [$userId, 'user'], $userName);
            ArrayHelper::setValue($byAssignee, [$userId, 'status_' . $row['status']], $row['value']);
        }
        $byAssignee = array_values($byAssignee);

        $searchModel = new SearchTask();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $userIds);

        return $this->render('view', [
            'model' => $list,
            'boards' => ArrayHelper::map(Board::findByUserId(), 'id', 'name'),
            'users' => $allUsers,
            'statuses' => $this->module->statuses,
            'byStatus' => $byStatus,
            'byBoard' => $byBoard,
            'byAssignee' => $byAssignee,
            'colors' => $this->module->statusColors,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Export list as csv
     * @param integer $id
     * @return false|string
     *
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function actionCsv($id)
    {
        $list = $this->findModel($id);

        $csv = fopen('php://temp/maxmemory:20971520', 'r+');
        fputcsv($csv, [
            $list->getAttributeLabel('name'),
            $list->name,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ], ';');
        fputcsv($csv, [
            Yii::t('simialbi/kanban/monitoring', 'Export date'),
            Yii::$app->formatter->asDate('today'),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ], ';');
        fputcsv($csv, [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ], ';');
        $qry = new Query();
        $qry->select([
            '{{t}}.[[id]]',
            'board' => '{{p}}.[[name]]',
            'bucket' => '{{b}}.[[name]]',
            '{{t}}.[[subject]]',
            '{{t}}.[[description]]',
            '{{t}}.[[status]]',
            '{{t}}.[[start_date]]',
            '{{t}}.[[end_date]]',
            '{{t}}.[[created_at]]',
            'assignees' => new Expression('GROUP_CONCAT({{ua}}.[[user_id]])'),
            '{{t}}.[[created_by]]',
            '{{t}}.[[finished_at]]',
            '{{t}}.[[finished_by]]'
        ])
            ->distinct(true)
            ->from(['t' => Task::tableName()])
            ->innerJoin(['ua' => TaskUserAssignment::tableName()], '{{t}}.[[id]] = {{ua}}.[[task_id]]')
            ->innerJoin(['b' => Bucket::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->innerJoin(['p' => Board::tableName()], '{{b}}.[[board_id]] = {{p}}.[[id]]')
            ->where(['{{ua}}.[[user_id]]' => ArrayHelper::getColumn($list->members, 'user_id')])
            ->groupBy(['{{t}}.[[id]]']);
        fputcsv($csv, [
            Yii::t('simialbi/kanban/model/task', 'Id'),
            Yii::t('simialbi/kanban/model/bucket', 'Board'),
            Yii::t('simialbi/kanban/model/task', 'Bucket'),
            Yii::t('simialbi/kanban/model/task', 'Subject'),
            Yii::t('simialbi/kanban/model/task', 'Status'),
            Yii::t('simialbi/kanban/plan', 'Assigned to'),
            Yii::t('simialbi/kanban/model/task', 'Created by'),
            Yii::t('simialbi/kanban/model/task', 'Created at'),
            Yii::t('simialbi/kanban/model/task', 'Start date'),
            Yii::t('simialbi/kanban/model/task', 'End date'),
            Yii::t('simialbi/kanban/task', 'Late'),
            Yii::t('simialbi/kanban/model/task', 'Finished at'),
            Yii::t('simialbi/kanban/model/task', 'Finished by'),
            Yii::t('simialbi/kanban/model/task', 'Description')
        ], ';');
        foreach ($qry->all() as $row) {
            $assignees = [];
            $row['assignees'] = explode(',', $row['assignees']);
            foreach ($row['assignees'] as $assignee) {
                if (false !== ($assignee = ArrayHelper::getValue($this->module->users, $assignee, false))) {
                    $assignees[] = $assignee->name;
                }
            }
            fputcsv($csv, [
                $row['id'],
                $row['board'],
                $row['bucket'],
                $row['subject'],
                ArrayHelper::getValue($this->module->statuses, $row['status'], Yii::t('yii', '(not set)')),
                implode(', ', $assignees),
                ArrayHelper::getValue($this->module->users, [$row['created_by'], 'name'], Yii::t('yii', '(not set)')),
                Yii::$app->formatter->asDate($row['created_at']),
                empty($row['start_date']) ? '' : Yii::$app->formatter->asDate($row['start_date']),
                empty($row['end_date']) ? '' : Yii::$app->formatter->asDate($row['end_date']),
                (!empty($row['end_date']) && $row['end_date'] < strtotime('today')) ? '1' : '0',
                empty($row['finished_at']) ? '' : Yii::$app->formatter->asDate($row['finished_at']),
                ArrayHelper::getValue($this->module->users, [$row['finished_by'], 'name'], ''),
                strip_tags(preg_replace('/<br ?\/?>/i', "\n", $row['description']))
            ], ';');
        }
        rewind($csv);
        $output = stream_get_contents($csv);
        if (function_exists('mb_convert_encoding')) {
            $output = mb_convert_encoding($output, 'windows-1252', Yii::$app->charset ?: 'UTF-8');
        }
        fclose($csv);

        Yii::$app->response->setDownloadHeaders(
            'list_' . Inflector::slug($list->name) . '_' . Yii::$app->formatter->asDate('now', 'yyyyMMddHHmm') . '.csv',
            'text/csv',
            false,
            StringHelper::byteLength($output)
        );

        return $output;
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return MonitoringList the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = MonitoringList::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
