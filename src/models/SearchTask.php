<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\kanban\Module;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

class SearchTask extends Task
{
    /**
     * @var int|string
     */
    public int|string $board_id = '';

    /**
     * @var int|string|array
     */
    public int|string|array $assignee_id = '';

    /**
     * @var int|string
     */
    public int|string $startTimeStamp = '';

    /**
     * @var int|string
     */
    public int|string $endTimeStamp = '';

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        /** @var Module $module */
        $module = \Yii::$app->getModule('schedule');

        return [
            [['id', 'bucket_id', 'ticket_id', 'client_id', 'board_id', 'status'], 'integer'],
            ['subject', 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys($module->statuses)],
            ['description', 'string'],
            [['card_show_description', 'card_show_checklist', 'card_show_links'], 'boolean'],
            ['start_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'startTimeStamp'],
            ['end_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'endTimeStamp'],
            [['created_at', 'updated_at', 'finished_at'], 'date', 'format' => 'dd.MM.yyyy'],
            [['created_by', 'updated_by', 'finished_by'], 'string', 'max' => 64],
            ['assignee_id', 'each', 'rule' => ['string', 'max' => 64]],
            ['responsible_id', 'each', 'rule' => ['string', 'max' => 64]],

            [['bucket_id', 'ticket_id'], 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param integer[]|string[] $userIds
     *
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public function search(array $params, array $userIds): ActiveDataProvider
    {
        $query = Task::find()
            ->alias('t')
            ->innerJoinWith('bucket b')
            ->innerJoinWith('board p')
            ->innerJoinWith('assignments a')
            ->where(['{{a}}.[[user_id]]' => $userIds]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'end_date' => SORT_DESC
                ]
            ]
        ]);

        $dataProvider->sort->attributes['board_id'] = [
            'asc' => ['{{p}}.[[name]]' => SORT_ASC],
            'desc' => ['{{p}}.[[name]]' => SORT_DESC]
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }


        // filter late tasks here
        if ($this->status == static::STATUS_LATE) {
            $query->andFilterWhere([
                'and',
                ['not', ['{{t}}.[[end_date]]' => null]],
                ['<=', '{{t}}.[[end_date]]', time()],
                ['!=', '{{t}}.[[status]]', static::STATUS_DONE],
            ]);
        } else {
            $query->andFilterWhere([
                '{{t}}.[[status]]' => $this->status
            ]);
        }

        $query->andFilterWhere([
            '{{t}}.[[id]]' => $this->id,
            '{{t}}.[[bucket_id]]' => $this->bucket_id,
            '{{b}}.[[board_id]]' => $this->board_id,
            '{{a}}.[[user_id]]' => $this->assignee_id,
            '{{t}}.[[client_id]]' => $this->client_id,
            '{{t}}.[[responsible_id]]' => $this->responsible_id,
            '{{t}}.[[start_date]]' => $this->startTimeStamp,
//            '{{t}}.[[end_date]]' => $this->endTimeStamp,
            '{{t}}.[[created_by]]' => $this->created_by,
            '{{t}}.[[updated_by]]' => $this->updated_by,
            '{{t}}.[[finished_by]]' => $this->finished_by,
        ]);

        $query->andFilterWhere(['like', '{{t}}.[[subject]]', $this->subject])
            ->andFilterWhere([
                'and',
                ['>=', '{{t}}.[[created_at]]', (empty($this->created_at)) ? null : strtotime($this->created_at)],
                [
                    '<=',
                    '{{t}}.[[created_at]]',
                    (empty($this->created_at)) ? null : strtotime($this->created_at . ' +1 day')
                ],
            ])
            ->andFilterWhere([
                'and',
                ['>=', '{{t}}.[[updated_at]]', (empty($this->updated_at)) ? null : strtotime($this->updated_at)],
                [
                    '<=',
                    '{{t}}.[[updated_at]]',
                    (empty($this->updated_at)) ? null : strtotime($this->updated_at . ' +1 day')
                ],
            ])
            ->andFilterWhere([
                'and',
                ['>=', '{{t}}.[[finished_at]]', (empty($this->finished_at)) ? null : strtotime($this->finished_at)],
                [
                    '<=',
                    '{{t}}.[[finished_at]]',
                    (empty($this->finished_at)) ? null : strtotime($this->finished_at . ' +1 day')
                ],
            ])
            ->andFilterWhere([
                '<=', '{{t}}.[[end_date]]', $this->endTimeStamp
            ]);

        // mobile-filter
        $filterMobile = ArrayHelper::getValue($params, 'filter.mobile');
        $query->andFilterWhere([
            'or',
            ['like', '{{t}}.[[subject]]', $filterMobile],
            ['like', '{{b}}.[[name]]', $filterMobile],
            ['like', '{{p}}.[[name]]', $filterMobile],
        ]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * Searches for all tasks, no restrictions from assignees
     * Used in CRM-Module to list tasks assigned to a client
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public function searchAll(array $params): ActiveDataProvider
    {
        $query = Task::find()
            ->alias('t')
            ->innerJoinWith('bucket b')
            ->innerJoinWith('board p');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'end_date' => SORT_DESC
                ]
            ]
        ]);

        $dataProvider->sort->attributes['board_id'] = [
            'asc' => ['{{p}}.[[name]]' => SORT_ASC],
            'desc' => ['{{p}}.[[name]]' => SORT_DESC]
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }


        // filter late tasks here
        if ($this->status == static::STATUS_LATE) {
            $query->andFilterWhere([
                'and',
                ['not', ['{{t}}.[[end_date]]' => null]],
                ['<=', '{{t}}.[[end_date]]', time()],
                ['!=', '{{t}}.[[status]]', static::STATUS_DONE],
            ]);
        } else {
            $query->andFilterWhere([
                '{{t}}.[[status]]' => $this->status
            ]);
        }

        $query->andFilterWhere([
            '{{t}}.[[id]]' => $this->id,
            '{{t}}.[[bucket_id]]' => $this->bucket_id,
            '{{b}}.[[board_id]]' => $this->board_id,
            '{{a}}.[[user_id]]' => $this->assignee_id,
            '{{t}}.[[client_id]]' => $this->client_id,
            '{{t}}.[[responsible_id]]' => $this->responsible_id,
            '{{t}}.[[start_date]]' => $this->startTimeStamp,
            '{{t}}.[[end_date]]' => $this->endTimeStamp,
            '{{t}}.[[created_by]]' => $this->created_by,
            '{{t}}.[[updated_by]]' => $this->updated_by,
            '{{t}}.[[finished_by]]' => $this->finished_by,
        ]);

        $query->andFilterWhere(['like', '{{t}}.[[subject]]', $this->subject])
            ->andFilterWhere([
                'and',
                ['>=', '{{t}}.[[created_at]]', (empty($this->created_at)) ? null : strtotime($this->created_at)],
                [
                    '<=',
                    '{{t}}.[[created_at]]',
                    (empty($this->created_at)) ? null : strtotime($this->created_at . ' +1 day')
                ],
            ])
            ->andFilterWhere([
                'and',
                ['>=', '{{t}}.[[updated_at]]', (empty($this->updated_at)) ? null : strtotime($this->updated_at)],
                [
                    '<=',
                    '{{t}}.[[updated_at]]',
                    (empty($this->updated_at)) ? null : strtotime($this->updated_at . ' +1 day')
                ],
            ])
            ->andFilterWhere([
                'and',
                ['>=', '{{t}}.[[finished_at]]', (empty($this->finished_at)) ? null : strtotime($this->finished_at)],
                [
                    '<=',
                    '{{t}}.[[finished_at]]',
                    (empty($this->finished_at)) ? null : strtotime($this->finished_at . ' +1 day')
                ],
            ]);

        // mobile-filter
        $filterMobile = ArrayHelper::getValue($params, 'filter.mobile');
        $query->andFilterWhere([
            'or',
            ['like', '{{t}}.[[subject]]', $filterMobile],
            ['like', '{{b}}.[[name]]', $filterMobile],
            ['like', '{{p}}.[[name]]', $filterMobile],
        ]);

        return $dataProvider;
    }
}
