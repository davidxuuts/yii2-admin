<?php

namespace davidxu\admin\models;

use Yii;
use davidxu\admin\components\Configs;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "menu".
 *
 * @property integer $id Menu id(autoincrement)
 * @property string $name Menu name
 * @property integer $parent Menu parent
 * @property string $route Route for this menu
 * @property integer $order Menu order
 * @property string $data Extra information for this menu
 *
 * @property string $parent_name Parent name
 *
 * @property Menu $menuParent Menu parent
 * @property Menu[] $menus Menu children
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Menu extends ActiveRecord
{
    public ?string $parent_name = null;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public static function tableName()
    {
        return Configs::instance()->menuTable;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public static function getDb()
    {
        if (Configs::instance()->db !== null) {
            return Configs::instance()->db;
        } else {
            return parent::getDb();
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function rules(): array
    {
        return [
            [['order'], 'integer'],
            [['name'], 'required'],
            [['parent_name'], 'in',
                'range' => static::find()->select(['name'])->column(),
                'message' => Yii::t('rbac-admin', 'Menu "{value}" not found.')],
            [['parent', 'route', 'order', 'data'], 'default'],
            [['parent'], 'filterParent', 'when' => function() {
                return !$this->isNewRecord;
            }],
            [['name'], 'string', 'max' => 128],
            [['route'], 'string', 'max' => 255],
            [['data'], 'string'],
            [['route'], 'in',
                'range' => static::getSavedRoutes(),
                'message' => Yii::t('rbac-admin','Route "{value}" not found.')],
        ];
    }

    /**
     * Use to loop detected.
     * @throws InvalidConfigException
     */
    public function filterParent(): void
    {
        $parent = $this->parent;
        $db = static::getDb();
        $query = (new Query)->select(['parent'])
            ->from(static::tableName())
            ->where('[[id]]=:id');
        while ($parent) {
            if ($this->id == $parent) {
                $this->addError('parent_name', 'Loop detected.');
                return;
            }
            $parent = $query->params([':id' => $parent])->scalar($db);
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('rbac-admin', 'ID'),
            'name' => Yii::t('rbac-admin', 'Name'),
            'parent' => Yii::t('rbac-admin', 'Parent'),
            'parent_name' => Yii::t('rbac-admin', 'Parent Name'),
            'route' => Yii::t('rbac-admin', 'Route'),
            'order' => Yii::t('rbac-admin', 'Order'),
            'data' => Yii::t('rbac-admin', 'Icon'),
        ];
    }

    /**
     * Get menu parent
     * @return ActiveQuery
     */
    public function getMenuParent(): ActiveQuery
    {
        return $this->hasOne(Menu::class, ['id' => 'parent']);
    }

    /**
     * Get menu children
     * @return ActiveQuery
     */
    public function getMenus(): ActiveQuery
    {
        return $this->hasMany(Menu::class, ['parent' => 'id']);
    }
    private static ?array $_routes = null;

    /**
     * Get saved routes.
     * @return array
     * @throws InvalidConfigException
     */
    public static function getSavedRoutes(): array
    {
        if (self::$_routes === null) {
            self::$_routes = [];
            foreach (Configs::authManager()->getPermissions() as $name => $value) {
                if ($name[0] === '/' && !str_ends_with($name, '*')) {
                    self::$_routes[] = $name;
                }
            }
        }
        return self::$_routes;
    }

    /**
     * @throws InvalidConfigException
     */
    public static function getMenuSource(): array
    {
        $tableName = static::tableName();
        return (new Query())
                ->select(['m.id', 'm.name', 'm.route', 'parent_name' => 'p.name'])
                ->from(['m' => $tableName])
                ->leftJoin(['p' => $tableName], '[[m.parent]]=[[p.id]]')
                ->all(static::getDb());
    }
}
