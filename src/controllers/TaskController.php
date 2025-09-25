<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\models\Attachment;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\ChecklistElement;
use simialbi\yii2\kanban\models\Comment;
use simialbi\yii2\kanban\models\Link;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskCopyForm;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\kanban\models\TimeWindow;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\TaskEvent;
use simialbi\yii2\ticket\models\Ticket;
use Yii;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'create',
                            'create-sub-task',
                            'update',
                            'copy',
                            'copy-per-user',
                            'move',
                            'delete',
                            'set-status',
                            'set-end-date',
                            'set-dates',
                            'assign-user',
                            'expel-user',
                            'list-clients',
                            'todo'
                        ],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'view',
                            'view-completed',
                            'view-delegated',
                            'view-responsible',
                            'history',
                            'detail'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Render task item
     *
     * @param int $id Tasks id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
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
     *
     * @param int $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionHistory(int $id): string
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
     * View delegated
     *
     * @param string $view
     *
     * @return string
     */
    public function actionViewDelegated(string $view = 'task'): string
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
    public function actionViewResponsible(): string
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
        ]);
    }

    /**
     * Renders details of Task
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionDetail(): string
    {
        $id = Yii::$app->request->post('expandRowKey');
        $model = $this->findModel($id);

        return $this->renderPartial('detail', [
            'model' => $model,
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Create a new task
     *
     * @param int $boardId
     * @param int|null $bucketId
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionCreate(int $boardId, ?int $bucketId): string
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
            'task' => $task,
            'buckets' => $buckets,
            'users' => $this->module->users
        ]);
    }

    /**
     * Create a sub task for a certain task
     *
     * @param int $id The parent task's id
     *
     * @return string|Response
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     * @throws \Exception
     */
    public function actionCreateSubTask(int $id): string|Response
    {
        $parent = $this->findModel($id);
        $child = new Task([
            'parent_id' => $parent->id,
            'root_parent_id' => $parent->root_parent_id ?? $parent->id,
            'bucket_id' => $parent->bucket_id,
            'description' => $parent->description,
            'start_date' => Yii::$app->formatter->asDate('today', 'php:d.m.Y'),
        ]);

        if ($child->load(Yii::$app->request->post()) && $child->save()) {
            $this->module->trigger(Module::EVENT_TASK_CREATED, new TaskEvent([
                'task' => $child
            ]));

            $assignees = Yii::$app->request->getBodyParam('assignees', []);
            foreach ($assignees as $assignee) {
                $assignment = new TaskUserAssignment();
                $assignment->task_id = $child->id;
                $assignment->user_id = $assignee;
                $assignment->save();
                $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
                    'task' => $child,
                    'user' => ArrayHelper::getValue($this->module->users, $assignee)
                ]));
            }

            return $this->redirect(['plan/view', 'id' => $child->board->id, 'showTask' => $child->id]);
        }

        return $this->renderAjax('create-sub-task', [
            'model' => $child,
            'users' => $this->module->users,
            'buckets' => Bucket::getSelect2Options(),
            'statuses' => $this->module->statuses
        ]);
    }

    /**
     * Update a task
     *
     * @param int $id Tasks id
     * @param bool $updateSeries Update the series?
     * @param string $return What to render back
     * @param bool $readonly If readonly mode?
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     * @throws Exception
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionUpdate(int $id, bool $updateSeries = false, string $return = 'card', bool $readonly = false): string
    {
        $model = $this->findModel($id);

        if ($updateSeries) {
            $model = $model->getOriginalRecord();
        }

        $timeWindows = TimeWindow::find()
            ->where([
                'task_id' => $model->id,
                'user_id' => Yii::$app->user->id
            ])
            ->orderBy(['time_start' => SORT_ASC])
            ->all();

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

            $standaloneInstance = $model->isRecurrentInstance() && $model->id !== $model->getOriginalRecord()->id;
            if ($standaloneInstance) {
                // merge in new where possible to ensure that we copy all relations

                $newChecklistElements = ArrayHelper::merge(
                    $checklistElements,
                    $newChecklistElements
                );
                $checklistElements = [];

                $newLinkElements = ArrayHelper::merge(
                    $links,
                    $newLinkElements
                );
                $links = [];

                foreach ($model->attachments as $attachment) {
                    $newAttachments[] = new UploadedFile([
                        'name' => $attachment->name,
                        'tempName' => FileHelper::normalizePath(Yii::getAlias(
                            "{$this->module->uploadWebRoot}/task/{$model->getOriginalRecord()->id}/{$attachment->name}"
                        )),
                        'type' => $attachment->mime_type,
                        'size' => $attachment->size,
                    ]);
                }
                unset($attachment);
            }


            /**
             * CHECKLIST ELEMENTS
             */
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


            /**
             * ASSIGNEES
             */
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


            /**
             * COMMENTS
             */
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


            /**
             * LINKS
             */
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


            /**
             * ATTACHMENTS
             */
            Attachment::loadMultiple($model->attachments, Yii::$app->request->post());
            foreach ($model->attachments as $attachment) {
                $attachment->save();
            }

            if (!empty($newAttachments)) {
                $path = Yii::getAlias($this->module->uploadWebRoot . '/task/' . $model->id);
                if (FileHelper::createDirectory($path)) {
                    foreach ($newAttachments as $uploadedFile) {
                        FileHelper::renameFileIfExists($uploadedFile, $path);
                        $filePath = $path . DIRECTORY_SEPARATOR . $uploadedFile->baseName . '.' . $uploadedFile->extension;
                        if (!$uploadedFile->saveAs($filePath, !$standaloneInstance)) {
                            continue;
                        }
                        $attachment = new Attachment([
                            'task_id' => $model->id,
                            'name' => $uploadedFile->name,
                            'mime_type' => $uploadedFile->type,
                            'size' => $uploadedFile->size,
                            'path' => Yii::getAlias($this->module->uploadWeb . '/task/' . $model->id . '/' . $uploadedFile->baseName . '.' . $uploadedFile->extension)
                        ]);
                        $attachment->save();

                        $this->module->trigger(Module::EVENT_ATTACHMENT_ADDED, new TaskEvent([
                            'task' => $model,
                            'data' => $attachment
                        ]));
                    }
                }
            }

            /**
             * TIME WINDOWS
             */
            $savedIds = ArrayHelper::getColumn($timeWindows, 'id');
            $receivedIds = ArrayHelper::getColumn(Yii::$app->request->getBodyParam('TimeWindow', []), 'id');
            $timesToDelete = TimeWindow::find()
                ->where([
                    'in',
                    'id',
                    array_diff($savedIds, $receivedIds)
                ])
                ->all();
            foreach ($timesToDelete as $timeToDelete) {
                $timeToDelete->delete();
            }

            $tWs = Yii::$app->request->getBodyParam('TimeWindow', []);
            for ($i = 0; $i < count($tWs); $i++) {
                $id = ArrayHelper::getValue($tWs[$i], 'id', 0);
                $data = $tWs[$i];

                if ($id > 0) {
                    $timeWindow = TimeWindow::findOne($id);
                    $timeWindow->setAttributes($data);
                } else {
                    $data = ArrayHelper::merge($data, [
                        'task_id' => $model->id,
                        'user_id' => Yii::$app->user->id
                    ]);
                    $timeWindow = new TimeWindow($data);
                }

                $timeWindow->save();
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
                'task' => clone $model,
                'data' => clone $model,
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
            } elseif ($return === 'calendar') {
                return $this->renderAjax('/task/calendar', [
                    'task' => $model,
                    'kanbanModuleName' => $this->module->id,
                ]);
            }

            $model = $this->findModel($model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id);

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

        if ($model->start_date !== null) {
            $model->start_date = Yii::$app->formatter->asDate($model->start_date);
        }
        if ($model->end_date !== null) {
            $model->end_date = Yii::$app->formatter->asDate($model->end_date);
        }

        $statuses = $this->module->statuses;
        unset($statuses[Task::STATUS_LATE]);

        foreach ($timeWindows as $timeWindow) {
            $timeWindow->time_start = Yii::$app->formatter->asDate($timeWindow->time_start, 'php:d.m.Y H:i');
            $timeWindow->time_end = Yii::$app->formatter->asDate($timeWindow->time_end, 'php:d.m.Y H:i');
        }
        if (empty($timeWindows)) {
            $timeWindows = [
                new TimeWindow([
                    'task_id' => $model->id,
                    'user_id' => Yii::$app->user->id
                ])
            ];
        }

        $checklistTemplateModels = $model->board->getChecklistTemplates()
            ->innerJoinWith('elements', false)
            ->all();
        $checklistTemplates = [];
        foreach ($checklistTemplateModels as $checklistTemplate) {
            $checklistTemplates[] = [
                'label' => $checklistTemplate->name,
                'url' => '#',
                'linkOptions' => [
                    'onclick' => 'window.sa.kanban.loadChecklistTemplate(' . $checklistTemplate->id . ');'
                ]
            ];
        }

        return $this->renderAjax('update', [
            'model' => $model,
            'buckets' => $buckets,
            'users' => $this->module->users,
            'updateSeries' => $updateSeries,
            'statuses' => $statuses,
            'return' => $return,
            'readonly' => $readonly,
            'timeWindows' => $timeWindows,
            'checklistTemplates' => $checklistTemplates
        ]);
    }

    /**
     * Copy a task for each user
     *
     * @param string $id The id of the task
     * @param string $group In which view are we?
     * @param bool $readonly In readonly mode?
     *
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionCopyPerUser(string $id, string $group = 'bucket', bool $readonly = false): Response|string
    {
        $model = $this->findModel($id);

        if ($model->isRecurrentInstance()) {
            $model = $model->getOriginalRecord();
        }

        $assignees = Yii::$app->request->getBodyParam('assignees', []);

        if (Yii::$app->request->isPost && !empty($assignees)) {
            $transaction = Yii::$app->db->beginTransaction();

            foreach ($assignees as $assignee) {
                $newTask = new Task($model->attributes);
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
                        'path' => Yii::getAlias($this->module->uploadWeb . '/task/' . $newTask->id . '/' . $attachment->name),
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'card_show' => $attachment->card_show
                    ]);

                    $src = Yii::getAlias($this->module->uploadWebRoot . '/task/' . $model->id);
                    $dest = Yii::getAlias($this->module->uploadWebRoot . '/task/' . $newTask->id);

                    if (FileHelper::createDirectory($dest) && copy($src . '/' . $attachment->name, $dest . '/' . $attachment->name)) {
                        $newAttachment->save();
                    }
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
                if ($assignment->save()) {
                    $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
                        'task' => $newTask,
                        'user' => Yii::$app->user->identityClass::findIdentity($assignee)
                    ]));
                }
            }

            $transaction->commit();

            switch ($group) {
                case 'bucket':
                    return $this->redirect(['plan/view', 'id' => $model->board->id]);
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
     *
     * @param int $id Tasks id
     *
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws InvalidConfigException|Exception
     */
    public function actionCopy(int $id): Response|string
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
                        'path' => Yii::getAlias($this->module->uploadWeb . '/task/' . $newTask->id . '/' . $attachment->name),
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'card_show' => $attachment->card_show
                    ]);

                    $src = Yii::getAlias($this->module->uploadWebRoot . '/task/' . $task->id);
                    $dest = Yii::getAlias($this->module->uploadWebRoot . '/task/' . $newTask->id);

                    if (FileHelper::createDirectory($dest) && copy($src . '/' . $attachment->name, $dest . '/' . $attachment->name)) {
                        $newAttachment->save();
                    }
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

        $boards = $this->module::getUserBoards();
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
            'buckets' => $buckets
        ]);
    }

    /**
     * Move a task to a different board / bucket
     *
     * @param int $id Task id to move
     *
     * @return string|Response
     * @throws NotFoundHttpException|\yii\db\Exception
     */
    public function actionMove(int $id): string|Response
    {
        $task = $this->findModel($id);
        $model = new DynamicModel([
            'bucketId' => 0
        ]);
        $model
            ->addRule('bucketId', 'required')
            ->addRule('bucketId', 'int')
            ->addRule('bucketId', 'exist', [
                'targetClass' => Bucket::class,
                'targetAttribute' => 'id'
            ])
            ->setAttributeLabel('bucketId', Yii::t('simialbi/kanban', 'Bucket'));

        // Save
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $task->bucket_id = $model->bucketId;
            $task->save(['bucket_id']);
            $task->refresh();

            return $this->redirect(['plan/view', 'id' => $task->board->id]);
        }

        $boards = $this->module::getUserBoards();
        $buckets = [];
        foreach ($boards as $board) {
            $buckets[$board->name] = $board->getBuckets()
                ->select(['name', 'id'])
                ->orderBy(['name' => SORT_ASC])
                ->indexBy('id')
                ->column();
        }

        return $this->renderAjax('move', [
            'model' => $model,
            'buckets' => $buckets
        ]);
    }

    /**
     * Delete a task
     *
     * @param int $id Tasks id
     * @param bool $deleteSeries Delete the whole series?
     *
     * @return void
     * @throws NotFoundHttpException|ForbiddenHttpException|\Throwable
     */
    public function actionDelete(int $id, bool $deleteSeries = false): void
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
     * @param int $id Tasks id
     * @param int $status New status
     * @param bool $readonly In read only mode?
     * @param string $return
     *
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionSetStatus(int $id, int $status, bool $readonly = false, string $return = 'bucket'): string
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

        if ($status === Task::STATUS_DONE) {
            $this->module->trigger(Module::EVENT_TASK_COMPLETED, new TaskEvent([
                'task' => $model
            ]));
        }

        if ($readonly) {
            $finishedTasks = $model->bucket->getTasks()
                ->innerJoinWith('assignments a')
                ->where(['[[status]]' => Task::STATUS_DONE])
                ->andWhere(['{{a}}.[[user_id]]' => Yii::$app->user->id])
                ->count(Task::tableName() . '.[[id]]');
        } else {
            $finishedTasks = $model->bucket->getTasks()->where(['status' => Task::STATUS_DONE])->count('id');
        }

        if ($return === 'update') {
            Yii::$app->request->setBodyParams([]);

            return $this->actionUpdate($id);
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
     * @param int $id Tasks id
     * @param int $date New end date
     *
     * @throws NotFoundHttpException
     * @throws InvalidConfigException|\yii\db\Exception
     */
    public function actionSetEndDate(int $id, int $date): void
    {
        $model = $this->findModel($id);

        $model->end_date = Yii::$app->formatter->asDate($date, 'dd.MM.yyyy');
        $model->save();

        Yii::$app->response->setStatusCode(204);
    }

    /**
     * Change task dates
     *
     * @param int $id Tasks id
     *
     * @throws NotFoundHttpException
     * @throws InvalidConfigException|\yii\db\Exception
     */
    public function actionSetDates(int $id): void
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
     * @param int $id Tasks id
     * @param int|string $userId Users id
     * @param bool $readonly In read only mode?
     *
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionAssignUser(int $id, int|string $userId, bool $readonly): string
    {
        $model = $this->findModel($id);

        $assignment = new TaskUserAssignment();
        $assignment->task_id = $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id;
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
     * @param int $id Tasks id
     * @param int|string $userId Users id
     * @param bool $readonly In read only mode?
     *
     * @return string
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionExpelUser(int $id, int|string $userId, bool $readonly): string
    {
        $model = $this->findModel($id);

        if (Yii::$app->user->id != $model->created_by) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }

        TaskUserAssignment::deleteAll([
            'task_id' => $model->isRecurrentInstance() ? $model->recurrence_parent_id : $model->id,
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
     * Action to render the to-do list.
     * Can be used if you want to re-render the widget via setting the src attribute of the turbo-frame
     *
     * @param bool|null $renderModal
     * @param bool|null $addBoardFilter
     * @param int|null $cacheDuration
     *
     * @return string
     */
    public function actionTodo(?bool $renderModal = null, ?bool $addBoardFilter = null, ?int $cacheDuration = null): string
    {
        return $this->renderAjax('todo', [
            'kanbanModuleName' => $this->module->id,
            'renderModal' => $renderModal,
            'addBoardFilter' => $addBoardFilter,
            'cacheDuration' => $cacheDuration
        ]);
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): Task
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
     * @param int $id
     *
     * @return Board the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findBoardModel(int $id): Board
    {
        if (($model = Board::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    /**
     * Update ticket status if task belongs to a ticket
     *
     * @param Task $model
     *
     * @return void
     * @throws \Exception
     */
    protected function setTicketStatus(Task $model): void
    {
        if ($model->ticket_id && ($ticket = $model->ticket)) {

            /** @var \simialbi\yii2\ticket\Module $module */
            if ($module = Yii::$app->getModule('ticket')) {
                $module->attachNotificationBehaviors($module::EVENT_TICKET_RESOLVED, $ticket);
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
