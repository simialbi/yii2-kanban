<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\migrations;

use Yii;
use yii\db\Migration;

class m220314_110926_init_tests extends Migration
{
    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey()->unsigned()->notNull(),
            'username' => $this->string(255)->notNull(),
            'first_name' => $this->string(255)->notNull(),
            'last_name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'mobile' => $this->string(255)->null()->defaultValue(null),
            'password' => $this->string(255)->null()->defaultValue(null),
            'token' => $this->string(32)->notNull(),
            'image' => $this->string(1024)->null()->defaultValue(null)
        ]);

        $this->batchInsert('{{%user}}', ['username', 'first_name', 'last_name', 'email', 'password', 'token'], [
            [
                'john.doe',
                'John',
                'Doe',
                'john.doe@example.com',
                Yii::$app->security->generatePasswordHash('asdf1234%'),
                Yii::$app->security->generateRandomString()
            ],
            [
                'jane.doe',
                'Jane',
                'Doe',
                'jane.doe@example.com',
                Yii::$app->security->generatePasswordHash('asdf1235%'),
                Yii::$app->security->generateRandomString()
            ]
        ]);

        $auth = Yii::$app->authManager;
        if ($auth) {
            $role = $auth->getRole('kanbanSurveillanceOperator');
            $auth->assign($role, 1);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}
