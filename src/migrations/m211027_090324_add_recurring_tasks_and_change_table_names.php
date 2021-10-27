<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m211027_090324_add_recurring_tasks_and_change_table_names extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->renameTable('{{%kanban_attachment}}', '{{%kanban__attachment}}');
        $this->renameTable('{{%kanban_board}}', '{{%kanban__board}}');
        $this->renameTable('{{%kanban_board_user_assignment}}', '{{%kanban__board_user_assignment}}');
        $this->renameTable('{{%kanban_bucket}}', '{{%kanban__bucket}}');
        $this->renameTable('{{%kanban_checklist_element}}', '{{%kanban__checklist_element}}');
        $this->renameTable('{{%kanban_comment}}', '{{%kanban__comment}}');
        $this->renameTable('{{%kanban_link}}', '{{%kanban__link}}');
        $this->renameTable('{{%kanban_monitoring_list}}', '{{%kanban__monitoring_list}}');
        $this->renameTable('{{%kanban_monitoring_member}}', '{{%kanban__monitoring_member}}');
        $this->renameTable('{{%kanban_task}}', '{{%kanban__task}}');
        $this->renameTable('{{%kanban_task_user_assignment}}', '{{%kanban__task_user_assignment}}');

        $this->addColumn(
            '{{%kanban__task}}',
            'is_recurring',
            $this->boolean()->notNull()->defaultValue(0)->after('end_date')
        );
        $this->addColumn(
            '{{%kanban__task}}',
            'recurrence_pattern',
            $this->string()->null()->defaultValue(null)->after('end_date')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%kanban__task}}', 'recurrence_pattern');
        $this->dropColumn('{{%kanban__task}}', 'is_recurring');

        $this->renameTable('{{%kanban__attachment}}', '{{%kanban_attachment}}');
        $this->renameTable('{{%kanban__board}}', '{{%kanban_board}}');
        $this->renameTable('{{%kanban__board_user_assignment}}', '{{%kanban_board_user_assignment}}');
        $this->renameTable('{{%kanban__bucket}}', '{{%kanban_bucket}}');
        $this->renameTable('{{%kanban__checklist_element}}', '{{%kanban_checklist_element}}');
        $this->renameTable('{{%kanban__comment}}', '{{%kanban_comment}}');
        $this->renameTable('{{%kanban__link}}', '{{%kanban_link}}');
        $this->renameTable('{{%kanban__monitoring_list}}', '{{%kanban_monitoring_list}}');
        $this->renameTable('{{%kanban__monitoring_member}}', '{{%kanban_monitoring_member}}');
        $this->renameTable('{{%kanban__task}}', '{{%kanban_task}}');
        $this->renameTable('{{%kanban__task_user_assignment}}', '{{%kanban_task_user_assignment}}');
    }
}
