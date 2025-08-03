<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LogSearch;
use yii\helpers\ArrayHelper;

class LogController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new LogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        $requestsByDate = $this->getRequestsByDateData($searchModel);
        $browserShareData = $this->getBrowserShareData($searchModel);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'requestsByDate' => $requestsByDate,
            'browserShareData' => $browserShareData,
        ]);
    }

    protected function getRequestsByDateData($searchModel)
    {
        $query = clone $searchModel->getQuery();
        $query->select([
            'DATE(request_date) as date',
            'COUNT(*) as count'
        ])->groupBy('DATE(request_date)');
        
        return $query->asArray()->all();
    }

    protected function getBrowserShareData($searchModel)
    {
        $query = clone $searchModel->getQuery();
        $query->select([
            'DATE(request_date) as date',
            'browser.name as browser',
            'COUNT(*) as count'
        ])
        ->groupBy('DATE(request_date), browser.name')
        ->orderBy('date, count DESC');
        
        $rawData = $query->asArray()->all();
        $result = [];
        $dates = array_unique(ArrayHelper::getColumn($rawData, 'date'));
        
        foreach ($dates as $date) {
            $dateData = array_filter($rawData, function($item) use ($date) {
                return $item['date'] === $date;
            });
            
            $total = array_sum(ArrayHelper::getColumn($dateData, 'count'));
            $topBrowsers = array_slice($dateData, 0, 3);
            
            foreach ($topBrowsers as $browserData) {
                $result[] = [
                    'date' => $date,
                    'browser' => $browserData['browser'],
                    'percentage' => round(($browserData['count'] / $total) * 100, 2)
                ];
            }
        }
        
        return $result;
    }
}
