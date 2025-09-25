<?php

declare(strict_types=1);

namespace simialbi\extensions\kanban\unit;

use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Frequency;
use Recurr\Rule;
use simialbi\extensions\kanban\UnitTester;
use simialbi\yii2\kanban\behaviors\RepeatableBehavior;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\BoardUserAssignment;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use yii\db\Exception;

final class RepeatableCest
{
    public int $boardId;
    public int $bucketId;

    public function _before(UnitTester $I): void
    {
        // Code here will be executed before each test.
    }

    /**
     * @throws Exception
     */
    public function initTest(UnitTester $I): void
    {
        $rep = new RepeatableBehavior();

        \Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        \Yii::$app->db->createCommand('TRUNCATE TABLE ' . Bucket::tableName())->execute();
        \Yii::$app->db->createCommand('TRUNCATE TABLE ' . Board::tableName())->execute();
        \Yii::$app->db->createCommand('TRUNCATE TABLE ' . Task::tableName())->execute();
        \Yii::$app->db->createCommand('TRUNCATE TABLE ' . BoardUserAssignment::tableName())->execute();
        \Yii::$app->db->createCommand('TRUNCATE TABLE ' . $rep->recurrenceDoneTableName)->execute();
        \Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();

        // Generate Board and Bucket
        $this->boardId = $I->haveRecord(Board::class, [
            'name' => 'RepeatableBoard'
        ]);
        $this->bucketId = $I->haveRecord(Bucket::class, [
            'name' => 'Bucket1',
            'board_id' => $this->boardId
        ]);
    }

    /**
     * @throws InvalidArgument
     * @throws \Exception
     */
    public function dailyTest(UnitTester $I): void
    {
        // INTERVALL 1
        $rule = (new Rule())->setFreq(Frequency::DAILY)->setInterval(1);
        $task = $this->createTask($I, $rule);

        $I->assertTrue($task->isRecurrentInstance());

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-02', $this->getStartDateFormatted($task));

        // third occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-03', $this->getStartDateFormatted($task));

        // change status
        $this->saveStatus($task, Task::STATUS_IN_PROGRESS);
        $I->assertEquals('2025-01-03', $this->getStartDateFormatted($task));


        // INTERVALL > 0
        $rule->setInterval(3);
        $task = $this->createTask($I, $rule);

        // First occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-04', $this->getStartDateFormatted($task));
    }

    /**
     * @throws InvalidRRule
     * @throws Exception
     * @throws InvalidArgument
     * @throws \Exception
     */
    public function weeklyTest(UnitTester $I): void
    {
        // EVERY WEEK
        $rule = (new Rule)->setFreq(Frequency::WEEKLY)->setInterval(1);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-08', $this->getStartDateFormatted($task));


        // EVERY WEEK THURSDAY
        $rule->setByDay(['TH']);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-02', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-09', $this->getStartDateFormatted($task));


        // EVERY WEEK WEDNESDAY, FRIDAY
        $rule->setByDay(['WE', 'FR']);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-03', $this->getStartDateFormatted($task));

        // third occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-08', $this->getStartDateFormatted($task));
    }


    /**
     * @throws InvalidArgument
     * @throws Exception
     * @throws \Exception
     */
    public function monthlyTest(UnitTester $I): void
    {
        $rule = (new Rule)->setFreq(Frequency::MONTHLY)->setInterval(1);

        // EVERY MONTH
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-02-01', $this->getStartDateFormatted($task));


        // EVERY SECOND MONTH
        $rule->setInterval(2);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-03-01', $this->getStartDateFormatted($task));


        // 28 OF MONTH, EVERY THIRD MONTH
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))
            ->setDate(2025, 3, 28)
            ->setTime(0, 0);
        $rule->setByMonthDay([28])->setInterval(3);
        $task = $this->createTask($I, $rule, $date);

        // first occurrence
        $I->assertEquals('2025-03-28', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-06-28', $this->getStartDateFormatted($task));

        // third occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-09-28', $this->getStartDateFormatted($task));

        // forth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-12-28', $this->getStartDateFormatted($task));

        // fifth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2026-03-28', $this->getStartDateFormatted($task));


        // EVERY SECOND TUESDAY ALL 4 MONTHS
        $rule = (new Rule)->setFreq(Frequency::MONTHLY)->setInterval(4)->setByDay(['2TU']);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-14', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-05-13', $this->getStartDateFormatted($task));

        // third occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-09-09', $this->getStartDateFormatted($task));

        // forth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2026-01-13', $this->getStartDateFormatted($task));


        // LAST DAY OF MONTH EVERY MONTH
        $rule = (new Rule)->setFreq(Frequency::MONTHLY)->setInterval(1)->setByMonthDay([-1]);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-31', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-02-28', $this->getStartDateFormatted($task));

        // third occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-03-31', $this->getStartDateFormatted($task));

        // forth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-04-30', $this->getStartDateFormatted($task));

        // fifth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-05-31', $this->getStartDateFormatted($task));

        // sixth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-06-30', $this->getStartDateFormatted($task));
    }

    /**
     * @throws InvalidArgument
     * @throws \Exception
     */
    public function yearlyTest(UnitTester $I): void
    {
        // EVERY YEAR
        $rule = (new Rule)->setFreq(Frequency::YEARLY)->setInterval(1);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2026-01-01', $this->getStartDateFormatted($task));


        // EVERY SECOND YEAR
        $rule->setInterval(2);
        $task = $this->createTask($I, $rule);
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2027-01-01', $this->getStartDateFormatted($task));


        // SAME DATE EVERY 2 YEARS
        $rule->setByMonthDay([28])->setByMonth([2])->setInterval(2);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-02-28', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2027-02-28', $this->getStartDateFormatted($task));


        // THIRD FRIDAY IN JULY every 3 YEARS
        $rule = (new Rule)->setFreq(Frequency::YEARLY)->setInterval(3)->setByDay(['3FR'])->setByMonth([7]);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-07-18', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2028-07-21', $this->getStartDateFormatted($task));
    }

    /**
     * @throws InvalidArgument
     * @throws \Exception
     * @throws \Throwable
     */
    public function exDatesTest(UnitTester $I): void
    {
        $rule = (new Rule())->setFreq(Frequency::DAILY)->setInterval(1);
        $task = $this->createTask($I, $rule);

        // first occurrence
        $I->assertEquals('2025-01-01', $this->getStartDateFormatted($task));

        // second occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-02', $this->getStartDateFormatted($task));

        // exclude third occurrence
        $task->description = 'asdf';
        $task->save();
        /** @var Task $task */
        $task = $I->grabRecord(Task::class, ['id' => $task->recurrence_parent_id]);
        $I->assertEquals('2025-01-03', $this->getStartDateFormatted($task));

        // fourth occurrence
        $this->saveStatus($task);
        $I->assertEquals('2025-01-05', $this->getStartDateFormatted($task));

        // sixth occurrence (fifth deleted)
        $task->delete();
        /** @var Task $task */
        $task = $I->grabRecord(Task::class, ['id' => $task->recurrence_parent_id]);
        $I->assertEquals('2025-01-06', $this->getStartDateFormatted($task));
    }

    /**
     * @throws \Exception
     */
    protected function createTask(UnitTester $I, Rule $rule, ?\DateTime $date = null): Task
    {
        if ($date === null) {
            $date = (new \DateTime('now', new \DateTimeZone('UTC')))
                ->setDate(2025, 1, 1)
                ->setTime(0, 0);
        }

        $taskId = $I->haveRecord(Task::class, [
            'subject' => 'TestRepeatableDaily',
            'bucket_id' => $this->bucketId,
            'start_date' => $date->getTimestamp(),
            'is_recurring' => true,
            'recurrence_pattern' => $rule->getString()
        ]);

        return $I->grabRecord(Task::class, ['id' => $taskId]);
    }

    protected function getStartDateFormatted(Task $task): string
    {
        return (new \DateTime())->setTimestamp($task->start_date)->format('Y-m-d');
    }

    /**
     * @throws Exception
     */
    protected function saveStatus(Task $task, $status = Task::STATUS_DONE): void
    {
        $task->status = $status;
        $task->save(true, ['status']);
        $task->refresh();
    }
}
