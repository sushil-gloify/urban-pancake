<?php

use yii\helpers\Html;
use yii\grid\GridView;

use app\subscription_api\v4\models\TicketsTopic;
use app\subscription_api\v4\models\HelpTracking;


$this->title = "Tickets List";
$this->params['breadcrumbs'][] = ['label' => 'TIckets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="airlines-view">
<h1><?= Html::encode($this->title) ?></h1>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'id' => 'index_order_list',
        'rowOptions'=>function($model){},
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute'=>'ticket_number',
                'header'=>'Ticket Id',
                'value' => function ($model) {
                     if($model->ticket_id){
                        return !empty($model->ticket_number) ? $model->ticket_number: '-' ;
                    }
                },
            ],
            [
                'attribute'=>'topic_name',
                'header'=>'Topic Name',
                'value' => function ($model) {
                $result = Yii::$app->db->createCommand("select topic_name from tbl_tickets_topic where topic_id = '".$model->topic_name."'")->queryone();
                    return ($result['topic_name']) ? ucwords($result['topic_name']) : "-";
                },

            ],
            [
                'attribute'=>'status',
                'header'=>'Status',
                'value' => function ($model) {
                   
                    if($model->status){
                        return ($model->status) ? $model->status: '-' ;
                    } 
                },
            ],
            [
                'attribute'=>'created_at',
                'header'=>'Created',
                'value' => function ($model) {
                    if($model->created_date){
                        return !empty($model->created_date) ?date("d-m-Y",strtotime($model->created_date)) : '-' ;

                    } 
                },
            ],
            [ 
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Action',
                'template' => '{view}',
                'buttons' => [
                    'view' => function ($url, $model) { 
                        return Html::a('<span  class="glyphicon glyphicon-eye-open"></span>',$url, 
                        ['title' => Yii::t('app', 'View'), 'class'=>'btn btn-primary btn-xs', ]) ;
                    },
                ]
            ],

        ]

    ]) ?>

</div>