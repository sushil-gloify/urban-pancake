<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\URL;

/* @var $this yii\web\View */
/* @var $searchModel app\api_v3\v3\models\ThirdpartyCorporateAirportsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
?>
<a href="index.php?r=thirdparty-corporate-airports/airport-index&id=<?= $_GET['id'] ?>"> <button type="button" class="btn btn-primary" >Local</button></a>
<a href="index.php?r=thirdparty-corporate-airports/airport-outstation-index&id=<?= $_GET['id'] ?>"><button type="button" class="btn btn-outline-primary">Out Station</button> </a>
<?php 
$this->title = 'Thirdparty Corporate Airports Price';
$this->params['breadcrumbs'][] = $this->title;
?>
<br>
<div id="session_msg">
    <?php if(Yii::$app->session->hasFlash('success')) {?>
    <div class="alert alert-success" role="alert">
        <?= Yii::$app->session->getFlash('success'); ?>
    </div>
    <?php  } else if (Yii::$app->session->hasFlash('error')){ ?>
    <div class="alert alert-danger" role="alert">
        <?= Yii::$app->session->getFlash('error'); ?>
    </div>
    <?php } else if (Yii::$app->session->hasFlash('warning')){ ?>
        <div class="alert alert-warning" role="alert">
        <?= Yii::$app->session->getFlash('warning'); ?>
    </div>
    <?php } ?>
</div>
<div class="thirdparty-corporate-airports-index">

    <h3><?= Html::encode($this->title) ?></h3>
   
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'thirdparty_corporate_airport_id',
            //'thirdparty_corporate_id',
            // [
            //     'attribute'=>'thirdparty_corporate_id',
            //     'label' => 'Thirdparty Corporate',
            //     'value' => function($model){
            //         $name = $model->getThirdpartyCorporateName($model->thirdparty_corporate_id);
            //         return $name;
            //     }
            // ],
           // 'airport_id',
            [
                'attribute'=>'airport_id',
                'label' => 'Airport',
                'value' => function($model){
                    $name = $model->getAirportName($model->airport_id);
                    return $name;
                }
            ],
            [
                'attribute'=>'bag_price',
                'label' => 'Base Price',
                'value' => function($model){
                    return (isset($model->airport->bag_price)) ? $model->airport->bag_price : '';
                }
            ],
            // 'created_on',
            // 'modified_on',

            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Actions',
                'template' => '{set_price} {delete}',
                'buttons' => [
                    'set_price' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-plus"></span>',
                          $url = Url::toRoute(['thirdparty-corporate-airports/update-price','id'=>$model->thirdparty_corporate_airport_id ]), 
                          [ 'title' => Yii::t('app', 'Price Setup'), 'class'=>'', ])
                        );
                    },

                    'delete' => function ($url, $model) {
                        // return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-airport-price','id'=>$model->thirdparty_corporate_airport_id,'corporateId'=>$_GET['id']]),['title' => Yii::t('app','Delete'),'class' => '']));
                        return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-airport-region-price','id'=>$model->thirdparty_corporate_airport_id,'corporateId'=>$_GET['id'],'type'=>'airport']),['title' => Yii::t('app','Delete'),'class' => '']));
                    },
                ] 
            ],
        ],
    ]); ?>

    <h3><?= 'Thirdparty Corporate City Price' ?></h3>
    <?= GridView::widget([
        'dataProvider' => $regiondataProvider,
        'filterModel' => $searchregionModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'thirdparty_corporate_city_id',
            //'thirdparty_corporate_id',
            // [
            //     'attribute' => 'thirdparty_corporate_id',
            //     'label' => 'Thridparty Corporate',
            //     'value' => function($model){
            //         $name = $model->getThirdpartyCorporateName($model->thirdparty_corporate_id);
            //         return $name;
            //     }
            // ],
            //'city_region_id',
            [
                'attribute' => 'city_region_id',
                'label' => 'City Region ',
                'value' => function($model){
                    $name = $model->getCityName($model->city_region_id);
                    return $name;
                }
            ],
            [
                'attribute'=>'bag_price',
                'label' => 'Base Price',
                'value' => function($model){
                    return (isset($model->airport->bag_price)) ? $model->airport->bag_price : '';
                }
            ],
            // 'created_on',
            // 'modified_on',

            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Actions',
                'template' => '{set_price} {delete}',
                'buttons' => [
                    'set_price' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-plus"></span>',
                          $url = Url::toRoute(['thirdparty-corporate-city-region/update','id'=>$model->thirdparty_corporate_city_id ]), 
                          [ 'title' => Yii::t('app', 'City Price Setup'), 'class'=>'', ])
                        );
                    },
                    'delete' => function ($url, $model) {
                        // return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-region-price','id'=>$model->thirdparty_corporate_city_id,'corporateId'=>$_GET['id']]),['title' => Yii::t('app','Delete'),'class' => '']));
                        return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-airport-region-price','id'=>$model->thirdparty_corporate_city_id,'corporateId'=>$_GET['id'],'type'=>'region']),['title' => Yii::t('app','Delete'),'class' => '']));
                    },
                ] 
            ],
        ],
    ]); ?>

<?php 
$this->title = 'Corporate Discount Price';
$this->params['breadcrumbs'][] = $this->title;
?>
</div>
<div class="thirdparty-corporate-airports-index">

    <h3><?= Html::encode($this->title) ?></h3>
    <?= GridView::widget([
        'dataProvider' => $discountairportdataProvider,
        'filterModel' => $searchdiscountairportModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute'=>'airport_id',
                'label' => 'Airport',
                'value' => function($model){
                    $name = $model->getAirportName($model->airport_id);
                    return $name;
                }
            ],
            [
                'attribute'=>'bag_price',
                'label' => 'Base Price',
                'value' => function($model){
                    return (isset($model->discountAirport->bag_price)) ? $model->discountAirport->bag_price : '';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Actions',
                'template' => '{set_price} {delete}',
                'buttons' => [
                    'set_price' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-plus"></span>',
                          $url = Url::toRoute(['thirdparty-corporate-airports/update-corporate-price','id'=>$model->thirdparty_corporate_airport_id ]), 
                          [ 'title' => Yii::t('app', 'Price Setup'), 'class'=>'', ])
                        );
                    },
                    'delete' => function ($url, $model) {
                        // return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-discount-airport-price','id'=>$model->thirdparty_corporate_airport_id,'corporateId'=>$_GET['id']]),['title' => Yii::t('app','Delete'),'class' => '']));
                        return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-airport-region-price','id'=>$model->thirdparty_corporate_airport_id,'corporateId'=>$_GET['id'],'type'=>'discount-airport']),['title' => Yii::t('app','Delete'),'class' => '']));
                    },
                ] 
            ],
        ],
    ]); ?>

    <h3><?= 'Corporate Discount City Price' ?></h3>
    <?= GridView::widget([
        'dataProvider' => $discountregiondataProvider,
        'filterModel' => $searchdiscountregionModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'city_region_id',
                'label' => 'City Region ',
                'value' => function($model){
                    $name = $model->getCityName($model->city_region_id);
                    return $name;
                }
            ],
            [
                'attribute'=>'bag_price',
                'label' => 'Base Price',
                'value' => function($model){
                    return (isset($model->discountRegion->bag_price)) ? $model->discountRegion->bag_price : '';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Actions',
                'template' => '{set_price} {delete}',
                'buttons' => [
                    'set_price' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-plus"></span>',
                          $url = Url::toRoute(['thirdparty-corporate-city-region/discount-update','id'=>$model->thirdparty_corporate_city_id ]), 
                          [ 'title' => Yii::t('app', 'City Price Setup'), 'class'=>'', ])
                        );
                    },
                    'delete' => function ($url, $model) {
                        // return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-discount-region-price','id'=>$model->thirdparty_corporate_city_id,'corporateId'=>$_GET['id']]),['title' => Yii::t('app','Delete'),'class' => '']));
                        return (Html::a('<span class="glyphicon glyphicon-trash"></span>', $url = Url::toRoute(['thirdparty-corporate-airports/delete-airport-region-price','id'=>$model->thirdparty_corporate_city_id,'corporateId'=>$_GET['id'],'type'=>'discount-region']),['title' => Yii::t('app','Delete'),'class' => '']));
                    },
                ] 
            ],
        ],
    ]); ?>
</div>
<script>
    setTimeout(function(){
        $("#session_msg").delay(3000).fadeOut();
    },1000);    
</script>
