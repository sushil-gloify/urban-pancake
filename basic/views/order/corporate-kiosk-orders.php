<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use app\models\OrderStatus;
use app\models\User;
use yii\helpers\Url;
use kartik\daterange\DateRangePicker;
use app\components\Common;
use app\models\ThirdpartyCorporate;
/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
$id_employee = Yii::$app->user->identity->id_employee;
$client_id =$client['client_id'];

$this->title = 'Orders';
//$this->params['breadcrumbs'][] = $this->title;

//print_r(Yii::$app->user->identity->name);exit;
?>
<style>
    .table > thead > tr > td.green, .table > tbody > tr > td.green, .table > tfoot > tr > td.green, .table > thead > tr > th.green, .table > tbody > tr > th.green, .table > tfoot > tr > th.green, .table > thead > tr.green > td, .table > tbody > tr.green > td, .table > tfoot > tr.green > td, .table > thead > tr.green > th, .table > tbody > tr.green > th, .table > tfoot > tr.green > th {
         background-color: #00b200;
     }
</style>
<script type="text/javascript">
    var checkRazoreStatus = function(id){
        $.get( "index.php?r=employee/check-razorpay-status",{order_id:id,status:'single'}).done(function( data ) {
        });
    }    
</script>
<div class="order-index" style="padding-top: 30px;">
    <?php if (Yii::$app->session->getFlash('msg')) { ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">
            <iclass="glyphicon glyphicon-remove-sign"></i>
        </button>
        <i class="icon fa fa-check"></i>
        <?= Yii::$app->session->getFlash('msg'); ?>
    </div>
    <?php } else if (Yii::$app->session->getFlash('error')) { ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"><i
                class="glyphicon glyphicon-remove-sign"></i></button>
        <i class="icon fa fa-check"></i>
        <?= Yii::$app->session->getFlash('error'); ?>
    </div>
    <?php } ?>
    
    <div class="">
        
        <span class="pull-right">
        <?php 
            $gridColumns = [
                // ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute'=>'fk_tbl_order_id_customer',
                    'header'=>'Corporate Name',
                    'value' => function ($model) {
                        return !empty($model->fkTblOrderCorporateId) ? $model->fkTblOrderCorporateId->name : '-' ;
                    },
                ],
                [
                    'attribute' => 'order_type_str',
                    'header' => 'Transfer Type',
                    'value' => function($model){
                        return isset($model->order_type_str) ? $model->order_type_str : '';
                    },
                ],
                [
                    'attribute' => 'terminal_type',
                    'header' => 'Travel Type',
                    'value' => function($model){
                        return ($model->terminal_type == 1) ? "International Travel" : (($model->terminal_type == 2) ? "Domestic Travel" : "");
                    },
                ],
                [
                    'attribute'=>'fk_tbl_order_id_customer',
                    'header'=>'Customer Name',
                    'value' => function ($model) {
                        return !empty($model->fkTblOrderIdCustomer) ? ucwords($model->fkTblOrderIdCustomer->name) : '-' ;
                    },
                ],
                'order_number',
                [
                    'attribute' => 'date_created',
                    'header' => 'Date Of Booking',
                    'value' => function ($model) {
                        return  $model->date_created ? date('Y-m-d',strtotime($model->date_created)) : '0000-00-00';
                    },
                ],
                [
                    'attribute'=>'order_date',
                    'header'=>'Date Of Service',
                    'value' => function ($model) {
                        return date('Y-m-d', strtotime($model->order_date));
                    },
                    'filter' => DateRangePicker::widget([
                        'model' => $searchModel,

                        'attribute' => 'order_date',
                        'convertFormat' => true,
                        'pluginOptions' => [
                            'locale' => [
                                'separator'=>'/',
                                'format' => 'Y-m-d'
                                    ],
                                ],
                            ]),
                ],
                [
                    'attribute'=>'flight_number',
                    'header'=>'Flight Number',
                    'value' => function ($model) {
                        return !empty($model->flight_number) ? strtoupper($model->flight_number) : "";
                    },
                ],
                [
                    'attribute'=>'pnr_number',
                    'header'=>'PNR number',
                    'value' => function ($model) {
                        return !empty($model->pnr_number) ? strtoupper($model->pnr_number) : "";
                    },
                ],
                [
                    'attribute' => 'city_id',
                    'header' => 'City Name',
                    'value' => function ($model) {
                        if(!empty($model->city_id)){
                            return Yii::$app->Common->getCity($model->city_id,'city');
                        } else {
                            return Yii::$app->Common->getCity($model->fk_tbl_airport_of_operation_airport_name_id,'airport');
                        }
                    }
                ],
                [
                    'attribute' => 'city_id',
                    'header' => 'Pickup Pincode',
                    'value' => function ($model) {
                        return Yii::$app->Common->getPincodes($model->id_order,'pick',$model->service_type,$model->order_transfer);
                    }
                ],
                [
                    'attribute' => 'city_id',
                    'header' => 'Drop Pincode',
                    'value' => function ($model) {
                        return Yii::$app->Common->getPincodes($model->id_order,'drop',$model->service_type,$model->order_transfer);
                    }
                ],
                [
                    'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                    'header'=>'Airport',
                    'format' => 'raw',
                    'value' => function ($model) { 
                        return $model->getAirportName($model->fk_tbl_airport_of_operation_airport_name_id);
                    },
                    'filter'=>array("3"=>"KIAL","4"=>"VOBG","7"=>"RGAI","5"=>"VOJK","6"=>"VOYK","8"=>"VODG"),
                ],
                [
                    'attribute' => 'airport_service',
                    'header' => 'Airport Service',
                    'value' => function($model){
                        $val = "";
                        if($model->service_type == 1){
                            if($model->airport_service == 1){
                                $string = "Airport : DropOff Point";
                            } else if($model->airport_service == 2){
                                $string = "Door Step Pickup";
                            }
                        } else if($model->service_type == 2){
                            if($model->airport_service == 1){
                                $string = "Airport : Pickup Point";
                            } else if($model->airport_service == 2){
                                $string = "Door Step Delivery";
                            }
                        }
                        return !empty($string) ? $string : ""; 
                    }
                ],
                [
                    'attribute'=>'order_transfer',
                    'header'=>'Order Transfer',
                    'format' => 'raw',
                    'value' => function ($model) { 
                        
                        return (isset($model->order_transfer) && $model->order_transfer == 1) ? 'City Transfer' : 'Airport Transfer'; 
                    },
                    'filter'=>array("1"=>"City Transfer","2"=>"Airport Transfer"),
                ],
                [
                    'attribute' => 'delivery_type',
                    'header' => 'Delivery Type',
                    'value' => function($model) {
                        return $model->delivery_type == 1 ? 'Local' : 'Outstation'; 
                    }
                ],
                [
                    'attribute'=>'no_of_units',
                    'header'=>'Number of bags',
                    'value' => function ($model) { 
                        return $model->getluggagecount();
                    },
                ],
                [
                    'attribute' => "usages_used",
                    'header' => 'Usages Used',
                    'value' => function ($model){
                        return $model->usages_used;
                    }
    
                ],
                [
                    'attribute'=>'payment_mode_excess',
                    'header'=>'Payment Mode',
                    'value' => function ($model) {
                        return isset($model->payment_mode_excess)?$model->payment_mode_excess : 'NA';
                    },
                ],
                [
                    'attribute'=>'excess_bag_amount',
                    'header'=>'Amount collected by Porter',
                    'value' => function ($model) {
                        return isset($model->excess_bag_amount)?$model->excess_bag_amount : '0';
                    },
                ],
                [
                    'attribute' =>'amount_paid',
                    'header' => 'Total Value',
                    'format' => 'raw',
                    'value' => function($model) {
                        $reschedule_luggage = isset($model->reschedule_luggage) ? $model->reschedule_luggage : 0;
                        $corporate_type = isset($model->corporate_type) ? $model->corporate_type : 0;
                        $corporate_id = isset($model->corporate_id) ? $model->corporate_id : 0; 
                        $luggage_price = isset($model->luggage_price) ? $model->luggage_price : 0;
                        $total_amount = $model->getTotalCollectedValue($model->id_order,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id);
                        return isset($total_amount) ? $total_amount : 0;
                    }
                ],
                // [
                //     'attribute' => 'amount_paid',
                //     'header' => 'Post Modification Total Value',
                //     'format' => 'raw',
                //     'value' => function($model){
                //         $luggage_price = isset($model->luggage_price) ? $model->luggage_price : 0;
                //         $modified_amount = isset($model->modified_amount) ? $model->modified_amount : 0;
                //         $total_post_amount = $model->getTotalModificationValue($model->id_order,$luggage_price,$modified_amount);
                //         return isset($total_post_amount) ? $total_post_amount : 0;
                //     }
                // ],
                [
                    'attribute'=>'date_of_delivery',
                    'header'=>'Date of Delivery',
                    'value' => function ($model) {
                        return $model->delivery_date ? date('Y-m-d', strtotime($model->delivery_date)) : '-';
                        // return date('Y-m-d', strtotime($model->delivery_date));
                    },
                ],
                [
                    'attribute' => 'delivery_datetime',
                    'header' => 'Delivery Date & Time(Actual)',
                    'value' => function($model){
                        $result = $model->getActualDeliveryDate($model->id_order);
                        return !empty($result) ? $result : "-";
                    }
                ],
                [
                    'attribute' => 'delivery_datetime',
                    'header' => 'Delivery Date & Time(Expected)',
                    'value' => function($model){
                        if($model->delivery_datetime){
                            // return date("Y-m-d", strtotime($model->delivery_datetime))." ".$model->delivery_time_status." ".date("H:i",strtotime($model->delivery_datetime)); 
                            return !empty($model->delivery_datetime) ? date("Y-m-d h:i A", strtotime($model->delivery_datetime)) : "-"; 
                        } else {
                            return "-";
                        }
                    }
                ],
                [
                    'attribute' => 'service_type',
                    'header' => 'Service Type',
                    'value' => function ($model) {
                        return  Common::getServiceType($model->id_order);
                    },
                ],
                // [
                //     'attribute'=>'date_of_delivery',
                //     'header'=>'Delivery Status',
                //     'value' => function ($model) {
                //         if($model->fk_tbl_order_id_slot ==1 || $model->fk_tbl_order_id_slot ==2 || $model->fk_tbl_order_id_slot ==3 || $model->fk_tbl_order_id_slot == 6)
                //             {
                //                 return $model->departure_date." ".$model->departure_time;
                //             } elseif($model->fk_tbl_order_id_slot==4){
                //                 return  $model->order_date;
                //             } else{
                //                 return "-";//$model->getDateOfDelivery($model->fk_tbl_order_id_slot,$model->order_date);
                //             } 
                            
                //     },
                // ],
                [
                    'attribute'=>'related_order_id',
                    'format' => 'raw',
                    'header'=>'Related Order Status',
                    'value' => function ($data, $key) {
                        return (($data->related_order_id !=0 && !empty($data->relatedOrder)) ? Html::a($data->relatedOrder->order_status,['/order/update','id'=>$data->relatedOrder->id_order]) : '-' );
                    },
                ],
                [
                    'attribute'=>'dservice_type',
                    'header'=>'Delivery Service Type',
                    'value' => function ($model) { 
                        return $model->getDeliveryName($model->dservice_type, $model->corporate_id);
                    },
                ],
                
                
                // [
                //     'attribute'=>'meet_time_gate',
                //     'header'=>'Gate Meeting Time',
                //     'value' => function ($model) {
                //         return date('h:i A', strtotime($model->meet_time_gate));
                //     },
                // ],
                // [
                //     'attribute'=>'fk_tbl_order_id_slot',
                //     'label'=>'Slot',
                //     'value' => function ($model) {
                //         return !empty($model->fkTblOrderIdSlot) ? $model->fkTblOrderIdSlot->time_description : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'assigned_porter',
                //     'label'=>'Porter',
                //     'value' => function ($model) { 
                //         $vehicleslot=$model->vehicleSlotAllocations;
                //         return !empty($vehicleslot) ? ( !empty($vehicleslot->fkTblVehicleSlotAllocationIdEmployee) ? $vehicleslot->fkTblVehicleSlotAllocationIdEmployee->name : '-') :  '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'assigned_porterx',
                //     'label'=>'Porterx',
                //     'value' => function ($model) { 
                //         $porterx=$model->porterxAllocations; 
                //         return !empty($porterx) ? ( !empty($porterx->porterxAllocationsIdEmployee) ? $porterx->porterxAllocationsIdEmployee->name : '-') :  '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'travel_person',
                //     'header'=>'Assigned Person',
                //     'value' => function ($model) {
                //         if(!empty($model->travell_passenger_name)){
                //             return $model->travell_passenger_name ;
                //         } else {
                //             return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                //         }
                //     },
                // ],
                // [
                //     'attribute'=>'assigned_person_verification',
                //     'format' => 'raw',
                //     'header'=>'Assigned Person verification',
                //     'value' => function ($data, $key) { 
                //         return $data->travel_person==1 ? $data->documentVerification : '-' ;
                //     },
                // ],
                'order_status',
                
                // [
                //     'attribute'=>'amount_paid',
                //     'header'=>'Amount collected',
                //     'format' => 'raw',
                //     'value' => function ($model) { 
                //         return $model->getAmountCollected($model->id_order);
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Payment Mode',
                //     'value' => function ($model) {
                //             $pay_detail = Common::getPaymentType($model->id_order);
                //             return !empty($pay_detail->payment_type) ? $pay_detail->payment_type : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Payment Status',
                //     'value' => function ($model) {
                //             $pay_detail = Common::getPaymentType($model->id_order);
                //             return !empty($pay_detail->payment_status) ? $pay_detail->payment_status : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Area',
                //     'value' => function ($model) {
                //             return !empty($model->orderSpotDetails) ? $model->orderSpotDetails->area : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Pincode',
                //     'value' => function ($model) {
                //             return !empty($model->orderSpotDetails) ? $model->orderSpotDetails->pincode : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Landmark',
                //     'value' => function ($model) {
                //             return !empty($model->orderSpotDetails) ? $model->orderSpotDetails->landmark : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Address Line1',
                //     'value' => function ($model) {
                //             return !empty($model->orderSpotDetails) ? $model->orderSpotDetails->address_line_1 : '-' ;
                //     },
                // ],
                // [
                //     'attribute'=>'id_order',
                //     'header'=>'Address Line2',
                //     'value' => function ($model) {
                //             return !empty($model->orderSpotDetails) ? $model->orderSpotDetails->address_line_2 : '-' ;
                //     },
                // ],
            ];
            User::downloadExportData($dataProvider,$gridColumns,'Corporate-K-Orders');
        ?>
        </span>
    </div>


    <h1><?= Html::encode($this->title) ?></h1>
    <div id="headMsg"></div>

    <div style="display:flex;">
        <div class="col-md-6 panel panel-primary">
            <div class="row " style="margin:42px 0px;">
                <div class="col-xl-3 col-lg-3  col-md-3 col-sm-3">
                    <h4>Start Date</h4>
                    <input  class="btn btn-default form-control" type="date" id="start_date" name="start_date">
                </div>
                <div class="col-xl-3 col-lg-3  col-md-3 col-sm-3">
                    <h4>End Date</h4>
                    <input class="btn btn-default form-control" type="date" id="end_date" name="end_date">
                </div>
                <div class="col-xl-4 col-lg-4  col-md-4 col-sm-4">
                    <h4>Email</h4>
                    <input class="btn btn-default form-control" type="text" id="email" name="email">
                </div>
                <div class="col-xl-2 col-lg-2 col-md-2 col-sm-2">
                 </br>  </br>
                    <button class="btn btn-primary clickexport" type="submit">Export CSV</button>
                </div>
            </div>        
        </div>  
    </div>   
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions'=>function($model){
            if($model->unreadCount() > 0){ 
                return ['class' => 'info'];
            }
            if(isset($model->order_transfer) && $model->order_transfer ==1){ 
                return ['class' => 'green'];
            }
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id_order',
            /*[
                'attribute'=>'corporate_id',
                'label'=>'Corporate Name',
                'value' => function ($model) { //print_r($model);exit;
                    return !empty($model->corporaterName) ? $model->corporaterName : '-' ;
                },
            ],*/
            
            /*[
                'attribute'=>'flight_number', 
                'label'=>'Reference number',
                'value' => function ($model) {
                    return $model->flight_number;
                },
            ],*/
            [
                'attribute' => 'created_by_name',
                'header' => 'Created By',
                'value' => function ($model) {
                    return $model->created_by_name;
                },
            ],
            [
                'attribute' => 'corporate_customer_id',
                'header' => 'Customer Corporate Id',
                'value' => function ($model) {
                    return !empty($model->corporate_customer_id) ? $model->corporate_customer_id : "-";
                },
            ],
            [
                'attribute'=>'fk_tbl_order_id_customer',
                'header'=>'Corporate Name',
                'value' => function ($model) {
                    // if($model->corporate_id == 0){
                    //     return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                    // }else{
                        return !empty($model->fkTblOrderCorporateId) ? ucwords($model->fkTblOrderCorporateId->name) : '-' ;
                    // }
                },
            ],
            [
                'attribute' => 'order_type_str',
                'header' => 'Transfer Type', 
                'value' => function($model){
                    return isset($model->order_type_str) ? $model->order_type_str : '';
                },
            ],
            [
                'attribute' => 'terminal_type',
                'header' => 'Travel Type',
                'value' => function($model){
                    return ($model->terminal_type == 1) ? "International Travel" : (($model->terminal_type == 2) ? "Domestic Travel" : "");
                },
            ],
            [
                'attribute'=>'fk_tbl_order_id_customer',
                'header'=>'Customer Name',
                'value' => function ($model) {
                    // if($model->corporate_id == 0){
                        return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                    // }else{
                    //     return !empty($model->fkTblOrderCorporateId) ? $model->fkTblOrderCorporateId->name : '-' ;
                    // }
                },
            ],
            'order_number',
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
                'filter' => DateRangePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'order_date',
                    'convertFormat' => true,
                    // 'autoUpdateOnInit'=>true,
                    'pluginOptions' => [
                        'locale' => [
                            'separator'=>'/',
                            'format' => 'Y-m-d'
                                ],
                                //'opens'=>'left'
                            ],
                ]),
            ],
            [
                'attribute'=>'flight_number',
                'header'=>'Flight Number',
                'value' => function ($model) {
                    return !empty($model->flight_number) ? strtoupper($model->flight_number) : "";
                },
            ],
            [
                'attribute'=>'pnr_number',
                'header'=>'PNR number',
                'value' => function ($model) {
                    return !empty($model->pnr_number) ? strtoupper($model->pnr_number) : "";
                },
            ],
            /*[
                'attribute'=>'no_of_units',
                'label'=>'Number of bags',
                'value' => function ($model) { 
                    return $model->no_of_units;
                },
            ],*/
            [
                'attribute' => 'city_id',
                'header' => 'City Name',
                'value' => function ($model) {
                    if(!empty($model->city_id)){
                        return Yii::$app->Common->getCity($model->city_id,'city');
                    } else {
                        return Yii::$app->Common->getCity($model->fk_tbl_airport_of_operation_airport_name_id,'airport');
                    }
                }
            ],
            [
                'attribute' => 'city_id',
                'header' => 'Pickup Pincode',
                'value' => function ($model) {
                    return Yii::$app->Common->getPincodes($model->id_order,'pick',$model->service_type,$model->order_transfer);
                }
            ],
            [
                'attribute' => 'city_id',
                'header' => 'Drop Pincode',
                'value' => function ($model) {
                    return Yii::$app->Common->getPincodes($model->id_order,'drop',$model->service_type,$model->order_transfer);
                }
            ],
            [ 
                'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                'header'=>'Airport',
                'format' => 'raw',
                'value' => function ($model) { 
                    return $model->getAirportName($model->fk_tbl_airport_of_operation_airport_name_id);
                },
            ],
            [
                'attribute' => 'airport_service',
                'header' => 'Airport Service',
                'value' => function($model){
                    $val = "";
                    if($model->service_type == 1){
                        if($model->airport_service == 1){
                            $string = "Airport : DropOff Point";
                        } else if($model->airport_service == 2){
                            $string = "Door Step Pickup";
                        }
                    } else if($model->service_type == 2){
                        if($model->airport_service == 1){
                            $string = "Airport : Pickup Point";
                        } else if($model->airport_service == 2){
                            $string = "Door Step Delivery";
                        }
                    }
                    return !empty($string) ? $string : ""; 
                }
            ],
            [
                'attribute' => '-',
                'header' => 'Order Transfer',
                'value' => function ($model) {
                    return (isset($model->order_transfer) && $model->order_transfer == 1) ? 'City Transfer' : 'Airport Transfer';
                }
            ],
            [
                'attribute' => 'delivery_type',
                'header' => 'Delivery Type',
                'value' => function($model) {
                    return $model->delivery_type == 1 ? 'Local' : 'Outstation'; 
                }
            ],
            [
                'attribute'=>'no_of_units',
                'header'=>'Number <br> of bags',
                'value' => function ($model) { 
                    return $model->getluggagecount();
                },
            ],
            [
                'attribute' => "usages_used",
                'header' => 'Usages Used',
                'value' => function ($model){
                    return $model->usages_used;
                }

            ],
            //'payment_mode_excess',
           // 'excess_bag_amount',
            [
                'attribute'=>'payment_mode_excess',
                //'label'=>'Reference number',
                'value' => function ($model) {
                    return isset($model->payment_mode_excess)?$model->payment_mode_excess : 'N/A';
                },
            ],
            //'excess_bag_amount',
             [
                'attribute'=>'excess_bag_amount',
                'header'=>'<center> Amount collected <br> by Porter</center>',
                'value' => function ($model) {
                    return isset($model->excess_bag_amount)?$model->excess_bag_amount : '0';
                },
            ],
            [
                'attribute' =>'amount_paid',
                'header' => 'Total Value',
                'format' => 'raw',
                'value' => function($model) {
                    $reschedule_luggage = isset($model->reschedule_luggage) ? $model->reschedule_luggage : 0;
                    $corporate_type = isset($model->corporate_type) ? $model->corporate_type : 0;
                    $corporate_id = isset($model->corporate_id) ? $model->corporate_id : 0; 
                    $luggage_price = isset($model->luggage_price) ? $model->luggage_price : 0;
                    $total_amount = $model->getTotalCollectedValue($model->id_order,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id);
                    return isset($total_amount) ? $total_amount : 0;
                }
            ],
            // [
            //     'attribute' => 'amount_paid',
            //     'header' => 'Post Modification Total Value',
            //     'format' => 'raw',
            //     'value' => function($model){
            //         $luggage_price = isset($model->luggage_price) ? $model->luggage_price : 0;
            //         $modified_amount = isset($model->modified_amount) ? $model->modified_amount : 0;
            //         $total_post_amount = $model->getTotalModificationValue($model->id_order,$luggage_price,$modified_amount);
            //         return isset($total_post_amount) ? $total_post_amount : 0;
            //     }
            // ],
            [
                'attribute'=>'corporate_price',
                'label'=>'Price',
                'value' => function ($model) { 
                    return $model->amount_paid;
                },
            ],
            
            [
                        'attribute'=>'dservice_type',
                        'header'=>'Delivery Service Type',
                        'format' => 'raw',
                        'value' => function ($model) { 
                            return $model->getDeliveryName($model->dservice_type, $model->corporate_id);
                        },
                        // 'filter'=>array("1"=>"Repairs","2"=>"Reverse Pick Up","3"=>"Express - Outstation","4"=>"Express - Fragile","5"=>"Outstation- Fragile","6"=>"Normal - Fragile", "7"=> "Normal Delivery", '8' => 'Express', '9'=>'Outstation'),
            ],
            // [
            //         'attribute'=>'dservice_type',
            //         'header'=>'Delivery Service Type',
            //         'value' => function ($model) { 
            //             return (($model->dservice_type == 1) ? "Repairs" :
            //                               (($model->dservice_type == 2) ? "Reverse Pick Up" :
            //                                (($model->dservice_type == 3) ? "Express - Outstation" :
            //                                 (($model->dservice_type == 4) ? "Express - Fragile" :
            //                                     (($model->dservice_type == 5) ? "Outstation- Fragile" :
            //                                     (($model->dservice_type == 6) ? "Normal - Fragile" :
            //                                         (($model->dservice_type == 7) ? "Normal Delivery" :
            //                                             (($model->dservice_type == 8) ? "Express" :
            //                                 (($model->dservice_type == 9) ? "Outstation" : ""))))))))
            //                              );
            //             //return ($model->dservice_type == 1) ? "Normal": (($model->dservice_type ==2) ? "Express" : '') ;
            //         },
            //         'filter'=>array("1"=>"Repairs","2"=>"Reverse Pick Up","3"=>"Express - Outstation","4"=>"Express - Fragile","5"=>"Outstation- Fragile","6"=>"Normal - Fragile", "7"=> "Normal Delivery", '8' => 'Express', '9'=>'Outstation'),
            // ],
    
            [
                'attribute'=>'delivery_date',
                'label'=>'Date of Delivery',
                'value' => function ($model) { 
                    return $model->getDeliverydate();
                    //return ($model->fkTblOrderIdSlot != null) ? $model->fkTblOrderIdSlot->description : '-';
                },
            ],
            [
                'attribute' => 'delivery_datetime',
                'header' => 'Delivery Date & Time(Actual)',
                'value' => function($model){
                    $result = $model->getActualDeliveryDate($model->id_order);
                    return !empty($result) ? $result : "-";
                }
            ],
            [
                'attribute' => 'delivery_datetime',
                'header' => 'Delivery Date & Time(Expected)',
                'value' => function($model){
                    if($model->delivery_datetime){
                        return !empty($model->delivery_datetime) ? date("Y-m-d h:i A", strtotime($model->delivery_datetime)) : "-"; 
                        // return date("Y-m-d", strtotime($model->delivery_datetime))." ".$model->delivery_time_status." ".date("H:i",strtotime($model->delivery_datetime)); 
                    } else {
                        return "-";
                    }
                }
            ],
            [
                'attribute'=>'fk_tbl_order_status_id_order_status',
                'label'=>'Order Status',
                'value' => function ($model) {
                    return $model->order_status;
                },
                'filter'=>ArrayHelper::map(OrderStatus::find()->all(),'id_order_status','status_name'),
            ],
            // 'order_status',
            [
                'attribute'=>'related_order_id',
                'format' => 'raw',
                'header'=>'<center> Related Order <br> Status </center>',
                'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                    return $data->related_order_id !=0 ? Html::a($data->relatedOrder->order_status,['/order/user-order-update','id'=>$data->relatedOrder->id_order]) : '-' ;
                },
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width:80px;'],
                'header'=>'Actions',
                'template' => '{update} {view}',
                'buttons' => [

                'update' => function ($url, $model) {
                    $users  = Yii::$app->Common->checkUserAccess($model->created_by_name, Yii::$app->user->identity->id_employee, Yii::$app->user->identity->fk_tbl_employee_id_employee_role);
                    if($users){
                        return (
                          Html::a('<span class="glyphicon glyphicon-pencil"></span>',
                          $url = Url::toRoute(['order/thirdparty-update','id'=>$model->id_order ]), 
                          [ 'title' => Yii::t('app', 'Update'), 'class'=>'', 'id'=>$model->id_order,'onclick'=>"checkRazoreStatus($model->id_order);"])
                          );
                    }
                },
                'view' => function ($url, $model) { 
                    if($model->created_by != 10){
                          return (Yii::$app->user->identity->fk_tbl_employee_id_employee_role  != 4  ?
                          Html::a('<span  class="glyphicon glyphicon-eye-open"></span>',
                          $url = Url::toRoute(['order/view-kiosk','id'=>$model->id_order ]),
                          ['title' => Yii::t('app', 'View'), 'class'=>'btn btn-primary btn-xs', ]) : '');
                    }
                },
                
                ]
            ],
        ],
    ]); ?>
</div>

<script>

    $(document).ready(function(){   
        $(".clickexport").click(function() {
            console.log('check click');
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var email = $('#email').val();
            
            if($('#start_date').val() == ''){
                alert('Select start date first'); 
            }
            if($('#end_date').val() == ''){
                alert('Select end date first'); 
            }
            if($('#email').val() == ''){
                alert('Please enter eamil'); 
            }
            var token ="<?= $client_id; ?>";
            var role ="<?= $role;?>";
            var id_employee ="<?= $id_employee;?>";
            //  var url = "https://hyd.carterx.in/api/v1/getcsvdata";
            if(start_date != '' && end_date != '' && email != ''){
                $.ajax({ 
                    type: "POST",
                    dataType: "json",
                    data :{"start_date":start_date,"end_date":end_date,"role_id":role,"id_employee":id_employee,"email":email},
                    headers: {"Authorization": token},
                    url: "https://hyd.carterx.in/api/v1/getcsvdata",
                    success: function(response){
                        console.log(response);
                        $("html, body").animate({scrollTop: 0}, "slow");
                        $('#headMsg').empty();
                        var message = response.msg;
                        if(response.status ==201){
                            $('#headMsg').html("<div class='alert alert-danger'><button class='close' type='button' data-dismiss='alert'>x</button><strong>" + message + "</strong></div>");
                            $("#login_headMsg").fadeTo(2000, 800).slideUp(800, function(){
                                $('#login_headMsg').empty();
                                
                            });
                        }else if(response.status ==200){
                            var path = "<?=Yii::$app->request->absoluteUrl?>";
                            var url = path.replace("r=order%2Fcorporate-kiosk-orders", "r=order%2Fexport");
                            //location.href = url;
                            console.log(url);
                            var message = "Export has been started once it will be done,It will be sent to your entered email";
                            $('#headMsg').html("<div class='alert alert-success'><button class='close' type='button' data-dismiss='alert'>x</button><strong>" + message + "</strong><a href="+url+"> click Here</a></div>");
                         
                            setTimeout(function() {
                            $('#headMsg').fadeOut('slow');
                            }, 5000); 
                            $('#start_date').val('');
                            $('#end_date').val('');
                        
                        }
                        

                    }
                });
            }

        });
    });
</script>
    