<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model davidxu\admin\models\MenuCate */
/* @var $menuCateDropdownList array */

$this->title = Yii::t('rbac-admin', 'Update Menu') . ': ' . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', 'Menus'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('rbac-admin', 'Update');
?>

<div class="admin-menu-update card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <?= $this->render('_form', [
                'model' => $model,
                'menuCateDropdownList' => $menuCateDropdownList,
            ]) ?>
        </div>
    </div>
</div>

