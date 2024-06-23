<?php

namespace davidxu\admin\controllers;

use davidxu\admin\components\Helper;
use davidxu\admin\components\UserStatus;
use davidxu\admin\models\form\ChangePassword;
use davidxu\admin\models\form\Login;
use davidxu\admin\models\form\PasswordResetRequest;
use davidxu\admin\models\form\ResetPassword;
use davidxu\admin\models\form\Signup;
use davidxu\admin\models\form\UserForm;
use davidxu\admin\models\searchs\User as UserSearch;
use davidxu\admin\models\User;
use davidxu\adminlte4\helpers\ActionHelper;
use Yii;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\UserException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;
use yii\filters\VerbFilter;
use yii\mail\BaseMailer;
use yii\rbac\Item;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;

/**
 * User controller
 */
class UserController extends Controller
{
//    private $_oldMailPath;

    public string|ActiveRecordInterface|null $modelClass = null;

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        if (empty($this->modelClass)) {
            $this->modelClass = Yii::$app->getUser()->identityClass;
        }
        if (!$this->modelClass) {
            throw new InvalidConfigException(Yii::t('base', 'Invalid configuration: {attribute}', [
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
                    'logout' => ['post'],
                    'activate' => ['post'],
                ],
            ],
        ];
    }
//
//    /**
//     * @inheritdoc
//     */
//    public function beforeAction($action)
//    {
//        if (parent::beforeAction($action)) {
//            if (Yii::$app->has('mailer') && ($mailer = Yii::$app->getMailer()) instanceof BaseMailer) {
//                /* @var $mailer BaseMailer */
//                $this->_oldMailPath = $mailer->getViewPath();
//                $mailer->setViewPath('@davidxu/admin/mail');
//            }
//            return true;
//        }
//        return false;
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function afterAction($action, $result)
//    {
//        if ($this->_oldMailPath !== null) {
//            Yii::$app->getMailer()->setViewPath($this->_oldMailPath);
//        }
//        return parent::afterAction($action, $result);
//    }

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
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
                'model' => $this->findModel($id),
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
                'type' => Item::TYPE_ROLE,
            ])->asArray()->all(),
            'name', 'name');

        $model->loadData();
        ActionHelper::activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Helper::invalidate();
                return ActionHelper::message(Yii::t('srbac', 'Saved successfully'),
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
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
//        Helper::invalidate();
        return ActionHelper::message(Yii::t('app', 'Deleted successfully'), $this->redirect(['index']));
//        return $this->redirect(['index']);
    }

//    /**
//     * Login
//     * @return string
//     */
//    public function actionLogin()
//    {
//        if (!Yii::$app->getUser()->isGuest) {
//            return $this->goHome();
//        }
//
//        $model = new Login();
//        if ($model->load(Yii::$app->getRequest()->post()) && $model->login()) {
//            return $this->goBack();
//        } else {
//            return $this->render('login', [
//                    'model' => $model,
//            ]);
//        }
//    }
//
//    /**
//     * Logout
//     * @return string
//     */
//    public function actionLogout()
//    {
//        Yii::$app->getUser()->logout();
//
//        return $this->goHome();
//    }
//
//    /**
//     * Signup new user
//     * @return string
//     */
//    public function actionSignup()
//    {
//        $model = new Signup();
//        if ($model->load(Yii::$app->getRequest()->post())) {
//            if ($user = $model->signup()) {
//                return $this->goHome();
//            }
//        }
//
//        return $this->render('signup', [
//                'model' => $model,
//        ]);
//    }
//
//    /**
//     * Request reset password
//     * @return string
//     */
//    public function actionRequestPasswordReset()
//    {
//        $model = new PasswordResetRequest();
//        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
//            if ($model->sendEmail()) {
//                Yii::$app->getSession()->setFlash('success', 'Check your email for further instructions.');
//
//                return $this->goHome();
//            } else {
//                Yii::$app->getSession()->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
//            }
//        }
//
//        return $this->render('requestPasswordResetToken', [
//                'model' => $model,
//        ]);
//    }
//
//    /**
//     * Reset password
//     * @return string
//     */
//    public function actionResetPassword($token)
//    {
//        try {
//            $model = new ResetPassword($token);
//        } catch (InvalidParamException $e) {
//            throw new BadRequestHttpException($e->getMessage());
//        }
//
//        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate() && $model->resetPassword()) {
//            Yii::$app->getSession()->setFlash('success', 'New password was saved.');
//
//            return $this->goHome();
//        }
//
//        return $this->render('resetPassword', [
//                'model' => $model,
//        ]);
//    }
//
//    /**
//     * Reset password
//     * @return string
//     */
//    public function actionChangePassword()
//    {
//        $model = new ChangePassword();
//        if ($model->load(Yii::$app->getRequest()->post()) && $model->change()) {
//            return $this->goHome();
//        }
//
//        return $this->render('change-password', [
//                'model' => $model,
//        ]);
//    }

    /**
     * Activate new user
     * @param integer $id
     * @return type
     * @throws UserException
     * @throws NotFoundHttpException
     */
    public function actionActivate($id)
    {
        /* @var $user User */
        $user = $this->findModel($id);
        if ($user->status == UserStatus::INACTIVE) {
            $user->status = UserStatus::ACTIVE;
            if ($user->save()) {
                return $this->goHome();
            } else {
                $errors = $user->firstErrors;
                throw new UserException(reset($errors));
            }
        }
        return $this->goHome();
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
