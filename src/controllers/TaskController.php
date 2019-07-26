<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;


use simialbi\yii2\kanban\models\Attachment;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\ChecklistElement;
use simialbi\yii2\kanban\models\Comment;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\UserInterface;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\TaskEvent;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Class TaskController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class TaskController extends Controller
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
                        'actions' => [
                            'create',
                            'update',
                            'delete',
                            'set-status',
                            'set-end-date',
                            'set-dates',
                            'assign-user',
                            'expel-user'
                        ],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view']
                    ]
                ]
            ]
        ];
    }

    /**
     * Render task item
     *
     * @param string $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->renderAjax('item', [
            'model' => $model,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Create a new bucket
     * @param integer $boardId
     * @param integer|null $bucketId
     * @param integer|null $userId
     * @param integer|null $status
     * @param integer|null $date
     * @return string
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCreate($boardId, $bucketId = null, $userId = null, $status = null, $date = null)
    {
        $board = $this->findBoardModel($boardId);
        $buckets = [];
        if (isset($bucketId)) {
            $task = new Task(['bucket_id' => $bucketId]);
            $keyName = 'bucketId';
            $id = $bucketId;
            $group = 'bucket';
        } elseif (isset($userId)) {
            $task = new Task();

            $buckets = Bucket::find()
                ->select(['name', 'id'])
                ->where(['board_id' => $board->id])
                ->orderBy(['created_at' => SORT_ASC])
                ->indexBy('id')
                ->column();
            $keyName = 'userId';
            $id = $userId;
            $group = 'assignee';

            $task->on(Task::EVENT_AFTER_INSERT, function () use ($userId, $task) {
                $task::getDb()->createCommand()->insert('{{%kanban_task_user_assignment}}', [
                    'task_id' => $task->id,
                    'user_id' => $userId
                ])->execute();
            });
        } elseif (isset($status)) {
            $task = new Task(['status' => $status]);

            $buckets = Bucket::find()
                ->select(['name', 'id'])
                ->where(['board_id' => $board->id])
                ->orderBy(['created_at' => SORT_ASC])
                ->indexBy('id')
                ->column();
            $keyName = 'status';
            $id = $status;
            $group = 'status';
        } elseif (isset($date)) {
            $task = new Task(['end_date' => empty($date) ? null : Yii::$app->formatter->asDate($date)]);

            $buckets = Bucket::find()
                ->select(['name', 'id'])
                ->where(['board_id' => $board->id])
                ->orderBy(['created_at' => SORT_ASC])
                ->indexBy('id')
                ->column();
            $keyName = 'date';
            $id = $date;
            $group = 'date';
        } else {
            throw new BadRequestHttpException(Yii::t('yii', 'Missing required parameters: {params}', [
                'params' => Yii::t('simialbi/kanban/task/notification', 'One of {params}', [
                    'params' => 'bucketId, userId, status, date'
                ])
            ]));
        }

        if ($task->load(Yii::$app->request->post()) && $task->save()) {
            $this->module->trigger(Module::EVENT_TASK_CREATED, new TaskEvent([
                'task' => $task
            ]));

            return $this->redirect(['plan/view', 'id' => $board->id, 'group' => $group]);
        }

        return $this->renderAjax('create', [
            'board' => $board,
            'id' => $id,
            'keyName' => $keyName,
            'task' => $task,
            'buckets' => $buckets,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $checklistElements = Yii::$app->request->getBodyParam('checklist', []);
            $newElements = ArrayHelper::remove($checklistElements, 'new', []);
            $assignees = Yii::$app->request->getBodyParam('assignees', []);
            $comment = Yii::$app->request->getBodyParam('comment');
            $newAttachments = UploadedFile::getInstancesByName('attachments');

            ChecklistElement::deleteAll([
                'and',
                ['task_id' => $model->id],
                ['not', ['id' => array_keys($checklistElements)]]
            ]);

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

                $this->module->trigger(Module::EVENT_CHECKLIST_CREATED, new TaskEvent([
                    'task' => $model,
                    'data' => $element
                ]));

                $element->save();
            }

            try {
                $model::getDb()->createCommand()->delete(
                    '{{%kanban_task_user_assignment}}',
                    ['and', ['task_id' => $model->id], ['not', ['user_id' => $assignees]]]
                )->execute();
            } catch (Exception $e) {
            }
            foreach ($assignees as $assignee) {
                try {
                    $model::getDb()->createCommand()->insert(
                        '{{%kanban_task_user_assignment}}',
                        ['task_id' => $model->id, 'user_id' => $assignee]
                    )->execute();
                    $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
                        'task' => $model,
                        'user' => ArrayHelper::getValue(Yii::$app->cache->get('kanban-users'), $assignee)
                    ]));
                } catch (Exception $e) {
                }
            }

            if ($comment) {
                $comment = new Comment([
                    'task_id' => $model->id,
                    'text' => $comment
                ]);

                $comment->save();

                $this->module->trigger(Module::EVENT_COMMENT_CREATED, new TaskEvent([
                    'task' => $model,
                    'data' => $comment
                ]));
            }

            Attachment::loadMultiple($model->attachments, Yii::$app->request->post());
            foreach ($model->attachments as $attachment) {
                $attachment->save();
            }

            if (!empty($newAttachments)) {
                $path = Yii::getAlias('@webroot/uploads');
                if (FileHelper::createDirectory($path)) {
                    foreach ($newAttachments as $uploadedFile) {
                        $filePath = $path . DIRECTORY_SEPARATOR . $uploadedFile->baseName . '.' . $uploadedFile->extension;
                        if (!$uploadedFile->saveAs($filePath)) {
                            continue;
                        }
                        $attachment = new Attachment([
                            'task_id' => $model->id,
                            'name' => $uploadedFile->name,
                            'mime_type' => $uploadedFile->type,
                            'size' => $uploadedFile->size,
                            'path' => Yii::getAlias('@web/uploads/' . $uploadedFile->baseName . '.' . $uploadedFile->extension)
                        ]);
                        $attachment->save();

                        $this->module->trigger(Module::EVENT_ATTACHMENT_ADDED, new TaskEvent([
                            'task' => $model,
                            'data' => $attachment
                        ]));
                    }
                }
            }

            if ($model->isAttributeChanged('status')) {
                $this->module->trigger(Module::EVENT_TASK_STATUS_CHANGED, new TaskEvent([
                    'task' => $model,
                    'data' => $model->status
                ]));
                if ($model->status == Task::STATUS_DONE) {
                    $this->module->trigger(Module::EVENT_TASK_COMPLETED, new TaskEvent([
                        'task' => $model
                    ]));
                }
            }

            $previous = Url::previous('plan-view') ?: ['plan/view', 'id' => $model->board->id];

            return $this->redirect($previous);
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

        $statuses = $this->module->statuses;
        unset($statuses[Task::STATUS_LATE]);

        return $this->renderAjax('update', [
            'model' => $model,
            'buckets' => $buckets,
            'users' => Yii::$app->cache->get('kanban-users'),
            'statuses' => $statuses
        ]);
    }

    /**
     * Delete task
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

        $this->module->trigger(Module::EVENT_TASK_STATUS_CHANGED, new TaskEvent([
            'task' => $model,
            'data' => $status
        ]));

        if ($status == Task::STATUS_DONE) {
            $this->module->trigger(Module::EVENT_TASK_COMPLETED, new TaskEvent([
                'task' => $model
            ]));
        }

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
            'users' => Yii::$app->cache->get('kanban-users'),
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Change task dates
     *
     * @param integer $id
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSetDates($id)
    {
        $model = $this->findModel($id);

        $startDate = Yii::$app->request->getBodyParam('startDate');
        $endDate = Yii::$app->request->getBodyParam('endDate');

        if ($startDate) {
            $model->start_date = Yii::$app->formatter->asDate($startDate);
        }
        if ($endDate) {
            $model->end_date = Yii::$app->formatter->asDate($endDate);
        }

        $model->save();
        Yii::$app->response->setStatusCode(204);
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

        $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
            'task' => $model,
            'user' => ArrayHelper::getValue(Yii::$app->cache->get('kanban-users'), $userId)
        ]));

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

        $this->module->trigger(Module::EVENT_TASK_UNASSIGNED, new TaskEvent([
            'task' => $model,
            'user' => ArrayHelper::getValue(Yii::$app->cache->get('kanban-users'), $userId)
        ]));

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

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Board the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findBoardModel($id)
    {
        if (($model = Board::findOne($id)) !== null) {
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
     * @return UserInterface the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findUserModel($id)
    {
        if (($model = call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
