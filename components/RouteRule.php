<?php

namespace davidxu\admin\components;

use yii\rbac\Rule;

/**
 * RouteRule Rule for check route with extra params.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class RouteRule extends Rule
{
    const RULE_NAME = 'route_rule';

    /**
     * @inheritdoc
     */
    public $name = self::RULE_NAME;

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params): bool
    {
        $routeParams = $item->data['params'] ?? [];
        if (count($routeParams)) {
            foreach ($routeParams as $key => $value) {
                if (!array_key_exists($key, $params) || $params[$key] != $value) {
                    return false;
                }
            }
        }
        return true;
    }
}
