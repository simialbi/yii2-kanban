<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Board;
use Yii;
use yii\filters\AccessControl;
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
    use RenderingTrait;

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
                        'actions' => ['index', 'view', 'schedule']
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

        $bucketContent = $this->renderBucketContent($model, $group);

        return $this->render('view', [
            'model' => $model,
            'buckets' => $bucketContent
        ]);
    }

    /**
     * Schedule view
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSchedule($id)
    {
        $model = $this->findModel($id);

        $tasks = $model->getTasks()
            ->where(['not', ['start_date' => null]])
            ->orWhere(['not', ['end_date' => null]])
            ->all();
        /* @var $tasks \simialbi\yii2\kanban\models\Task[] */

        $calendarTasks = [];
        foreach ($tasks as $task) {
            /* @var $task \simialbi\yii2\kanban\models\Task */
            $startDate = (empty($task->start_date))
                ? Yii::$app->formatter->asDatetime($task->end_date, 'php:c')
                : Yii::$app->formatter->asDatetime($task->start_date, 'php:c');
            $endDate = (empty($task->end_date))
                ? Yii::$app->formatter->asDatetime($task->start_date, 'php:c')
                : Yii::$app->formatter->asDatetime($task->end_date, 'php:c');

            $calendarTasks[] = [
                'id' => $task->id,
                'title' => $task->subject,
                'start' => $startDate,
                'end' => $endDate
            ];
        }

        return $this->render('schedule', [
            'model' => $model,
            'tasks' => $calendarTasks
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
