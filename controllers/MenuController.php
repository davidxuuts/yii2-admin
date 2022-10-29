<?php

namespace davidxu\admin\controllers;

use davidxu\base\enums\StatusEnum;
use davidxu\admin\components\BaseController;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use davidxu\admin\models\MenuCate;
use Yii;
use davidxu\admin\models\Menu;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use davidxu\admin\components\Helper;
use yii\web\Response;

/**
 * MenuController implements the CRUD actions for Menu model.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class MenuController extends BaseController
{
    public $modelClass = Menu::class;

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
     * Lists all Menu models.
     * @return mixed
     */
    public function actionIndex(): string
    {
        $query = $this->modelClass::find();
        $key = trim(Yii::$app->request->get('key'));
        if ($key) {
            $query->from(['m' => $this->modelClass::tableName()])
                ->andFilterWhere([
                'or',
                ['like', 'm.name', $key],
                ['like', 'm.route', $key],
                ['like', 'p.name', $key],
            ])
                ->joinWith(['menuParent p']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'parent' => SORT_ASC,
                    'order' => SORT_ASC,
                    'id' => SORT_ASC,
                ],
            ],
        ]);

        return $this->render('index', [
                'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Menu;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Helper::invalidate();
            return $this->message(Yii::t('app', 'Saved successfully'), $this->redirect(['index']));
        } else {
            return $this->render('create', [
                'model' => $model,
                'menuCateDropdownList' => $this->getMenuCateDropdownList(),
            ]);
        }
    }

    /**
     * Updates an existing Menu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->menuParent) {
            $model->parent_name = $model->menuParent->name;
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Helper::invalidate();
            return $this->message(Yii::t('rbac-admin', 'Saved successfully'), $this->redirect(['index']));
        } else {
            return $this->render('update', [
                'model' => $model,
                'menuCateDropdownList' => $this->getMenuCateDropdownList(),
            ]);
        }
    }

    /**
     * Deletes an existing Menu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Helper::invalidate();

        return $this->message(Yii::t('app', 'Deleted successfully'), $this->redirect(['index']));
    }
//
//    /**
//     * Finds the Menu model based on its primary key value.
//     * If the model is not found, a 404 HTTP exception will be thrown.
//     * @param int|null $id
//     * @return ActiveRecord
//     * @throws NotFoundHttpException if the model cannot be found
//     */
//    protected function findModel($id): ActiveRecord
//    {
//        if (($model = Menu::findOne($id)) !== null) {
//            return $model;
//        } else {
//            throw new NotFoundHttpException('The requested page does not exist.');
//        }
//    }

    protected function getMenuCateDropdownList()
    {
        $list = MenuCate::find()
            ->select(['id', 'title'])
            ->where(['status' => StatusEnum::ENABLED])
            ->orderBy(['id' => SORT_ASC])
            ->asArray()
            ->all();
        return ArrayHelper::map($list, 'id', 'title');
    }
}
