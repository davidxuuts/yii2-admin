<?php

namespace davidxu\admin\components;

use Yii;
use yii\caching\TagDependency;
use davidxu\admin\models\Menu;
use yii\helpers\ArrayHelper;
use Closure;
use yii\rbac\BaseManager;

/**
 * MenuHelper used to generate menu depend of user role.
 * Usage
 * 
 * ```
 * use davidxu\admin\components\MenuHelper;
 * use yii\bootstrap\Nav;
 *
 * echo Nav::widget([
 *    'items' => MenuHelper::getAssignedMenu(Yii::$app->user->id)
 * ]);
 * ```
 * 
 * To reformat returned, provide callback to method.
 * 
 * ```
 * $callback = function ($menu) {
 *    $data = eval($menu['data']);
 *    return [
 *        'label' => $menu['name'],
 *        'url' => [$menu['route']],
 *        'options' => $data,
 *        'items' => $menu['children']
 *        ]
 *    ]
 * }
 *
 * $items = MenuHelper::getAssignedMenu(Yii::$app->user->id, null, $callback);
 * ```
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class MenuHelper
{
    /**
     * Use to get assigned menu of user.
     * @param mixed $userId
     * @param integer|null $root
     * @param Closure|null $callback use to reformat output.
     * callback should have format like
     * 
     * ```
     * function ($menu) {
     *    return [
     *        'label' => $menu['name'],
     *        'url' => [$menu['route']],
     *        'options' => $data,
     *        'items' => $menu['children']
     *        ]
     *    ]
     * }
     * ```
     * @param boolean $refresh
     * @return array
     */
    public static function getAssignedMenu(mixed $userId, int $root = null, Closure $callback = null, bool $refresh = false)
    {
        $config = Configs::instance();

        /* @var $manager BaseManager */
        $manager = Configs::authManager();
        $menus = Menu::find()->asArray()->indexBy('id')->all();
        $key = [__METHOD__, $userId, $manager->defaultRoles];
        $cache = $config->cache;

        if ($refresh || $cache === null || ($assigned = $cache->get($key)) === false) {
            $routes = $filter1 = $filter2 = [];
            if ($userId !== null) {
                $userPermissions = $manager->getPermissionsByUser($userId);
                if (count($userPermissions) > 0) {
                    foreach ($userPermissions as $name => $value) {
                        if ($name[0] === '/') {
                            if (str_ends_with($name, '/*')) {
                                $name = substr($name, 0, -1);
                            }
                            $routes[] = $name;
                        }
                    }
                }
            }

            $defaultRoles = $manager->defaultRoles;
            if (count($defaultRoles) > 0) {
                foreach ($manager->defaultRoles as $role) {
                    $rolePermissions = $manager->getPermissionsByRole($role);
                    if (count($rolePermissions) > 0) {
                        foreach ($rolePermissions as $name => $value) {
                            if ($name[0] === '/') {
                                if (str_ends_with($name, '/*')) {
                                    $name = substr($name, 0, -1);
                                }
                                $routes[] = $name;
                            }
                        }
                    }
                }
            }
            $routes = array_unique($routes);
            sort($routes);
            $prefix = '\\';
            if (count($routes) > 0) {
                foreach ($routes as $route) {
                    if (!str_starts_with($route, $prefix)) {
                        if (str_ends_with($route, '/')) {
                            $prefix = $route;
                            $filter1[] = $route . '%';
                        } else {
                            $filter2[] = $route;
                        }
                    }
                }
            }
            $assigned = [];
            $query = Menu::find()->select(['id'])->asArray();
            if (count($filter2)) {
                $assigned = $query->where(['route' => $filter2])->column();
            }
            if (count($filter1)) {
                $query->where('route like :filter');
                foreach ($filter1 as $filter) {
                    $menu = $query->params([':filter' => $filter])->column();
                    $assigned = !empty($menu) ? ArrayHelper::merge($assigned, $query->params([':filter' => $filter])->column()) : $assigned;
//                    $assigned = array_merge($assigned, $query->params([':filter' => $filter])->column());
                }
            }
            $assigned = static::requiredParent($assigned, $menus);
            if ($cache !== null) {
                $cache->set($key, $assigned, $config->cacheDuration, new TagDependency([
                    'tags' => Configs::CACHE_TAG
                ]));
            }
        }
        $key = [__METHOD__, $assigned, $root];
        if ($refresh || $callback !== null || $cache === null || (($result = $cache->get($key)) === false)) {
            $result = static::normalizeMenu($assigned, $menus, $callback, $root);
            if ($cache !== null && $callback === null) {
                $cache->set($key, $result, $config->cacheDuration, new TagDependency([
                    'tags' => Configs::CACHE_TAG
                ]));
            }
        }
        return $result;
    }

    /**
     * Ensure all item menu has parent.
     * @param  array $assigned
     * @param  array $menus
     * @return array
     */
    private static function requiredParent($assigned, &$menus)
    {
        $l = count($assigned);
        for ($i = 0; $i < $l; $i++) {
            $id = $assigned[$i];
            $parent_id = $menus[$id]['parent'] ?? null;
            if ($parent_id !== null && (int)$parent_id !== 0 && !in_array($parent_id, $assigned)) {
                $assigned[$l++] = $parent_id;
            }
        }
        return $assigned;
    }

    /**
     * Parse route
     * @param  string $route
     * @return mixed
     */
    public static function parseRoute($route)
    {
//        Yii::info('route');
//        Yii::info($route);
//        Yii::info(!empty($route));
        if (!empty($route)) {
            $url = [];
            $r = explode('&', $route);
            $url[0] = $r[0];
            unset($r[0]);
            if (count($r) > 0) {
                foreach ($r as $part) {
                    $part = explode('=', $part);
                    $url[$part[0]] = $part[1] ?? '';
                }
            }
            return $url;
        }
        return '#';
    }

    /**
     * Normalize menu
     * @param  array $assigned
     * @param  array $menus
     * @param  Closure $callback
     * @param  integer $parent
     * @param  boolean $root
     * @return array
     */
    private static function normalizeMenu(&$assigned, &$menus, $callback, $parent = null, $root = true )
    {
        $children = [];
        $subitem = [];
        $result = [];
        $order = [];
        $item = [];

        // Recorre todas las opciones de menú asignadas al rol
        foreach($assigned as $id) {
            $menu = $menus[$id];
            // Obtiene todas las opciones en el nivel vigente
            if($menu['parent'] === $parent) {
                // Recupera todos los subitems
                $children = static::normalizeMenu( $assigned, $menus, $callback, $id, false );
                // Procesa los items del nivel vigente y anexa sus subitems
                if(!is_null($callback)) {
                    $item = call_user_func($callback, $menu, $root);
                } else {
                    // Only root levels should have items
                    if($root) {
                        $item = [
                            'label' => $menu['name'],
                            'url' => static::parseRoute($menu['route']),
                            'icon' => $menu['data'] ?? 'bi bi-circle',
                        ];
                        foreach($children as $child) {
                            $item['items'][] = $child;
                        }
                    } else {
                        if(is_null($menu['route'])) {
                            $subitem = [
                                '<div class="dropdown-divider"></div>',
                                '<div class="dropdown-header">'. $menu['name'] . '</div>',
                            ];
                            foreach($children as $child) {
                                $subitem[] = $child;
                            }
                        } else {
                            $item = [
                                'label' => $menu['name'],
                                'url' => static::parseRoute($menu['route']),
                                'icon' => $menu['data'] ?? 'bi bi-circle',
                            ];
                        }
                    }
                }

                if(count($item)) {
                    $result[] = $item;
                    $order[] = $menu['order'];
                    $item = [];
                } else if(count($subitem)) {
                    $result = array_merge($result, $subitem);
                }
            }
        }

        if(count($result) === count($order)) {
            array_multisort($order, $result);
        }

        return $result;
    }

    /**
     * Normalize menu
     * @param  array $assigned
     * @param  array $menus
     * @param  Closure $callback
     * @param  integer $parent
     * @return array
     */
    private static function normalizeMenuO(&$assigned, &$menus, $callback, $parent = null)
    {
        $result = [];
        $order = [];
        if (count($assigned) > 0) {
            foreach ($assigned as $id) {
                $menu = $menus[$id];
                if ($menu['parent'] === $parent) {
                    $menu['children'] = static::normalizeMenu($assigned, $menus, $callback, $id);
                    if ($callback !== null) {
                        $item = call_user_func($callback, $menu);
                    } else {
                        $url = static::parseRoute($menu['route']);
                        $url = !empty($url) || $url !== null || $url !== '' ? $url : '#';
                        $item = [
                            'label' => $menu['name'],
                            'url' => $url,
                            'icon' => $menu['data'] ?? 'bi bi-circle',
                        ];
                        if ($menu['children'] != []) {
                            $item['items'] = $menu['children'];
                        }
                    }
                    $result[] = $item;
                    $order[] = $menu['order'];
                }
            }
        }
        if ($result != []) {
            array_multisort($order, $result);
        }

        return $result;
    }
}
