<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m190614_125145_init extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%kanban_board}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(255)->notNull(),
            'image' => $this->string(512)->null()->defaultValue(null),
            'is_public' => $this->boolean()->notNull()->defaultValue(1),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_bucket}}', [
            'id' => $this->primaryKey()->unsigned(),
            'board_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255),
            'sort' => $this->smallInteger()->unsigned()->notNull(),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_task}}', [
            'id' => $this->primaryKey()->unsigned(),
            'bucket_id' => $this->integer()->unsigned()->notNull(),
            'subject' => $this->string(255),
            'status' => $this->tinyInteger()->unsigned()->notNull()->defaultValue(5),
            'start_date' => $this->integer()->unsigned()->null()->defaultValue(null),
            'end_date' => $this->integer()->unsigned()->null()->defaultValue(null),
            'description' => $this->text()->null()->defaultValue(null),
            'card_show_description' => $this->boolean()->notNull()->defaultValue(0),
            'card_show_checklist' => $this->boolean()->notNull()->defaultValue(0),
            'sort' => $this->smallInteger()->notNull(),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_checklist_element}}', [
            'id' => $this->primaryKey()->unsigned(),
            'task_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255)->notNull(),
            'is_done' => $this->boolean()->notNull()->defaultValue(0),
            'sort' => $this->smallInteger()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_attachment}}', [
            'id' => $this->primaryKey()->unsigned(),
            'task_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255)->notNull(),
            'path' => $this->string(512)->notNull(),
            'mime_type' => $this->string(255)->notNull(),
            'size' => $this->integer()->unsigned()->notNull(),
            'card_show' => $this->boolean()->notNull()->defaultValue(0),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'updated_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_comment}}', [
            'id' => $this->primaryKey()->unsigned()->notNull(),
            'task_id' => $this->integer()->unsigned()->notNull(),
            'text' => $this->text()->notNull(),
            'created_by' => $this->string(64)->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->notNull()
        ]);
        $this->createTable('{{%kanban_board_user_assignment}}', [
            'board_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'PRIMARY KEY ([[board_id]], [[user_id]])'
        ]);
        $this->createTable('{{%kanban_task_user_assignment}}', [
            'task_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'PRIMARY KEY ([[task_id]], [[user_id]])'
        ]);

        $this->addForeignKey(
            '{{%kanban_bucket_ibfk_1}}',
            '{{%kanban_bucket}}',
            'board_id',
            '{{%kanban_board}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban_task_ibfk_1}}',
            '{{%kanban_task}}',
            'bucket_id',
            '{{%kanban_bucket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban_checklist_element_ibfk_1}}',
            '{{%kanban_checklist_element}}',
            'task_id',
            '{{%kanban_task}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban_attachment_ibfk_1}}',
            '{{%kanban_attachment}}',
            'task_id',
            '{{%kanban_task}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban_comment_ibfk_1}}',
            '{{%kanban_comment}}',
            'task_id',
            '{{%kanban_task}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban_board_user_assignment_ibfk_1}}',
            '{{%kanban_board_user_assignment}}',
            'board_id',
            '{{%kanban_board}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%kanban_task_user_assignment_ibfk_1}}',
            '{{%kanban_task_user_assignment}}',
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
        $this->dropForeignKey('{{%kanban_bucket_ibfk_1}}', '{{%kanban_bucket}}');
        $this->dropForeignKey('{{%kanban_task_ibfk_1}}', '{{%kanban_task}}');
        $this->dropForeignKey('{{%kanban_checklist_element_ibfk_1}}', '{{%kanban_checklist_element}}');
        $this->dropForeignKey('{{%kanban_attachment_ibfk_1}}', '{{%kanban_attachment}}');
        $this->dropForeignKey('{{%kanban_comment_ibfk_1}}', '{{%kanban_comment}}');
        $this->dropForeignKey('{{%kanban_task_user_assignment_ibfk_1}}', '{{%kanban_task_user_assignment}}');
        $this->dropForeignKey('{{%kanban_board_user_assignment_ibfk_1}}', '{{%kanban_board_user_assignment}}');

        $this->dropTable('{{%kanban_task_user_assignment}}');
        $this->dropTable('{{%kanban_board_user_assignment}}');
        $this->dropTable('{{%kanban_comment}}');
        $this->dropTable('{{%kanban_attachment}}');
        $this->dropTable('{{%kanban_checklist_element}}');
        $this->dropTable('{{%kanban_task}}');
        $this->dropTable('{{%kanban_bucket}}');
        $this->dropTable('{{%kanban_board}}');
    }
}
