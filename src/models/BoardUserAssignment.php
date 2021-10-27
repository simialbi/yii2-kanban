<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use yii\db\ActiveRecord;

/**
 * Class BoardUserAssignment
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $board_id
 * @property string $user_id
 */
class BoardUserAssignment extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban__board_user_assignment}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            ['board_id', 'integer'],
            ['user_id', 'string'],

            [['board_id', 'user_id'], 'required']
        ];
    }
}
