<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use app\models\OrderStatus;
use app\models\EmployeeAirportRegion;
use app\models\User;
use yii\helpers\Url;
use kartik\daterange\DateRangePicker;
use yii\widgets\ActiveForm;
use app\models\OrderSmsDetails;
use kartik\datetime\DateTimePicker;
use kartik\time\TimePicker;
    $sms_model = new OrderSmsDetails;
    use app\models\SmsType;
/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
$id_employee = Yii::$app->user->identity->id_employee;
$client_id =$client['client_id'];
$this->title = 'Orders';
$url = "order/kiosk-orders";
Yii::$app->session->set('kiosk_page_name',$url);
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
    var checkRazoreStatus = function(id){
        $.get( "index.php?r=employee/check-razorpay-status",{order_id:id,status:'single'}).done(function( data ) {
        });
    }    
</script>
<div class="order-index" style="padding-top: 30px;">
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
<?php if (Yii::$app->session->getFlash('msg')) { ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"><i class="glyphicon glyphicon-remove-sign"></i></button>
        <i class="icon fa fa-check"></i>
        <?= Yii::$app->session->getFlash('msg'); ?>
    </div>
    <?php } else if (Yii::$app->session->getFlash('error')) { ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"><i class="glyphicon glyphicon-remove-sign"></i></button>
        <i class="icon fa fa-check"></i>
        <?= Yii::$app->session->getFlash('error'); ?>
    </div>
<?php } ?>

<div class=""><span class="pull-right">
<?php

$gridColumns = [
            // ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute'=>'flight_number',
                        'label'=>'Flight Number',
                        'value' => function ($model) {
                            return !empty($model->flight_number) ? $model->flight_number : "";
                        },
                    ],
                    [
                        'attribute'=>'pnr_number',
                        'header'=>'PNR Number',
                        'value' => function ($model) {
                            return !empty($model->pnr_number) ? $model->pnr_number : "";
                        },
                    ],
                    'order_number',
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
                        'attribute'=>'travell_passenger_name',
                        'label'=>'Customer name',
                        'value' => function ($model) { 
                            return $model->travell_passenger_name;
                        },
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
                        'attribute' => 'delivery_type',
                        'header' => 'Delivery Type',
                        'value' => function($model) {
                            return $model->delivery_type == 1 ? 'Local' : 'Outstation'; 
                        }
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
                        'label'=>'Date Of Service',
                        'value' => function ($model) {
                            return date('Y-m-d', strtotime($model->order_date));
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
                        'attribute'=>'corporate_price',
                        'label'=>'Price',
                        'value' => function ($model) { 
                            return $model->amount_paid;
                        },
                    ],
                    [
                        'attribute'=>'payment_mode_excess',
                        //'label'=>'Reference number',
                        'value' => function ($model) {
                            return isset($model->payment_mode_excess)?$model->payment_mode_excess : 'N/A';
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
                            $reschedule_luggage = $model->reschedule_luggage;
                            $corporate_type = $model->corporate_type;
                            $corporate_id = $model->corporate_id;
                            $luggage_price = $model->luggage_price;
                            $total_amount = $model->getTotalCollectedValue($model->id_order,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id);
                            return isset($total_amount) ? $total_amount : 0;
                        }
                    ],
                    [
                        'attribute'=>'dservice_type',
                        'label'=>'Type of service',
                        'value' => function ($model) { 
                            return ($model->dservice_type == 1) ? "Normal": (($model->dservice_type ==2) ? "Express" : '') ;
                        },
                        'filter'=>array("1"=>"Normal","2"=>"Express"),
                    ],     
                    [
                        'attribute'=>'delivery_date',
                        'label'=>'Date of Delivery',
                        'value' => function ($model) { 
                            return $model->getDeliverydate();
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
                        'attribute'=>'fk_tbl_order_status_id_order_status',
                        'label'=>'Order Status',
                        'value' => function ($model) {
                            return $model->order_status;
                        },
                        'filter'=>ArrayHelper::map(OrderStatus::find()->all(),'id_order_status','status_name'),
                    ],
                    // 'order_status',
                    [
                        'attribute'=>'delivery_date',
                        'label'=>'Date of Delivery',
                        'value' => function ($model) { 
                            return $model->getDeliverydate();
                            //return ($model->fkTblOrderIdSlot != null) ? $model->fkTblOrderIdSlot->description : '-';
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
                    [
                        'attribute'=>'related_order_id',
                        'format' => 'raw',
                        'label'=>'Related Order Status',
                        'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                            return (($data->related_order_id !=0 && !empty($data->relatedOrder)) ? $data->relatedOrder->order_status : '-' );
                            // return ($data->related_order_id !=0 && !empty($data->related_order_id)) ? $data->relatedOrder->order_status : '-' ;
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
                        'attribute'=>'order_transfer',
                        'header'=>'Order Transfer',
                        'format' => 'raw',
                        'value' => function ($model) { 
                            
                            return (isset($model->order_transfer) && $model->order_transfer == 1) ? 'City Transfer' : 'Airport Transfer'; 
                        },
                        'filter'=>array("1"=>"City Transfer","2"=>"Airport Transfer"),
                    ],

            ];

User::downloadExportData($dataProvider,$gridColumns,'Orders');            
 ?>

</span></div>


    <h1><?= Html::encode($this->title) ?></h1>
    <div id="headMsg"></div>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
    <div style="display:flex;">
        <div class="col-md-6 col-sm-12 panel panel-primary">
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
        <div class="col-md-6 col-sm-12">
            <p>
                <?=Html::beginForm(['order/select-multi-order-sms'],'post');?>
                    <?php $role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
                    if(in_array($role,array(1,2,4,10))){ ?>
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
        </div>
          
    </div> 
    
    <p>
        <?=Html::beginForm(['order/change-order-status'],'post');?>
            <?php $role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
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
        'id' => 'kiosk_order_list',
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
            ['class' => 'yii\grid\CheckboxColumn'],
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
                    if($model->corporate_id == 0){
                        return !empty($model->fkTblOrderIdCustomer) ? $model->fkTblOrderIdCustomer->name : '-' ;
                    }else{
                        return !empty($model->fkTblOrderCorporateId) ? $model->fkTblOrderCorporateId->name : '-' ;
                    }
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
               
            ],
            [
                'attribute'=>'flight_number',
                'header'=>'Flight Number',
                'value' => function ($model) {
                    return !empty($model->flight_number) ? $model->flight_number : "";
                },
            ],
            [
                'attribute'=>'pnr_number',
                'header'=>'PNR number',
                'value' => function ($model) {
                    return !empty($model->pnr_number) ? $model->pnr_number : "";
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
                'attribute' => "usages_used",
                'header' => 'Usages Used',
                'value' => function ($model){
                    return $model->usages_used;
                }

            ],
            [
                'attribute' => 'confirmation_number',
                'header' => 'Subscriber Name',
                'value' => function ($model){
                    if(!empty($model->confirmation_number)){
                        $result = Yii::$app->db->createCommand("select subscriber_name from tbl_subscription_transaction_details as std Right JOIN tbl_super_subscription as ss ON std.subscription_id = ss.subscription_id where subscription_transaction_id = '".$model->confirmation_number."'")->queryone();
                        return ($result['subscriber_name']) ? ucwords($result['subscriber_name']) : "-";
                    } else {
                        return "-";
                    }
                }
            ],
            [
                'attribute' => 'confirmation_number',
                'header' => 'Confirmation Number',
                'value' => function ($model){
                    if(!empty($model->confirmation_number)){
                        $result = Yii::$app->db->createCommand("select UPPER(confirmation_number) as confirmation_number from tbl_subscription_transaction_details where subscription_transaction_id = '".$model->confirmation_number."'")->queryone();
                        return !empty($result['confirmation_number']) ? $result['confirmation_number'] : '-';
                    } else {
                        return '-';
                    }
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
                'header'=>'Number <br> of bags',
                'value' => function ($model) { 
                    return $model->getluggagecount();
                },
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
                    $reschedule_luggage = $model->reschedule_luggage;
                    $corporate_type = $model->corporate_type;
                    $corporate_id = $model->corporate_id;
                    $luggage_price = $model->luggage_price;
                    $total_amount = $model->getTotalCollectedValue($model->id_order,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id);
                    return isset($total_amount) ? $total_amount : 0;
                }
            ],
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
                        // return date("Y-m-d", strtotime($model->delivery_datetime))." ".$model->delivery_time_status." ".date("H:i",strtotime($model->delivery_datetime)); 
                        return !empty($model->delivery_datetime) ? date("Y-m-d h:i A", strtotime($model->delivery_datetime)) : "-"; 
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
                'template' => '{view} {update} {status}',
                'buttons' => [

                'update' => function ($url, $model) {
                    // changed here (add condition to update order from kiosk)@BJ 
                    $airportArray = array();
                    $roleId = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
                    if(($model->created_by == 10) || ($roleId == 10)){
                        $kioskAirportId = EmployeeAirportRegion::find()->select(['fk_tbl_airport_of_operation_airport_name_id'])->where(['fk_tbl_employee_id'=> Yii::$app->user->id])->all();
                        foreach($kioskAirportId as $value){
                            array_push($airportArray,$value->fk_tbl_airport_of_operation_airport_name_id);
                        }
                        if(in_array($model->fk_tbl_airport_of_operation_airport_name_id,$airportArray)){
                            return (
                                Html::a('<span class="glyphicon glyphicon-pencil"></span>',
                                $url = Url::toRoute(['order/kiosk-order-update','id'=>$model->id_order ]), 
                                [ 'title' => Yii::t('app', 'Update'), 'class'=>'','onclick'=>"checkRazoreStatus($model->id_order);" ])
                            );
                        } else {
                            return (
                                Html::a('<span class="glyphicon glyphicon-pencil"></span>',
                                $url = Url::toRoute(['order/kiosk-order-update','id'=>$model->id_order ]), 
                                [ 'title' => Yii::t('app', 'Update'), 'class'=>'','onclick'=>"checkRazoreStatus($model->id_order);" ])
                            );
                        }
                    }// changed here (add condition to update order from kiosk)@BJ 
                },
                'view' => function ($url, $model) { 
                    if($model->created_by != 10){
                          return (Yii::$app->user->identity->fk_tbl_employee_id_employee_role  != 4  ?
                          Html::a('<span  class="glyphicon glyphicon-eye-open"></span>',
                          $url = Url::toRoute(['order/view-kiosk','id'=>$model->id_order ]),
                          ['title' => Yii::t('app', 'View'), 'class'=>'btn btn-primary btn-xs', ]) : '');
                    } else {
                        return (Yii::$app->user->identity->fk_tbl_employee_id_employee_role  != 4  ?
                          Html::a('<span  class="glyphicon glyphicon-eye-open"></span>',
                          $url = Url::toRoute(['order/view-kiosk','id'=>$model->id_order ]),
                          ['title' => Yii::t('app', 'View'), 'class'=>'btn btn-primary btn-xs', ]) : '');
                    }
                },
                'status' => function ($url, $model) {
                    if ($model->fk_tbl_order_status_id_order_status != 21) {
                         $icon = 'glyphicon glyphicon-remove-circle';
                         $statusTitle = 'Cancel Order';
                         $status = 21;
                     }else{
                            $icon = 'glyphicon glyphicon-remove-circle';
                            $statusTitle = 'Canceled';
                            $status = '';
                     } 
                    if(($model->created_by == 10) && ($model->corporate_type == 1) && (!($model->fk_tbl_order_status_id_order_status == 8 || $model->fk_tbl_order_status_id_order_status == 20 || $model->fk_tbl_order_status_id_order_status == 17 ||  $model->fk_tbl_order_status_id_order_status == 9 || $model->fk_tbl_order_status_id_order_status == 6 || $model->fk_tbl_order_status_id_order_status == 18 || $model->fk_tbl_order_status_id_order_status == 23 || $model->fk_tbl_order_status_id_order_status == 21) && $model->service_type == 2) || (!($model->fk_tbl_order_status_id_order_status == 20 || $model->fk_tbl_order_status_id_order_status == 15 || $model->fk_tbl_order_status_id_order_status == 16 || $model->fk_tbl_order_status_id_order_status == 18 || $model->fk_tbl_order_status_id_order_status == 23 || $model->fk_tbl_order_status_id_order_status == 21) && $model->service_type == 1)){
                     	$current_date_time = date("Y-m-d H:i:s");
						$created_date_time = date('Y-m-d H:i:s', strtotime("+1 hours", strtotime($model->date_created)));
                     	if($current_date_time <= $created_date_time){
                            return (
                                Html::a('<span class="' . $icon . '"></span>',
                                $url = Url::toRoute(['order/cancel-order-status','id'=>$model->id_order]), 
                                [   'title' => Yii::t('app', $statusTitle), 'data' => [
                                    'confirm' => 'Are you sure you want to cancel this order?',
                                    'method' => 'post',
                                ]])
                            );
                        }
                    }
            	},
                ]
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
        let isChecked = $('#kiosk_order_list input[type=checkbox]').is(':checked');
        var order_ids = $("#selected_order_id").val();
        if(isChecked){
            // if ($("#ordersmsdetails-order_sms_title_id option:selected").text()) {
            if($("#ordersmsdetails-order_sms_title_id").val()) {
                $("#submit_sms_content").prop('disabled',false);
            } else {
                $("#submit_sms_content").prop('disabled',true);
            }
        }
    }

    var unblockSubmitbtn2 = function(){
        let isChecked = $('#kiosk_order_list input[type=checkbox]').is(':checked');
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
        $("#kiosk_order_list input[type=checkbox]").click(function(){
            var keys = $('#kiosk_order_list').yiiGridView('getSelectedRows');
            if(keys!=""){
                $("#selected_order_id").val(keys);
                $("#selected_orderIds").val(keys);
                unblockSubmitbtn();
                unblockSubmitbtn2();
            } else {
                $("#submit_sms_content").prop('disabled',true);
                $("#change_status_btn").prop('disabled',true);
            }
        });
    });

    $("#order_status").change(function(){
        unblockSubmitbtn2();
    });
    $(".clickexport").click(function() {
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
                        $("#headMsg").fadeTo(2000, 800).slideUp(800, function(){
                            $('#headMsg').empty();
                            
                        });
                    }else if(response.status ==200){
                        $('#headMsg').empty();
                        var path = "<?=Yii::$app->request->absoluteUrl?>";
                        var url = path.replace("r=order%2Fkiosk-orders", "r=order%2Fexport");
                        //location.href = url;
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
    // for flash msg fadeout
    setTimeout(function(){
        $("#session_msg").delay(3000).fadeOut();
    },1000);
</script>
