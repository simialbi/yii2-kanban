<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
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
 * Class MonitoringMember
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $list_id
 * @property int|string $user_id
 * @property int|string $created_by
 * @property int|string $created_at
 *
 * @property-read MonitoringList $list
 * @property-read UserInterface $author
 * @property-read UserInterface $user
 */
class MonitoringMember extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__monitoring_member}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['user_id', 'string', 'max' => 64],
            ['list_id', 'integer'],

            [['list_id', 'user_id'], 'required']
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
            'id' => Yii::t('simialbi/kanban/model/monitoring-member', 'Id'),
            'list_id' => Yii::t('simialbi/kanban/model/monitoring-member', 'List'),
            'user_id' => Yii::t('simialbi/kanban/model/monitoring-member', 'User'),
            'created_by' => Yii::t('simialbi/kanban/model/monitoring-member', 'Created by'),
            'created_at' => Yii::t('simialbi/kanban/model/monitoring-member', 'Created at')
        ];
    }

    /**
     * Get associated monitoring list
     * @return ActiveQuery
     */
    public function getList(): ActiveQuery
    {
        return $this->hasOne(MonitoringList::class, ['id' => 'list_id']);
    }

    /**
     * Get related User
     * @return UserInterface
     * @throws \Exception
     */
    public function getUser(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->user_id);
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
}
