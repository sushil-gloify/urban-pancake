<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SuperSubscriptionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Super Subscriptions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="super-subscription-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Super Subscription', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            // 'subscription_id',
            'subscriber_name',
            'register_address',
            'subscription_area',
            'subscription_pincode',
            // [
            //     'attribute' => '',
            //     'header' => 'Purchase Count',
            //     'value' => function($model) {
            //         $res = Yii::$app->Common->getPurchessCount($model->subscription_id);
            //         return $res. " Purchased"; 
            //     }
            // ],
            //'primary_contact',
            //'secondary_contact',
            //'primary_email:email',
            //'secondary_email:email',
            //'subscription_GST',
            //'subscriber_logo:ntext',
            [
                'attribute' => 'subscriber_status',
                'header' => 'Status',
                'value' => function($model) {
                    return ($model->subscriber_status) ? ucwords($model->subscriber_status) : "-";
                },
                'filter' => array("enable"=>"Enable","disable"=>"Disable")
            ],
            //'create_date',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
