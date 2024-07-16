<?php

namespace davidxu\admin\models;

use davidxu\admin\components\Configs;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\rbac\Rule as RbacRule;

/**
 * This is the model class for table "{{%auth_rule}}".
 *
 * @property int $id ID
 * @property string $name Rule name
 * @property string $class_name Rule class name
 * @property resource|null $data Data
 * @property int|null $created_at Created at
 * @property int|null $updated_at Updated at
 *
 * @property Item[] $items
 */
class Rule extends ActiveRecord
{
    public RbacRule|string|null $class_name = null;

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public static function tableName(): string
    {
        return Configs::instance()->ruleTable;
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['class_name', 'name'], 'required'],
            [['name'], 'classAvailable'],
            [['name'], 'unique'],
            [['name'], 'string', 'max' => 64],
            [['class_name'], 'string', 'max' => 255],
            [['data'], 'string'],
            ['class_name', 'classExists'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('rbac-admin', 'Name'),
            'class_name' => Yii::t('rbac-admin', 'Class Name'),
            'data' => Yii::t('rbac-admin', 'Data'),
            'created_at' => Yii::t('rbac-admin', 'Created at'),
            'updated_at' => Yii::t('rbac-admin', 'Updated at'),
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function classAvailable(): void
    {
        $class = Yii::createObject($this->class_name);
        if (! ($class instanceof RbacRule)) {
            $this->addError('name', Yii::t('rbac-admin','class is invalid'));
        }
        if ($class->name == '' || !isset($class->name)) {
            $this->addError('name', Yii::t('rbac-admin','class name is empty'));
        }
    }


    /**
     * Validate class exists
     */
    public function classExists(): void
    {
        if (!class_exists($this->class_name)) {
            $message = Yii::t('rbac-admin', "Unknown class '{class}'", ['class' => $this->class_name]);
            $this->addError('class_name', $message);
            return;
        }
        if (!is_subclass_of($this->class_name, RbacRule::class)) {
            $message = Yii::t('rbac-admin', "'{class}' must extend from 'yii\rbac\Rule' or its child class", [
                'class' => $this->class_name]);
            $this->addError('class_name', $message);
        }
    }

    /**
     * Gets query for [[Items]].
     *
     * @return ActiveQuery
     */
    public function getItems(): ActiveQuery
    {
        return $this->hasMany(Item::class, ['rule_name' => 'name']);
    }

    /**
     * Gets class name
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->data && unserialize($this->data) ? get_class(unserialize($this->data)) : '';
    }
}
