<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;


use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class TaskController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class TaskController extends Controller
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
                        'actions' => ['create', 'update'],
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Create a new bucket
     * @param integer $bucketId
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionCreate($bucketId)
    {
        $model = $this->findBucketModel($bucketId);
        $task = new Task(['bucket_id' => $bucketId]);

        if ($task->load(Yii::$app->request->post()) && $task->save()) {
            return $this->renderAjax('/bucket/item', ['model' => $model]);
        }

        return $this->renderAjax('create', [
            'model' => $model,
            'task' => $task
        ]);
    }

    /**
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $buckets = Bucket::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->where(['board_id' => $model->board->id])
            ->indexBy('id')
            ->column();

        return $this->renderAjax('update', [
            'model' => $model,
            'buckets' => $buckets,
            'statuses' => $this->module->statuses
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
    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Bucket the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findBucketModel($id)
    {
        if (($model = Bucket::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
