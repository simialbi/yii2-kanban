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

/**
 * Class Link
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $task_id
 * @property string $url
 * @property int $created_by
 * @property int $updated_by
 * @property int|string $created_at
 * @property int|string $updated_at
 */
class Link extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__link}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'task_id'], 'integer'],
            /*
            ['url', 'url', 'pattern' => '/^{schemes}?:\/\/((?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?)(?::\d{1,5})?(?:$|[?\/#])/'],
            /*/
            ['url', 'url', 'pattern' => '/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/'],
            //*/

            [['task_id', 'url'], 'required']
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
            'id' => Yii::t('simialbi/kanban/model/link', 'Id'),
            'task_id' => Yii::t('simialbi/kanban/model/link', 'Task'),
            'url' => Yii::t('simialbi/kanban/model/link', 'URL'),
            'created_by' => Yii::t('simialbi/kanban/model/link', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/link', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/link', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/link', 'Updated at'),
        ];
    }
}
