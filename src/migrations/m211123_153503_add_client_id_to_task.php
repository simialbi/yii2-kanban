<?php

namespace simialbi\yii2\kanban\migrations;

class m211123_153503_add_client_id_to_task extends \yii\db\Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%kanban__task}}',
            'client_id',
            $this->integer()->unsigned()->null()->after('ticket_id')
        );
        $this->addForeignKey(
            '{{%kanban__task_ibfk_3}}',
            '{{%kanban__task}}',
            'client_id',
            '{{%re__client}}',
            '{{id}}',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%kanban__task_ibfk_3}}', '{{%kanban__task}}');
        $this->dropColumn('{{%kanban__task}}', 'client_id');
    }
}
