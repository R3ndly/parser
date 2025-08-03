<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

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
}
