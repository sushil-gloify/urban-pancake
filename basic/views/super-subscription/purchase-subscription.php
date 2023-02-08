<script src="https://checkout.razorpay.com/v1/checkout.js"></script>    

<?php
    use yii\helpers\Html;
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
    use app\models\SubscriptionPaymentLinkDetails;

    $this->title = 'Purchase Subscription';
   
    // echo "<pre>";print_r($this->params['breadcrumbs']);die;
    // $this->params['breadcrumbs'][] = ['label' => 'Purchase Subscriptions', 'url' => ['index']];
    $this->params['breadcrumbs'][] = $this->title;
    $razorpay_secret_key = Yii::$app->params['razorpay_secret_key'];
    $razor_all_key = Yii::$app->params['razorpay_api_key'];

    $total_cost = 0;
    $gst_cost = 0;
    $total_gst_cost = 0;
   
    if(isset($purchase_details)){
        $total_cost = $purchase_details->subscription_cost;
        $gst_cost = ($total_cost * $purchase_details->subscription_GST) / 100;
        $total_gst_cost = $total_cost + $gst_cost;
    }
    $today = date('Y-m-d'.' 23:59:59');
    $result = SubscriptionPaymentLinkDetails::find()->where(['payment_subscription_id' => $purchase_details->subscription_id,'payment_status' => 'zero'])->one();
    $payment_url = "";
    if(!empty($result)){
        $payment_url = $result['payment_link_id'];
    }
   
    //echo '<pre>';print_r($purchase_details); exit;
?>
<style>
    * {
        margin: 0;
        padding: 0; 
    }

    html {
        font-size: 20px;
        font-family: "Helvetica Neue", Helvetica, Arial, 'sans-serifoto';
    }

    body {
        /* background: linear-gradient(90deg, #ff9966, #ff5e62); */
    }

    .wrapper {
        height: 65vh;
        position: realtive;
    }

    .fas.fa-envelope {
        color: #fff;
        font-size: 2rem;
        background: #333;
        padding: 1rem;
        border-radius: 100%;
        margin: 0 0 1rem 0;
    }

    .card-content {
        max-width: 30rem;
        background-color: #fff;
        position: relative;
        top: 50%;
        left: 50%;
        transform: translate(-50%,-50%);
        border-radius: 1rem;
        padding: 2rem .5rem;
        box-shadow: 1px 1px 2rem rgba(0,0,0,.3);
        text-align: center;
    }

    .card-content h1 {
        /* text-transform: uppercase; */
        margin: 0 0 1rem 0;
    }

    .card-content p {
        font-size: .8rem;
        margin: 0 0 0.5rem 0;
    }

    input {
        padding: .8rem 1rem;
        width: 40%;
        border-radius: 5rem;
        outline: none;
        border: .1rem solid #d1d1d1;
        font-size: .7rem;
    }

    ::placeholder {
        color: #d1d1d1;
    }

    .subscribe-btn {
        padding: .8rem 2rem;
        border-radius: 5rem;
        background: linear-gradient(90deg, #00bff3, #00bff3);
        color: #fff;
        font-size: .7rem;
        border: none;
        outline: none;
        cursor: pointer;
    }
    .text-align{
        text-align : left;
    }
    .img-class{
        top: 50%;
        position: absolute;
        left: 50%;
    }
    .loadingimage{
        background-color: rgba(32, 122, 184, 0.3);
        z-index: 99999;
        position: fixed;
        opacity: 1;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }       
    
</style>
<div class="purchase-subscription">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="wrapper">
        <div class="card-content">
            <div class="container">
                <h2>Subscription Details</h2>
            </div>
            <div class="form-input">
                <p>No of Usages : <?= (isset($purchase_details) ? $purchase_details->no_of_usages : 0) . "<b> Orders</b>"; ?></p>
                <p>Redemption Cost : <?= "<b>₹ </b>".(isset($purchase_details) ? $purchase_details->redemption_cost : 0); ?></p>
                <p>Subscription Cost : <?= "<b>₹ </b>".(isset($purchase_details) ? $purchase_details->subscription_cost : 0); ?></p>
                <p>Subscription GST : <?= ((isset($purchase_details)) ? $purchase_details->subscription_GST : 0)."<b> %</b>"; ?></p>
                <hr>
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-6 form-group">
                        <select name="unit" id="unit" class="form-control">
                            <option value ="1" <?php if($result['payment_unit'] == 1) echo "selected";?>>1 Unit - 1 Confirmation Number</option>
                            <option value ="2" <?php if($result['payment_unit'] == 2) echo "selected";?>>2 Unit - 2 Confirmation Number</option>
                            <option value ="3" <?php if($result['payment_unit'] == 3) echo "selected";?>>3 Unit - 3 Confirmation Number</option>
                            <option value ="4" <?php if($result['payment_unit'] == 4) echo "selected";?>>4 Unit - 4 Confirmation Number</option>
                            <option value ="5" <?php if($result['payment_unit'] == 5) echo "selected";?>>5 Unit - 5 Confirmation Number</option>
                            <option value ="6" <?php if($result['payment_unit'] == 6) echo "selected";?>>6 Unit - 6 Confirmation Number</option>
                            <option value ="7" <?php if($result['payment_unit'] == 7) echo "selected";?>>7 Unit - 7 Confirmation Number</option>
                            <option value ="8" <?php if($result['payment_unit'] == 8) echo "selected";?>>8 Unit - 8 Confirmation Number</option>
                        </select>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                
                <p>Total Cost : <b>₹ </b><span id="total_cost"><?= sprintf('%0.2f',$total_cost); ?></span></p>
                <p>GST Cost : <b>₹ </b><span id="gst_cost"><?= sprintf('%0.2f', $gst_cost); ?></span></p>
                <hr>
                <p>Total Cost with GST : <b>₹ </b><span id="total_gst_cost"><?= round($total_gst_cost); ?></span></p><br>
                <div>
                    <button type="button" id="btn_confirm" class="btn btn-primary">Confirm</button>
                </div>
                <div>
                    <button type="button" id="btn_done" class="btn btn-primary">Done</button>
                </div>
                <div>
                    <a class="subscribe-btn" id="payment_a_url">Proceed for Payment</a>
                </div></br>
               

            </div>
        </div>
    </div>
</div>
<div id="yourParentElement"></div>
<button style="display:none;" id="rzp-button1">Pay</button>
<script type="text/javascript">
    $(document).ready(function(){
        var payment_status = 'pending';
	    var transaction_id = ''; 
        var subscription_cost = "<?= (isset($purchase_details) ? $purchase_details->subscription_cost : 0); ?>";
        if(subscription_cost == 0){
            $("#btn_confirm").css("display","none");
            $("#payment_a_url").css("display","none");
            $("#btn_done").css("display","initial");
        } else {
            $("#payment_a_url").css("display","none");
            // Check payment url exist so showing payment button
            var payment_url = "<?= $payment_url; ?>";
            if(payment_url != ""){
                calculation();
                $("#payment_a_url").css("display","initial");
                $("#unit").attr('readonly', true).css("pointer-events", "none");
                $("#btn_confirm").css("display","none");
                $("#btn_done").css("display","none");
            } else {
                $("#btn_confirm").css("display","initial");
                $("#btn_done").css("display", "none");
            }

            // function for create new payment url 
            $("#btn_confirm").click(function(){
                $("#btn_confirm").attr("disabled",'disabled');
                $("#btn_done").trigger('click');
            });
          
        }
   

        // when change unit so call calculation function
        $("#unit").change(function(){
            calculation();
        });
        $("#payment_a_url").click(function(){
            $("#btn_confirm").attr("disabled",'disabled');
            payment_by_rozer_pay();
        });
            
        // 
        $("#btn_done").click(function(){
            let subscription_id = "<?= $purchase_details->subscription_id; ?>";
            let razorpayStatus = "<?= $purchase_details->razorpay_status; ?>";
            let unit = $("#unit").val();
            $.ajax({
                type    : 'POST',
                data    : {"subscription_id" : subscription_id,"unit": unit, "razorpay_status" : razorpayStatus},
                cache   : true,
                url     : 'index.php?r=super-subscription/create-subscription-number',
                success : function(response){
                    alert("Subscription has been done.")
                    $("#payment_a_url").css("display","none");
                    $("#btn_confirm").css("display","none");
                    var path = "<?=Yii::$app->request->absoluteUrl?>";
                    var url = path.replace("r=super-subscription%2Fpurchase-subscription", "r=super-subscription%2Fsubscription-details");
                    location.href = url;

                }
            });
        });
        // calculation function 
        function calculation(){ 
            var total_cost = 0;
            var gst_cost = 0;
            var total_gst_cost = 0;
            let unit = ($("#unit").val() != "") ? $("#unit").val() : 1;
            let subscription_cost = "<?= $purchase_details->subscription_cost; ?>";
            let subscription_GST = "<?= $purchase_details->subscription_GST; ?>";

            total_cost = unit * subscription_cost;
            gst_cost = (total_cost * subscription_GST) / 100;
            total_gst_cost = total_cost + gst_cost;

            $("#total_cost").text(total_cost);
            $("#gst_cost").text(gst_cost);
            $("#total_gst_cost").text(total_gst_cost);
        }
        //payment by razorpay
        function payment_by_rozer_pay(){
        
            var seceret_key = "<?= $razorpay_secret_key; ?>";  
            var final_amt =$("#total_gst_cost").text(); 
            var options = {
                "key": "<?= $razor_all_key; ?>",
                "amount": parseInt(final_amt*100), // 2000 paise = INR 20
                "currency": 'INR',
                "name": "CarterPorter",
                "description": "Payment towards Carter",
                "image": "https://cdn.razorpay.com/logos/Du4P7LfElD9azm_medium.jpg",
                "captured": true,
                "handler": function (response){
                //alert(response.razorpay_payment_id);
                console.log(response.razorpay_payment_id);
                transaction_id=response.razorpay_payment_id;
                payment_status='paid';
                console.log(transaction_id);
                purchase_finaly_submit_to_database();
                },
                "notes": {
                "address": "Donations"
                },
                "theme": {
                "color": "#F37254"
                },
                "prefill": {
                    "name" : "<?= $purchase_details->subscriber_name; ?>",
                    "contact": "<?= $purchase_details->primary_contact; ?>",
                    "email":    "<?= $purchase_details->primary_email; ?>",
                },
            };
            var rzp1 = new Razorpay(options);

            document.getElementById('rzp-button1').onclick = function(e){
                rzp1.open();
                e.preventDefault();
            }
            $('#rzp-button1').trigger('click');
        }
        //purchase details submit into database
        function purchase_finaly_submit_to_database(){

            let unit = $("#unit").val();
            let totalGstCost = $("#total_gst_cost").text();
            let razorpayStatus = payment_status;
            let transaction_ids = transaction_id;
            let subscription_id = "<?= $purchase_details->subscription_id; ?>";
            var user_req = {
                "total_gst_cost" : totalGstCost, "unit": unit, "razorpay_status" : razorpayStatus,"subscription_id" : subscription_id,"transaction_id":transaction_ids
            }
            showLoadingImage();
            $.ajax({
                type     : 'POST',
                data     : user_req,
                cache    : true,
                url      : 'index.php?r=super-subscription/create-payment-link',
                success  : function(response) {
                   
                    var data = JSON.parse(response);
                    hideLoadingImage();
                    if(data.status == 1){
                        console.log('sucess');
                        $("#payment_a_url").css("display","none");
                        $("#btn_confirm").css("display","initial");
                        var path = "<?=Yii::$app->request->absoluteUrl?>";
                        var url = path.replace("r=super-subscription%2Fpurchase-subscription", "r=super-subscription%2Fsubscription-details");
                        location.href = url;
                        
                    } else {
                        $("#payment_a_url").css("display","none");
                        $("#btn_confirm").css("display","initial");
                    }
                }
            });
        }
        function showLoadingImage() {
            $('#yourParentElement').append('<div class="loadingimage" id="loading-image"><img class="img-class" src="<?php echo Yii::$app->params['site_url'].Yii::$app->params['image_path'].'loading.gif'; ?>" alt="Loading..." /></div>');
        }

        function hideLoadingImage() {
            $('#yourParentElement').remove();
        }
        
       
});

</script>
