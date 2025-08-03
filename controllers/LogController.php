<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use app\models\LogSearch;

class LogController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new LogSearch();
        $params = Yii::$app->request->queryParams;

        $dataProvider = $searchModel->searchAggregated($params);

       $requestsByDate = $searchModel->getBaseAggregatedQuery()->asArray()->all(); 

        $browserShareData = $this->getBrowserShareData($searchModel, $params);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'requestsByDate' => $requestsByDate,
            'browserShareData' => $browserShareData,
        ]);
    }

protected function getBrowserShareData($searchModel, $params)
{
    $searchModel->load($params);

    $query = \app\models\Log::find()
        ->select([
            'DATE(request_date) as date',
            'browser.name as browser',
            'COUNT(*) as cnt',
        ])
        ->joinWith('browser')
        ->groupBy(['date', 'browser.name'])
        ->orderBy(['date' => SORT_ASC])
        ->asArray();

    if ($searchModel->validate()) {
        if ($searchModel->dateFrom) {
            $query->andWhere(['>=', 'request_date', $searchModel->dateFrom]);
        }
        if ($searchModel->dateTo) {
            $query->andWhere(['<=', 'request_date', $searchModel->dateTo]);
        }
        if ($searchModel->osName) {
            $query->joinWith('os')->andWhere(['os.name' => $searchModel->osName]);
        }
        if ($searchModel->architecture) {
            $query->joinWith('os')->andWhere(['os.architecture' => $searchModel->architecture]);
        }
    }

    $rows = $query->all();

    $dates = array_unique(array_column($rows, 'date'));
    sort($dates);

    $browsersCount = [];
    $totalPerDate = [];

    foreach ($rows as $row) {
        $date = $row['date'];
        $browser = $row['browser'];
        $count = (int)$row['cnt'];

        $totalPerDate[$date] = isset($totalPerDate[$date]) ? $totalPerDate[$date] + $count : $count;
        if (!isset($browsersCount[$browser])) {
            $browsersCount[$browser] = [];
        }
        $browsersCount[$browser][$date] = $count;
    }

    $browserShareData = [];

    $sumByBrowser = array_map(function($arr) {
        return array_sum($arr);
    }, $browsersCount);

    arsort($sumByBrowser);

    $topBrowsers = array_slice(array_keys($sumByBrowser), 0, 3);

    foreach ($topBrowsers as $browser) {
        foreach ($dates as $date) {
            $count = $browsersCount[$browser][$date] ?? 0;
            $total = $totalPerDate[$date] ?? 1;
            $percentage = $total ? ($count / $total) * 100 : 0;

            $browserShareData[] = [
                'date' => $date,
                'browser' => $browser,
                'percentage' => round($percentage, 2),
            ];
        }
    }

    return $browserShareData;
}

}

