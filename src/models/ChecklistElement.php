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
use yii\helpers\Html;

/**
 * Class ChecklistElement
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property integer $task_id
 * @property string $name
 * @property integer|string|\DateTime $end_date
 * @property boolean $is_done
 * @property integer $sort
 *
 * @property-read string $label
 * @property-read Task $task
 */
class ChecklistElement extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban__checklist_element}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'task_id'], 'integer'],
            ['name', 'string'],
            ['end_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'end_date'],
            ['is_done', 'boolean'],

            ['is_done', 'default', 'value' => false],
            ['end_date', 'default'],

            ['task_id', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

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
            'end_date' => Yii::t('simialbi/kanban/model/checklist-element', 'End date'),
            'is_done' => Yii::t('simialbi/kanban/model/checklist-element', 'Is done'),
            'sort' => Yii::t('simialbi/kanban/model/checklist-element', 'Sort')
        ];
    }

    /**
     * Getter function for checklist element label
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getLabel()
    {
        $label = Html::encode($this->name);
        if ($this->end_date) {
            $label .= ' ';
            $label .= Html::tag('small', '(' . Yii::$app->formatter->asDate($this->end_date) . ')', [
                'class' => ['text-muted']
            ]);
        }

        return $label;
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
