<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\behaviors;

use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\base\Behavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

/**
 * @property-read ActiveRecord $owner
 */
class RepeatableBehavior extends Behavior
{
    /**
     * @var string Property
     */
    public $recurrenceProperty = 'is_recurring';

    /**
     * @var string Property
     */
    public $recurrencePatternProperty = 'recurrence_pattern';

    /**
     * @var string Property
     */
    public $recurrenceParentIdProperty = 'recurrence_parent_id';

    /**
     * @var string Property
     */
    public $idProperty = 'id';

    /**
     * @var string Property
     */
    public $startDateProperty = 'start_date';

    /**
     * @var string Property
     */
    public $endDateProperty = 'end_date';

    /**
     * @var string Property
     */
    public $statusProperty = 'status';

    /**
     * @var string
     */
    public $recurrenceDoneTableName = '{{%kanban__task_recurrent_task}}';

    /**
     * @var string
     */
    public $recurrenceDoneDateProperty = 'execution_date';
    /**
     * @var string
     */
    public $recurrenceDoneRelationProperty = 'task_id';

    /**
     * @var array An array of relation names to keep in recurrent instance
     */
    public $keepRelations = [
        'assignments'
    ];

    /**
     * @var bool If instance of this task is recurrent
     */
    private $_isRecurrentInstance = false;
    /**
     * @var null|ActiveRecord The original series record
     */
    private $_originalRecord;

    /**
     * {@inheritDoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'createRecurrentInstance',
            ActiveRecord::EVENT_BEFORE_INSERT => 'updateRecurrentInstance',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'updateRecurrentInstance',
            ActiveRecord::EVENT_BEFORE_DELETE => 'deleteRecurrentInstance'
        ];
    }

    /**
     * @param \yii\base\ModelEvent $event
     * @throws \yii\base\InvalidConfigException
     */
    public function deleteRecurrentInstance(\yii\base\ModelEvent $event)
    {
        if (!$this->isRecurrentInstance()) {
            if ($this->owner->{$this->recurrenceParentIdProperty}) {
                /** @var \yii\db\ActiveRecord $record */
                $record = call_user_func([$this->owner, 'findOne'],
                    $this->owner->getAttribute($this->recurrenceParentIdProperty))->getOriginalRecord();
                /** @var \Recurr\Rule $rule */
                $rule = $record->{$this->recurrencePatternProperty};

                $exDates = [];
                foreach ($rule->getExDates() as $exDate) {
                    if (Yii::$app->formatter->asDate($exDate->date,
                            'yyyy-MM-dd') !== Yii::$app->formatter->asDate($this->owner->{$this->endDateProperty},
                            'yyyy-MM-dd')) {
                        $exDates[] = Yii::$app->formatter->asDate($exDate->date, 'yyyy-MM-dd');
                    }
                }
                $rule->setExDates($exDates);
                $record->save();
            }
            return;
        }

        $record = $this->getOriginalRecord();
        /** @var \Recurr\Rule $rule */
        $rule = $record->{$this->recurrencePatternProperty};
        $rule->setExDates(array_merge($rule->getExDates(), [
            Yii::$app->formatter->asDatetime($this->owner->{$this->endDateProperty}, 'yyyy-MM-dd HH:mm:ss')
        ]));
        $record->save();

        $event->isValid = false;
    }

    /**
     * @throws \yii\db\Exception|\yii\base\InvalidConfigException|\Recurr\Exception\InvalidWeekday
     */
    public function updateRecurrentInstance(\yii\base\ModelEvent $event)
    {
        if (!$this->isRecurrentInstance()) {
            return;
        }

        $this->owner->setAttribute($this->idProperty, $this->owner->{$this->recurrenceParentIdProperty});
        $attributes = $this->cleanAttributes($this->owner->getDirtyAttributes());

        if (isset($attributes[$this->statusProperty]) && count($attributes) === 1) {
            /** @var \yii\db\Connection $connection */
            $connection = call_user_func([$this->owner, 'getDb']);
            $connection->createCommand()->upsert($this->recurrenceDoneTableName, [
                $this->recurrenceDoneRelationProperty => $this->owner->{$this->recurrenceParentIdProperty},
                $this->recurrenceDoneDateProperty => $this->owner->{$this->startDateProperty},
                $this->statusProperty => $attributes[$this->statusProperty]
            ])->execute();

            list($next,) = $this->findNextExecution();

            if ($next === null) {
                $record = $this->getOriginalRecord();
                $record->{$this->statusProperty} = Task::STATUS_DONE;
                $record->save();
            }

            $event->isValid = false;

            return;
        }

        /** @var \yii\db\ActiveRecord $instance */
        $instance = Yii::createObject([
            'class' => get_class($this->owner)
        ]);
        $instance->setAttributes($this->owner->getAttributes(null, array_keys($this->owner->getPrimaryKey(true))));
        list($next,) = $this->findNextExecution();
        if ($instance->save()) {
            $record = $this->getOriginalRecord();

            /** @var \Recurr\Rule $rule */
            $rule = $record->{$this->recurrencePatternProperty};
            if ($next->getStart()->getTimestamp() < $instance->getAttribute($this->endDateProperty)) {
                $rule->setExDates(array_merge($rule->getExDates(), [
                    Yii::$app->formatter->asDatetime(
                        $next->getStart(),
                        'yyyy-MM-dd HH:mm:ss'
                    ),
                    Yii::$app->formatter->asDatetime(
                        $instance->getAttribute($this->endDateProperty),
                        'yyyy-MM-dd HH:mm:ss'
                    )
                ]));
            } else {
                $rule->setExDates(array_merge($rule->getExDates(), [
                    Yii::$app->formatter->asDatetime(
                        $instance->getAttribute($this->endDateProperty),
                        'yyyy-MM-dd HH:mm:ss'
                    )
                ]));
            }
            $record->save();
        }
        $this->owner->setAttribute($this->idProperty, $instance->{$this->idProperty});

        $event->isValid = false;
    }

    /**
     * @return boolean
     */
    public function isRecurrentInstance()
    {
        return $this->_isRecurrentInstance;
    }

    /**
     * @return ActiveRecord|null
     */
    public function getOriginalRecord()
    {
        return $this->_originalRecord;
    }

    /**
     * @throws \Recurr\Exception\InvalidWeekday|\yii\base\InvalidConfigException
     */
    public function createRecurrentInstance()
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

        $this->_isRecurrentInstance = true;
        $this->_originalRecord = clone $this->owner;

        list($next, $status) = $this->findNextExecution();

        /** @var \yii\db\ActiveRecord $instance */
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
                unset($this->owner->{$relatedRecordName});
            }
        }
    }

    /**
     * Clean attributes from changed active record
     * @param array $attributes
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function cleanAttributes(array $attributes)
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
     * @throws \Recurr\Exception\InvalidWeekday|\yii\base\InvalidConfigException|\Exception
     */
    protected function findNextExecution()
    {
        $model = ($this->owner->{$this->recurrenceProperty})
            ? $this->owner
            : $this->getOriginalRecord();
        $lastDoneQuery = new Query();
        /** @var \Recurr\Rule $rule */
        $rule = $model->{$this->recurrencePatternProperty};
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
        $transformer = new ArrayTransformer((new ArrayTransformerConfig())->setVirtualLimit(1 + count($rule->getExDates())));
        $status = $this->owner->{$this->statusProperty};
        // Start date from not done tasks
        if ($last) {
            if ((int)$last['status'] === Task::STATUS_DONE) {
                $rule->setStartDate(new \DateTime(
                    Yii::$app->formatter->asDatetime((int)$last['last'] + 86400, 'yyyy-MM-dd HH:mm:ss'),
                    new \DateTimeZone(Yii::$app->timeZone)
                ));
                $status = Task::STATUS_NOT_BEGUN;
            } else {
                $rule->setStartDate(new \DateTime(
                    Yii::$app->formatter->asDatetime((int)$last['last'], 'yyyy-MM-dd HH:mm:ss'),
                    new \DateTimeZone(Yii::$app->timeZone)
                ));
                $status = (int)$last['status'];
            }
        } else {
            if (isset($this->owner->{$this->startDateProperty})) {
                $rule->setStartDate(new \DateTime(
                    Yii::$app->formatter->asDatetime($model->{$this->startDateProperty}, 'yyyy-MM-dd HH:mm:ss'),
                    new \DateTimeZone(Yii::$app->timeZone)
                ));
            }
        }

        return [ArrayHelper::getValue($transformer->transform($rule), 0), $status];
    }
}
