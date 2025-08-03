<?php

namespace app\controllers;

use Yii;
use app\models\Log;
use app\models\LogSearch;

class LogController extends \yii\web\Controller
{
    public function actionIndex()
    {
        $searchModel = new LogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]); 
    }

}
