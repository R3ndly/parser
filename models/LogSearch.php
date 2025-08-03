<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;

class LogSearch extends Model
{
    public $ip;
    public $dateFrom;
    public $dateTo;
    public $osName;
    public $architecture;
    public $browserName;
    
    public function rules()
    {
        return [
            [['ip', 'osName', 'architecture', 'browserName'], 'safe'],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }
    
    public function search($params)
    {
        $query = Log::find()
            ->joinWith(['os', 'browser', 'url']);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['request_date' => SORT_DESC],
            ],
        ]);
        
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        
        $query->andFilterWhere(['like', 'log.ip', $this->ip])
              ->andFilterWhere(['>=', 'log.request_date', $this->dateFrom])
              ->andFilterWhere(['<=', 'log.request_date', $this->dateTo])
              ->andFilterWhere(['os.name' => $this->osName])
              ->andFilterWhere(['os.architecture' => $this->architecture])
              ->andFilterWhere(['browser.name' => $this->browserName]);
        
        return $dataProvider;
    }

    public function getBaseAggregatedQuery()
    {
        $query = Log::find()
            ->select([
                'DATE(log.request_date) as date',
                'COUNT(*) as count',
            ])
            ->joinWith(['os', 'browser', 'url'])
            ->groupBy('DATE(log.request_date)')
            ->orderBy(['date' => SORT_DESC]);

        if ($this->validate()) {
            $query->andFilterWhere(['>=', 'log.request_date', $this->dateFrom])
                  ->andFilterWhere(['<=', 'log.request_date', $this->dateTo])
                  ->andFilterWhere(['os.name' => $this->osName])
                  ->andFilterWhere(['os.architecture' => $this->architecture]);
        }

        return $query;
    }

    public function searchAggregated($params)
    {
        $this->load($params);
        
        $baseQuery = $this->getBaseAggregatedQuery();

        $rows = $baseQuery->asArray()->all();

        if (empty($rows)) {
            return new ArrayDataProvider([
                'allModels' => [],
            ]);
        }

        $dates = array_column($rows, 'date');

        $popularUrls = (new Query())
            ->select([
                'DATE(l.request_date) as date',
                'u.path',
                'COUNT(*) as cnt',
            ])
            ->from('log l')
            ->innerJoin('url u', 'l.url_id = u.id')
            ->where(['IN', new \yii\db\Expression('DATE(l.request_date)'), $dates])
            ->groupBy(['date', 'l.url_id'])
            ->orderBy(['cnt' => SORT_DESC])
            ->all();

        $popularUrlByDate = [];
        foreach ($popularUrls as $row) {
            if (!isset($popularUrlByDate[$row['date']])) {
                $popularUrlByDate[$row['date']] = $row['path'];
            }
        }

        $popularBrowsers = (new Query())
            ->select([
                'DATE(l.request_date) as date',
                'b.name',
                'COUNT(*) as cnt',
            ])
            ->from('log l')
            ->innerJoin('browser b', 'l.browser_id = b.id')
            ->where(['IN', new \yii\db\Expression('DATE(l.request_date)'), $dates])
            ->groupBy(['date', 'l.browser_id'])
            ->orderBy(['cnt' => SORT_DESC])
            ->all();

        $popularBrowserByDate = [];
        foreach ($popularBrowsers as $row) {
            if (!isset($popularBrowserByDate[$row['date']])) {
                $popularBrowserByDate[$row['date']] = $row['name'];
            }
        }

        $finalRows = [];
        foreach ($rows as $row) {
            $date = $row['date'];
            $finalRows[] = [
                'date' => $date,
                'count' => $row['count'],
                'popular_url' => $popularUrlByDate[$date] ?? null,
                'popular_browser' => $popularBrowserByDate[$date] ?? null,
            ];
        }

        return new ArrayDataProvider([
            'allModels' => $finalRows,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['date' => SORT_DESC],
                'attributes' => ['date', 'count', 'popular_url', 'popular_browser'],
            ],
        ]);
    }
}

