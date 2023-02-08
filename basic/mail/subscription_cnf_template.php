<!DOCTYPE html>
<html>
<?php
    $body_title = "Order #".$data['order_details']['order']['order_number'];
    $customer_name = '';
    $GST_no ="29AAGCC8445A1ZP";
    $city_name ="Bengaluru";
    // Get GST Number, Get No of items String, Get name of city,
    // echo "<pre>";print_r($data);die;
    
    if(!empty($data['order_details']['order']['id_order'])){
        if(!empty($data['order_details']['order']['confirmation_number'])){
            $TTU = $data['order_details']['subscription_details']['no_of_usages'];
            $SC = $data['order_details']['subscription_details']['paid_amount'];
            $PUC = $SC / $TTU;
            $RU = $data['order_details']['subscription_details']['remaining_usages'];
            $VP = $RU * $PUC;

            $confirmation_id = $data['order_details']['order']['confirmation_number'];
            $bag_count = $data['order_details']['order']['no_of_units'];
            $travel_type = $data['order_details']['order']['terminal_type'];
            $pincode2 = $data['order_details']['order']['location_pincode'];
            $pincode1 = Yii::$app->db->CreateCommand("select airport_pincode from tbl_airport_of_operation where airport_name_id = '".$data['order_details']['order']['airport_id']."'")->queryOne()['airport_pincode'];
            $access_token = Yii::$app->db->CreateCommand("SELECT access_token FROM tbl_thirdparty_corporate where fk_corporate_id = '".$data['order_details']['corporate_details']['corporate_detail_id']."'")->queryOne()['access_token'];
            $result = Yii::$app->Common->getOutstationCalculation($access_token,$confirmation_id,$bag_count,$travel_type,$pincode1,$pincode2);
            $detail_of_useage = false;
            $redemption_cost = $data['order_details']['subscription_details']['redemption_cost'];
            $subscription_cost = $data['order_details']['subscription_details']['subscription_cost'];
            $gst_percent = $data['order_details']['subscription_details']['gst_percent'];
            $no_of_usages = $data['order_details']['subscription_details']['no_of_usages'];
            // $remaining_usages = $data['order_details']['subscription_details']['no_of_usages'] - $data['order_details']['order']['exhaust_usages'];
            $no_of_units = $data['order_details']['order']['no_of_units'];
            if($data['order_details']['order']['refund'] == 1){
                $extra_usages = $data['order_details']['subscription_details']['no_of_usages']-$data['order_details']['order']['usages_used'];
            }else{
                $extra_usages =$data['order_details']['order']['usages_used'];
            }
            if($data['order_details']['order']['exhaust'] == 1){
                if($data['order_details']['subscription_details']['remaining_usages'] == 0){
                    $remaining_usages = 0;
                }else{
                    $remaining_usages =$data['order_details']['subscription_details']['remaining_usages'] - $data['order_details']['order']['usages_used'];
                }
                
            }elseif($data['order_details']['order']['confirm'] == 1){
                $remaining_usages = $data['order_details']['order']['remaining_usages'] - $data['order_details']['order']['usages_used'];
              
            }else{
                $remaining_usages = $data['order_details']['order']['remaining_usages'];
            }
            
        } else {
            $VP = 0;
            $detail_of_useage = true;
        }
    } else {
        $VP = 0;
        $detail_of_useage = true;
    }
    // echo "<pre>";print_r($data);die;
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="">

</head>

<body style="">
    <div style="margin:0 20px;">
        <div style="font-family:sans-serif;font-size:12px;">
            <div>
                <div style="margin:0 20px;padding:18px 0;border-bottom:1px solid">
                    <table>
                        <tr>
                            <td style="width:50%;"><img src="https://carterporter.in/assets/images/Carterx-Logo.png" width="80px" height="30px" alt=""></td>
                            <td style="width:50%; font-size:14px;color:#3ba3c5;font-weight:bold;text-align:right">Thank you for placed subscription order.</td>
                        </tr>
                    </table>
                </div>
                <div style="margin:0 20px;margin-top:13px;border-bottom:1px solid;line-height:20px;">
                    <table style="line-height:20px; ">
                        <tr>
                            <td style="width:100%;padding-bottom:10px;">
                                <div style="font-size:11px;"> Subscription confirmation number::
                                    <?php echo !empty($data['cnf']) ? strtoupper($data['cnf']) : strtoupper($data['order_details']['subscription_details']['confirmation_number']);?>
                                </div>
                                <div style="font-size:11px;"> Name of Subscription Name:
                                    <?php echo !empty($data['Name_of_Subscription_Token']) ? strtoupper($data['Name_of_Subscription_Token']) : strtoupper($data['order_details']['subscription_details']['subscriber_name']);?>
                                </div>
                                <div style="font-size:11px;"> Current Date: 
                                    <?php echo !empty($data['CurrentDate']) ? $data['CurrentDate'] : date('Y-m-d');?>
                                </div>
                                <div style="font-size:11px;">Date of Purchase:
                                    <?= !empty($data['DateofPurchase']) ? $data['DateofPurchase'] : (isset($data['order_details']['subscription_details']['payment_date']) ? date('Y-m-d',strtotime($data['order_details']['subscription_details']['payment_date'])) : '0000-00-00'); ?>
                                </div>
                                <div style="font-size:11px;">Valid Till:
                                    <?= !empty($data['ValidTill']) ? $data['ValidTill'] : (isset($data['order_details']['subscription_details']['expire_date']) ? date('Y-m-d',strtotime($data['order_details']['subscription_details']['expire_date'])) : '0000-00-00'); ?>
                                </div>
                                <div style="font-size:11px"> Total No of Usages: 
                                   <?php if(!empty($data['total_useage'])){
                                    echo $data['total_useage'];
                                   }else if(!empty($no_of_usages)){
                                    echo $no_of_usages;
                                   }else{
                                    echo 0;
                                   }
                                  ?>
                                </div>
                                <div style="font-size:11px"> Value Pending:
                                    <?= !empty($data['value_pending']) ? $data['value_pending'] : (!empty($VP) ? $VP : 0); ?>
                                </div>
                                <div style="font-size:11px">Number of Usages Used: 
                                    <?= !empty($data['number_of_useage_used']) ? $data['number_of_useage_used'] : (isset($data['order_details']['order']['usages_used']) ? $data['order_details']['order']['usages_used'] : 0); ?>
                                </div>
                                <div style="font-size:11px">Details of Usage: 
                                    <?php if($detail_of_useage){ ?>
                                    <?= !empty($data['detail_of_useage']) ? $data['detail_of_useage'] :'NA'; ?>
                                    <?php } else { if($data['order_details']['order']['delivery_type'] == 2){ ?>
                                        <table style="border: 1px solid #337ab7;">
                                            <tr>
                                                <td style="width:50%;">
                                                    <div style="font-size:11px;"><b>Subscription Cost : </b><?= $result['price_breakup']['subscription_cost']; ?> </div>
                                                    <div style="font-size:11px;"><b>Subscription GST Cost : </b><?= $result['price_breakup']['subscription_cost_gst']; ?> </div>
                                                    <div style="font-size:11px;"><b>No of Usages : </b><?= $result['price_breakup']['no_of_usages']; ?> </div>
                                                    <div style="font-size:11px;"><b>Remaining Usages : </b><?= $result['price_breakup']['remaining_usages']; ?> </div>
                                                    <div style="font-size:11px;"><b>Per Usages Cost : </b><?= $result['price_breakup']['subscription_cost']; ?> </div>
                                                    <div style="font-size:11px;"><b>Per Usages GST Cost : </b><?= $result['price_breakup']['subscription_cost']; ?> </div>
                                                    <div style="font-size:11px;"><b>No of Bags : </b><?= $data['order_details']['order']['no_of_units']; ?> </div>
                                                    <div style="font-size:11px;"><b>Conveyance Usages : </b><?= $result['price_breakup']['convayance_usages']; ?> </div>
                                                    <div style="font-size:11px;"><b>Exhaust Usages : </b><?= $result['price_breakup']['exhaust_usages']; ?> </div>
                                                </td>
                                            </tr>
                                        </table>
                                    <?php } else if($data['order_details']['order']['delivery_type'] == 1){?>
                                        <table style="border: 1px solid #337ab7;">
                                            <tr>
                                                <td style="width:50%;">
                                                    <div style="font-size:11px;"><b>Redemption Cost(per Bag) : </b><?= !empty($redemption_cost) ? $redemption_cost : 0;//!empty($result['price_breakup']['per_bag_redemption_cost']) ? $result['price_breakup']['per_bag_redemption_cost'] : 0; ?> </div>
                                                    <div style="font-size:11px;"><b>GST Percent : </b><?= !empty($gst_percent) ? $gst_percent : 0;//$result['price_breakup']['gst_percent']; ?> </div>
                                                    <div style="font-size:11px;"><b>No of Usages : </b><?= !empty($no_of_usages) ? $no_of_usages :0;//$result['price_breakup']['no_of_usages']; ?> </div>
                                                    <div style="font-size:11px;"><b>Remaining Usages : </b><?= !empty($remaining_usages) ? $remaining_usages : 0; //$result['price_breakup']['remaining_usages']; ?> </div>
                                                    <div style="font-size:11px;"><b>No of Bags : </b><?= !empty($no_of_units) ? $no_of_units : 0;//$data['order_details']['order']['no_of_units']; ?> </div>
                                                    <div style="font-size:11px;"><b>Exhaust Usages  : </b><?= !empty($extra_usages) ? $extra_usages : 0;//$result['price_breakup']['exhaust_usages']; ?> </div>
                                                </td>
                                            </tr>
                                        </table>
                                    <?php }} ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                   
                </div>
                <div style="padding:18px;">
                    <p style="font-size:12px;">
                        <b style="font-size:12px;">Features:</b>
                        Fastrack your airport experience with CarterX on your Departure or Arrival across 5 airports: New
                        Delhi, Mumbai, Bangalore, Hyderabad and Chennai.
                        Subscription for No of usages that can be used across any or all 5 airports.
                        <ul style="font-size:12px;">
                            <li style="font-size:12px;">Baggage pick up and delivery from airport/doorstep</li>
                            <li style="font-size:12px;">Escort in and out of airports</li>
                            <li style="font-size:12px;">Assistance all the way to check in counters</li>
                            <li style="font-size:12px;">Assistance from baggage belts</li>
                        </ul>

                        <p style="font-size:12px;">Subscription Confirmation is delivered to your email inbox.
                        SMS and Email from booking to delivery. Subscription can be used for self or as e-gift.
                        Completely a digital experience of the subscription. Validity of 1 year from the date of purchase.</p>
                        <p style="font-size:12px;"><b style="font-size:12px;">Terms of the service:</b></p></br>
                        <p style="font-size:12px;">Service can be availed only on <a href="www.carterx.in">www.carterx.in</a>website or on corporate panel as directed for booking
                        Service usage cannot be combined with any more additional offers or services
                        Assistance does not include wheelchair or any other access assistance. Airport access is subject to
                        valid documentation of the passenger only. </p></br>
                        <p style="font-size:12px;"><b>Usages:</b>units of service. Domestic Departure is one unit of service and Domestic Arrival is one unit
                        of service. International departure is 2 units of service and International arrival is 2 units of service.
                        Any additional bags will be considered as 1 unit of service usage.</p></br>

                        <p style="font-size:12px;"><b style="font-size:12px;">Service available:</b>Departure and Arrival. Domestic Available. International Available.
                        Service Unit level is 1 bag for domestic service and 2 bags for International service.
                        Subscription can be booked for self or for travelling passenger for the usages/units available.
                        Booking validity for 1 year from the date of purchase and cannot be transferred or carried on.</p></br>

                        <p style="font-size:12px;"><b style="font-size:12px;">Current Stations include:</b> Airports listed for service.
                        Redemption Cost/Surcharge: may be paid additional if the subscription is from corporate houses or
                        card issuer
                        Cancellation and Refund Policy: as per Carterx individual service
                        Other Terms and Conditions: as per CarterX individual service</p>
                    </div>
                    <p><b>Orders redeemed on subscriptions are prepaid.</b></p>
                    <p><b>Conveyance charge on outstation redemption will be extra</b></p>
            </div>
           
            
        </div>

    </div>

</body>

</html>
