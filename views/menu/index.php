<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use davidxu\adminlte4\yii\grid\ActionColumn;
use yii\web\View;
use yii\data\ActiveDataProvider;
use davidxu\adminlte4\enums\ModalSizeEnum;
use davidxu\admin\models\searchs\Menu;
use davidxu\treegrid\TreeGrid;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */

$this->title = Yii::t('rbac-admin', 'Menus');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-menu-index card card-outline card-secondary">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title); ?> </h4>
        <div class="card-tools">
            <?= Html::a('<i class="bi bi-plus-circle-fill"></i> ' . Yii::t('rbac-admin', 'Create menu'),
                ['ajax-edit'],
                [
                    'class' => 'btn btn-sm btn-primary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modal',
                    'title' => Yii::t('rbac-admin', 'Create menu'),
                    'aria-label' => Yii::t('rbac-admin', 'Create menu'),
                    'data-bs-modal-class' => ModalSizeEnum::SIZE_LARGE,
                ]
            ) ?>
        </div>
    </div>
    <div class="card-body pt-3 pl-0 pr-0">
        <div class="app-container">
            <?php Pjax::begin(); ?>
<!--            --><?php //= $this->render('../common/_search', [
//                'placeholder' => Yii::t('rbac-admin', 'Search name/parent name')
//            ]) ?>
            <?php try {
                echo TreeGrid::widget([
                    'dataProvider' => $dataProvider,
                    'keyColumnName' => 'id',
                    'parentColumnName' => 'parent',
                    'parentRootValue' => null, //first parentId value
                    'pluginOptions' => [
                        'initialState' => 'collapsed',
                    ],
                    'options' => ['class' => 'table table-hover pt-3'],
                    'columns' => [
                        [
                            'attribute' => 'name',
                            'format' => 'RAW',
                            'value' => function ($model) {
                                /** @var Menu $model */
                                $icon = $model->data ? '<i class="bi bi-' . $model->data . '"></i> ' : '';
                                $str = Html::tag('span', $icon . $model->name);
                                if (!$model->parent) {
                                    $str .= Html::a(' <i class="bi bi-plus-circle-fill"></i>',
                                        ['ajax-edit', 'parent' => $model->id], [
                                            'title' => Yii::t('rbac-admin', 'Edit'),
                                            'arial-label' => Yii::t('rbac-admin', 'Edit'),
                                            'data-bs-toggle' => 'modal',
                                            'data-bs-target' => '#modal',
                                            'data-bs-modal-class' => ModalSizeEnum::SIZE_LARGE,
                                        ]);
                                }
                                return $str;
                            }
                        ],
                        'order',
                        [
                            'header' => Yii::t('rbac-admin', 'Operate'),
                            'class' => ActionColumn::class,
                            'template' => '{ajax-edit} {delete}',
                        ],
                    ]
                ]);
            } catch (Exception $e) {
                if (YII_ENV_DEV) {
                    echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                    echo $e->getTraceAsString() . "\n";
                }
            } ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
