<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use davidxu\admin\components\RouteRule;
use davidxu\admin\AutocompleteAsset;
use yii\helpers\Json;
use davidxu\admin\components\Configs;
use yii\web\View;
use davidxu\admin\models\AuthItem;
use davidxu\admin\components\ItemController;

/**
 * @var $this View
 * @var $model AuthItem
 * @var $form ActiveForm
 * @var $context ItemController
 */

$context = $this->context;
$labels = $context->labels();
$rules = Configs::authManager()->getRules();
unset($rules[RouteRule::RULE_NAME]);
$source = Json::htmlEncode(array_keys($rules));

$js = <<<JS
    $('#rule_name').autocomplete({
        source: $source,
    });
JS;
AutocompleteAsset::register($this);
$this->registerJs($js);
?>

<div class="admin-auth-item-form card">
    <?php
    try {
        $form = ActiveForm::begin([
            'layout' => 'horizontal',
            'id' => 'item-form',
            'fieldConfig' => [
                'horizontalCssClasses' => [
                    'label' => 'col-sm-3 text-end',
                    'offset' => 'offset-sm-3',
                    'wrapper' => 'col-sm-9',
                ],
            ]
        ]); ?>
        <div class="card-body pt-3 pl-0 pr-0">
            <div class="container">
                <?= $form->field($model, 'name')->textInput(['maxlength' => 64]) ?>
                <?= $form->field($model, 'ruleName')->textInput(['id' => 'rule_name']) ?>
                <?= $form->field($model, 'description')->textarea(['rows' => 2]) ?>
                <?= $form->field($model, 'data')->textarea(['rows' => 6]) ?>
            </div>
        </div>
        <div class="card-footer text-end">
            <?= Html::submitButton('<i class="bi bi-floppy2-fill"></i> ' . Yii::t('app', 'Save'),
                [
                    'class' => 'btn btn-success',
                    'name' => 'submit-button',
                ]
            ) ?>
        </div>
        <?php ActiveForm::end();
    } catch (\Exception|Throwable $e) {
        if (YII_ENV_DEV) {
            echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
            echo $e->getTraceAsString() . "\n";
        }
    } ?>
</div>
