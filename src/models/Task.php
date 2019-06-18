<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
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
 * @property string $subject
 * @property integer $status
 * @property integer|string $start_date
 * @property integer|string $end_date
 * @property string $description
 * @property boolean $card_show_description
 * @property boolean $card_show_checklist
 * @property integer $sort
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 *
 * @property-read string $checklistStats
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read Bucket $bucket
 * @property-read Board $board
 * @property-read ChecklistElement[] $checklistElements
 * @property-read Attachment[] $attachments
 * @property-read Comment[] $comments
 */
class Task extends ActiveRecord
{
    const STATUS_DONE = 0;
    const STATUS_IN_PROGRESS = 5;
    const STATUS_NOT_BEGUN = 10;

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
            [['id', 'bucket_id'], 'integer'],
            ['subject', 'string', 'max' => 255],
            ['status', 'in', 'range' => [self::STATUS_DONE, self::STATUS_IN_PROGRESS, self::STATUS_NOT_BEGUN]],
            ['start_date', 'date', 'timestampAttribute' => 'start_date'],
            ['end_date', 'date', 'timestampAttribute' => 'end_date'],
            ['description', 'string'],
            [['card_show_description', 'card_show_checklist'], 'boolean'],

            ['bucket_id', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['status', 'default', 'value' => self::STATUS_NOT_BEGUN],
            [['start_date', 'end_date', 'description'], 'default'],
            [['card_show_description', 'card_show_checklist'], 'default', 'value' => false],

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
            'sort' => Yii::t('simialbi/kanban/model/task', 'Sort'),
            'created_by' => Yii::t('simialbi/kanban/model/task', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/task', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/task', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/task', 'Updated at'),
        ];
    }

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
     * Get author
     * @return UserInterface
     */
    public function getAuthor()
    {
        return call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $this->created_by);
    }

    /**
     * Get user last updated
     * @return UserInterface
     */
    public function getUpdater()
    {
        return call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $this->updated_by);
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
        return $this->hasMany(ChecklistElement::class, ['task_id' => 'id'])->orderBy(['sort' => SORT_ASC]);
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
        return $this->hasMany(Comment::class, ['task_id' => 'id']);
    }
}
