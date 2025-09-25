<?php

namespace simialbi\yii2\kanban\models;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\LegacyFreeBusyType;
use simialbi\yii2\ews\models\CalendarEvent;
use simialbi\yii2\kanban\enums\ConnectionTypeEnum;
use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\bootstrap5\Html;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class OutlookEvent
 *
 * @property int $id
 * @property int $task_id
 * @property int|string $user_id
 * @property int $time_start
 * @property int $time_end
 * @property string $sync_id
 * @property string $change_key
 *
 * @property-read Task $task
 * @property-read UserInterface $user
 */
class TimeWindow extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__task_time_window}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'task_id'], 'integer'],
            ['user_id', 'string', 'max' => 64],
            [['time_start'], 'date', 'format' => 'php:d.m.Y H:i', 'timestampAttribute' => 'time_start', 'defaultTimeZone' => Yii::$app->timeZone],
            [['time_end'], 'date', 'format' => 'php:d.m.Y H:i', 'timestampAttribute' => 'time_end', 'defaultTimeZone' => Yii::$app->timeZone],
            [['sync_id'], 'string', 'max' => 152],
            [['change_key'], 'string', 'max' => 40],

            [['task_id', 'user_id', 'time_start', 'time_end'], 'required'],

            [
                'time_end',
                'simialbi\yii2\validators\DateCompareValidator',
                'compareAttribute' => 'time_start',
                'operator' => '>',
                'format' => 'php:d.m.Y H:i'
            ],

            [
                'task_id',
                'exist',
                'targetRelation' => 'task'
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'task_id' => Yii::t('simialbi/kanban/model/time-window', 'Task'),
            'user_id' => Yii::t('simialbi/kanban/model/time-window', 'User'),
            'time_start' => Yii::t('simialbi/kanban/model/time-window', 'Start'),
            'time_end' => Yii::t('simialbi/kanban/model/time-window', 'End'),
        ];
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function beforeSave($insert): bool
    {
        // check if timeWindows should be exported
        $connection = Connection::findOne([
            'user_id' => $this->user_id,
            'type' => ConnectionTypeEnum::Outlook
        ]);
        if (empty($connection) || !$connection->export_time_windows) {
            return true;
        }

        // check if timeWindow was updated via sync, then we stop here
        if (!$insert && $this->getDirtyAttributes(['change_key']) !== []) {
            return true;
        }


        // Lets go

        /** @var Module $module */
        $module = Yii::$app->getModule('schedule');

        if (empty($this->sync_id)) {
            $calendarEvent = new CalendarEvent();
        } else {
            $calendarEvent = CalendarEvent::findOne([
                'id' => $this->sync_id,
                'changeKey' => $this->change_key
            ]) ?? new CalendarEvent();
        }

        $calendarEvent->subject = $this->task->subject;

        $calendarEvent->format = BodyTypeType::HTML;
        $calendarEvent->body = ($this->task->description ?? '') . '<span class="remove"> <br>' .
            Html::a(
                Yii::t('simialbi/kanban/time-window', 'Link to task <b>{id}</b>', ['id' => $this->task_id]),
                Url::to(['/schedule/plan/view', 'id' => $this->task->board->id, 'showTask' => $this->task_id], 'https'),
                ['target' => '_blank']
            ) .
            '</span>';

        $calendarEvent->start = Yii::$app->formatter->asDatetime(
            $this->time_start,
            'yyyy-MM-dd HH:mm xxx'
        );
        $calendarEvent->end = Yii::$app->formatter->asDatetime(
            $this->time_end,
            'yyyy-MM-dd HH:mm xxx'
        );

        $calendarEvent->isAllDay = false;
        $calendarEvent->status = LegacyFreeBusyType::BUSY;

        if ($this->task->recurrence_parent_id) {
            $calendarEvent->recurrence = $this->task->getOriginalRecord()->recurrence_pattern;
            $calendarEvent->recurrence->setStartDate((new \DateTime())->setTimestamp($this->time_start));
        }

        $attachments = [];
        foreach ($this->task->attachments as $attachment) {
            $attachments[] = new \simialbi\yii2\ews\models\Attachment([
                'name' => $attachment->name,
                'content' => file_get_contents(
                    FileHelper::normalizePath(
                        str_replace(
                            Yii::getAlias($module->uploadWeb),
                            Yii::getAlias($module->uploadWebRoot),
                            $attachment->path
                        )
                    )
                ),
                'mime' => $attachment->mime_type,
                'isInline' => false
            ]);
        }
        $calendarEvent->attachments = $attachments;

        try {
            $calendarEvent->save(true, null, ['mailbox' => Yii::$app->user->identity->email]);
            $this->sync_id = $calendarEvent->id;
            $this->change_key = $calendarEvent->changeKey;

            Yii::debug($calendarEvent->toArray(), __METHOD__);
        } catch (\simialbi\yii2\ews\Exception|StaleObjectException|Exception|\Throwable $e) {
            Yii::error(print_r($e, 1) . print_r(ArrayHelper::toArray($calendarEvent), 1), __METHOD__);
        }

        return parent::beforeSave($insert);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if (!empty($this->sync_id)) {
            $calendarEvent = CalendarEvent::findOne([
                'id' => $this->sync_id,
                'changeKey' => $this->change_key
            ]);

            if ($calendarEvent) {
                try {
                    $success = $calendarEvent->delete();
                    if ($success === false) {
                        return false;
                    }
                } catch (StaleObjectException|Exception $e) {
                    Yii::error($e->getMessage(), __METHOD__);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get task relation
     * @return ActiveQuery
     */
    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     */
    public function getUser(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->user_id);
    }
}
