<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\migrations;

use Yii;
use yii\db\Migration;

class m210118_160251_add_monitoring_feature extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $this->createTable('{{%kanban_monitoring_list}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(255)->notNull(),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_monitoring_member}}', [
            'id' => $this->primaryKey()->unsigned(),
            'list_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->addForeignKey(
            '{{%kanban_monitoring_member_ibfk_1}}',
            '{{%kanban_monitoring_member}}',
            'list_id',
            '{{%kanban_monitoring_list}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        if ($auth) {
            $monitorTasks = $auth->createPermission('monitorKanbanTasks');
            $monitorTasks->description = 'Create monitoring lists and monitor tasks status';
            $auth->add($monitorTasks);

            $surveillanceOperator = $auth->createRole('kanbanSurveillanceOperator');
            $surveillanceOperator->description = 'A surveillance operator can create monitoring lists and monitor tasks progress';
            $auth->add($surveillanceOperator);

            $auth->addChild($surveillanceOperator, $monitorTasks);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $this->dropForeignKey('{{%kanban_monitoring_member_ibfk_1}}', '{{%kanban_monitoring_member}}');
        $this->dropTable('{{%kanban_monitoring_member}}');
        $this->dropTable('{{%kanban_monitoring_list}}');

        if ($auth) {
            $surveillanceOperator = $auth->getRole('kanbanSurveillanceOperator');
            $monitorTasks = $auth->getRole('monitorKanbanTasks');

            $auth->removeChild($surveillanceOperator, $monitorTasks);
            $auth->remove($surveillanceOperator);
            $auth->remove($monitorTasks);
        }
    }
}
