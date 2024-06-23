<?php

use davidxu\admin\components\ItemController;
use davidxu\adminlte4\enums\ModalSizeEnum;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\grid\GridView;
use davidxu\admin\components\RouteRule;
use davidxu\admin\components\Configs;
use davidxu\adminlte4\yii\grid\ActionColumn;
use yii\web\View;

/**
 * @var $this View
 * @var $dataProvider ActiveDataProvider
 * @var $context ItemController
 */

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
            <?= Html::a('<i class="bi bi-plus-circle-fill"></i> '
                . Yii::t('rbac-admin', 'Create ' . $labels['Item']),
                ['ajax-edit'],
                [
                    'class' => 'btn btn-xs btn-primary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modal',
                    'title' => Yii::t('rbac-admin', 'Create ' . $labels['Item']),
                    'aria-label' => Yii::t('rbac-admin', 'Create ' . $labels['Item']),
                    'data-bs-modal-class' => ModalSizeEnum::SIZE_LARGE,
                ]
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
<!--            --><?php //= $this->render('../common/_search', [
//                'placeholder' => Yii::t('rbac-admin', 'Search name/role(permission) name/description')
//            ]) ?>
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
                            'header' => Yii::t('rbac-admin', 'Operate'),
                            'template' => '{view} {ajax-edit} {delete}',
                        ],
                    ],
                ]);
            } catch (Exception|Throwable $e) {
                if (YII_ENV_DEV) {
                    echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                    echo $e->getTraceAsString() . "\n";
                }
            } ?>
        </div>
    </div>
</div>
