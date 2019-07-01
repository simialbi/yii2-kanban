<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\models\Board;
use yii\base\Event;

/**
 * BoardEvent represents the event parameter used for an board event.
 */
class BoardEvent extends Event
{
    /**
     * @var Board The board which triggered the event
     */
    public $board;
}
