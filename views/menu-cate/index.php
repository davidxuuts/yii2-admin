<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\grid\ActionColumn;
use davidxu\admin\models\MenuCate;
use davidxu\base\enums\StatusEnum;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac-admin', 'Menu category');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-menu-cate-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="fas fa-plus-circle"></i> ' . Yii::t('rbac-admin', 'Create'),
                ['create'],
                ['class' => 'btn btn-xs btn-primary']
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <?php Pjax::begin(); ?>
            <?= $this->render('../common/_search', [
                'placeholder' => Yii::t('rbac-admin', 'Search title')
            ]) ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'app_id',
                        'title',
                        'icon',
                        [
                            'attribute' => 'icon',
                            'format' => 'RAW',
                            'value' => function($model) {
                                /** @var MenuCate $model */
                                return '<i class="fas fa-' . $model->icon . '"></i>';
                            }
                        ],
                        'order',
                        [
                            'attribute' => 'status',
                            'value' => function($model) {
                                /** @var MenuCate $model */
                                return StatusEnum::getValue($model->status);
                            }
                        ],
                        [
                            'class' => ActionColumn::class,
                            'header' => Yii::t('app', 'Operate'),
                            'template' => '{update} {delete}',
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
