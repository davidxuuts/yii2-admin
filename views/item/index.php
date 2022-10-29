<?php

use yii\helpers\Html;
use yii\grid\GridView;
use davidxu\admin\components\RouteRule;
use davidxu\admin\components\Configs;
use yii\grid\ActionColumn;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel davidxu\admin\models\searchs\AuthItem */
/* @var $context davidxu\admin\components\ItemController */

$context = $this->context;
$labels = $context->labels();
$this->title = Yii::t('rbac-admin', $labels['Items']);
$this->params['breadcrumbs'][] = $this->title;

$rules = array_keys(Configs::authManager()->getRules());
$rules = array_combine($rules, $rules);
unset($rules[RouteRule::RULE_NAME]);
?>

<div class="admin-role-permission-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="fas fa-plus-circle"></i> '
                . Yii::t('rbac-admin', 'Create ' . $labels['Item']),
                ['create'],
                ['class' => 'btn btn-xs btn-primary']
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <?= $this->render('../common/_search', [
                'placeholder' => Yii::t('rbac-admin', 'Search name/role(permission) name/description')
            ]) ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-bordered'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute' => 'name',
                            'label' => Yii::t('rbac-admin', 'Name'),
                        ],
                        [
                            'attribute' => 'ruleName',
                            'label' => Yii::t('rbac-admin', 'Rule Name'),
                            'filter' => $rules
                        ],
                        [
                            'attribute' => 'description',
                            'label' => Yii::t('rbac-admin', 'Description'),
                        ],
                        [
                            'class' => ActionColumn::class,
                            'header' => Yii::t('app', 'Operate'),
                            'template' => '{view} {update} {delete}',
                        ],
                    ],
                ]);
            } catch (Exception $e) {
                echo YII_ENV_PROD ? null : $e->getMessage();
            } ?>
        </div>
    </div>
</div>
