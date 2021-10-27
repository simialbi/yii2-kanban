<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Bucket
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property integer $board_id
 * @property string $name
 * @property integer $sort
 * @property integer|string $created_by
 * @property integer|string $updated_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 *
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read Board $board
 * @property-read Task[] $tasks
 * @property-read Task[] $openTasks
 * @property-read Task[] $finishedTasks
 */
class Bucket extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban__bucket}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'board_id'], 'integer'],
            ['name', 'string', 'max' => 255],

            ['board_id', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            [['board_id', 'name'], 'required']
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
                    return Bucket::find()->where(['board_id' => $this->board_id]);
                }
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/bucket', 'Id'),
            'board_id' => Yii::t('simialbi/kanban/model/bucket', 'Board'),
            'name' => Yii::t('simialbi/kanban/model/bucket', 'Name'),
            'sort' => Yii::t('simialbi/kanban/model/bucket', 'Sort'),
            'created_by' => Yii::t('simialbi/kanban/model/bucket', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/bucket', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/bucket', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/bucket', 'Updated at'),
        ];
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
     * @return mixed
     */
    public function getUpdater()
    {
        return ArrayHelper::getValue(Yii::$app->controller->module->users, $this->updated_by);
    }

    /**
     * Get associated board
     * @return \yii\db\ActiveQuery
     */
    public function getBoard()
    {
        return $this->hasOne(Board::class, ['id' => 'board_id']);
    }

    /**
     * Get associated tasks
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['bucket_id' => 'id'])
            ->orderBy([Task::tableName() . '.[[sort]]' => SORT_ASC]);
    }

    /**
     * Get associated open tasks
     * @param bool $onlyOwn
     * @return \yii\db\ActiveQuery
     */
    public function getOpenTasks($onlyOwn = false)
    {
        $query = $this->hasMany(Task::class, ['bucket_id' => 'id'])
            ->where(['not', ['status' => Task::STATUS_DONE]])
            ->orderBy([Task::tableName() . '.[[sort]]' => SORT_ASC])
            ->with('attachments')
            ->with('assignments')
            ->with('comments')
            ->with('checklistElements')
            ->with('links');
        if ($onlyOwn) {
            $query->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
        }

        return $query;
    }

    /**
     * Get associated finished tasks
     * @param bool $onlyOwn
     * @return \yii\db\ActiveQuery
     */
    public function getFinishedTasks($onlyOwn = false)
    {
        $query = $this->hasMany(Task::class, ['bucket_id' => 'id'])
            ->where(['status' => Task::STATUS_DONE])
            ->orderBy([Task::tableName() . '.[[sort]]' => SORT_ASC])
            ->with('attachments')
            ->with('assignments')
            ->with('comments')
            ->with('checklistElements')
            ->with('links');
        if ($onlyOwn) {
            $query->innerJoinWith('assignments u')->andWhere(['{{u}}.[[user_id]]' => Yii::$app->user->id]);
        }

        return $query;
    }
}
