<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;
use yii\helpers\ArrayHelper;

class m191217_112935_add_ticket_module_link extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%kanban_task}}',
            'ticket_id',
            $this->integer()->unsigned()->null()->defaultValue(null)->after('bucket_id')
        );

        if (ArrayHelper::keyExists($this->db->quoteTableName('{{%ticket_ticket}}'), $this->db->schema->tableNames)) {
            $this->addForeignKey(
                '{{%kanban_task_ibfk_2}}',
                '{{%kanban_task}}',
                'ticket_id',
                '{{%ticket_ticket}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%kanban_task_ibfk_2}}', '{{%kanban_task}}');

        $this->dropColumn('{{%kanban_task}}', 'ticket_id');
    }
}
