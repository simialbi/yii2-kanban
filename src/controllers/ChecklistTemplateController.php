<?php

namespace simialbi\yii2\kanban\controllers;

use simialbi\yii2\kanban\models\Board;
use simialbi\yii2\kanban\models\ChecklistTemplate;
use simialbi\yii2\kanban\models\ChecklistTemplateElement;
use simialbi\yii2\kanban\models\SearchChecklistTemplate;
use simialbi\yii2\kanban\Module;
use Yii;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\ContentNegotiator;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

class ChecklistTemplateController extends Controller
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
                        'actions' => [
                            'index',
                            'create',
                            'view',
                            'list',
                        ],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'update',
                            'delete',
                        ],
                        'matchCallback' => function (): bool {
                            return Yii::$app->user->id === ChecklistTemplate::findOne(Yii::$app->request->get('id'))->created_by;
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'create-element',
                        ],
                        'matchCallback' => function (): bool {
                            return Yii::$app->user->id === ChecklistTemplate::findOne(Yii::$app->request->get('templateId'))->created_by;
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'update-element',
                            'delete-element',
                        ],
                        'matchCallback' => function (): bool {
                            return Yii::$app->user->id === ChecklistTemplateElement::findOne(Yii::$app->request->get('id'))->template->created_by;
                        }
                    ]
                ]
            ],
            'negotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['list'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON
                ]
            ]
        ];
    }

    /**
     * Action index
     *
     * @param int $boardId
     *
     * @return string
     */
    public function actionIndex(int $boardId): string
    {
        $board = Board::findOne($boardId);

        $searchModel = new SearchChecklistTemplate();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $boardId);

        /** @var Module $module */
        $module = Yii::$app->getModule('schedule');
        $users = ArrayHelper::getColumn($module->getUsers(), 'name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'board' => $board,
            'users' => $users
        ]);
    }

    /**
     * Action create
     *
     * @param int $boardId
     *
     * @return string|Response
     * @throws Exception
     */
    public function actionCreate(int $boardId): Response|string
    {
        $model = new ChecklistTemplate([
            'board_id' => $boardId
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return <<<HTML
<script type="text/javascript">
    jQuery('#hqDynamicModal').modal('hide');
    jQuery.pjax.reload({container: '#checklistTemplatePjax', timeout: 3000});
</script>
HTML;
        }

        return $this->renderAjax('create', [
            'model' => $model
        ]);
    }

    /**
     * Action update
     *
     * @param int $id
     *
     * @return string|Response
     * @throws Exception
     */
    public function actionUpdate(int $id): string|Response
    {
        $model = ChecklistTemplate::findOne($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return <<<HTML
<script type="text/javascript">
    jQuery('#hqDynamicModal').modal('hide');
    jQuery.pjax.reload({container: '#checklistTemplatePjax', timeout: 3000});
</script>
HTML;
        }

        return $this->renderAjax('update', [
            'model' => $model
        ]);
    }

    /**
     * Action view
     *
     * @param int $id
     *
     * @return string|Response
     */
    public function actionView(int $id): string|Response
    {
        $model = ChecklistTemplate::findOne($id);

        return $this->render('view', [
            'model' => $model
        ]);
    }

    /**
     * Action delete
     *
     * @param int $id
     * @param int $boardId
     *
     * @return Response
     */
    public function actionDelete(int $id, int $boardId): Response
    {
        $model = ChecklistTemplate::findOne($id);
        try {
            $model->delete();
        } catch (StaleObjectException|\Throwable) {
        }

        return $this->redirect(['index', 'boardId' => $boardId]);
    }

    /**
     * Action create element
     *
     * @param int $templateId
     *
     * @return string
     * @throws Exception
     */
    public function actionCreateElement(int $templateId): string
    {
        $model = new ChecklistTemplateElement([
            'template_id' => $templateId
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return <<<HTML
<script type="text/javascript">
    jQuery('#hqDynamicModal').modal('hide');
    jQuery.pjax.reload({container: '#checklistTemplateElementsPjax', timeout: 3000});
</script>
HTML;
        }

        return $this->renderAjax('create-element', [
            'model' => $model
        ]);
    }

    /**
     * Action update element
     *
     * @param int $id
     *
     * @return string
     * @throws Exception
     */
    public function actionUpdateElement(int $id): string
    {
        $model = ChecklistTemplateElement::findOne($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return <<<HTML
<script type="text/javascript">
    jQuery('#hqDynamicModal').modal('hide');
    jQuery.pjax.reload({container: '#checklistTemplateElementsPjax', timeout: 3000});
</script>
HTML;
        }

        return $this->renderAjax('update-element', [
            'model' => $model
        ]);
    }

    /**
     * Action delete element
     *
     * @param int $id
     *
     * @return Response
     */
    public function actionDeleteElement(int $id): Response
    {
        $model = ChecklistTemplateElement::findOne($id);
        $templateId = $model->template_id;
        try {
            $model->delete();
        } catch (StaleObjectException|\Throwable) {
        }

        return $this->redirect(['view', 'id' => $templateId]);
    }

    /**
     * Returns an array of checklist template elements
     *
     * @param int $id template id
     *
     * @return array
     */
    public function actionList(int $id): array
    {
        return ChecklistTemplateElement::find()
            ->select(['name', 'dateOffset'])
            ->where(['template_id' => $id])
            ->orderBy(['sort' => SORT_ASC])
            ->asArray()
            ->all();
    }
}
