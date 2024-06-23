<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/** @var string $placeholder */
?>

<div class="row">
    <div class="col text-right pb-3">
        <?= Html::beginForm(['index'], 'get') ?>
            <div class="input-group input-group-sm">
                <?= Html::input('text', 'key', '', [
                    'class' => 'form-control input-sm input-sm-2',
                    'placeholder' => $placeholder
                ])?>
                <?= Html::submitButton('<i class="b bi-search"></i>', [
                    'class' => 'btn btn-sm btn-secondary'
                ]) ?>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>