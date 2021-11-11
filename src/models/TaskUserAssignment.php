<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use yii\db\ActiveRecord;

/**
 * Class TaskUserAssignment
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $task_id
 * @property string $user_id
 */
class TaskUserAssignment extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban__task_user_assignment}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            ['task_id', 'integer'],
            ['user_id', 'string'],

            [['task_id', 'user_id'], 'required']
        ];
    }
}
