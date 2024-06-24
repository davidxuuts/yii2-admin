<?php

namespace davidxu\admin\controllers;

use Yii;
use davidxu\admin\models\Assignment;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\User;

/**
 * AssignmentController implements the CRUD actions for Assignment model.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AssignmentController extends Controller
{
    public string|User|ActiveRecord|null $userClassName = null;
    public string $idField = 'id';
    public string $usernameField = 'username';
    public ?string $fullnameField = null;
//    public $searchClass;
    public array $extraColumns = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        if ($this->userClassName === null) {
            $this->userClassName = Yii::$app->getUser()->identityClass;
            $this->userClassName = $this->userClassName ? : 'davidxu\admin\models\User';
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'assign' => ['post'],
                    'revoke' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Assignment models.
     * @return string
     */
    public function actionIndex(): string
    {
        $query = $this->userClassName::find();
        $key = trim(Yii::$app->request->get('key', ''));
        if ($key) {
            $query->andFilterWhere([
                'like', $this->usernameField, $key
            ]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'idField' => $this->idField,
            'usernameField' => $this->usernameField,
            'extraColumns' => $this->extraColumns,
        ]);
    }

    /**
     * Displays a single Assignment model.
     * @param integer $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $model = $this->findModel($id);

        return $this->render('view', [
                'model' => $model,
                'idField' => $this->idField,
                'usernameField' => $this->usernameField,
                'fullnameField' => $this->fullnameField,
        ]);
    }

    /**
     * Assign items
     * @param string|int|array $id
     * @return array
     */
    public function actionAssign(string|int|array $id): array
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = new Assignment($id);
        $success = $model->assign($items);
        Yii::$app->response->format = 'json';
        return array_merge($model->getItems(), ['success' => $success]);
    }

    /**
     * Assign items
     * @param int|array|string $id
     * @return array
     */
    public function actionRevoke(int|array|string $id): array
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = new Assignment($id);
        $success = $model->revoke($items);
        Yii::$app->getResponse()->format = 'json';
        return array_merge($model->getItems(), ['success' => $success]);
    }

    /**
     * Finds the Assignment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int|array|string $id
     * @return Assignment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int|array|string $id): Assignment
    {
        $class = $this->userClassName;
        if (($user = $class::findIdentity($id)) !== null) {
            return new Assignment($id, $user);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
