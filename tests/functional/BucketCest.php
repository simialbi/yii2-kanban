<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\extensions\kanban\functional;

use simialbi\extensions\kanban\FunctionalTester;
use simialbi\yii2\kanban\models\Bucket;

class BucketCest
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
        $I->amOnPage(['kanban/bucket/create', 'boardId' => 1]);

        $I->seeElement('#bucket-name');
        $I->seeElement('#sa-kanban-create-bucket-form');
    }

    public function submitCreateFormEmpty(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/bucket/create', 'boardId' => 1]);
        $I->submitForm('#sa-kanban-create-bucket-form', []);
        $I->see('Name darf nicht leer sein.', '.invalid-feedback');
    }

    public function submitCreateForm(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/bucket/create', 'boardId' => 1]);
        $I->submitForm('#sa-kanban-create-bucket-form', [
            'Bucket[name]' => 'Test bucket'
        ]);

        /** @var Bucket $bucket */
        $bucket = $I->grabRecord(Bucket::class, [
            'id' => 1
        ]);
        $I->assertNotNull($bucket);
        $I->assertInstanceOf(Bucket::class, $bucket);
        $I->assertEquals('Test bucket', $bucket->name);
        $I->assertEquals(1, $bucket->id);
        $I->assertEquals(1, $bucket->created_by);
        $I->assertEquals(1, $bucket->sort);
        $I->assertEquals(1, $bucket->board_id);
    }

    public function viewBucketOnBoard(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/plan/view', 'id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeInSource('<turbo-frame id="bucket-1-frame"');
    }

    public function viewBucket(FunctionalTester $I)
    {
        $I->amOnPage(['kanban/bucket/view', 'id' => 1, 'readonly' => 0]);
        $I->seeElement('#sa-kanban-create-task-form');
        $I->seeElement('#dropdown-user-create-task-1');
    }
}
