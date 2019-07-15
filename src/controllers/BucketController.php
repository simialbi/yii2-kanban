<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\BucketEvent;
use simialbi\yii2\kanban\models\Bucket;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Class BucketController
 * @package simialbi\yii2\kanban\controllers
 *
 * @property-read \simialbi\yii2\kanban\Module $module
 */
class BucketController extends Controller
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
                    ]
                ]
            ]
        ];
    }

    /**
     * Create a new bucket
     * @param integer $boardId
     * @return string
     */
    public function actionCreate($boardId)
    {
        $model = new Bucket(['board_id' => $boardId]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->renderAjax('_item', [
                'statuses' => $this->module->statuses,
                'id' => $model->id,
                'boardId' => $model->board_id,
                'title' => $model->name,
                'tasks' => $model->tasks,
                'keyName' => 'bucketId',
                'action' => 'change-parent',
                'sort' => true
            ]);
        }

        $this->module->trigger(Module::EVENT_BUCKET_CREATED, new BucketEvent([
            'bucket' => $model
        ]));

        return $this->renderAjax('create', [
            'model' => $model
        ]);
    }
}
