<?php

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\ews\Query;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class SearchChecklistTemplate extends ChecklistTemplate
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'board_id', 'created_at', 'updated_at'], 'integer'],
            [['created_by', 'updated_by'], 'string', 'max' => 64],
            ['name', 'string', 'max' => 255],
            ['elementCount', 'integer', 'skipOnEmpty' => true],
        ];
    }

    /**
     * Get element count
     * @return int|string
     */
    public function getElementCount(): int|string
    {
        return $this->elementCount;
    }

    /**
     * Search checklist templates
     * @param array $params
     * @param int $boardId
     * @return ActiveDataProvider
     */
    public function search(array $params, int $boardId): ActiveDataProvider
    {
        $query = ChecklistTemplate::find()
            ->alias('ct')
            ->select([
                'ct.*',
                'elementCount' => (new Query())
                    ->select(new Expression('COUNT(*)'))
                    ->from(['c' => ChecklistTemplateElement::tableName()])
                    ->where('c.template_id = ct.id')
            ])
            ->joinWith('creator c')
            ->where(['board_id' => $boardId]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC]
            ]
        ]);

        $dataProvider->sort->attributes['created_by'] = [
            'asc' => [
                'c.lastname' => SORT_ASC,
                'c.firstname' => SORT_ASC
            ],
            'desc' => [
                'c.lastname' => SORT_DESC,
                'c.firstname' => SORT_DESC
            ]
        ];
        $dataProvider->sort->attributes['elementCount'] = [
            'asc' => [
                'elementCount' => SORT_ASC
            ],
            'desc' => [
                'elementCount' => SORT_DESC
            ]
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'created_by' => $this->created_by
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        $query->filterHaving([
            'elementCount' => $this->elementCount
        ]);

        return $dataProvider;
    }
}
