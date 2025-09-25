<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\models;

use tonic\hq\models\AddresspoolAddress;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Class SearchMonitoringList
 * @package simialbi\yii2\kanban\models
 */
class SearchMonitoringList extends MonitoringList
{
    /**
     * @var string|array Member filter
     */
    public string|array $member_id = '';

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'date', 'format' => 'dd.MM.yyyy'],
            ['member_id', 'each', 'rule' => ['string', 'max' => 64]]
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
     * @param integer|string $userId
     *
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public function search(array $params, int|string $userId): ActiveDataProvider
    {
        $query = MonitoringList::find()
            ->distinct()
            ->alias('l')
            ->joinWith('members m')
            ->where(['{{l}}.[[created_by]]' => $userId]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'name' => SORT_ASC
                ]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            '{{l}}.[[id]]' => $this->id,
            '{{l}}.[[created_by]]' => $this->created_by,
            '{{l}}.[[updated_by]]' => $this->updated_by,
            '{{m}}.[[user_id]]' => $this->member_id
        ]);
        $query->andFilterWhere(['like', '{{l}}.[[name]]', $this->name])
            ->andFilterWhere([
                'and',
                ['>=', '{{l}}.[[created_at]]', (empty($this->created_at)) ? null : strtotime($this->created_at)],
                [
                    '<=',
                    '{{l}}.[[created_at]]',
                    (empty($this->created_at)) ? null : strtotime($this->created_at . ' +1 day')
                ],
            ])
            ->andFilterWhere([
                'and',
                ['>=', '{{l}}.[[updated_at]]', (empty($this->updated_at)) ? null : strtotime($this->updated_at)],
                [
                    '<=',
                    '{{l}}.[[updated_at]]',
                    (empty($this->created_at)) ? null : strtotime($this->updated_at . ' +1 day')
                ],
            ]);

        // mobile-filter
        $filterMobile = ArrayHelper::getValue($params, 'filter.mobile');
        if (isset($filterMobile)) {
            $query->leftJoin(AddresspoolAddress::tableName() . ' a', ['{{m}}.[[user_id]]' => '{{a}}.[[id]]']);
        }
        $query->andFilterWhere([
            'or',
            ['like', '{{l}}.[[name]]', $filterMobile],
            ['like', '{{a}}.[[firstname]]', $filterMobile],
            ['like', '{{a}}.[[lastname]]', $filterMobile],
        ]);

        return $dataProvider;
    }
}
