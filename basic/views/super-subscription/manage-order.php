<?php

    use yii\helpers\Html;
    use yii\grid\GridView;
    use yii\helpers\ArrayHelper;
    use app\models\OrderStatus;
    use yii\widgets\ActiveForm;
    use app\models\User;
    use app\models\Order;   
    use yii\helpers\Url;
    use kartik\daterange\DateRangePicker;
    use app\models\EmployeeRole;
    use app\components\Common;
    use app\models\SmsType;
    use kartik\time\TimePicker;
    use kartik\datetime\DateTimePicker;
    use app\models\OrderSmsDetails;
    $sms_model = new OrderSmsDetails;
    /* @var $this yii\web\View */
    /* @var $searchModel app\models\OrderSearch */
    /* @var $dataProvider yii\data\ActiveDataProvider */

    $url = "order/index";
    Yii::$app->session->set('page_name',$url);

    $this->title = 'Orders';
    $this->params['breadcrumbs'][] = $this->title;
    ini_set('memory_limit', '-1');

    $role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
    $id_employee = Yii::$app->user->identity->id_employee;
    $client_id =$client['client_id'];
?>
<style type="text/css">
    .table > thead > tr > td.green, .table > tbody > tr > td.green, .table > tfoot > tr > td.green, .table > thead > tr > th.green, .table > tbody > tr > th.green, .table > tfoot > tr > th.green, .table > thead > tr.green > td, .table > tbody > tr.green > td, .table > tfoot > tr.green > td, .table > thead > tr.green > th, .table > tbody > tr.green > th, .table > tfoot > tr.green > th {
         background-color: #00b200;
     }
    /*.green{
        background-color: #00b200;
    }*/
</style>
<script type="text/javascript">
    $(function() {
        $('#ordersearch-fk_tbl_airport_of_operation_airport_name_id').change(function() {
            this.form.submit();
        });
    });
    var checkRazoreStatus = function(id){
        $.get( "index.php?r=employee/check-razorpay-status",{order_id:id,status:'single'}).done(function( data ) {
        });
    }
</script>

<div class="order-index">

    <!-- Modal -->
    <div id="session_msg">
        <?php if(Yii::$app->session->hasFlash('success')) {?>
        <div class="alert alert-success" role="alert">
            <?= Yii::$app->session->getFlash('success'); ?>
        </div>
        <?php  } else if (Yii::$app->session->hasFlash('error')){ ?>
        <div class="alert alert-danger" role="alert">
            <?= Yii::$app->session->getFlash('error'); ?>
        </div>
        <?php } ?>
    </div>

    <div class="">
        <span class="pull-right">
            <?php $form = ActiveForm::begin(['options' =>['style' => 'width: 20%;float: right']]); ?>
            <?= $form->field($searchModel, 'fk_tbl_airport_of_operation_airport_name_id')->dropDownList(ArrayHelper::map(EmployeeRole::getAirportCorporate(),'airport_name_id','airport_name'),['prompt'=>'Select your airport'])->label(false); ?>
            <?php ActiveForm::end(); ?>

            <?php

                $gridColumns = [
                    // ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute'=>'fk_tbl_order_id_customer',
                        'header'=>'Corporate Name',
                        'value' => function ($model) {
                        // if($model->corporate_id == 0){
                        //     return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                        // }else{
                        return !empty($model->fkTblOrderCorporateId) ? $model->fkTblOrderCorporateId->name : '-' ;
                        // }
                        },
                    ],
                    [
                        'attribute'=>'fk_tbl_order_id_customer',
                        'header'=>'Customer Name',
                        'value' => function ($model) {
                            return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                        },
                    ],
                    [
                        'attribute'=>'confirmation_number',
                        'header'=>'Confirmation Number',
                        'value' => function ($model) {
                            $result = Yii::$app->Common->getConfirmationDetails($model->confirmation_number);
                            return !empty($result) ? strtoupper($result->confirmation_number) : '-' ;
                        },
                    ],
                    'order_number',
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
                        'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                        'header'=>'Airport',
                        'format' => 'raw',
                        'value' => function ($model) { 
                            return $model->getAirportName($model->fk_tbl_airport_of_operation_airport_name_id);
                        },
                        'filter'=>array("3"=>"KIAL","4"=>"VOBG","7"=>"RGAI","5"=>"VOJK","6"=>"VOYK","8"=>"VODG"),
                    ],
                    [
                        'attribute' => 'delivery_type',
                        'header' => 'Delivery Type',
                        'value' => function($model) {
                            return ($model['corporate_type'] != 2) ? ($model->delivery_type == 1 ? 'Local' : 'Outstation') : ""; 
                        }
                    ],
                    [
                        'attribute'=>'order_transfer',
                        'header'=>'Order Transfer',
                        'format' => 'raw',
                        'value' => function ($model) { 
                            return (isset($model->order_transfer) && $model->order_transfer == 1) ? 'City Transfer' : ((isset($model->order_transfer) && $model->order_transfer == 2) ? 'Airport Transfer' : ""); 
                        },
                        'filter'=>array("1"=>"City Transfer","2"=>"Airport Transfer"),
                    ],
                    [
                        'attribute' => 'airport_service',
                        'header' => 'Airport Service',
                        'value' => function($model){
                            $val = "";
                            if($model->service_type == 1){
                                if($model->airport_service == 1){
                                    $string = "Airport : Pickup Point";
                                } else if($model->airport_service == 2){
                                    $string = "Door Step Pickup";
                                }
                            } else if($model->service_type == 2){
                                if($model->airport_service == 1){
                                    $string = "Airport : Delivery Point";
                                } else if($model->airport_service == 2){
                                    $string = "Door Step Delivery";
                                }
                            }
                            return $string; 
                        }
                    ],
                    [
                        'attribute' => 'fk_tbl_order_id_pick_drop_location',
                        'label'=>'Sector',
                        'value' => function ($model) {
                            //--------------------------------------------
                            if( $model->fk_tbl_order_id_pick_drop_location === null){
                                $model->fk_tbl_order_id_pick_drop_location = 2;
                            }
                            //-------------------------------------------
                            return $model->getPincodeSector($model->fk_tbl_order_id_pick_drop_location);
                        },

                        // 'filter'=> $sectors,
                    ],
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
                            return $model->order_date ? date('Y-m-d', strtotime($model->order_date)) : '0000-00-00';
                        },
                    ],
                    [
                        'attribute'=>'date_of_delivery',
                        'header'=>'Date of Delivery',
                        'value' => function ($model) {
                            if($model->fk_tbl_order_id_slot ==1 || $model->fk_tbl_order_id_slot ==2 || $model->fk_tbl_order_id_slot ==3 || $model->fk_tbl_order_id_slot == 6)
                                {
                                    return $model->departure_date." ".$model->departure_time;
                                } elseif($model->fk_tbl_order_id_slot==4){
                                    return  $model->order_date;
                                } else{
                                    return $model->getDateOfDelivery($model->fk_tbl_order_id_slot,$model->order_date);
                                } 
                            // return $model->delivery_date ? date('Y-m-d', strtotime($model->delivery_date)) : '-';
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
                            } else {
                                return "-";
                            }
                        }
                    ],
                    // [
                    //     'attribute'=>'date_of_delivery',
                    //     'header'=>'Delivery Status',
                    //     'value' => function ($model) {
                    //         if($model->delivery_type){
                    //             return $model->delivery_type;
                    //         } else {
                    //             return '-';
                    //         }
                    //         // if($model->fk_tbl_order_id_slot ==1 || $model->fk_tbl_order_id_slot ==2 || $model->fk_tbl_order_id_slot ==3 || $model->fk_tbl_order_id_slot == 6)
                    //         // {
                    //         //     return $model->departure_date." ".$model->departure_time;
                    //         // } elseif($model->fk_tbl_order_id_slot==4){
                    //         //     return  $model->order_date;
                    //         // } else{
                    //         //     return $model->getDateOfDelivery($model->fk_tbl_order_id_slot,$model->order_date);
                    //         // } 
                    //     },
                    // ],
                    [
                        'attribute'=>'flight_number',
                        'header'=>'Reference number',
                        'value' => function ($model) {
                            if($model->flight_number){
                                return $model->flight_number;
                            } else {
                                return '-';
                            }
                            
                        },
                    ],
                    [
                        'attribute'=>'no_of_units',
                        'header'=>'Number of bags',
                        'value' => function ($model) { 
                            return $model->getluggagecount();
                        },
                    ],
                    [
                        'attribute'=>'payment_mode_excess',
                        'header'=>'Payment Mode',
                        'value' => function ($model) {
                            return isset($model->payment_method) ? $model->payment_method : $model->payment_mode_excess;
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
                        'attribute'=>'meet_time_gate',
                        'header'=>'Gate Meeting Time',
                        'value' => function ($model) {
                            return date('h:i A', strtotime($model->meet_time_gate));
                        },
                    ],                 
                    [
                        'attribute' => 'service_type',
                        'header' => 'Service Type',
                        'value' => function ($model) {
                            return  Common::getServiceType($model->id_order);
                            // return Yii::$app->Common->getServiceType($order_details['order']['id_order']);
                        },
                        // 'filter'=>array("1"=>"To Airport","2"=>"From Airport"),
                    ],
                    [
                        'attribute'=>'dservice_type',
                        'header'=>'Delivery Service Type',
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
                        'attribute'=>'travel_person',
                        'header'=>'Assigned Person',
                        'value' => function ($model) { //print_r($model->orderSpotDetails);exit;
                            //return $model->travel_person==1 ? $model->travell_passenger_name : 'Me';
                            if(!empty($model->travell_passenger_name)){
                                return $model->travell_passenger_name ;
                            } else {
                                return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                            }
                        },
                    ],
                    [
                        'attribute'=>'assigned_person_verification',
                        'format' => 'raw',
                        'header'=>'Assigned Person verification',
                        'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                            //return $data->orderSpotDetails->assigned_person==1 ? $data->orderSpotDetails->documentVerification : '-' ;
                            return $data->travel_person==1 ? strip_tags($data->documentVerification) : '-' ;
                        },
                    ],
                    'order_status',
                    [
                        'attribute'=>'related_order_id',
                        'format' => 'raw',
                        'header'=>'Related Order Status',
                        'value' => function ($data, $key) {
                            return (($data->related_order_id !=0 && !empty($data->relatedOrder)) ? Html::a($data->relatedOrder->order_status,['/order/update','id'=>$data->relatedOrder->id_order]) : '-' );
                            /*return $data->related_order_id !=0 ? Html::a($data->relatedOrder->order_status,['/order/update','id'=>$data->relatedOrder->id_order]) : '-' ;*/
                        },
                    ],
                    [
                        'attribute'=>'amount_paid',
                        'header'=>'Amount collected',
                        'format' => 'raw',
                        'value' => function ($model) { 
                            return $model->getAmountCollected($model->id_order);
                        },
                    ],
                    [
                        'attribute' =>'amount_paid',
                        'header' => 'Total Value',
                        'format' => 'raw',
                        'value' => function($model) {
                            $reschedule_luggage = $model->reschedule_luggage;
                            $corporate_type = $model->corporate_type;
                            $corporate_id = $model->corporate_id;
                            $luggage_price = $model->luggage_price;
                            $total_amount = $model->getTotalCollectedValue($model->id_order,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id);
                            return isset($total_amount) ? $total_amount : 0;
                        }
                    ],
                    // [
                    //     'attribute' => 'amount_paid',
                    //     'header' => 'Modification Value',
                    //     'format' => 'raw',
                    //     'value' => function($model){
                    //         return $model->modified_amount ? $model->modified_amount : 0;
                    //     }
                    // ],
                    // [
                    //     'attribute' => 'amount_paid',
                    //     'header' => 'Post Modification Total Value',
                    //     'format' => 'raw',
                    //     'value' => function($model){
                    //         $luggage_price = $model->luggage_price;
                    //         $modified_amount = $model->modified_amount;
                    //         $total_post_amount = $model->getTotalModificationValue($model->id_order,$luggage_price,$modified_amount);
                    //         return isset($total_post_amount) ? $total_post_amount : 0;
                    //     }
                    // ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Payment Mode',
                        'value' => function ($model) {
                            $pay_detail = Common::getPaymentType($model->id_order);
                            return !empty($pay_detail->payment_type) ? $pay_detail->payment_type : '-' ;
                        },
                    ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Payment Status',
                        'value' => function ($model) {
                            $pay_detail = Common::getPaymentType($model->id_order);
                            return !empty($pay_detail->payment_status) ? $pay_detail->payment_status : '-' ;
                        },
                    ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Area',
                        'value' => function ($model) {
                            if(!empty($model->orderSpotDetails)) {
                                return ($model->orderSpotDetails->area) ? $model->orderSpotDetails->area : '-';
                            } else {
                                return '-' ;
                            }
                            
                        },
                    ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Pincode',
                        'value' => function ($model) {
                            if(!empty($model->orderSpotDetails)){
                                return $model->orderSpotDetails->pincode ? $model->orderSpotDetails->pincode : '-';
                            } else {
                                return '-' ;
                            }
                            
                        },
                    ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Landmark',
                        'value' => function ($model) {
                            if(!empty($model->orderSpotDetails)){
                                return $model->orderSpotDetails->landmark ? $model->orderSpotDetails->landmark : '-';
                            } else {
                                return '-' ;
                            }
                            
                        },
                    ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Address Line1',
                        'value' => function ($model) {
                            if(!empty($model->orderSpotDetails)) {
                                return $model->orderSpotDetails->address_line_1 ? $model->orderSpotDetails->address_line_1 : '-';
                            } else {
                                return '-' ;
                            }
                            
                        },
                    ],
                    [
                        'attribute'=>'id_order',
                        'header'=>'Address Line2',
                        'value' => function ($model) {
                            if(!empty($model->orderSpotDetails)){
                                return $model->orderSpotDetails->address_line_2 ? $model->orderSpotDetails->address_line_2 : '-';
                            } else {
                                return '-' ;
                            }
                        
                        },
                    ],
                    [
                        'attribute' => 'pickupPersonName',
                        'header' => 'Pick Up Person Name',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['pickupPersonName']){
                                return $model['orderMetaDetailsRelation'][0]['pickupPersonName'];
                            } else {
                                return '-';
                            }
                        
                        }
                    ],
                    [
                        'attribute' => 'pickupPersonAddressLine1',
                        'header' => 'Pick Up Address Line 1',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine1']){
                                return $model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine1'];
                            } else {
                                return '-';
                            }
                            
                        }
                    ],
                    [
                        'attribute' => 'pickupPersonAddressLine2',
                        'header' => 'Pick Up Address Line 2',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine2']){
                                return $model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine2'];
                            } else {
                                return '-';
                            }
                        }
                    ],
                    [
                        'attribute' => 'pickupArea',
                        'header' => 'Pick Up Area',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['pickupArea']){
                                return $model['orderMetaDetailsRelation'][0]['pickupArea'];
                            } else {
                                return '-';
                            }
                        }
                    ],
                    [
                        'attribute' => 'pickupPincode',
                        'header' => 'Pick Up Pincode',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['pickupPincode']){
                                return $model['orderMetaDetailsRelation'][0]['pickupPincode'];
                            } else {
                                return '-';
                            }
                        }
                    ],
                    [
                        'attribute' => 'dropPersonName',
                        'header' => 'Drop Person Name',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['dropPersonName']){
                                return $model['orderMetaDetailsRelation'][0]['dropPersonName'];
                            } else {
                                return '-';
                            }
                        }
                    ],
                    [
                        'attribute' => 'dropPersonAddressLine1',
                        'header' => 'Drop Address Line 1',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['dropPersonAddressLine1']){
                                return $model['orderMetaDetailsRelation'][0]['dropPersonAddressLine1'];
                            } else {
                                return '-';
                            }  
                        }
                    ],
                    [
                        'attribute' => 'dropPersonAddressLine2',
                        'header' => 'Drop Address Line 2',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['dropPersonAddressLine2']){
                                return $model['orderMetaDetailsRelation'][0]['dropPersonAddressLine2'];
                            } else {
                                return '-';
                            }
                        }
                    ],
                    [
                        'attribute' => 'droparea',
                        'header' => 'Drop Area',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['droparea']){
                                return $model['orderMetaDetailsRelation'][0]['droparea'];
                            } else {
                                return '-';
                            }
                            
                        }
                    ],
                    [
                        'attribute' => 'dropPincode',
                        'header' => 'Drop Pincode',
                        'value' => function ($model){
                            if($model['orderMetaDetailsRelation'][0]['dropPincode']){
                                return $model['orderMetaDetailsRelation'][0]['dropPincode'];
                            } else {
                                return '-';
                            }
                            
                        }
                    ],
                    [
                        'attribute' => 'sms_count',
                        'header' => 'Order SMS Sent',
                        'value' => function($model){
                            return $model->getCountSms($model->id_order);
                        }
                    ],
                ];
                User::downloadExportData($dataProvider,$gridColumns,'Orders');            
            ?>
        </span>
    </div>



   <div> <h1><?= Html::encode($this->title) ?></h1></div>
   <div id="headMsg"></div>
    <div class="row" style="margin:10px;">
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
    <p>
    
        <?=Html::beginForm(['order/select-multi-order-sms'],'post');?>
            <?php $role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            if(in_array($role,array(1,2,4))){ ?>
                <div class="panel panel-primary">
                    <div style="margin: 10px 10px;">
                        <?php $form = ActiveForm::begin(['method' => 'post','action' => ['order/select-multi-order-sms']]); ?>
                        <div class="row" id="order_sms">
                            <input type="hidden" name="selected_order_id" id="selected_order_id" class="selected_order_id">
                            <div class="form-group col-sm-4">
                                <?=  $form->field($sms_model, 'order_sms_title_id')->dropDownList(ArrayHelper::map(SmsType::find()->where(['sms_status' => 'Enable'])->orderBy(['sms_view_order_no'=>'SORT_ASC'])->All(), 'pk_sms_id', 'sms_title'),['prompt'=>'--- Select Sms Type ---','required'=>true,'class'=>'form-control'])->label('SMS Title'); ?>
                            </div>
                            <div class="col-sm-8">
                                <lable>SMS Template : </lable><br>
                                <code id="sms_content_para"></code>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-6 ordersmsdetails-order_sms_text">
                                <fieldset><legend>Option 1:</legend>
                                    <?= $form->field($sms_model, 'order_sms_text')->textarea(['maxlength'=>30,'rows'=>'6','placeholder'=>'Enter only 30 character.','onmouseleave'=>'if($(this).val()){removeDisable();} else {addDisable();}'])->label('Text')?>
                                </fieldset>
                            </div>
                            <div class="form-group col-sm-6 ordersmsdetails-order_sms_days">
                                <fieldset><legend>Option 1:</legend>
                                    <?= $form->field($sms_model, 'order_sms_days')->textInput()->input('number', ['placeholder' => "Enter No of Days",'minLength' => 0,'onmouseleave'=>'if($(this).val()){removeDisable();} else {addDisable();}'])->label('No of Days')?>
                                </fieldset>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-6 ordersmsdetails-order_sms_time_start_end">
                                <p class="text-center p_tag">Or</p>
                                <fieldset><legend class="legend_option">Option 2:</legend>
                                <?= $form->field($sms_model,'order_sms_time_start')->widget(TimePicker::className(),['options' => ['class' => 'form-control','readonly' => false],'convertFormat' => true,'pluginOptions' => ['autoclose'=>true,'format' => 'H:m:s']])->label('Start Time');?>
                                <?= $form->field($sms_model,'order_sms_time_end')->widget(TimePicker::className(),['options' => ['class' => 'form-control','readonly' => false],'convertFormat' => true,'pluginOptions' => ['autoclose'=>true,'format' => 'H:m:s']])->label('End Time');?>
                                </fieldset>
                            </div>
                            <div class="form-group col-sm-6 ordersmsdetails-order_sms_extra_text">
                                <?=$form->field($sms_model, 'order_sms_extra_text')->textarea(array('maxlength'=>30,'rows' => 6,'cols' =>6,'placeholder'=>'Enter only 30 character.','onmouseleave'=>'if($(this).val()){removeDisable();} else {addDisable();}'))->label('Extra text'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <?=html::button('Preview SMS',['class'=>'btn btn-primary sms_btn','id'=>"preview_sms_btn"]); ?> 
                                <?= Html::submitButton('Send', ['class' => 'btn btn-primary send_btn', 'id'=>"submit_sms_content"]) ?>
                            </div>
                        </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            <?php } ?>
        <?= Html::endForm();?>
    </p>
    <p>
        <?=Html::beginForm(['order/change-order-status'],'post');?>
            <?php 
            if(in_array($role,array(1,2,4,10))){ ?>
                <div class="panel panel-primary">
                    <p style="margin:10px 10px;font-size:15px;">
                        <b><lable>Change order status</lable></b>
                    </p>
                    <hr>
                    <div style="margin: 10px 10px;">
                        <?php $form = ActiveForm::begin(['method' => 'post','action' => ['order/select-multi-order-sms']]); ?>
                        <div class="row" id="order_sms">
                            <div class="form-group col-md-4">
                                <input type="hidden" name="selected_orderIds" id="selected_orderIds" class="selected_orderIds">
                                <?=  Html::dropDownList('id_order_status', '', ArrayHelper::map(OrderStatus::find()->all(),'id_order_status','status_name'), ['prompt' => '--- select status ---','class'=>'form-control','id'=>'order_status']); ?>
                            </div>    
                            <div class="form-group">
                                <?= Html::submitButton('Update Status', ['class' => 'btn btn-primary save','id'=>'change_status_btn']) ?>
                            </div>
                        </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            <?php } ?>
        <?= Html::endForm();?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'id' => 'index_order_list',
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
            // ['class' => 'yii\grid\CheckboxColumn'],
            // 'id_order',
            [
                'attribute'=>'fk_tbl_order_id_customer',
                'header'=>'Corporate Name',
                'value' => function ($model) {
                    if($model->corporate_id == 0){
                        return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                        
                    }else{
                        return !empty($model->fkTblOrderCorporateId) ? $model->fkTblOrderCorporateId->name : '-' ;
                    }
                },
            ],
            [
                'attribute'=>'fk_tbl_order_id_customer',
                'header'=>'Customer Name',
                'value' => function ($model) {

                    return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                },
            ],
            [
                'attribute'=>'confirmation_number',
                'header'=>'Confirmation Number',
                'value' => function ($model) {
                    $result = Yii::$app->Common->getConfirmationDetails($model->confirmation_number);
                    return !empty($result) ? strtoupper($result->confirmation_number) : '-' ;
                },
            ],
            [
                'attribute' => 'terminal_type',
                'header' => 'Used Usage',
                'value' => function($model){
                    return isset($model->usages_used) ? $model->usages_used : (($model->terminal_type == 1) ? (2 * $model->no_of_units) : (($model->terminal_type == 2)  ? (1 * $model->no_of_units) : 0)); 
                }
            ],
            'order_number',
            [
                'attribute' => 'created_by_name',
                'header' => 'Created By',
                'value' => function ($model) {
                    return $model->created_by_name;
                },
            ],
            //'fk_tbl_airport_of_operation_airport_name_id',
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
                    $pincode = Yii::$app->Common->getPincodes($model->id_order,'pick',$model->service_type,$model->order_transfer);
                    return isset($pincode) ? $pincode : $model->pickup_pincode;
                }
            ],
            [
                'attribute' => 'city_id',
                'header' => 'Drop Pincode',
                'value' => function ($model) {
                    $pincode = Yii::$app->Common->getPincodes($model->id_order,'drop',$model->service_type,$model->order_transfer);
                    return isset($pincode) ? $pincode : $model->drop_pincode;
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
                'attribute' => 'delivery_type',
                'header' => 'Delivery Type',
                'value' => function($model) {
                    return ($model['corporate_type'] != 2) ? ($model->delivery_type == 1 ? 'Local' : 'Outstation') : ""; 
                }
            ],
            [
                'attribute'=>'order_transfer',
                'header'=>'Order Transfer',
                'format' => 'raw',
                'value' => function ($model) { 
                    
                    return (isset($model->order_transfer) && $model->order_transfer == 1) ? 'City Transfer' : ((isset($model->order_transfer) && $model->order_transfer == 2) ? 'Airport Transfer' : ""); 
                },
                'filter'=>array("1"=>"City Transfer","2"=>"Airport Transfer"),
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
                    return $string; 
                }
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
                'header'=>'Date Of Service',
                'value' => function ($model) {
                   
                    return $model->order_date ? date('Y-m-d', strtotime($model->order_date)) : '0000-00-00';
                },
                'filter' => DateRangePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'order_date',
                    'convertFormat'=>true,
                    // 'useWithAddon'=>true,
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
                'attribute'=>'date_of_delivery',
                'header'=>'Date of Delivery',
                'value' => function ($model) {
                
                     if($model->order_transfer == 2 || $model->order_transfer == ''){
                        if($model->fk_tbl_order_id_slot ==1 || $model->fk_tbl_order_id_slot ==2 || $model->fk_tbl_order_id_slot ==3 || $model->fk_tbl_order_id_slot == 6)
                        {
                            return $model->departure_date." ".$model->departure_time;
                        } elseif($model->fk_tbl_order_id_slot==4){
                            
                            return  $model->order_date;
                        } else{
                            return $model->getDateOfDelivery($model->fk_tbl_order_id_slot,$model->order_date);
                        } 
                    }else{
                        return Common::mysqlDate($model->delivery_date);
                        // return Yii::$app->Common->mysqlDate($model->delivery_date);
                    }                          
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
                        return !empty($model->delivery_datetime) ? date("Y-m-d h:i:s A", strtotime($model->delivery_datetime)) : "-"; 
                    } else {
                        return "-";
                    }
                }
            ],
            [
                'attribute'=>'flight_number',
                'header'=>'Reference number',
                'value' => function ($model) {
                    return $model->flight_number;
                },
            ],
            [
                'attribute' =>'amount_paid',
                'header' => 'Total Value',
                // 'format' => 'raw',
                'value' => function($model) {
                    $reschedule_luggage = $model->reschedule_luggage;
                    $corporate_type = $model->corporate_type;
                    $corporate_id = $model->corporate_id;
                    $luggage_price = $model->luggage_price;
                    $total_amount = $model->getTotalCollectedValue($model->id_order,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id);
                    return isset($total_amount) ? $total_amount : 0;
                }
            ],

            [
                'attribute'=>'no_of_units',
                'header'=>'Number of bags',
                'value' => function ($model) { 
                    return ($model->getluggagecount()) ? $model->getluggagecount() : $model->no_of_units;
                },
            ],
            [
                'attribute'=>'payment_mode_excess',
                'header'=>'Payment Mode',
                'value' => function ($model) {
                    return isset($model->payment_method) ? $model->payment_method : $model->payment_mode_excess;
                },
            ],
            //'excess_bag_amount',
            [
                'attribute'=>'excess_bag_amount',
                'header'=>'Excess Amount Calculated',
                'value' => function ($model) {
                    return isset($model->excess_bag_amount)?$model->excess_bag_amount : '0';
                },
            ],
            [
                'attribute'=>'meet_time_gate',
                'header'=>'Gate Meeting Times',
                'value' => function ($model) {
                    return ($model->order_transfer == 2) ? date('h:i A', strtotime($model->meet_time_gate)) : '';
                },
            ],
            /*[
                'attribute' => 'service_type',
                'value' => function ($model) {
                    return ($model->fk_tbl_order_id_slot ==1 || $model->fk_tbl_order_id_slot ==2 || $model->fk_tbl_order_id_slot ==3 || $model->fk_tbl_order_id_slot == 6) ? "To Airport": "From Airport" ;
                },
                'filter'=>array("1"=>"To Airport","2"=>"From Airport"),
            ],*/
            [
                'attribute' => 'service_type',
                'header' => 'Service Type',
                'value' => function ($model) {
                // return Yii::$app->Common->getServiceType($model->id_order);
                    return  Common::getServiceType($model->id_order);
                },
                // 'filter'=>array("1"=>"To Airport","2"=>"From Airport"),
            ],

            [
                        'attribute'=>'dservice_type',
                        'header'=>'Delivery Service Type',
                        'value' => function ($model) { 
                       
                            return $model->getDeliveryName($model->dservice_type, $model->corporate_id);
                        },
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
                'attribute'=>'travell_passenger_name',
                'header'=>'Assigned Person',
                'value' => function ($model) { //print_r($model->orderSpotDetails);exit;
                    //return $model->travel_person==1 ? $model->travell_passenger_name : 'Me';
                    
                    return $model->travell_passenger_name ;
                },
            ],
            [
                'attribute'=>'assigned_person_verification',
                'format' => 'raw',
                'header'=>'Assigned Person verification',
                'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                    //return $data->orderSpotDetails->assigned_person==1 ? $data->orderSpotDetails->documentVerification : '-' ;
                    return $data->travel_person==1 ? $data->documentVerification : '-' ;
                },
            ],
             'order_status',
            [
                'attribute'=>'related_order_id',
                'format' => 'raw',
                'header'=>'Related Order Status',
                'value' => function ($data, $key) { //print_r($data->relatedOrder->order_status );
                   
                      return (($data->related_order_id !=0 && !empty($data->relatedOrder)) ? Html::a($data->relatedOrder->order_status,['/order/update','id'=>$data->relatedOrder->id_order]) : '-' );
                    /*return $data->related_order_id !=0 ? Html::a($data->relatedOrder->order_status,['/order/update','id'=>$data->relatedOrder->id_order]) : '-' ;*/
               
                    // print_r($r);die();
                },
            ],
            [
                'attribute' => 'minutes_to_reach',
                'header'=>'Minutes To Reach',
                'value' => function ($model) { 
                   
                    return $model->minutes_to_reach ;
                },
            ],
            // 'service_type',
            // 'round_trip',
            // 'fk_tbl_order_id_customer',
            
            // 'payment_method',
            // 'payment_transaction_id',
            // 'payment_status',
            // 'invoice_number',
            // 'allocation',
            // 'enable_cod',
            // 'date_created',
            // 'date_modified',

            // [
            //     'attribute' => 'pickupPersonName',
            //     'header' => 'Pick Up Person Name',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['pickupPersonName']){
            //             return $model['orderMetaDetailsRelation'][0]['pickupPersonName'];
            //         } else {
            //             return '-';
            //         }
                   
            //     }
            // ],
            // [
            //     'attribute' => 'pickupPersonAddressLine1',
            //     'header' => 'Pick Up Address Line 1',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine1']){
            //             return $model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine1'];
            //         } else {
            //             return '-';
            //         }
                    
            //     }
            // ],
            // [
            //     'attribute' => 'pickupPersonAddressLine2',
            //     'header' => 'Pick Up Address Line 2',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine2']){
            //             return $model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine2'];
            //         } else {
            //             return '-';
            //         }
            //     }
            // ],
            // [
            //     'attribute' => 'pickupArea',
            //     'header' => 'Pick Up Area',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['pickupArea']){
            //             return $model['orderMetaDetailsRelation'][0]['pickupArea'];
            //         } else {
            //             return '-';
            //         }
            //     }
            // ],
            // [
            //     'attribute' => 'pickupPincode',
            //     'header' => 'Pick Up Pincode',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['pickupPincode']){
            //             return $model['orderMetaDetailsRelation'][0]['pickupPincode'];
            //         } else {
            //             return '-';
            //         }
            //     }
            // ],
            // [
            //     'attribute' => 'dropPersonName',
            //     'header' => 'Drop Person Name',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['dropPersonName']){
            //             return $model['orderMetaDetailsRelation'][0]['dropPersonName'];
            //         } else {
            //             return '-';
            //         }
            //     }
            // ],
            // [
            //     'attribute' => 'dropPersonAddressLine1',
            //     'header' => 'Drop Address Line 1',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['dropPersonAddressLine1']){
            //             return $model['orderMetaDetailsRelation'][0]['dropPersonAddressLine1'];
            //         } else {
            //             return '-';
            //         }  
            //     }
            // ],
            // [
            //     'attribute' => 'dropPersonAddressLine2',
            //     'header' => 'Drop Address Line 2',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['pickupPersonAddressLine1']){
        
            //         } else {
            //             return '-';
            //         }
            //         return $model['orderMetaDetailsRelation'][0]['dropPersonAddressLine2'];
            //     }
            // ],
            // [
            //     'attribute' => 'droparea',
            //     'header' => 'Drop Area',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['droparea']){
            //             return $model['orderMetaDetailsRelation'][0]['droparea'];
            //         } else {
            //             return '-';
            //         }
                    
            //     }
            // ],
            // [
            //     'attribute' => 'dropPincode',
            //     'header' => 'Drop Pincode',
            //     'value' => function ($model){
            //         if($model['orderMetaDetailsRelation'][0]['dropPincode']){
            //             return $model['orderMetaDetailsRelation'][0]['dropPincode'];
            //         } else {
            //             return '-';
            //         }
                    
            //     }
            // ],
            [
                'attribute' => 'sms_count',
                'header' => 'Order SMS Sent',
                'value' => function($model){
                    return $model->getCountSms($model->id_order);
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width:90px;'],
                'header'=>'Actions',
                'template' => '{vieworder}',

                'buttons' => [
                    'vieworder' => function ($url, $model) {                        
                        return Html::a('<span>View</span>', $url, ['title' => Yii::t('app', 'Update'), 'class'=>'btn btn-warning btn-xs', ]); 
                    },

                ],
                
            ],
        ],
    ]); ?>

</div>
<script>

    $(document).ready(function(){
        addDisable();
        $(".ordersmsdetails-order_sms_days").hide();
        $(".ordersmsdetails-order_sms_time_start_end").hide();
        $(".ordersmsdetails-order_sms_time_start_end").hide();
        $(".ordersmsdetails-order_sms_text").hide();
        $("#ordersmsdetails-order_sms_text").attr('disabled', 'disabled');
        $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
        $(".ordersmsdetails-order_sms_extra_text").hide();
        $("#ordersmsdetails-order_sms_extra_text").attr('disabled', 'disabled');
        // $(".ordersmsdetails-order_sms_extra_text_next").hide();
        // $("#ordersmsdetails-order_sms_extra_text_next").attr('disabled', 'disabled');

        $("#ordersmsdetails-order_sms_title_id").change(function(){
            $(".p_tag").show();
            $(".legend_option").show();
            smsTitleId = $("#ordersmsdetails-order_sms_title_id").val();
            if(smsTitleId == 1){
                addDisable();
                $(".ordersmsdetails-order_sms_days").show();
                $(".ordersmsdetails-order_sms_time_start_end").hide();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").hide();
                $("#ordersmsdetails-order_sms_text").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_days").removeAttr("disabled");
            } else if(smsTitleId == 2){
                addDisable();
                $(".ordersmsdetails-order_sms_days").show();
                $(".ordersmsdetails-order_sms_time_start_end").show();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").hide();
                $("#ordersmsdetails-order_sms_days").removeAttr("disabled");
                $("#ordersmsdetails-order_sms_time_start").removeAttr('disabled');
                $("#ordersmsdetails-order_sms_time_end").removeAttr('disabled');
                $("#ordersmsdetails-order_sms_extra_text").attr('disabled', 'disabled');
                // $(".days_with_text").show();
                // $(".no_of_days").hide();
                // $("#ordersmsdetails-order_sms_extra_text_next").removeAttr('disabled', 'disabled');
            } else if(smsTitleId == 3){
                addDisable();
                $(".ordersmsdetails-order_sms_days").show();
                $(".ordersmsdetails-order_sms_time_start_end").show();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").hide();
                $("#ordersmsdetails-order_sms_days").removeAttr("disabled");
                $("#ordersmsdetails-order_sms_time_start").removeAttr('disabled');
                $("#ordersmsdetails-order_sms_time_end").removeAttr('disabled');
                $("#ordersmsdetails-order_sms_extra_text").attr('disabled', 'disabled');
            } else if(smsTitleId == 4){
                addDisable();
                $(".ordersmsdetails-order_sms_days").hide();
                $(".ordersmsdetails-order_sms_time_start_end").hide();
                $(".ordersmsdetails-order_sms_text").show();
                $(".ordersmsdetails-order_sms_extra_text").hide();
                $("#ordersmsdetails-order_sms_text").removeAttr("disabled");
                $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_start").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_end").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_extra_text").attr('disabled', 'disabled');
            } else if(smsTitleId == 5){
                $(".ordersmsdetails-order_sms_days").hide();
                $(".ordersmsdetails-order_sms_time_start_end").hide();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").hide();
                $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_text").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_start").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_end").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_extra_text").attr('disabled', 'disabled');
            } else if(smsTitleId == 6){
                $(".ordersmsdetails-order_sms_days").hide();
                $(".ordersmsdetails-order_sms_time_start_end").hide();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").show();
                $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_text").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_start").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_end").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_extra_text").removeAttr('disabled', 'disabled');
            } else if((smsTitleId == 7) || (smsTitleId == 9)){
                $(".ordersmsdetails-order_sms_days").show();
                $(".ordersmsdetails-order_sms_time_start_end").hide();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").show();
                $("#ordersmsdetails-order_sms_days").removeAttr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_text").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_start").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_end").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_extra_text").removeAttr('disabled', 'disabled');
            } else if((smsTitleId == 8) || (smsTitleId == 10)){
                $(".ordersmsdetails-order_sms_days").hide();
                $(".ordersmsdetails-order_sms_time_start_end").show();
                $(".ordersmsdetails-order_sms_text").hide();
                $(".ordersmsdetails-order_sms_extra_text").show();
                $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_text").attr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_start").removeAttr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_time_end").removeAttr('disabled', 'disabled');
                $("#ordersmsdetails-order_sms_extra_text").removeAttr('disabled', 'disabled');
                $(".p_tag").hide();
                $(".legend_option").hide();
            }
        });

        $("#ordersmsdetails-order_sms_days").change(function() {
            $("#ordersmsdetails-order_sms_days").removeAttr("disabled");
            $("#ordersmsdetails-order_sms_time_start").attr('disabled', 'disabled');
            $("#ordersmsdetails-order_sms_time_end").attr('disabled', 'disabled');
        });

        // $("#ordersmsdetails-order_sms_extra_text_next").change(function() {
        //     $("#ordersmsdetails-order_sms_extra_text_next").removeAttr("disabled");
        //     $("#ordersmsdetails-order_sms_days").attr('disabled',"disabled");
        //     $("#ordersmsdetails-order_sms_time_start").attr('disabled', 'disabled');
        //     $("#ordersmsdetails-order_sms_time_end").attr('disabled', 'disabled');
        // });

        $("#ordersmsdetails-order_sms_time_start").change(function(){
            $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
            $("#ordersmsdetails-order_sms_time_start").removeAttr("disabled");
            $("#ordersmsdetails-order_sms_time_end").removeAttr("disabled");
        });

        $("#ordersmsdetails-order_sms_time_end").change(function(){
            $("#ordersmsdetails-order_sms_days").attr('disabled', 'disabled');
            $("#ordersmsdetails-order_sms_time_start").removeAttr("disabled");
            $("#ordersmsdetails-order_sms_time_end").removeAttr("disabled");
        });
    });

    var getSms = function(titleId,orderId,days=null,text=null,startTime=null,endTime=null,extra_text=null,extra_text_days=null){
        let smstext = "";
        switch(titleId) {
            case '1':
                smstext = "Dear Customer, your order# ORDER-ID is dispatched today. Your order will be delivered in "+days+" days. We will send the next communication on the day of delivery. Thank you for choosing CarterX <a href='www.carterx.in'> www.carterx.in </a>";
                break;
            case '2':
                if(!days) {
                    smstext = "Dear Customer, your order# ORDER-ID will be picked up. Your order will be picked between "+startTime+'-'+endTime+". Thank you for choosing CarterX <a href='www.carterx.in'> www.carterx.in </a>";
                } else {
                    smstext = "Dear Customer, your order# ORDER-ID will be picked up in "+days+" days. Thank you for choosing CarterX <a href='www.carterx.in'> www.carterx.in </a>";
                }
                break;
            case '3':
                if(!days) {
                    smstext = "Dear Customer, your order# ORDER-ID is out for delivery. Your order will be delivered between "+startTime+'-'+endTime+". Thank you for choosing CarterX <a href='www.carterx.in'> www.carterx.in </a>";
                } else {
                    smstext = "Dear Customer, your order# ORDER-ID  will be delivered in "+days+" days. Thank you for choosing CarterX <a href='www.carterx.in'> www.carterx.in </a>";
                }
                break;
            case '4':
                smstext = "Dear Customer, your order# ORDER-ID has currently reached our "+text+" facility. Your orders will be delivered soon. Thank you for choosing us <a href='www.carterx.in'> www.carterx.in </a>";
                break;
            case '5':
                smstext = "Dear Customer, your order# ORDER-ID has been delivered. Thank you for choosing CarterX <a href='www.carterx.in'> www.carterx.in </a>";
                break;
            case '6':
                smstext = "Dear Customer, for your order# ORDER-ID please be informed that "+extra_text+". Thank you for choosing CarterX www.carterx.in";
                break;
            case '7':
                smstext = "Dear Customer, your order# ORDER-ID will be picked up in "+days+" Day. Please be informed that "+extra_text+". Thank you for choosing CarterX  www.carterx.in";
                break;
            case '8':
                smstext = "Dear Customer, your order# ORDER-ID will be picked up. Your order will be picked between "+startTime+'-'+endTime+". Please be informed that "+extra_text+". Thank you for choosing CarterX www.carterx.in";
                break;
            case '9':
                smstext = "Dear Customer, your order# ORDER-ID will be delivered in "+days+" Day. Please be informed that "+extra_text+". Thank you for choosing CarterX www.carterx.in";
                break;
            case '10':
                smstext = "Dear Customer, your order# ORDER-ID will be delivered. Your order will be delivered between "+startTime+'-'+endTime+". Please be informed that "+extra_text+". Thank you for choosing CarterX www.carterx.in";
                break;
            default:
                smstext = "";
        }
        return smstext;
    }

    var addDisable = function(){
        var days = $("#ordersmsdetails-order_sms_days").val();
        var startTime = $("#ordersmsdetails-order_sms_time_start").val();
        var endTime = $("#ordersmsdetails-order_sms_time_end").val();
        var text = $("#ordersmsdetails-order_sms_text").val();
        if((startTime == "") || (startTime == "") || (endTime == "") || (text == "")){
            $(".sms_btn").attr('disabled', 'disabled');
            $(".send_btn").attr('disabled', 'disabled');
            $("#sms_content_para").html("");
        }
    }

    var removeDisable = function(){
        $(".sms_btn").removeAttr("disabled");
        // $(".send_btn").removeAttr("disabled");
        unblockSubmitbtn();
    }

    var unblockSubmitbtn = function(){
        let isChecked = $('#index_order_list input[type=checkbox]').is(':checked');
        var order_ids = $("#selected_order_id").val();
        if(isChecked){
            if ($("#ordersmsdetails-order_sms_title_id").val()) {
                $("#submit_sms_content").prop('disabled',false);
            } else {
                $("#submit_sms_content").prop('disabled',true);
            }
        }
    }

    var unblockSubmitbtn2 = function(){
        let isChecked = $('#index_order_list input[type=checkbox]').is(':checked');
        var order_ids = $("#selected_order_id").val();
        if(isChecked){
            if ($("#order_status").val()) {
                $("#change_status_btn").prop('disabled',false);
            } else {
                $("#change_status_btn").prop('disabled',true);
            }
        }
    }

    $("#preview_sms_btn").click(function() {
        var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
        var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
        var days = $("#ordersmsdetails-order_sms_days").val();
        var startTime = $("#ordersmsdetails-order_sms_time_start").val();
        var endTime = $("#ordersmsdetails-order_sms_time_end").val();
        var text = $("#ordersmsdetails-order_sms_text").val();
        var extra_text = $("#ordersmsdetails-order_sms_extra_text").val();
        // var extra_text_days = $("#ordersmsdetails-order_sms_extra_text_next").val();

        if((smstitleid !== '5') && ((days!="") || (text!="") || (startTime!="") || (endTime!="") || (extra_text!=""))){
            var content = getSms(smstitleid,orderNo,days,text,startTime,endTime,extra_text);
            if(content){
                $("#sms_content_para").html(content);
            }
        } else if(smstitleid === '5') {
            var content = getSms(smstitleid,orderNo,days,text,startTime,endTime,extra_text);
            if(content){
                $("#sms_content_para").html(content);
            }
        } else {
            addDisable();
        }
    });

    $("#ordersmsdetails-order_sms_days").change(function(){
        var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
        var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
        var days = $("#ordersmsdetails-order_sms_days").val();
        if((smstitleid == 2) && (!extra_text)){
            var content = getSms(smstitleid,orderNo,days,null,null,null,null);
        } else {
            var extra_text = $("#ordersmsdetails-order_sms_extra_text").val();
            if(extra_text)
            var content = getSms(smstitleid,orderNo,days,null,null,null,extra_text); 
        }
        if(content){
            $("#sms_content_para").html(content);
        }
    });

    // $("#ordersmsdetails-order_sms_extra_text_next").change(function(){
    //     var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
    //     var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
    //     var extra_text_days = $("#ordersmsdetails-order_sms_extra_text_next").val();
    //     alert("text iwht days___"+extra_text_days);
    //     var content = getSms(smstitleid,orderNo,null,null,null,null,null,extra_text_days);
    //     if(content){
    //         $("#sms_content_para").html(content);
    //     }
    // });

    $("#ordersmsdetails-order_sms_extra_text").change(function(){
        var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
        var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
        var extra_text_days = $("#ordersmsdetails-order_sms_extra_text").val();
        if(smstitleid == 7){
            var days = $("#ordersmsdetails-order_sms_days").val();
            var content = getSms(smstitleid,orderNo,days,null,null,null,extra_text_days);
        } else if(smstitleid == 8){
            var startTime = $("#ordersmsdetails-order_sms_time_start").val();
            var endTime = $("#ordersmsdetails-order_sms_time_end").val();
            var content = getSms(smstitleid,orderNo,null,null,startTime,endTime,extra_text_days);
        }        
        if(content){
            $("#sms_content_para").html(content);
        }
    });

    $(".picker").click(function(){
        $("#ordersmsdetails-order_sms_time_start").change(function(){
            var endTime = $("#ordersmsdetails-order_sms_time_end").val();
            if(endTime != ""){
                var startTime = $("#ordersmsdetails-order_sms_time_start").val();
                var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
                var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
                if((smstitleid == 2) && (!extra_text)){
                    var content = getSms(smstitleid,orderNo,null,null,startTime,endTime,null);
                } else {
                    var extra_text = $("#ordersmsdetails-order_sms_extra_text").val();
                    if(extra_text)
                    var content = getSms(smstitleid,orderNo,null,null,startTime,endTime,extra_text);
                }
                removeDisable();
                if(content){
                    $("#sms_content_para").html(content);
                }
            }
        });
        $("#ordersmsdetails-order_sms_time_end").change(function(){
            removeDisable();
            var endTime = $("#ordersmsdetails-order_sms_time_end").val();
            var startTime = $("#ordersmsdetails-order_sms_time_start").val();
            var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
            var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
            if((smstitleid == 2) && (!extra_text)){
                var content = getSms(smstitleid,orderNo,null,null,startTime,endTime,null);
            } else {
                var extra_text = $("#ordersmsdetails-order_sms_extra_text").val();
                if(extra_text)
                var content = getSms(smstitleid,orderNo,null,null,startTime,endTime,extra_text);
            }
            if(content){
                $("#sms_content_para").html(content);
            }
        });
    });
        
    $("#ordersmsdetails-order_sms_text").change(function(){
        var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
        var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
        var text = $("#ordersmsdetails-order_sms_text").val();
        var content = getSms(smstitleid,orderNo,null,text,null,null,null);
        
        if(content){
            $("#sms_content_para").html(content);
        }
    });
    $("#ordersmsdetails-order_sms_title_id").change(function(){
        var smstitleid = $("#ordersmsdetails-order_sms_title_id").val();
        var orderNo = $("#ordersmsdetails-order_sms_order_id").val();
        if(smstitleid == 5){
            removeDisable();
            var content = getSms(smstitleid,orderNo,null,null,null,null,null);
            if(content){
                $("#sms_content_para").html(content);
            }
        } else {
            $("#sms_content_para").html("");
        } 
        $("#ordersmsdetails-order_sms_days").val('');
        $("#ordersmsdetails-order_sms_time_start").val('');
        $("#ordersmsdetails-order_sms_time_end").val('');
        $("#ordersmsdetails-order_sms_text").val('');
        $("#ordersmsdetails-order_sms_extra_text").val('');
        // $("#ordersmsdetails-order_sms_extra_text_next").val('');
    });

    // for check checkbox are selected or not
    $(document).ready(function(){
        $("#submit_sms_content").prop("disabled",true);
        $("#change_status_btn").prop("disabled",true);
        $("#index_order_list input[type=checkbox]").click(function(){
            var keys = $('#index_order_list').yiiGridView('getSelectedRows');
            if(keys!=""){
                $("#selected_order_id").val(keys);
                $("#selected_orderIds").val(keys);
                unblockSubmitbtn();
                unblockSubmitbtn2();
            } else {
                $("#submit_sms_content").prop('disabled',true);
                $("#change_status_btn").prop("disabled",true);
            }
        });
    });

    $("#order_status").change(function(){
        unblockSubmitbtn2();
    });

    // for flash msg fadeout
    setTimeout(function(){
        $("#session_msg").delay(3000).fadeOut();
    },1000);

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
                        var url = path.replace("r=super-subscription%2Fmanage-order", "r=order%2Fexport");
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
</script>