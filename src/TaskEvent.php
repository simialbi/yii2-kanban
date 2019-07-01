<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\models\Task;
use yii\base\Event;

/**
 * TaskEvent represents the event parameter used for an task event.
 */
class TaskEvent extends Event
{
    /**
     * @var Task The board which triggered the event
     */
    public $task;
}
