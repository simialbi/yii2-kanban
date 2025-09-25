<?php

namespace simialbi\yii2\kanban\commands;

use jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use jamesiarmes\PhpEws\Enumeration\TaskStatusType;
use simialbi\yii2\ews\models\CalendarEvent;
use simialbi\yii2\ews\models\Task as EwsTask;
use simialbi\yii2\kanban\enums\ConnectionTypeEnum;
use simialbi\yii2\kanban\models\Attachment;
use simialbi\yii2\kanban\models\Connection;
use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\kanban\models\TimeWindow;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\TaskEvent;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use simialbi\yii2\kanban\helpers\FileHelper;

/**
 * Class SyncController
 *
 * This controller handles synchronization tasks between the application and external calendars or task management systems,
 * such as Outlook. Its primary functions include importing time windows and tasks, updating existing records with external data,
 * and maintaining synchronization integrity.
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class SyncController extends Controller
{
    /**
     * Import time windows of tasks from outlook
     * @return int
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionImportTimeWindows(): int
    {
        $this->stdout('Importing time windows of tasks from outlook...' . PHP_EOL, Console::FG_PURPLE);

        $aMonthAgo = Yii::$app->formatter->asTimestamp('-1 month');

        // get all time windows from open tasks which have a sync_id and are not older than a month
        $timeWindows = TimeWindow::find()
            ->alias('ti')
            ->joinWith(['task ta'])
            ->where([
                'and',
                ['!=', '{{ta}}.[[status]]', Task::STATUS_DONE],
                ['not', ['{{ti}}.[[sync_id]]' => null]],
                [
                    'or',
                    ['>', 'time_start', $aMonthAgo],
                    ['>', 'time_end', $aMonthAgo]
                ]
            ])
            ->all();

        $calendarEvents = CalendarEvent::find()
            ->where([
                'id' => ArrayHelper::getColumn($timeWindows, 'sync_id'),
            ])
            ->all();

        // loop all timewindows, search for the matching calendar event and update the timewindow if necessary
        foreach ($timeWindows as $timeWindow) {
            // search for calendar event
            $calendarEvent = array_filter($calendarEvents, function ($item) use ($timeWindow) {
                return $item->id == $timeWindow->sync_id;
            });

            if (empty($calendarEvent)) {
                // $calendarEvent not found, delete timeWindow
                $this->stdout("Calendar event not found, deleting Time Window $timeWindow->id" . PHP_EOL . PHP_EOL, Console::FG_RED);

                // delete TimeWindow with deleteAll to bypass beforeDelete
                TimeWindow::deleteAll([
                    'sync_id' => $timeWindow->sync_id
                ]);

                continue;
            }

            $calendarEvent = array_shift($calendarEvent);

            /** @var CalendarEvent $calendarEvent */
            if ($calendarEvent->changeKey != $timeWindow->change_key) {
                $this->stdout("Time Window $timeWindow->id changed" . PHP_EOL);
                $this->stdout('ChangeKey: ' . $calendarEvent->changeKey . PHP_EOL);
                $this->stdout('Time Start: ' . Yii::$app->formatter->asDate($calendarEvent->start, 'php:d.m.Y H:i') . PHP_EOL);
                $this->stdout('Time End: ' . Yii::$app->formatter->asDate($calendarEvent->end, 'php:d.m.Y H:i') . PHP_EOL . PHP_EOL);
                $this->stdout('Original description: ' . $calendarEvent->body . PHP_EOL . PHP_EOL);
                $this->stdout('Description: ' . $this->parseBody($calendarEvent) . PHP_EOL . PHP_EOL);
                $this->stdout('Format: ' . $calendarEvent->format . PHP_EOL . PHP_EOL);

                $timeWindow->change_key = $calendarEvent->changeKey;
                $timeWindow->time_start = Yii::$app->formatter->asDate($calendarEvent->start, 'php:d.m.Y H:i');
                $timeWindow->time_end = Yii::$app->formatter->asDate($calendarEvent->end, 'php:d.m.Y H:i');

                $task = $timeWindow->getTask()->one()->recurrence_parent_id ? $timeWindow->getTask()->one()->getOriginalRecord() : $timeWindow->task;

                $task->subject = $calendarEvent->subject;
                $task->description = $this->parseBody($calendarEvent);

                // Save timeWindow and task
                if (!$timeWindow->save()) {
                    $this->stdout(print_r($timeWindow->errors, 1) . PHP_EOL . PHP_EOL, Console::FG_RED);
                }
                if (!$task->save()) {
                    $this->stdout(print_r($task->errors, 1) . PHP_EOL . PHP_EOL, Console::FG_RED);
                }
            }
        }


        $this->stdout('Done!' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Import tasks from outlook
     * @return integer
     * @throws \Exception
     * @throws Throwable
     */
    public function actionImportTasks(): int
    {
        /** @var Module $module */
        $module = $this->module;

        $connections = Connection::find()
            ->where([
                'type' => ConnectionTypeEnum::Outlook
            ])
            ->all();

        // loop through all outlook connections
        foreach ($connections as $connection) {
            $this->stdout('Importing tasks from outlook for ' . $connection->address->email . '...' . PHP_EOL, Console::FG_PURPLE);

            if (!$connection->import_tasks) {
                $this->stdout('disabled, skipping...' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
                continue;
            }

            // fetch all tasks from outlook of this user
            $ewsTasks = EwsTask::find()
                ->select(['id'])
                ->from([
                    'mailbox' => $connection->address->email
                ])
                ->all();

            if (count($connections) == 0) {
                $this->stdout('no tasks found' . PHP_EOL . PHP_EOL);
            }

            // loop through all tasks
            foreach ($ewsTasks as $ewsTask) {
                $this->stdout('importing task ' . $ewsTask->subject . ' (' . $ewsTask->id . ') ...' . PHP_EOL . PHP_EOL, Console::FG_CYAN);
                $t = EwsTask::findOne([
                    'id' => $ewsTask->id
                ]);

                if ($t->isComplete) {
                    $this->stdout('Task is completed, skipping...' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
                    continue;
                }

                $alreadyImported = Task::findOne([
                    'sync_id' => $t->id,
                    'connection_id' => $connection->id
                ]);
                if ($alreadyImported) {
                    $this->stdout('Task already imported, skipping...' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
                    continue;
                }

                // import task
                $task = new Task([
                    'bucket_id' => $connection->bucket_id,
                    'subject' => $t->subject,
                    'description' => $this->parseBody($t),
                    'start_date' => $t->startDate ? Yii::$app->formatter->asDate($t->startDate, 'php:d.m.Y') : null,
                    'end_date' => $t->dueDate ? Yii::$app->formatter->asDate($t->dueDate, 'php:d.m.Y') : null,
                    'status' => $this->parseStatus($t->status),
                    'sync_id' => $t->id,
                    'connection_id' => $connection->id,
                    'created_by' => $connection->address_id,
                    'updated_by' => $connection->address_id,
                    'created_at' => Yii::$app->formatter->asTimestamp($t->createdAt),
                    'updated_at' => Yii::$app->formatter->asTimestamp($t->updatedAt),
                ]);

                if ($t->isRecurring) {
                    $task->is_recurring = true;
                    $task->recurrence_pattern = $t->recurrence->getString();
                }

                $task->detachBehavior('blameable');
                $task->detachBehavior('timestamp');

                if (!$task->save()) {
                    $this->stdout('Task could not be saved: ' . print_r($task->errors, 1) . PHP_EOL . PHP_EOL, Console::FG_RED);
                    continue;
                }
                $module->trigger(Module::EVENT_TASK_CREATED, new TaskEvent([
                    'task' => $task
                ]));


                // add assignee
                $assignee = new TaskUserAssignment([
                    'task_id' => $task->id,
                    'user_id' => $connection->address_id
                ]);
                if (!$assignee->save()) {
                    $this->stdout('Assignee could not be saved: ' . print_r($assignee->errors, 1) . PHP_EOL . PHP_EOL, Console::FG_RED);
                    $this->stdout('Deleting task and continuing' . PHP_EOL . PHP_EOL);
                    $task->delete();
                    continue;
                }
                $module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
                    'task' => $task,
                    'user' => $connection->address
                ]));


                // import attachments
                $pathWeb = Yii::getAlias($this->module->uploadWeb . '/task/' . $task->id);
                $path = FileHelper::normalizePath(Yii::getAlias($this->module->uploadWebRoot . '/task/' . $task->id));
                if (!file_exists($path)) {
                    FileHelper::createDirectory($path);
                }

                $attachments = $t->attachments ?? [];
                foreach ($attachments as $attachment) {
                    $filePath = FileHelper::normalizePath($path . '/' . ArrayHelper::getValue($attachment, 'name'));

                    try {
                        $saved = file_put_contents($filePath, $attachment->content);
                    } catch (\Throwable $e) {
                        $this->stdout('Error saving attachment ' . $filePath . ' ' . print_r($e, 1) . PHP_EOL . PHP_EOL, Console::FG_RED);
                        $this->stdout(print_r($attachment, 1) . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
                        continue;
                    }
                    if (!$saved) {
                        $this->stdout('Error saving attachment ' . $filePath . PHP_EOL . PHP_EOL, Console::FG_RED);
                        $this->stdout(print_r($attachment, 1) . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
                        continue;
                    }

                    $attachmentModel = new Attachment();
                    $attachmentModel->setAttributes([
                        'task_id' => $task->id,
                        'name' => ArrayHelper::getValue($attachment, 'name'),
                        'mime_type' => ArrayHelper::getValue($attachment, 'mime'),
                        'size' => ArrayHelper::getValue($attachment, 'size'),
                        'path' => $pathWeb . '/' . ArrayHelper::getValue($attachment, 'name'),
                        'sync_id' => ArrayHelper::getValue($attachment, 'id'),
                        'created_by' => $connection->address_id,
                        'updated_by' => $connection->address_id,
                    ], false);

                    $attachmentModel->detachBehavior('blameable');
                    if (!$attachmentModel->save()) {
                        $this->stdout('Error saving attachment model ' . $filePath . PHP_EOL . PHP_EOL, Console::FG_RED);
                        $this->stdout('Deleting task' . PHP_EOL . PHP_EOL);
                        $task->delete();
                        continue;
                    }

                    $module->trigger(Module::EVENT_ATTACHMENT_ADDED, new TaskEvent([
                        'task' => $task,
                        'user' => $connection->address
                    ]));
                }

                // Delete outlook task if option is set
                $this->stdout('Deleting outlook task' . PHP_EOL . PHP_EOL);
                if ($connection->delete_tasks_after_import) {
                    try {
                        $t->delete();
                        $this->stdout('Outlook task deleted' . PHP_EOL . PHP_EOL);
                    } catch (\Throwable $e) {
                        $this->stdout('Error deleting outlook task: ' . print_r($e, 1) . PHP_EOL . PHP_EOL, Console::FG_RED);
                        $this->stdout('Deleting task' . PHP_EOL . PHP_EOL);
                        $task->delete();
                        continue;
                    }
                } else {
                    $this->stdout('Disabled, Skipped deleting...' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
                }
            }
        }

        $this->stdout(PHP_EOL . PHP_EOL . 'Done!', Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Parse event body
     * @param CalendarEvent|EwsTask $item
     * @return string
     */
    protected function parseBody(CalendarEvent|EwsTask $item): string
    {
        $string = $item->body;
        if (empty($string)) {
            return '';
        }

        // Remove link we added to the body on sending to outlook
        $string = preg_replace('#<span class="remove">.*</span>#s', '', $string);

        if ($item->format === BodyTypeType::HTML) {
            $html = '';
            $dom = new \DOMDocument();

            @$dom->loadHTML($string);
            $xpath = new \DOMXPath($dom);


            // Body is xml
            $ws = $xpath->query('//div[@class="WordSection1"]');
            if ($ws->length > 0) {
                $ws = $ws->item(0);

                /** @var \DOMElement|\DOMText $ws */
                if ($ws?->childNodes) {
                    try {
                        foreach ($ws->childNodes as $ws) {
                            if ($ws instanceof \DOMElement) {
                                if ($ws->tagName === 'p' && ($value = trim($ws->nodeValue)) !== '') {
                                    $html .= "<p>$value</p>\n";
                                } elseif ($ws->tagName === 'div') {
                                    break;
                                }
                            }
                        }
                    } catch (\Throwable) {
                        $this->stdout('Error parsing WordSection1' . PHP_EOL, Console::FG_RED);
                    }
                }

                $html = preg_replace('#<p>([ \xc2\xa0]+?)</p>#', '', $html);
                return preg_replace('#<p>(.+?)</p>\n<p>(.+?)</p>#', "<p>$1\n<br>\n$2</p>", $html);
            }

            // body is html
            /** @var \DOMElement|\DOMText $node */
            $body = $xpath->query('//body')->item(0);
            if (!empty($body) && $body->hasChildNodes()) {
                if ($item instanceof EwsTask) {
                    // remove attachments from body
                    $this->removeAttachmentsFromBody($body);
                    // remove empty elements
                    $this->removeEmptyElements($body);
                }

                foreach ($body->childNodes as $node) {
                    if ($node instanceof \DOMElement && $node->tagName !== 'p') {
                        $body->removeChild($node);
                    }
                    $html .= $dom->saveHTML($node);
                }
            }

            return $html;
        }

        return $string;
    }

    /**
     * Remove attachments from body
     * @param \DOMNode $node
     */
    protected function removeAttachmentsFromBody(\DOMNode $node): void
    {
        /** @var \DOMElement|\DOMText $child */
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);
            if ($child->hasAttributes()) {
                $toCheck = ['src', 'href'];
                foreach ($toCheck as $check) {
                    $text = $child->getAttribute($check);
                    if ($text && str_contains($text, 'cid:')) {
                        $node->removeChild($child);
                        continue 2;
                    }
                }
            }
            $this->removeAttachmentsFromBody($child);
        }
    }

    /**
     * Remove empty elements from body
     * @param \DOMNode $node
     * @return void
     */
    protected function removeEmptyElements(\DOMNode $node): void
    {
        $exceptions = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);
            if (!in_array($child->nodeName, $exceptions) && $child->nodeName !== '#text' && trim($child->nodeValue) == '') {
                $node->removeChild($child);
                continue;
            }
            $this->removeEmptyElements($child);
        }
    }

    /**
     * Parse status from outlook to kanban
     * @param string $status
     * @return int
     */
    protected function parseStatus(string $status): int
    {
        return match ($status) {
            TaskStatusType::COMPLETED => Task::STATUS_DONE,
            TaskStatusType::DEFERRED, TaskStatusType::NOT_STARTED, TaskStatusType::WAITING_ON_OTHERS => Task::STATUS_NOT_BEGUN,
            TaskStatusType::IN_PROGRESS => Task::STATUS_IN_PROGRESS,
        };
    }
}
