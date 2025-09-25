<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m250127_164900_add_parent_child_relation_to_tasks extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): void
    {
        $this->addColumn('{{%kanban__task}}', 'parent_id', $this->integer()->unsigned()->after('client_id'));
        $this->addForeignKey(
            '{{%kanban__task_ibfk_9}}',
            '{{%kanban__task}}',
            'parent_id',
            '{{%kanban__task}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addColumn('{{%kanban__task}}', 'root_parent_id', $this->integer()->unsigned()->after('parent_id'));
        $this->addForeignKey(
            '{{%kanban__task_ibfk_10}}',
            '{{%kanban__task}}',
            'root_parent_id',
            '{{%kanban__task}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('{{%kanban__task_ibfk_9}}', '{{%kanban__task}}');
        $this->dropColumn('{{%kanban__task}}', 'parent_id');

        $this->dropForeignKey('{{%kanban__task_ibfk_10}}', '{{%kanban__task}}');
        $this->dropColumn('{{%kanban__task}}', 'root_parent_id');
    }
}
