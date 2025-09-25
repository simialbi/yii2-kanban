<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use DateTime;
use DateTimeZone;
use Exception;
use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Rule;
use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\behaviors\RepeatableBehavior;
use simialbi\yii2\kanban\enums\ConnectionTypeEnum;
use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\RecurrenceValidator;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\ticket\models\Ticket;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\behaviors\AttributeTypecastBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\bootstrap5\Html;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\validators\Validator;

/**
 * Class Task
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $bucket_id
 * @property int $ticket_id
 * @property int|string $client_id
 * @property int $parent_id
 * @property int $root_parent_id
 * @property int|string $responsible_id
 * @property string $subject
 * @property int $status
 * @property int|string|DateTime $start_date
 * @property int|string|DateTime $end_date
 * @property string|Rule $recurrence_pattern
 * @property int $recurrence_parent_id
 * @property bool $is_recurring
 * @property string|null $description
 * @property bool $card_show_description
 * @property bool $card_show_checklist
 * @property bool $card_show_links
 * @property int $sort
 * @property int|string $created_by
 * @property int|string $updated_by
 * @property int|string $finished_by
 * @property int|string $created_at
 * @property int|string $updated_at
 * @property int|string $finished_at
 * @property int $sync_id
 * @property int $connection_id
 *
 * @method bool isRecurrentInstance() If this model is an instance of an recurrent task
 * @method self getOriginalRecord() The original record if model is recurrent instance
 *
 * @property-read string $hash
 * @property-read string $checklistStats
 * @property-read string|null $endDate
 * @property-read string $sharePointUrl
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read UserInterface $finisher
 * @property-read UserInterface[] $assignees
 * @property-read TaskUserAssignment[] $assignments
 * @property-read Bucket $bucket
 * @property-read Board $board
 * @property-read ChecklistElement[] $checklistElements
 * @property-read Link[] $links
 * @property-read Attachment[] $attachments
 * @property-read Comment[] $comments
 * @property-read Ticket $ticket
 * @property-read Task $recurrenceParent
 * @property-read UserInterface $client
 * @property-read UserInterface $responsible
 * @property-read ConnectionInterface $connection
 * @property-read TimeWindow[] $timeWindows
 * @property-read Task $parent
 * @property-read Task $rootParent
 * @property-read Task[] $children
 */
class Task extends ActiveRecord
{
    public const EVENT_BEFORE_FINISH = 'beforeFinish';
    public const EVENT_AFTER_FINISH = 'afterFinish';

    public const STATUS_DONE = 0;
    public const STATUS_IN_PROGRESS = 5;
    public const STATUS_NOT_BEGUN = 10;
    public const STATUS_LATE = 15;


    /**
     * @var string|null Hash
     */
    private ?string $_hash = null;

    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__task}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [
                [
                    'id',
                    'bucket_id',
                    'ticket_id',
                    'parent_id',
                    'root_parent_id',
                    'status',
                    'recurrence_parent_id',
                    'connection_id'
                ],
                'integer'
            ],
            [['subject', 'sync_id'], 'string', 'max' => 255],
            [['responsible_id', 'client_id'], 'string', 'max' => 64],
            [['responsible_id', 'client_id'], 'default', 'value' => null],
            ['status', 'in', 'range' => array_keys(Module::getInstance()->statuses)],
            ['start_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'start_date'],
            ['end_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'end_date'],
            [['description'], 'string'],
            [['card_show_description', 'card_show_checklist', 'card_show_links', 'is_recurring'], 'boolean'],

            [
                'recurrence_pattern',
                'validateRecurrence',
                'when' => function ($model) {
                    /** @var static $model */
                    return $model->is_recurring;
                }
            ],
            [
                'recurrence_pattern',
                'filter',
                'filter' => function () {
                    return null;
                },
                'when' => function ($model) {
                    /** @var static $model */
                    return !$model->is_recurring;
                }
            ],
            [
                // client side validation of recurrence
                'is_recurring',
                RecurrenceValidator::class,
                'message' => Yii::t('simialbi/kanban/model/task', 'Recurrence is invalid'),
                'when' => function () {
                    return false;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#" . Html::getInputId($this, 'is_recurring') . "').is(':checked');
                }"
            ],

            [
                ['bucket_id', 'ticket_id', 'client_id', 'responsible_id', 'status'],
                'filter',
                'filter' => 'intval',
                'skipOnEmpty' => true
            ],

            ['status', 'default', 'value' => self::STATUS_NOT_BEGUN],
            [
                ['card_show_description', 'card_show_checklist', 'card_show_links', 'is_recurring'],
                'default',
                'value' => false
            ],

            [
                'start_date',
                'required',
                'when' => function ($model) {
                    /** @var static $model */
                    return $model->is_recurring;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#" . Html::getInputId($this, 'is_recurring') . "').is(':checked');
                }"
            ],
            [['start_date', 'end_date', 'description'], 'default'],

            [['bucket_id', 'subject', 'status', 'card_show_description', 'card_show_checklist'], 'required'],

            ['parent_id', 'exist', 'targetRelation' => 'parent'],
            ['root_parent_id', 'exist', 'targetRelation' => 'rootParent'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_by', 'updated_by'],
                    self::EVENT_BEFORE_UPDATE => 'updated_by',
                    self::EVENT_BEFORE_FINISH => 'finished_by'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                    self::EVENT_BEFORE_FINISH => 'finished_at'
                ]
            ],
            'sortable' => [
                'class' => ContinuousNumericalSortableBehavior::class,
                'prependAdded' => true,
                'sortAttribute' => 'sort',
                'scope' => function () {
                    return Task::find()->where(['bucket_id' => $this->bucket_id]);
                }
            ],
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'recurrence_pattern' => function ($value) {
                        if ($value === null) {
                            return null;
                        }
                        if (is_string($value)) {
                            $start = (empty($this->start_date)) ? $this->created_at : $this->start_date;

                            return Rule::createFromString(
                                $value,
                                Yii::$app->formatter->asDatetime($start, 'yyyy-MM-dd HH:mm:ss'),
                                $this->end_date
                                    ? Yii::$app->formatter->asDate($this->end_date, 'yyyy-MM-dd HH:mm:ss')
                                    : null,
                                Yii::$app->timeZone
                            );
                        }

                        return $value;
                    }
                ],
                'typecastAfterValidate' => false,
                'typecastBeforeSave' => false,
                'typecastAfterFind' => true
            ],
            'typecast2' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'card_show_description' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'card_show_checklist' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'card_show_links' => AttributeTypecastBehavior::TYPE_INTEGER
                ],
                'typecastAfterValidate' => true,
                'typecastBeforeSave' => false,
                'typecastAfterFind' => false
            ],
            'repeatable' => [
                'class' => RepeatableBehavior::class,
                'keepRelations' => [
                    'assignees',
                    'assignments',
                    'attachments',
                    'bucket',
                    'checklistElements',
                    'client',
                    'links',
                    'responsible',
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/task', 'Id'),
            'bucket_id' => Yii::t('simialbi/kanban/model/task', 'Bucket'),
            'board_id' => Yii::t('simialbi/kanban/model/task', 'Board'),
            'client_id' => Yii::t('simialbi/kanban/model/task', 'Client'),
            'assignee_id' => Yii::t('simialbi/kanban/model/task', 'Assignee'),
            'responsible_id' => Yii::t('simialbi/kanban/model/task', 'Responsible'),
            'subject' => Yii::t('simialbi/kanban/model/task', 'Subject'),
            'status' => Yii::t('simialbi/kanban/model/task', 'Status'),
            'start_date' => Yii::t('simialbi/kanban/model/task', 'Start date'),
            'end_date' => Yii::t('simialbi/kanban/model/task', 'End date'),
            'is_recurring' => Yii::t('simialbi/kanban/model/task', 'Is recurring'),
            'recurrence_pattern' => Yii::t('simialbi/kanban/model/task', 'Recurrence'),
            'description' => Yii::t('simialbi/kanban/model/task', 'Description'),
            'card_show_description' => Yii::t('simialbi/kanban/model/task', 'Show description on card'),
            'card_show_checklist' => Yii::t('simialbi/kanban/model/task', 'Show checklist on card'),
            'card_show_links' => Yii::t('simialbi/kanban/model/task', 'Show links on card'),
            'sort' => Yii::t('simialbi/kanban/model/task', 'Sort'),
            'created_by' => Yii::t('simialbi/kanban/model/task', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/task', 'Updated by'),
            'finished_by' => Yii::t('simialbi/kanban/model/task', 'Finished by'),
            'created_at' => Yii::t('simialbi/kanban/model/task', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/task', 'Updated at'),
            'finished_at' => Yii::t('simialbi/kanban/model/task', 'Finished at')
        ];
    }

    /**
     * Recurrence validator and transform to string
     *
     * @param string $attribute The attribute name
     * @param array|null $params
     * @param Validator $validator
     *
     * @throws InvalidArgument|InvalidRRule|InvalidConfigException
     * @throws Exception
     */
    public function validateRecurrence(string $attribute, ?array $params, Validator $validator): void
    {
        if (is_array($this->$attribute) && ArrayHelper::isAssociative($this->$attribute)) {
            $rule = new Rule();
            $rule->setStartDate(new DateTime(
                Yii::$app->formatter->asDatetime($this->start_date, 'yyyy-MM-dd HH:mm:ss'),
                new DateTimeZone(Yii::$app->timeZone)
            ));
            $rule->setTimezone(Yii::$app->timeZone);
            if (isset($this->{$attribute}['FREQ'])) {
                $rule->setFreq($this->{$attribute}['FREQ']);
            }
            if (isset($this->{$attribute}['INTERVAL'])) {
                $rule->setInterval($this->{$attribute}['INTERVAL']);
            }
            if (isset($this->{$attribute}['BYDAY'])) {
                if (is_array($this->{$attribute}['BYDAY']) && ArrayHelper::isAssociative($this->{$attribute}['BYDAY'])) {
                    $byDay = [$this->{$attribute}['BYDAY']['int'] . $this->{$attribute}['BYDAY']['string']];
                } else {
                    $byDay = (array)$this->{$attribute}['BYDAY'];
                }
                $rule->setByDay($byDay);
            }
            if (isset($this->{$attribute}['BYMONTHDAY'])) {
                $rule->setByMonthDay((array)$this->{$attribute}['BYMONTHDAY']);
            }
            if (isset($this->{$attribute}['BYMONTH'])) {
                $rule->setByMonth((array)$this->{$attribute}['BYMONTH']);
            }

            $this->{$attribute} = $rule->getString();
        } elseif ($this->$attribute instanceof Rule) {
            $this->{$attribute} = $this->{$attribute}->getString();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave($insert): bool
    {
        if ($this->isAttributeChanged('status') && (int)$this->status === self::STATUS_DONE) {
            if (!$this->beforeFinish()) {
                return false;
            }
        }

        return parent::beforeSave($insert);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        try {
            /** @var Module $module */
            $module = Yii::$app->getModule('schedule');

            // Delete attachments
            FileHelper::removeDirectory(Yii::getAlias($module->uploadWebRoot . '/task/' . $this->id));

            // Delete time windows
            $timeWindows = TimeWindow::find()
                ->where([
                    'task_id' => $this->id,
                    'user_id' => Yii::$app->user->id
                ])
                ->all();
            foreach ($timeWindows as $timeWindow) {
                $timeWindow->delete();
            }

            // Update children
            foreach ($this->children as $child) {
                $this->updateChildrenOnParentDelete($child, $this->parent_id, $this->root_parent_id);
            }
        } catch (ErrorException $e) {
            Yii::error('Could not delete folder: ' . $e->getMessage());
        } catch (StaleObjectException|\Throwable $e) {
            Yii::error('Could not delete outlook event: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * This method is called before a task will be finished.
     *
     * The default implementation will trigger an [[EVENT_BEFORE_FINISH]] event.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeFinish()
     * {
     *     if (!parent::beforeFinish()) {
     *         return false;
     *     }
     *
     *     // ...custom code here...
     *     return true;
     * }
     * ```
     *
     * @return bool whether the insertion or updating should continue.
     * If `false`, the insertion or updating will be cancelled.
     */
    public function beforeFinish(): bool
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_FINISH, $event);

        return $event->isValid;
    }

    /**
     * {@inheritDoc}
     */
    public function afterSave($insert, $changedAttributes): void
    {
        if (isset($changedAttributes['status']) && (int)$this->status === self::STATUS_DONE) {
            $this->afterFinish($changedAttributes);
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * This method is called after finishing a task.
     * The default implementation will trigger an [[EVENT_AFTER_FINISH]] event.
     * The event class used is [[AfterSaveEvent]]. When overriding this method, make sure you call the
     * parent implementation so that the event is triggered.
     *
     * @param array $changedAttributes The old values of attributes that had changed and were saved.
     * You can use this parameter to take action based on the changes made for example send an email
     * when the password had changed or implement audit trail that tracks all the changes.
     * `$changedAttributes` gives you the old attribute values while the active record (`$this`) has
     * already the new, updated values.
     *
     * Note that no automatic type conversion performed by default. You may use
     * [[\yii\behaviors\AttributeTypecastBehavior]] to facilitate attribute typecasting.
     * See http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#attributes-typecasting.
     */
    public function afterFinish(array $changedAttributes): void
    {
        $this->trigger(self::EVENT_AFTER_FINISH, new AfterSaveEvent([
            'changedAttributes' => $changedAttributes,
        ]));
    }

    /**
     * Generate unique hash per task
     * @return string
     */
    public function getHash(): string
    {
        if (!$this->_hash) {
            $string = $this->id . $this->bucket_id . $this->status . $this->end_date . $this->subject;
            $this->_hash = md5($string);
        }

        return $this->_hash;
    }

    /**
     * Get checklist status information
     * @return string
     * @throws Exception
     */
    public function getChecklistStats(): string
    {
        if (empty($this->checklistElements)) {
            return '';
        }

        $grouped = ArrayHelper::index($this->checklistElements, null, 'is_done');
        $done = count(ArrayHelper::getValue($grouped, '1', []));
        $all = count($this->checklistElements);

        return "$done/$all";
    }

    /**
     * Get the end date, either from task or checklist element
     * @return DateTime|int|string|null
     * @throws Exception
     */
    public function getEndDate(): DateTime|int|string|null
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        if (empty($this->checklistElements)) {
            return null;
        }
        /** @var ChecklistElement[] $checklistElements */
        $grouped = ArrayHelper::index(
            array_filter($this->checklistElements, function ($item) {
                return $item->end_date != null;
            }),
            null,
            'is_done'
        );
        $checklistElements = ArrayHelper::getValue($grouped, '0', []);
        if (empty($checklistElements)) {
            return null;
        }
        ArrayHelper::multisort($checklistElements, 'end_date', SORT_ASC, SORT_NUMERIC);

//        echo "<pre>"; var_dump(ArrayHelper::toArray($grouped, $checklistElements)); exit;

        return $checklistElements[0]->end_date;
    }

    /**
     * Get sharepoint url of customer dossier
     * @return string|false
     * @throws Exception
     */
    public function getSharePointUrl(): string|false
    {
        return $this->client?->sharePointUrl ?? false;
    }

    /**
     * Get associated checklist elements
     * @return ActiveQuery
     */
    public function getChecklistElements(): ActiveQuery
    {
        return $this->hasMany(ChecklistElement::class, ['task_id' => 'id'])
            ->orderBy([ChecklistElement::tableName() . '.[[sort]]' => SORT_ASC]);
    }

    /**
     * Get author
     * @return UserInterface
     * @throws Exception
     */
    public function getAuthor(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->created_by);
    }

    /**
     * Get user last updated
     * @return UserInterface
     * @throws Exception
     */
    public function getUpdater(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->updated_by);
    }

    /**
     * Get user finished
     * @return UserInterface
     * @throws Exception
     */
    public function getFinisher(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->finished_by);
    }

    /**
     * Get users assigned to this task
     * @return array
     * @throws Exception
     */
    public function getAssignees(): array
    {
        $allAssignees = Module::getInstance()->users;

        $assignees = [];
        foreach ($this->assignments as $assignment) {
            $item = ArrayHelper::getValue($allAssignees, $assignment->user_id);
            if ($item) {
                $assignees[] = $item;
            }
        }

        return $assignees;
    }

    /**
     * Get assigned user id's
     * @return ActiveQuery
     */
    public function getAssignments(): ActiveQuery
    {
        return $this->hasMany(TaskUserAssignment::class, ['task_id' => 'id']);
    }

    /**
     * Get associated bucket
     * @return ActiveQuery
     */
    public function getBucket(): ActiveQuery
    {
        return $this->hasOne(Bucket::class, ['id' => 'bucket_id']);
    }

    /**
     * Get associated board
     * @return ActiveQuery
     */
    public function getBoard(): ActiveQuery
    {
        return $this->hasOne(Board::class, ['id' => 'board_id'])->via('bucket');
    }

    /**
     * Get associated links
     * @return ActiveQuery
     */
    public function getLinks(): ActiveQuery
    {
        return $this->hasMany(Link::class, ['task_id' => 'id']);
    }

    /**
     * Get associated attachments
     * @return ActiveQuery
     */
    public function getAttachments(): ActiveQuery
    {
        return $this->hasMany(Attachment::class, ['task_id' => 'id']);
    }

    /**
     * Get associated comments
     * @return ActiveQuery
     */
    public function getComments(): ActiveQuery
    {
        return $this->hasMany(Comment::class, ['task_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Get associated ticket
     * @return ActiveQuery
     */
    public function getTicket(): ActiveQuery
    {
        return $this->hasOne(Ticket::class, ['id' => 'ticket_id']);
    }

    /**
     * Get associated recurrence parent
     * @return ActiveQuery
     */
    public function getRecurrenceParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'recurrence_parent_id']);
    }

    /**
     * Get associated client
     * @return UserInterface
     * @throws Exception
     */
    public function getClient(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->client_id);
    }

    /**
     * Get responsible User
     * @return UserInterface
     * @throws Exception
     */
    public function getResponsible(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->responsible_id);
    }

    /**
     * Get related Connection.
     *
     * Model depends on the typo of connection.
     * @return ActiveQuery
     * @see ConnectionTypeEnum
     */
    public function getConnection(): ActiveQuery
    {
        $connection = Connection::findOne($this->connection_id);
        $class = ConnectionTypeEnum::tryFrom($connection->type)->getModel();

        return $this->hasOne($class, ['id' => 'connection_id']);
    }

    /**
     * Get related time windows
     * @return ActiveQuery
     */
    public function getTimeWindows(): ActiveQuery
    {
        return $this->hasMany(TimeWindow::class, ['task_id' => 'id']);
    }

    /**
     * Get parent task
     * @return ActiveQuery
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * Get root parent task
     * @return ActiveQuery
     */
    public function getRootParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'root_parent_id']);
    }

    /**
     * Get children tasks
     * @return ActiveQuery
     */
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(static::class, ['parent_id' => 'id']);
    }

    /**
     * Create a tree of subtasks
     * returns an array of this task with it's children and grandchildren etc
     * @return array
     */
    public function getTree(): array
    {
        $tasks = static::find()
            ->with('board', 'assignees')
            ->where([
                'or',
                ['root_parent_id' => $this->root_parent_id ?? $this->id],
                ['id' => $this->root_parent_id ?? $this->id]
            ])
            ->orderBy([
                'root_parent_id' => SORT_ASC,
                'parent_id' => SORT_ASC,
                'end_date' => SORT_DESC,
                'subject' => SORT_ASC
            ])
            ->all();

        $ret = [];

        // we use this array to store references to the parent tasks, so we can easily find them later
        $parents = [];
        foreach ($tasks as $i => $task) {
            if ($i == 0) {
                $ret['task'] = $task;
                $parent = &$ret;
                $parents[$task->id] = &$parent;
                continue;
            }

            // get parent
            $parent = &$parents[$task->parent_id];
            // add task to parent
            $parent['children'][$task->id]['task'] = $task;

            // add to parents array, if not exists
            if (!array_key_exists($task->id, $parents)) {
                $parents[$task->id] = &$parent['children'][$task->id];
            }
        }

        return $ret;
    }

    /**
     * Create a html tree of subtasks
     *
     * @param array $tree
     * @param array $statuses
     * @param ?Task $caller
     * @param int $level
     *
     * @return string
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function createHtmlTree(
        array $tree,
        array $statuses,
        ?Task $caller,
        int   $level = 0
    ): string
    {
        $children = ArrayHelper::getValue($tree, 'children', []);
        $ident = str_repeat('&nbsp;', $level * 4);
        if ($level > 0) {
            $ident .= FAS::i('arrow-turn-down-right')->fixedWidth();
        }

        $html = '';
        if ($level == 0) {
            $assigneeText = Yii::t('simialbi/kanban/plan', 'Assigned to');
            $actionLabel = Yii::t('simialbi/kanban/task', 'Actions');
            $html = "<thead>
                        <tr>
                            <th>{$this->getAttributeLabel('subject')}</th>
                            <th>{$this->getAttributeLabel('status')}</th>
                            <th>{$this->getAttributeLabel('end_date')}</th>
                            <th>$assigneeText</th>
                            <th>$actionLabel</th>
                        </tr>
                    </thead>";
        }

        /** @var Task $task */
        $task = $tree['task'];
        $class = ($task->id == $caller->id) ? 'table-info' : '';
        $endDate = $task->endDate ? Yii::$app->formatter->asDate($task->endDate) : '';
        $assignees = '';
        foreach ($task->assignees as $assignee) {
            $assignees .= "<img src='$assignee->image' class='rounded-circle me-2' title='$assignee->name' alt='$assignee->name'>";
        }

        $view = Html::a(
            FAS::i('eye')->fixedWidth(),
            // set return to to-do if task is in another board
            ['task/update', 'id' => $task->id, 'return' => ($task->board->id == $caller->board->id) ? null : 'todo'],
            [
                'class' => 'text-decoration-none',
                'data' => [
                    'turbo-frame' => 'task-modal-frame'
                ]
            ]
        );
        $finish = Html::a(
            FAR::i('circle-check', ['class' => 'ms-1'])->fixedWidth(),
            ['task/set-status', 'id' => $task->id, 'status' => self::STATUS_DONE, 'return' => 'update'],
            [
                'class' => 'text-decoration-none',
                'data' => [
                    'turbo-frame' => 'task-modal-frame'
                ]
            ]
        );
        $actions = '';
        if ($task->canEdit()) {
            $actions .= $view;
            if ($task->status != self::STATUS_DONE) {
                $actions .= $finish;
            }
        }

        $html .= "<tr class='$class'>";
        $html .= "<td><span title='{$task->board->name} - {$task->bucket->name}'>$ident$task->subject</span></td>";
        $html .= "<td>{$statuses[$task->status]}</td>";
        $html .= "<td>$endDate</td>";
        $html .= "<td>$assignees</td>";
        $html .= "<td style='width: 35px;'>$actions</td>";
        $html .= "</tr>";

        foreach ($children as $child) {
            $html .= $this->createHtmlTree($child, $statuses, $caller, $level + 1);
        }

        if ($level == 0) {
            return "<table class='tree-table table table-bordered table-sm table-striped'>$html</table>";
        }

        return $html;
    }

    /**
     * Use this method in other modules (ticket, crm etc) to check if a user can edit the task.
     *
     * // todo move to rbac
     *
     * @param int|UserInterface|null $user The user to check. If not set, the current user will be used.
     *
     * @return bool
     */
    public function canEdit(int|null|UserInterface $user = null): bool
    {
        if ($user instanceof UserInterface) {
            $userId = $user->id;
        } elseif ($user === null) {
            $userId = Yii::$app->user->id;
        } else {
            $userId = $user;
        }

        /*
         * A user can edit a task if:
         * - the task was created by the user OR
         * - the user is assigned to the task OR
         * - the user is assigned to the board the task is in
         */

        return $this->created_by == $userId ||
            in_array($userId, ArrayHelper::getColumn($this->assignments, 'user_id')) ||
            in_array($userId, ArrayHelper::getColumn($this->board->assignments, 'user_id'));
    }

    /**
     * Updates children on parent delete
     *
     * @param Task $task
     * @param int|null $parentId
     * @param int|null $rootId
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    protected function updateChildrenOnParentDelete(Task $task, ?int $parentId = null, ?int $rootId = null): bool
    {
        $task->parent_id = $parentId;
        $task->root_parent_id = $rootId;
        $task->save(attributeNames: ['parent_id', 'root_parent_id']);

        foreach ($task->children as $child) {
            $this->updateChildrenOnParentDelete($child, $task->id, $rootId ?? $task->id);
        }

        return true;
    }
}
