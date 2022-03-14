<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\models;

use simialbi\yii2\models\UserInterface;
use yii\base\Model;

class User extends Model implements UserInterface
{
    /**
     * @var integer
     */
    public $id;
    /**
     * @var string
     */
    public $username;
    /**
     * @var string
     */
    public $token;
    /**
     * @var string
     */
    public $image;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $mobile;

    /**
     * @var array[]
     */
    private static $_users = [];

    /**
     * {@inheritDoc}
     */
    public static function findIdentity($id)
    {
        return self::$_users[$id] ?: null;
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentities()
    {
        return self::$_users;
    }

    /**
     * @return array[]
     */
    public static function getUsers()
    {
        return self::$_users;
    }

    /**
     * @param array[] $users
     */
    public static function setUsers($users)
    {
        self::$_users = $users;
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
        return $this->name;
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
