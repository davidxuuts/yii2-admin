<?php

namespace davidxu\admin\controllers;

use davidxu\adminlte4\helpers\ActionHelper;
use yii\base\ExitException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;
use Yii;
use davidxu\admin\models\Menu;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use davidxu\admin\components\Helper;


/**
 * MenuController implements the CRUD actions for Menu model.
 *
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class MenuController extends Controller
{
    public string|Menu $modelClass = Menu::class;

    /**
     * Lists all Menu models.
     * @return mixed
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->orderBy(['order' => SORT_ASC]),
            'pagination' => false,
        ]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * @throws ExitException
     * @throws BadRequestHttpException
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id', 0);
        $routes  = Menu::getSavedRoutes();
        $data = [];
        if (count($routes) > 1) {
            foreach ($routes as $route) {
                $data[$route] = $route;
            }
        }
        /** @var Menu $model */
        $model = $this->findMenuModel($id, $this->modelClass);
        if ($model->isNewRecord && ($parent = Yii::$app->request->get('parent')) > 0) {
            $model->parent = $parent;
            /** @var Menu $modelParent */
            $modelParent = $this->findMenuModel($parent, $this->modelClass);
        }
        ActionHelper::activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Helper::invalidate();
                return ActionHelper::message(Yii::t('adminlte4', 'Saved successfully'),
                    $this->redirect(Yii::$app->request->referrer));
            }
            return ActionHelper::message(ActionHelper::getError($model),
                $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'data' => $data,
        ]);
    }

    /**
     * Deletes an existing Menu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete(int $id): mixed
    {
        $this->findMenuModel($id)->delete();
        Helper::invalidate();

        return ActionHelper::message(Yii::t('app', 'Deleted successfully'), $this->redirect(['index']));
    }

    /**
     * @param int|string|null $id
     * @param string|ActiveRecordInterface $modelClass
     * @return ActiveRecordInterface|ActiveRecord|Model|Menu
     * @throws BadRequestHttpException
     */
    protected function findMenuModel(int|string|null $id,
                                 string|ActiveRecordInterface $modelClass = Menu::class
    ): ActiveRecordInterface|ActiveRecord|Model|Menu
    {
        /* @var $modelClass ActiveRecordInterface|Model|ActiveRecord */
        if (!$modelClass) {
            throw new BadRequestHttpException('No modelClass found.');
        }
        $keys = $modelClass::primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $model = $modelClass::findOne(array_combine($keys, $values));
            }
        } elseif ($id !== null) {
            $model = $modelClass::findOne($id);
        } elseif ($modelClass::findOne($id) === null) {
            $model = new $modelClass;
        }

        if (!isset($model)) {
            $model = new $modelClass;
        }
        $model->loadDefaultValues();
        return $model;
    }
}
