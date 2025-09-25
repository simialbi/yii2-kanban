<?php

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ChecklistTemplate
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $board_id
 * @property string $name
 * @property int|string $created_by
 * @property int|string $updated_by
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Board $board
 * @property-read ChecklistTemplateElement[] $elements
 * @property-read UserInterface $creator
 * @property-read UserInterface $updater
 */
class ChecklistTemplate extends ActiveRecord
{
    /**
     * @var int|string $elementCount
     */
    protected int|string $elementCount = '';

    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__checklist_template}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['board_id', 'name'], 'required'],

            [['id', 'board_id', 'created_at', 'updated_at'], 'integer'],
            ['name', 'string', 'max' => 255],
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
                    self::EVENT_BEFORE_UPDATE => 'updated_by',
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                ]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'board_id' => Yii::t('simialbi/kanban/model/checklist-template', 'Board'),
            'name' => Yii::t('simialbi/kanban/model/checklist-template', 'Name'),
            'created_by' => Yii::t('simialbi/kanban/model/checklist-template', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/checklist-template', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/checklist-template', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/checklist-template', 'Updated at'),
            'elementCount' => Yii::t('simialbi/kanban/model/checklist-template', 'Elements'),
        ];
    }

    /**
     * Get board relation
     * @return ActiveQuery
     */
    public function getBoard(): ActiveQuery
    {
        return $this->hasOne(Board::class, ['id' => 'board_id'])
            ->inverseOf('checklistTemplates');
    }

    /**
     * Get related elements
     * @return ActiveQuery
     */
    public function getElements(): ActiveQuery
    {
        return $this->hasMany(ChecklistTemplateElement::class, ['template_id' => 'id'])
            ->orderBy(['sort' => SORT_ASC])
            ->inverseOf('template');
    }

    /**
     * Get creator relation
     * @return ActiveQuery
     * @throws \Exception
     */
    public function getCreator(): ActiveQuery
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->created_by);
    }

    /**
     * Get updater relation
     * @return ActiveQuery
     * @throws \Exception
     */
    public function getUpdater(): ActiveQuery
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->updated_by);
    }

    /**
     * Get element count
     * @return int|string
     */
    public function getElementCount(): int|string
    {
        if ($this->elementCount === '') {
            $this->elementCount = count($this->elements);
        }
        return $this->elementCount;
    }

    /**
     * Set element count
     * @param int|null $elementCount
     */
    public function setElementCount(?int $elementCount): void
    {
        $this->elementCount = $elementCount;
    }
}
