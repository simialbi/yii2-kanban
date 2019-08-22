# Kanban board implementation for yii2

[![Latest Stable Version](https://poser.pugx.org/simialbi/yii2-kanban/v/stable?format=flat-square)](https://packagist.org/packages/simialbi/yii2-kanban)
[![Total Downloads](https://poser.pugx.org/simialbi/yii2-kanban/downloads?format=flat-square)](https://packagist.org/packages/simialbi/yii2-kanban)

[![License](https://poser.pugx.org/simialbi/yii2-kanban/license?format=flat-square)](https://packagist.org/packages/simialbi/yii2-kanban)

## Resources

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require --prefer-dist simialbi/yii2-kanban
```

or add

```
"simialbi/yii2-kanban": "~1.0@dev"
```

to the `require` section of your `composer.json`.

## Usage

In order to use this module, you will need to:

1. [Configure](#setup-module) your application so that the module is available.

### Setup Module

Configure the module in the modules section of your Yii configuration file.

```php
'modules' => [
    'kanban' => [
        'class' => 'simialbi\yii2\kanban\Module',
        //'statuses' => [],
        //'statusColors' => [],
        //'on boardCreated' => function ($event) {},
        //[...]
    ]
]
```

#### Parameters

| Parameter      | Description                                                                                                                         |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `statuses`     | Define your own task statuses.                                                                                                      |
| `statusColors` | Add your own color for each status. *Be sure to define a color for each status if you override colors or define your own statuses.* |

> Notice: The Statuses `Task::STATUS_NOT_BEGUN`, `Task::STATUS_DONE` and `TASK::STATUS_LATE` will automatically be defined
  if you do not define them.

#### Events
| Event                       | Description                                                              |
| --------------------------- | ------------------------------------------------------------------------ |
| `EVENT_BOARD_CREATED`       | Will be triggered after a new board was created.                         |
| `EVENT_BUCKET_CREATED`      | Will be triggered after a new bucket was created in any board.           |
| `EVENT_TASK_CREATED`        | Will be triggered after a task was created in any bucket.                |
| `EVENT_TASK_ASSIGNED`       | Will be triggered after a task got assigned to a user.                   |
| `EVENT_TASK_UNASSIGNED`     | Will be triggered after an assignment from a task to a user got revoked. |
| `EVENT_TASK_STATUS_CHANGED` | Will be triggered after a tasks status changed.                          |
| `EVENT_TASK_COMPLETED`      | Will be triggered after a tasks status changed to `Task::STATUS_DONE`.   |
| `EVENT_CHECKLIST_CREATED`   | Will be triggered after a task got one or more new checklist elements.   |
| `EVENT_COMMENT_CREATED`     | Will be triggered after a task got a new comment.                        |
| `EVENT_ATTACHMENT_ADDED`    | Will be triggered after a task got one or more new attachments.          |

## Example Usage


## License

**yii2-kanban** is released under MIT license. See bundled [LICENSE](LICENSE) for details.
