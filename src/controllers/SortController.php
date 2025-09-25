<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Task;
use simialbi\yii2\kanban\models\TaskUserAssignment;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\kanban\TaskEvent;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * {@inheritDoc}
 *
 * @property-read Module $module
 */
class SortController extends \arogachev\sortable\controllers\SortController
{
    /**
     * @throws InvalidConfigException|Exception
     */
    public function actionChangeParent(): void
    {
        foreach ($this->_model->getSortableScopeCondition() as $attribute => $value) {
            $newValue = Yii::$app->request->post($attribute, $value);
            $this->_model->setAttribute($attribute, $newValue);
        }

        $this->_model->save();
    }

    /**
     * @throws Exception|\Exception
     */
    public function actionChangeAssignee(): void
    {
        $userId = Yii::$app->request->getBodyParam('user_id');
        TaskUserAssignment::deleteAll(['task_id' => $this->_model->primaryKey]);
        if (!empty($userId)) {
            $assignment = new TaskUserAssignment();
            $assignment->task_id = $this->_model->primaryKey;
            $assignment->user_id = $userId;
            $assignment->save();
        }
        $this->module->trigger(Module::EVENT_TASK_ASSIGNED, new TaskEvent([
            'task' => $this->_model,
            'user' => ArrayHelper::getValue($this->module->users, $userId)
        ]));
    }

    /**
     * @throws Exception
     */
    public function actionChangeStatus(): void
    {
        $status = Yii::$app->request->getBodyParam('status');
        $this->_model->setAttribute('status', $status);
        $this->_model->save();

        $this->module->trigger(Module::EVENT_TASK_STATUS_CHANGED, new TaskEvent([
            'task' => $this->_model,
            'data' => $status
        ]));
        if ($status == Task::STATUS_DONE) {
            $this->module->trigger(Module::EVENT_TASK_COMPLETED, new TaskEvent([
                'task' => $this->_model
            ]));
        }
    }

    /**
     * @throws Exception
     */
    public function actionChangeDate(): void
    {
        $date = Yii::$app->request->getBodyParam('date');
        $this->_model->setAttribute('end_date', $date);
        $this->_model->save();
    }
}
