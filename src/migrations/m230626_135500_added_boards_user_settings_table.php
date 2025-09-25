<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m230626_135500_added_boards_user_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable(
            '{{%kanban__board_user_setting}}',
            [
                'id' => $this->primaryKey()->unsigned()->notNull(),
                'board_id' => $this->integer()->unsigned()->notNull(),
                'user_id' => $this->integer()->unsigned()->notNull(),
                'is_hidden' => $this->boolean()->notNull()->defaultValue(false),
            ]
        );
        $this->addForeignKey(
            '{{%kanban__board_user_setting_ibfk_1}}',
            '{{%kanban__board_user_setting}}',
            'board_id',
            '{{%kanban__board}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban__board_user_setting_ibfk_2}}',
            '{{%kanban__board_user_setting}}',
            'user_id',
            '{{%addresspool__address}}',
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
        $this->dropForeignKey('{{%kanban__board_user_setting_ibfk_2}}', '{{%kanban__board_user_setting}}');
        $this->dropForeignKey('{{%kanban__board_user_setting_ibfk_1}}', '{{%kanban__board_user_setting}}');
        $this->dropTable('{{%kanban__board_user_setting}}');
    }
}
