<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\models;

use Yii;
use yii\base\Model;
use yii\db\Exception;

/**
 * Class MonitoringForm
 * @package simialbi\yii2\kanban\models
 */
class MonitoringForm extends Model
{
    /**
     * @var integer|null the lists id (only update)
     */
    public ?int $id = 0;

    /**
     * @var string Monitoring list name
     */
    public string $name = '';

    /**
     * @var array Monitoring list members (who to monitor)
     */
    public array $members = [];

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],
            ['members', 'each', 'rule' => ['string', 'max' => 64]],

            ['id', 'default'],

            [['name', 'members'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/monitoring-form', 'Id'),
            'name' => Yii::t('simialbi/kanban/model/monitoring-form', 'Name'),
            'members' => Yii::t('simialbi/kanban/model/monitoring-form', 'Members')
        ];
    }

    /**
     * Save the list (create new or update existing)
     * @return boolean
     * @throws Exception
     */
    public function saveList(): bool
    {
        $model = ($this->id) ? MonitoringList::findOne($this->id) : new MonitoringList();
        $model->name = $this->name;
        if (!$model->save()) {
            // TODO throw
            return false;
        }
        $model->unlinkAll('members', true);
        foreach ($this->members as $memberId) {
            $member = new MonitoringMember([
                'list_id' => $model->id,
                'user_id' => $memberId
            ]);
            if (!$member->save()) {
                // TODO
                return false;
            }
        }

        return true;
    }
}
