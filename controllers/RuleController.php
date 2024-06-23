<?php

namespace davidxu\admin\controllers;

use davidxu\adminlte4\helpers\ActionHelper;
use Yii;
use davidxu\admin\models\BizRule;
use yii\web\Controller;
use davidxu\admin\models\searchs\BizRule as BizRuleSearch;
use yii\filters\VerbFilter;
use davidxu\admin\components\Helper;
use davidxu\admin\components\Configs;

/**
 * Description of RuleController
 *
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class RuleController extends Controller
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
                ],
            ],
        ];
    }

    /**
     * Lists all BizRule models.
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new BizRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
        ]);
    }

    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id', null);
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Helper::invalidate();
            return ActionHelper::message(
                Yii::t('rbac-admin', 'Saved successfully'),
                $this->redirect(['index'])
            );
        } else {
            return $this->renderAjax($this->action->id, ['model' => $model]);
        }
    }

    /**
     * Deletes an existing BizRule model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete(string $id)
    {
        $model = $this->findModel($id);
        Configs::authManager()->remove($model->item);
        Helper::invalidate();
        return ActionHelper::message(Yii::t('rbac-admin', 'Deleted successfully'), $this->redirect(['index']));

//        return $this->redirect(['index']);
    }

    /**
     * Finds the BizRule model based on its primary key value.
     * @param  ?string $id
     * @return BizRule  the loaded model
     */
    protected function findModel(string $id = null): BizRule
    {
        if (!empty($id)) {
            $item = Configs::authManager()->getRule($id);
            if ($item) {
                return new BizRule($item);
            }
            return new BizRule(null);
        }
        return new BizRule(null);
    }
}
