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
    public $subject;
    /**
     * @var integer Id of bucket to copy task in
     */
    public $bucketId;
    /**
     * @var boolean Whether or not to copy task assignments
     */
    public $copyAssignment;
    /**
     * @var boolean Whether or not to copy task status
     */
    public $copyStatus;
    /**
     * @var boolean Whether or not to copy task start and end date
     */
    public $copyDates;
    /**
     * @var boolean Whether or not to copy task description
     */
    public $copyDescription = true;
    /**
     * @var boolean Whether or not to copy task checklist
     */
    public $copyChecklist = true;
    /**
     * @var boolean Whether or not to copy task attachments
     */
    public $copyAttachments = true;
    /**
     * @var boolean Whether or not to copy task attachments
     */
    public $copyLinks = true;

    /**
     * {@inheritDoc}
     */
    public function rules()
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
    public function attributeLabels()
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
