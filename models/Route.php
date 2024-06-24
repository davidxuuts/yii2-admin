<?php

namespace davidxu\admin\models;

use davidxu\admin\BaseObject;
use Exception;
use davidxu\admin\components\Configs;
use davidxu\admin\components\Helper;
use davidxu\admin\components\RouteRule;
use ReflectionClass;
use Yii;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\caching\TagDependency;
use yii\helpers\VarDumper;

/**
 * Description of Route
 *
 * @property mixed $routePrefix
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Route extends BaseObject
{
    const CACHE_TAG = 'davidxu.admin.route';

    const PREFIX_ADVANCED = '@';
    const PREFIX_BASIC = '/';

    private string $_routePrefix = '';

    /**
     * Assign or remove items
     * @param array $routes
     * @return void
     * @throws InvalidConfigException
     */
    public function addNew(array $routes): void
    {
        $manager = Configs::authManager();
        foreach ($routes as $route) {
            try {
                $r = explode('&', $route);
                $item = $manager->createPermission($this->getPermissionName($route));
                if (count($r) > 1) {
                    $action = '/' . trim($r[0], '/');
                    if (($itemAction = $manager->getPermission($action)) === null) {
                        $itemAction = $manager->createPermission($action);
                        $manager->add($itemAction);
                    }
                    unset($r[0]);
                    foreach ($r as $part) {
                        $part = explode('=', $part);
                        $item->data['params'][$part[0]] = $part[1] ?? '';
                    }
                    $this->setDefaultRule();
                    $item->ruleName = RouteRule::RULE_NAME;
                    $manager->add($item);
                    $manager->addChild($item, $itemAction);
                } else {
                    $manager->add($item);
                }
            } catch (Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();
    }

    /**
     * Assign or remove items
     * @param array $routes
     * @return void
     * @throws InvalidConfigException
     */
    public function remove(array $routes): void
    {
        $manager = Configs::authManager();
        foreach ($routes as $route) {
            try {
                $item = $manager->createPermission($this->getPermissionName($route));
                $manager->remove($item);
            } catch (Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();
    }

    /**
     * Return route prefix depending on the configuration.
     * @return string Route prefix
     * @throws InvalidConfigException
     */
    public function getRoutePrefix(): string
    {
        if (!$this->_routePrefix) {
            $this->_routePrefix = Configs::instance()->advanced ? self::PREFIX_ADVANCED : self::PREFIX_BASIC;
        }
        return $this->_routePrefix;
    }

    /**
     * Returns the correct permission name depending on the configuration.
     * @param string $route Route
     * @return string        Permission name
     */
    public function getPermissionName(string $route): string
    {
        if (self::PREFIX_BASIC == $this->routePrefix) {
            return self::PREFIX_BASIC . trim($route, self::PREFIX_BASIC);
        } else {
            return self::PREFIX_ADVANCED . ltrim(trim($route, self::PREFIX_BASIC), self::PREFIX_ADVANCED);
        }
    }

    /**
     * Get available and assigned routes
     * @return array
     * @throws InvalidConfigException
     */
    public function getRoutes(): array
    {
        $manager = Configs::authManager();
        // Get advanced configuration
        $advanced = Configs::instance()->advanced;
        if ($advanced) {
            // Use advanced route scheme.
            // Set advanced route prefix.
            $this->_routePrefix = self::PREFIX_ADVANCED;
            $routes = [];
            // Save original app.
            $yiiApp = Yii::$app;
            // Step through each configured application
            foreach ($advanced as $id => $configPaths) {
                // Force correct id string.
                $id = $this->routePrefix . ltrim(trim($id), $this->routePrefix);
                $config = [];
                // Assemble configuration for current app.
                foreach ($configPaths as $configPath) {
                    // Merge every new configuration with the old config array.
                    $config = yii\helpers\ArrayHelper::merge($config, require (Yii::getAlias($configPath)));
                }
                unset($config['bootstrap']);
                $app = new yii\web\Application($config);
                // Get all the routes of the newly created app.
                $r = $this->getAppRoutes($app);
                // Dump new app
                unset($app);
                // Prepend the app id to all routes.
                foreach ($r as $route) {
                    $routes[$id . $route] = $id . $route;
                }
            }
            Yii::$app = $yiiApp;
            unset($yiiApp);
        } else {
            // Set basic route prefix
            $this->_routePrefix = self::PREFIX_BASIC;
            // Get basic app routes.
            $routes = $this->getAppRoutes();
        }
        $exists = [];
        foreach (array_keys($manager->getPermissions()) as $name) {
            if ($name[0] !== $this->routePrefix) {
                continue;
            }
            $exists[] = $name;
            unset($routes[$name]);
        }
        return [
            'available' => array_keys($routes),
            'assigned' => $exists,
        ];
    }

    /**
     * Get a list of application routes
     * @param null $module
     * @return array
     * @throws InvalidConfigException
     */
    public function getAppRoutes($module = null): array
    {
        if ($module === null) {
            $module = Yii::$app;
        } elseif (is_string($module)) {
            $module = Yii::$app->getModule($module);
        }
        $key = [__METHOD__, Yii::$app->id, $module->getUniqueId()];
        $cache = Configs::instance()->cache;
        if ($cache === null || ($result = $cache->get($key)) === false) {
            $result = [];
            $this->getRouteRecursive($module, $result);
            $cache?->set($key, $result, Configs::instance()->cacheDuration, new TagDependency([
                'tags' => self::CACHE_TAG,
            ]));
        }

        return $result;
    }

    /**
     * Get route(s) recursive
     * @param Module $module
     * @param array|string $result
     */
    protected function getRouteRecursive(Module $module, array|string &$result): void
    {
        $token = "Get Route of '" . get_class($module) . "' with id '" . $module->uniqueId . "'";
        Yii::beginProfile($token, __METHOD__);
        try {
            foreach ($module->getModules() as $id => $child) {
                if (($child = $module->getModule($id)) !== null) {
                    $this->getRouteRecursive($child, $result);
                }
            }

            foreach ($module->controllerMap as $id => $type) {
                $this->getControllerActions($type, $id, $module, $result);
            }

            $namespace = trim($module->controllerNamespace, '\\') . '\\';
            $this->getControllerFiles($module, $namespace, '', $result);
            $all = '/' . ltrim($module->uniqueId . '/*', '/');
            $result[$all] = $all;
        } catch (Exception $exc) {
            Yii::error($exc->getMessage(), __METHOD__);
        }
        Yii::endProfile($token, __METHOD__);
    }

    /**
     * Get list controller under module
     * @param Module $module
     * @param string $namespace
     * @param string $prefix
     * @param mixed $result
     * @return void
     */
    protected function getControllerFiles(Module $module, string $namespace, string $prefix, mixed &$result): void
    {
        $path = Yii::getAlias('@' . str_replace('\\', '/', $namespace), false);
        $token = "Get controllers from '$path'";
        Yii::beginProfile($token, __METHOD__);
        try {
            if (!is_dir($path)) {
                return;
            }
            foreach (scandir($path) as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_dir($path . '/' . $file) && preg_match('%^[a-z0-9_/]+$%i', $file . '/')) {
                    $this->getControllerFiles($module, $namespace . $file . '\\', $prefix . $file . '/', $result);
                } elseif (strcmp(substr($file, -14), 'Controller.php') === 0) {
                    $baseName = substr(basename($file), 0, -14);
                    $name = strtolower(preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $baseName));
                    $id = ltrim(str_replace(' ', '-', $name), '-');
                    $className = $namespace . $baseName . 'Controller';
                    if (!str_contains($className, '-') && class_exists($className) && is_subclass_of($className, 'yii\base\Controller')) {
                        $this->getControllerActions($className, $prefix . $id, $module, $result);
                    }
                }
            }
        } catch (Exception $exc) {
            Yii::error($exc->getMessage(), __METHOD__);
        }
        Yii::endProfile($token, __METHOD__);
    }

    /**
     * Get list action of controller
     * @param mixed $type
     * @param string|int $id
     * @param Module $module
     * @param mixed $result
     */
    protected function getControllerActions(mixed $type, string|int $id, Module $module, mixed &$result): void
    {
        $token = "Create controller with config=" . VarDumper::dumpAsString($type) . " and id='$id'";
        Yii::beginProfile($token, __METHOD__);
        try {
            /* @var $controller Controller */
            $controller = Yii::createObject($type, [$id, $module]);
            $this->getActionRoutes($controller, $result);
            $all = "/$controller->uniqueId/*";
            $result[$all] = $all;
        } catch (Exception $exc) {
            Yii::error($exc->getMessage(), __METHOD__);
        }
        Yii::endProfile($token, __METHOD__);
    }

    /**
     * Get route of action
     * @param Controller $controller
     * @param mixed $result all controller action.
     */
    protected function getActionRoutes(Controller $controller, mixed &$result): void
    {
        $token = "Get actions of controller '" . $controller->uniqueId . "'";
        Yii::beginProfile($token, __METHOD__);
        try {
            $prefix = '/' . $controller->uniqueId . '/';
            foreach ($controller->actions() as $id => $value) {
                $result[$prefix . $id] = $prefix . $id;
            }
            $class = new ReflectionClass($controller);
            foreach ($class->getMethods() as $method) {
                $name = $method->getName();
                if ($method->isPublic() && !$method->isStatic() && str_starts_with($name, 'action') && $name !== 'actions') {
                    $name = strtolower(preg_replace('/(?<![A-Z])[A-Z]/', ' \0', substr($name, 6)));
                    $id = $prefix . ltrim(str_replace(' ', '-', $name), '-');
                    $result[$id] = $id;
                }
            }
        } catch (Exception $exc) {
            Yii::error($exc->getMessage(), __METHOD__);
        }
        Yii::endProfile($token, __METHOD__);
    }

    /**
     * Invalidate cache
     * @throws InvalidConfigException
     */
    public static function invalidate(): void
    {
        if (Configs::cache() !== null) {
            TagDependency::invalidate(Configs::cache(), self::CACHE_TAG);
        }
    }

    /**
     * Set default rule of parameterize route.
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function setDefaultRule(): void
    {
        if (Configs::authManager()->getRule(RouteRule::RULE_NAME) === null) {
            Configs::authManager()->add(new RouteRule());
        }
    }
}
