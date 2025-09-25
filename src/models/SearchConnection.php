<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\models;

use Exception;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Class SearchMonitoringList
 * @package simialbi\yii2\kanban\models
 */
class SearchConnection extends Connection
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'bucket_id', 'type'], 'integer'],
            ['user_id', 'string', 'max' => 64],
            [
                'bucket_id',
                'exist',
                'targetClass' => Bucket::class,
                'targetAttribute' => 'id'
            ]
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param int|string $id User id
     *
     * @return ActiveDataProvider
     * @throws Exception
     */
    public function search(array $params, int|string $id): ActiveDataProvider
    {
        $query = Connection::find()
            ->joinWith('bucket bucket')
            ->where([
                'user_id' => $id
            ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'bucket_id' => SORT_ASC
                ]
            ]
        ]);

        $dataProvider->sort->attributes['bucket_id'] = [
            'asc' => ['bucket.name' => SORT_ASC],
            'desc' => ['bucket.name' => SORT_DESC]
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'bucket_id' => $this->bucket_id,
            'type' => $this->type
        ]);

        // mobile-filter
        $filterMobile = ArrayHelper::getValue($params, 'filter.mobile');
        $query->andFilterWhere([
            'or',
            ['like', '{{bucket}}.[[name]]', $filterMobile],
        ]);

        return $dataProvider;
    }
}
