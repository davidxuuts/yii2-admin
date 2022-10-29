<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model davidxu\admin\models\AuthItem */
/* @var $context davidxu\admin\components\ItemController */

$context = $this->context;
$labels = $context->labels();
$this->title = Yii::t('rbac-admin', 'Create ' . $labels['Item']);
$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', $labels['Items']), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-auth-item-create card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
