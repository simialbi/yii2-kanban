<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\MonitoringForm;
use simialbi\yii2\kanban\models\MonitoringList;
use simialbi\yii2\kanban\models\SearchMonitoringList;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class MonitoringController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read Module $module
 */
class MonitoringController extends Controller
{
    /**
     * {@inheritDoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'permissions' => ['monitorKanbanTasks']
                    ]
                ]
            ]
        ];
    }

    /**
     * List monitoring lists
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SearchMonitoringList();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Yii::$app->user->id);

        return $this->renderAjax('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'users' => ArrayHelper::getColumn($this->module->users, 'name', true)
        ]);
    }

    /**
     * Create a new list
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new MonitoringForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveList()) {
            return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
        }

        return $this->renderAjax('upsert', [
            'model' => $model,
            'users' => ArrayHelper::getColumn($this->module->users, 'name', true)
        ]);
    }

    /**
     * Update an existing list
     * @param integer|string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $list = $this->findModel($id);
        $model = new MonitoringForm([
            'id' => $list->id,
            'name' => $list->name,
            'members' => ArrayHelper::getColumn($list->members, 'id')
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveList()) {
            return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
        }

        return $this->renderAjax('upsert', [
            'model' => $model,
            'users' => ArrayHelper::getColumn($this->module->users, 'name', true)
        ]);
    }

    /**
     * Delete a list
     * @param integer $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $list = $this->findModel($id);

        $list->delete();

        return $this->redirect(['plan/index', 'activeTab' => 'monitoring']);
    }

    /**
     * @param integer $id
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $list = $this->findModel($id);
    }

    /**
     * Finds the model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return MonitoringList the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = MonitoringList::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
