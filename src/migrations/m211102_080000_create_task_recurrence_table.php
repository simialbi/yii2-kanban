<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m211102_080000_create_task_recurrence_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%kanban__task_recurrent_task}}', [
            'task_id' => $this->integer()->unsigned()->notNull(),
            'execution_date' => $this->integer()->unsigned()->notNull(),
            'status' => $this->tinyInteger()->unsigned()->notNull()->defaultValue(5),
            'PRIMARY KEY ([[task_id]], [[execution_date]])'
        ]);

        $this->addForeignKey(
            '{{%kanban__task_recurrent_task_ibfk_1}}',
            '{{%kanban__task_recurrent_task}}',
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
    public function safeDown()
    {
        $this->dropForeignKey('{{%kanban__task_recurrent_task_ibfk_1}}', '{{%kanban__task_recurrent_task}}');

        $this->dropTable('{{%kanban__task_recurrent_task}}');
    }
}
