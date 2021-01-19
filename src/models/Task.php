<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use simialbi\yii2\models\UserInterface;
use simialbi\yii2\ticket\models\Ticket;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Task
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property integer $bucket_id
 * @property integer $ticket_id
 * @property string $subject
 * @property integer $status
 * @property integer|string|\DateTime $start_date
 * @property integer|string|\DateTime $end_date
 * @property string $description
 * @property boolean $card_show_description
 * @property boolean $card_show_checklist
 * @property boolean $card_show_links
 * @property integer $sort
 * @property integer|string $created_by
 * @property integer|string $updated_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 *
 * @property-read string $hash
 * @property-read string $checklistStats
 * @property-read string|null $endDate
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read UserInterface[] $assignees
 * @property-read TaskUserAssignment[] $assignments
 * @property-read Bucket $bucket
 * @property-read Board $board
 * @property-read ChecklistElement[] $checklistElements
 * @property-read Link[] $links
 * @property-read Attachment[] $attachments
 * @property-read Comment[] $comments
 * @property-read Ticket $ticket
 */
class Task extends ActiveRecord
{
    /**
     * @var string Hash
     */
    private $_hash;

    const STATUS_DONE = 0;
    const STATUS_IN_PROGRESS = 5;
    const STATUS_NOT_BEGUN = 10;
    const STATUS_LATE = 15;

    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban_task}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'bucket_id', 'ticket_id'], 'integer'],
            ['subject', 'string', 'max' => 255],
            ['status', 'in', 'range' => [self::STATUS_DONE, self::STATUS_IN_PROGRESS, self::STATUS_NOT_BEGUN]],
            ['start_date', 'date', 'timestampAttribute' => 'start_date'],
            ['end_date', 'date', 'timestampAttribute' => 'end_date'],
            ['description', 'string'],
            [['card_show_description', 'card_show_checklist', 'card_show_links'], 'boolean'],

            [['bucket_id', 'ticket_id'], 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['status', 'default', 'value' => self::STATUS_NOT_BEGUN],
            [['start_date', 'end_date', 'description'], 'default'],
            [['card_show_description', 'card_show_checklist', 'card_show_links'], 'default', 'value' => false],

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
                    self::EVENT_BEFORE_UPDATE => 'updated_by'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at'
                ]
            ],
            'sortable' => [
                'class' => ContinuousNumericalSortableBehavior::class,
                'sortAttribute' => 'sort',
                'scope' => function () {
                    return Task::find()->where(['bucket_id' => $this->bucket_id]);
                }
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
            'subject' => Yii::t('simialbi/kanban/model/task', 'Subject'),
            'status' => Yii::t('simialbi/kanban/model/task', 'Status'),
            'start_date' => Yii::t('simialbi/kanban/model/task', 'Start date'),
            'end_date' => Yii::t('simialbi/kanban/model/task', 'End date'),
            'description' => Yii::t('simialbi/kanban/model/task', 'Description'),
            'card_show_description' => Yii::t('simialbi/kanban/model/task', 'Show description on card'),
            'card_show_checklist' => Yii::t('simialbi/kanban/model/task', 'Show checklist on card'),
            'card_show_links' => Yii::t('simialbi/kanban/model/task', 'Show links on card'),
            'sort' => Yii::t('simialbi/kanban/model/task', 'Sort'),
            'created_by' => Yii::t('simialbi/kanban/model/task', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/task', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/task', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/task', 'Updated at'),
        ];
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
     */
    public function getEndDate()
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        $qry = $this->getChecklistElements()->where(['not', ['end_date' => null]])->orderBy(['end_date' => SORT_DESC]);
        if ($qry->count()) {
            /** @var ChecklistElement $element */
            $element = $qry->one();
            return $element->end_date;
        }

        return null;
    }

    /**
     * Get author
     * @return UserInterface
     */
    public function getAuthor()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->created_by);
    }

    /**
     * Get user last updated
     * @return UserInterface
     */
    public function getUpdater()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->updated_by);
    }

    /**
     * Get users assigned to this task
     * @return array
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
     * Get associated checklist elements
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistElements()
    {
        return $this->hasMany(ChecklistElement::class, ['task_id' => 'id'])
            ->orderBy([ChecklistElement::tableName() . '.[[sort]]' => SORT_ASC]);
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
}
