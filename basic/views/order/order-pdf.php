<?= $this->render('layouts/mail_header') ?>
<?php
if(isset($order_details_pdf)){
    $data = $order_details_pdf[0];
}
$body_title = "Order #".$data['order']['order_number'];
$customer_name = '';

if($data['order']['corporate_type']==1){
    $customer_name = $data['order']['travell_passenger_name'];
} else{
    $customer_name = $data['order']['customer_name'];
}
?>

    <div style=" padding: 12px; background: white;border: 1px solid;border-color: #d8d6d6;margin-top:15px;font-family:sans-serif;font-size:14px;line-height:20px;">
        <div style="font-weight: bold;"> <?php echo $body_title?></div>
    </div>
    <div style="padding: 12px;background: white;border: 1px solid;border-color: #d8d6d6;margin: 8px 0px;font-family:sans-serif;font-size:14px;line-height:20px;">
        <div>Dear <b><?php echo $customer_name;?></b> </div>
        <div >

            <?php if($data['order']['service_type']== 1) { ?>
                <p> The status of your order is <b>Confirmed.</b> Order Value: Rs.<b><?php echo $data['order']['amount_paid'] ?>.</b> All receipts for cash/Card/razorpay transactions will be sent on successful delivery of the order. <b>Signing the Security Declaration</b> is <b>MANDATORY.</b></p>
                <p>Please keep the same filled and signed before we arrive to pick the order. Meet with CarterX personnel is <b>Mandatory</b> before the passenger enters the terminal. Order details are enclosed. Outstation timelines: upto 3 days. </p>

            <?php } else {?>

                <p> the status has been Confirmed. Order Value: Rs.<b><?php echo $data['order']['amount_paid'] ?></b> All receipts for cash/Card/razorpay transactions will be sent on successful delivery.</p>
                <p>Meet with CarterX personnel is <b>Mandatory</b> at the terminal. Order details are enclosed. Outstation timelines: upto 3 days. </p>

            <?php } ?>
        </div>
    </div>

<?= $this->render('layouts/mail_footer') ?>