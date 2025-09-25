<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m230613_085800_add_is_checklist_to_board extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%kanban__board}}',
            'is_checklist',
            $this->boolean()->notNull()->defaultValue(false)->after('is_public')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): void
    {
        $this->dropColumn(
            '{{%kanban__board}}',
            'is_checklist'
        );
    }
}
