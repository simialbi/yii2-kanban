<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

/**
 * Class m210121_161616_add_finished_at_and_finished_by_to_task_table
 * @package simialbi\yii2\kanban\migrations
 */
class m210121_161616_add_finished_at_and_finished_by_to_task_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%kanban_task}}',
            'finished_by',
            $this->integer()->unsigned()->null()->defaultValue(null)->after('updated_by')
        );
        $this->addColumn(
            '{{%kanban_task}}',
            'finished_at',
            $this->integer()->unsigned()->null()->defaultValue(null)->after('updated_at')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%kanban_task}}', 'finished_at');
        $this->dropColumn('{{%kanban_task}}', 'finished_by');
    }
}
