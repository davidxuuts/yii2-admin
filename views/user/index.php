<?php

use yii\helpers\Html;
use yii\grid\GridView;
use davidxu\admin\components\Helper;
use yii\widgets\Pjax;
use davidxu\adminlte4\yii\grid\ActionColumn;

/* @var $this yii\web\View */
/* @var $searchModel davidxu\admin\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac-admin', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-user-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="bi bi-plus-circle-fill"></i> ' . Yii::t('rbac-admin', 'Create user'),
                ['create'],
                ['class' => 'btn btn-xs btn-primary']
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="app-container">
            <?php Pjax::begin(); ?>
<!--            --><?php //= $this->render('../common/_search', [
//                'placeholder' => Yii::t('rbac-admin', 'Search name/parent name')
//            ]) ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'username',
                        'email:email',
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->status == 0 ? 'Inactive' : 'Active';
                            },
                            'filter' => [
                                0 => 'Inactive',
                                10 => 'Active'
                            ]
                        ],
                        [
                            'class' => ActionColumn::class,
                            'template' => Helper::filterActionColumn(['view', 'activate', 'delete']),
                            'buttons' => [
                                'activate' => function ($url, $model) {
                                    if ($model->status == 10) {
                                        return '';
                                    }
                                    $options = [
                                        'title' => Yii::t('rbac-admin', 'Activate'),
                                        'aria-label' => Yii::t('rbac-admin', 'Activate'),
                                        'data-confirm' => Yii::t('rbac-admin', 'Are you sure you want to activate this user?'),
                                        'data-method' => 'post',
                                        'data-pjax' => '0',
                                    ];
                                    return Html::a('<i class="bi bi-check-circle-fill"></i>', $url, $options);
                                }
                            ]
                        ],
                    ],
                ]);
            } catch (Exception $e) {
                 if (YII_ENV_DEV) {
                     echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                     echo $e->getTraceAsString() . "\n";
                 }
            } ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
