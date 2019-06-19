<?php
/**
 * @package yii2-kanban
 * @author Simon Karlen <simi.albi@outlook.com>
 * @copyright Copyright © 2019 Simon Karlen
 */

namespace simialbi\yii2\kanban\models;


use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Class Board
 * @package simialbi\yii2\kanban\models
 *
 * @property integer $id
 * @property string $name
 * @property string $image
 * @property boolean $is_public
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer|string $created_at
 * @property integer|string $updated_at
 *
 * @property-read string $visual
 * @property-read UserInterface $author
 * @property-read UserInterface $updater
 * @property-read UserInterface[] $assignees
 * @property-read Bucket[] $buckets
 * @property-read Task[] $tasks
 */
class Board extends ActiveRecord
{
    /**
     * @var array Colors to user for visualisation generation
     */
    private $_colors = [
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
    private $_visual;

    /**
     * {@inheritDoc}
     */
    public static function tableName()
    {
        return '{{%kanban_board}}';
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],
            ['image', 'file', 'mimeTypes' => 'image/*'],
            ['is_public', 'boolean'],

            ['is_public', 'default', 'value' => true],
            ['image', 'default'],

            [['name', 'is_public'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors()
    {
        return [
            'blameable' => [
                'class'      => BlameableBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_by', 'updated_by'],
                    self::EVENT_BEFORE_UPDATE => 'updated_by'
                ]
            ],
            'timestamp' => [
                'class'      => TimestampBehavior::class,
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
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('simialbi/kanban/model/board', 'Id'),
            'name' => Yii::t('simialbi/kanban/model/board', 'Name'),
            'image' => Yii::t('simialbi/kanban/model/board', 'Image'),
            'is_public' => Yii::t('simialbi/kanban/model/board', 'Is public'),
            'created_by' => Yii::t('simialbi/kanban/model/board', 'Created by'),
            'updated_by' => Yii::t('simialbi/kanban/model/board', 'Updated by'),
            'created_at' => Yii::t('simialbi/kanban/model/board', 'Created at'),
            'updated_at' => Yii::t('simialbi/kanban/model/board', 'Updated at')
        ];
    }

    /**
     * Find boards assigned to user
     * @param integer|string|null $id
     *
     * @return Board[]
     */
    public static function findByUserId($id = null)
    {
        if ($id === null) {
            $id = Yii::$app->user->id;
        }

        $query = static::find()
            ->alias('b')
            ->leftJoin(['ua' => '{{%kanban_board_user_assignment}}'], '{{ua}}.[[board_id]] = {{b}}.[[id]]')
            ->where(['{{b}}.[[is_public]]' => 1])
            ->orWhere(['{{ua}}.[[user_id]]' => $id]);

        return $query->all();
    }

    /**
     * {@inheritDoc}
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            static::getDb()->createCommand()->insert('{{%kanban_board_user_assignment}}', [
                'board_id' => $this->id,
                'user_id' => Yii::$app->user->id
            ])->execute();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Get visualisation. If set, this method return the image, otherwise generates a visualisation
     * @return string
     */
    public function getVisual()
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
     */
    public function getAuthor()
    {
        return call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $this->created_by);
    }

    /**
     * Get user last updated
     * @return mixed
     */
    public function getUpdater()
    {
        return call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $this->updated_by);
    }

    /**
     * Get users assigned to this task
     * @return array
     */
    public function getAssignees()
    {
        $assignees = [];

        $query = new Query();
        $query->from('{{%kanban_board_user_assignment}}')
            ->where(['board_id' => $this->id]);

        foreach ($query->all() as $item) {
            $assignees[] = call_user_func([Yii::$app->user->identityClass, 'findIdentity'], $item['user_id']);
        }

        return $assignees;
    }

    /**
     * Get associated buckets
     * @return \yii\db\ActiveQuery
     */
    public function getBuckets()
    {
        return $this->hasMany(Bucket::class, ['board_id' => 'id']);
    }

    /**
     * Get associated tasks
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['id' => 'id'])->via('buckets');
    }
}
