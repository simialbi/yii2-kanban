<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m240426_144000_checklist_template extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('{{%kanban__checklist_template}}', [
            'id' => $this->primaryKey()->unsigned(),
            'board_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255)->notNull(),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);

        $this->addForeignKey(
            '{{%kanban__checklist_template_ibfk_1}}',
            '{{%kanban__checklist_template}}',
            'board_id',
            '{{%kanban__board}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%kanban__checklist_template_element}}', [
            'id' => $this->primaryKey()->unsigned(),
            'template_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string()->notNull(),
            'dateOffset' => $this->integer(),
            'sort' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addForeignKey(
            '{{%kanban__checklist_template_element_ibfk_1}}',
            '{{%kanban__checklist_template_element}}',
            'template_id',
            '{{%kanban__checklist_template}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('{{%kanban__checklist_template_element_ibfk_1}}', '{{%kanban__checklist_template_element}}');
        $this->dropTable('{{%kanban__checklist_template_element}}');

        $this->dropForeignKey('{{%kanban__checklist_template_ibfk_1}}', '{{%kanban__checklist_template}}');
        $this->dropTable('{{%kanban__checklist_template}}');
    }
}
