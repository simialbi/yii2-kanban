<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use Yii;

/**
 * {@inheritDoc}
 */
class SortController extends \arogachev\sortable\controllers\SortController
{
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function actionChangeParent()
    {
        foreach ($this->_model->getSortableScopeCondition() as $attribute => $value) {
            $newValue = Yii::$app->request->post($attribute, $value);
            $this->_model->setAttribute($attribute, $newValue);
        }

        $this->_model->save();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionChangeAssignee()
    {
        $userId = Yii::$app->request->getBodyParam('user_id');
        $db = call_user_func([$this->_model, 'getDb']);
        /** @var $db \yii\db\Connection */
        $db->createCommand()->delete('{{%kanban_task_user_assignment}}', [
            'task_id' => $this->_model->primaryKey
        ])->execute();
        if (!empty($userId)) {
            $db->createCommand()->insert('{{%kanban_task_user_assignment}}', [
                'task_id' => $this->_model->primaryKey,
                'user_id' => $userId
            ])->execute();
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionChangeStatus()
    {
        $status = Yii::$app->request->getBodyParam('status');
        $this->_model->setAttribute('status', $status);
        $this->_model->save();
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionChangeDate()
    {
        $date = Yii::$app->request->getBodyParam('date');
        $this->_model->setAttribute('end_date', $date);
        $this->_model->save();
    }
}
