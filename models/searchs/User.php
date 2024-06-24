<?php

namespace davidxu\admin\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * User represents the model behind the search form about `davidxu\admin\models\User`.
 */
class User extends Model
{
    public int $id = 0;
    public ?string $username = null;
    public ?string $email = null;
    public int $status = 0;
    
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'status',], 'integer'],
            [['username', 'email'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with a search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        /* @var $query ActiveQuery */
        $class = Yii::$app->getUser()->identityClass ? : 'davidxu\admin\models\User';
        $query = $class::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if (!$this->validate()) {
            $query->where('1=0');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }
}
