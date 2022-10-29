<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel davidxu\admin\models\searchs\Assignment */
/* @var $usernameField string */
/* @var $extraColumns string[] */

$this->title = Yii::t('rbac-admin', 'Assignments');
$this->params['breadcrumbs'][] = $this->title;

$columns = [
    ['class' => 'yii\grid\SerialColumn'],
    [
        'attribute' => $usernameField,
        'label' => Yii::t('app', 'Username')
    ],
];
if (!empty($extraColumns)) {
    $columns = array_merge($columns, $extraColumns);
}
$columns[] = [
    'class' => 'yii\grid\ActionColumn',
    'header' => Yii::t('app', 'Operate'),
    'template' => '{view}'
];
?>
<div class="admin-assignment-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <?php Pjax::begin(); ?>
            <?= $this->render('../common/_search', [
                'placeholder' => Yii::t('rbac-admin', 'Search username')
            ]) ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => $columns,
                ]);
            } catch (Exception $e) {
                echo YII_ENV_PROD ? null : $e->getMessage();
            }
            Pjax::end(); ?>
        </div>
    </div>
</div>
