<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\models\UserInterface;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class MonitoringList
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property string $name
 * @property int|string $created_by
 * @property int|string $updated_by
 * @property int|string $created_at
 * @property int|string $updated_at
 *
 * @property-read MonitoringMember[] $members
 * @property-read UserInterface[] $users
 */
class MonitoringList extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__monitoring_list}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],

            [['name'], 'required']
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
                    self::EVENT_BEFORE_UPDATE => 'updated_by'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at'
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
            'id' => Yii::t('simialbi/kanban/model/monitoring-list', 'Id'),
            'name' => Yii::t('simialbi/kanban/model/monitoring-list', 'Name'),
            'member_id' => Yii::t('simialbi/kanban/model/search-monitoring-list', 'Members'),
            'created_by' => Yii::t('simialbi/kanban/model/monitoring-list', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/monitoring-list', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/monitoring-list', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/monitoring-list', 'Updated at')
        ];
    }

    /**
     * Get associated members
     * @return ActiveQuery
     */
    public function getMembers(): ActiveQuery
    {
        return $this->hasMany(MonitoringMember::class, ['list_id' => 'id']);
    }

    /**
     * Get associated users
     * @return UserInterface[]
     */
    public function getUsers(): array
    {
        return ArrayHelper::getColumn($this->members, 'user');
    }
}
