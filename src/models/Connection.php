<?php

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\models\UserInterface;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Class Connection
 *
 * @property int $id
 * @property int|string $user_id
 * @property int $bucket_id
 * @property int $type
 * @property array $options
 *
 * @property-read UserInterface $address
 * @property-read string $typeLabel
 * @property-read Bucket $bucket
 */
class Connection extends BaseConnection
{
    /**
     * if true, time windows will be exported to outlook calendar
     * @var bool
     */
    public bool $export_time_windows = false;

    /**
     * if true, tasks will be imported from outlook calendar
     * @var bool
     */
    public bool $import_tasks = false;

    /**
     * if true, tasks will be deleted from outlook after import
     * @var bool
     */
    public bool $delete_tasks_after_import = false;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return ArrayHelper::merge(parent::rules(), [
            [['export_time_windows', 'import_tasks', 'delete_tasks_after_import'], 'required'],
            [['export_time_windows', 'import_tasks', 'delete_tasks_after_import'], 'boolean'],
            [['export_time_windows', 'import_tasks', 'delete_tasks_after_import'], 'default', 'value' => false],
            ['delete_tasks_after_import', 'filter', 'filter' => function ($value) {
                if (!$this->import_tasks) {
                    $value = false;
                }
                return $value;
            }]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'export_time_windows' => Yii::t('simialbi/kanban/model/connection', 'Export time windows'),
            'import_tasks' => Yii::t('simialbi/kanban/model/connection', 'Import tasks'),
            'delete_tasks_after_import' => Yii::t('simialbi/kanban/model/connection', 'Delete tasks after import'),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function optionAttributes(): array
    {
        return [
            'export_time_windows',
            'import_tasks',
            'delete_tasks_after_import'
        ];
    }
}
