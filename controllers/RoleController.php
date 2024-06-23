<?php

namespace davidxu\admin\controllers;

use davidxu\admin\components\ItemController;
use yii\rbac\Item;

/**
 * RoleController implements the CRUD actions for AuthItem model.
 *
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class RoleController extends ItemController
{
    /**
     * @inheritdoc
     */
    public function labels(): array
    {
        return[
            'Item' => 'Role',
            'Items' => 'Roles',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getType(): int
    {
        return Item::TYPE_ROLE;
    }
}
