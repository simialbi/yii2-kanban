<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;


use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\ChecklistElement;
use simialbi\yii2\kanban\models\Comment;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
                        'actions' => ['create', 'update', 'set-status', 'set-end-date', 'assign-user', 'expel-user'],
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
            return $this->renderAjax('/bucket/item', [
                'model' => $model,
                'statuses' => $this->module->statuses
            ]);
        }

        return $this->renderAjax('create', [
            'model' => $model,
            'task' => $task,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $checklistElements = Yii::$app->request->getBodyParam('checklist', []);
            $newElements = ArrayHelper::remove($checklistElements, 'new', []);
            $comment = Yii::$app->request->getBodyParam('comment');

            ChecklistElement::deleteAll(['not', ['id' => array_keys($checklistElements)]]);

            foreach ($checklistElements as $id => $checklistElement) {
                $element = ChecklistElement::findOne($id);
                if (!$element) {
                    continue;
                }

                $element->setAttributes($checklistElement);
                $element->save();
            }
            foreach ($newElements as $checklistElement) {
                $element = new ChecklistElement($checklistElement);
                $element->task_id = $model->id;

                $element->save();
            }

            if ($comment) {
                $comment = new Comment([
                    'task_id' => $model->id,
                    'text' => $comment
                ]);

                $comment->save();
            }

            return $this->redirect(['plan/view', 'id' => $model->board->id]);
        }

        $buckets = Bucket::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->where(['board_id' => $model->board->id])
            ->indexBy('id')
            ->column();

        if ($model->start_date !== null) {
            $model->start_date = Yii::$app->formatter->asDate($model->start_date);
        }
        if ($model->end_date !== null) {
            $model->end_date = Yii::$app->formatter->asDate($model->end_date);
        }

        return $this->renderAjax('update', [
            'model' => $model,
            'buckets' => $buckets,
            'users' => call_user_func([Yii::$app->user->identityClass, 'findIdentities']),
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Set status of task and redirect back
     *
     * @param integer $id
     * @param integer $status
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionSetStatus($id, $status)
    {
        $model = $this->findModel($id);

        $model->status = $status;
        $model->save();

        return $this->renderAjax('item', [
            'model' => $model,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Set status of task and redirect back
     *
     * @param integer $id
     * @param integer $date
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSetEndDate($id, $date)
    {
        $model = $this->findModel($id);

        $model->end_date = Yii::$app->formatter->asDate($date);
        $model->save();

        return $this->renderAjax('item', [
            'model' => $model,
            'users' => call_user_func([Yii::$app->user->identityClass, 'findIdentities']),
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Assign user to task
     *
     * @param integer $id
     * @param integer|string $userId
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionAssignUser($id, $userId)
    {
        $model = $this->findModel($id);

        $model::getDb()->createCommand()->insert('{{%kanban_task_user_assignment}}', [
            'task_id' => $model->id,
            'user_id' => $userId
        ])->execute();

        return $this->renderAjax('item', [
            'model' => $model,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Assign user to task
     *
     * @param integer $id
     * @param integer|string $userId
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionExpelUser($id, $userId)
    {
        $model = $this->findModel($id);

        $model::getDb()->createCommand()->delete('{{%kanban_task_user_assignment}}', [
            'task_id' => $model->id,
            'user_id' => $userId
        ])->execute();

        return $this->renderAjax('item', [
            'model' => $model,
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
