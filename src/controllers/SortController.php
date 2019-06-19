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
}
