<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

use davidxu\admin\models\form\UserForm;
use kartik\select2\Select2;
use yii\base\InvalidConfigException;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;
use yii\web\View;
use yii\helpers\Html;

/**
 * @var $this View
 * @var $model UserForm
 * @var $form ActiveForm
 * @var $authItems array
 */

$hint = $model->isNewUser
    ? Yii::t('rbac-admin', 'If empty, random initial password will be generated (suggested)')
    : Yii::t('rbac-admin', 'If change password is not need, please keep empty here (suggested)');

try {
    $form = ActiveForm::begin([
        'id' => $model->formName(),
        'enableAjaxValidation' => true,
        'options' => [
            'class' => 'form-horizontal',
        ],
        'validationUrl' => Url::to(['ajax-edit', 'id' => $model->id]),
        'fieldConfig' => [
            'options' => ['class' => 'form-group row mb-2'],
            'template' => "<div class='col-sm-2 text-end'>{label}</div>"
                . "<div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
        ]
    ]);
    ?>

    <div class="modal-header">
        <h4 class="modal-title"><?= Yii::t('rbac-admin', 'Edit user') ?></h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="container">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'readonly' => !$model->isNewUser])
                ->hint(Yii::t('rbac-admin', 'Can not modify username after account created')) ?>
            <?= $form->field($model, 'realname')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'password')->passwordInput()->hint($hint) ?>
            <?= $form->field($model, 'roles')->widget(Select2::class, [
                'data' => $authItems,
                'options' => [
                    'placeholder' => Yii::t('rbac-admin', '-- Select role --'),
                    'multiple' => true,
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'dropdownParent' => '#modal',
                    'tags' => true,
                    'tokenSeparators' => [',', ' '],
                ],
            ])->label(Yii::t('rbac-admin', 'Role')); ?>

<!--            --><?php //= $form->field($model, 'username')->textInput() ?>
<!--            --><?php //= $form->field($model, 'realname')->textInput() ?>
<!--            --><?php //= $form->field($model, 'email')->textInput() ?>
<!--            --><?php //= $form->field($model, 'password')->passwordInput() ?>
        </div>
    </div>
    <?php
} catch (Exception|InvalidConfigException $e) {
    if (YII_ENV_DEV) {
        echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
        echo $e->getTraceAsString() . "\n";
    }
}
?>
    <div class="modal-footer">
        <?= Html::button(Yii::t('rbac-admin', 'Close'), [
            'class' => 'btn btn-secondary',
            'data-bs-dismiss' => 'modal'
        ]) ?>
        <?= Html::submitButton(Yii::t('rbac-admin', 'Save'), ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end();
