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
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class MonitoringList
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property string $name
 * @property integer|string $created_by
 * @property integer|string $updated_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 *
 * @property-read MonitoringMember[] $members
 * @property-read UserInterface[] $users
 */
class MonitoringList extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban__monitoring_list}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
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
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels()
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
     * @return \yii\db\ActiveQuery
     */
    public function getMembers()
    {
        return $this->hasMany(MonitoringMember::class, ['list_id' => 'id']);
    }

    /**
     * Get associated users
     * @return UserInterface[]
     */
    public function getUsers()
    {
        return ArrayHelper::getColumn($this->members, 'user');
    }
}
