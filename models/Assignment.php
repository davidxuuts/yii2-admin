<?php

namespace davidxu\admin\models;

//use davidxu\admin\BaseObject;
use davidxu\admin\components\Configs;
use davidxu\admin\components\Helper;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
//use yii\base\Model;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
//use davidxu\admin\models\Item;
use yii\rbac\Item as RbacItem;
use yii\web\IdentityInterface;

/**
 * Description of Assignment
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @author David XU <david.xu.uts@163.com>
 * @since 2.5
 *
 * @property string|int|array|null $id ID
 * @property ?string $item_name Item name
 * @property ?string $user_id User ID
 * @property integer|null $created_at Created at
 *
 * @property Item $item
 */
class Assignment extends ActiveRecord
{
    /**
     * @var IdentityInterface User
     */
    public mixed $user;

    public string|int|array|null $id = null;
    public ?string $username = null;
//    public ?string $item_name = null;
//
//    /**
//     * @inheritdoc
//     */
//    public function __construct($id, $user = null, $config = array())
//    {
//        $this->id = $id;
//        $this->user = $user;
//        parent::__construct($config);
//    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public static function tableName(): string
    {
//        $authManager = Configs::authManager();
        return Configs::instance()->assignmentTable;
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
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
        ];
    }

    /**
     * Grands a roles from a user.
     * @param array $items
     * @return integer number of successful grand
     * @throws InvalidConfigException
     */
    public function assign(array $items): int
    {
        $manager = Configs::authManager();
        $success = 0;
        if (count($items)) {
            foreach ($items as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ?: $manager->getPermission($name);
                    $manager->assign($item, $this->id);
                    $success++;
                } catch (Exception $exc) {
                    Yii::error($exc->getMessage(), __METHOD__);
                }
            }
            Helper::invalidate();
        }
//        Helper::invalidate();
        return $success;
    }

    /**
     * Revokes a roles from a user.
     * @param array $items
     * @return integer number of successful revokes
     * @throws InvalidConfigException
     */
    public function revoke(array $items): int
    {
        $manager = Configs::authManager();
        $success = 0;
        if (count($items)) {
            foreach ($items as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ?: $manager->getPermission($name);
                    $manager->revoke($item, $this->id);
                    $success++;
                } catch (Exception $exc) {
                    Yii::error($exc->getMessage(), __METHOD__);
                }
            }
        }
        Helper::invalidate();
        return $success;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['item_name', 'user_id'], 'required'],
            [['user_id'], 'integer'],
            [['item_name'], 'string', 'max' => 64],
            [['item_name', 'user_id'], 'unique', 'targetAttribute' => ['item_name', 'user_id']],
            [
                ['item_name'], 'exist', 'skipOnError' => true,
                'targetClass' => Item::class,
                'targetAttribute' => ['item_name' => 'name']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'item_name' => Yii::t('srbac', 'Name'),
            'user_id' => Yii::t('srbac', 'User'),
            'created_at' => Yii::t('srbac', 'Created at'),
        ];
    }

    /**
     * Get all available and assigned roles/permission
     * @return array
     * @throws InvalidConfigException
     */
    public function getItems(): array
    {
        $manager = Configs::authManager();
        $available = [];
        foreach (array_keys($manager->getRoles()) as $name) {
            $available[$name] = 'role';
        }

        foreach (array_keys($manager->getPermissions()) as $name) {
            if ($name[0] != '/') {
                $available[$name] = 'permission';
            }
        }

        $assigned = [];
        foreach ($manager->getAssignments($this->id) as $item) {
            $assigned[$item->roleName] = $available[$item->roleName];
            unset($available[$item->roleName]);
        }

        ksort($available);
        ksort($assigned);
        return [
            'available' => $available,
            'assigned' => $assigned,
        ];
    }

    /**
     * Gets query for [[Item]]
     * @return ActiveQuery
     */
    public function getItem(): ActiveQuery
    {
        return $this->hasOne(Item::class, ['name' => 'item_name']);
    }

//    /**
//     * Find a role
//     * @param string|int $id
//     * @return null|self
//     * @throws InvalidConfigException
//     */
//    public static function find($id): ?Assignment
//    {
//        $item = Configs::authManager()->getRole($id);
//        if ($item !== null) {
//            return new self((array)$item);
//        }
//
//        return null;
//    }

//    /**
//     * @inheritdoc
//     * @param $name
//     * @return mixed|void
//     */
//    public function __get($name)
//    {
//        if ($this->user) {
//            return $this->user->$name;
//        }
//    }
}
