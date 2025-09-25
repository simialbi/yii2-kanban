<?php

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class ChecklistTemplateElement
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $template_id
 * @property string $name
 * @property string $dateOffset
 * @property int $sort
 *
 * @property-read ChecklistTemplate $template
 */
class ChecklistTemplateElement extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__checklist_template_element}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['template_id', 'name'], 'required'],

            [['id', 'template_id', 'dateOffset', 'sort'], 'integer'],
            ['name', 'string', 'max' => 255],

            ['dateOffset', 'default', 'value' => null]
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
                'scope' => function () {
                    return static::find()->where(['template_id' => $this->template_id]);
                }
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'template_id' => Yii::t('simialbi/kanban/model/checklist-template-element', 'Template'),
            'name' => Yii::t('simialbi/kanban/model/checklist-template-element', 'Name'),
            'dateOffset' => Yii::t('simialbi/kanban/model/checklist-template-element', 'End date'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeHints(): array
    {
        return [
            'dateOffset' => Yii::t('simialbi/kanban/model/checklist-template-element', 'Days after reference date'),
        ];
    }

    /**
     * Get template relation
     * @return ActiveQuery
     */
    public function getTemplate(): ActiveQuery
    {
        return $this->hasOne(ChecklistTemplate::class, ['id' => 'template_id'])
            ->inverseOf('elements');
    }
}
