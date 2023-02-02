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
use simialbi\yii2\kanban\models\Link;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskCopyForm;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\TaskEvent;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\ticket\models\Ticket;
use simialbi\yii2\ticket\models\Topic;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class TaskController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
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
                        'actions' => [
                            'create',
                            'update',
                            'copy',
                            'copy-per-user',
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
                        'actions' => ['view', 'view-completed', 'view-delegated', 'view-responsible', 'history']
                    ]
                ]
            ]
        ];
    }

    /**
     * Render task item
     *
     * @param integer $id Tasks id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->renderAjax('item', [
            'model' => $model,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'closeModal' => false
        ]);
    }

    /**
     * View recurrence task history
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionHistory($id)
    {
        $model = $this->findModel($id);

        $query = new Query();
        $query->select([
            'date' => 'execution_date',
            'status'
        ])
            ->from('{{%kanban__task_recurrent_task}}')
            ->where(['task_id' => $id])
            ->orderBy(['execution_date' => SORT_DESC]);

        return $this->renderAjax('history', [
            'model' => $model,
            'statuses' => $this->module->statuses,
            'history' => $query->all()
        ]);
    }

    /**
     * @param string $view
     */
    public function actionViewDelegated($view = 'task')
    {
        $query = Task::find()
            ->alias('t')
            ->innerJoinWith(['assignments u'])
            ->innerJoinWith('bucket b')
            ->with(['bucket', 'assignments', 'checklistElements', 'board', 'comments'])
            ->where(['{{t}}.[[created_by]]' => Yii::$app->user->id])
            ->andWhere(['not', ['{{u}}.[[user_id]]' => Yii::$app->user->id]])
            ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
            ->addOrderBy(['{{u}}.[[user_id]]' => SORT_ASC]);
//            ->groupBy(['{{u}}.[[user_id]]']);

        $indexed = [];
        foreach ($query->all() as $task) {
            foreach ($task->assignments as $assignment) {
                if ($assignment->user_id != Yii::$app->user->id) {
                    $indexed[$assignment->user_id][] = $task;
                }
            }
        }

        if ($view === 'list') {
            foreach ($indexed as &$user) {
                Module::sortTasks($user);
            }
            unset($user);
        }

        return $this->renderAjax('delegated', [
            'tasks' => $indexed,
            'view' => $view,
            'users' => $this->module->users,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function actionViewResponsible()
    {
        $filters = Yii::$app->request->getQueryParam('Filters', []);

        $query = Board::find()
            ->select([
                'b.name',
                'bo.*'
            ])
            ->alias('bo')
            ->distinct()
            ->joinWith([
                'buckets b' => function (ActiveQuery $query) {
                    $query->joinWith([
                        'tasks t' => function (ActiveQuery $query) {
                            $query
                                ->where(['{{t}}.[[responsible_id]]' => Yii::$app->user->id])
                                ->andWhere(['not', ['{{t}}.[[status]]' => Task::STATUS_DONE]])
                                ->orderBy([]);
                        }
                    ], true, 'INNER JOIN')
                        ->orderBy([]);
                }
            ], true, 'INNER JOIN')
            ->filterWhere([
                Board::tableName() . '.id' => ArrayHelper::getValue($filters, 'boardId')
            ])
            ->orderBy([
                '{{bo}}.[[name]]' => SORT_ASC,
                '{{b}}.[[name]]' => SORT_ASC
            ]);

        return $this->renderAjax('responsible', [
            'boards' => $query->all(),
            'module' => $this->module
        ]);
    }

    /**
     * Create a new task
     * @param integer $boardId
     * @param integer|null $bucketId
     * @param boolean $mobile
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionCreate($boardId, $bucketId, $mobile = 0)
    {
        $board = $this->findBoardModel($boardId);
        $buckets = [];
        $task = new Task(['bucket_id' => $bucketId]);
        $keyName = 'bucketId';
        $id = $bucketId;

        if ($task->load(Yii::$app->request->post()) && $task->save()) {
            $taskPerUser = Yii::$app->request->getBodyParam('copy_per_user', false);
            $assignees = Yii::$app->request->getBodyParam('assignees', []);
            $this->module->trigger(Module::EVENT_TASK_CREATED, new TaskEvent([
                'task' => $task
            ]));

            $i = 0;
            foreach ($assignees as $assignee) {
                if ($taskPerUser && $i++ > 0) {
                    $task->setAttribute('id', null);
                    $task = new Task($task->toArray());
                    $task->save();
                }
                $assignment = new TaskUserAssignment();
                $assignment->task_id = $task->id;
                $assignment->user_id = $assignee;
                $assignment->save();
                $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
                    'task' => $task,
                    'user' => ArrayHelper::getValue($this->module->users, $assignee)
                ]));
            }

            return $this->renderAjax('/bucket/view', [
                'model' => $task->getBucket()->with(['openTasks'])->one(),
                'statuses' => $this->module->statuses,
                'users' => $this->module->users,
                'finishedTasks' => $task->bucket->getTasks()->where(['status' => Task::STATUS_DONE])->count('id'),
                'readonly' => false
            ]);
        }

        return $this->renderAjax('create', [
            'board' => $board,
            'id' => $id,
            'keyName' => $keyName,
            'bucketName' => ($mobile ? 'Mobile' : '') . (($keyName === 'bucketId') ? Inflector::slug($task->bucket->name) : ''),
            'mobile' => $mobile ? true : false,
            'task' => $task,
            'buckets' => $buckets,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users
        ]);
    }

    /**
     * Update a task
     * @param integer $id Tasks id
     * @param boolean $updateSeries Update the series?
     * @param string $return What to render back
     * @param boolean $readonly If readonly mode?
     * @return string
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function actionUpdate($id, $updateSeries = false, $return = 'card', $readonly = false)
    {
        $model = $this->findModel($id);

        if ($updateSeries) {
            $model = $model->getOriginalRecord();
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $hasStatusChanged = $model->isAttributeChanged('status', false);
            $oldAttributes = $model->oldAttributes;
            $model->save(false);
            $checklistElements = Yii::$app->request->getBodyParam('checklist', []);
            $newChecklistElements = ArrayHelper::remove($checklistElements, 'new', []);
            $assignees = Yii::$app->request->getBodyParam('assignees', []);
            $comment = Yii::$app->request->getBodyParam('comment');
            $newAttachments = UploadedFile::getInstancesByName('attachments');
            $links = Yii::$app->request->getBodyParam('link', []);
            $newLinkElements = ArrayHelper::remove($links, 'new', []);
            $dependencies = Yii::$app->request->getBodyParam('dependencies', []);

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
            foreach ($newChecklistElements as $checklistElement) {
                $element = new ChecklistElement($checklistElement);
                $element->task_id = $model->id;

                $this->module->trigger(Module::EVENT_CHECKLIST_CREATED, new TaskEvent([
                    'task' => $model,
                    'data' => $element
                ]));

                $element->save();
            }

            $oldAssignees = ArrayHelper::getColumn($model->assignees, 'id');
            TaskUserAssignment::deleteAll(['task_id' => $model->id]);
            foreach ($assignees as $assignee) {
                $assignment = new TaskUserAssignment();
                $assignment->task_id = $model->id;
                $assignment->user_id = $assignee;
                $assignment->save();
            }
            // Send notification to all new assignees
            foreach (array_diff($assignees, $oldAssignees) as $assigneeId) {
                $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
                    'task' => $model,
                    'user' => ArrayHelper::getValue($this->module->users, $assigneeId)
                ]));
            }
            // Send notification to all expelled assignees
            foreach (array_diff($oldAssignees, $assignees) as $assigneeId) {
                $this->module->trigger(Module::EVENT_TASK_UNASSIGNED, new TaskEvent([
                    'task' => $model,
                    'user' => ArrayHelper::getValue($this->module->users, $assigneeId)
                ]));
            }
            $model->unlinkAll('dependencies');
            foreach ($dependencies as $dependency) {
                $dependency = Task::findOne($dependency);
                if ($dependency) {
                    $model->link('dependencies', $dependency);
                }
            }

            if ($comment) {
                $comment = new Comment([
                    'task_id' => $model->id,
                    'text' => $comment
                ]);

                $comment->save();

                if ($model->ticket_id) {
                    $ticketComment = new \simialbi\yii2\ticket\models\Comment([
                        'ticket_id' => $model->ticket_id,
                        'text' => $comment->text,
                        'created_by' => $comment->created_by,
                        'created_at' => $comment->created_at
                    ]);
                    $ticketComment->detachBehaviors();
                    $ticketComment->save();
                }

                $this->module->trigger(Module::EVENT_COMMENT_CREATED, new TaskEvent([
                    'task' => $model,
                    'data' => $comment
                ]));
            }

            Link::deleteAll([
                'and',
                ['task_id' => $model->id],
                ['not', ['id' => array_keys($links)]]
            ]);
            foreach ($links as $id => $link) {
                $element = Link::findOne($id);
                if (!$element) {
                    continue;
                }

                $element->setAttributes($link);
                $element->save();
            }
            foreach ($newLinkElements as $link) {
                $element = new Link($link);
                $element->task_id = $model->id;

                $element->save();
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

            if ($hasStatusChanged) {
                $this->setTicketStatus($model);
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

            $this->module->trigger(Module::EVENT_TASK_UPDATED, new TaskEvent([
                'task' => $model,
                'data' => $model,
                'oldAttributes' => $oldAttributes
            ]));

            if ($return === 'todo') {
                return $this->renderAjax('todo', [
                    'kanbanModuleName' => $this->module->id
                ]);
            } elseif ($return === 'bucket') {
                return $this->renderAjax('/bucket/view', [
                    'model' => $model->bucket,
                    'statuses' => $this->module->statuses,
                    'users' => $this->module->users,
                    'closeModal' => true,
                    'finishedTasks' => $model->bucket->getTasks()->where(['status' => Task::STATUS_DONE])->count('id'),
                    'readonly' => $readonly
                ]);
            } elseif ($return === 'list-item') {
                return $this->renderAjax('/task/list-item', [
                    'task' => $model
                ]);
            }

            $model = $this->findModel($model->id);

            return $this->renderAjax('item', [
                'boardId' => $model->board->id,
                'model' => $model,
                'statuses' => $this->module->statuses,
                'users' => $this->module->users,
                'closeModal' => true,
                'group' => 'bucket',
                'readonly' => $readonly
            ]);
        }

        $buckets = Bucket::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->where(['board_id' => $model->board->id])
            ->indexBy('id')
            ->column();
        $tasks = $model->board->getTasks()
            ->where(['not', ['id' => $model->id]])
            ->andWhere(['not', ['id' => ArrayHelper::getColumn($model->dependencies, 'id')]])
            ->orderBy(['subject' => SORT_ASC])
            ->all();
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
            'users' => $this->module->users,
            'updateSeries' => $updateSeries,
            'statuses' => $statuses,
            'return' => $return,
            'readonly' => $readonly,
            'tasks' => $tasks
        ]);
    }

    /**
     * Copy a task for each user
     * @param string $id The id of the task
     * @param string $group In which view are we?
     * @param boolean $readonly In readonly mode?
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionCopyPerUser($id, $group = 'bucket', $readonly = false)
    {
        $model = $this->findModel($id);

        if ($model->isRecurrentInstance()) {
            $model = $model->getOriginalRecord();
        }

        $assignees = Yii::$app->request->getBodyParam('assignees', []);

        if (Yii::$app->request->isPost && !empty($assignees)) {
            foreach ($assignees as $assignee) {
                $newTask = new Task($model);
                $newTask->id = null;
                if (!$newTask->save()) {
                    continue;
                }
                foreach ($model->checklistElements as $element) {
                    $newElement = new ChecklistElement([
                        'task_id' => $newTask->id,
                        'name' => $element->name,
                        'sort' => $element->sort
                    ]);
                    $newElement->save();
                }
                foreach ($model->attachments as $attachment) {
                    $newAttachment = new Attachment([
                        'task_id' => $newTask->id,
                        'name' => $attachment->name,
                        'path' => $attachment->path,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'card_show' => $attachment->card_show
                    ]);
                    $newAttachment->save();
                }
                foreach ($model->links as $link) {
                    $newLink = new Link([
                        'task_id' => $newTask->id,
                        'url' => $link->url
                    ]);
                    $newLink->save();
                }
                $assignment = new TaskUserAssignment([
                    'task_id' => $newTask->id,
                    'user_id' => $assignee
                ]);
                $assignment->save();
            }

            switch ($group) {
                case 'bucket':
                    return $this->renderAjax('/bucket/view', [
                        'model' => $model->getBucket()->with(['openTasks'])->one(),
                        'statuses' => $this->module->statuses,
                        'users' => $this->module->users,
                        'finishedTasks' => $model->bucket->getTasks()->where(['status' => Task::STATUS_DONE])->count('id'),
                        'closeModal' => true,
                        'readonly' => $readonly
                    ]);
                case 'assignee':
                    return $this->redirect(['plan/view', 'id' => $model->board->id, 'group' => 'assignee']);
                case 'status':
                    $query = Task::find()
                        ->alias('t')
                        ->joinWith('assignments u')
                        ->joinWith('checklistElements')
                        ->joinWith('comments co')
                        ->innerJoinWith('bucket b')
                        ->with(['attachments', 'links'])
                        ->where([
                            '{{t}}.[[status]]' => $model->status,
                            '{{b}}.[[board_id]]' => $model->bucket->board_id
                        ]);
                    return $this->renderAjax('/bucket/view-status', [
                        'tasks' => $query->all(),
                        'status' => $model->status,
                        'statuses' => $this->module->statuses,
                        'users' => $this->module->users,
                        'closeModal' => true
                    ]);
            }
        }

        return $this->renderAjax('copy-per-user', [
            'model' => $model,
            'users' => $this->module->users,
            'group' => $group
        ]);
    }

    /**
     * Copy a task
     * @param integer $id Tasks id
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionCopy($id)
    {
        $task = $this->findModel($id);
        $model = new TaskCopyForm([
            'subject' => $task->subject,
            'bucketId' => $task->bucket_id
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $newTask = new Task([
                'subject' => $model->subject,
                'bucket_id' => $model->bucketId
            ]);
            if ($model->copyDescription) {
                $newTask->description = $task->description;
            }
            if ($model->copyDates) {
                $newTask->start_date = $task->start_date ? Yii::$app->formatter->asDate($task->start_date) : null;
                $newTask->end_date = $task->end_date ? Yii::$app->formatter->asDate($task->end_date) : null;
            }
            if ($model->copyStatus) {
                $newTask->status = $task->status;
            }

            $newTask->save();

            if ($model->copyAssignment) {
                foreach ($task->assignments as $assignment) {
                    $newAssignment = new TaskUserAssignment([
                        'task_id' => $newTask->id,
                        'user_id' => $assignment->user_id
                    ]);
                    $newTask->link('assignments', $newAssignment);
                }
            }
            if ($model->copyChecklist) {
                foreach ($task->checklistElements as $element) {
                    $newElement = new ChecklistElement([
                        'task_id' => $newTask->id,
                        'name' => $element->name,
                        'sort' => $element->sort
                    ]);
                    $newElement->save();
                }
            }
            if ($model->copyAttachments) {
                foreach ($task->attachments as $attachment) {
                    $newAttachment = new Attachment([
                        'task_id' => $newTask->id,
                        'name' => $attachment->name,
                        'path' => $attachment->path,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'card_show' => $attachment->card_show
                    ]);
                    $newAttachment->save();
                }
            }
            if ($model->copyLinks) {
                foreach ($task->links as $link) {
                    $newLink = new Link([
                        'task_id' => $newTask->id,
                        'url' => $link->url
                    ]);
                    $newLink->save();
                }
            }

            return $this->redirect(['plan/view', 'id' => $newTask->board->id, 'showTask' => $newTask->id]);
        }

        $module = $this->module;
        $boards = $module::getUserBoards();

        $buckets = [];
        foreach ($boards as $board) {
            $buckets[$board->name] = $board->getBuckets()
                ->select(['name', 'id'])
                ->orderBy(['name' => SORT_ASC])
                ->indexBy('id')
                ->column();
        }

        return $this->renderAjax('copy', [
            'model' => $model,
            'task' => $task,
            'buckets' => $buckets
        ]);
    }

    /**
     * Delete a task
     * @param integer $id Tasks id
     * @param boolean $deleteSeries Delete the whole series?
     *
     * @return string
     * @throws NotFoundHttpException|ForbiddenHttpException|\Throwable
     */
    public function actionDelete($id, $deleteSeries = false)
    {
        $model = $this->findModel($id);

        if ($deleteSeries) {
            $model = $model->getOriginalRecord();
        }

        if (Yii::$app->user->id != $model->created_by) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }

        try {
            $model->delete();
        } finally {
            // prevent error caused by ContinuousNumericalSortableBehavior
            Yii::$app->response->setStatusCode(204);
            return;
        }
    }

    /**
     * Set status of task and redirect back
     *
     * @param integer $id Tasks id
     * @param integer $status New status
     * @param boolean $readonly In read only mode?
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionSetStatus($id, $status, $readonly = false)
    {
        $model = $this->findModel($id);

        $model->status = $status;
        $model->save();

        $model->refresh();

        $this->setTicketStatus($model);

        $this->module->trigger(Module::EVENT_TASK_STATUS_CHANGED, new TaskEvent([
            'task' => $model,
            'data' => $status
        ]));

        if ($status == Task::STATUS_DONE) {
            $this->module->trigger(Module::EVENT_TASK_COMPLETED, new TaskEvent([
                'task' => $model
            ]));
        }

        if ($readonly) {
            $finishedTasks = $model->bucket->getTasks()
                ->alias('t')
                ->innerJoinWith('assignments a')
                ->where(['{{t}}.[[status]]' => Task::STATUS_DONE])
                ->andWhere(['{{a}}.[[user_id]]' => Yii::$app->user->id])
                ->count('{{t}}.[[id]]');
        } else {
            $finishedTasks = $model->bucket->getTasks()->where(['status' => Task::STATUS_DONE])->count('id');
        }

        return $this->renderAjax('/bucket/view', [
            'model' => $model->getBucket()->with(['openTasks'])->one(),
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'finishedTasks' => $finishedTasks,
            'closeModal' => true,
            'readonly' => $readonly
        ]);
    }

    /**
     * Set end date of task and redirect back
     *
     * @param integer $id Tasks id
     * @param integer $date New end date
     *
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionSetEndDate($id, $date)
    {
        $model = $this->findModel($id);

        $model->end_date = Yii::$app->formatter->asDate($date, 'dd.MM.yyyy');
        $model->save();

        Yii::$app->response->setStatusCode(204);
    }

    /**
     * Change task dates
     *
     * @param integer $id Tasks id
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionSetDates($id)
    {
        $model = $this->findModel($id);

        $startDate = Yii::$app->request->getBodyParam('startDate');
        $endDate = Yii::$app->request->getBodyParam('endDate');

        if ($startDate) {
            $model->start_date = Yii::$app->formatter->asDate($startDate, 'dd.MM.yyyy');
        }
        if ($endDate) {
            $model->end_date = Yii::$app->formatter->asDate($endDate, 'dd.MM.yyyy');
        }

        if ($model->save()) {
            Yii::$app->response->setStatusCode(204);
        }
    }

    /**
     * Assign user to task
     *
     * @param integer $id Tasks id
     * @param integer|string $userId Users id
     * @param boolean $readonly In read only mode?
     *
     * @return string
     * @throws NotFoundHttpException|Exception|ForbiddenHttpException
     */
    public function actionAssignUser($id, $userId, $readonly)
    {
        $model = $this->findModel($id);

        $assignment = new TaskUserAssignment();
        $assignment->task_id = $model->id;
        $assignment->user_id = $userId;
        $assignment->save();

        $model->refresh();

        $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
            'task' => $model,
            'user' => ArrayHelper::getValue($this->module->users, $userId)
        ]));

        return $this->renderAjax('item', [
            'model' => $model,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'closeModal' => false,
            'readonly' => $readonly
        ]);
    }

    /**
     * Assign user to task
     *
     * @param integer $id Tasks id
     * @param integer|string $userId Users id
     * @param boolean $readonly In read only mode?
     *
     * @return string
     * @throws NotFoundHttpException|Exception|ForbiddenHttpException
     */
    public function actionExpelUser($id, $userId, $readonly)
    {
        $model = $this->findModel($id);

        if (Yii::$app->user->id != $model->created_by) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }

        TaskUserAssignment::deleteAll([
            'task_id' => $model->id,
            'user_id' => $userId
        ]);

        $model->refresh();

        $this->module->trigger(Module::EVENT_TASK_UNASSIGNED, new TaskEvent([
            'task' => $model,
            'user' => ArrayHelper::getValue($this->module->users, $userId)
        ]));

        return $this->renderAjax('item', [
            'model' => $model,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'closeModal' => false,
            'readonly' => $readonly
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
        /** @var $model Task */
        if (($model = Task::find()->with('assignments')->where(['id' => $id])->one()) !== null) {
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

    /**
     * @param Task $model
     * @return void
     * @throws \Exception
     */
    protected function setTicketStatus(Task $model)
    {
        if ($model->ticket_id && ($ticket = $model->ticket)) {

            /** @var \simialbi\yii2\ticket\Module $module */
            if ($module = Yii::$app->getModule('ticket')) {
                $module->attachNotificationBehaviors(Topic::EVENT_ON_TICKET_RESOLUTION, $ticket);
            }

            switch ($model->status) {
                case Task::STATUS_IN_PROGRESS:
                    $ticket->status = Ticket::STATUS_IN_PROGRESS;
                    break;
                case Task::STATUS_NOT_BEGUN:
                default:
                    $ticket->status = Ticket::STATUS_OPEN;
                    break;
                case Task::STATUS_DONE:
                    $ticket->status = Ticket::STATUS_RESOLVED;
                    break;
            }

            $ticket->save();
        }
    }
}
