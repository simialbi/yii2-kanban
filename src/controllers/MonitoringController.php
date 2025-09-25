<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
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
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\Query;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
    public function behaviors(): array
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
     * @throws \Exception
     */
    public function actionIndex(): string
    {
        $searchModel = new SearchMonitoringList();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Yii::$app->user->id);

        return $this->renderAjax('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'users' => ArrayHelper::getColumn($this->module->users, 'name')
        ]);
    }

    /**
     * Create a new list
     * @return string|Response
     * @throws Exception
     */
    public function actionCreate(): Response|string
    {
        $model = new MonitoringForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveList()) {
            return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
        }

        return $this->renderAjax('upsert', [
            'model' => $model,
            'users' => ArrayHelper::getColumn($this->module->users, 'name')
        ]);
    }

    /**
     * Update an existing list
     *
     * @param int $id
     *
     * @return string|Response
     * @throws NotFoundHttpException|Exception
     */
    public function actionUpdate(int $id): Response|string
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
            'users' => ArrayHelper::getColumn($this->module->users, 'name')
        ]);
    }

    /**
     * Delete a list
     *
     * @param int $id
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete(int $id): Response
    {
        $list = $this->findModel($id);

        $list->delete();

        return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
    }

    /**
     * View monitoring list details
     *
     * @param int $id
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionView(int $id): string
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
     * Export list as excel file
     *
     * @param int $id
     *
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function actionCsv(int $id): void
    {
        $list = $this->findModel($id);

        $sp = new Spreadsheet();
        $as = $sp->getActiveSheet();

        $as
            ->fromArray([
                $list->getAttributeLabel('name'),
                $list->name
            ])
            ->fromArray([
                Yii::t('simialbi/kanban/monitoring', 'Export date'),
                Yii::$app->formatter->asDate('today'),
            ], startCell: 'A2')
            ->fromArray([
                Yii::t('simialbi/kanban/model/task', 'Id'),
                Yii::t('simialbi/kanban/model/bucket', 'Board'),
                Yii::t('simialbi/kanban/model/task', 'Bucket'),
                Yii::t('simialbi/kanban/model/task', 'Subject'),
                Yii::t('simialbi/kanban/model/task', 'Responsible'),
                Yii::t('simialbi/kanban/model/task', 'Status'),
                Yii::t('simialbi/kanban/plan', 'Assigned to'),
                Yii::t('simialbi/kanban/model/task', 'Created by'),
                Yii::t('simialbi/kanban/model/task', 'Created at'),
                Yii::t('simialbi/kanban/model/task', 'Updated by'),
                Yii::t('simialbi/kanban/model/task', 'Updated at'),
                Yii::t('simialbi/kanban/model/task', 'Start date'),
                Yii::t('simialbi/kanban/model/task', 'End date'),
                Yii::t('simialbi/kanban/task', 'Late'),
                Yii::t('simialbi/kanban/model/task', 'Finished at'),
                Yii::t('simialbi/kanban/model/task', 'Finished by'),
                Yii::t('simialbi/kanban/model/task', 'Description')
            ], startCell: 'A4');


        $userIds = ArrayHelper::getColumn($list->members, 'user_id');

        $searchModel = new SearchTask();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $userIds);
        $dataProvider->pagination = false;

        $startCell = 5;
        foreach ($dataProvider->getModels() as $task) {
            /** @var Task $task */
            $as->fromArray([
                $task->id,
                $task->board->name,
                $task->bucket->name,
                $task->subject,
                $task->responsible?->name ?? '',
                ArrayHelper::getValue($this->module->statuses, $task->status, Yii::t('yii', '(not set)')),
                implode(', ', ArrayHelper::getColumn($task->assignees, 'name')),
                $task->author?->name ?? Yii::t('yii', '(not set)'),
                Yii::$app->formatter->asDate($task->created_at),
                $task->updater?->name ?? Yii::t('yii', '(not set)'),
                Yii::$app->formatter->asDate($task->updated_at),
                $task->start_date ? Yii::$app->formatter->asDate($task->start_date) : '',
                $task->end_date ? Yii::$app->formatter->asDate($task->end_date) : '',
                ($task->end_date && $task->end_date < strtotime('today')) ? '1' : '0',
                $task->finisher?->name ?? '',
                $task->finished_at ? Yii::$app->formatter->asDate($task->finished_at) : '',
                strip_tags(preg_replace('/<br ?\/?>/i', "\n", $task->description))
            ], '', 'A' . $startCell);
            $startCell++;
        }

        // set as Table
        $start = "A4";
        $end = $as->getHighestRowAndColumn();
        $table = new Table($start . ':' . $end['column'] . $end['row']);
        $as->addTable($table);

        $text = "&C" . Yii::t('hq-re/general/pdf', 'internal') . "&R&D";
        $as->getHeaderFooter()->setOddFooter($text);

        // Download
        $filename = 'list_' . Inflector::slug($list->name) . '_' . Yii::$app->formatter->asDate('now', 'yyyyMMddHHmm') . '.xlsx';
        Yii::$app->response->setDownloadHeaders(
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        )->send();

        $writer = IOFactory::createWriter($sp, IOFactory::WRITER_XLSX);
        $writer->save('php://output');

        exit;
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param mixed $condition
     *
     * @return MonitoringList the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(mixed $condition): MonitoringList
    {
        if (($model = MonitoringList::findOne($condition)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
