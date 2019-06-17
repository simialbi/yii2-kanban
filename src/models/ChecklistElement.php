<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;


use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use Yii;
use yii\db\ActiveRecord;

/**
 * Class ChecklistElement
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property integer $task_id
 * @property string $name
 * @property boolean $is_done
 * @property integer $sort
 *
 * @property-read Task $task
 */
class ChecklistElement extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban_checklist_element}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'task_id'], 'integer'],
            ['name', 'string'],
            ['is_done', 'boolean'],

            ['is_done', 'default', 'value' => false],

            [['task_id', 'name', 'is_done'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors()
    {
        return [
            'sortable' => [
                'class' => ContinuousNumericalSortableBehavior::class,
                'sortAttribute' => 'sort',
                'scope' => function () {
                    return ChecklistElement::find()->where(['task_id' => $this->task_id]);
                }
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/checklist-element', 'Id'),
            'task_id' => Yii::t('simialbi/kanban/model/checklist-element', 'Task'),
            'name' => Yii::t('simialbi/kanban/model/checklist-element', 'Name'),
            'is_done' => Yii::t('simialbi/kanban/model/checklist-element', 'Is done'),
            'sort' => Yii::t('simialbi/kanban/model/checklist-element', 'Sort')
        ];
    }

    /**
     * Get associated task
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
}
