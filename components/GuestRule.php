<?php

namespace davidxu\admin\components;

use yii\rbac\Rule;
use yii\web\User;

/**
 * Description of GuestRule
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 2.5
 */
class GuestRule extends Rule
{
    /**
     * @inheritdoc
     */
    public $name = 'guest_rule';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params): bool
    {
        /** @var User $user */
        return $user->getIsGuest();
    }
}
