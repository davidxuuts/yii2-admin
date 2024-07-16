<?php

namespace davidxu\admin\controllers;

use davidxu\admin\components\Helper;
use davidxu\admin\models\form\ChangePasswordForm;
use davidxu\admin\models\form\UserForm;
use davidxu\adminlte4\helpers\ActionHelper;
use davidxu\adminlte4\widgets\SweetAlert2;
use Yii;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;
use yii\db\Exception;
use yii\filters\VerbFilter;
use davidxu\admin\models\Item;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\rbac\Item as RbacItem;

/**
 * User controller
 */
class UserController extends Controller
{
    public string|ActiveRecordInterface|null $modelClass = null;

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        if (empty($this->modelClass)) {
            $this->modelClass = Yii::createObject(Yii::$app->user->identityClass);
        }
        if (!$this->modelClass) {
            throw new InvalidConfigException(Yii::t('rbac-admin', 'Invalid configuration: {attribute}', [
                'attribute' => 'modelClass'
            ]));
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
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return string
     */
    public function actionIndex(): string
    {
        $query = $this->modelClass::find();
        $key = trim(Yii::$app->request->get('key', ''));
        if ($key) {
            $where = $this->modelClass->hasAttribute('realname')
                ? [
                    'or',
                    ['like', 'username', $key],
                    ['like', 'realname', $key],
                ]
                : [
                    'or',
                    ['like', 'username', $key],
                ];
            $query->andFilterWhere($where);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_ASC,
                    'updated_at' => SORT_DESC,
                ],
            ],
        ]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return mixed
     * @throws InvalidConfigException|ExitException
     */
    public function actionAjaxEdit(): mixed
    {
        $id = Yii::$app->request->get('id');
        $model = new UserForm(['id' => $id]);
        $authItems = ArrayHelper::map(
            Item::find()->select(['name'])->where([
                'type' => RbacItem::TYPE_ROLE,
            ])->asArray()->all(),
            'name', 'name');

        $model->loadData();
        ActionHelper::activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Helper::invalidate();
                return ActionHelper::message(Yii::t('rbac-admin', 'Saved successfully'),
                    $this->redirect(Yii::$app->request->referrer));
            }
            return ActionHelper::message(ActionHelper::getError($model),
                $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax('ajax-edit', [
            'model' => $model,
            'authItems' => $authItems,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return mixed
     */
    public function actionDelete(int $id): mixed
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->findModel($id)->delete();
            $authManager = Yii::$app->authManager;
            $authManager->revokeAll($id);
            $transaction->commit();
            Helper::invalidate();
            return ActionHelper::message(
                Yii::t('rbac-admin', 'Deleted successfully'),
                $this->redirect(['index'])
            );
        } catch (Exception|InvalidConfigException|NotFoundHttpException $e) {
            if (YII_ENV_DEV) {
                echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                echo $e->getTraceAsString() . "\n";
            }
            $transaction->rollBack();
            return ActionHelper::message(
                Yii::t('rbac-admin', 'Delete failed'),
                $this->redirect(['index'], SweetAlert2::TYPE_ERROR)
            );
        }
    }

    /**
     * Reset password
     * @return Response|string
     * @throws Exception|\yii\base\Exception
     */
    public function actionChangePassword(): Response|string
    {
        $model = new ChangePasswordForm();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->change()) {
            return $this->goHome();
        }

        return $this->renderAjax('change-password', [
                'model' => $model,
        ]);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return mixed
     * @throws InvalidConfigException
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): mixed
    {
        $modelClass = Yii::createObject(Yii::$app->user->identityClass);
        if (($model = $modelClass::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('rbac-admin','The requested user does not exist.'));
        }
    }
}
