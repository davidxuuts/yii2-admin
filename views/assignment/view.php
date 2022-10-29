<?php

use davidxu\admin\AnimateAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model davidxu\admin\models\Assignment */
/* @var $fullnameField string */

$userName = $model->{$usernameField};
if (!empty($fullnameField)) {
    $userName .= ' (' . ArrayHelper::getValue($model, $fullnameField) . ')';
}
$userName = Html::encode($userName);

$this->title = Yii::t('rbac-admin', 'Assignment') . ' : ' . $userName;

$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', 'Assignments'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $userName;

AnimateAsset::register($this);
YiiAsset::register($this);
$opts = Json::htmlEncode([
    'items' => $model->getItems(),
]);
$this->registerJs("var _opts = {$opts};");
$this->registerJs($this->render('_script.js'));
$animateIcon = ' <i class="fas fa-sync-alt fa-spin"></i>';

?>

<div class="admin-assignment-view card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="container">
            <div class="input-group mb-3">
                <?= Html::textInput(null, null, [
                    'id' => 'inp-route',
                    'class' => 'form-control',
                    'placeholder' => Yii::t('rbac-admin', 'New route(s)'),
                ]) ?>
                <div class="input-group-append">
                    <span class="input-group-text">
                        <?= Html::a(Yii::t('rbac-admin', 'Add') .$animateIcon, ['create'],[
                            'id' => 'btn-new'
                        ]) ?>
                    </span>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-5">
                    <?= Html::textInput(null, null, [
                        'class' => 'form-control search',
                        'data-target' => 'available',
                        'placeholder' => Yii::t('rbac-admin', 'Search for available'),
                    ]) ?>
                    <select multiple size="20" class="form-control list" data-target="available"></select>
                </div>
                <div class="col-sm-2 text-center">
                    <br><br>
                    <?=Html::a('&gt;&gt;' . $animateIcon, ['assign', 'id' => (string) $model->id], [
                        'class' => 'btn btn-success btn-assign w-50',
                        'data-target' => 'available',
                        'title' => Yii::t('rbac-admin', 'Assign'),
                    ]);?><br><br>
                    <?=Html::a('&lt;&lt;' . $animateIcon, ['revoke', 'id' => (string) $model->id], [
                        'class' => 'btn btn-danger btn-assign w-50',
                        'data-target' => 'assigned',
                        'title' => Yii::t('rbac-admin', 'Remove'),
                    ]);?>
                </div>
                <div class="col-sm-5">
                    <?= Html::textInput(null, null, [
                        'class' => 'form-control search',
                        'data-target' => 'assigned',
                        'placeholder' => Yii::t('rbac-admin', 'Search for assigned'),
                    ]) ?>
                    <select multiple size="20" class="form-control list" data-target="assigned"></select>
                </div>
            </div>
        </div>
    </div>
</div>
