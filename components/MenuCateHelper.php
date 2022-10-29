<?php

namespace davidxu\admin\components;

use davidxu\base\enums\StatusEnum;
use Yii;
use davidxu\admin\models\MenuCate;

/**
 * MenuCateHelper used to generate menuCate depend of user role.
 * Usage
 * 
 * ```
 * use davidxu\admin\components\MenuCateHelper;
 *
 * ```
 * $items = MenuHelper::getMenuCate();
 * ```
 *
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class MenuCateHelper
{
    /**
     * Use to get menu category.
     * @param string $appid
     * @return array
     */
    public static function getMenuCate($appid)
    {
        $menuCates = MenuCate::find()->where([
            'app_id' => $appid,
            'status' => StatusEnum::ENABLED,
        ])->orderBy([
            'order' => SORT_ASC,
            'id' => SORT_ASC,
        ])->all();
        return $menuCates;
    }
}
