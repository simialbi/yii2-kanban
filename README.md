# Kanban board implementation for yii2

[![Latest Stable Version](https://poser.pugx.org/simialbi/yii2-kanban/v/stable?format=flat-square)](https://packagist.org/packages/simialbi/yii2-kanban)
[![Total Downloads](https://poser.pugx.org/simialbi/yii2-kanban/downloads?format=flat-square)](https://packagist.org/packages/simialbi/yii2-kanban)
[![License](https://poser.pugx.org/simialbi/yii2-kanban/license?format=flat-square)](https://packagist.org/packages/simialbi/yii2-kanban)
[![build](https://github.com/simialbi/yii2-kanban/actions/workflows/build.yml/badge.svg)](https://github.com/simialbi/yii2-kanban/actions/workflows/build.yml)

## Resources

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require --prefer-dist simialbi/yii2-kanban
```

or add

```
"simialbi/yii2-kanban": "^2.0.0"
```

to the `require` section of your `composer.json`.

## Usage

In order to use this module, you will need to:

1. [Setup Module](#setup-module) your application so that the module is available.
2. [Create a user identity](#create-identity) class which extends UserInterface

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

### Setup console config and apply migrations

Apply the migrations either with the following command: `yii migrate --migration-namespaces='simialbi\yii2\kanban\migrations'`
or configure your console like this:

```php
[
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationNamespaces' => [
                'simialbi\yii2\kanban\migrations'
            ]
        ]
    ]
]
```

and apply the `yii migrate` command.

### Create identity

Create an identity class which implements `simialbi\yii2\models\UserInterface` e.g.:
```php
<?php
use yii\db\ActiveRecord;
use simialbi\yii2\models\UserInterface;

class User extends ActiveRecord implements UserInterface
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritDoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function getMobile() {
        return $this->mobile;
    }

    /**
     * {@inheritDoc}
     */
    public static function findIdentities() {
        return static::find()->all();
    }
}
```

After creating this class define it as identity class in your application configuration:
```php
'components' => [
    'user' => [
        'identityClass' => 'app\models\User'
    ]
]
``` 

### Configure jQuery UI (optional)

If you don't use jQuery UI somewhere else in your application, you can minify the load by just load the needed scripts:
```php
'components' => [
    'assetManager' => [
        'bundles' => [
            'yii\jui\JuiAsset' => [
                'css' => [],
                'js' => [
                    'ui/data.js',
                    'ui/scroll-parent.js',
                    'ui/widget.js',
                    'ui/widgets/mouse.js',
                    'ui/widgets/sortable.js',
                ],
                'publishOptions' => [
                    'only' => [
                        'ui/*',
                        'ui/widgets/*'
                    ]
                ]
            ]
        ]
    ]
]
```

> Notice: If you use the full jquery ui package, the bootstrap tooltip used by this module gets overridden by 
  jui tooltip

## Example Usage

Now you can access the kanban module by navigating to `/kanban`.

> Notice: Some of the actions can only be done as authenticated (logged in) user like creating boards, buckets etc.

## License

**yii2-kanban** is released under MIT license. See bundled [LICENSE](LICENSE) for details.
