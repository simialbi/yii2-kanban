<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Comment;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\TaskEvent;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class CommentController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class CommentController extends Controller
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
                        'actions' => [
                            'create'
                        ],
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Create a new comment in a task
     *
     * @param integer $taskId
     * @param string $group
     * @param boolean $readonly
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCreate($taskId, $group = 'bucket', $readonly = false)
    {
        $task = $this->findTaskModel($taskId);
        $model = new Comment(['task_id' => $taskId]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->module->trigger(Module::EVENT_COMMENT_CREATED, new TaskEvent([
                'task' => $task,
                'data' => $model
            ]));

            $this->module->trigger(Module::EVENT_TASK_UPDATED, new TaskEvent([
                'task' => $task,
                'data' => $model
            ]));

            return $this->renderAjax('/task/item', [
                'boardId' => $task->board->id,
                'model' => $task,
                'statuses' => $this->module->statuses,
                'users' => $this->module->users,
                'group' => $group,
                'closeModal' => true,
                'readonly' => $readonly
            ]);
        }

        return $this->renderAjax('create', [
            'model' => $model
        ]);
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTaskModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
