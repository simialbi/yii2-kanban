<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Attachment;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class AttachmentController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class AttachmentController extends Controller
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
                        'actions' => ['delete'],
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Delete attachment
     * @param int $id
     * @param bool $readonly
     * @param bool $updateSeries
     * @return Response
     * @throws NotFoundHttpException
     * @throws StaleObjectException
     * @throws \Throwable
     */
    public function actionDelete(int $id, bool $readonly = false, bool $updateSeries = false): Response
    {
        $model = $this->findModel($id);
        $task = $model->task;
        $taskId = $task->isRecurrentInstance() ? $task->recurrence_parent_id : $task->id;

        $model->delete();

        return $this->redirect([
            'task/update',
            'id' => $taskId,
            'updateSeries' => $updateSeries,
            'return' => 'card',
            'readonly' => $readonly
        ]);
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return Attachment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): Attachment
    {
        if (($model = Attachment::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
