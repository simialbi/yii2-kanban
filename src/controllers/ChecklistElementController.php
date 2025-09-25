<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\ChecklistElement;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class ChecklistElementController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class ChecklistElementController extends Controller
{
    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['set-done'],
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * Set checklist element to done status
     * @param int $id
     * @param bool $readonly
     * @return string
     * @throws NotFoundHttpException|Exception
     */
    public function actionSetDone(int $id, bool $readonly = false): string
    {
        $model = $this->findModel($id);

        $model->is_done = true;
        $model->save();

        return $this->renderAjax('/task/item', [
            'model' => $model->task,
            'statuses' => $this->module->statuses,
            'users' => $this->module->users,
            'closeModal' => false,
            'group' => 'bucket',
            'readonly' => $readonly
        ]);
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return ChecklistElement the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): ChecklistElement
    {
        if (($model = ChecklistElement::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
