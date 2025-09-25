<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Bucket
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property int $board_id
 * @property string $name
 * @property int $sort
 * @property int|string $created_by
 * @property int|string $updated_by
 * @property int|string $created_at
 * @property int|string $updated_at
 * @property string $sync_id
 *
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read Board $board
 * @property-read Task[] $tasks
 * @property-read Task[] $openTasks
 * @property-read Task[] $finishedTasks
 */
class Bucket extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__bucket}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'board_id'], 'integer'],
            [['name', 'sync_id'], 'string', 'max' => 255],

            ['board_id', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            [['board_id', 'name'], 'required']
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
            ],
            'sortable' => [
                'class' => ContinuousNumericalSortableBehavior::class,
                'sortAttribute' => 'sort',
                'scope' => function () {
                    return Bucket::find()->where(['board_id' => $this->board_id]);
                }
            ]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/bucket', 'Id'),
            'board_id' => Yii::t('simialbi/kanban/model/bucket', 'Board'),
            'name' => Yii::t('simialbi/kanban/model/bucket', 'Name'),
            'sort' => Yii::t('simialbi/kanban/model/bucket', 'Sort'),
            'created_by' => Yii::t('simialbi/kanban/model/bucket', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/bucket', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/bucket', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/bucket', 'Updated at'),
        ];
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
     * Get associated board
     * @return ActiveQuery
     */
    public function getBoard(): ActiveQuery
    {
        return $this->hasOne(Board::class, ['id' => 'board_id']);
    }

    /**
     * Get associated tasks
     * @return ActiveQuery
     */
    public function getTasks(): ActiveQuery
    {
        return $this->hasMany(Task::class, ['bucket_id' => 'id'])
            ->orderBy([Task::tableName() . '.[[sort]]' => SORT_ASC]);
    }

    /**
     * Get associated open tasks
     * @param bool $onlyOwn
     * @return ActiveQuery
     */
    public function getOpenTasks(bool $onlyOwn = false): ActiveQuery
    {
        $query = $this->hasMany(Task::class, ['bucket_id' => 'id'])
            ->where(['not', ['status' => Task::STATUS_DONE]])
            ->orderBy([Task::tableName() . '.[[sort]]' => SORT_ASC])
            ->with([
                'attachments',
                'assignments',
                'assignees',
                'comments',
                'checklistElements',
                'links',
                'proposal',
                'feedback',
                'ticket',
                'client',
                'recurrenceParent',
                'responsible',
                'children',
            ]);
        if ($onlyOwn) {
            $query
                ->innerJoinWith('assignments u')
                ->andWhere([
                    'or',
                    ['{{u}}.[[user_id]]' => Yii::$app->user->id],
                    [Task::tableName() . '.[[responsible_id]]' => Yii::$app->user->id]
                ]);
        }

        return $query;
    }

    /**
     * Get associated finished tasks
     * @param bool $onlyOwn
     * @return ActiveQuery
     */
    public function getFinishedTasks(bool $onlyOwn = false): ActiveQuery
    {
        $query = $this->hasMany(Task::class, ['bucket_id' => 'id'])
            ->where(['status' => Task::STATUS_DONE])
            ->orderBy([Task::tableName() . '.[[sort]]' => SORT_ASC])
            ->with('attachments')
            ->with('assignments')
            ->with('comments')
            ->with('checklistElements')
            ->with('links');
        if ($onlyOwn) {
            $query
                ->joinWith('assignments u')
                ->andWhere([
                    'or',
                    ['{{u}}.[[user_id]]' => Yii::$app->user->id],
                    [Task::tableName() . '.[[responsible_id]]' => Yii::$app->user->id]
                ]);
        }

        return $query;
    }

    /**
     * Returns an array for the Select2 widget, the boards being optgroups
     *
     * @param int|null|string $userId
     * @param bool $checklists if checklists should be included
     * @return array
     */
    public static function getSelect2Options(int|string|null $userId = null, bool $checklists = false): array
    {
        $userId = $userId ?? Yii::$app->user->id;
        $buckets = Bucket::find()
            ->select([
                '{{bu}}.[[id]]',
                '{{bu}}.[[name]]',
                'board_name' => Board::tableName() . '.[[name]]'
            ])
            ->alias('bu')
            ->innerJoinWith('board.assignments a', false)
            ->where([
                '{{a}}.[[user_id]]' => $userId,
                Board::tableName() . '.[[is_checklist]]' => $checklists
            ])
            ->orderBy([
                Board::tableName() . '.[[name]]' => SORT_ASC,
                '{{bu}}.[[name]]' => SORT_ASC
            ])
            ->asArray()
            ->indexBy('id')
            ->all();
        return ArrayHelper::map($buckets, 'id', 'name', 'board_name');
    }
}
