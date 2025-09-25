<?php

namespace simialbi\yii2\kanban\models;

/**
 * Interface ConnectionInterface
 */
interface ConnectionInterface
{
    /**
     * Returns an array of attributes which should be saved in options json
     * @return string[]
     */
    public function optionAttributes(): array;
}
