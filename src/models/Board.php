<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;

use rmrevin\yii\fontawesome\FAL;
use simialbi\yii2\kanban\helpers\FileHelper;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\base\ErrorException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\DbDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class Board
 * @package simialbi\yii2\kanban\models
 *
 * @property int $id
 * @property string $name
 * @property string $image
 * @property boolean $is_public
 * @property boolean $is_checklist
 * @property int|string $created_by
 * @property int|string $updated_by
 * @property int|string $created_at
 * @property int|string $updated_at
 * @property int $sync_id
 *
 * @property-read string $fullName
 * @property-read string $visual
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read UserInterface[] $assignees
 * @property-read BoardUserAssignment[] $assignments
 * @property-read Bucket[] $buckets
 * @property-read Task[] $tasks
 * @property-read BoardUserSetting[] $settings              // all settings for this board
 * @property-read BoardUserSetting $setting                 // current user setting for this board
 * @property-read boolean $hidden                           // if the board is hidden for the current user
 * @property-read ChecklistTemplate[] $checklistTemplates
 */
class Board extends ActiveRecord
{
    /**
     * @var string|UploadedFile|null
     */
    public UploadedFile|string|null $uploadedFile = null;
    /**
     * @var array Colors to user for visualisation generation
     */
    private array $_colors = [
        [0, 123, 255],
        [102, 16, 242],
        [111, 66, 193],
        [232, 62, 140],
        [220, 53, 69],
        [253, 126, 20],
        [255, 193, 7],
        [40, 167, 69],
        [32, 201, 151],
        [23, 162, 184]
    ];
    /**
     * @var string Visualisation
     */
    private string $_visual;

    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__board}}';
    }

    /**
     * Find boards assigned to user
     *
     * @param integer|string|null $id
     * @param array $filters
     *
     * @return Board[]
     */
    public static function findByUserId(int|string|null $id = null, array $filters = []): array
    {
        if ($id === null) {
            $id = Yii::$app->user->id;
        }

        $sql = <<<SQL
SELECT COUNT(`b`.`id`) as cnt1, (SELECT COUNT(`s`.`id`) FROM `re_kanban__board_user_setting` as `s` WHERE `is_hidden` = true) AS cnt2
FROM `re_kanban__board` AS `b`
SQL;

        $dep = new DbDependency([
            'sql' => $sql
        ]);

        $query = static::find()
            ->cache(60, $dep)
            ->alias('b')
            ->joinWith('assignments ba', false)
            ->joinWith('buckets.tasks.assignments ta', false)
            ->leftJoin(['s' => BoardUserSetting::tableName()], [
                '{{s}}.[[board_id]]' => new Expression('{{b}}.[[id]]'),
                '{{s}}.[[user_id]]' => Yii::$app->user->id
            ])
            ->where([
                'or',
                ['{{b}}.[[is_public]]' => 1],
                ['{{ba}}.[[user_id]]' => $id],
                ['{{ta}}.[[user_id]]' => $id],
                [Task::tableName() . '.[[responsible_id]]' => $id],
            ])
            ->andFilterWhere($filters)
            ->orderBy(['{{b}}.[[name]]' => SORT_ASC]);

        return $query->all();
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            [['name', 'image', 'sync_id'], 'string', 'max' => 255],
            ['uploadedFile', 'file', 'mimeTypes' => 'image/*'],
            [['is_public', 'is_checklist'], 'boolean'],

            ['is_public', 'default', 'value' => true],
            ['is_checklist', 'default', 'value' => false],
            ['image', 'default'],

            [['name', 'is_public'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_by', 'updated_by'],
                    self::EVENT_BEFORE_UPDATE => 'updated_by'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at'
                ]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/board', 'Id'),
            'name' => Yii::t('simialbi/kanban/model/board', 'Name'),
            'image' => Yii::t('simialbi/kanban/model/board', 'Image'),
            'is_public' => Yii::t('simialbi/kanban/model/board', 'Is public'),
            'is_checklist' => Yii::t('simialbi/kanban/model/board', 'Is checklist'),
            'created_by' => Yii::t('simialbi/kanban/model/board', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/board', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/board', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/board', 'Updated at')
        ];
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert && !Yii::$app->user->isGuest) {
            $assignment = new BoardUserAssignment();
            $assignment->board_id = $this->id;
            $assignment->user_id = (string)Yii::$app->user->id;
            $assignment->save();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        try {
            /** @var Module $module */
            $module = Yii::$app->controller->module;
            FileHelper::removeDirectory(Yii::getAlias($module->uploadWebRoot . '/plan/' . $this->id));
        } catch (ErrorException $e) {
            Yii::error('Could not delete folder: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Prefixes name with an icon, if board is a checklist
     * @return string
     */
    public function getFullName(): string
    {
        $prefix = '';
        if ($this->is_checklist) {
            $prefix = FAL::i('clipboard-check') . ' ';
        }

        return $prefix . $this->name;
    }

    /**
     * Get visualisation. If set, this method return the image, otherwise generates a visualisation
     * @return string
     */
    public function getVisual(): string
    {
        if (!isset($this->image)) {
            if (empty($this->_visual)) {
                if (function_exists('imagecreatetruecolor')) {
                    $color = $this->_colors[($this->id % count($this->_colors) - 1)];
                    $text = strtoupper(substr($this->name, 0, 1));
                    $font = Yii::getAlias('@simialbi/yii2/kanban/assets/fonts/arial.ttf');

                    $img = imagecreatetruecolor(120, 100);
                    $bgColor = imagecolorallocate($img, $color[0], $color[1], $color[2]);
                    $white = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $bgColor);
                    $bbox = imagettfbbox(20, 0, $font, $text);
                    $x = (120 - ($bbox[2] - $bbox[0])) / 2;
                    $y = 60;
                    imagettftext($img, 20, 0, $x, $y, $white, $font, $text);

                    ob_start();
                    imagepng($img);
                    $image = ob_get_clean();

                    $this->_visual = 'data:image/png;base64,' . base64_encode($image);
                    imagedestroy($img);
                }
            }

            return $this->_visual;
        }

        return $this->image;
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     */
    public function getAuthor(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->created_by);
    }

    /**
     * Get user last updated
     * @return UserInterface
     * @throws \Exception
     */
    public function getUpdater(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->updated_by);
    }

    /**
     * Get users assigned to this task
     * @return array
     * @throws \Exception
     */
    public function getAssignees(): array
    {
        $allAssignees = Yii::$app->controller->module->users;

        $assignees = [];
        foreach ($this->assignments as $assignment) {
            $item = ArrayHelper::getValue($allAssignees, $assignment->user_id);
            if ($item) {
                $assignees[] = $item;
            }
        }

        return $assignees;
    }

    /**
     * Get assigned user id's
     * @return ActiveQuery
     */
    public function getAssignments(): ActiveQuery
    {
        return $this->hasMany(BoardUserAssignment::class, ['board_id' => 'id']);
    }

    /**
     * Get associated buckets
     * @return ActiveQuery
     */
    public function getBuckets(): ActiveQuery
    {
        return $this->hasMany(Bucket::class, ['board_id' => 'id'])
            ->orderBy([Bucket::tableName() . '.[[sort]]' => SORT_ASC]);
    }

    /**
     * Get associated tasks
     * @return ActiveQuery
     */
    public function getTasks(): ActiveQuery
    {
        return $this->hasMany(Task::class, ['bucket_id' => 'id'])->via('buckets');
    }

    /**
     * Get associated board user settings
     * @return ActiveQuery
     */
    public function getSettings(): ActiveQuery
    {
        return $this->hasMany(BoardUserSetting::class, ['board_id' => 'id']);
    }

    /**
     * Get board user setting for the current user
     * @return ActiveQuery
     */
    public function getSetting(): ActiveQuery
    {
        return $this->hasOne(BoardUserSetting::class, ['board_id' => 'id'])
            ->where([
                'user_id' => Yii::$app->user->id
            ]);
    }

    /**
     * If board is hidden for current user
     * @return boolean
     */
    public function getHidden(): bool
    {
        return $this->setting?->is_hidden ?? false;
    }

    /**
     * Related checklist templates
     * @return ActiveQuery
     */
    public function getChecklistTemplates(): ActiveQuery
    {
        return $this->hasMany(ChecklistTemplate::class, ['board_id' => 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->inverseOf('board');
    }
}
