<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m231009_143700_add_time_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): void
    {
        $this->createTable('{{%kanban__task_time_window}}', [
            'id' => $this->primaryKey()->unsigned()->notNull(),
            'task_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->string(64)->null()->defaultValue(null),
            'time_start' => $this->integer()->unsigned()->notNull(),
            'time_end' => $this->integer()->unsigned()->notNull(),
            'sync_id' => $this->string(152)->null(),
            'change_key' => $this->string(40)->null()
        ]);

        $this->addForeignKey(
            '{{%kanban__task_time_window_ibfk_1}}',
            '{{%kanban__task_time_window}}',
            'task_id',
            '{{%kanban__task}}',
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
        $this->dropForeignKey('{{%kanban__task_time_window_ibfk_1}}', '{{%kanban__task_time_window}}');
        $this->dropTable('{{%kanban__task_time_window}}');
    }
}
