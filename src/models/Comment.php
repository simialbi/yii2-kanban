<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Comment
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $task_id
 * @property string $text
 * @property int|string $created_by
 * @property int|string $created_at
 * @property int $sync_id
 *
 * @property-read UserInterface $author
 * @property-read Task $task
 * @property-read Bucket $bucket
 * @property-read Board $board
 */
class Comment extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__comment}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'task_id'], 'integer'],
            ['text', 'string'],
            ['sync_id', 'string', 'max' => 255],

            [['task_id', 'text'], 'required']
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
                    self::EVENT_BEFORE_INSERT => 'created_by'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => 'created_at'
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
            'id' => Yii::t('simialbi/kanban/model/comment', 'Id'),
            'task_id' => Yii::t('simialbi/kanban/model/comment', 'Task'),
            'text' => Yii::t('simialbi/kanban/model/comment', 'Text'),
            'created_by' => Yii::t('simialbi/kanban/model/comment', 'Created by'),
            'created_at' => Yii::t('simialbi/kanban/model/comment', 'Created at')
        ];
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     */
    public function getAuthor(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->created_by);
    }

    /**
     * Get associated task
     * @return ActiveQuery
     */
    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    /**
     * Get associated bucket
     * @return ActiveQuery
     */
    public function getBucket(): ActiveQuery
    {
        return $this->hasOne(Bucket::class, ['id' => 'bucket_id'])->via('task');
    }

    /**
     * Get associated board
     * @return ActiveQuery
     */
    public function getBoard(): ActiveQuery
    {
        return $this->hasOne(Board::class, ['id' => 'board_id'])->via('bucket');
    }
}
