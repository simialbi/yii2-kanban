<?php

namespace simialbi\yii2\kanban\enums;

use simialbi\yii2\kanban\models\Connection;

/**
 * Class ConnectionTypeEnum
 */
enum ConnectionTypeEnum: int
{
    case Outlook = 1;

    /**
     * Get select list
     * @return string[]
     */
    public static function getSelectList(): array
    {
        $arr = [];
        foreach (ConnectionTypeEnum::cases() as $type) {
            $arr[$type->value] = $type->getLabel();
        }
        asort($arr);

        return $arr;
    }

    /**
     * Get label for a type
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            ConnectionTypeEnum::Outlook => 'Outlook'
        };
    }

    /**
     * Return the view for a connection type
     *
     * @param string $action
     *
     * @return string
     */
    public function getView(string $action): string
    {
        return match ($this) {
            ConnectionTypeEnum::Outlook => "@simialbi/yii2/kanban/views/connection/$action",
        };
    }

    /**
     * Return the model for a connection type
     * @return string
     */
    public function getModel(): string
    {
        return match ($this) {
            ConnectionTypeEnum::Outlook => Connection::class,
        };
    }
}
