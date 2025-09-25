<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\BoardEvent;
use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\BoardUserAssignment;
use simialbi\yii2\kanban\models\BoardUserSetting;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\Query;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class PlanController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class PlanController extends Controller
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
                        'actions' => ['create', 'assign-user', 'expel-user', 'toggle-hidden-boards', 'toggle-board-visibility'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'delete'],
                        'matchCallback' => function (): bool {
                            $board = $this->findModel(Yii::$app->request->getQueryParam('id'));

                            return $board->created_by == Yii::$app->user->id;
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'schedule', 'chart']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'matchCallback' => function (): bool {
                            return ArrayHelper::keyExists(
                                Yii::$app->request->getQueryParam('id'),
                                ArrayHelper::index(Board::findByUserId(Yii::$app->user->id), 'id')
                            );
                        }
                    ]
                ]
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'search-tasks' => ['POST'],
                    'delete' => ['POST']
                ]
            ]
        ];
    }

    /**
     * Plan overview
     *
     * @param string $activeTab One of 'plan', 'tasks', 'delegated', 'responsible'
     *
     * @return string
     */
    public function actionIndex(string $activeTab = 'plan'): string
    {
        $filters = [
            '{{s}}.[[is_hidden]]' => [false, null]
        ];
        if (Yii::$app->session->get('kanban.plan.showHiddenBoards', false)) {
            $filters = [];
        }

        $hiddenBoards = Board::findByUserId(filters: [
            '{{s}}.[[is_hidden]]' => [true]
        ]);
        $boards = Board::findByUserId(filters: $filters);

        return $this->render('index', [
            'boards' => $boards,
            'activeTab' => $activeTab,
            'hiddenCnt' => count($hiddenBoards)
        ]);
    }

    /**
     * Show board
     *
     * @param int $id
     * @param string $group
     * @param int|null $showTask
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id, string $group = 'bucket', ?int $showTask = null): string
    {
        $model = $this->findModel($id);
        $readonly = !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count();

        return $this->render('view', [
            'boards' => Board::findByUserId(filters: [
                '{{s}}.[[is_hidden]]' => [false, null]
            ]),
            'model' => $model,
            'readonly' => $readonly,
            'group' => $group,
            'users' => $this->module->users,
            'showTask' => $showTask
        ]);
    }

    /**
     * Schedule view
     *
     * @param int $id
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionSchedule(int $id): string
    {
        $model = $this->findModel($id);
        $readonly = !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count();

        $taskQuery = $model->getTasks()
            ->where(['not', ['start_date' => null]])
            ->orWhere(['not', ['end_date' => null]]);
        /* @var $tasks Task[] */

        if ($readonly) {
            $taskQuery->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
        }

        $calendarTasks = [];
        foreach ($taskQuery->all() as $task) {
            /* @var $task Task */
            $startDate = (empty($task->start_date))
                ? Yii::$app->formatter->asDatetime($task->end_date, 'php:c')
                : Yii::$app->formatter->asDatetime($task->start_date, 'php:c');
            $endDate = (empty($task->end_date))
                ? Yii::$app->formatter->asDatetime($task->start_date, 'php:c')
                : Yii::$app->formatter->asDatetime($task->end_date, 'php:c');

            $calendarTask = [
                'id' => $task->id,
                'title' => $task->subject,
                'start' => $startDate,
                'end' => $endDate,
                'allDay' => true,
                'classNames' => ['border-0'],
                'url' => Url::to(['task/update', 'id' => $task->id])
            ];

            if (strtotime($endDate) < time()) {
                $calendarTask['classNames'] = ['border-0', 'bg-danger'];
            }
            if ($task->status === Task::STATUS_DONE) {
                $calendarTask['classNames'] = ['border-0', 'bg-success'];
            }
            if ($task->status !== Task::STATUS_NOT_BEGUN && $task->status !== Task::STATUS_DONE) {
                $calendarTask['classNames'] = ['border-0', 'bg-dark'];
            }

            $calendarTasks[] = $calendarTask;
        }

        return $this->render('schedule', [
            'model' => $model,
            'otherTasks' => $model->getTasks()
                ->where([
                    'start_date' => null,
                    'end_date' => null
                ])
                ->with(['bucket'])
                ->orderBy(['bucket_id' => SORT_ASC])
                ->andWhere(['not', ['status' => Task::STATUS_DONE]])
                ->all(),
            'calendarTasks' => $calendarTasks,
            'users' => $this->module->users,
            'statuses' => $this->module->statuses,
            'readonly' => $readonly
        ]);
    }

    /**
     * Chart view
     *
     * @param int $id
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionChart(int $id): string
    {
        $model = $this->findModel($id);
        $readonly = !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count();

        $allUsers = $this->module->users;

        $query = new Query();
        $query->select([
            'value' => new Expression('COUNT({{t}}.[[id]])'),
            '{{t}}.[[status]]'
        ])
            ->from(['p' => $model::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->groupBy(['{{t}}.[[status]]'])
            ->where(['{{p}}.[[id]]' => $id])
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
            ->where(['{{p}}.[[id]]' => $id])
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
            'bucket' => '{{b}}.[[name]]',
            '{{t}}.[[status]]',
            '{{b}}.[[sort]]'
        ])
            ->from(['p' => $model::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->groupBy(['{{b}}.[[id]]', '{{b}}.[[name]]', '{{t}}.[[status]]'])
            ->where(['{{p}}.[[id]]' => $id])
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
                'bucket' => '{{b}}.[[name]]',
                'status' => new Expression('15'),
                '{{b}}.[[sort]]'
            ])
            ->where(['{{p}}.[[id]]' => $id])
            ->andWhere(['not', ['{{t}}.[[end_date]]' => null]])
            ->andWhere(['<', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')])
            ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
        $query = (new Query())
            ->from($query->union($query2))
            ->orderBy([
                '[[sort]]' => SORT_ASC
            ]);
        $rows = $query->all();
        $byBucket = [];
        foreach ($rows as $row) {
            ArrayHelper::setValue($byBucket, [$row['bucket'], 'bucket'], $row['bucket']);
            ArrayHelper::setValue($byBucket, [$row['bucket'], 'status_' . $row['status']], $row['value']);
        }
        $byBucket = array_values($byBucket);

        $query = new Query();
        $query->select([
            'value' => new Expression('COUNT({{t}}.[[id]])'),
            '{{u}}.[[user_id]]',
            '{{t}}.[[status]]'
        ])
            ->from(['p' => $model::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->leftJoin(['u' => TaskUserAssignment::tableName()], '{{u}}.[[task_id]] = {{t}}.[[id]]')
            ->groupBy(['{{u}}.[[user_id]]', '{{t}}.[[status]]'])
            ->where(['{{p}}.[[id]]' => $id])
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
                '{{u}}.[[user_id]]',
                'status' => new Expression('15')
            ])
            ->where(['{{p}}.[[id]]' => $id])
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

        return $this->render('chart', [
            'model' => $model,
            'users' => $this->module->users,
            'statuses' => $this->module->statuses,
            'byStatus' => $byStatus,
            'byBucket' => $byBucket,
            'byAssignee' => $byAssignee,
            'colors' => $this->module->statusColors,
            'readonly' => $readonly
        ]);
    }

    /**
     * Create new board
     * @return string|Response
     * @throws Exception
     */
    public function actionCreate(): Response|string
    {
        $model = new Board([
            'is_public' => false
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->saveImage($model);

            Yii::$app->session->addFlash('success', Yii::t(
                'simialbi/kanban/plan/notification',
                'Board <b>{board}</b> created',
                ['board' => $model->name]
            ));

            $this->module->trigger(Module::EVENT_BOARD_CREATED, new BoardEvent([
                'board' => $model
            ]));

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     * Update a board
     *
     * @param int $id
     *
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->saveImage($model);

            Yii::$app->session->addFlash('success', Yii::t(
                'simialbi/kanban/plan/notification',
                'Board <b>{board}</b> updated',
                ['board' => $model->name]
            ));

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    /**
     * Delete a board
     *
     * @param int $id
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws Exception
     * @throws \Throwable
     */
    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);

        $model->delete();

        return $this->redirect(['plan/index']);
    }

    /**
     * Assign user to plan
     *
     * @param int $id
     * @param int|string $userId
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionAssignUser(int $id, int|string $userId): string
    {
        $model = $this->findModel($id);

        $assignment = new BoardUserAssignment();
        $assignment->board_id = $model->id;
        $assignment->user_id = $userId;
        $assignment->save();

        return $this->renderAjax('assignees', [
            'model' => $model,
            'readonly' => !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count(),
            'users' => $this->module->users
        ]);
    }

    /**
     * Expel user from plan
     *
     * @param int $id
     * @param int|string $userId
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionExpelUser(int $id, int|string $userId): string
    {
        $model = $this->findModel($id);

        $assignment = BoardUserAssignment::findOne(['board_id' => $id, 'user_id' => $userId]);
        $assignment->delete();

        return $this->renderAjax('assignees', [
            'model' => $model,
            'readonly' => !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count(),
            'users' => $this->module->users
        ]);
    }

    /**
     * Toggle hidden boards
     * @return Response
     */
    public function actionToggleHiddenBoards(): Response
    {
        Yii::$app->session->set(
            'kanban.plan.showHiddenBoards',
            !Yii::$app->session->get('kanban.plan.showHiddenBoards', false)
        );

        return $this->redirect(['index']);
    }

    /**
     * Toggle a board visibility for the current user
     *
     * @param int $id boardId
     *
     * @return Response
     * @throws \yii\db\Exception
     */
    public function actionToggleBoardVisibility(int $id): Response
    {
        $board = Board::findOne($id);
        $setting = $board->setting ?? new BoardUserSetting([
            'board_id' => $board->id,
            'user_id' => Yii::$app->user->id
        ]);

        $setting->is_hidden = !$setting->is_hidden;
        $setting->save();

        return $this->redirect(['index']);
    }


    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param mixed $condition
     *
     * @return Board the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(mixed $condition): Board
    {
        if (($model = Board::findOne($condition)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    /**
     * @param Board $model
     *
     * @return void
     * @throws Exception
     */
    protected function saveImage(Board $model): void
    {
        $image = UploadedFile::getInstance($model, 'uploadedFile');
        if ($image) {
            $path = Yii::getAlias($this->module->uploadWebRoot . '/plan/' . $model->id);
            if (FileHelper::createDirectory($path)) {
                $filePath = $path . DIRECTORY_SEPARATOR . $image->baseName . '.' . $image->extension;
                if ($image->saveAs($filePath)) {
                    $model->image = Yii::getAlias($this->module->uploadWeb . '/plan/' . $model->id . '/' . $image->baseName . '.' . $image->extension);
                    $model->save();
                }
            }
        }
    }
}
