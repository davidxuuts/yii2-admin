<?php

namespace davidxu\admin\models\searchs;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use davidxu\admin\components\Configs;
use yii\rbac\BaseManager;
use yii\rbac\Item;

/**
 * AuthItemSearch represents the model behind the search form about AuthItem.
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AuthItem extends Model
{
    const TYPE_ROUTE = 101;

    public string $name = '';
    public int $type = 0;
    public string $description = '';
    public ?string $ruleName = null;
    public ?string $data = null;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name', 'ruleName', 'description'], 'safe'],
            [['type'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('rbac-admin', 'Name'),
            'item_name' => Yii::t('rbac-admin', 'Name'),
            'type' => Yii::t('rbac-admin', 'Type'),
            'description' => Yii::t('rbac-admin', 'Description'),
            'ruleName' => Yii::t('rbac-admin', 'Rule Name'),
            'data' => Yii::t('rbac-admin', 'Data'),
        ];
    }

    /**
     * Search authItem
     * @param array $params
     * @return ArrayDataProvider
     * @throws InvalidConfigException
     */
    public function search(array $params): ArrayDataProvider
    {
        /* @var BaseManager $authManager */
        $authManager = Configs::authManager();
        $advanced = Configs::instance()->advanced;
        $type = $this->type;
        if ($type === Item::TYPE_ROLE) {
            $items = $authManager->getRoles();
        } else {
            $items = array_filter($authManager->getPermissions(), function($item) use ($advanced, $type){
              $isPermission = $type === Item::TYPE_PERMISSION;
              if ($advanced) {
                return $isPermission xor (strncmp($item->name, '/', 1) === 0 or strncmp($item->name, '@', 1) === 0);
              }
              else {
                return $isPermission xor strncmp($item->name, '/', 1) === 0;
              }
            });
        }
        $this->load($params);
        if ($this->validate()) {
            $search = mb_strtolower(trim($this->name));
            $desc = mb_strtolower(trim($this->description));
            $ruleName = $this->ruleName;
            if (count($items)) {
                foreach ($items as $name => $item) {
                    $f = (empty($search) || mb_strpos(mb_strtolower($item->name), $search) !== false) &&
                        (empty($desc) || mb_strpos(mb_strtolower($item->description), $desc) !== false) &&
                        (empty($ruleName) || $item->ruleName == $ruleName);
                    if (!$f) {
                        unset($items[$name]);
                    }
                }
            }
        }

        return new ArrayDataProvider([
            'allModels' => $items,
        ]);
    }
}
