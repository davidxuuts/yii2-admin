<?php

namespace davidxu\admin\components;

use davidxu\adminlte4\helpers\ActionHelper;
use Yii;
use davidxu\admin\models\AuthItem;
use davidxu\admin\models\searchs\AuthItem as AuthItemSearch;
use yii\rbac\Permission;
use yii\rbac\Rule;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\base\NotSupportedException;
use yii\filters\VerbFilter;
use yii\rbac\Item;

/**
 * AuthItemController implements the CRUD actions for AuthItem model.
 *
 * @property integer $type
 * @property array $labels
 * 
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class ItemController extends Controller
{

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
                    'assign' => ['post'],
                    'remove' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all AuthItem models.
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new AuthItemSearch(['type' => $this->type]);
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');
        if ($id) {
            $model = $this->findModel($id);
        } else {
            $model = new AuthItem(null);
            $model->type = $this->type;
        }

        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            Helper::invalidate();
            return ActionHelper::message(
                Yii::t('rbac-admin', 'Saved successfully'),
                $this->redirect(['view', 'id' => $model->name]));
        } else {
            return $this->renderAjax($this->action->id, ['model' => $model]);
        }
    }

    /**
     * Displays a single AuthItem model.
     * @param string $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(string $id): string
    {
        $model = $this->findModel($id);

        return $this->render('view', ['model' => $model]);
    }

    /**
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate(): mixed
    {
        $model = new AuthItem(null);
        $model->type = $this->type;
        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            Helper::invalidate();
            return ActionHelper::message(Yii::t('app', 'Saved successfully'), $this->redirect(['view', 'id' => $model->name]));
//            return $this->redirect(['view', 'id' => $model->name]);
        } else {
            return $this->render('create', ['model' => $model]);
        }
    }

    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionUpdate(string $id): mixed
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            Helper::invalidate();
            return ActionHelper::message(Yii::t('app', 'Saved successfully'), $this->redirect(['view', 'id' => $model->name]));
//            return $this->redirect(['view', 'id' => $model->name]);
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionDelete(string $id): mixed
    {
        $model = $this->findModel($id);
        Configs::authManager()->remove($model->item);
        Helper::invalidate();
//        Helper::invalidate();
        return ActionHelper::message(Yii::t('app', 'Deleted successfully'), $this->redirect(['index']));

//        return $this->redirect(['index']);
    }

    /**
     * Assign items
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAssign(string $id): array
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = $this->findModel($id);
        $success = $model->addChildren($items);
        Yii::$app->getResponse()->format = 'json';

        return array_merge($model->getItems(), ['success' => $success]);
    }

    /**
     * Assign items
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionGetUsers(string $id): array
    {
//        $page = Yii::$app->getRequest()->get('page', 0);
        $model = $this->findModel($id);
        Yii::$app->getResponse()->format = 'json';

        return array_merge($model->getUsers());
    }

    /**
     * Assign or remove items
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionRemove(string $id): array
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = $this->findModel($id);
        $success = $model->removeChildren($items);
        Yii::$app->getResponse()->format = 'json';

        return array_merge($model->getItems(), ['success' => $success]);
    }

    /**
     * @inheritdoc
     */
    public function getViewPath(): string
    {
        return $this->module->getViewPath() . DIRECTORY_SEPARATOR . 'item';
    }

    /**
     * Label use in view
     * @throws NotSupportedException
     */
    public function labels()
    {
        throw new NotSupportedException(get_class($this) . ' does not support labels().');
    }

    /**
     * Type of Auth Item.
     */
    public function getType()
    {
        
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AuthItem|Permission|Rule the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(string $id): AuthItem|Permission|Rule
    {
        $auth = Configs::authManager();
        $item = $this->type === Item::TYPE_ROLE ? $auth->getRole($id) : $auth->getPermission($id);
        if ($item) {
            return new AuthItem($item);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
