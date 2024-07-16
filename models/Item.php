<?php

namespace davidxu\admin\models;


use davidxu\admin\components\Configs;
use davidxu\admin\components\Helper;
use davidxu\admin\controllers\AssignmentController;
use davidxu\admin\Module;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\rbac\Item as RbacItem;
use yii\rbac\Rule as RbacRule;

/**
 * This is the model class for table "auth_item".
 *
 * @property string $name Name
 * @property int $type Type
 * @property string|null $description Description
 * @property string|null $rule_name Rule name
 * @property resource|null $data data
 * @property int|null $created_at Created at
 * @property int|null $updated_at Updated at
 *
 * @property Assignment[] $authAssignments
 * @property Item[] $children
 * @property Item[] $parents
 * @property Rule $ruleName
 */
class Item extends ActiveRecord
{
    private RbacItem|Item|null $_item;

    /**
     * Initialize object
     * @param Item  $item
     * @param array $config
     */
    public function __construct($item = null, array $config = [])
    {
        $this->_item = $item;
        if ($item !== null) {
            $this->name = $item->name;
            $this->type = $item->type;
            $this->description = $item->description;
            $this->rule_name = $item->rule_name ?? null;
            $this->data = $item->data === null ? null : Json::encode($item->data);
        }
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public static function tableName(): string
    {
        return Configs::instance()->itemTable;
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
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name', 'type'], 'required'],
            [['type'], 'integer'],
            [['type'], 'in', 'range' => [RbacItem::TYPE_ROLE, RbacItem::TYPE_PERMISSION]],
            [['description', 'data'], 'string'],
            [['name', 'rule_name'], 'string', 'max' => 64],
            [['name'], 'unique'],
//            [
//                ['rule_name'], 'exist', 'skipOnError' => true,
//                'targetClass' => Rule::class,
//                'targetAttribute' => ['rule_name' => 'name']
//            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('rbac-admin', 'Name'),
            'type' => Yii::t('rbac-admin', 'Type'),
            'description' => Yii::t('rbac-admin', 'Description'),
            'rule_name' => Yii::t('rbac-admin', 'Rule name'),
            'data' => Yii::t('rbac-admin', 'Data'),
            'created_at' => Yii::t('rbac-admin', 'Created at'),
            'updated_at' => Yii::t('rbac-admin', 'Updated at'),
        ];
    }

    /**
     * Check for rule
     * @throws InvalidConfigException
     */
    public function checkRule(): void
    {
        $name = $this->rule_name;
        if (!Configs::authManager()->getRule($name)) {
            try {
                $rule = Yii::createObject($name);
                if ($rule instanceof RbacRule) {
                    $rule->name = $name;
                    Configs::authManager()->add($rule);
                } else {
                    $this->addError('rule_name', Yii::t('rbac-admin', 'Invalid rule "{value}"', ['value' => $name]));
                }
            } catch (Exception) {
                $this->addError('rule_name', Yii::t('rbac-admin', 'Rule "{value}" does not exists', ['value' => $name]));
            }
        }
    }

    /**
     * Gets query for [[AuthAssignments]]
     *
     * @return ActiveQuery
     */
    public function getAuthAssignments(): ActiveQuery
    {
        return $this->hasMany(Assignment::class, ['item_name' => 'name']);
    }

    /**
     * Gets query for [[RuleName]]
     *
     * @return ActiveQuery
     */
    public function getRuleName(): ActiveQuery
    {
        return $this->hasOne(Rule::class, ['name' => 'rule_name']);
    }

    /**
     * Gets query for [[Children]].
     *
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getChildren(): ActiveQuery
    {
        $itemChildTable = Configs::instance()->itemChildTable;
        return $this->hasMany(Item::class, ['name' => 'child'])
            ->viaTable($itemChildTable, ['parent' => 'name']);
    }

    /**
     * Gets query for [[Parents]].
     *
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getParents(): ActiveQuery
    {
        $itemChildTable = Configs::instance()->itemChildTable;
        return $this->hasMany(Item::class, ['name' => 'parent'])
            ->viaTable($itemChildTable, ['child' => 'name']);
    }

    /**
     * Adds an item as a child of another item.
     * @param array $items
     * @return int
     * @throws InvalidConfigException
     */
    public function addChildren(array $items): int
    {
        $manager = Configs::authManager();
        $success = 0;
//        if ($this->_item) {
            foreach ($items as $name) {
                $child = $manager->getPermission($name);
                if ($this->type == RbacItem::TYPE_ROLE && $child === null) {
                    $child = $manager->getRole($name);
                }
                try {
                    $manager->addChild($this->_item, $child);
                    $success++;
                } catch (Exception $exc) {
                    Yii::error($exc->getMessage(), __METHOD__);
                }
            }
//        }
        if ($success > 0) {
            Helper::invalidate();
        }
        return $success;
    }

    /**
     * Remove an item as a child of another item.
     * @param array $items
     * @return int
     * @throws InvalidConfigException
     */
    public function removeChildren(array $items): int
    {
        $manager = Configs::authManager();
        $success = 0;
        if ($this->_item !== null) {
            foreach ($items as $name) {
                $child = $manager->getPermission($name);
                if ($this->type == RbacItem::TYPE_ROLE && $child === null) {
                    $child = $manager->getRole($name);
                }
                try {
                    $manager->removeChild($this->_item, $child);
                    $success++;
                } catch (Exception $exc) {
                    Yii::error($exc->getMessage(), __METHOD__);
                }
            }
        }
        if ($success > 0) {
            Helper::invalidate();
        }
        return $success;
    }

    /**
     * Get items
     * @return array
     * @throws InvalidConfigException
     */
    public function getItems(): array
    {
        $manager = Configs::authManager();
        $advanced = Configs::instance()->advanced;
        $available = [];
        if ($this->type == RbacItem::TYPE_ROLE) {
            foreach (array_keys($manager->getRoles()) as $name) {
                $available[$name] = 'role';
            }
        }
        foreach (array_keys($manager->getPermissions()) as $name) {
            $available[$name] = $name[0] == '/' || $advanced && $name[0] == '@' ? 'route' : 'permission';
        }

        $assigned = [];
        foreach ($manager->getChildren($this->_item->name) as $item) {
            $assigned[$item->name] = $item->type == 1 ? 'role' : ($item->name[0] == '/' || $advanced && $item->name[0] == '@'
                ? 'route' : 'permission');
            unset($available[$item->name]);
        }
        unset($available[$this->name]);
        ksort($available);
        ksort($assigned);
        return [
            'available' => $available,
            'assigned' => $assigned,
        ];
    }

//    /**
//     * @throws InvalidConfigException
//     */
//    public function getUsers(): array
//    {
//        $module = Yii::$app->controller->module;
//        if (!$module instanceof Module) {
//            return [];
//        }
//        $ctrl = $module->createController('assignment');
//        $result = [];
//        if ($ctrl && $ctrl[0] instanceof AssignmentController) {
//            $ctrl = $ctrl[0];
//            $class = $ctrl->userClassName;
//            $idField = $ctrl->idField;
//            $usernameField = $ctrl->usernameField;
//
//            $manager = Configs::authManager();
//            $ids = $manager->getUserIdsByRole($this->name);
//
//            $provider = new ArrayDataProvider([
//                'allModels' => $ids,
//                'pagination' => [
//                    'pageSize' => Configs::userRolePageSize(),
//                ]
//            ]);
//            $users = $class::find()
//                ->select(['id' => $idField, 'username' => $usernameField])
//                ->where([$idField => $provider->getModels()])
//                ->asArray()->all();
//
//            $route = '/' . $ctrl->uniqueId . '/view';
//            foreach ($users as &$row) {
//                $row['link'] = Url::to([$route, 'id' => $row['id']]);
//            }
//            $result['users'] = $users;
//            $currentPage = $provider->pagination->getPage();
//            $pageCount = $provider->pagination->getPageCount();
//            if ($pageCount > 0) {
//                $result['first'] = 0;
//                $result['last'] = $pageCount - 1;
//                if ($currentPage > 0) {
//                    $result['prev'] = $currentPage - 1;
//                }
//                if ($currentPage < $pageCount - 1) {
//                    $result['next'] = $currentPage + 1;
//                }
//            }
//        }
//        return $result;
//    }
}