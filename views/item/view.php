<?php

use davidxu\admin\AnimateAsset;
use davidxu\admin\components\ItemController;
use davidxu\admin\models\AuthItem;
use davidxu\adminlte4\enums\ModalSizeEnum;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\DetailView;

/**
 * @var $this View
 * @var $model AuthItem
 * @var $context ItemController
 */

$context = $this->context;
$labels = $context->labels();
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', $labels['Items']), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

AnimateAsset::register($this);
YiiAsset::register($this);
$opts = Json::htmlEncode([
        'items' => $model->getItems(),
        'users' => $model->getUsers(),
        'getUserUrl' => Url::to(['get-users', 'id' => $model->name])
    ]);
Yii::info($opts);
$this->registerJs("var _opts = {$opts};");
$this->registerJs($this->render('_script.js'));
$animateIcon = ' <i class="bi bi-arrow-repeat fa-spin"></i>';
?>
<div class="admin-auth-item-view card card-outline card-secondary">
    <div class="card-header">
        <div class="row gx-1">
            <div class="col text-end">
                <?= Html::a('<i class="bi bi-pencil-square"></i> ' . Yii::t('rbac-admin', 'Edit'),
                    ['ajax-edit', 'id' => $model->name],
                    [
                    'class' => 'btn btn-sm btn-secondary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modal',
                    'title' => Yii::t('rbac-admin', 'Create ' . $labels['Item']),
                    'aria-label' => Yii::t('rbac-admin', 'Create ' . $labels['Item']),
                    'data-bs-modal-class' => ModalSizeEnum::SIZE_LARGE,
                ]) ?>
            </div>
            <div class="col text-start">
                <?= Html::a('<i class="bi bi-trash3"></i> ' . Yii::t('rbac-admin', 'Delete'),
                    ['delete', 'id' => $model->name],
                    [
                        'class' => 'btn btn-sm btn-danger',
                        'data-confirm' => Yii::t('rbac-admin', 'Are you sure to delete this item?'),
                        'data-method' => 'post',
                    ]) ?>
            </div>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'name',
                            'description:ntext',
                            'ruleName',
                            'data:ntext',
                        ],
                        'template' => '<tr><th style="width:25%">{label}</th><td>{value}</td></tr>',
                    ]);
                    ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <table class="table table-striped table-bordered">
                        <tbody>
                        <tr>
                            <th><?= Yii::t('rbac-admin', 'Assigned users'); ?></th>
                        </tr>
                        <tr>
                            <td id="list-users"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-5">
                    <?= Html::textInput(null, null, [
                        'class' => 'form-control search mb-1',
                        'data-target' => 'available',
                        'placeholder' => Yii::t('rbac-admin', 'Search for available'),
                    ]) ?>
                    <select multiple size="20" class="form-control list" data-target="available"></select>
                </div>
                <div class="col-sm-2 text-center">
                    <br><br>
                    <?=
                    Html::a('&gt;&gt;' . $animateIcon, ['assign', 'id' => $model->name], [
                        'class' => 'btn btn-success btn-assign w-50',
                        'data-target' => 'available',
                        'title' => Yii::t('rbac-admin', 'Assign'),
                    ]);
                    ?><br><br>
                    <?=
                    Html::a('&lt;&lt;' . $animateIcon, ['remove', 'id' => $model->name], [
                        'class' => 'btn btn-danger btn-assign w-50',
                        'data-target' => 'assigned',
                        'title' => Yii::t('rbac-admin', 'Remove'),
                    ]);
                    ?>
                </div>
                <div class="col-sm-5">
                    <?= Html::textInput(null, null, [
                        'class' => 'form-control search mb-1',
                        'data-target' => 'assigned',
                        'placeholder' => Yii::t('rbac-admin', 'Search for assigned'),
                    ]) ?>
                    <select multiple size="20" class="form-control list" data-target="assigned"></select>
                </div>
            </div>
        </div>
    </div>
</div>
