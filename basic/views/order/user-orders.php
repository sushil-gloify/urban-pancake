<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use kartik\daterange\DateRangePicker;
/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Orders';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <!-- <p>
        <? //echo Html::a('Create Order', ['create'], ['class' => 'btn btn-success']) ?>
    </p> -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id_order',
            [
                'attribute'=>'fk_tbl_order_id_customer',
                'label'=>'Customer Name',
                'value' => function ($model) {
                    return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                },
            ],
            'order_number',
             [
                'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                'header'=>'Region',
                'format' => 'raw',
                'value' => function ($model) { 
                    return $model->getRegionName($model->fk_tbl_airport_of_operation_airport_name_id);
                },
            ],
            [
                'attribute' => 'date_created',
                'header' => 'Date Of Booking',
                'value' => function ($model) {
                    return  $model->date_created ? date('Y-m-d',strtotime($model->date_created)) : '0000-00-00';
                },
                'filter' => DateRangePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'date_created',
                    'convertFormat'=>true,
                    'pluginOptions'=>[
                        'locale'=>[
                            'format'=>'Y-m-d',
                            //'separator'=>'-',
                        ],
                        'opens'=>'left',
                    ]
                ]),
            ],
            [
                'attribute'=>'order_date',
                'label'=>'Date Of Service',
                'value' => function ($model) {
                    return date('Y-m-d', strtotime($model->order_date));
                },
            ],
            [
                'attribute'=>'meet_time_gate',
                'label'=>'Gate1 Meeting Time',
                'value' => function ($model) {
                    return date('h:i A', strtotime($model->meet_time_gate));
                },
            ],
            [
                'attribute' => 'service_type',
                'value' => function ($model) {
                    return ($model->service_type ==1) ? "To Airport": "From Airport" ;
                },
                'filter'=>array("1"=>"To Airport","2"=>"From Airport"),
            ],
            // 'fk_tbl_order_id_pick_drop_location',
            // 'no_of_units',
            // 'fk_tbl_order_id_slot',
            [
                'attribute'=>'fk_tbl_order_id_slot',
                'label'=>'Slot',
                'value' => function ($model) {
                    return !empty($model->fkTblOrderIdSlot) ? $model->fkTblOrderIdSlot->time_description : '-' ;
                },
            ],
            [
                'attribute'=>'assigned_porter',
                'label'=>'Porter',
                'value' => function ($model) { 
                    $vehicleslot=$model->vehicleSlotAllocations;
                    return !empty($vehicleslot) ? ( !empty($vehicleslot->fkTblVehicleSlotAllocationIdEmployee) ? $vehicleslot->fkTblVehicleSlotAllocationIdEmployee->name : '-') :  '-' ;
                },
            ],
            [
                'attribute'=>'assigned_porterx',
                'label'=>'Porterx',
                'value' => function ($model) { 
                    $porterx=$model->porterxAllocations; 
                    return !empty($porterx) ? ( !empty($porterx->porterxAllocationsIdEmployee) ? $porterx->porterxAllocationsIdEmployee->name : '-') :  '-' ;
                },
            ],
            [
                'attribute'=>'assigned_person',
                'label'=>'Assigned Person',
                'value' => function ($model) { //print_r($model->orderSpotDetails);exit;
                    //return $model->orderSpotDetails->assigned_person==1 ? $model->orderSpotDetails->person_name : 'Me' ;
                    return $model->travel_person==1 ? $model->travell_passenger_name : 'Me';
                },
            ],
            [
                'attribute'=>'assigned_person_verification',
                'format' => 'raw',
                //'label'=>'Assigned Person verification',
                'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                    //return $data->orderSpotDetails->assigned_person==1 ? $data->orderSpotDetails->documentVerification : '-' ;
                    return $data->travel_person==1 ? $data->documentVerification : '-' ;
                },
            ],
             'order_status',
            [
                'attribute'=>'related_order_id',
                'format' => 'raw',
                'label'=>'Related Order Status',
                'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                    return $data->related_order_id !=0 ? Html::a($data->relatedOrder->order_status,['/order/update','id'=>$data->relatedOrder->id_order]) : '-' ;
                },
            ],
            //['class' => 'yii\grid\ActionColumn'],
            [
                'class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width:80px;'],
                'header'=>'Actions',
                'template' => '{view}',
                'buttons' => [


                'view' => function ($url, $model) { 
                          return (
                          Html::a('<span class="glyphicon glyphicon-eye-open"></span>',
                          $url = Url::toRoute(['order/user-order-update','id'=>$model->id_order ]), 
                          [ 'title' => Yii::t('app', 'assign'), 'class'=>'', ])
                          );
                      },
                
                ]
            ],
        ],
    ]); ?>
</div>
