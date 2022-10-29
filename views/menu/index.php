<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\grid\ActionColumn;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac-admin', 'Menus');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-menu-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="fas fa-plus-circle"></i> ' . Yii::t('rbac-admin', 'Create menu'),
                ['create'],
                ['class' => 'btn btn-xs btn-primary']
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <?php Pjax::begin(); ?>
            <?= $this->render('../common/_search', [
                'placeholder' => Yii::t('rbac-admin', 'Search name/parent name')
            ]) ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'name',
                        [
                            'attribute' => 'menuParent.name',
                            'label' => Yii::t('rbac-admin', 'Parent'),
                        ],
                        'route',
                        'order',
                        [
                            'class' => ActionColumn::class,
                            'header' => Yii::t('app', 'Operate'),
                            'template' => '{update} {delete}'
                        ],
                    ],
                ]);
            } catch (Exception $e) {
                echo YII_ENV_PROD ? null : $e->getMessage();
            } ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
