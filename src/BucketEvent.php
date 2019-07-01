<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban;

use simialbi\yii2\kanban\models\Bucket;
use yii\base\Event;

/**
 * BucketEvent represents the event parameter used for an bucket event.
 */
class BucketEvent extends Event
{
    /**
     * @var Bucket The board which triggered the event
     */
    public $bucket;
}
