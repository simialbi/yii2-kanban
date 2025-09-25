<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use DateTime;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * Class ChecklistElement
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $task_id
 * @property string $name
 * @property int|string|DateTime $end_date
 * @property boolean $is_done
 * @property int $sort
 * @property int $sync_id
 *
 * @property-read string $label
 * @property-read Task $task
 */
class ChecklistElement extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__checklist_element}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'task_id'], 'integer'],
            ['name', 'string'],
            ['sync_id', 'string', 'max' => 255],
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
    public function behaviors(): array
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
    public function attributeLabels(): array
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
     * @throws InvalidConfigException
     */
    public function getLabel(): string
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
     * @return ActiveQuery
     */
    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
}
