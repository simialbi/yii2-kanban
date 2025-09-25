<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Attachment
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $task_id
 * @property string $name
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property boolean $card_show
 * @property int|string $created_by
 * @property int|string $updated_by
 * @property int|string $created_at
 * @property int|string $updated_at
 * @property int $sync_id
 *
 * @property-read string $icon
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read Task $task
 */
class Attachment extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__attachment}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'task_id', 'size'], 'integer'],
            [['name', 'mime_type', 'sync_id'], 'string', 'max' => 255],
            ['path', 'file'],
            ['card_show', 'boolean'],

            ['card_show', 'default', 'value' => false],

            [['task_id', 'size', 'name', 'mime_type', 'path', 'card_show'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_by', 'updated_by'],
                    self::EVENT_BEFORE_UPDATE => 'updated_by'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at'
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/attachment', 'Id'),
            'task_id' => Yii::t('simialbi/kanban/model/attachment', 'Task'),
            'name' => Yii::t('simialbi/kanban/model/attachment', 'Name'),
            'path' => Yii::t('simialbi/kanban/model/attachment', 'Path'),
            'mime_type' => Yii::t('simialbi/kanban/model/attachment', 'Mime type'),
            'size' => Yii::t('simialbi/kanban/model/attachment', 'Size'),
            'card_show' => Yii::t('simialbi/kanban/model/attachment', 'Show on card'),
            'created_by' => Yii::t('simialbi/kanban/model/attachment', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/attachment', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/attachment', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/attachment', 'Updated at'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        /** @var Module $module */
        $module = Yii::$app->controller->module;
        FileHelper::unlink(FileHelper::normalizePath(Yii::getAlias(
            $module->uploadWebRoot . '/task/' . $this->task_id . '/' . $this->name
        )));

        return true;
    }

    /**
     * Get the icon type based on the MIME type of the file.
     *
     * @return string The icon type corresponding to the file MIME type.
     */
    public function getIcon(): string
    {
        switch ($this->mime_type) {
            case 'image/png':
            case 'image/jpeg':
            case 'image/gif':
            case 'image/wbmp':
            case 'image/bmp':
                return 'image';

            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.template':
            case 'application/vnd.ms-word.document.macroEnabled.12':
            case 'application/vnd.ms-word.template.macroEnabled.12':
                return 'file-word';

            case 'application/msexcel':
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
            case 'application/vnd.ms-excel.sheet.macroEnabled.12';
            case 'application/vnd.ms-excel.template.macroEnabled.12';
            case 'application/vnd.ms-excel.addin.macroEnabled.12';
            case 'application/vnd.ms-excel.sheet.binary.macroEnabled.12';
                return 'file-excel';

            case 'application/mspowerpoint':
            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
            case 'application/vnd.openxmlformats-officedocument.presentationml.template':
            case 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
            case 'application/vnd.ms-powerpoint.addin.macroEnabled.12':
            case 'application/vnd.ms-powerpoint.presentation.macroEnabled.12':
            case 'application/vnd.ms-powerpoint.template.macroEnabled.12':
            case 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12':
                return 'file-powerpoint';

            case 'application/pdf':
                return 'file-pdf';

            case 'application/json':
            case 'application/javascript':
            case 'application/xhtml+xml':
            case 'application/xml':
            case 'application/x-httpd-php':
            case 'text/css':
            case 'text/html':
            case 'text/javascript':
            case 'text/xml':
                return 'file-code';

            case 'video/mpeg':
            case 'video/mp4':
            case 'video/ogg':
            case 'video/quicktime':
            case 'video/vnd.vivo':
            case 'video/webm':
            case 'video/x-msvideo':
            case 'video/x-sgi-movie':
                return 'video';

            default:
                return 'file';
        }
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     */
    public function getAuthor(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->created_by);
    }

    /**
     * Get user last updated
     * @return UserInterface
     * @throws \Exception
     */
    public function getUpdater(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->updated_by);
    }

    /**
     * Get associated task
     * @return ActiveQuery
     */
    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
}
