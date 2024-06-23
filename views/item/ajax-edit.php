<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

use yii\base\InvalidConfigException;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;
use yii\web\View;
use yii\helpers\Html;
use davidxu\admin\models\searchs\Menu;

/**
 * @var $this View
 * @var $model Menu
 * @var $form ActiveForm
 * @var $data array
 */

$context = $this->context;
$labels = $context->labels();
try {
    $form = ActiveForm::begin([
        'id' => $model->formName(),
        'enableAjaxValidation' => true,
        'options' => [
            'class' => 'form-horizontal',
        ],
        'validationUrl' => Url::to(['ajax-edit', 'id' => $model->name]),
        'fieldConfig' => [
            'options' => ['class' => 'form-group row mb-2'],
            'template' => "<div class='col-sm-2 text-end'>{label}</div>"
                . "<div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
        ]
    ]);
    ?>

    <div class="modal-header">
        <h4 class="modal-title"><?= Yii::t('rbac-admin', 'Edit ' . $labels['Item']) ?></h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="container">
            <?= $form->field($model, 'name')->textInput(['maxlength' => 64]) ?>
            <?= $form->field($model, 'ruleName')->textInput(['id' => 'rule_name']) ?>
            <?= $form->field($model, 'description')->textarea(['rows' => 2]) ?>
            <?= $form->field($model, 'data')->textarea(['rows' => 6]) ?>
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
