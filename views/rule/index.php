<?php

use davidxu\adminlte4\enums\ModalSizeEnum;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use davidxu\adminlte4\yii\grid\ActionColumn;
use yii\web\View;
use davidxu\admin\models\BizRule;
use yii\data\ActiveDataProvider;

/**
 * @var $this View
 * @var $model BizRule
 * @var $dataProvider ActiveDataProvider
 */

$this->title = Yii::t('rbac-admin', 'Rules');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-role-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="bi bi-plus-circle-fill"></i> ' . Yii::t('rbac-admin', 'Create Rule'),
                ['ajax-edit'],
                [
                    'class' => 'btn btn-xs btn-primary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modal',
                    'title' => Yii::t('rbac-admin', 'Create Rule'),
                    'aria-label' => Yii::t('rbac-admin', 'Create Rule'),
                    'data-bs-modal-class' => ModalSizeEnum::SIZE_LARGE,
                ]
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="app-container">
            <?php Pjax::begin(); ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
//                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute' => 'name',
                            'label' => Yii::t('rbac-admin', 'Name'),
                        ],
                        ['class' => ActionColumn::class,],
                    ],
                ]);
            } catch (Throwable|Exception $e) {
                if (YII_ENV_DEV) {
                    echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                    echo $e->getTraceAsString() . "\n";
                }
            } ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
