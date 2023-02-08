<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use app\models\OrderStatus;
use app\models\User;
use yii\helpers\Url;
use app\models\Order;
use kartik\daterange\DateRangePicker;

$this->title = 'Orders';
$role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
    $id_employee = Yii::$app->user->identity->id_employee;
    $client_id =$client['client_id'];
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-index" style="padding-top: 30px;">
<div class=""><span class="pull-right">
<?php


$gridColumns = [
            ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute'=>'flight_number',
                        'label'=>'Reference number',
                        'value' => function ($model) {
                            return $model->flight_number;
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
                        'attribute'=>'location', 
                        'label'=>'Destination',
                        'value' => function ($model) { 
                            return $model->location;
                        },
                        'filter'=>array('N/A'=>'N/A','GOI'=>'GOI','COK'=>'COK','JAI'=>'JAI','HYD'=>'HYD','GAU'=>'GAU','IXR'=>'IXR','VTZ'=>'VTZ','BBI'=>'BBI','PNQ'=>'PNQ','IXC'=>'IXC','DEL'=>'DEL','BLR'=>'BLR','CCU'=>'CCU','SXR'=>'SXR','IXB'=>'IXB', 'IMF'=>'IMF'),
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
                        'attribute'=>'meet_time_gate',
                        'header'=>'Gate Meet Time',
                        'value' => function ($model) { 
                            return $model->meet_time_gate;
                            //return ($model->fkTblOrderIdSlot != null) ? $model->fkTblOrderIdSlot->description : '-';
                        },
                       
                    ],
                    [
                        'attribute'=>'delivery_date',
                        'header'=>'Date of Delivery',
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
                        'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                        'header'=>'Airport',
                        'format' => 'raw',
                        'value' => function ($model) { 
                            return $model->getAirportName($model->fk_tbl_airport_of_operation_airport_name_id);
                        }, 
                        'filter'=>array("3"=>"KIAL","4"=>"VOBG","7"=>"RGAI","5"=>"VOJK","6"=>"VOYK","8"=>"VODG"),
                    ],

                    [
                        'attribute'=>'no_of_units',
                        'header'=>'Number of bags',
                        'value' => function ($model) { 
                            return $model->getluggagecount();
                        },
                    ],
                   
                    [
                        'attribute'=>'passive_tag',
                        'header'=>'Passive Tags',
                        'value' => function ($model) { 
                            return $model->getPassiveTags($model->id_order);
                            //return ($model->fkTblOrderIdSlot != null) ? $model->fkTblOrderIdSlot->description : '-';
                        },
                       
                    ],

                    [
                        'attribute'=>'payment_mode_excess',
                        'header'=>'Payment Mode',
                        'value' => function ($model) {
                            return isset($model->payment_mode_excess)?$model->payment_mode_excess : 'NA';
                        },
                    ],
                    //'excess_bag_amount',
                     [
                        'attribute'=>'excess_bag_amount',
                        'header'=>'Amount collected by Porter',
                        'value' => function ($model) {
                            return isset($model->excess_bag_amount)?$model->excess_bag_amount : '0';
                        },
                    ],
                    [
                        'attribute'=>'corporate_price',
                        'label'=>'Price',
                        'value' => function ($model) { 
                            return $model->corporate_price;
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
                        'label'=>'Related Order Status',
                        'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit;
                            return $data->related_order_id !=0 ? $data->relatedOrder->order_status : '-' ;
                        },
                    ],

            ];

User::downloadExportData($dataProvider,$gridColumns,'Orders');            
 ?>
</span></div>
    <h1><?= Html::encode($this->title) ?></h1>
    <div id="headMsg"></div>
    <div class="row">
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
                //'order_number',
                [
                    'attribute'=>'flight_number', 
                    'header'=>'Reference<br/> number',
                    'value' => function ($model) {
                        return $model->flight_number;
                    },

                ],
            
                [
                    'attribute'=>'travell_passenger_name',
                    'header'=>'Customer<br/> name',
                    'value' => function ($model) { 
                        return $model->travell_passenger_name;
                    },
                ],

                [
                            'attribute'=>'location', 
                            'label'=>'Destination',
                            'value' => function ($model) { 
                                return $model->location;
                            },
                            'filter'=>array('N/A'=>'N/A','GOI'=>'GOI','COK'=>'COK','JAI'=>'JAI','HYD'=>'HYD','GAU'=>'GAU','IXR'=>'IXR','VTZ'=>'VTZ','BBI'=>'BBI','PNQ'=>'PNQ','IXC'=>'IXC','DEL'=>'DEL','BLR'=>'BLR','CCU'=>'CCU','SXR'=>'SXR','IXB'=>'IXB', 'IMF'=>'IMF', 'NA'=>'NA'),
                        ],

                    /*    [
                    'attribute' => 'fk_tbl_order_id_pick_drop_location',
                    'label'=>'Sector',
                    'value' => function ($model) {
                        return Order::getPincodeSector($model->fk_tbl_order_id_pick_drop_location);
                    },
                    'filter'=>array("1"=>"North","2"=>"Sorth","3"=>"East","4"=>"West"),
                ],*/

                //'meet_time_gate',

                
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
                    'header'=>'Date Of <br> Service',
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
                                ],
                    ]),
                ],
                [
                    'attribute'=>'meet_time_gate',
                    'header'=>'Gate <br> Meet <br/>Time',
                    'value' => function ($model) { 
                        return $model->meet_time_gate;
                        //return ($model->fkTblOrderIdSlot != null) ? $model->fkTblOrderIdSlot->description : '-';
                    },
                
                ],

                [
                    'attribute'=>'delivery_date',
                    'header'=>'Date of <br/> Delivery',
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
                /*[
                    'attribute'=>'no_of_units',
                    'label'=>'Number of bags',
                    'value' => function ($model) { 
                        return $model->no_of_units;
                    },
                ],*/
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
                    'attribute'=>'no_of_units',
                    'header'=>'Number <br> of bags',
                    'value' => function ($model) { 
                        return $model->getluggagecount();
                    },
                ],

                [
                    'attribute'=>'passive_tag',
                    'header'=>'Passive <br/> Tags',
                    'value' => function ($model) { 
                        return $model->getPassiveTags($model->id_order);
                        //return ($model->fkTblOrderIdSlot != null) ? $model->fkTblOrderIdSlot->description : '-';
                    },
                
                ],

                //'payment_mode_excess',
            // 'excess_bag_amount',
                [
                    'attribute'=>'payment_mode_excess',
                    'header'=>'Payment <br/>Mode',
                    'value' => function ($model) {
                        return isset($model->payment_mode_excess)?$model->payment_mode_excess : 'NA';
                    },
                ],
                //'excess_bag_amount',
                [
                    'attribute'=>'excess_bag_amount',
                    'header'=>'<center> Excess <br/>Amount <br> Calculated</center>',
                    'value' => function ($model) {
                        return isset($model->excess_bag_amount)?$model->excess_bag_amount : '0';
                    },
                ],
                [
                    'attribute'=>'corporate_price',
                    'label'=>'Price',
                    'value' => function ($model) { 
                        return $model->corporate_price;
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
                            'attribute'=>'dservice_type',
                            'header'=>'Delivery <br/>Service <br/>Type',
                            'value' => function ($model) { 
                                return (($model->dservice_type == 1) ? "Normal" : (($model->dservice_type == 2) ? "Express" :(($model->dservice_type == 3) ? "Out station" : "")));
                            },
                            'filter'=>array("1"=>"Normal","2"=>"Express","3"=>"Out station"),
                ],     
                    
            
                [
                    'attribute'=>'fk_tbl_order_status_id_order_status',
                    'header'=>'Order <br/>Status',
                    'value' => function ($model) {
                        return $model->order_status;
                    },
                    'filter'=>ArrayHelper::map(OrderStatus::find()->all(),'id_order_status','status_name'),
                ],
                // 'order_status',
                [
                    'attribute'=>'related_order_id',
                    'format' => 'raw',
                    'header'=>'<center> Related<br/> Order <br> Status </center>',
                    'value' => function ($data, $key) { //print_r($model->orderSpotDetails);exit; 
                        if(Order::isItmanRealatedOrder($data->related_order_id) ==0){
                            if($data->related_order_id !=0){ 
                                if($data->relatedOrder){ 
                                    return Html::a($data->relatedOrder->order_status,['/order/user-order-update','id'=>$data->relatedOrder->id_order]);
                                }else{ 
                                    return '-'; 
                                } 
                            }else{ 
                                return '-'; 
                            }
                        }else{
                            return 'T-man Order';
                        }
                        
                    },
                ],

                [
                    'class' => 'yii\grid\ActionColumn', 
                    'contentOptions' => ['style' => 'width:80px;'],
                    'header'=>'Actions',
                    'template' => '{view} {download}',
                    'buttons' => [


                    'view' => function ($url, $model) { 
                            return (
                            Html::a('<span class="glyphicon glyphicon-eye-open"></span>',
                            $url = Url::toRoute(['order/user-order-update','id'=>$model->id_order ]), 
                            [ 'title' => Yii::t('app', 'View'), 'class'=>'', ])
                            );
                        },
                    'download' => function ($url, $model) { 
                                return 
                                Html::a('<span  class="glyphicon glyphicon-download-alt"></span>',
                                $url = Url::toRoute(['order/order-download','id_order'=>$model->id_order]), 
                                [ 'title' => Yii::t('app', 'download'), 'class'=>'btn btn-primary btn-xs']);
                            },
                    /*'count' => function ($url, $model) { 
                                return ($model->unreadCount() > 0 ?
                                Html::a('<span  class="">'.$model->unreadCount().'</span>',
                                $url = '', 
                                ['title' => Yii::t('app', 'download'), 'class'=>'btn btn-primary btn-xs', ]) : '');
                            },*/
                    ]
                ],
            ],
        ]); ?>
        
    </div>
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
            if(start_date != '' && end_date != ''&& email != ''){
                $.ajax({ 
                    type: "POST",
                    dataType: "json",
                    data :{"start_date":start_date,"end_date":end_date,"role_id":role,"id_employee":id_employee ,"email":email},
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
                            var url = path.replace("r=order%2Findex", "r=order%2Fexport");
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