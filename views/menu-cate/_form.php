<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use davidxu\admin\models\MenuCate;
use yii\helpers\Json;
use davidxu\base\enums\AppIdEnum;

/* @var $this yii\web\View */
/* @var $model davidxu\admin\models\MenuCate */
/* @var $form yii\bootstrap4\ActiveForm */
?>

<div class="admin-menu-cate-form card">
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
                <?= $form->field($model, 'title')->textInput(['maxlength' => 128]) ?>
                <?= $form->field($model, 'app_id')->dropdownList(AppIdEnum::getManagement(), [
                    'prompt' => Yii::t('rbac-admin', 'Please select app id')
                ]) ?>
                <?= $form->field($model, 'addon')->textInput() ?>
                <?= $form->field($model, 'order')->input('number') ?>
                <?= $form->field($model, 'icon')->textInput(['maxlength' => 50]) ?>
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
