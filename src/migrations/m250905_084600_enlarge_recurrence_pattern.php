<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m250905_084600_enlarge_recurrence_pattern extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): void
    {
        $this->alterColumn(
            '{{%kanban__task}}',
            'recurrence_pattern',
            $this->string(1024)->null()->defaultValue(null)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): void
    {
        $this->alterColumn(
            '{{%kanban__task}}',
            'recurrence_pattern',
            $this->string(255)->null()->defaultValue(null)
        );
    }
}
