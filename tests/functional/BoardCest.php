<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\functional;

use simialbi\extensions\kanban\FunctionalTester;

class BoardCest
{
    public function _fixtures()
    {
        return [
            'user' => [
                'class' => '\simialbi\extensions\kanban\fixtures\UserFixture',
                'dataFile' => codecept_data_dir('user.php')
            ]
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
    }

//    public function checkCreateForm(FunctionalTester $I)
//    {
//        $I->amOnRoute('kanban/plan/create');
//    }
}
