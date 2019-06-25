<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use rmrevin\yii\fontawesome\FAR;
use rmrevin\yii\fontawesome\FAS;
use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\models\Task;
use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
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
                        'actions' => ['create', 'assign-user', 'expel-user'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'schedule', 'chart']
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

        Url::remember(['plan/view', 'id' => $id, 'group' => $group], 'plan-view');

        return $this->render('view', [
            'model' => $model,
            'buckets' => $bucketContent,
            'users' => call_user_func([Yii::$app->user->identityClass, 'findIdentities'])
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

            $calendarTask = [
                'id' => $task->id,
                'title' => $task->subject,
                'start' => $startDate,
                'end' => $endDate,
                'allDay' => true,
                'classNames' => ['border-0'],
                'url' => Url::to(['task/update', 'id' => $task->id])
            ];

            if (strtotime($endDate) < time()) {
                $calendarTask['title'] = FAR::i('calendar-alt') . ' ' . $calendarTask['title'];
                $calendarTask['classNames'] = ['border-0', 'bg-danger'];
            }
            if ($task->status !== Task::STATUS_NOT_BEGUN && $task->status !== Task::STATUS_DONE) {
                $calendarTask['title'] = FAS::i('star-half-alt') . ' ' . $calendarTask['title'];
            }

            $calendarTasks[] = $calendarTask;
        }

        Url::remember(['plan/schedule', 'id' => $id], 'plan-view');

        return $this->render('schedule', [
            'model' => $model,
            'otherTasks' => $this->renderBucketContent($model, 'schedule'),
            'calendarTasks' => $calendarTasks,
            'users' => call_user_func([Yii::$app->user->identityClass, 'findIdentities'])
        ]);
    }

    /**
     * @param integer $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionChart($id)
    {
        $model = $this->findModel($id);

        $query = new Query();
        $query->select([
            'value' => new Expression('SUM({{t}}.[[id]])'),
            '{{t}}.[[status]]'
        ])
            ->from(['p' => $model::tableName()])
            ->innerJoin(['b' => Bucket::tableName()], '{{p}}.[[id]] = {{b}}.[[board_id]]')
            ->innerJoin(['t' => Task::tableName()], '{{t}}.[[bucket_id]] = {{b}}.[[id]]')
            ->groupBy(['{{t}}.[[status]]'])
            ->where(['{{p}}.[[id]]' => $id]);
        $byStatus = $query->all();
        foreach ($byStatus as &$item) {
            $item['status'] = ArrayHelper::getValue($this->module->statuses, $item['status'], $item['status']);
        }


        return $this->render('chart', [
            'model' => $model,
            'users' => call_user_func([Yii::$app->user->identityClass, 'findIdentities']),
            'byStatus' => $byStatus
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
     * Assign user to plan
     *
     * @param integer $id
     * @param integer|string $userId
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionAssignUser($id, $userId)
    {
        $model = $this->findModel($id);

        $model::getDb()->createCommand()->insert('{{%kanban_board_user_assignment}}', [
            'board_id' => $model->id,
            'user_id' => $userId
        ])->execute();

        return $this->redirect(Url::previous('plan-view'));
    }

    /**
     * Assign user to plan
     *
     * @param integer $id
     * @param integer|string $userId
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionExpelUser($id, $userId)
    {
        $model = $this->findModel($id);

        $model::getDb()->createCommand()->delete('{{%kanban_board_user_assignment}}', [
            'board_id' => $model->id,
            'user_id' => $userId
        ])->execute();
        $model::getDb()->createCommand()->delete('{{%kanban_task_user_assignment}}', [
            'task_id' => ArrayHelper::getColumn($model->tasks, 'id'),
            'user_id' => $userId
        ])->execute();

        return $this->redirect(Url::previous('plan-view'));
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
