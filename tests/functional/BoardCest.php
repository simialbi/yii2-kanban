<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\functional;

use simialbi\extensions\kanban\FunctionalTester;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\BoardUserAssignment;

class BoardCest
{
    /**
     * @throws \Codeception\Exception\ModuleException
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
    }

    public function checkCreateForm(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/create']);
        $I->seeInTitle('Neuer Plan');
        $I->seeInSource('<h1>Neuer Plan</h1>');
        $I->seeCheckboxIsChecked('#board-is_public');
    }

    public function submitCreateFormEmpty(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/create']);
        $I->submitForm('#sa-kanban-create-plan-form', []);
        $I->see('Name darf nicht leer sein.', '.invalid-feedback');
    }

    public function submitCreateForm(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/create']);
        $I->submitForm('#sa-kanban-create-plan-form', [
            'Board[name]' => 'Test',
            'Board[is_public]' => 0
        ]);

        /** @var Board $board */
        $board = $I->grabRecord(Board::class, [
            'id' => 1
        ]);
        $I->assertNotNull($board);
        $I->assertInstanceOf(Board::class, $board);
        $I->assertEquals('Test', $board->name);
        $I->assertEquals(1, $board->id);
        $I->assertEquals(false, $board->is_public);
        $I->assertEquals(1, $board->created_by);
        $I->assertNull($board->image);

        $I->seeRecord(BoardUserAssignment::class, [
            'board_id' => 1,
            'user_id' => 1
        ]);
    }

    public function submitUpdateForm(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/update', 'id' => 1]);

        $I->seeInFormFields('#sa-kanban-update-plan-form', [
            'Board[name]' => 'Test'
        ]);

        $I->submitForm('#sa-kanban-update-plan-form', [
            'Board[name]' => 'Test 2'
        ]);

        /** @var Board $board */
        $board = $I->grabRecord(Board::class, [
            'id' => 1
        ]);
        $I->assertNotNull($board);
        $I->assertInstanceOf(Board::class, $board);
        $I->assertEquals('Test 2', $board->name);
        $I->assertEquals(1, $board->id);
        $I->assertEquals(false, $board->is_public);
        $I->assertNull($board->image);
    }

    public function viewBoardOnOverview(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/index']);
        $I->seeInTitle('Kanban Hub');
        $I->see('Test 2');
//        $I->seeInSource('<a href="/kanban/plan/view?id=1" class="flex-grow-1 text-body text-decoration-none">');
//        $I->seeInSource('<a class="text-body" href="/kanban/plan/update?id=1" title="Plan bearbeiten">');
//        $I->seeInSource('<a class="text-body" href="/kanban/plan/delete?id=1" title="Plan löschen" data-confirm="Wollen Sie diesen Eintrag wirklich löschen?" data-method="post">');
    }

    /**
     * @throws \Codeception\Exception\ModuleException
     */
    public function dontViewBoardOnOverviewAsJane(FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnPage(['kanban/plan/index']);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Test 2');
    }

    public function viewBoard(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/view', 'id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeInTitle('Test 2');
        $I->see('Test 2');
        $I->see('Neuen Bucket hinzufügen');
    }

    /**
     * @throws \Codeception\Exception\ModuleException
     */
    public function viewBoardAsJaneError(FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnPage(['kanban/plan/view', 'id' => 1]);
        $I->seeResponseCodeIs(403);
    }
}
