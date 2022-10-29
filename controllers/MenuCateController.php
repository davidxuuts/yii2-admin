<?php

namespace davidxu\admin\controllers;

use davidxu\admin\components\BaseController;
use Yii;
use davidxu\admin\models\MenuCate;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use davidxu\admin\components\Helper;

/**
 * MenuCateController implements the CRUD actions for MenuCate model.
 *
 * @author David Xu <david.xu.uts@163.com>
 * @since 1.0
 */
class MenuCateController extends BaseController
{
    public $modelClass = MenuCate::class;
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
     * Lists all MenuCate models.
     * @return mixed
     */
    public function actionIndex(): string
    {
        $query = MenuCate::find();
        $key = Yii::$app->request->get('key');
        if ($key) {
            $query->addFilterWhere([
                'or',
                ['like', 'title', $key],
                ['like', 'addon', $key],
            ]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
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
     * Creates a new MenuCate model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new MenuCate;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Helper::invalidate();
            return $this->message(Yii::t('rbac-admin', 'Saved successfully'), $this->redirect(['index']));
        } else {
            return $this->render('create', [
                    'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing MenuCate model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Helper::invalidate();
            return $this->message(Yii::t('rbac-admin', 'Saved successfully'), $this->redirect(['index']));
        } else {
            return $this->render('update', [
                    'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing MenuCate model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Helper::invalidate();

        return $this->message(Yii::t('rbac-admin', 'Deleted successfully'), $this->redirect(['index']));
    }

}
