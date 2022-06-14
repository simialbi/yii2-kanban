<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m220603_111502_initialize_project extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%kanban__task}}',
            'percentage_done',
            $this->tinyInteger()->unsigned()->notNull()->defaultValue(0)->after('end_date')
        );

        $this->createTable('{{%kanban__task_dependency}}', [
            'parent_id' => $this->integer()->unsigned()->notNull(),
            'dependant_id' => $this->integer()->unsigned()->notNull(),
            'PRIMARY KEY ([[parent_id]], [[dependant_id]])'
        ]);

        $this->addForeignKey(
            '{{%kanban__task_dependency_ibfk_1}}',
            '{{%kanban__task_dependency}}',
            'parent_id',
            '{{%kanban__task}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban__task_dependency_ibfk_2}}',
            '{{%kanban__task_dependency}}',
            'dependant_id',
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
        $this->dropForeignKey('{{%kanban__task_dependency_ibfk_2}}', '{{%kanban__task_dependency}}');
        $this->dropForeignKey('{{%kanban__task_dependency_ibfk_1}}', '{{%kanban__task_dependency}}');
        $this->dropTable('{{%kanban__task_dependency}}');

        $this->dropColumn('{{%kanban_task}}', 'percentage_done');
    }
}
