<?php

namespace davidxu\admin\components;

use yii\base\ActionFilter;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;
use yii\base\Module;
use Yii;
use yii\web\User;
use yii\di\Instance;

/**
 * Access Control Filter (ACF) is a simple authorization method that is best used by applications that only need some simple access control. 
 * As its name indicates, ACF is an action filter that can be attached to a controller or a module as a behavior. 
 * ACF will check a set of access rules to make sure the current user can access the requested action.
 *
 * To use AccessControl, declare it in the application config as behavior.
 * For example.
 *
 * ```
 * 'as access' => [
 *     'class' => 'davidxu\admin\components\AccessControl',
 *     'allowActions' => ['site/login', 'site/error']
 * ]
 * ```
 *
 * @property User $user
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AccessControl extends ActionFilter
{
    /**
     * @var User|string User for check access.
     */
    private User|string $_user = 'user';
    /**
     * @var array List of action that not need to check access.
     */
    public array $allowActions = [];

    /**
     * Get user
     * @return User|string
     * @throws InvalidConfigException
     */
    public function getUser(): User|string
    {
        if (!$this->_user instanceof User) {
            $this->_user = Instance::ensure($this->_user, User::class);
        }
        return $this->_user;
    }

    /**
     * Set user
     * @param string|User $user
     */
    public function setUser(User|string $user): void
    {
        $this->_user = $user;
    }

    /**
     * @param $action
     * @return true|void
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    public function beforeAction($action)
    {
        $actionId = $action->getUniqueId();
        $user = $this->getUser();
        if (Helper::checkRoute('/' . $actionId, Yii::$app->getRequest()->get(), $user)) {
            return true;
        }
        $this->denyAccess($user);
    }

    /**
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403-HTTP exception will be thrown.
     * @param User $user the current user
     * @throws ForbiddenHttpException if the user is already logged in.
     */
    protected function denyAccess(User $user): void
    {
        if ($user->getIsGuest()) {
            $user->loginRequired();
        } else {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function isActive($action): bool
    {
        $uniqueId = $action->getUniqueId();
        if ($uniqueId === Yii::$app->getErrorHandler()->errorAction) {
            return false;
        }

        $user = $this->getUser();
        if($user->getIsGuest())
        {
            $loginUrl = null;
            if(is_array($user->loginUrl) && isset($user->loginUrl[0])){
                $loginUrl = $user->loginUrl[0];
                }else if(is_string($user->loginUrl)){
                    $loginUrl = $user->loginUrl;
                }
                if(!is_null($loginUrl) && trim($loginUrl,'/') === $uniqueId)
                {
                    return false;
                }
        }

        if ($this->owner instanceof Module) {
            // convert action uniqueId into an ID relative to the module
            $mid = $this->owner->getUniqueId();
            $id = $uniqueId;
            if ($mid !== '' && str_starts_with($id, $mid . '/')) {
                $id = substr($id, strlen($mid) + 1);
            }
        } else {
            $id = $action->id;
        }

        if (count($this->allowActions) > 0) {
            foreach ($this->allowActions as $route) {
                if (str_ends_with($route, '*')) {
                    $route = rtrim($route, "*");
                    if ($route === '' || str_starts_with($id, $route)) {
                        return false;
                    }
                } else {
                    if ($id === $route) {
                        return false;
                    }
                }
            }
        }

        if ($action->controller->hasMethod('allowAction') && in_array($action->id, $action->controller->allowAction())) {
            return false;
        }

        return true;
    }
}
