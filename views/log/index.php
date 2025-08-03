<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;
use app\models\Os;
use dosamigos\highcharts\HighCharts;

$this->title = 'Анализ логов Nginx';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="log-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="panel panel-default">
        <div class="panel-heading">Фильтры</div>
        <div class="panel-body">
            <?php $form = ActiveForm::begin(['method' => 'get']); ?>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'dateFrom')->input('date') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'dateTo')->input('date') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'osName')->dropDownList(
                        Os::find()->select('name')->distinct()->indexBy('name')->column(),
                        ['prompt' => 'Все ОС']
                    ) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'architecture')->dropDownList(
                        ['x86' => 'x86', 'x64' => 'x64'],
                        ['prompt' => 'Все архитектуры']
                    ) ?>
                </div>
            </div>

            <div class="form-group">
                <?= Html::submitButton('Применить', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-default']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?= HighCharts::widget([
                'clientOptions' => [
                    'title' => ['text' => 'Запросы по датам'],
                    'xAxis' => [
                        'title' => ['text' => 'Ось X – дата'],
                        'categories' => ArrayHelper::getColumn($requestsByDate, 'date'),
                    ],
                    'yAxis' => [
                        'title' => ['text' => 'Ось Y – число запросов'],
                    ],
                    'series' => [
                        ['name' => 'Запросы', 'data' => ArrayHelper::getColumn($requestsByDate, 'count')],
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
            <?php
            $browserSeries = [];
            $browsers = array_unique(ArrayHelper::getColumn($browserShareData, 'browser'));

            foreach ($browsers as $browser) {
                $browserData = array_filter($browserShareData, function($item) use ($browser) {
                    return $item['browser'] === $browser;
                });
                $browserSeries[] = [
                    'name' => $browser,
                    'data' => ArrayHelper::getColumn($browserData, 'percentage'),
                ];
            }
            ?>

            <?= HighCharts::widget([
                'clientOptions' => [
                    'title' => ['text' => 'Доля браузеров (%)'],
                    'xAxis' => [
                        'title' => ['text' => 'Ось X – дата'],
                        'categories' => array_unique(ArrayHelper::getColumn($browserShareData, 'date')),
                    ],
                    'yAxis' => [
                        'title' => ['text' => 'Ось Y - % числа запросов'],
                        'max' => 100,
                    ],
                    'plotOptions' => [
                        'series' => ['stacking' => 'percent']
                    ],
                    'series' => $browserSeries,
                ]
            ]); ?>
        </div>
    </div>

    <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
            <h3>Статистика по дням</h3>
            <?php Pjax::begin(); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    [
                        'attribute' => 'date',
                        'label' => 'Дата',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDate($model['date']);
                        },
                        'enableSorting' => true,
                    ],
                    [
                        'attribute' => 'count',
                        'label' => 'Число запросов',
                        'enableSorting' => true,
                    ],
                    [
                        'attribute' => 'popular_url',
                        'label' => 'Самый популярный URL',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::a(Html::encode($model['popular_url']), $model['popular_url'], ['target' => '_blank']);
                        },
                        'enableSorting' => false,
                    ],
                    [
                        'attribute' => 'popular_browser',
                        'label' => 'Самый популярный браузер',
                        'enableSorting' => true,
                    ],
                ],
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

