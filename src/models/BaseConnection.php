<?php

namespace simialbi\yii2\kanban\models;

use simialbi\yii2\kanban\enums\ConnectionTypeEnum;
use simialbi\yii2\kanban\Module;
use simialbi\yii2\models\UserInterface;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

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
 * @property-read UserInterface $user
 * @property-read string $typeLabel
 * @property-read Bucket $bucket
 */
class BaseConnection extends ActiveRecord implements ConnectionInterface
{

    /**
     * {@inheritDoc}
     */
    public static function tableName(): string
    {
        return '{{%kanban__connection}}';
    }

    /**
     * Find all buckets the user has access to
     * @return array
     */
    public static function findBuckets(): array
    {
        return Bucket::getSelect2Options();
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'bucket_id', 'type'], 'integer'],
            ['user_id', 'string', 'max' => 64],
            ['options', 'safe'],
            [
                'bucket_id',
                'exist',
                'targetRelation' => 'bucket',
            ],
            [['bucket_id'], 'unique', 'targetAttribute' => ['type', 'user_id']],
            [['user_id', 'bucket_id', 'type'], 'required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'user_id' => Yii::t('simialbi/kanban/model/connection', 'User'),
            'bucket_id' => Yii::t('simialbi/kanban/model/connection', 'Bucket'),
            'type' => Yii::t('simialbi/kanban/model/connection', 'Type'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // pack options
        $arr = [];
        foreach ($this->optionAttributes() as $attribute) {
            $arr[$attribute] = $this->{$attribute};
        }
        $this->options = $arr;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function afterFind(): void
    {
        // unpack options
        foreach ($this->optionAttributes() as $attribute) {
            if (isset($this->options[$attribute])) {
                $this->{$attribute} = $this->options[$attribute];
            }
        }

        parent::afterFind();
    }

    /**
     * {@inheritDoc}
     */
    public function optionAttributes(): array
    {
        return [];
    }

    /**
     * Returns the type label
     * @return string
     */
    public function getTypeLabel(): string
    {
        return ConnectionTypeEnum::tryFrom($this->type)->getLabel();
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     *
     * @deprecated
     */
    public function getAddress(): UserInterface
    {
        return $this->getUser();
    }

    /**
     * Get author
     * @return UserInterface
     * @throws \Exception
     */
    public function getUser(): UserInterface
    {
        return ArrayHelper::getValue(Module::getInstance()->users, $this->user_id);
    }

    /**
     * Get related bucket
     * @return ActiveQuery
     */
    public function getBucket(): ActiveQuery
    {
        return $this->hasOne(Bucket::class, ['id' => 'bucket_id']);
    }
}
