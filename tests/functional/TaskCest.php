<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\functional;

use simialbi\extensions\kanban\FunctionalTester;
use simialbi\yii2\kanban\models\Task;

class TaskCest
{
    /**
     * @throws \Codeception\Exception\ModuleException
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
    }

    public function submitCreateForm(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/bucket/view', 'id' => 1, 'readonly' => 0]);

        $endDate =  date('d.m.Y');

        $I->submitForm('#sa-kanban-create-task-form', [
            'Task[subject]' => 'Test task',
            'Task[end_date]' => $endDate,
            'assignees[0]' => 1,
            'assignees[1]' => 2
        ]);

        /** @var Task $task */
        $task = $I->grabRecord(Task::class, [
            'id' => 1
        ]);
        $I->assertNotNull($task);
        $I->assertInstanceOf(Task::class, $task);
        $I->assertEquals('Test task', $task->subject);
        $I->assertEquals(1, $task->id);
        $I->assertEquals(1, $task->created_by);
        $I->assertEquals(1, $task->sort);
        $I->assertEquals(1, $task->bucket_id);
        $I->assertEquals($endDate, date('d.m.Y', $task->end_date));
        $I->assertNull($task->description);
        $I->assertNull($task->responsible_id);
        $I->assertNull($task->start_date);
        $I->assertEmpty($task->checklistElements);
        $I->assertEmpty($task->links);
        $I->assertEmpty($task->attachments);
        $I->assertFalse((bool)$task->is_recurring);
        $I->assertFalse((bool)$task->card_show_description);
        $I->assertFalse((bool)$task->card_show_checklist);
        $I->assertFalse((bool)$task->card_show_links);
    }

    public function checkUpdateForm(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/task/update', 'id' => 1, 'return' => 'card', 'readonly' => 0]);

        $I->seeElement('#sa-kanban-task-modal-form');
        $I->seeInFormFields('#sa-kanban-task-modal-form', [
            'Task[subject]' => 'Test task',
            'Task[bucket_id]' => 1,
            'Task[status]' => Task::STATUS_NOT_BEGUN,
            'Task[start_date]' => '',
            'Task[end_date]' => date('d.m.Y'),
            'Task[responsible_id]' => '',
            'Task[description]' => ''
        ]);
    }
}
