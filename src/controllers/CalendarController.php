<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class CalendarController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class CalendarController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'get-ics';

    /**
     * Creates an ics-File with all open and assigned tasks
     * Sends the file to the browser to import into Outlook or any other email program.
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetIcs()
    {
        $str = "BEGIN:VCALENDAR\r\n";
        $str .= "VERSION:2.0\r\n";
        $str .= "PRODID:-//simialbi/yii2/kanban//DE\r\n";
        $str .= 'NAME:Kanban' . (Yii::$app->user->isGuest ? '' : ' ' . Yii::$app->user->identity->getName()) . "\r\n";
        $str .= 'X-WR-NAME:Kanban' . (Yii::$app->user->isGuest ? '' : ' ' . Yii::$app->user->identity->getName()) . "\r\n";
        $str .= "REFRESH-INTERVAL;VALUE=DURATION:PT15M\r\n";
        $str .= "X-PUBLISHED-TTL:PT15M\r\n";

        $userId = Yii::$app->user->getId();
        $tasks = Task::find()
            ->joinWith('assignments')
            ->where([
                'and',
                ['user_id' => $userId],
                ['not', ['status' => Task::STATUS_DONE]]
            ])
            ->all();

        foreach ($tasks as $task) {
            // send only if at least one date is present
            if (!$task->start_date && !$task->end_date) {
                continue;
            }

            $start = $task->start_date;
            $end = $task->end_date;

            if (!$start) {
                $start = $end;
            }

            // edit endDate
            if ($end) {
                $end = $end + 86400;
            } else {
                $end = $start + 86400;
            }

            $description = $this->br2nl($task->description);

            $str .= "BEGIN:VEVENT\r\n";
            $str .= "UID:{$task->id}\r\n";
            $str .= 'DTSTART:' . Yii::$app->formatter->asDate($start, 'yyyyMMdd') . "\r\n";
            $str .= 'DTEND:' . Yii::$app->formatter->asDate($end, 'yyyyMMdd') . "\r\n";
            $str .= "SUMMARY:{$task->subject}\r\n";
            $str .= "DESCRIPTION:$description\r\n";
            $str .= "X-ALT-DESC;FMTTYPE=text/html:{$task->description}\r\n";
            $str .= "END:VEVENT\r\n";
        }

        $str .= "END:VCALENDAR\r\n";

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/calendar');

        return $str;
    }


    /**
     * Tries to convert text in p-tags and br to new lines
     * @param string $text
     * @return string
     */
    private function br2nl($text = '')
    {
        if (empty($text)) {
            return '';
        }

        // replace all empty lines
        $text = str_replace('<p><br></p>', "\\n", $text);

        // replace all paragraphs with new lines
        $count = substr_count($text, '<p>');
        $text = preg_replace('~<p>(.*?)</p>~', "$1\\n\\n", $text, $count - 1);
        $text = preg_replace('~<p>(.*?)</p>~', '$1', $text);

        // replace remaining <br>
        $text = str_replace(['<br>', '</br>', '<br />'], "\\n", $text);

        // strip all remainig tags
        return strip_tags($text);
    }
}
