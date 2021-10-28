<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m211028_170301_add_recurring_parent_field_to_task_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%kanban__task}}',
            'recurrence_parent_id',
            $this->integer()->unsigned()->null()->defaultValue(null)->after('recurrence_pattern')
        );

        $this->addForeignKey(
            '{{%kanban__task_ibfk_2}}',
            '{{%kanban__task}}',
            'recurrence_parent_id',
            '{{%kanban__task}}',
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
        $this->dropForeignKey('{{%kanban__task_ibfk_2}}', '{{%kanban__task}}');

        $this->dropColumn('{{%kanban__task}}', 'recurrence_parent_id');
    }
}
