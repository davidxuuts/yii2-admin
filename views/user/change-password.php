<?php

use davidxu\admin\models\form\ChangePasswordForm;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var $this View
 * @var $form ActiveForm
 * @var $model ChangePasswordForm
 */

try {
$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'options' => [
        'class' => 'form-horizontal',
    ],
    'validationUrl' => Url::to(['change-password']),
    'fieldConfig' => [
        'options' => ['class' => 'form-group row mb-2'],
        'template' => "<div class='col-sm-2 text-end'>{label}</div>"
            . "<div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ]
]);
?>

<div class="modal-header">
    <h4 class="modal-title"><?= Yii::t('rbac-admin', 'Change password') ?></h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="container">
        <?= $form->field($model, 'oldPassword')->passwordInput() ?>
        <?= $form->field($model, 'newPassword')->passwordInput() ?>
        <?= $form->field($model, 'retypePassword')->passwordInput() ?>
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
        <?= Html::button(Yii::t('app', 'Close'), [
            'class' => 'btn btn-secondary',
            'data-bs-dismiss' => 'modal'
        ]) ?>
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end();

