<?php

namespace davidxu\admin\models;

use davidxu\adminlte4\enums\AppIdEnum;
use davidxu\adminlte4\enums\StatusEnum;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%menu_cate}}".
 *
 * @property int $id ID
 * @property string $title Title
 * @property string|null $addon Addons name
 * @property string|null $icon Icon
 * @property int $order Order
 * @property int|null $status Status[-1:Deleted;0:Disabled;1:Enabled]
 * @property int $created_at Created at
 * @property int $updated_at Updated at
 *
 * @property Menu[] $menus
 */
class MenuCate extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%menu_cate}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['title', 'app_id', 'order'], 'required'],
            [['order', 'status'], 'integer'],
            [['status'], 'default', 'value' => StatusEnum::STATUS_ENABLED],
            [['status'], 'in', 'range' => StatusEnum::getKeys()],
            [['title', 'icon'], 'string', 'max' => 50],
            [['addon'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title' => Yii::t('rbac-admin', 'Title'),
            'addon' => Yii::t('rbac-admin', 'Addons name'),
            'icon' => Yii::t('rbac-admin', 'Icon'),
            'order' => Yii::t('rbac-admin', 'Order'),
            'status' => Yii::t('rbac-admin', 'Status'),
        ];
    }

    /**
     * Gets query for [[Menus]].
     *
     * @return ActiveQuery
     */
    public function getMenus(): ActiveQuery
    {
        return $this->hasMany(Menu::class, ['cate_id' => 'id']);
    }
}
