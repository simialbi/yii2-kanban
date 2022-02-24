<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Attachment;
use simialbi\yii2\kanban\models\Bucket;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class AttachmentController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class AttachmentController extends Controller
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
                        'actions' => ['delete'],
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Delete attachment
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id, $readonly = false)
    {
        $model = $this->findModel($id);
        $task = $model->task;

        $model->delete();

        $buckets = Bucket::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->where(['board_id' => $task->board->id])
            ->indexBy('id')
            ->column();

        if ($task->start_date !== null) {
            $task->start_date = Yii::$app->formatter->asDate($task->start_date);
        }
        if ($task->end_date !== null) {
            $task->end_date = Yii::$app->formatter->asDate($task->end_date);
        }

        return $this->renderAjax('/task/update', [
            'model' => $task,
            'buckets' => $buckets,
            'users' => $this->module->users,
            'updateSeries' => false,
            'statuses' => $this->module->statuses,
            'return' => 'card',
            'readonly' => $readonly,
        ]);
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Attachment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Attachment::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
