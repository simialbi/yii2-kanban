<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\BucketEvent;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class BucketController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class BucketController extends Controller
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
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Create a new bucket
     *
     * @param int $boardId
     *
     * @return string|Response
     * @throws Exception
     */
    public function actionCreate(int $boardId): Response|string
    {
        $model = new Bucket(['board_id' => $boardId]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->module->trigger(Module::EVENT_BUCKET_CREATED, new BucketEvent([
                'bucket' => $model
            ]));

            return $this->redirect(['plan/view', 'id' => $model->board_id]);
        }

        return $this->renderAjax('create', [
            'model' => $model
        ]);
    }

    /**
     * Render bucket
     *
     * @param int $id
     * @param bool $readonly
     *
     * @return string
     */
    public function actionView(int $id, bool $readonly = false): string
    {
        $model = Bucket::find()
            ->with([
                'openTasks' => function ($query) use ($readonly) {
                    /** @var $query ActiveQuery */
                    if ($readonly) {
                        $query
                            ->innerJoinWith('assignments u')
                            ->andWhere([
                                'or',
                                ['{{u}}.[[user_id]]' => Yii::$app->user->id],
                                [Task::tableName() . '.[[responsible_id]]' => Yii::$app->user->id]
                            ]);
                    }
                }, 'board'
            ])
            ->where(['id' => $id])
            ->one();

        if ($readonly) {
            $finishedTasks = $model->getTasks()
                ->distinct()
                ->innerJoinWith('assignments a')
                ->where(['[[status]]' => Task::STATUS_DONE])
                ->andWhere([
                    'or',
                    ['{{a}}.[[user_id]]' => Yii::$app->user->id],
                    [Task::tableName() . '.[[responsible_id]]' => Yii::$app->user->id]
                ])
                ->count('[[id]]');
        } else {
            $finishedTasks = $model->getTasks()->where(['status' => Task::STATUS_DONE])->count('id');
        }

        return $this->renderPartial('view', [
            'model' => $model,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'finishedTasks' => $finishedTasks,
            'closeModal' => false,
            'readonly' => $readonly
        ]);
    }

    /**
     * Render bucket
     *
     * @param int $id
     * @param bool $readonly
     * @param int $start
     * @param int $limit
     *
     * @return string
     */
    public function actionViewFinished(int $id, bool $readonly = false, int $start = 0, int $limit = 20): string
    {
        $model = Bucket::find()
            ->with([
                'finishedTasks' => function ($query) use ($readonly, $start, $limit) {
                    /** @var $query ActiveQuery */
                    if ($readonly) {
                        $query
                            ->innerJoinWith('assignments u')
                            ->andWhere([
                                'or',
                                ['{{u}}.[[user_id]]' => Yii::$app->user->id],
                                [Task::tableName() . '.[[responsible_id]]' => Yii::$app->user->id]
                            ]);
                    }
                    $query->offset($start)->limit($limit);
                }
            ])
            ->where(['id' => $id])
            ->one();

        return $this->renderPartial('view-finished', [
            'model' => $model,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'readonly' => $readonly
        ]);
    }

    /**
     * @param int $boardId
     * @param int|string|null $id
     * @param bool $readonly
     *
     * @return string
     * @throws \Exception
     */
    public function actionViewAssignee(int $boardId, int|string|null $id = null, bool $readonly = false): string
    {
        $query = Task::find()
            ->alias('t')
            ->distinct()
            ->joinWith('assignments u')
            ->innerJoinWith('bucket b')
            ->with(['attachments', 'links', 'assignments', 'checklistElements', 'comments'])
            ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
            ->andWhere([
                '{{b}}.[[board_id]]' => $boardId,
            ]);

        if ($readonly) {
            $query->andWhere([
                'or',
                ['{{u}}.[[user_id]]' => Yii::$app->user->id],
                ['{{t}}.[[responsible_id]]' => Yii::$app->user->id]
            ]);
        } else {
            $query->andWhere(['{{u}}.[[user_id]]' => $id]);
        }

        return $this->renderPartial('view-assignee', [
            'id' => $id,
            'boardId' => $boardId,
            'tasks' => $query->all(),
            'user' => ArrayHelper::getValue($this->module->users, $id),
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'finishedTasks' => $query->where(['{{t}}.[[status]]' => Task::STATUS_DONE])->andWhere([
                '{{b}}.[[board_id]]' => $boardId,
                '{{u}}.[[user_id]]' => $id
            ])->count(),
            'readonly' => $readonly
        ]);
    }

    /**
     * @param int $boardId
     * @param int|string|null $id
     * @param bool $readonly
     * @param int $start
     * @param int $limit
     *
     * @return string
     * @throws \Exception
     */
    public function actionViewAssigneeFinished(
        int             $boardId,
        int|string|null $id = null,
        bool            $readonly = false,
        int             $start = 0,
        int             $limit = 20
    ): string
    {
        $query = Task::find()
            ->alias('t')
            ->distinct()
            ->joinWith('assignments u')
            ->innerJoinWith('bucket b')
            ->with(['attachments', 'links', 'assignments', 'checklistElements', 'comments'])
            ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
            ->andWhere([
                '{{b}}.[[board_id]]' => $boardId,
                '{{u}}.[[user_id]]' => $id
            ])
            ->orderBy(['{{t}}.[[sort]]' => SORT_ASC])
            ->offset($start)
            ->limit($limit);

        return $this->renderPartial('view-assignee-finished', [
            'boardId' => $boardId,
            'tasks' => $query->all(),
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'readonly' => $readonly
        ]);
    }

    /**
     * @param int $status
     * @param int $boardId
     * @param bool $readonly
     *
     * @return string
     * @throws \Exception
     */
    public function actionViewStatus(int $boardId, int $status, bool $readonly = false): string
    {
        $query = Task::find()
            ->alias('t')
            ->joinWith('assignments u')
            ->joinWith('checklistElements')
            ->joinWith('comments co')
            ->innerJoinWith('bucket b')
            ->with(['attachments', 'links'])
            ->where([
                '{{t}}.[[status]]' => $status,
                '{{b}}.[[board_id]]' => $boardId
            ]);
        if ($readonly) {
            $query->andWhere([
                'or',
                ['{{u}}.[[user_id]]' => Yii::$app->user->id],
                ['{{t}}.[[responsible_id]]' => Yii::$app->user->id]
            ]);
        }

        return $this->renderPartial('view-status', [
            'tasks' => $query->all(),
            'status' => $status,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'readonly' => $readonly
        ]);
    }

    /**
     * Update bucket
     *
     * @param int $id
     *
     * @return string
     * @throws NotFoundHttpException|Exception
     */
    public function actionUpdate(int $id): string
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->renderAjax('_header', [
                'id' => $model->id,
                'title' => $model->name,
                'renderButtons' => true,
                'readonly' => false
            ]);
        }

        return $this->renderAjax('update', [
            'model' => $model
        ]);
    }

    /**
     * Delete bucket
     *
     * @param int $id
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);

        $model->delete();

        return $this->redirect([
            'plan/view',
            'id' => $model->board->id,
            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
        ]);
    }

    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return Bucket the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): Bucket
    {
        if (($model = Bucket::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
