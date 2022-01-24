<?php

namespace simialbi\yii2\kanban\migrations;

class m211130_083441_add_responsible_to_task extends \yii\db\Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%kanban__task}}', 'responsible_id', $this->string(64)->after('ticket_id'));
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%kanban__task}}', 'responsible_id');
    }
}
