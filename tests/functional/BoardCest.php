<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\functional;

use simialbi\extensions\kanban\FunctionalTester;
use simialbi\yii2\kanban\models\Board;

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
        $I->amOnRoute('kanban/plan/create');
        $I->seeInTitle('Neuer Plan');
//        $I->see('<h1>Neuer Plan</h1>');
        $I->seeCheckboxIsChecked('#board-is_public');
    }

    public function submitCreateFormEmpty(FunctionalTester $I)
    {
        $I->amOnRoute('kanban/plan/create');
        $I->submitForm('#sa-kanban-create-plan-form', []);
        $I->see('Name darf nicht leer sein.', '.invalid-feedback');
    }

    public function submitCreateForm(FunctionalTester $I)
    {
        $I->amOnRoute('kanban/plan/create');
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
        $I->assertNull($board->image);
    }

    public function submitUpdateForm(FunctionalTester $I)
    {
        $I->amOnRoute('kanban/plan/update', ['id' => 1]);

        $I->seeInFormFields('#sa-kanban-update-plan-form', [
            'Board[name]' => 'Test',
            'Board[uploadedFile]' => '',
            'Board[is_public]' => false
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
}
