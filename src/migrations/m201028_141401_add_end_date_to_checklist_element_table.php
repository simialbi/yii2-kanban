<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m201028_141401_add_end_date_to_checklist_element_table extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%kanban_checklist_element}}',
            'end_date',
            $this->integer()->unsigned()->null()->defaultValue(null)->after('name')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%kanban_checklist_element}}', 'end_date');
    }
}
