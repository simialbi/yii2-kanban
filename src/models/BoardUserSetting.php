<?php

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use tonic\hq\models\AddresspoolAddress;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class BoardUserSetting
 *
 * @property int $id
 * @property int $board_id
 * @property int|string $user_id
 * @property boolean $is_hidden
 *
 * @property-read UserInterface $user
 * @property-read Board $board
 */
class BoardUserSetting extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__board_user_setting}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'board_id'], 'integer'],
            [['is_hidden'], 'boolean'],

            ['is_hidden', 'default', 'value' => false],
            ['user_id', 'string', 'max' => 64],

            [['board_id', 'user_id', 'is_hidden'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'board_id' => Yii::t('simialbi/kanban/model/board-user-setting', 'Board'),
            'user_id' => Yii::t('simialbi/kanban/model/board-user-setting', 'User'),
            'is_hidden' => Yii::t('simialbi/kanban/model/board-user-setting', 'Hidden')
        ];
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
     * Get related Board
     * @return ActiveQuery
     */
    public function getBoard(): ActiveQuery
    {
        return $this->hasOne(Board::class, ['id' => 'board_id']);
    }
}
