<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\BoardEvent;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\ChecklistElement;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Class PlanController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class PlanController extends Controller
{
    use RenderingTrait;

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
                        'actions' => ['create', 'assign-user', 'expel-user'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'delete'],
                        'matchCallback' => function () {
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
                        'actions' => ['view', 'search-tasks'],
                        'matchCallback' => function () {
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
     * @param string $activeTab One of 'plan', 'tasks', 'delegated'
     * @return string
     */
    public function actionIndex($activeTab = 'plan')
    {
        $boards = Board::findByUserId();

        Url::remember(['plan/index'], 'plan-view');

        return $this->render('index', [
            'boards' => $boards,
            'delegated' => $this->renderDelegatedTasks(),
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Show board
     *
     * @param integer $id
     * @param string $group
     * @param integer|null $showTask
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id, $group = 'bucket', $showTask = null)
    {
        $model = $this->findModel($id);
        $readonly = !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count();

        $bucketContent = $this->renderBucketContent($model, $group, $readonly);

        Url::remember(['plan/view', 'id' => $id, 'group' => $group], 'plan-view');

        return $this->render('view', [
            'boards' => Board::findByUserId(),
            'model' => $model,
            'readonly' => $readonly,
            'buckets' => $bucketContent,
            'users' => $this->module->users,
            'showTask' => $showTask
        ]);
    }

    /**
     * @param integer $id
     * @param string $group
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionSearchTasks($id, $group = 'bucket')
    {
        $q = Yii::$app->request->getBodyParam('q');
        if (empty($q)) {
            return $this->redirect(['view', 'id' => $id]);
        }

        $model = $this->findModel($id);
        $readonly = !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count();

        $bucketContent = $this->renderBucketContent($model, $group, $readonly, [
            'or',
            ['like', '{{t}}.[[subject]]', $q],
            ['like', '{{t}}.[[description]]', $q],
            ['like', ChecklistElement::tableName() . '.[[name]]', $q],
            ['like', '{{co}}.[[text]]', $q]
        ]);

        return $this->renderAjax('buckets', [
            'model' => $model,
            'buckets' => $bucketContent
        ]);
    }

    /**
     * Schedule view
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSchedule($id)
    {
        $model = $this->findModel($id);
        $readonly = !$model->is_public && !$model->getAssignments()->where(['user_id' => Yii::$app->user->id])->count();

        $taskQuery = $model->getTasks()
            ->where(['not', ['start_date' => null]])
            ->orWhere(['not', ['end_date' => null]]);
        /* @var $tasks \simialbi\yii2\kanban\models\Task[] */

        if ($readonly) {
            $taskQuery->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
        }

        $calendarTasks = [];
        foreach ($taskQuery->all() as $task) {
            /* @var $task \simialbi\yii2\kanban\models\Task */
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
//                $calendarTask['title'] = FAR::i('calendar-alt') . ' ' . $calendarTask['title'];
                $calendarTask['classNames'] = ['border-0', 'bg-danger'];
            }
            if ($task->status === Task::STATUS_DONE) {
                $calendarTask['classNames'] = ['border-0', 'bg-success'];
            }
            if ($task->status !== Task::STATUS_NOT_BEGUN && $task->status !== Task::STATUS_DONE) {
//                $calendarTask['title'] = FAS::i('star-half-alt') . ' ' . $calendarTask['title'];
                $calendarTask['classNames'] = ['border-0', 'bg-dark'];
            }

            $calendarTasks[] = $calendarTask;
        }

        Url::remember(['plan/schedule', 'id' => $id], 'plan-view');

        return $this->render('schedule', [
            'model' => $model,
            'otherTasks' => $this->renderBucketContent($model, 'schedule', $readonly),
            'calendarTasks' => $calendarTasks,
            'users' => $this->module->users,
            'readonly' => $readonly
        ]);
    }

    /**
     * @param integer $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionChart($id)
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
            '{{t}}.[[status]]'
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
                'status' => new Expression('15')
            ])
            ->where(['{{p}}.[[id]]' => $id])
            ->andWhere(['not', ['{{t}}.[[end_date]]' => null]])
            ->andWhere(['<', '{{t}}.[[end_date]]', Yii::$app->formatter->asTimestamp('today')])
            ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]]);
        $query->union($query2);
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
            ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
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
     * @return string|\yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionCreate()
    {
        $model = new Board([
            'is_public' => true
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $image = UploadedFile::getInstance($model, 'uploadedFile');

            if ($image) {
                $path = Yii::getAlias('@webroot/uploads');
                if (FileHelper::createDirectory($path)) {
                    $filePath = $path . DIRECTORY_SEPARATOR . $image->baseName . '.' . $image->extension;
                    if ($image->saveAs($filePath)) {
                        $model->image = Yii::getAlias('@web/uploads/' . $image->baseName . '.' . $image->extension);
                        $model->save();
                    }
                }
            }

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
     * @param integer $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $image = UploadedFile::getInstance($model, 'uploadedFile');

            if ($image) {
                $path = Yii::getAlias('@webroot/uploads');
                if (FileHelper::createDirectory($path)) {
                    $filePath = $path . DIRECTORY_SEPARATOR . $image->baseName . '.' . $image->extension;
                    if ($image->saveAs($filePath)) {
                        $model->image = Yii::getAlias('@web/uploads/' . $image->baseName . '.' . $image->extension);
                        $model->save();
                    }
                }
            }

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
     * @param integer $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $model->delete();

        return $this->redirect(['plan/index']);
    }

    /**
     * Assign user to plan
     *
     * @param integer $id
     * @param integer|string $userId
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionAssignUser($id, $userId)
    {
        $model = $this->findModel($id);

        $model::getDb()->createCommand()->insert('{{%kanban_board_user_assignment}}', [
            'board_id' => $model->id,
            'user_id' => $userId
        ])->execute();

        $previous = Url::previous('plan-view') ?: ['plan/view', 'id' => $model->id];

        return $this->redirect($previous);
    }

    /**
     * Assign user to plan
     *
     * @param integer $id
     * @param integer|string $userId
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionExpelUser($id, $userId)
    {
        $model = $this->findModel($id);

        $model::getDb()->createCommand()->delete('{{%kanban_board_user_assignment}}', [
            'board_id' => $model->id,
            'user_id' => $userId
        ])->execute();
        $model::getDb()->createCommand()->delete('{{%kanban_task_user_assignment}}', [
            'task_id' => ArrayHelper::getColumn($model->tasks, 'id'),
            'user_id' => $userId
        ])->execute();

        $previous = Url::previous('plan-view') ?: ['plan/view', 'id' => $model->id];

        return $this->redirect($previous);
    }

    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Board the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Board::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
