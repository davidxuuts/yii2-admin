<?php

namespace davidxu\admin\models\searchs;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use davidxu\admin\models\BizRule as MBizRule;
use davidxu\admin\components\RouteRule;
use davidxu\admin\components\Configs;
use yii\rbac\BaseManager;

/**
 * Description of BizRule
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class BizRule extends Model
{
    /**
     * @var string name of the rule
     */
    public string $name;

    public function rules(): array
    {
        return [
            [['name'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('rbac-admin', 'Name'),
        ];
    }

    /**
     * Search BizRule
     * @param array $params
     * @return ArrayDataProvider
     * @throws InvalidConfigException
     */
    public function search(array $params): ArrayDataProvider
    {
        /* @var BaseManager $authManager */
        $authManager = Configs::authManager();
        $models = [];
        $included = !($this->load($params) && $this->validate() && trim($this->name) !== '');
        $rules = $authManager->getRules();
        if (count($rules)) {
            foreach ($rules as $name => $item) {
                if ($name != RouteRule::RULE_NAME && ($included || stripos($item->name, $this->name) !== false)) {
                    $models[$name] = new MBizRule($item);
                }
            }
        }

        return new ArrayDataProvider([
            'allModels' => $models,
        ]);
    }
}
