<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class PlanController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class PlanController extends Controller
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
                        'actions' => ['create'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view']
                    ]
                ]
            ]
        ];
    }

    /**
     * Plan overview
     */
    public function actionIndex()
    {
        $boards = Board::findByUserId();

        return $this->render('index', [
            'boards' => $boards
        ]);
    }

    /**
     * Show board
     *
     * @param integer $id
     * @param string $group
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id, $group = 'bucket')
    {
        $model = $this->findModel($id);

        switch ($group) {
            case 'assignee':
                $query = new Query();
                $query->select(['{{t}}.*', '{{u}}.[[user_id]]'])
                    ->from(['t' => Task::tableName()])
                    ->leftJoin(['u' => '{{%kanban_task_user_assignment}}'], '{{u}}.[[task_id]] = {{t}}.[[id]]')
                    ->innerJoin(['b' => Bucket::tableName()], '{{b}}.[[id]] = {{t}}.[[bucket_id]]')
                    ->innerJoin(['p' => Board::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
                    ->where(['{{p}}.[[id]]' => $model->id])
                    ->groupBy(['user_id', 'id']);
                $tasks = ArrayHelper::index($query->all(), null, 'user_id');

                $bucketContent = $this->renderPartial('_group_assignee', [
                    'model' => $model,
                    'tasksByUser' => $tasks,
                    'statuses' => $this->module->statuses
                ]);
                break;

            case 'status':
                $tasks = ArrayHelper::index($model->getTasks()->groupBy('status')->all(), null, 'status');

                $bucketContent = '';
                break;

            case 'date':
                $bucketContent = '';
                break;

            case 'bucket':
            default:
                $bucketContent = $this->renderPartial('_group_bucket', [
                    'model' => $model,
                    'statuses' => $this->module->statuses
                ]);
                break;
        }

        return $this->render('view', [
            'model' => $model,
            'buckets' => $bucketContent
        ]);
    }

    /**
     * Create new board
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Board([
            'is_public' => true
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', Yii::t(
                'simialbi/kanban/plan/notification',
                'Board <b>{board}</b> created',
                ['board' => $model->name]
            ));

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Board the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Board::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}
