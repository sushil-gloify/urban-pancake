<?php
    use yii\helpers\Html;
    use yii\grid\GridView;
    use yii\widgets\ActiveForm;
    use kartik\select2\Select2;
    use yii\helpers\ArrayHelper;
    use app\models\BankDetails;
    use app\models\CustomizeBank;
    use app\models\SupportedPaymentMethod;
    use app\models\AirportOfOperation;
    use app\models\CityOfOperation;
    use app\models\SubscriptionAirport;
    use app\models\SubscriptionRegion;
    use app\models\SubscriptionPaymentRestriction;
    use app\models\SubscriptionTokenMap;
    use app\models\ThirdpartyCorporate;
    use app\models\User;
    use kartik\time\TimePicker;
    use kartik\datetime\DateTimePicker;
    use kartik\daterange\DateRangePicker;
    // use yii\bootstrap4\Accordion;
    // use yii\base\Widget;

    $this->title = 'Subscription Details';
    // echo "<pre>";print_r($this->params['breadcrumbs']);die;
    // $this->params['breadcrumbs'][] = ['label' => 'Purchase Subscriptions', 'url' => ['index']];
    $this->params['breadcrumbs'][] = $this->title;
    $id_employee = Yii::$app->user->identity->id_employee;
    $result = Yii::$app->Common->get_super_subscription_details($id_employee);
?>

<input  type="hidden" id="check_id" value="<?php echo $result['subscription_id'];?>">
<div class="purchase-subscription">
    <div class="">
        <span class="pull-right">
            <?php $gridColumns = [
                    [
                        'attribute' => 'payment_transaction_id',
                        'header' => 'Payment Transaction Id',
                        'value' => function($model){
                            return ucwords($model->payment_transaction_id);
                        }
                    ],
                    [
                        'attribute' => 'confirmation_number',
                        'header' => 'Subscription Name',
                        'value' => function ($model) {
                            $subscriber_name = Yii::$app->db->CreateCommand("SELECT subscriber_name FROM tbl_super_subscription where subscription_id =".$model->subscription_id)->queryOne();
                            return  strtoupper($subscriber_name['subscriber_name']);
                        },
                    ],
                    [
                        'attribute' => 'confirmation_number',
                        'header' => 'Confirmation Number',
                        'value' => function ($model) {
                            return  strtoupper($model->confirmation_number);
                        },
                    ],
                    'no_of_usages',
                    'remaining_usages',
                    [
                        'attribute' => 'paid_amount',
                        'header' => 'Paid Amount',
                        'value' => function ($model) {
                            return  number_format(($model->paid_amount),'2','.','');
                        },
                    ],
                    'redemption_cost',
                    'subscription_cost',
                    [
                        'attribute' => 'payment_status',
                        'header' => 'Payment Status',
                        'value' => function ($model) {
                            return  !empty($model->payment_status) ? ucwords($model->payment_status) : "Not Paid";
                        },
                    ],
                    [
                        'attribute' => 'payment_date',
                        'header' => 'Payment Date',
                        'value' => function ($model) {
                            return  $model->payment_date ? date('d-m-Y',strtotime($model->payment_date)) : '00-00-0000';
                        },
                    ],
                    [
                        'attribute' => 'expire_date',
                        'header' => 'Expire Date',
                        'value' => function ($model) {
                            return  $model->expire_date ? date('d-m-Y',strtotime($model->expire_date)) : '00-00-0000';
                        },
                    ],
                    [
                        'attribute' => '',
                        'header' => 'Employee',
                        'value' =>function($model){
                            $result = Yii::$app->Common->getAssignEmployee($model->subscription_transaction_id);
                            return ($result) ? $result : "--Not Set--";
                        }
                    ],
                   
                    ];
                    User::downloadExportData($dataProvider,$gridColumns,'Subscription_details');  
            // User::downloadExportInsuranceData($dataProvider, $gridColumns,'Subscription_details');  
            ?>
        </span>
    </div>
    <h1><?= Html::encode($this->title) ?></h1>
    <!-- check -->
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'id' => 'index_subscription_list',
        'rowOptions'=>function($model){
                      
        },

        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'payment_transaction_id',
                'header' => 'Payment Transaction Id',
                'value' => function($model){
                    return ucwords($model->payment_transaction_id);
                }
            ],
            [
                'attribute' => '',
                'header' => 'Subscription Name',
                'value' => function ($model) {
                    $subscriber_name = Yii::$app->db->CreateCommand("SELECT subscriber_name FROM tbl_super_subscription where subscription_id =".$model->subscription_id)->queryOne();
                    return  strtoupper($subscriber_name['subscriber_name']);
                },
            ],
            [
                'attribute' => 'confirmation_number',
                'header' => 'Confirmation Number',
                'value' => function ($model) {
                    return  strtoupper($model->confirmation_number);
                },
            ],
            [
                'attribute' => '',
                'header' => 'Total Usages',
                'value' => function ($model) {
                    return $model->no_of_usages;
                },
            ],
            'remaining_usages',
            [
                'attribute' => 'remaining_balence',
                'header' => 'Remaining Amount',
                'value' =>function($model){
                    if($model->remaining_balance < 0 &&  $model->remaining_balance != -1){
                        $total = $model->balence_value;
                       
                    } else {
                        $total = round($model->remaining_balance + $model->balence_value);
                    }
                    return number_format(($total),'2','.','');
                    // return  $model->remaining_balance ? number_format(($model->remaining_balance),'2','.','') : '---';
                
                }
            ],
            [
                'attribute' => '',
                'header' => 'Paid Amount',
                'value' => function ($model) {
                    return  number_format(($model->paid_amount),'2','.','');
                },
            ],
            [
                'header' => 'redemption_cost',
                'value' => function ($model) {
                    return $model->redemption_cost;
                }
            ],
            [
                'header' => 'subscription_cost',
                'value' => function ($model) {
                    return $model->subscription_cost;
                }
            ],
            [
                'attribute' => '',
                'header' => 'Payment Status',
                'value' => function ($model) {
                    return  !empty($model->payment_status) ? ucwords($model->payment_status) : "Not Paid";
                },
            ],
            [
                'attribute' => 'payment_date',
                'header' => 'Payment Date',
                'value' => function ($model) {
                    // echo "<pre>";print_r($model->payment_date);die; 
                    return  ($model->payment_date != "0000-00-00 00:00:00") ? date('d-m-Y',strtotime($model->payment_date)) : '00-00-0000';
                },
                'filter' => DateRangePicker::widget([
                    'model' => $searchModel, 
                    'attribute' => 'payment_date',
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
                'attribute' => '',
                'header' => 'Expire Date',
                'value' => function ($model) {
                    return  $model->expire_date ? date('d-m-Y',strtotime($model->expire_date)) : '00-00-0000';
                },
            ],
            [
                'attribute' => '',
                'header' => 'Payment Month',
                'value' => function ($model) {
                    return  $model->payment_date ? date('F',strtotime($model->payment_date)) : '-';
                },
                
            ],
            
            [
                'attribute' => '',
                'header' => 'Employee',
                'value' =>function($model){
                    $result = Yii::$app->Common->getAssignEmployee($model->subscription_transaction_id);
                    return ($result) ? $result : "--Not Set--";
                }
            ],
            
           
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width:100px;'],
                'header' => '<span style="color:#3c8dbc;">Action</span>',
                'template' => '{map-employee} {add-usages}',
                'buttons' => [
                    'map-employee' => function($url, $model) {
                        if(Yii::$app->user->identity->fk_tbl_employee_id_employee_role == 17){
                            return (Yii::$app->Common->getAssignEmployee($model->subscription_transaction_id) == "") ? Html::a('<span class="glyphicon glyphicon-plus"></span>', $url, ['title' => Yii::t('app', 'lead-view')]) : "";
                        } else {
                            return false;
                        }
                    },
                    'add-usages' => function($url, $model) {
                        if((Yii::$app->user->identity->fk_tbl_employee_id_employee_role == 1) && ($model->add_usages_status == "enable")){
                            return "<button class='glyphicon glyphicon-plus' onclick='updateUsages(".$model->subscription_transaction_id.");'></button>";
                        }
                    }
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if(Yii::$app->user->identity->fk_tbl_employee_id_employee_role == 17){
                        if ($action === 'map-employee') {
                            $url ='index.php?r=super-subscription/employee-allocation&id='.$model->subscription_transaction_id;
                            return $url;
                        }
                    } else {
                        return false;
                    }
                }
            ]
        ],
    ]); ?>
</div>
<!-- Modal for update Usages -->
    <div class="modal fade" id="bsModal3" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="mySmallModalLabel">Update Usages</h4>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <legend>Info :<b id="confirm_no"></b></legend>
                        <span>Usages : <b id="total_usage"></b></span><br>
                        <span>Remaining Usages : <b id="remain_usage"></b></span>
                    </fieldset><br>
                    <div class="form-group">
                        <label>Usages :</label>
                        <input type="number" min="1" max="8" name="usages_count" id="usages_count" class="form-control">
                        <input type="hidden" name="subscription_transaction_id" id="subscription_transaction_id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="submitUsages();" >Submit</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<!-- Modal for update Usages -->

<script>
    $( document ).ready(function(){
        var check_id = $('check_id').val();
        if(check_id != null){
            checkRazoreStatus();
        }
      
    })
    function checkRazoreStatus(){
        $.get( "index.php?r=super-subscription/razorpay-status",{subscription_id:"<?php echo $result->subscription_id; ?>"}).done(function( data ) {
        });
    }  

    var updateUsages = function(id){
        // $("#bsModal3").modal('show');
        $.ajax({
            type    : 'POST',
            data    : {'subscription_transaction_id' : id},
            cache   : true,
            url     : 'index.php?r=super-subscription/get-subscription-details',
            // beforeSend: function(){
            //     $(".loaderTop").show();
            // },
            // complete: function(){
            //     $(".loaderTop").hide();
            // },
            success : function(response) {
                var data = JSON.parse(response);
                if(data.status == true){
                    $("#bsModal3").modal('show');
                    $("#confirm_no").text(data.result.confirmation_number.toUpperCase());
                    $("#total_usage").text(data.result.no_of_usages);
                    $("#remain_usage").text(data.result.remaining_usages);
                    $("#subscription_transaction_id").val(id);
                } else {
                    $("#bsModal3").modal('hide');
                }
            }
        });
    }

    var submitUsages = function(){
        var usages_count = $("#usages_count").val();
        var subscription_transaction_id = $("#subscription_transaction_id").val();
        if(usages_count != ""){
            $.ajax({
                type    : 'POST',
                data    : {'subscription_transaction_id' : subscription_transaction_id, 'usages_count' : usages_count},
                cache   : true,
                url     : 'index.php?r=super-subscription/update-subscription-useage',
                success : function(response) {
                    var data = JSON.parse(response);
                    if(data.status == true){
                        $("#bsModal3").modal('hide');
                        alert(data.message);
                        window.location.reload();
                    } else {
                        $("#bsModal3").modal('hide');
                        alert(data.message);
                    }
                }
            });
        } else {
            alert("Please add usgaes.");
        }
    }
</script>