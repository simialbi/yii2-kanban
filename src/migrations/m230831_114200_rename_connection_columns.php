<?php

namespace simialbi\yii2\kanban\migrations;

use yii\db\Migration;

class m230831_114200_rename_connection_columns extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function safeUp(): void
    {
        // Attachment
        $this->renameColumn('{{%kanban__attachment}}', 'wedo_attachment_id', 'sync_id');
        $this->alterColumn('{{%kanban__attachment}}', 'sync_id', $this->string(255)->null());

        // Board
        $this->renameColumn('{{%kanban__board}}', 'wedo_checklist_id', 'sync_id');
        $this->alterColumn('{{%kanban__board}}', 'sync_id', $this->string(255)->null());

        // Bucket
        $this->renameColumn('{{%kanban__bucket}}', 'wedo_section_id', 'sync_id');
        $this->alterColumn('{{%kanban__bucket}}', 'sync_id', $this->string(255)->null());

        // ChecklistElement
        $this->renameColumn('{{%kanban__checklist_element}}', 'wedo_subtask_id', 'sync_id');
        $this->alterColumn('{{%kanban__checklist_element}}', 'sync_id', $this->string(255)->null());

        // Comment
        $this->renameColumn('{{%kanban__comment}}', 'wedo_comment_id', 'sync_id');
        $this->alterColumn('{{%kanban__comment}}', 'sync_id', $this->string(255)->null());

        // Task
        $this->renameColumn('{{%kanban__task}}', 'wedo_task_id', 'sync_id');
        $this->alterColumn('{{%kanban__task}}', 'sync_id', $this->string(255)->null());

        $this->renameColumn('{{%kanban__task}}', 'wedo_connection_id', 'connection_id');
    }

    /**
     * {@inheritDoc}
     */
    public function safeDown(): void
    {
        // Attachment
        $this->alterColumn('{{%kanban__attachment}}', 'sync_id', $this->integer()->unsigned()->null());
        $this->renameColumn('{{%kanban__attachment}}', 'sync_id', 'wedo_attachment_id');

        // Board
        $this->alterColumn('{{%kanban__board}}', 'sync_id', $this->integer()->unsigned()->null());
        $this->renameColumn('{{%kanban__board}}', 'sync_id', 'wedo_checklist_id');

        // Bucket
        $this->alterColumn('{{%kanban__bucket}}', 'sync_id', $this->integer()->unsigned()->null());
        $this->renameColumn('{{%kanban__bucket}}', 'sync_id', 'wedo_section_id');

        // ChecklistElement
        $this->alterColumn('{{%kanban__checklist_element}}', 'sync_id', $this->integer()->unsigned()->null());
        $this->renameColumn('{{%kanban__checklist_element}}', 'sync_id', 'wedo_subtask_id');

        // Comment
        $this->alterColumn('{{%kanban__comment}}', 'sync_id', $this->integer()->unsigned()->null());
        $this->renameColumn('{{%kanban__comment}}', 'sync_id', 'wedo_comment_id');

        // Comment
        $this->alterColumn('{{%kanban__task}}', 'sync_id', $this->integer()->unsigned()->null());
        $this->renameColumn('{{%kanban__task}}', 'sync_id', 'wedo_task_id');

        $this->renameColumn('{{%kanban__task}}', 'connection_id', 'wedo_connection_id');
    }
}
