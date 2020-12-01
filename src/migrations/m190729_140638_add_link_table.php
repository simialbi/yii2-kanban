<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m190729_140638_add_link_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $urlColumn = $this->isMSSSQL()
            ? '[[url]] NVARCHAR(MAX) NOT NULL'
            : '[[url]] VARCHAR(2083) CHARACTER SET \'ascii\' COLLATE \'ascii_general_ci\' NOT NULL';

        $this->createTable('{{%kanban_link}}', [
            'id' => $this->primaryKey()->unsigned(),
            'task_id' => $this->integer()->unsigned()->notNull(),
            $urlColumn,
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->addColumn(
            '{{%kanban_task}}',
            'card_show_links',
            $this->boolean()->notNull()->defaultValue(0)->after('card_show_checklist')
        );
        $this->addForeignKey(
            '{{%kanban_link_ibfk_1}}',
            '{{%kanban_link}}',
            'task_id',
            '{{%kanban_task}}',
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
        $this->dropForeignKey('{{%kanban_link_ibfk_1}}', '{{%kanban_link}}');
        $this->dropTable('{{%kanban_link}}');
        $this->dropColumn('{{%kanban_task}}', 'card_show_links');
    }

    /**
     * Check if is mssql
     * @return bool
     */
    protected function isMSSSQL()
    {
        return $this->db->driverName === 'sqlsrv' || $this->db->driverName === 'dblib' || $this->db->driverName === 'mssql';
    }
}
