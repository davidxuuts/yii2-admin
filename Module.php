<?php

namespace davidxu\admin;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;
use yii\web\Application;

/**
 * GUI manager for RBAC.
 *
 * Use [[\yii\base\Module::$controllerMap]] to change property of controller.
 * To change a listed menu, use property [[$menus]].
 *
 * ```
 * 'layout' => 'left-menu', // default to null mean use application layout.
 * 'controllerMap' => [
 *     'assignment' => [
 *         'class' => 'davidxu\admin\controllers\AssignmentController',
 *         'userClassName' => 'app\models\User',
 *         'idField' => 'id'
 *     ]
 * ],
 * 'menus' => [
 *     'assignment' => [
 *         'label' => 'Grand Access' // change label
 *     ],
 *     'route' => null, // disable menu
 * ],
 * ```
 *
 * @property string $mainLayout Main layout using for module. Default to layout of parent module.
 * Its used when `layout` set to 'left-menu', 'right-menu' or 'top-menu'.
 * @property array $menus List available menu of module.
 * It generated by module items .
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $defaultRoute = 'role';
    /**
     * @var ?array Nav bar items.
     */
    public ?array $navbar = null;
    /**
     * @var string Main layout using for module. Default to layout of parent module.
     * Its used when `layout` set to 'left-menu', 'right-menu' or 'top-menu'.
     */
    public string $mainLayout = '@davidxu/admin/views/layouts/main.php';
    /**
     * @var array
     * @see [[menus]]
     */
    private array $_menus = [];
    /**
     * @var array
     * @see [[menus]]
     */
    private array $_coreItems = [
        'user' => 'Users',
        'assignment' => 'Assignments',
        'role' => 'Roles',
        'permission' => 'Permissions',
        'route' => 'Routes',
        'rule' => 'Rules',
        'menu' => 'Menus',
    ];
    /**
     * @var ?array
     * @see [[items]]
     */
    private ?array $_normalizeMenus = null;

    /**
     * @var ?string Default url for breadcrumb
     */
    public ?string $defaultUrl = null;

    /**
     * @var ?string Default url label for breadcrumb
     */
    public ?string $defaultUrlLabel = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        if (!isset(Yii::$app->i18n->translations['rbac-admin'])) {
            Yii::$app->i18n->translations['rbac-admin'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@davidxu/admin/messages',
            ];
        }

        //user did not define the Navbar?
        if ($this->navbar === null && Yii::$app instanceof Application) {
            $this->navbar = [
                ['label' => Yii::t('rbac-admin', 'Help'), 'url' => ['default/index']],
                ['label' => Yii::t('rbac-admin', 'Application'), 'url' => Yii::$app->homeUrl],
            ];
        }
        if (class_exists('yii\jui\JuiAsset')) {
            Yii::$container->set('davidxu\admin\AutocompleteAsset', 'yii\jui\JuiAsset');
        }
    }

    /**
     * Get available menu.
     * @return array
     * @throws InvalidConfigException
     */
    public function getMenus(): array
    {
        if ($this->_normalizeMenus === null) {
            $mid = '/' . $this->getUniqueId() . '/';
            // resolve core menus
            $this->_normalizeMenus = [];

            $config = components\Configs::instance();
            $conditions = [
                'user' => $config->db && $config->db->schema->getTableSchema($config->userTable),
                'assignment' => ($userClass = Yii::$app->getUser()->identityClass) && is_subclass_of($userClass, 'yii\db\BaseActiveRecord'),
                'menu' => $config->db && $config->db->schema->getTableSchema($config->menuTable),
            ];
            foreach ($this->_coreItems as $id => $label) {
                if (!isset($conditions[$id]) || $conditions[$id]) {
                    $this->_normalizeMenus[$id] = ['label' => Yii::t('rbac-admin', $label), 'url' => [$mid . $id]];
                }
            }
            foreach (array_keys($this->controllerMap) as $id) {
                $this->_normalizeMenus[$id] = ['label' => Yii::t('rbac-admin', Inflector::humanize($id)), 'url' => [$mid . $id]];
            }

            // user configure menus
            foreach ($this->_menus as $id => $value) {
                if (empty($value)) {
                    unset($this->_normalizeMenus[$id]);
                    continue;
                }
                if (is_string($value)) {
                    $value = ['label' => $value];
                }
                $this->_normalizeMenus[$id] = isset($this->_normalizeMenus[$id]) ? array_merge($this->_normalizeMenus[$id], $value)
                : $value;
                if (!isset($this->_normalizeMenus[$id]['url'])) {
                    $this->_normalizeMenus[$id]['url'] = [$mid . $id];
                }
            }
        }
        return $this->_normalizeMenus;
    }

    /**
     * Set or add an available menu.
     * @param array $menus
     */
    public function setMenus(array $menus): void
    {
        $this->_menus = array_merge($this->_menus, $menus);
        $this->_normalizeMenus = null;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (parent::beforeAction($action)) {
            $view = $action->controller->getView();

            $view->params['breadcrumbs'][] = [
                'label' => ($this->defaultUrlLabel ?: Yii::t('rbac-admin', 'Admin')),
                'url' => ['/' . ($this->defaultUrl ?: $this->uniqueId)],
            ];
            return true;
        }
        return false;
    }
}
