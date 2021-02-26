<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class SearchTask extends Task
{
    /**
     * @var integer
     */
    public $board_id;

    /**
     * @var integer
     */
    public $assignee_id;

    /**
     * @var integer
     */
    public $startTimeStamp;

    /**
     * @var integer
     */
    public $endTimeStamp;

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['id', 'bucket_id', 'ticket_id', 'board_id'], 'integer'],
            ['subject', 'string', 'max' => 255],
            ['status', 'in', 'range' => [self::STATUS_DONE, self::STATUS_IN_PROGRESS, self::STATUS_NOT_BEGUN]],
            ['description', 'string'],
            [['card_show_description', 'card_show_checklist', 'card_show_links'], 'boolean'],
            ['start_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'startTimeStamp'],
            ['end_date', 'date', 'format' => 'dd.MM.yyyy', 'timestampAttribute' => 'endTimeStamp'],
            [['created_at', 'updated_at', 'finished_at'], 'date', 'format' => 'dd.MM.yyyy'],
            [['created_by', 'updated_by', 'finished_by'], 'integer'],
            ['assignee_id', 'each', 'rule' => ['integer']],

            [['bucket_id', 'ticket_id'], 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function scenarios()
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
     */
    public function search($params, $userIds)
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

        $query->andFilterWhere([
            '{{t}}.[[id]]' => $this->id,
            '{{t}}.[[bucket_id]]' => $this->bucket_id,
            '{{b}}.[[board_id]]' => $this->board_id,
            '{{a}}.[[user_id]]' => $this->assignee_id,
            '{{t}}.[[start_date]]' => $this->startTimeStamp,
            '{{t}}.[[end_date]]' => $this->endTimeStamp,
            '{{t}}.[[created_by]]' => $this->created_by,
            '{{t}}.[[updated_by]]' => $this->updated_by,
            '{{t}}.[[finished_by]]' => $this->finished_by,
            '{{t}}.[[status]]' => $this->status
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

        return $dataProvider;
    }
}
