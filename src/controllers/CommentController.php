<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Comment;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

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
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCreate($taskId, $group = 'bucket')
    {
        $task = $this->findTaskModel($taskId);
        $model = new Comment(['task_id' => $taskId]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['plan/view', 'id' => $task->board->id, 'group' => $group]);
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
