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
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class BucketController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class BucketController extends Controller
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
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Create a new bucket
     * @param integer $boardId
     * @return string|\yii\web\Response
     */
    public function actionCreate($boardId)
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
     * @param integer $id
     * @param boolean $readonly
     * @return string
     */
    public function actionView($id, $readonly = false)
    {
        $model = Bucket::find()
            ->with([
                'openTasks' => function ($query) use ($readonly) {
                    /** @var $query \yii\db\ActiveQuery */
                    if ($readonly) {
                        $query->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                    }
                }
            ])
            ->where(['id' => $id])
            ->one();

        if ($readonly) {
            $finishedTasks = $model->getTasks()
                ->alias('t')
                ->innerJoinWith('assignments a')
                ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
                ->andWhere(['{{a}}.[[user_id]]' => Yii::$app->user->id])
                ->count('{{t}}.[[id]]');
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
     * @param integer $id
     * @param boolean $readonly
     * @return string
     */
    public function actionViewFinished($id, $readonly = false)
    {
        $model = Bucket::find()
            ->with([
                'finishedTasks' => function ($query) use ($readonly) {
                    /** @var $query \yii\db\ActiveQuery */
                    if ($readonly) {
                        $query->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
                    }
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
     * @param integer $boardId
     * @param integer|null $id
     * @param boolean $readonly
     *
     * @return string
     * @throws \Exception
     */
    public function actionViewAssignee($boardId, $id = null, $readonly = false)
    {
        $query = Task::find()
            ->alias('t')
            ->distinct(true)
            ->joinWith('assignments u')
            ->innerJoinWith('bucket b')
            ->with(['attachments', 'links', 'assignments', 'checklistElements', 'comments'])
            ->where(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
            ->andWhere([
                '{{b}}.[[board_id]]' => $boardId,
                '{{u}}.[[user_id]]' => $id
            ]);

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
     * @param integer $boardId
     * @param integer|null $id
     *
     * @return string
     * @throws \Exception
     */
    public function actionViewAssigneeFinished($boardId, $id = null, $readonly = false)
    {
        $query = Task::find()
            ->alias('t')
            ->distinct(true)
            ->joinWith('assignments u')
            ->innerJoinWith('bucket b')
            ->with(['attachments', 'links', 'assignments', 'checklistElements', 'comments'])
            ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
            ->andWhere([
                '{{b}}.[[board_id]]' => $boardId,
                '{{u}}.[[user_id]]' => $id
            ]);

        return $this->renderPartial('view-assignee-finished', [
            'id' => $id,
            'boardId' => $boardId,
            'tasks' => $query->all(),
            'user' => ArrayHelper::getValue($this->module->users, $id),
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'readonly' => $readonly
        ]);
    }

    /**
     * @param integer $status
     * @param integer $boardId
     * @param boolean $readonly
     *
     * @return string
     * @throws \Exception
     */
    public function actionViewStatus($boardId, $status, $readonly = false)
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
            $query->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
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
     * @param integer $id
     *
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->renderAjax('_header', [
                'id' => $model->id,
                'title' => $model->name
            ]);
        }

        return $this->renderAjax('update', [
            'model' => $model
        ]);
    }

    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Bucket the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Bucket::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    /**
     * Delete bucket
     *
     * @param integer $id
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $model->delete();

        return $this->redirect([
            'plan/view',
            'id' => $model->board->id,
            'group' => Yii::$app->request->getQueryParam('group', 'bucket')
        ]);
    }
}
