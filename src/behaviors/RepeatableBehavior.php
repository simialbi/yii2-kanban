<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\behaviors;

use Recurr\Exception\InvalidWeekday;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use simialbi\yii2\kanban\models\ChecklistElement;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\Query;
use yii\di\Instance;

/**
 * @property-read ActiveRecord $owner
 */
class RepeatableBehavior extends Behavior
{
    /**
     * @var string Property
     */
    public string $recurrenceProperty = 'is_recurring';

    /**
     * @var string Property
     */
    public string $recurrencePatternProperty = 'recurrence_pattern';

    /**
     * @var string Property
     */
    public string $recurrenceParentIdProperty = 'recurrence_parent_id';

    /**
     * @var string Property
     */
    public string $idProperty = 'id';

    /**
     * @var string Property
     */
    public string $startDateProperty = 'start_date';

    /**
     * @var string Property
     */
    public string $endDateProperty = 'end_date';

    /**
     * @var string Property
     */
    public string $statusProperty = 'status';

    /**
     * @var string
     */
    public string $recurrenceDoneTableName = '{{%kanban__task_recurrent_task}}';

    /**
     * @var string
     */
    public string $recurrenceDoneDateProperty = 'execution_date';
    /**
     * @var string
     */
    public string $recurrenceDoneRelationProperty = 'task_id';

    /**
     * @var array An array of relation names to keep in recurrent instance
     */
    public array $keepRelations = [];

    /**
     * @var bool If instance of this task is recurrent
     */
    private bool $_isRecurrentInstance = false;
    /**
     * @var null|ActiveRecord The original series record
     */
    private ?ActiveRecord $_originalRecord;

    /**
     * {@inheritDoc}
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'createRecurrentInstance',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'updateRecurrentInstance',
            ActiveRecord::EVENT_BEFORE_DELETE => 'deleteRecurrentInstance'
        ];
    }

    /**
     * @param ModelEvent $event
     *
     * @throws InvalidConfigException|Exception
     */
    public function deleteRecurrentInstance(ModelEvent $event): void
    {
        if (!$this->isRecurrentInstance()) {
            if ($this->owner->{$this->recurrenceParentIdProperty}) {
                /** @var ActiveRecord $record */
                $record = call_user_func(
                    [$this->owner, 'findOne'],
                    $this->owner->getAttribute($this->recurrenceParentIdProperty)
                )->getOriginalRecord();
                /** @var Rule $rule */
                $rule = $record->{$this->recurrencePatternProperty};

                $exDates = [];
                foreach ($rule->getExDates() as $exDate) {
                    if (Yii::$app->formatter->asDate($exDate->date, 'yyyy-MM-dd') !== Yii::$app->formatter->asDate($this->owner->{$this->endDateProperty}, 'yyyy-MM-dd')) {
                        $exDates[] = Yii::$app->formatter->asDate($exDate->date, 'yyyy-MM-dd');
                    }
                }
                $rule->setExDates($exDates);
                $record->save();
            }
            return;
        }

        $record = $this->getOriginalRecord();
        /** @var Rule $rule */
        $rule = $record->{$this->recurrencePatternProperty};
        $rule->setExDates(array_merge($rule->getExDates(), [
            Yii::$app->formatter->asDatetime($this->owner->{$this->endDateProperty}, 'yyyy-MM-dd HH:mm:ss')
        ]));
        $record->save();

        $event->isValid = false;
    }

    /**
     * @throws Exception|InvalidConfigException|InvalidWeekday
     */
    public function updateRecurrentInstance(ModelEvent $event): void
    {
        if (!$this->isRecurrentInstance()) {
            return;
        }

        $this->owner->setAttribute($this->idProperty, $this->owner->{$this->recurrenceParentIdProperty});
        $attributes = $this->cleanAttributes($this->owner->getDirtyAttributes());

        // If no attributes are changed, we can just return
        if (empty($attributes)) {
            $event->isValid = false;
            return;
        }

        // If only status is changed, we can just update the recurrence done table
        if (isset($attributes[$this->statusProperty]) && count($attributes) === 1) {
            /** @var Connection $connection */
            $connection = call_user_func([$this->owner, 'getDb']);
            $connection->createCommand()->upsert($this->recurrenceDoneTableName, [
                $this->recurrenceDoneRelationProperty => $this->owner->{$this->recurrenceParentIdProperty},
                $this->recurrenceDoneDateProperty => $this->owner->{$this->startDateProperty},
                $this->statusProperty => $attributes[$this->statusProperty]
            ])->execute();

            [$next,] = $this->findNextExecution();

            if ($next === null) {
                $record = $this->getOriginalRecord();
                $record->{$this->statusProperty} = Task::STATUS_DONE;
                $record->save();
            } else {
                if (in_array('checklistElements', $this->keepRelations)) {
                    foreach ($this->owner->checklistElements as $element) {
                        /** @var ChecklistElement $element */
                        $element->setAttribute('is_done', false);
                        $element->save();
                    }
                }
            }

            $event->isValid = false;
            return;
        }

        /** @var ActiveRecord $instance */
        $instance = Yii::createObject([
            'class' => get_class($this->owner)
        ]);
        $instance->setAttributes($this->owner->getAttributes(null, array_keys($this->owner->getPrimaryKey(true))));
        [$next,] = $this->findNextExecution();
        if ($instance->save()) {
            $record = $this->getOriginalRecord();

            /** @var Rule $rule */
            $rule = $record->{$this->recurrencePatternProperty};
            if ($next->getStart()->getTimestamp() < $instance->getAttribute($this->endDateProperty)) {
                $rule->setExDates(array_unique(array_merge($rule->getExDates(), [
                    Yii::$app->formatter->asDatetime(
                        $next->getStart(),
                        'yyyy-MM-dd HH:mm:ss'
                    ),
                    Yii::$app->formatter->asDatetime(
                        $instance->getAttribute($this->endDateProperty),
                        'yyyy-MM-dd HH:mm:ss'
                    )
                ])));
            } else {
                $rule->setExDates(array_unique(array_merge($rule->getExDates(), [
                    Yii::$app->formatter->asDatetime(
                        $instance->getAttribute($this->endDateProperty),
                        'yyyy-MM-dd HH:mm:ss'
                    )
                ])));
            }
            $record->save();
        }
        $this->owner->setAttribute($this->idProperty, $instance->{$this->idProperty});

        $event->isValid = false;
    }

    /**
     * @return boolean
     */
    public function isRecurrentInstance(): bool
    {
        return $this->_isRecurrentInstance;
    }

    /**
     * @return ActiveRecord|null
     */
    public function getOriginalRecord(): ?ActiveRecord
    {
        return $this->_originalRecord;
    }

    /**
     * @throws InvalidWeekday|InvalidConfigException
     */
    public function createRecurrentInstance(): void
    {
        if ($this->owner === null) {
            return;
        }
        if (!$this->owner->hasProperty($this->recurrenceProperty) || !$this->owner->{$this->recurrenceProperty}) {
            return;
        }
        if (!$this->owner->hasProperty($this->recurrencePatternProperty) || empty($this->owner->{$this->recurrencePatternProperty})) {
            return;
        }
        if (!$this->owner->hasProperty($this->statusProperty) || $this->owner->{$this->statusProperty} === Task::STATUS_DONE) {
            return;
        }

        [$next, $status] = $this->findNextExecution();

        if (!$next) {
            return;
        }

        $this->_isRecurrentInstance = true;
        $this->_originalRecord = clone $this->owner;

        // Populate relations that should be kept in the recurrent instance
        foreach ($this->keepRelations as $relationName) {
            $relation = $this->owner->{$relationName};
            $this->owner->populateRelation($relationName, $relation);
        }

        $instance = clone $this->owner;
        $this->owner->setAttribute($this->recurrenceProperty, false);
        $this->owner->setAttribute($this->recurrencePatternProperty, null);
        $this->owner->setAttribute($this->recurrenceParentIdProperty, $instance->{$this->idProperty});
        $this->owner->setAttribute($this->idProperty, null);
        $this->owner->setAttribute($this->startDateProperty, $next->getStart()->getTimestamp());
        $this->owner->setAttribute($this->endDateProperty, $next->getStart()->getTimestamp());
        $this->owner->setAttribute($this->statusProperty, $status);

        foreach ($this->owner->getRelatedRecords() as $relatedRecordName => $relatedRecord) {
            if (!in_array($relatedRecordName, $this->keepRelations)) {
                $this->owner->populateRelation($relatedRecordName, is_array($relatedRecord) ? [] : null);
            }
        }
    }

    /**
     * Clean attributes from changed active record
     *
     * @param array $attributes
     *
     * @return array
     * @throws InvalidConfigException
     */
    protected function cleanAttributes(array $attributes): array
    {
        unset(
            $attributes[$this->recurrenceProperty],
            $attributes[$this->recurrencePatternProperty],
            $attributes[$this->recurrenceParentIdProperty],
            $attributes[$this->idProperty],
            $attributes[$this->startDateProperty],
            $attributes[$this->endDateProperty]
        );

        foreach ($this->owner->behaviors() as $behavior) {
            if (is_array($behavior)) {
                $behavior = Instance::ensure($behavior);
            }
            if ($behavior instanceof BlameableBehavior or $behavior instanceof TimestampBehavior) {
                foreach ($behavior->attributes as $attribute) {
                    if (is_array($attribute)) {
                        foreach ($attribute as $item) {
                            unset($attributes[$item]);
                        }
                    } else {
                        unset($attributes[$attribute]);
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Find next date in recurrence
     * @return array
     * @throws InvalidWeekday|\Exception
     */
    protected function findNextExecution(): array
    {
        $model = ($this->owner->{$this->recurrenceProperty})
            ? $this->owner
            : $this->getOriginalRecord();
        $status = $this->owner->{$this->statusProperty};
        /** @var Rule $rule */
        $rule = $model->{$this->recurrencePatternProperty};

        $lastDoneQuery = new Query();
        $last = $lastDoneQuery->select([
            'last' => $this->recurrenceDoneDateProperty,
            'status' => $this->statusProperty
        ])
            ->from($this->recurrenceDoneTableName)
            ->where([
                $this->recurrenceDoneRelationProperty => $model->{$this->idProperty},
            ])
            ->orderBy([$this->recurrenceDoneDateProperty => SORT_DESC])
            ->one();


        // Start date from not done tasks
        $virtualLimit = 1;
        if ($last) {
            $startDate = $last['last'];
            if ((int)$last['status'] === Task::STATUS_DONE) {
                $virtualLimit = 2;
                $status = Task::STATUS_NOT_BEGUN;
            } else {
                $status = (int)$last['status'];
            }
        } else {
            if (isset($this->owner->{$this->startDateProperty})) {
                $startDate = $model->{$this->startDateProperty};
            } else {
                $startDate = null;
            }
        }
        if ($startDate) {
            $rule->setStartDate(
                (new \DateTime("@$startDate"))->setTimezone(new \DateTimeZone(Yii::$app->timeZone))
            );
        }

        $arrTransConfig = new ArrayTransformerConfig();
        $arrTransConfig->setVirtualLimit($virtualLimit + count($rule->getExDates()));
        $arrTransConfig->enableLastDayOfMonthFix();

        $transformer = new ArrayTransformer($arrTransConfig);

        if (($next = $transformer->transform($rule)->next()) === false) {
            $next = $transformer->transform($rule)->last();
        }

        return [$next, $status];
    }
}
