<?php

namespace davidxu\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * DefaultController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DefaultController extends Controller
{

    /**
     * Action index
     */
    public function actionIndex($page = 'README.md'): Response|string|\yii\console\Response
    {
        if (preg_match('/^docs\/images\/image\d+\.png$/',$page)) {
            $file = Yii::getAlias("@davidxu/admin/$page");
            return Yii::$app->getResponse()->sendFile($file);
        }
        return $this->render('index', ['page' => $page]);
    }
}
