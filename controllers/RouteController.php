<?php

namespace davidxu\admin\controllers;

use Yii;
use davidxu\admin\models\Route;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\filters\VerbFilter;

/**
 * Description of RouteController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @author David XU <david.xu.uts@163.com>
 * @since 1.0
 */
class RouteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
                    'assign' => ['post'],
                    'remove' => ['post'],
                    'refresh' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Route models.
     * @return string
     * @throws InvalidConfigException
     */
    public function actionIndex(): string
    {
        $model = new Route();
        return $this->render('index', ['routes' => $model->getRoutes()]);
    }

    /**
     * Creates a new Route model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return array
     * @throws InvalidConfigException
     */
    public function actionCreate(): array
    {
        Yii::$app->getResponse()->format = 'json';
        $routes = Yii::$app->getRequest()->post('route', '');
        $routes = preg_split('/\s*,\s*/', trim($routes), -1, PREG_SPLIT_NO_EMPTY);
        $model = new Route();
        $model->addNew($routes);
        return $model->getRoutes();
    }

    /**
     * Assign routes
     * @return array
     * @throws InvalidConfigException
     */
    public function actionAssign(): array
    {
        $routes = Yii::$app->getRequest()->post('routes', []);
        $model = new Route();
        $model->addNew($routes);
        Yii::$app->getResponse()->format = 'json';
        return $model->getRoutes();
    }

    /**
     * Remove routes
     * @return array
     * @throws InvalidConfigException
     */
    public function actionRemove(): array
    {
        $routes = Yii::$app->getRequest()->post('routes', []);
        $model = new Route();
        $model->remove($routes);
        Yii::$app->getResponse()->format = 'json';
        return $model->getRoutes();
    }

    /**
     * Refresh cache
     * @return array
     * @throws InvalidConfigException
     */
    public function actionRefresh(): array
    {
        $model = new Route();
        $model->invalidate();
        Yii::$app->getResponse()->format = 'json';
        return $model->getRoutes();
    }
}
