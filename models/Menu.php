<?php

namespace davidxu\admin\models;

use Yii;
use davidxu\admin\components\Configs;
use yii\db\ActiveQuery;
use yii\db\Query;
use davidxu\admin\models\MenuCate;

/**
 * This is the model class for table "menu".
 *
 * @property integer $id Menu id(autoincrement)
 * @property integer $cate_id Menu category
 * @property string $name Menu name
 * @property integer $parent Menu parent
 * @property string $route Route for this menu
 * @property integer $order Menu order
 * @property string $data Extra information for this menu
 *
 * @property string $parent_name Parent name
 *
 * @property Menu $menuParent Menu parent
 * @property Cate $cate Menu category
 * @property Menu[] $menus Menu children
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Menu extends \yii\db\ActiveRecord
{
    public $parent_name;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Configs::instance()->menuTable;
    }

    /**
     * @inheritdoc
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
     */
    public function rules()
    {
        return [
            [['cate_id', 'order'], 'integer'],
            [['name', 'cate_id'], 'required'],
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
            [
                ['cate_id'], 'exist', 'skipOnError' => true,
                'targetClass' => MenuCate::class,
                'targetAttribute' => ['cate_id' => 'id']
            ],
            [['route'], 'in',
                'range' => static::getSavedRoutes(),
                'message' => Yii::t('rbac-admin','Route "{value}" not found.')],
        ];
    }

    /**
     * Use to loop detected.
     */
    public function filterParent()
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
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('rbac-admin', 'ID'),
            'cate_id' => Yii::t('rbac-admin', 'Menu category'),
            'name' => Yii::t('rbac-admin', 'Name'),
            'parent' => Yii::t('rbac-admin', 'Parent'),
            'parent_name' => Yii::t('rbac-admin', 'Parent Name'),
            'route' => Yii::t('rbac-admin', 'Route'),
            'order' => Yii::t('rbac-admin', 'Order'),
            'data' => Yii::t('rbac-admin', 'Data'),
        ];
    }

    /**
     * Get menu parent
     * @return \yii\db\ActiveQuery
     */
    public function getMenuParent()
    {
        return $this->hasOne(Menu::class, ['id' => 'parent']);
    }

    /**
     * Get menu children
     * @return \yii\db\ActiveQuery
     */
    public function getMenus()
    {
        return $this->hasMany(Menu::class, ['parent' => 'id']);
    }
    private static $_routes;

    /**
     * Get saved routes.
     * @return array
     */
    public static function getSavedRoutes()
    {
        if (self::$_routes === null) {
            self::$_routes = [];
            foreach (Configs::authManager()->getPermissions() as $name => $value) {
                if ($name[0] === '/' && substr($name, -1) != '*') {
                    self::$_routes[] = $name;
                }
            }
        }
        return self::$_routes;
    }

    public static function getMenuSource()
    {
        $tableName = static::tableName();
        return (new \yii\db\Query())
                ->select(['m.id', 'm.name', 'm.route', 'parent_name' => 'p.name'])
                ->from(['m' => $tableName])
                ->leftJoin(['p' => $tableName], '[[m.parent]]=[[p.id]]')
                ->all(static::getDb());
    }

    /**
     * Gets query for [[Cate]].
     *
     * @return ActiveQuery
     */
    public function getCate(): ActiveQuery
    {
        return $this->hasOne(MenuCate::class, ['id' => 'cate_id']);
    }
}
