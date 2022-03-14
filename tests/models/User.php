<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\models;

use simialbi\yii2\models\UserInterface;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property string $username
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $mobile
 * @property string $password
 * @property string $token
 * @property string $image
 */
class User extends ActiveRecord implements UserInterface
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentities()
    {
        return static::find()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthKey()
    {
        return $this->token;
    }

    /**
     * {@inheritDoc}
     */
    public function validateAuthKey($authKey)
    {
        return $authKey === $this->token;
    }

    /**
     * {@inheritDoc}
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function getMobile()
    {
        return $this->mobile;
    }
}
