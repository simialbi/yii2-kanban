<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\functional;

use simialbi\extensions\kanban\FunctionalTester;

class BoardCest
{
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
    }

    public function checkCreateForm(FunctionalTester $I)
    {
        $I->amOnRoute('kanban/plan/create');
        $I->seeInTitle('Neuer Plan');
        $I->see('<h1>Neuer Plan</h1>');
        $I->seeCheckboxIsChecked('#board-is_public');
    }

    public function submitCreateFormEmpty(FunctionalTester $I)
    {
        $I->amOnRoute('kanban/plan/create');
        $I->submitForm('#w0', []);
        $I->see('Name darf nicht leer sein.', '.invalid-feedback');
    }
}
