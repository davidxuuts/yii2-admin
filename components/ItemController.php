<?php

namespace davidxu\admin\components;

use davidxu\admin\models\Item;
use davidxu\adminlte4\widgets\SweetAlert2;
use Throwable;
use yii\base\ExitException;
use yii\db\Exception;
use yii\rbac\Item as RbacItem;
use davidxu\adminlte4\helpers\ActionHelper;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\rbac\Permission;
use yii\rbac\Rule;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\base\NotSupportedException;
use yii\filters\VerbFilter;

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
    public string|Item $modelClass = Item::class;
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
        $query = $this->modelClass::find()->where(['type' => $this->type]);
        $key = trim(Yii::$app->request->get('key', ''));
        if ($key) {
            $query->andFilterWhere([
                'or',
                ['like', 'name', $key],
                ['like', 'description', $key],
                ['like', 'rule_name', $key],
            ]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'updated_at' => SORT_DESC,
                    'created_at' => SORT_DESC,
                    'name' => SORT_ASC,
                ],
            ],
        ]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @throws NotFoundHttpException|InvalidConfigException|Exception
     * @throws ExitException
     */
    public function actionAjaxEdit()
    {
        $name = Yii::$app->request->get('id');
        if ($name) {
            $model = new Item($this->findModel($name));
            $model->isNewRecord = false;
        } else {
            $model = new Item(null);
            $model->isNewRecord = true;
            $model->type = $this->type;
        }
        ActionHelper::activeFormValidate($model);

        Yii::info($model->attributes);

        if ($model->load(Yii::$app->getRequest()->post())) {
            if (!$model->rule_name) {
                $model->rule_name = null;
            }
            if ($model->save()) {
                Helper::invalidate();
                return ActionHelper::message(
                    Yii::t('rbac-admin', 'Saved successfully'),
                    $this->redirect(['view', 'id' => $model->name]));
            } else {
                Yii::info($model->getFirstErrors());
                return ActionHelper::message(
                    Yii::t('rbac-admin', 'Save failed'),
                    $this->redirect(['view', 'id' => $model->name]), SweetAlert2::TYPE_ERROR);
            }
        } else {
            return $this->renderAjax($this->action->id, ['model' => $model]);
        }
    }

    /**
     * Displays a single AuthItem model.
     * @param string $id
     * @return string
     * @throws NotFoundHttpException|InvalidConfigException
     */
    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        return $this->render('view', ['model' => $model]);
    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException|InvalidConfigException
     */
    public function actionDelete(string $id): mixed
    {
        $model = $this->findModel($id);
        $auth = Configs::authManager();
        $item = $model->type === RbacItem::TYPE_ROLE
            ? $auth->createRole($model->name)
            : $auth->createPermission($model->name);

        try {
            if ($auth->remove($item)) {
                Helper::invalidate();
                return ActionHelper::message(Yii::t('rbac-admin', 'Deleted successfully'),
                    $this->redirect(Yii::$app->request->referrer));
            } else {
                Yii::info($model->getFirstErrors());
                return ActionHelper::message(Yii::t('rbac-admin', 'Delete failed'),
                    $this->redirect(Yii::$app->request->referrer), SweetAlert2::TYPE_ERROR);
            }
        } catch (InvalidConfigException|Throwable $e) {
            if (YII_ENV_DEV) {
                echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                echo $e->getTraceAsString() . "\n";
            }
        }
        return ActionHelper::message(ActionHelper::getError($model),
            $this->redirect(Yii::$app->request->referrer), SweetAlert2::TYPE_ERROR);
    }

    /**
     * Assign items
     * @param string $id
     * @return array
     * @throws NotFoundHttpException|InvalidConfigException
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
     * Assign or remove items
     * @param string $id
     * @return array
     * @throws NotFoundHttpException|InvalidConfigException
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
     * @return Item|Permission|Rule the loaded model
     * @throws NotFoundHttpException|InvalidConfigException if the model cannot be found
     */
    protected function findModel(string $id): Item|Permission|Rule
    {
        $auth = Configs::authManager();
        $item = $this->type === RbacItem::TYPE_ROLE ? $auth->getRole($id) : $auth->getPermission($id);
        if ($item) {
            return new Item($item);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
