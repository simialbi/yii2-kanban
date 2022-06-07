<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m220607_144949_change_task_dependency_to_nested_set extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('{{%kanban__task_dependency_ibfk_2}}', '{{%kanban__task_dependency}}');
        $this->dropForeignKey('{{%kanban__task_dependency_ibfk_1}}', '{{%kanban__task_dependency}}');
        $this->dropTable('{{%kanban__task_dependency}}');

        $this->addColumn(
            '{{%kanban__task}}',
            'lft',
            $this->integer()->unsigned()->notNull()->defaultValue(1)->after('card_show_links')
        );
        $this->addColumn(
            '{{%kanban__task}}',
            'rgt',
            $this->integer()->unsigned()->notNull()->defaultValue(2)->after('lft')
        );
        $this->addColumn(
            '{{%kanban__task}}',
            'depth',
            $this->integer()->unsigned()->notNull()->defaultValue(0)->after('rgt')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%kanban__task}}', 'depth');
        $this->dropColumn('{{%kanban__task}}', 'rgt');
        $this->dropColumn('{{%kanban__task}}', 'lft');

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
}
