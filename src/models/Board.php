<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;


use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Class Board
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property string $name
 * @property string $image
 * @property boolean $is_public
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 *
 * @property-read IdentityInterface $author
 * @property-read IdentityInterface $updater
 * @property-read Bucket[] $buckets
 */
class Board extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban_board}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],
            ['image', 'file', 'mimeTypes' => 'image/*'],
            ['is_public', 'boolean'],

            ['is_public', 'default', 'value' => true],
            ['image', 'default'],

            [['name', 'is_public'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors()
    {
        return [
            'blameable' => [
                'class'      => BlameableBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_by', 'updated_by'],
                    self::EVENT_BEFORE_UPDATE => 'updated_by'
                ]
            ],
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at'
                ]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/board', 'Id'),
            'name' => Yii::t('simialbi/kanban/model/board', 'Name'),
            'image' => Yii::t('simialbi/kanban/model/board', 'Image'),
            'is_public' => Yii::t('simialbi/kanban/model/board', 'Is public'),
            'created_by' => Yii::t('simialbi/kanban/model/board', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/board', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/board', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/board', 'Updated at')
        ];
    }

    /**
     * Find boards assigned to user
     * @param integer|string|null $id
     */
    public static function findByUserId($id = null)
    {
        if ($id === null) {
            $id = Yii::$app->user->id;
        }

        static::find()
            ->alias('b')
            ->innerJoin(['ua' => '{{%kanban_board_user_assignment}}'], '{{ua}}.[[board_id]] = {{b}}.[[id]]')
            ->where(['{{b}}.[[is_public]]' => true])
            ->orWhere(['{{ua}}.[[user_id]]' => $id]);
    }

    /**
     * {@inheritDoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            static::getDb()->createCommand()->insert('{{%kanban_board_user_assignment}}', [
                'board_id' => $this->id,
                'user_id' => Yii::$app->user->id
            ]);
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Get author
     * @return IdentityInterface
     */
    public function getAuthor()
    {
        return call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $this->created_by);
    }

    /**
     * Get user last updated
     * @return mixed
     */
    public function getUpdater()
    {
        return call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $this->updated_by);
    }

    /**
     * Get associated buckets
     * @return \yii\db\ActiveQuery
     */
    public function getBuckets()
    {
        return $this->hasMany(Bucket::class, ['board_id' => 'id']);
    }
}
