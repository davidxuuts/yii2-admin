<?php

namespace davidxu\admin\components;

use davidxu\admin\models\Route;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;
use yii\web\User;

/**
 * Description of Helper
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 2.3
 */
class Helper
{
    private static array $_userRoutes = [];
    private static ?array $_defaultRoutes = [];
    private static ?array $_routes = [];

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public static function getRegisteredRoutes(): array
    {
        if (self::$_routes === null) {
            self::$_routes = [];
            $manager = Configs::authManager();
            foreach ($manager->getPermissions() as $item) {
                if ($item->name[0] === '/') {
                    self::$_routes[$item->name] = $item->name;
                }
            }
        }
        return self::$_routes;
    }

    /**
     * Get assigned routes by default roles
     * @return array|null
     * @throws InvalidConfigException
     */
    protected static function getDefaultRoutes(): ?array
    {
        if (self::$_defaultRoutes === null) {
            $manager = Configs::authManager();
            $roles = $manager->defaultRoles;
            $cache = Configs::cache();
            if ($cache && ($routes = $cache->get($roles)) !== false) {
                self::$_defaultRoutes = $routes;
            } else {
                $permissions = self::$_defaultRoutes = [];
                foreach ($roles as $role) {
                    $permissions = array_merge($permissions, $manager->getPermissionsByRole($role));
                }
                foreach ($permissions as $item) {
                    if ($item->name[0] === '/') {
                        self::$_defaultRoutes[$item->name] = true;
                    }
                }
                if ($cache) {
                    $cache->set($roles, self::$_defaultRoutes, Configs::cacheDuration(), new TagDependency([
                        'tags' => Configs::CACHE_TAG,
                    ]));
                }
            }
        }
        return self::$_defaultRoutes;
    }

    /**
     * Get assigned routes of user.
     * @param integer|string $userId
     * @return array
     * @throws InvalidConfigException
     */
    public static function getRoutesByUser(int|string $userId): array
    {
        if (!isset(self::$_userRoutes[$userId])) {
            $cache = Configs::cache();
            if ($cache && ($routes = $cache->get([__METHOD__, $userId])) !== false) {
                self::$_userRoutes[$userId] = $routes;
            } else {
                $routes = static::getDefaultRoutes();
                $manager = Configs::authManager();
                foreach ($manager->getPermissionsByUser($userId) as $item) {
                    if ($item->name[0] === '/') {
                        $routes[$item->name] = true;
                    }
                }
                self::$_userRoutes[$userId] = $routes;
                if ($cache) {
                    $cache->set([__METHOD__, $userId], $routes, Configs::cacheDuration(), new TagDependency([
                        'tags' => Configs::CACHE_TAG,
                    ]));
                }
            }
        }
        return self::$_userRoutes[$userId];
    }

    /**
     * Check access route for user.
     * @param array|string $route
     * @param array $params
     * @param integer|User|null $user
     * @return boolean
     * @throws InvalidConfigException
     */
    public static function checkRoute(array|string $route, array $params = [], User|int $user = null): bool
    {
        $config = Configs::instance();
        $r = static::normalizeRoute($route, $config->advanced);
        if ($config->onlyRegisteredRoute && !isset(static::getRegisteredRoutes()[$r])) {
            return true;
        }

        if ($user === null) {
            $user = Yii::$app->getUser();
        }
        $userId = $user instanceof User ? $user->getId() : $user;

        if ($config->strict) {
            if ($user->can($r, $params)) {
                return true;
            }
            while (($pos = strrpos($r, '/')) > 0) {
                $r = substr($r, 0, $pos);
                if ($user->can($r . '/*', $params)) {
                    return true;
                }
            }
            return $user->can('/*', $params);
        } else {
            $routes = static::getRoutesByUser($userId);
            if (isset($routes[$r])) {
                return true;
            }
            while (($pos = strrpos($r, '/')) > 0) {
                $r = substr($r, 0, $pos);
                if (isset($routes[$r . '/*'])) {
                    return true;
                }
            }
            return isset($routes['/*']);
        }
    }

    /**
     * Normalize route
     * @param string $route    Plain route string
     * @param boolean|array $advanced Array containing the advanced configuration. Defaults to false.
     * @return string            Normalized route string
     */
    protected static function normalizeRoute(string $route, bool|array $advanced = false): string
    {
        if ($route === '') {
            $normalized = '/' . Yii::$app->controller->getRoute();
        } elseif (strncmp($route, '/', 1) === 0) {
            $normalized = $route;
        } elseif (!str_contains($route, '/')) {
            $normalized = '/' . Yii::$app->controller->getUniqueId() . '/' . $route;
        } elseif (($mid = Yii::$app->controller->module->getUniqueId()) !== '') {
            $normalized = '/' . $mid . '/' . $route;
        } else {
            $normalized = '/' . $route;
        }
        // Prefix @app-id to route.
        if ($advanced) {
            $normalized = Route::PREFIX_ADVANCED . Yii::$app->id . $normalized;
        }
        return $normalized;
    }

    /**
     * Filter menu items
     * @param array $items
     * @param integer|User|null $user
     * @return array
     * @throws InvalidConfigException
     */
    public static function filter(array $items, User|int $user = null): array
    {
        if ($user === null) {
            $user = Yii::$app->getUser();
        }
        return static::filterRecursive($items, $user);
    }

    /**
     * Filter menu recursive
     * @param array $items
     * @param integer|User $user
     * @return array
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected static function filterRecursive(array $items, User|int $user): array
    {
        $result = [];
        foreach ($items as $i => $item) {
            $url = ArrayHelper::getValue($item, 'url', '#');
//            $allow = is_array($url) ? static::checkRoute($url[0], array_slice($url, 1), $user) : true;
            $allow = !is_array($url) || static::checkRoute($url[0], array_slice($url, 1), $user);

            if (isset($item['items']) && is_array($item['items'])) {
                $subItems = self::filterRecursive($item['items'], $user);
                if (count($subItems)) {
                    $allow = true;
                }
                $item['items'] = $subItems;
            }
            if ($allow && !($url == '#' && empty($item['items']))) {
                $result[$i] = $item;
            }
        }
        return $result;
    }

    /**
     * Filter action column button. Use with [[yii\grid\GridView]]
     * ```php
     * 'columns' => [
     *     ...
     *     [
     *         'class' => 'yii\grid\ActionColumn',
     *         'template' => Helper::filterActionColumn(['view','update','activate'])
     *     ]
     * ],
     * ```
     * @param array|string $buttons
     * @param integer|User|null $user
     * @return string
     * @throws InvalidConfigException
     */
    public static function filterActionColumn(array|string $buttons = [], User|int $user = null): string
    {
        if (is_array($buttons)) {
            $result = [];
            foreach ($buttons as $button) {
                if (static::checkRoute($button, [], $user)) {
                    $result[] = "{{$button}}";
                }
            }
            return implode(' ', $result);
        }
        return preg_replace_callback('/\\{([\w\-\/]+)\\}/', function ($matches) use ($user) {
            return static::checkRoute($matches[1], [], $user) ? "{{$matches[1]}}" : '';
        }, $buttons);
    }

    /**
     * Use to invalidate cache.
     * @throws InvalidConfigException
     */
    public static function invalidate(): void
    {
        if (Configs::cache() !== null) {
            TagDependency::invalidate(Configs::cache(), Configs::CACHE_TAG);
        }
    }
}
