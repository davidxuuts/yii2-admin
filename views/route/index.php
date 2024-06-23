<?php

use davidxu\admin\AnimateAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $routes [] */

$this->title = Yii::t('rbac-admin', 'Routes');
$this->params['breadcrumbs'][] = ['label' => $this->title];

AnimateAsset::register($this);
YiiAsset::register($this);
$opts = Json::htmlEncode([
    'routes' => $routes,
]);
$this->registerJs("var _opts = {$opts};");
$this->registerJs($this->render('_script.js'));
$animateIcon = ' <i class="bi bi-arrow-repeat fa-spin"></i>';
?>
<div class="admin-route-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="app-container">
            <div class="input-group mb-3">
                <?= Html::textInput(null, null, [
                    'id' => 'inp-route',
                    'class' => 'form-control',
                    'placeholder' => Yii::t('rbac-admin', 'New route(s)'),
                ]) ?>
                <span class="input-group-text">
                    <?= Html::a(Yii::t('rbac-admin', 'Add') . $animateIcon, ['create'], [
                        'id' => 'btn-new',
                    ]) ?>
                </span>
            </div>
            <div class="row">
                <div class="col-sm-5">
                    <div class="input-group mb-1">
                        <?= Html::textInput(null, null, [
                            'data-target' => 'available',
                            'class' => 'form-control search',
                            'placeholder' => Yii::t('rbac-admin', 'Search for available'),
                        ]) ?>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <?= Html::a('<i class="bi bi-arrow-repeat"></i>', ['refresh'],[
                                    'id' => 'btn-refresh'
                                ]) ?>
                            </span>
                        </div>
                    </div>
                    <select multiple size="20" class="form-control list" data-target="available"></select>
                </div>
                <div class="col-sm-2 text-center">
                    <br><br>
                    <?=Html::a('&gt;&gt;' . $animateIcon, ['assign'], [
                        'class' => 'btn btn-success btn-assign w-50',
                        'data-target' => 'available',
                        'title' => Yii::t('rbac-admin', 'Assign'),
                    ]);?><br><br>
                    <?=Html::a('&lt;&lt;' . $animateIcon, ['remove'], [
                        'class' => 'btn btn-danger btn-assign w-50',
                        'data-target' => 'assigned',
                        'title' => Yii::t('rbac-admin', 'Remove'),
                    ]);?>
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
