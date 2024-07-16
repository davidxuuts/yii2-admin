<?php

use davidxu\adminlte4\enums\ModalSizeEnum;
use davidxu\adminlte4\helpers\HtmlHelper;
use yii\data\ActiveDataProvider;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\grid\GridView;
use davidxu\admin\components\Helper;
use yii\web\View;
use yii\widgets\Pjax;
use davidxu\adminlte4\yii\grid\ActionColumn;

/**
 * @var $this View
 * @var $dataProvider ActiveDataProvider
 */

$this->title = Yii::t('rbac-admin', 'Users');
$this->params['breadcrumbs'][] = $this->title;

$currentUserId = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id;
?>
<div class="admin-user-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="bi bi-plus-circle-fill"></i> ' . Yii::t('rbac-admin', 'Create user'),
                ['ajax-edit'],
                [
                    'class' => 'btn btn-xs btn-primary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modal',
                    'title' => Yii::t('rbac-admin', 'Create user'),
                    'aria-label' => Yii::t('rbac-admin', 'Create user'),
                    'data-bs-modal-class' => ModalSizeEnum::SIZE_LARGE,
                ]
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="app-container">
            <?php Pjax::begin(); ?>
            <?= $this->render('../common/_search', [
                'placeholder' => Yii::t('rbac-admin', 'Search username/real name')
            ]) ?>
            <?php try {
                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => SerialColumn::class],
                        'username',
                        'realname',
                        [
                            'attribute' => 'roles',
                            'label' => Yii::t('rbac-admin', 'Roles'),
                            'format' => 'raw',
                            'value' => function ($model) {
                                $roles = Helper::getRoles($model->id) ?? [];
                                return $roles ? implode(', ', $roles) : '';
                            }
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'RAW',
                            'value' => function ($model) {
                                return HtmlHelper::displayStatus($model->status);
                            },
                        ],
                        [
                            'class' => ActionColumn::class,
                            'header' => Yii::t('rbac-admin', 'Operate'),
                            'template' => '{ajax-edit} {delete}',
                            'visibleButtons' => [
                                'delete' => function ($model, $key) use ($currentUserId) {
                                    return $key !== $currentUserId;
                                }
                            ],
                        ],
                    ],
                ]);
            } catch (Exception|Throwable $e) {
                 if (YII_ENV_DEV) {
                     echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                     echo $e->getTraceAsString() . "\n";
                 }
            } ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
