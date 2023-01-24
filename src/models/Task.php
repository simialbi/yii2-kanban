<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use Recurr\Rule;
use simialbi\yii2\kanban\behaviors\RepeatableBehavior;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\ticket\models\Ticket;
use Yii;
use yii\base\ModelEvent;
use yii\behaviors\AttributeTypecastBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\ArrayHelper;

/**
 * Class Task
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property integer $bucket_id
 * @property integer $ticket_id
 * @property string $responsible_id
 * @property string $subject
 * @property integer $status
 * @property integer|string|\DateTime $start_date
 * @property integer|string|\DateTime $end_date
 * @property integer $percentage_done
 * @property string|Rule $recurrence_pattern
 * @property integer $recurrence_parent_id
 * @property boolean $is_recurring
 * @property string $description
 * @property boolean $card_show_description
 * @property boolean $card_show_checklist
 * @property boolean $card_show_links
 * @property integer $sort
 * @property integer|string $created_by
 * @property integer|string $updated_by
 * @property integer|string $finished_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 * @property integer|string $finished_at
 *
 * @method boolean isRecurrentInstance() If this model is an instance of an recurrent task
 * @method static getOriginalRecord() The original record if model is recurrent instance
 *
 * @property-read string $hash
 * @property-read string $checklistStats
 * @property-read string|null $endDate
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
 * @property-read UserInterface $responsible
 * @property-read Task[] $dependants
 * @property-read Task[] $dependencies
 */
class Task extends ActiveRecord
{
    const EVENT_BEFORE_FINISH = 'beforeFinish';
    const EVENT_AFTER_FINISH = 'afterFinish';
    const STATUS_DONE = 0;
    const STATUS_IN_PROGRESS = 5;
    const STATUS_NOT_BEGUN = 10;
    const STATUS_LATE = 15;
    /**
     * @var string Hash
     */
    private $_hash;

    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban__task}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'bucket_id', 'ticket_id', 'status', 'recurrence_parent_id'], 'integer'],
            ['subject', 'string', 'max' => 255],
            ['responsible_id', 'string', 'max' => 64],
            ['responsible_id', 'default', 'value' => null],
            ['status', 'in', 'range' => [self::STATUS_DONE, self::STATUS_IN_PROGRESS, self::STATUS_NOT_BEGUN]],
            ['start_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'start_date'],
            ['end_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'end_date'],
            ['percentage_done', 'match', 'pattern' => '#^\d+ ?%?$#'],
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

            [['bucket_id', 'ticket_id'], 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['status', 'default', 'value' => self::STATUS_NOT_BEGUN],
            [['start_date', 'end_date', 'description'], 'default'],
            [
                ['card_show_description', 'card_show_checklist', 'card_show_links', 'is_recurring'],
                'default',
                'value' => false
            ],

            [['bucket_id', 'subject', 'status', 'card_show_description', 'card_show_checklist'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors()
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
                                YIi::$app->timeZone
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
                'class' => RepeatableBehavior::class
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/task', 'Id'),
            'bucket_id' => Yii::t('simialbi/kanban/model/task', 'Bucket'),
            'board_id' => Yii::t('simialbi/kanban/model/task', 'Board'),
            'assignee_id' => Yii::t('simialbi/kanban/model/task', 'Assignee'),
            'responsible_id' => Yii::t('simialbi/kanban/model/task', 'Responsible'),
            'subject' => Yii::t('simialbi/kanban/model/task', 'Subject'),
            'status' => Yii::t('simialbi/kanban/model/task', 'Status'),
            'start_date' => Yii::t('simialbi/kanban/model/task', 'Start date'),
            'end_date' => Yii::t('simialbi/kanban/model/task', 'End date'),
            'percentage_done' => Yii::t('simialbi/kanban/model/task', 'Percentage done'),
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
     * @param array $params
     * @param \yii\validators\Validator $validator
     * @throws \Recurr\Exception\InvalidArgument|\Recurr\Exception\InvalidRRule|\yii\base\InvalidConfigException
     */
    public function validateRecurrence($attribute, $params, $validator)
    {
        if (is_array($this->$attribute) && ArrayHelper::isAssociative($this->$attribute)) {
            $rule = new Rule();
            $rule->setStartDate(new \DateTime(
                Yii::$app->formatter->asDatetime($this->start_date, 'yyyy-MM-dd HH:mm:ss'),
                new \DateTimeZone(Yii::$app->timeZone)
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
    public function beforeSave($insert)
    {
        if ($this->isAttributeChanged('status') && (int)$this->status === self::STATUS_DONE) {
            if (!$this->beforeFinish()) {
                return false;
            }
        }

        return parent::beforeSave($insert);
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
    public function beforeFinish()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_FINISH, $event);

        return $event->isValid;
    }

    /**
     * {@inheritDoc}
     */
    public function afterSave($insert, $changedAttributes)
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
    public function afterFinish($changedAttributes)
    {
        $this->trigger(self::EVENT_AFTER_FINISH, new AfterSaveEvent([
            'changedAttributes' => $changedAttributes,
        ]));
    }

    /**
     * Generate unique hash per task
     * @return string
     */
    public function getHash()
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
     * @throws \Exception
     */
    public function getChecklistStats()
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
     * @return string|null
     * @throws \Exception
     */
    public function getEndDate()
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        if (empty($this->checklistElements)) {
            return null;
        }
        /** @var ChecklistElement[] $checklistElements */
        $grouped = ArrayHelper::index(
            array_filter($this->checklistElements, function($item) {return $item->end_date != null;}),
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
     * Get associated checklist elements
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistElements()
    {
        return $this->hasMany(ChecklistElement::class, ['task_id' => 'id'])
            ->orderBy([ChecklistElement::tableName() . '.[[sort]]' => SORT_ASC]);
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     */
    public function getAuthor()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->created_by);
    }

    /**
     * Get user last updated
     * @return UserInterface
     * @throws \Exception
     */
    public function getUpdater()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->updated_by);
    }

    /**
     * Get user finished
     * @return UserInterface
     * @throws \Exception
     */
    public function getFinisher()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->finished_by);
    }

    /**
     * Get users assigned to this task
     * @return array
     * @throws \Exception
     */
    public function getAssignees()
    {
        $allAssignees = Yii::$app->controller->module->users;

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
     * @return \yii\db\ActiveQuery
     */
    public function getAssignments()
    {
        return $this->hasMany(TaskUserAssignment::class, ['task_id' => 'id']);
    }

    /**
     * Get associated bucket
     * @return \yii\db\ActiveQuery
     */
    public function getBucket()
    {
        return $this->hasOne(Bucket::class, ['id' => 'bucket_id']);
    }

    /**
     * Get associated board
     * @return \yii\db\ActiveQuery
     */
    public function getBoard()
    {
        return $this->hasOne(Board::class, ['id' => 'board_id'])->via('bucket');
    }

    /**
     * Get associated links
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(Link::class, ['task_id' => 'id']);
    }

    /**
     * Get associated attachments
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, ['task_id' => 'id']);
    }

    /**
     * Get associated comments
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['task_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Get associated ticket
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(Ticket::class, ['id' => 'ticket_id']);
    }

    /**
     * Get associated recurrence parent
     * @return \yii\db\ActiveQuery
     */
    public function getRecurrenceParent()
    {
        return $this->hasOne(static::class, ['id' => 'recurrence_parent_id']);
    }

    /**
     * Get responsible User
     * @return UserInterface
     * @throws \Exception
     */
    public function getResponsible()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->responsible_id);
    }

    /**
     * Get dependant tasks
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getDependants()
    {
        return $this->hasMany(Task::class, ['id' => 'parent_id'])
            ->viaTable('{{%kanban__task_dependency}}', ['dependant_id' => 'id']);
    }

    /**
     * Get dependency tasks
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getDependencies()
    {
        return $this->hasMany(Task::class, ['id' => 'dependant_id'])
            ->viaTable('{{%kanban__task_dependency}}', ['parent_id' => 'id']);
    }
}
