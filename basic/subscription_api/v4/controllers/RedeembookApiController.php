<?php

namespace app\subscription_api\v4\controllers;

use Yii;
use OAuth2;
/* For APi-start */
use linslin\yii2\curl;
use yii\web\Response;
use yii\helpers\Json;
use yii\rest\ActiveController;
/* For APi-end */

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\models\Airlines;
use app\models\SubscriptionTransactionDetails;

use app\models\airlinesSearch;
use app\components\SendOTP;
use app\models\ThirdpartyCorporate;
use app\api_v3\v3\models\ThirdpartyCorporateOrderMapping;
use app\models\Customer;
use app\models\Order;
use app\models\OrderPaymentDetails;
use app\models\OrderItems;
use app\models\OrderSpotDetails;
use app\api_v3\v3\models\OrderMetaDetails;
use app\models\User;
use app\models\OrderHistory;

class RedeembookApiController extends ActiveController
{
    public $modelClass = "app\models\Airlines";
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'corsFilter' => [
                  'class' => \yii\filters\Cors::className(),
                  'cors' => [
                      // restrict access to
                      'Origin' => ['*'],
                      'Access-Control-Request-Method' => ['POST', 'GET'],
                      // Allow only POST and PUT methods
                      'Access-Control-Request-Headers' => ['*'],
                      //'Access-Control-Allow-Origin'=>['*'],
                      // Allow only headers 'X-Wsse'
                      'Access-Control-Allow-Credentials' => true,
                      // Allow OPTIONS caching
                      'Access-Control-Max-Age' => 3600,
                      // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                      'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                  ],

              ],
        ];
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
    
    /**
    * @inheritdoc
    */
    /* api for fetching Airlines list */
    public static function actionGetAirlineDetails()
    {
        header('Access-Control-Allow-Origin: *');

        $data = Airlines::getairlines();
        return Json::encode(['status'=>true, 'lists' => $data['airlines']]);
    }
    /* api for for validating cinfirmation number */

    public static function actionValidateSubscriptionId(){
        header('Access-Control-Allow-Origin: *');
        $detail = array();
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $subscription_id =$data['subscription_id'];
       
        if(strlen($subscription_id) < 6){
            $msg ='Subscription Number can not be less than 6';
            return Json::encode(['status'=>false, 'msg' => $msg]);
        }else if(strlen($subscription_id) > 6){
            $msg ='Subscription Number can not be more than 6';
            return Json::encode(['status'=>false, 'msg' => $msg]);
        }

        $result = SubscriptionTransactionDetails::validatesubscriptionid($subscription_id);
        if(!empty($result['user_detail'])){
            $detail = $result['user_detail'];
           
        }

        
        return Json::encode(['status'=>true, 'msg' =>  $result['msg'], 'subscription_detail' => $detail]);
    }

     /* api for for validating subscription number */

    public static function actionValidateSubscriptionNumber(){

        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);

        $result = SubscriptionTransactionDetails::validatewithnumber($data);
        return Json::encode(['status'=>true,  'msg' => $result['msg']]);

    }

     /* api for for validating otp */ 
    public static function actionVerifySubscriptionOtp(){
        
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $result = SubscriptionTransactionDetails::verifyotp($data);
       $detail['data']=array();
        if($result['status']=='true'){

            $detail = SubscriptionTransactionDetails::fetchsubscriberdetail($data);
        }
        return Json::encode(['status'=>true , 'msg'=>$result['msg'], 'subscriber_detail'=>$detail['data']]);
        
       
    } 
     /* api for for getting pick and drop location*/ 

    public static function actionGetPickDropAddres(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);

        $address = SubscriptionTransactionDetails::getpickupaddress($data);   
        return Json::encode(['status'=>true , 'msg'=>$address['msg'], 'subscriber_detail'=>$address['address']]);
        
    }
    /* api for for placing order through subscription */ 
    public static function actionPlaceOrderWithSubscription(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $order = SubscriptionTransactionDetails::placesubsorder($data);   
        return Json::encode(['status'=>true , 'msg'=>$address['msg'], 'subscriber_detail'=>$order['order_id']]);
        
    }

    public static function actionGetSupersubscription(){
        header('Access-Control-Allow-Origin: *');
        
        $data = SubscriptionTransactionDetails::getsupersubscription();

        return Json::encode(['status'=>true , 'subscription_detail'=>$data]);

    }

    public static function actionBuySubcriptionByUser(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        
        $result = SubscriptionTransactionDetails::buysubscriptionbyuser($data); 
        return Json::encode(['status'=>true , 'result'=>$result]);

    }

    public static function actionBuySubscriptionWithPurchase(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $subs = SubscriptionTransactionDetails::buysubscriptionwithpurchase($data); 
        return Json::encode(['status'=>true , 'result'=>$subs]);


    }

       /* api for for validating otp */ 
    public static function actionVerifyusernumber(){
        
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $result = SubscriptionTransactionDetails::verifyusernumber($data);
       
        if($result['status']=='true'){

            $detail = SubscriptionTransactionDetails::CreateCustomerEmployee($result['user_detail']);
           
       
        }
        return Json::encode(['result'=>$result]);
        
       
    }

    /**
     * Function for Checking the access token 
     */
    public function CheckAccesstoken(){
        $headers = Yii::$app->request->getHeaders();
        $access_token = $headers['token'];
        
        $corporate = ThirdpartyCorporate::find()
                       // ->select(['thirdparty_corporate_id','corporate_name','is_active'])
                        ->where(['access_token'=>$access_token])
                        ->asArray()->one();
        if(!$corporate){
            echo Json::encode(['status'=>false,'message'=>"Invalid Corporate Credentials"]);exit;

        }else{
            return $corporate;
        }
    }
    
    public function actionBooking(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $corporateData = $this->CheckAccesstoken();
        $order = $this->actionSubscriptionOrderBooking($data,$corporateData);
        return Json::encode($order);
    }
    
    private function actionSubscriptionOrderBooking($data,$corporateData){
        if(!empty($data)){
            $this->Getreamainingprice($data['subscription_transaction_id'],$corporateData['thirdparty_corporate_id'],$data['no_of_units'],$data['exhaust_usages'],$data['airport_id'],$data['luggage_price'],$corporateData['gst'],$data['remaining_usages']);
            $customer = Customer::find()->where(['mobile'=>$data['travell_passenger_contact']])->asArray()->one();
            $cargo_status = Yii::$app->Common->checkCargoStatus($corporateData['fk_corporate_id']);
            $model = new Order();
            $model->service_type = $data['service_type']; //departure or arrival
            $model->order_transfer = $data['order_type']; // airport or city
            $model->delivery_type = $data['transfer_type']; // local or outstation
            $model->dservice_type = isset($data['dservice_type']) ? $data['dservice_type'] : '7';//normal luggage
            $model->order_type_str = isset($data['order_type_str']) ? $data['order_type_str'] : ""; //Airport Transfer
            $model->terminal_type = isset($data['terminal_type']) ? $data['terminal_type'] : ""; //domestic or interterminal
            $model->airport_service = isset($data['pick_drop_point']) ? $data['pick_drop_point'] : ""; //airport:pick drop point or doorstep drop pick
            $model->no_of_units = $data['no_of_units']; // no of bags

            $model->travell_passenger_contact = $data['travell_passenger_contact']; //traveller mobile no
            $model->travell_passenger_name = $data['travell_passenger_name']; // traveller name
            $model->travell_passenger_email = $data['travell_passenger_email']; // traveller email
            $model->travel_person = '1';
            $model->fk_tbl_order_id_country_code = $data['country_code']; // country code
            $model->confirmation_number = $data['subscription_transaction_id']; //
            $model->fk_tbl_order_id_customer = isset($customer['id_customer']) ? $customer['id_customer'] : '';
            $model->fk_tbl_airport_of_operation_airport_name_id = isset($data['airport_id']) ? $data['airport_id'] : 0;
            $model->city_id = isset($data['city_id']) ? $data['city_id'] : 0;
            $model->corporate_id = $corporateData['fk_corporate_id'];
            $model->created_by_name = $data['travell_passenger_name'];
            $model->created_by = $customer['id_customer'];

            // $model->location = isset($data['location']) ? $data['location'] : "";
            // $model->sector = isset($data['sector']) ? $data['sector'] : "";
            // $model->weight = isset($data['weight']) ? $data['weight'] : "";

            $model->order_date = date('Y-m-d',strtotime($data['order_date'])); 
            $model->extra_weight_purched = isset($data['extra_weight_purched']) ? $data['extra_weight_purched'] : "no";
            $model->date_created = date('Y-m-d H:i:s'); // order creation date

            $model->usages_used = $data['exhaust_usages']; // exhaust usages
            $model->payment_mode_excess = ucwords($data['payment_type']); // payment type
            $model->payment_method = ucwords($data['payment_type']); // payment type
            $model->corporate_type = $data['corporate_type'];

            $model->service_tax_amount = $data['service_tax_amount'];
            $model->luggage_price = !empty($data['luggage_price']) ? $data['luggage_price'] : 0;
            $model->order_status =  'Confirmed';
            $model->fk_tbl_order_status_id_order_status = 2;
            $model->amount_paid = !empty($data['total_luggage_price']) ? $data['total_luggage_price'] : 0;
            $model->delivery_datetime = isset($data['delivery_datetime']) ? date('Y-m-d H:i:s',strtotime($data['delivery_datetime'])) : "";

            $model->insurance_price = 0;
            $model->remaining_usages = $data['remaining_usages'];
            $model->extra_usages = $data['total_usages'];
            
            if($data['pick_drop_point'] == 1){//Airport:pick/drop point
                $model->pickup_dropoff_point = isset($data['pick_drop_address']) ? $data['pick_drop_address'] : 0;
                $model->airport_slot_time = isset($data['airport_slot_time']) ? date("H:i:s", strtotime($data['airport_slot_time'])) : "00:00:00";
                if($data['service_type'] == 1){
                    // $model->pickup_pincode = $data['pincode_second'];
                    $model->drop_pincode = $data['pincode_first'];
                } else if($data['service_type'] == 2){
                    $model->pickup_pincode = $data['pincode_first'];
                    // $model->drop_pincode = $data['pincode_second'];
                }
            } else if(($data['pick_drop_point'] == 2) && ($data['order_type'] == 2)){// Airport:Doorstep point
                $model->fk_tbl_order_id_slot = isset($data['fk_tbl_order_id_slot']) ? $data['fk_tbl_order_id_slot'] : 1;
                if($data['service_type'] == 1){
                    $model->pickup_pincode = $data['pincode_first'];
                    $model->drop_pincode = $data['pincode_second'];
                } else if($data['service_type'] == 2){
                    $model->pickup_pincode = $data['pincode_second'];
                    $model->drop_pincode = $data['pincode_first'];
                }
            } else if(($data['pick_drop_point'] == 2)){// Doorstep point
                $model->fk_tbl_order_id_slot = isset($data['fk_tbl_order_id_slot']) ? $data['fk_tbl_order_id_slot'] : 1;
                if($data['service_type'] == 1){
                    $model->pickup_pincode = $data['pincode_second'];
                    $model->drop_pincode = $data['pincode_first'];
                } else if($data['service_type'] == 2){
                    $model->pickup_pincode = $data['pincode_first'];
                    $model->drop_pincode = $data['pincode_second'];
                }
            }
    
            if($data['order_type'] == 2){
                $model->flight_number = $data['flight_number'];
                $model->pnr_number = $data['pnr_number'];
            }    
            
            if($model->save(false)){
                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                $model->save(false);

                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                
                if($data['pick_drop_point'] == 2){
                    if($data['order_type'] == 1){ // insert cargo or city order address in 'tbl_order_meta_details'
                        $orderMetaDetails = new OrderMetaDetails();
                        $orderMetaDetails->stateId = 0;
                        $orderMetaDetails->orderId = $model->id_order;
                        $orderMetaDetails->pickupPersonName = "";
                        $orderMetaDetails->pickupPersonNumber = "";
                        $orderMetaDetails->dropPersonName = "";
                        $orderMetaDetails->dropPersonNumber = "";

                        $orderMetaDetails->pickupPersonAddressLine1 = isset($data['pickup_address_line1']) ? $data['pickup_address_line1'] : "";
                        $orderMetaDetails->pickupPersonAddressLine2 = isset($data['pickup_address_line2']) ? $data['pickup_address_line2'] : "";
                        $orderMetaDetails->pickupArea = isset($data['pickup_area']) ? $data['pickup_area'] : "";
                        $orderMetaDetails->pickupPincode = isset($data['pickup_pincode']) ? $data['pickup_pincode'] : "";
                        $orderMetaDetails->pickupLocationType = isset($data['pickup_location_type']) ? $data['pickup_location_type'] : "";
                        $orderMetaDetails->pickupBuildingNumber = isset($data['pickup_building_number']) ? $data['pickup_building_number'] : null;
                        $orderMetaDetails->dropPersonAddressLine1 = isset($data['drop_address_line1']) ? $data['drop_address_line1'] : "";
                        $orderMetaDetails->dropPersonAddressLine2 = isset($data['drop_address_line2']) ? $data['drop_address_line2'] : "";
                        $orderMetaDetails->droparea = isset($data['drop_area']) ? $data['drop_area'] : "";
                        $orderMetaDetails->dropPincode = isset($data['drop_pincode']) ? $data['drop_pincode'] : "";
                        $orderMetaDetails->pickupLocationType = isset($data['drop_location_type']) ? $data['drop_location_type'] : "";
                        $orderMetaDetails->dropBuildingNumber = isset($data['drop_building_number']) ? $data['drop_building_number'] : null;

                        $orderMetaDetails->pickupBusinessName = "";
                        $orderMetaDetails->pickupMallName = "";
                        $orderMetaDetails->pickupStoreName = "";
                        $orderMetaDetails->save(false);
                        // $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'RazorPay', '');
                    } else if($data['order_type'] == 2){ // insert airport order address in 'tbl_order-spot_details'
                        $order_spot_details = new OrderSpotDetails();
                        $order_spot_details->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $order_spot_details->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($data['pick_drop_spots_type']) ? $data['pick_drop_spots_type'] : 1;
                        $order_spot_details->person_name = "";
                        $order_spot_details->person_mobile_number = "";
                        $order_spot_details->mall_name = "";
                        $order_spot_details->store_name = "";
                        $order_spot_details->business_name = "";

                        if($model->travell_passenger_contact != $data['person_mobile_number']){
                            $order_spot_details->assigned_person = 1;
                        }

                        $order_spot_details->address_line_1 = isset($data['address_line_1']) ? $data['address_line_1'] : null;
                        $order_spot_details->address_line_2 = isset($data['address_line_2']) ? $data['address_line_2'] : null;
                        $order_spot_details->area = isset($data['area']) ? $data['area'] : null;
                        $order_spot_details->pincode = isset($data['pincode'])? $data['pincode']:null;
                        $order_spot_details->landmark = isset($data['landmark'])?$data['landmark']:null;
                        $order_spot_details->building_number = isset($data['building_number']) ? $data['building_number']:null;
                        $order_spot_details->other_comments = null;
                        $order_spot_details->building_restriction = isset($data['building_restriction']) ? serialize($data['building_restriction']) : null;
                        $order_spot_details->save(false);
                        // if($order_spot_details->save(false)){
                        //     if(!empty($_FILES['name']['booking_confirmation_file'])){
                        //         $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                        //     }
                        // }
                    }
                }

                // insert data in tbl_thirdparty_corporate_order_mapping
                $thirdpartymapping = new ThirdpartyCorporateOrderMapping();
                $thirdpartymapping->thirdparty_corporate_id = $corporateData['thirdparty_corporate_id'];
                $thirdpartymapping->order_id = $model->id_order;
                $thirdpartymapping->stateId = '';
                $thirdpartymapping->cityId = $data['city_id'];
                $thirdpartymapping->created_on = date('Y-m-d H:i:s');
                $thirdpartymapping->save(false);

                // insert data in tbl_order_payment_details
                $order_payment_details = new OrderPaymentDetails();
                $order_payment_details->id_order = $model->id_order;
                $order_payment_details->payment_type = ucwords($data['payment_type']);
                $order_payment_details->id_employee = 0;
                if($payment_type == 'razorpay'){
                    $order_payment_details->payment_status = 'Not paid';
                }else{
                    $order_payment_details->payment_status = 'Success';
                }
                $order_payment_details->amount_paid = $data['total_luggage_price'];
                $order_payment_details->value_payment_mode = 'Order Amount';
                $order_payment_details->date_created= date('Y-m-d H:i:s');
                $order_payment_details->date_modified= date('Y-m-d H:i:s');
                $order_payment_details->save(false);

                if(!empty($data['no_of_units'])){
                    for($i=0; $i < $data['no_of_units']; $i++){
                        $model_item = new OrderItems();
                        $model_item->fk_tbl_order_items_id_order = $model->id_order;
                        $model_item->fk_tbl_order_items_id_luggage_type = 2;
                        $model_item->fk_tbl_order_items_id_luggage_type_old = 2;
                        $model_item->item_price = ($data['total_luggage_price'] / $data['exhaust_usages']);
                        $model_item->bag_type = "bag".$i;
                        $model_item->new_luggage = 0;
                        $model_item->save(false);
                    }
                }

                // decrease usages from remaining usages
                    $removing_usages = isset($data['exhaust_usages']) ? $data['exhaust_usages'] : $data['no_of_units'];
                    $remaining_usages = Yii::$app->Common->updateConfirmationNumber($data['subscription_transaction_id'],$data['terminal_type'],$removing_usages,$data['transfer_type']);
                
                /* update remaing and balance value */
                    
                /* End of update remaing and balance value */
                // subscription email send here
                    $new_order_details = Order::getorderdetails($model->id_order);
                    $model1['order_details']=$new_order_details;
                    $model1['order_details']['subscription_details'] = Yii::$app->Common->getSubscriptionDetails($model1['order_details']['order']['confirmation_number']);

                // SMS send from here
                    $sms_data = array("confirmation_number" => !empty($model1['order_details']['subscription_details']['confirmation_number']) ? strtoupper($model1['order_details']['subscription_details']['confirmation_number']) : "",
                        "subscription_name" => !empty($model1['order_details']['subscription_details']['subscriber_name']) ? strtoupper($model1['order_details']['subscription_details']['subscriber_name']) : "",
                        "paid_amount" => !empty($model1['order_details']['order']['amount_paid']) ? $model1['order_details']['order']['amount_paid'] : "",
                        "pay_amount" => !empty($model1['order_details']['subscription_details']['s_subscription_cost']) ? $model1['order_details']['subscription_details']['s_subscription_cost'] : "",
                        "refund_amount"=> !empty($model1['order_details']['subscription_details']['']) ? $model1['order_details']['subscription_details'][''] : "");

                    $mobile_arr = array_unique(array($new_order_details['order']['customer_mobile'],$new_order_details['order']['travell_passenger_contact'],$model1['order_details']['subscription_details']['primary_contact'],$model1['order_details']['subscription_details']['secondary_contact']));

                    // customer and super subscriber
                    $customer_email = !empty($model1['order_details']['order']['travell_passenger_email']) ? $model1['order_details']['order']['travell_passenger_email'] : (!empty($model1['order_details']['order']['customer_email']) ? $model1['order_details']['order']['customer_email'] : "");

                    $emailSubscriberTo = array($model1['order_details']['subscription_details']['primary_email'],
                        $model1['order_details']['subscription_details']['secondary_email']
                    );

                    $emailTokenTo = array(!empty($model1['order_details']['corporate_details']['default_email']) ? array($model1['order_details']['corporate_details']['default_email']) : "");

                    $emailCustomerCareTo = array(Yii::$app->params['customer_email']);
                    
                    Yii::$app->Common->subscriptionSmsSent('validate_activate_subscription_confirmation',$sms_data,array_filter($mobile_arr));//confirmation sms
                    if(($model1['order_details']['order']['delivery_type'] == 1) && !empty($data['order_details']['order']['amount_paid'])){
                        Yii::$app->Common->subscriptionSmsSent('validate_activate_subscription_redemption',$sms_data,array_filter($mobile_arr)); //redemption cost sms
                    } else if(($model1['order_details']['order']['delivery_type'] == 2) && !empty($data['order_details']['order']['amount_paid'])){
                        Yii::$app->Common->subscriptionSmsSent('validate_activate_subscription_additional',$sms_data,array_filter($mobile_arr)); //redemption cost sms
                    }
                    
                    $cargo_status = Yii::$app->Common->checkCargoStatus($new_order_details['corporate_details']['corporate_detail_id']);

                // confirmation update email
                    $model1['order_details']['order']['confirm'] = 1;
                    $file_name = "subscription_confirmation_".time().'_'.$model1['order_details']['order']['order_number'].".pdf";
                    $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($model1,'subscription_cnf_template',$file_name);
                    User::sendcnfemail($customer_email,"CarterX Confirmed Subscription #".strtoupper($model1['order_details']['subscription_details']['confirmation_number']),'sub_cnf_email',$model1,$attachment_cnf,array_filter(array_unique($emailSubscriberTo)));

                // Token confirmation email
                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'token_confirmation_pdf_template');
                    if($cargo_status){
                        $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'cargo_security.pdf';    
                    } else {
                        $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'passenger_security.pdf';
                    } 
                    User::sendSubscriberEmailWithMultipleAttachment($customer_email,"CarterX Confirmed Subscription Order #".$model1['order_details']['order']['order_number']."",'order_confirmation',$model1,$attachment_det,array_filter(array_unique(array_merge($emailTokenTo,$emailCustomerCareTo))),false);
                
                // invoice email order_payments_pdf_template
                    $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'subscription_order_invoice_pdf');
                    User::sendemailexpressattachment($customer_email,"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det,array_filter(array_unique($emailSubscriberTo)),true);

                if($model1['order_details']['order']['amount_paid'] != 0){
                    // invoice email reedem_order_payments_pdf_template
                    $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'subscription_redemption_invoice_pdf');
                    User::sendemailexpressattachment($customer_email,"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det,array_filter(array_unique($emailSubscriberTo)),true);
                }

                if($remaining_usages == 0){
                    $model1['order_details']['subscription_details'] = Yii::$app->Common->getSubscriptionDetails($model1['order_details']['order']['confirmation_number']);
                    Yii::$app->Common->subscriptionSmsSent('exhaustion_of_subscription',$sms_data,array_filter($mobile_arr)); //Exhaustion sms
                    // Exhaustion of Subscription: confirmation update email
                    $model1['order_details']['order']['exhaust'] = 1;

                    $file_name = "subscription_confirmation_".time().'_'.$model1['order_details']['order']['order_number'].".pdf";
                    $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($model1,'subscription_cnf_template',$file_name);
                    User::sendcnfemail($customer_email,"CarterX Exhaustion of Subscription: Confirmation update for Subscription #".strtoupper($model1['order_details']['subscription_details']['confirmation_number']),'sub_cnf_email',$model1,$attachment_cnf,array_filter(array_unique(array_merge($emailSubscriberTo,$emailCustomerCareTo))));
                } 
                // sms order creation
                $msg_to_airport = "Dear Customer, Welcome to CarterX! Login to www.carterx.in with your registered mobile number to track your order placed by " . $customer_name . " under 'Manage Orders'. For all service related queries contact our customer support on +91-6366835588  -CarterX";
                $msg_to_airport = urlencode($msg_to_airport);
                $order_date = ($model1['order_details']['order']['order_date']) ? date("Y-m-d", strtotime($model1['order_details']['order']['order_date'])) : '';
                $date_created = ($model1['order_details']['order']['date_created']) ? date("Y-m-d", strtotime($model1['order_details']['order']['date_created'])) : '';
                $customer_name = $model1['order_details']['order']['travell_passenger_name'];

                $slot_start_time = ($model1['order_details']['order']['order']['slot_start_time']) ? date('h:i a', strtotime($model1['order_details']['order']['order']['slot_start_time'])) : '';
                $slot_end_time = ($model1['order_details']['order']['order']['slot_end_time']) ? date('h:i a', strtotime($model1['order_details']['order']['order']['slot_end_time'])) : '';
                if(empty($model1['order_details']['order']['order']['slot_start_time']) && empty($model1['order_details']['order']['order']['slot_end_time'])){
                    $slot_scehdule = date("h:i a",strtotime($model1['order_details']['order']['airport_slot_time'])) .' To '. date("h:i a",strtotime($model1['order_details']['order']['airport_slot_time']+60*60*4));
                } else {
                    $slot_scehdule = $slot_start_time . ' To ' . $slot_end_time;
                }

                if ($model1['order_details']['order']['order_transfer'] == 1) {
                    $sms_content = Yii::$app->Common->generateCityTransferSms($model1['order_details']['order']['id_order'], 'OrderConfirmation', '');
                } else {
                    if ($model1['order_details']['order']['service_type'] == 1) {
                        $service = ($model1['order_details']['order']['order_transfer'] == 1) ? 'To City' : 'To Airport';
                        $bookingCustomer = 'Dear Customer, your Order #' . $model1['order_details']['order']['order_number'] . ' ' . $service . '  placed on ' . $date_created . ' by ' . $customer_name . ' is confirmed for service on ' . $order_date . ' between ' . $slot_scehdule . '. Thanks carterx.in';
                        User::sendsms('91'.$model1['order_details']['order']['travell_passenger_email'], $msg_to_airport);
                        User::sendsms('91'.$new_order_details['order']['customer_mobile'], $bookingCustomer);
                    } elseif ($model1['order_details']['order']['service_type'] == 2) {
                        $service = ($model1['order_details']['order']['order_transfer'] == 1) ? 'From City' : 'From Airport';
                        $bookingCustomer = 'Dear Customer, your Order #' . $model1['order_details']['order']['order_number'] . ' ' . $service . '  placed on ' . $date_created . ' by ' . $customer_name . ' is confirmed for service on ' . $order_date . ' between ' . $slot_scehdule . '. Thanks carterx.in';
                        User::sendsms('91'.$model1['order_details']['order']['travell_passenger_email'], $msg_to_airport);
                        User::sendsms('91'.$new_order_details['order']['customer_mobile'], $bookingCustomer);
                    }
                }
                // sms order creation
                
                return ['status'=>true,'message'=>'Booking is done', 'order_number'=>$new_order_details['order']['order_number'],'order_id'=>$new_order_details['order']['id_order']];
            }
        } else {
            return ['status'=>false,'message'=>'Some Paramters are missing'];
        }
    }

    // function call for fetching data after session
    public function actionFetchSubscriberDetails(){
        $return =array();
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $detail = SubscriptionTransactionDetails::fetchsubscriberdetail($data);
        if(isset($detail['data'])){
            $return = $detail['data'];
        }
        return Json::encode(['status'=>true , 'subscriber_detail'=>$return]);

    }
    public function actionSendemail(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $detail = SubscriptionTransactionDetails::sendmail($data);
        return Json::encode(['status'=>true , 'msg'=>$detail['msg']]); 
    }

    public function actionCorporateRegistration(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $result = Customer::verifyusernumber($data); // check validation
        if (empty($result)) {
            $checkBoth = Customer::find()->where(['mobile' => $data['mobile'],'email'=>$data['email']])->One(); // get customer details from email & mobile 
            if(!empty($checkBoth)){ // condition for Old customer
                $checkAirlinesMap = Yii::$app->db->createCommand("SELECT * FROM tbl_corporate_employee_airline_mapping WHERE fk_corporate_employee_id ='".$checkBoth->id_customer."' and fk_airline_id = '".$data['airline_id']."'")->queryOne(); // check customer mapped with same airline or not
                if(!empty($checkAirlinesMap)){ // mapped with
                    echo Json_encode(['status' => false,'message' => "User already exist with this airline. Please login with number."]);
                } else { // not mapped
                    $checkOth = Yii::$app->db->createCommand("SELECT c.id_customer,c.fk_role_id,c.name,c.document,c.customer_profile_picture,c.email,c.mobile,c.address_line_1,c.address_line_2,c.area,c.pincode,c.id_proof_verification,c.email_verification,c.mobile_number_verification,oc.client_id,oc.client_secret,c.fk_tbl_customer_id_country_code FROM tbl_customer c INNER JOIN oauth_clients oc ON oc.user_id = c.id_customer  WHERE c.mobile='".$data['mobile']."' AND c.fk_tbl_customer_id_country_code='".$data['country_code']."'")->queryOne(); // check client id or secret

                    $checkBoth->fk_role_id = 19;
                    $checkBoth->name = $data['name'];
                    $checkBoth->acc_verification = 1;
                    $checkBoth->email_verification = 1;
                    $checkBoth->id_proof_verification = 1;
                    $checkBoth->update_status = 1;
                    $checkBoth->update_date = date("Y-m-d");
                    $checkBoth->gst_number = $data['gst_number'];
                    $checkBoth->tour_id = ($data['airline_id'] == 5) ? strtoupper($data['tour_id']) : 0;

                    $checkBoth->save(false);
                    $airlineName = Yii::$app->db->CreateCommand("SELECT UPPER(airline_name) as airline_name FROM tbl_airlines WHERE airline_id = '".$data['airline_id']."'")->queryOne()['airline_name']; // get airline name
                    $airlineName = str_replace(' ','',$airlineName).$checkBoth->id_customer; // create customer unique id

                    if(empty($checkOth)){ // if doesn't exist
                        $client['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
                        $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999)); 
                        $client['user_id']=$checkBoth->id_customer;
                        User::addClient($client);
                    }

                    $ResData = array("id_customer" => $checkBoth->id_customer,"name" => $data['name'],
                        "email" => $data['email'],
                        "mobile" => $data['mobile'],
                        "gst_number" => $data['gst_number'],
                        "fk_tbl_customer_id_country_code" => 95,
                        "fk_airline_id" => $data['airline_id'],
                        "customerId" => $airlineName,
                        "tour_id" => isset($data['tour_id']) ? strtoupper($data['tour_id']) : "-",
                    );
                    User::sendemail($data['email'],"New Registration Confirmation",'corporate_id_information_email',$ResData);

                    Yii::$app->db->CreateCommand("INSERT INTO tbl_corporate_employee_airline_mapping (fk_corporate_employee_id,fk_airline_id,customerId) values('".$checkBoth->id_customer."','".$data['airline_id']."','".$airlineName."')")->execute(); // mapping airline with customer
                    Yii::$app->Common->updatecustomercorporate($checkBoth->id_customer,$airlineName);
                    echo Json_encode(['status' => true, 'message' => "Registration successful. Your Corporate ID : ".$airlineName]);
                }
            } else { // Condition for New customer
                $model = new Customer;
                $model->fk_role_id = '19';
                $model->gender = 0;
                $model->building_restriction = 'a:1:{i:0;s:1:"5";}';
                $model->mobile_number_verification = 0;
                $model->email_verification = 0;
                $model->id_proof_verification = 0;
                $model->fk_tbl_customer_id_country_code = $data['country_code'];
                $model->email = $data['email'];
                $model->name = $data['name'];
                $model->mobile = $data['mobile'];
                $model->gst_number = $data['gst_number'];
                $model->tour_id = ($data['airline_id'] == 5) ? strtoupper($data['tour_id']) : 0;
                $model->date_created = date('Y-m-d');
                $model->status = 1;
                if($model->save(false)){
                    $airlineName = Yii::$app->db->CreateCommand("SELECT UPPER(airline_name) as airline_name FROM tbl_airlines WHERE airline_id = '".$data['airline_id']."'")->queryOne()['airline_name']; // get airline name
                    $airlineName = str_replace(' ','',$airlineName).$model->id_customer; // create customer unique id

                    Yii::$app->db->CreateCommand("INSERT INTO tbl_corporate_employee_airline_mapping (fk_corporate_employee_id,fk_airline_id,customerId) values('".$model->id_customer."','".$data['airline_id']."','".$airlineName."')")->execute(); // mapping airline with customer
                   
                    Yii::$app->Common->updatecustomercorporate($model->id_customer,$airlineName); //udate customer table

                    // create client id and scret id
                    $client['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999)); 
                    $client['user_id']=$model->id_customer;
                    User::addClient($client);

                    $ResData = array("id_customer" => $model->id_customer,"name" => $data['name'],
                        "email" => $data['email'],
                        "mobile" => $data['mobile'],
                        "gst_number" => $data['gst_number'],
                        "fk_tbl_customer_id_country_code" => $data['country_code'],
                        "fk_airline_id" => $data['airline_id'],
                        "customerId" => $airlineName,
                        "tour_id" => !empty($data['tour_id']) ? strtoupper($data['tour_id']) : "-",
                    );
                    
                    /* Email and SMS on Registration of customer - Start*/
                        User::sendemail($model->email,"Verify Email Address",'verify_email_link',$model);

                        User::sendemail($model->email,"Welcome to Carter X",'welcome_customer',$model);

                        User::sendemail($data['email'],"New Registration Confirmation",'corporate_id_information_email',$ResData);

                        $country_code = Customer::getcontrycode($data['country_code']);

                        User::sendsms($country_code.$model->mobile,"Dear Customer, Thank you for registering with us and welcome to Carter X where luggage transfer is simplified. Get Carter & Travel Smart! .".PHP_EOL."Thanks carterx.in");
                    /* End*/
                }
                echo Json_encode(['status' => true, 'message' => "Registration successful, confirmation email has been sent. Your Corporate ID : ".$airlineName]);
            }
        } else {
            echo json_encode(['status' => false,'message'=>$result['message']]);
        }
    }

    /**
     * Function for Forgot Corporate Id
     * 
    */
    public function actionForgotCorporateId(){
        header('Access-Control-Allow-Origin: *');
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        if(!empty($data)){
            $customerResult = Yii::$app->db->createCommand("SELECT C.id_customer,C.name,C.email,C.mobile,C.gst_number,C.fk_tbl_customer_id_country_code,C.tour_id FROM tbl_customer C WHERE C.email = '".$data['email_id']."' and C.mobile = '".$data['mobile_number']."' and C.fk_role_id = '".$data['user_role_id']."'")->queryOne();
            if(!empty($customerResult)){
                $corporateIdInfo = Yii::$app->db->CreateCommand("SELECT * FROM tbl_corporate_employee_airline_mapping CEAM WHERE fk_airline_id = '".$data['airline_id']."' and CEAM.fk_corporate_employee_id = '".$customerResult['id_customer']."'")->queryOne();
                if(!empty($corporateIdInfo)){
                    // $ResData = array_merge($customerResult,$corporateIdInfo);
                    $ResData = array_merge($corporateIdInfo,$customerResult);
                    User::sendemail($ResData['email'],"Forgot Corporate ID",'corporate_id_information_email',$ResData);
                    echo json_encode(['status' => true, "message" => "Successfully sent mail on your email id. Please check and get your Corporate ID."]);
                } else {
                    echo json_encode(['status' => false, "message" => "Corporate ID Not Found. Please register with this Corporate."]);
                }
            } else {
                echo json_encode(['status' => false, "message" => "Customer not Found!"]);
            }
        } else {
            echo json_encode(['status' => false, 'message' => "Please send required parameters!"]);
        }
    }

    public function Getreamainingprice($subscription_transaction_id, $thirdparty_corporate_id,$no_of_units,$exhaust_usages,$airport_id,$luggage_price,$gst,$remaining_usages){
       /*  $subscription_transaction_id = "2417";
        $thirdparty_corporate_id="143";
        $no_of_units="3";
        $exhaust_usages="3";
        $airport_id="3";
        $luggage_price="0";
        $gst="12";$remaining_usages="4"; */
        $subs_detail = Yii::$app->Common->getSubscriptionDetails($subscription_transaction_id);
        $total_loss  = 0;

        if(isset($subs_detail)){
            $peruseage_charge = $subs_detail['paid_amount'] / $subs_detail['no_of_usages'];
            $remaining_amt =$subs_detail['remaining_balance'];
            $balence_value =$subs_detail['balence_value'];
           
           
            
           /*  echo "1))---->".$airport_id."---thirdparty_corporate_id : ".$thirdparty_corporate_id."--- balence_value :".$balence_value."---remaining_amt :".$remaining_amt."---peruseage_charge :".$peruseage_charge; 
             */$bag_price =Yii::$app->db->createCommand("select la.bag_price
            from tbl_thirdparty_corporate_luggage_price_airport la
            left join tbl_thirdparty_corporate_airports ca on ca.thirdparty_corporate_airport_id = la.thirdparty_corporate_airport_id
            where thirdparty_corporate_id='". $thirdparty_corporate_id."' and airport_id='".$airport_id."'")->queryOne();
           
            $gst_price = ($bag_price['bag_price'] * $gst) / 100;
            $total_gst = $no_of_units*$gst_price;
            $bag_prices = round($bag_price['bag_price'] ,0) + $gst_price;
            $per_useage_price = round($peruseage_charge,0);
          
            if(isset($bag_price)){
                if($per_useage_price > $bag_prices){
                    $loss_value = $per_useage_price - $bag_prices;
                    $total_loss = ($exhaust_usages * $loss_value ) ;
                    //$total_loss_with_gst =  ($total_loss * $gst) / 100;
                } 
            }
            $deducted_amt =$exhaust_usages * $per_useage_price;
            $remaining_balence =round($remaining_amt,0) - round($deducted_amt,0); 
         
           

            $balence_value = $balence_value + $total_loss; 
            //$remaining_balence =$remaining_balence + $balence_value;
            if($no_of_units > $remaining_usages){
                $remaining_balence =  - $luggage_price;
            } 
           
            /* echo "<br>2))---->".$airport_id."---thirdparty_corporate_id : ".$thirdparty_corporate_id."--- balence_value :".$balence_value."---remaining_balence :".$remaining_balence."---peruseage_charge :".$per_useage_price ."<br>".$total_loss; die;
            
            die; */
            Yii::$app->Common->updatebalencevalue($remaining_balence,$balence_value,$subscription_transaction_id);
        }
    }
}
?> 
