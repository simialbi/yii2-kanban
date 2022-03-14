<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\fixtures;

use yii\test\FileFixtureTrait;
use yii\test\Fixture;

class UserFixture extends Fixture
{
    use FileFixtureTrait;
    /**
     * @var string
     */
    public $modelClass = '\simialbi\extensions\kanban\models\User';

    /**
     * @var string
     */
    public $dataFile;

    /**
     * {@inheritDoc}
     * @throws \yii\base\InvalidConfigException
     */
    public function load()
    {
        $users = [];
        foreach ($this->loadData($this->dataFile) as $user) {
            $users[$user['id']] = $user;
        }

        call_user_func([$this->modelClass, 'setUsers'], $users);
    }

    /**
     * {@inheritDoc}
     */
    public function unload()
    {
        call_user_func([$this->modelClass, 'setUsers'], []);
    }
}
