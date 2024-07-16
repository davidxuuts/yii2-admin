<?php

namespace davidxu\admin\components;

use davidxu\admin\BaseObject;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\rbac\ManagerInterface;

/**
 * Configs
 * Used to configure some values. To set config you can use [[\yii\base\Application::$params]]
 *
 * ```
 * return [
 *
 *     'davidxu.admin.configs' => [
 *         'db' => 'customDb',
 *         'menuTable' => '{{%admin_menu}}',
 *         'cache' => [
 *             'class' => 'yii\caching\DbCache',
 *             'db' => ['dsn' => 'sqlite:@runtime/admin-cache.db'],
 *         ],
 *     ]
 * ];
 * ```
 *
 * or use [[\Yii::$container]]
 *
 * ```
 * Yii::$container->set('davidxu\admin\components\Configs',[
 *     'db' => 'customDb',
 *     'menuTable' => 'admin_menu',
 * ]);
 * ```
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */

class Configs extends BaseObject
{
    const CACHE_TAG = 'davidxu.admin';

    /**
     * @var string config params
     */
    public static string $configParams = 'davidxu.admin.configs';

    /**
     * @var ManagerInterface|string .
     */
    public ManagerInterface|string $authManager = 'authManager';

    /**
     * @var Connection|string Database connection.
     */
    public Connection|string $db = 'db';

    /**
     * @var Connection|string Database connection.
     */
    public Connection|string $userDb = 'db';

    /**
     * @var Cache|string Cache component.
     */
    public Cache|string $cache = 'cache';

    /**
     * @var integer Cache duration. Default to an hour.
     */
    public int $cacheDuration = 3600;

    /**
     * @var string Menu table name.
     */
    public string $menuTable = '{{%menu}}';

    /**
     * @var string Item table name.
     */
    public string $itemTable = '{{%auth_item}}';

    /**
     * @var string User table name.
     */
    public string $userTable = '{{%user}}';

    /**
     * @var string AuthItemChild table name.
     */
    public string $itemChildTable = '{{%auth_item_child}}';

    /**
     * @var string AuthAssignment table name.
     */
    public string $assignmentTable = '{{%auth_assignment}}';

    /**
     * @var string AuthRule table name.
     */
    public string $ruleTable = '{{%auth_rule}}';

    /**
     * @return string
     * @throws Exception
     */
    public static function itemTable(): string
    {
        return static::instance()->itemTable;
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function ruleTable(): string
    {
        return static::instance()->ruleTable;
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function itemChildTable(): string
    {
        return static::instance()->itemChildTable;
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function assignmentTable(): string
    {
        return static::instance()->assignmentTable;
    }

    /**
     * @var integer Default status user signup. 10 mean active.
     */
    public int $defaultUserStatus = 10;

    /**
     * @var integer Number of user role(s).
     */
    public int $userRolePageSize = 100;

    /**
     * @var boolean If true, then AccessControl only check if route is registered.
     */
    public bool $onlyRegisteredRoute = false;

    /**
     * @var boolean If false, then AccessControl will check without Rule.
     */
    public bool $strict = true;

    /**
     * @var array
     */
    public array $options = [];

    /**
     * @var array|false Used for multiple application
     * ```php
     * [
     *     'frontend' => [
     *         '@common/config/main.php',
     *         '@common/config/main-local.php',
     *         '@frontend/config/main.php',
     *         '@frontend/config/main-local.php',
     *     ],
     *     'backend' => [
     *         '@common/config/main.php',
     *         '@common/config/main-local.php',
     *         '@backend/config/main.php',
     *         '@backend/config/main-local.php',
     *     ],
     * ]
     * ```     *
     */
    public array|false $advanced = false;

    /**
     * @var object|null Instance of self
     */
    private static null|object $_instance = null;
    private static array $_classes = [
        'db' => 'yii\db\Connection',
        'userDb' => 'yii\db\Connection',
        'cache' => 'yii\caching\Cache',
        'authManager' => 'yii\rbac\ManagerInterface',
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        foreach (self::$_classes as $key => $class) {
            try {
                $this->{$key} = empty($this->{$key}) ? null : Instance::ensure($this->{$key}, $class);
            } catch (Exception $exc) {
                $this->{$key} = null;
                Yii::error($exc->getMessage());
            }
        }
    }

    /**
     * Create instance of self
     * @return object
     * @throws InvalidConfigException
     * @throws Exception
     */
    public static function instance(): object
    {
        if (self::$_instance === null) {
            $type = ArrayHelper::getValue(Yii::$app->params, self::$configParams, []);
            if (is_array($type) && !isset($type['class'])) {
                $type['class'] = static::class;
            }

            self::$_instance = Yii::createObject($type);
            return self::$_instance;
        }
        return self::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|void|null
     * @throws InvalidConfigException
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = static::instance();
        if ($instance->hasProperty($name)) {
            return $instance->$name;
        } else {
            if (count($arguments)) {
                $instance->options[$name] = reset($arguments);
            } else {
                return array_key_exists($name, $instance->options) ? $instance->options[$name] : null;
            }
        }
    }

    /**
     * @return string|Connection
     * @throws InvalidConfigException
     */
    public static function db(): string|Connection
    {
        return static::instance()->db;
    }

    /**
     * @return string|Connection
     * @throws InvalidConfigException
     */
    public static function userDb(): string|Connection
    {
        return static::instance()->userDb;
    }

    /**
     * @return Cache|string
     * @throws InvalidConfigException
     */
    public static function cache(): Cache|string
    {
        return static::instance()->cache;
    }

    /**
     * @return ManagerInterface|string
     * @throws InvalidConfigException
     */
    public static function authManager(): ManagerInterface|string
    {
        return static::instance()->authManager;
    }

    /**
     * @return integer
     * @throws InvalidConfigException
     */
    public static function cacheDuration(): int
    {
        return static::instance()->cacheDuration;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public static function menuTable(): string
    {
        return static::instance()->menuTable;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public static function userTable(): string
    {
        return static::instance()->userTable;
    }

    /**
     * @return int|string
     * @throws InvalidConfigException
     */
    public static function defaultUserStatus(): int|string
    {
        return static::instance()->defaultUserStatus;
    }

    /**
     * @return boolean
     * @throws InvalidConfigException
     */
    public static function onlyRegisteredRoute(): bool
    {
        return static::instance()->onlyRegisteredRoute;
    }

    /**
     * @return boolean
     * @throws InvalidConfigException
     */
    public static function strict(): bool
    {
        return static::instance()->strict;
    }

    /**
     * @return int
     * @throws InvalidConfigException
     */
    public static function userRolePageSize(): int
    {
        return static::instance()->userRolePageSize;
    }
}
