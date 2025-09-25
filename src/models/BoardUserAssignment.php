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
 * @property int $board_id
 * @property int|string $user_id
 */
class BoardUserAssignment extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__board_user_assignment}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['board_id'], 'integer'],
            ['user_id', 'string', 'max' => 64],
            [['board_id', 'user_id'], 'required']
        ];
    }
}
