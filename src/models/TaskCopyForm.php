<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2020 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use Yii;
use yii\base\Model;

class TaskCopyForm extends Model
{
    /**
     * @var string Tasks new subject
     */
    public string $subject = '';
    /**
     * @var integer Id of bucket to copy task in
     */
    public int $bucketId = 0;
    /**
     * @var boolean Whether or not to copy task assignments
     */
    public bool $copyAssignment = false;
    /**
     * @var boolean Whether or not to copy task status
     */
    public bool $copyStatus = false;
    /**
     * @var boolean Whether or not to copy task start and end date
     */
    public bool $copyDates = false;
    /**
     * @var boolean Whether or not to copy task description
     */
    public bool $copyDescription = true;
    /**
     * @var boolean Whether or not to copy task checklist
     */
    public bool $copyChecklist = true;
    /**
     * @var boolean Whether or not to copy task attachments
     */
    public bool $copyAttachments = true;
    /**
     * @var boolean Whether or not to copy task attachments
     */
    public bool $copyLinks = true;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['subject', 'string'],
            ['bucketId', 'integer'],
            [
                [
                    'copyAssignment',
                    'copyStatus',
                    'copyDates',
                    'copyDescription',
                    'copyChecklist',
                    'copyAttachments',
                    'copyLinks'
                ],
                'boolean'
            ],
            [['copyAssignment', 'copyStatus', 'copyDates'], 'default', 'value' => false],
            [['copyDescription', 'copyChecklist', 'copyAttachments', 'copyLinks'], 'default', 'value' => true],

            [['subject', 'bucketId'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'subject' => Yii::t('simialbi/kanban/model/task-copy-form', 'Subject'),
            'bucketId' => Yii::t('simialbi/kanban/model/task-copy-form', 'Bucket Id'),
            'copyAssignment' => Yii::t('simialbi/kanban/model/task-copy-form', 'Assignment'),
            'copyStatus' => Yii::t('simialbi/kanban/model/task-copy-form', 'Status'),
            'copyDates' => Yii::t('simialbi/kanban/model/task-copy-form', 'Dates'),
            'copyDescription' => Yii::t('simialbi/kanban/model/task-copy-form', 'Description'),
            'copyChecklist' => Yii::t('simialbi/kanban/model/task-copy-form', 'Checklist'),
            'copyAttachments' => Yii::t('simialbi/kanban/model/task-copy-form', 'Attachments'),
            'copyLinks' => Yii::t('simialbi/kanban/model/task-copy-form', 'Links')
        ];
    }
}
