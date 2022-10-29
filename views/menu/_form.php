<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use davidxu\admin\models\Menu;
use yii\helpers\Json;
use davidxu\admin\AutocompleteAsset;
use davidxu\base\assets\JqueryMigrateAsset;

/* @var $this yii\web\View */
/* @var $model davidxu\admin\models\Menu */
/* @var $form yii\bootstrap4\ActiveForm */
/* @var $menuCateDropdownList array */
JqueryMigrateAsset::register($this);
AutocompleteAsset::register($this);
$opts = Json::htmlEncode([
        'menus' => Menu::getMenuSource(),
        'routes' => Menu::getSavedRoutes(),
    ]);
$this->registerJs("var _opts = $opts;");
$this->registerJs($this->render('_script.js'));
?>

<div class="admin-menu-form card">
    <?php
    try {
        $form = ActiveForm::begin([
            'layout' => 'horizontal',
            'id' => 'item-form',
            'fieldConfig' => [
                'horizontalCssClasses' => [
                    'label' => 'col-sm-3 text-right',
                    'offset' => 'offset-sm-3',
                    'wrapper' => 'col-sm-9',
                ],
            ]
        ]); ?>
        <div class="card-body pt-3 pl-0 pr-0">
            <div class="container">
                <?= Html::activeHiddenInput($model, 'parent', ['id' => 'parent_id']); ?>
                <?= $form->field($model, 'name')->textInput(['maxlength' => 128]) ?>
                <?= $form->field($model, 'cate_id')->dropdownList($menuCateDropdownList) ?>
                <?= $form->field($model, 'parent_name')->textInput(['id' => 'parent_name']) ?>
                <?= $form->field($model, 'route')->textInput(['id' => 'route']) ?>
                <?= $form->field($model, 'order')->input('number') ?>
                <?= $form->field($model, 'data')->textarea(['rows' => 4]) ?>
            </div>
        </div>
        <div class="card-footer text-right">
            <?= Html::submitButton('<i class="fas fa-save"></i> ' . Yii::t('app', 'Save'),
                [
                    'class' => 'btn btn-success',
                    'name' => 'submit-button',
                ]
            ) ?>
        </div>
        <?php ActiveForm::end();
    } catch (\Exception|Throwable $e) {
        echo YII_ENV_PROD ? null : $e->getMessage();
    } ?>
</div>
