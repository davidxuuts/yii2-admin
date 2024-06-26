<?php

namespace davidxu\admin\models;

use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\rbac\Rule;
use davidxu\admin\components\Configs;

/**
 * BizRule
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class BizRule extends Model
{
    /**
     * @var ?string name of the rule
     */
    public ?string $name = null;

    /**
     * @var integer UNIX timestamp representing the rule creation time
     */
    public int $createdAt = 0;

    /**
     * @var integer UNIX timestamp representing the rule updating time
     */
    public int $updatedAt = 0;

    /**
     * @var ?string Rule classname.
     */
    public ?string $className = null;

    /**
     * @var ?Rule
     */
    private ?Rule $_item;

    /**
     * Initialize object
     * @param ?Rule $item
     * @param array $config
     */
    public function __construct($item = null, array $config = [])
    {
        $this->_item = $item;
        if ($item !== null) {
            $this->name = $item->name;
            $this->className = get_class($item);
        }
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name', 'className'], 'required'],
            [['className'], 'string'],
            [['className'], 'classExists']
        ];
    }

    /**
     * Validate class exists
     */
    public function classExists(): void
    {
        if (!class_exists($this->className)) {
            $message = Yii::t('rbac-admin', "Unknown class '{class}'", ['class' => $this->className]);
            $this->addError('className', $message);
            return;
        }
        if (!is_subclass_of($this->className, Rule::class)) {
            $message = Yii::t('rbac-admin', "'{class}' must extend from 'yii\rbac\Rule' or its child class", [
                    'class' => $this->className]);
            $this->addError('className', $message);
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('rbac-admin', 'Name'),
            'className' => Yii::t('rbac-admin', 'Class Name'),
        ];
    }

    /**
     * Check if new record.
     * @return boolean
     */
    public function getIsNewRecord(): bool
    {
        return $this->_item === null;
    }

    /**
     * Find model by id
     * @param string $id
     * @return null|static
     * @throws InvalidConfigException
     */
    public static function find(string $id): ?BizRule
    {
        $item = Configs::authManager()->getRule($id);
        if ($item !== null) {
            return new static($item);
        }

        return null;
    }

    /**
     * Save model to authManager
     * @return boolean
     * @throws Exception
     */
    public function save(): bool
    {
        $oldName = null;
        if ($this->validate()) {
            $manager = Configs::authManager();
            $class = $this->className;
            if ($this->_item === null) {
                $this->_item = new $class();
                $isNew = true;
            } else {
                $isNew = false;
                $oldName = $this->_item->name;
            }
            $this->_item->name = $this->name;

            if ($isNew) {
                $manager->add($this->_item);
            } else {
                $manager->update($oldName, $this->_item);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get item
     * @return Rule|null
     */
    public function getItem(): ?Rule
    {
        return $this->_item;
    }
}
