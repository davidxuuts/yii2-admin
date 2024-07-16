<?php

namespace davidxu\admin\controllers;

use davidxu\adminlte4\helpers\ActionHelper;
use davidxu\adminlte4\widgets\SweetAlert2;
use Exception;
use Yii;
use davidxu\admin\models\Rule;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\web\Controller;
use yii\filters\VerbFilter;
use davidxu\admin\components\Helper;
use yii\rbac\Rule as RbacRule;

/**
 * Description of RuleController
 *
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class RuleController extends Controller
{

    public string|Rule $modelClass = Rule::class;

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
     * Lists all Rule models.
     * @return string
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $this->modelClass::find(),
        ]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * @return mixed
     * @throws InvalidConfigException
     * @throws ExitException
     * @throws Exception
     */
    public function actionAjaxEdit(): mixed
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);
        $model->class_name = $model->getClassName();

        ActionHelper::activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $auth = Yii::$app->authManager;
            $rule = new $model->class_name;
            $rule->name = $rule->name ?? $model->name;
            if ($rule instanceof RbacRule) {
                $result = $model->isNewRecord ? $auth->add($rule) : $auth->update($rule->name, $rule);
                if ($result) {
                    Helper::invalidate();
                    ActionHelper::message(Yii::t('rbac-admin', 'Saved successfully'),
                        $this->redirect(Yii::$app->request->referrer));
                }
                return ActionHelper::message(ActionHelper::getError($model),
                    $this->redirect(Yii::$app->request->referrer),
                    SweetAlert2::TYPE_ERROR
                );
            }
            return ActionHelper::message(ActionHelper::getError($model),
                $this->redirect(Yii::$app->request->referrer),
                SweetAlert2::TYPE_ERROR
            );
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * Delete a Rule
     * @return mixed
     * @throws InvalidConfigException
     */
    public function actionDelete(): mixed
    {
        $model = $this->findModel(Yii::$app->request->get('id'));
        $auth = Yii::$app->authManager;
        if ($auth->remove($auth->getRule($model->name))) {
            Helper::invalidate();
            return ActionHelper::message(Yii::t('rbac-admin', 'Deleted successfully'),
                $this->redirect(Yii::$app->request->referrer));
        }
        return ActionHelper::message(Yii::t('rbac-admin', 'Delete failed'),
            $this->redirect(Yii::$app->request->referrer),
            SweetAlert2::TYPE_ERROR
        );
    }

    /**
     * @param int|string|null $id
     * @param string|ActiveRecordInterface|null $modelClass
     * @return ActiveRecordInterface|ActiveRecord|Model|Rule
     */
    protected function findModel(int|string|null $id, string|ActiveRecordInterface $modelClass = null): ActiveRecordInterface|ActiveRecord|Model|Rule
    {
        /* @var $modelClass ActiveRecordInterface|Model|ActiveRecord */
        if (!$modelClass) {
            $modelClass = $this->modelClass;
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
