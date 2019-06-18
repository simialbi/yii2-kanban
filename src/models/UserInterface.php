<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use yii\web\IdentityInterface;

/**
 * UserInterface extents IdentityInterface and is the interface that should be implemented by
 * a class providing user information.
 *
 * This interface can typically be implemented by a user model class. For example, the following
 * code shows how to implement this interface by a User ActiveRecord class:
 *
 * ```php
 * class User extends ActiveRecord implements UserInterface
 * {
 *     public static function findIdentity($id)
 *     {
 *         return static::findOne($id);
 *     }
 *
 *     public static function findIdentityByAccessToken($token, $type = null)
 *     {
 *         return static::findOne(['access_token' => $token]);
 *     }
 *
 *     public function getImage()
 *     {
 *         return $this->image;
 *     }
 *
 *     public function getName()
 *     {
 *         return $this->name;
 *     }
 *
 *     public function getId()
 *     {
 *         return $this->id;
 *     }
 *
 *     public function getAuthKey()
 *     {
 *         return $this->authKey;
 *     }
 *
 *     public function validateAuthKey($authKey)
 *     {
 *         return $this->authKey === $authKey;
 *     }
 * }
 * ```
 *
 * @property-read string|null $name
 * @property-read string|null $image
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface UserInterface extends IdentityInterface
{
    /**
     * Returns an a users profile image.
     * @return string|null A users profile image.
     */
    public function getImage();

    /**
     * Returns an a users name (first and last name or username).
     * @return string|null A users name.
     */
    public function getName();
}
