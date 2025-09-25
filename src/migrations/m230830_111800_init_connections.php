<?php

namespace simialbi\yii2\kanban\migrations;

use yii\base\Exception;
use yii\db\Migration;

class m230830_111800_init_connections extends Migration
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function safeUp(): void
    {
        // Create table for connections
        $this->createTable('{{%kanban__connection}}', [
            'id' => $this->primaryKey()->unsigned()->notNull(),
            'user_id' => $this->string(64)->null()->defaultValue(null),
            'bucket_id' => $this->integer()->unsigned()->notNull(),
            'type' => $this->integer()->unsigned()->notNull(),
            'options' => $this->json()->notNull()->defaultExpression('(JSON_ARRAY())'),
        ]);
        $this->addForeignKey(
            '{{%kanban__connection_ibfk_1}}',
            '{{%kanban__connection}}',
            'bucket_id',
            '{{%kanban__bucket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%kanban__task_ibfk_4}}',
            '{{%kanban__task}}',
            'wedo_connection_id',
            '{{%kanban__connection}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): void
    {
        // change foreign key of task
        $this->dropForeignKey('{{%kanban__task_ibfk_4}}', '{{%kanban__task}}');

        // Drop new table
        $this->dropForeignKey('{{%kanban__connection_ibfk_1}}', '{{%kanban__connection}}');
        $this->dropTable('{{%kanban__connection}}');
    }
}
