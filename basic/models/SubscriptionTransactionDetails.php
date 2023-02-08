<?php

namespace app\models;
use app\components\SendOTP;
use Razorpay\Api\Api;
use app\models\Order;
use app\models\Employee;
use app\models\Customer;
use app\models\User;
use app\models\OrderPaymentDetails;


use Yii;

/**
 * This is the model class for table "tbl_subscription_transaction_details".
 *
 * @property int $subscription_transaction_id
 * @property int $subscription_id
 * @property string $confirmation_number
 * @property string $payment_transaction_id
 * @property float $paid_amount
 * @property float $redemption_cost
 * @property float $subscription_cost
 * @property int $no_of_usages
 * @property string $payment_status
 * @property string $payment_date
 * @property string|null $expire_date
 * @property string $create_date
 */
class SubscriptionTransactionDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_subscription_transaction_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subscription_id', 'confirmation_number', 'payment_transaction_id', 'paid_amount', 'redemption_cost', 'subscription_cost', 'no_of_usages', 'payment_status','payment_date'], 'required'],
            [['subscription_id', 'no_of_usages'], 'integer'],
            [['paid_amount', 'redemption_cost', 'subscription_cost'], 'number'],
            [['payment_date', 'expire_date', 'create_date'], 'safe'],
            [['confirmation_number', 'payment_transaction_id'], 'string', 'max' => 50],
            [['payment_status'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'subscription_transaction_id' => 'Subscription Transaction ID',
            'subscription_id' => 'Subscription ID',
            'confirmation_number' => 'Confirmation Number',
            'payment_transaction_id' => 'Payment Transaction ID',
            'paid_amount' => 'Paid Amount',
            'redemption_cost' => 'Redemption Cost',
            'subscription_cost' => 'Subscription Cost',
            'no_of_usages' => 'No Of Usages',
            'payment_status' => 'Payment Status',
            'payment_date' => 'Payment Date',
            'expire_date' => 'Expire Date',
            'create_date' => 'Create Date',
        ];
    }

    public static function validatesubscriptionid($subscription_id){
        header('Access-Control-Allow-Origin: *');
        $user_detail = array();
        $check = SubscriptionTransactionDetails::find()
        ->where( [ 'confirmation_number' => $subscription_id ] )
        ->exists(); 
        if($check === true){
            $emp_details = Yii::$app->db->createCommand("select ea.employee_id ,e.mobile , e.name , e.email,c.fk_tbl_customer_id_country_code,c.id_customer
            from tbl_employee_allocation ea
            left join tbl_subscription_transaction_details ts on ts.subscription_transaction_id = ea.subscription_transaction_id
            left join tbl_employee e on e.id_employee = ea.employee_id
            left join tbl_customer c on c.fk_id_employee = e.id_employee
            where  ts.confirmation_number='".$subscription_id."'")->queryAll();

            if(!empty($emp_details)){
                $mobile = $emp_details[0]['mobile'];
                $email = $emp_details[0]['email'];
                $id = $emp_details[0]['employee_id'];
                $custBoth = Customer::find()->where( ['mobile'=> $mobile,'email' =>$email] )->one();
                if(!empty($custBoth)){
                    if(empty($custBoth['fk_id_employee'])){
                        $custBoth['fk_id_employee'] =  $id;
                        $custBoth->save(false);
                    }
                }
            }
            $emp_detail = Yii::$app->db->createCommand("select ea.employee_id ,e.mobile , e.name , e.email,c.fk_tbl_customer_id_country_code,c.id_customer
            from tbl_employee_allocation ea
            left join tbl_subscription_transaction_details ts on ts.subscription_transaction_id = ea.subscription_transaction_id
            left join tbl_employee e on e.id_employee = ea.employee_id
            left join tbl_customer c on c.fk_id_employee = e.id_employee
            where  ts.confirmation_number='".$subscription_id."'")->queryAll();
          
            if(!empty($emp_detail['0']['fk_tbl_customer_id_country_code'])){

                $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$emp_detail['0']['fk_tbl_customer_id_country_code']."'")->queryOne();
                $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
                $customer_detail['mobile'] = $emp_detail['0']['mobile'];
                $manage = SendOTP::generateOTP($customer_detail);
                $manage_msg = json_decode($manage, true);
               
                $msg = $manage_msg['message']['message']; 
                $user_detail = array(
                    'mobile'=>$emp_detail['0']['mobile'],
                    'email'=>$emp_detail['0']['email'],
                    'name'=>$emp_detail['0']['name'],
                );
              
            }else{
                $msg ="No subscription found on the entered number! You have to purchase the subscription for discounted rate.";
                
            }
            
            
            $return_array =array(
                'msg'=>$msg,
                'user_detail'=> $user_detail
            );

           
        }else{
            $msg ='Subscription number does not exist';
            $return_array =array(
                'msg'=>$msg,
                'user_detail'=> $user_detail
            );
           
        }
       
        
        return $return_array;
    }
    
    public static function validatewithnumber($data){
        $return_array =array();
        if(preg_match('/^[0-9]{10}+$/', $data['number'])) {
            
            $msg = " Valid Phone Number";
        } else {
            $msg =" Invalid Phone Number";
            $return_array = array(
                
                'msg'=> $msg ,
               
            );
            return $return_array ;
        }

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

            //echo("$data['email'] is a valid email address");
        } else {
            $return_array = array(
               
                'msg'=> 'Email is not valid',
               
            );
            return $return_array ;
            
        }
        $customer_detail= Yii::$app->db->createCommand("select e.mobile ,c.fk_tbl_customer_id_country_code
        from tbl_employee e
        left join tbl_customer c on c.fk_id_employee = e.id_employee
        where e.mobile='".$data['number']."' and  e.email='".$data['email']."'")->queryOne();

        if(!empty($customer_detail['fk_tbl_customer_id_country_code'])){
            $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
            $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
            $manage = SendOTP::generateOTP($customer_detail);
            $manage_msg = json_decode($manage, true);
           
            $msg = $manage_msg['message']['message']; 
          
        }else{
            $msg =" No Subscription found on the entered number, You have to purchase the subscription for discounted rate.";
            
        }

        $return_array =array(
            'msg'=>$msg,
        );
        return $return_array;
    
        
        

    }
    public static function  verifyotp($data){
        
        $customer_detail= Yii::$app->db->createCommand("select e.mobile ,c.fk_tbl_customer_id_country_code
        from tbl_employee e
        left join tbl_customer c on c.fk_id_employee = e.id_employee
        where e.mobile='".$data['mobile']."' and e.email='".$data['email']."'")->queryOne();
        if(!empty($customer_detail['fk_tbl_customer_id_country_code'])){
            $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
           
            $customer_detail['id_country_code'] = $CountryCode['id_country_code'];
            $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
            $request['customer_detail'] = $customer_detail;
            $request['mobile'] = $data['mobile'];
            $request['otp'] = $data['otp'];
            $response = SendOTP::verifyBySendOtpsubs($request);
            $return_array =array(
                'msg'=>$response['message'],
                'status'=>$response['status'],
                'fk_tbl_customer_id_country_code'=>$customer_detail['fk_tbl_customer_id_country_code'],
                
            );
           
        }else{
            $return_array =array(
                'status'=>'false',
                'msg'=>'The service is restricted to registered users only. Please register and use. Inconvenience regretted. Thank you.',
            );
           
        } 
        return  $return_array ;
    }             

    public static function fetchsubscriberdetail($data){
        
        $return_array = array();
        $customer_detail= Yii::$app->db->createCommand("select e.id_employee 
        from tbl_employee e
        left join tbl_customer c on c.fk_id_employee = e.id_employee
        where e.mobile='".$data['mobile']."' and e.email='".$data['email']."'")->queryOne();
        if(!empty($customer_detail)){
            $result = Yii::$app->db->createCommand("select e.id_employee,e.name,e.mobile,e.email, s.subscriber_name ,st.*, c.fk_tbl_customer_id_country_code,c.id_customer,c.email,c.mobile,oc.client_id,oc.client_secret,c.name ,UPPER(st.confirmation_number) as confirmation_number
            from tbl_employee_allocation ea  
            left join tbl_employee e on e.id_employee = ea.employee_id
            left join tbl_customer c on c.fk_id_employee = e.id_employee
            left join oauth_clients oc on oc.user_id = e.id_employee
            left join tbl_subscription_transaction_details st on st.subscription_transaction_id = ea.subscription_transaction_id
            left join tbl_super_subscription s on s.subscription_id = st.subscription_id
            where e.id_employee ='".$customer_detail['id_employee']."'  and st.remaining_usages > 0  order by ea.allocation_id desc ")->queryAll();
    
            $return_array =array(
                'data'=>$result,
            );
        }
        return $return_array;
        

    }

    public static function getpickupaddress($data){
        //echo '<pre>'; print_r($data); exit;
        $result =array();
        if(isset($data['delivery_type']) && $data['delivery_type'] !=2){
            return  $return_array =array(
                'msg'=>'Please select delivery type as airport transfer',
                'address'=>$result
            );
        }
        if(isset($data['pickup_type']) && $data['pickup_type'] == 2){
            return  $return_array =array(
                'msg'=>'Please select pick-up point as airport pickup point',
                'address'=>$result
            );
        }
        $departure ="arrival";
        if(isset($data['departure_type']) && $data['departure_type'] == 1){
            $departure ='departure';
        }
       

        $result = Yii::$app->db->createCommand("select *
        from tbl_departure_arrival_address_point ap 
        inner join tbl_departure_arrival_airport_mapping am on am.pickdrop_address_id = ap.pick_drop_id
        where pick_drop_status='enable' and pick_drop_type ='".$departure."' and airport_id ='".$data['airport_id']."'
        group by ap.pick_drop_id")->queryAll();

        if(!empty($result)){
            $return_array = array(
                'msg'=>'fetch sucessfully',
                'address'=>$result
            );
        }else{
            $return_array = array(
                'msg'=>'Data not found',
                'address'=>$result
            ); 
        }
        return $return_array;

      

                                                
    }

    public static function updateordertotal($id_order, $luggage_price, $service_tax, $insurance_price){
        $order_total_data = [
            [
                'fk_tbl_order_total_id_order'=>$id_order,
                'title'=>'Sub Order Amount',
                'price'=>$luggage_price,
                'code'=>'sub_order_amount',
            ],
            [
                'fk_tbl_order_total_id_order'=>$id_order,
                'title'=>'Service Tax Amount',
                'price'=>$service_tax,
                'code'=>'service_tax_amount',
            ],
            [
                'fk_tbl_order_total_id_order'=>$id_order,
                'title'=>'Insurance Amount',
                'price'=>$insurance_price,
                'code'=>'insurance_amount',
            ]
        ];
        $columnNameArray=['fk_tbl_order_total_id_order','title','price','code'];
        $order_total = Yii::$app->db->createCommand()
            ->batchInsert('tbl_order_total', $columnNameArray, $order_total_data)
            ->execute();
    }

    public static function placesubsorder($data){
        $model = new Order();
        if(isset($data['fk_tbl_order_id_slot'])){
            $DeliveryRes = Yii::$app->Common->getExpectedDeliveryDateTime($data['departure_type'],$data['service_type'],$data['fk_tbl_order_id_slot'],date('Y-m-d',strtotime($data['order_date'])));
        }
        if(!empty($DeliveryRes)){
            $model->delivery_datetime = isset($DeliveryRes['delivery_date_time']) ? $DeliveryRes['delivery_date_time'] : "";
            $model->delivery_time_status = isset($DeliveryRes['delivery_status']) ? $DeliveryRes['delivery_status'] : "";
        }

        $delivery_dates = Yii::$app->Common->selectedSlot($data['fk_tbl_order_id_slot'], $order_date, $delivery_type);
            
        $model->delivery_date = $delivery_dates['delivery_date'];
        $model->delivery_time = $delivery_dates['delivery_time'];
        $model->dservice_type = $data['dservice_type'];
        $model->flight_number = $data['flight_number'];

        $model->travell_passenger_name = $data['name'];
        $model->travel_person = '1';
        $model->fk_tbl_order_id_country_code = $data['fk_tbl_customer_id_country_code'];
        $model->travell_passenger_contact = $data['mobile'];
        $model->confirmation_number = $data['subscription_transaction_id'];
        $model->pnr_number = $data['pnr_number'];
        $model->location=$data['location'];
        $model->sector = $data['sector'];
        $model->weight = $data['weight'];
        $model->service_type = $data['departure_type']; //departure or arrival
        $model->order_transfer = $data['delivery_type'];// airport or cargo
        $model->delivery_type = $data['service_type'];//Local  or outstation
        $model->no_of_units = $data['bag_limit'];
        $model->terminal_type = $data['terminal_type'];
        $model->order_date = date('Y-m-d',strtotime($data['order_date']));
        $model->extra_weight_purched = $data['extra_weight_purched'];
        $model->airport_service = isset($data['pickup_type']) ? $data['pickup_type'] : 0;

        $model->fk_tbl_order_id_slot = isset($data['fk_tbl_order_id_slot']) ? $data['fk_tbl_order_id_slot'] : 1;
        $model->airport_slot_time = isset($data['airport_slot_time']) ? date("H:i:s", strtotime($data['airport_slot_time'])) : "00:00:00";
        $model->service_tax_amount = $data['service_tax_amount'];
        $model->luggage_price = $data['luggage_price'];

        if($data['remainUsages'] > $data['exhaustUsages']){
            $model->usages_used = $data['exhaustUsages'];
        } else if($data['remainUsages'] < $data['exhaustUsages']){
            $model->usages_used = $data['remainUsages'];
        }

        if($data['payment_type'] == "razorpay"){
            $model->payment_mode_excess = $data['payment_type'];
        } else {
            $model->payment_mode_excess = $data['payment_type'];
        }

        // pincode update in order table
        $model->pickup_pincode = $data['pickup_pincode'];
        $model->drop_pincode = $data['drop_pincode'];

        $model->insurance_price = 0;
        $model->payment_method = $data['payment_type'];
        if($data['payment_type'] == 'razorpay'){
            $model->fk_tbl_order_status_id_order_status = 2;
            $model->order_status = 'Confirmed';
            
        } else {
            $model->fk_tbl_order_status_id_order_status = ($data['departure_type'] == 1) ? 3 : 2;
            $model->order_status = ($data['departure_type'] == 1) ? 'open' : 'Confirmed';
            if(!empty($data['total_convayance_amount'])){
                $model->amount_paid = !empty($data['total_convayance_amount']) ? $data['total_convayance_amount'] : 0;
            }else {
                $model->amount_paid = !empty($data['luggage_price']) ? $data['luggage_price'] : 0;
            }
        }

        if($data['departure_type'] ==1){
            $departure_date = isset($data['departure_date']) ? $data['departure_date'] :null;
            $departure_time = isset($data['departure_time']) ? date("H:i", strtotime($data['departure_time'])) :null;
        }else{
            $arrival_date = isset($data['arrival_date']) ? $data['arrival_date'] :null;
            $arrival_time = isset($data['arrival_time']) ? date("H:i", strtotime($data['arrival_time'])) :null;
        }
        $date_created = date('Y-m-d H:i:s');
        $corporate_type = Yii::$app->Common->getCorporateType($data['corporate_id']);

        $corporate_id = Yii::$app->Common->getCorporates($data['corporate_id']);
        $corporate_id = $corporate_id->fk_corporate_id;
        if($model->save(false)){
            if(($data['departure_type'] == 2) || (!empty($_POST['convayance_price']))){
                $outstation_id = isset($data['outstation_id']) ? $data['outstation_id'] : 0;
                $city_name = isset($data['city_name']) ? $data['city_name'] : 0;
                $state_name = isset($data['state_name']) ? $data['state_name'] : 0;
                $extr_kms = isset($data['extr_kms']) ? $data['extr_kms'] : 0;
                $service_tax_amount = isset($data['service_tax_amount']) ? $data['service_tax_amount'] : 0;
                $convayance_price = isset($data['convayance_price']) ? $data['convayance_price'] : 0;
                $date = date('Y-m-d H:i:s');
                Yii::$app->db->createCommand("insert into tbl_order_zone_details (orderId,outstationZoneId,cityZoneId,stateId,extraKilometer,taxAmount,outstationCharge,createdOn) values($model->id_order,$outstation_id,$city_name,$state_name,$extr_kms,$service_tax_amount,$convayance_price,'".$date."')")->execute();
           
            }
            $thirdpartymapping = new ThirdpartyCorporateOrderMapping();
            $thirdpartymapping->thirdparty_corporate_id = $data['corporate_id'];
            $thirdpartymapping->order_id = $model->id_order;
            $thirdpartymapping->stateId = ($data['idState']) ? $data['idState'] : '';
            $thirdpartymapping->cityId = $data['fk_tbl_airport_of_operation_airport_name_id'];
            $thirdpartymapping->created_on = date('Y-m-d H:i:s');
            $thirdpartymapping->save();

            $model = Order::findOne($model->id_order);
            $model->order_number = 'ON'.date('mdYHis').$model->id_order;
            $model->save(false);

            $order_payment_details = new OrderPaymentDetails();
            $order_payment_details->id_order = $model->id_order;
            $order_payment_details->payment_type = $data['payment_type'];
            $order_payment_details->id_employee = $data['id_employee'];
            if($data['payment_type'] == 'razorpay'){
                $order_payment_details->payment_status = 'Not paid';
            }else{
                $order_payment_details->payment_status = 'Success';
            }
            $order_payment_details->amount_paid = $_POST['total_convayance_amount'] ? $_POST['total_convayance_amount'] : $_POST['luggage_price'];
            $order_payment_details->value_payment_mode = 'Order Amount';
            $order_payment_details->date_created= date('Y-m-d H:i:s');
            $order_payment_details->date_modified= date('Y-m-d H:i:s');
            $order_payment_details->save();

            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
            /*order total table*/
            $this->updateordertotal($model->id_order, $data['luggage_price'], $data['service_tax_amount'], $model->insurance_price);
            /*order total*/

            if($data['pickup_type'] == 2){
                if($data['departure_type'] == 2 || $data['departure_type'] == 1){
                    if($data['delivery_type'] == 1){
                        $orderMetaDetails = new OrderMetaDetails();
                        $orderMetaDetails->stateId = 0;
                        $orderMetaDetails->orderId = $model->id_order;
                        $orderMetaDetails->pickupPersonName =$data['name'];
                        $orderMetaDetails->pickupPersonNumber =$data['mobile'];
                        $orderMetaDetails->pickupPersonAddressLine1 =$data['pickupPersonAddressLine1'];
                        $orderMetaDetails->pickupPersonAddressLine2 =$data['pickupPersonAddressLine2'];

                        $orderMetaDetails->pickupArea =$data['pickupArea'];
                        $orderMetaDetails->pickupPincode =$data['pickupPincode'];
                        $orderMetaDetails->pickupLocationType =$data['pickupLocationType'];
                        $orderMetaDetails->pickupBuildingNumber = isset($data['pickupBuildingNumber']) ?$data['pickupBuildingNumber']:null;

                        $orderMetaDetails->dropBuildingNumber = isset($data['dropBuildingNumber']) ?$data['dropBuildingNumber']:null;

                        $orderMetaDetails->dropPersonName =$data['dropPersonName'];
                        $orderMetaDetails->dropPersonNumber =$data['dropPersonNumber'];
                        $orderMetaDetails->dropPersonAddressLine1 =$data['dropPersonAddressLine1'];
                        $orderMetaDetails->dropPersonAddressLine2 =$data['dropPersonAddressLine2'];
                        $orderMetaDetails->droparea =$data['droparea'];
                        $orderMetaDetails->dropPincode =$data['dropPincode'];

                        $orderMetaDetails->pickupLocationType =$data['pickupLocationType'];

                        $orderMetaDetails->pickupBusinessName =$data['pickupBusinessName'];
                        $orderMetaDetails->pickupMallName =$data['pickupMallName'];
                        $orderMetaDetails->pickupStoreName =$data['pickupStoreName'];

                        if($_POST['OrderMetaDetails']['pickupLocationType'] == 2){
                            $orderMetaDetails->pickupHotelType =$data['pickupHotelType'];
                            $orderMetaDetails->PickupHotelName =$data['PickupHotelName'];
                        }

                        //drop
                        $orderMetaDetails->dropLocationType =$data['dropLocationType'];
                        $orderMetaDetails->dropBusinessName =$data['dropBusinessName'];
                        $orderMetaDetails->dropMallName =$data['dropMallName'];
                        $orderMetaDetails->dropStoreName =$data['dropStoreName'];

                        if($_POST['OrderMetaDetails']['dropLocationType'] == 2){
                            $orderMetaDetails->dropHotelType =$data['dropHotelType'];
                            $orderMetaDetails->dropHotelName =$data['dropHotelName'];
                        }

                        $orderMetaDetails->save(false);
                        
                        $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'RazorPay', '');

                    }else{
                        $model->travell_passenger_name = $data['name'];
                        $model->fk_tbl_order_id_country_code = $data['fk_tbl_order_id_country_code'];
                        $model->travell_passenger_contact = $data['mobile'];

                        $model->flight_number = $data['flight_number'];
                        if($data['departure_type'] ==1){
                            $departure_date = isset($data['departure_date']) ? $data['departure_date'] :null;
                            $departure_time = isset($data['departure_time']) ? date("H:i", strtotime($data['departure_time'])) :null;
                        }else{
                            $arrival_date = isset($data['arrival_date']) ? $data['arrival_date'] :null;
                            $arrival_time = isset($data['arrival_time']) ? date("H:i", strtotime($data['arrival_time'])) :null;
                        }
                        $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));  
                        $model->save(false);
                    }
                }
            } else {
                $model->pickup_dropoff_point = $data['address'];
                $model->save(false);
            }
                
            $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
            $return_array=array(
                'status'=>true,
                'order_id'=>$model->id_order,
                "msg"=>'order created sucessfully'
            );  
               
        }else{
            $return_array=array(
                'status'=>false,
                "msg"=>'something went wrong'
            );   
        }

        return $return_array;

    }

    public static function getsupersubscription(){
        $supersubscription =array();
        $supersubscription  = Yii::$app->db->createCommand("SELECT `a`.* 
        FROM `tbl_super_subscription` `a` 
        where a.subscriber_status ='enable' ")->queryAll();

        return $supersubscription;
       
        //return $return_array;
    }

    public static function buysubscriptionbyuserOld($data){

       
        $country_code= Yii::$app->db->createCommand("select id_country_code
        from tbl_country_code 
        where status ='1' and country_code ='".$data['country_code']."' ")->queryOne();

        $user_detail = array();
        if(preg_match('/^[0-9]{10}+$/', $data['mobile'])) {
            
            $msg = " Valid Phone Number";
        } else {
            $msg =" Invalid Phone Number";
            $return_array = array(
                'res_status'=>201,
                'msg'=> $msg ,
                'user_detail'=> $user_detail
            );
            return $return_array ;
        }

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

            //echo("$data['email'] is a valid email address");
        } else {
            $return_array = array(
                'res_status'=>201,
                'msg'=> 'email is not valid',
                'user_detail'=> $user_detail
            );
            return $return_array ;
            
        }
        $emp = Employee::find()
        ->where( [ 'email' => $data['email']] )
        ->exists(); 
        $empmobile = Employee::find()
        ->where( ['mobile'=>$data['mobile']] )
        ->exists(); 

        if($emp === true){
            $user_detailemail = Employee::find()
            ->where( [ 'email' => $data['email'] ] )
            ->all();
          
            if($user_detailemail['0']['mobile'] != $data['mobile']){

                $returnmsg ="Mobile number did not match with account.";
            };
        }
       
        
        if($empmobile === true){
            $user_detailmobile = Employee::find()
            ->where( ['mobile' => $data['mobile'] ] )
            ->all();
            
            if($user_detailmobile['0']['email'] != $data['email']){
                $returnmsg ="Email-Id did not match. with account";
            };
        }
        
        $user_detail_exist = Employee::find()
        ->where( [ 'email' => $data['email'] , 'mobile'=>$data['mobile']] )
        ->exists(); 

       
       
        if($emp === true && $empmobile === true && $user_detail_exist === true){
            $user_detail = Employee::find()
            ->where( [ 'email' => $data['email'] , 'mobile'=>$data['mobile']] )
            ->all(); 
           
            $customer_detail = Customer::find()
            ->where( [ 'email' => $data['email'] , 'mobile'=>$data['mobile'] ,'fk_id_employee'=>$user_detail['0']['id_employee']])
            ->all();
            if(empty($customer_detail)){

                $customer_model = new Customer;
                $customer_model->name = $user_detail['0']['name'];
                $customer_model->mobile =$user_detail['0']['mobile'];
                $customer_model->email = $user_detail['0']['email'];
                $customer_model->mobile_number_verification = '1';
                $customer_model->gender = '0';
                $customer_model->fk_id_employee = $user_detail['0']['id_employee'];
                $customer_model->fk_tbl_customer_id_country_code = isset($country_code['id_country_code'])?$country_code['id_country_code']:'95';
                $customer_model->status = '1';
                $customer_model->date_created = date('Y-m-d H:i:s');
                $customer_model->save(false);
                $customer_id = Yii::$app->db->getLastInsertID();
                
                $client['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
                $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999));
                $client['user_id']=$customer_id;
                User::addClient($client);
            }else{
                   
                $token  = Yii::$app->db->createCommand("SELECT `oc`.* 
                FROM `oauth_clients` `oc` 
                where `oc`.`user_id` = '".$customer_detail[0]['id_customer']."'")->queryAll();
                if(empty($token)){
                    $client['client_id']=base64_encode($customer_detail[0]['email'].mt_rand(100000, 999999));
                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($customer_detail[0]['email'].mt_rand(100000, 999999));
                    $client['user_id']=$customer_detail[0]['id_customer'];
                    User::addClient($client);
                }

              
            }
            $tokens =array();
            $tokens  = Yii::$app->db->createCommand("SELECT e.id_employee,c.*,`oc`.* 
            from tbl_employee `e`
            left join tbl_customer c on c.fk_id_employee = e.id_employee
            left join oauth_clients oc on oc.user_id = c.id_customer 
            where  c.id_customer  = '".$customer_detail[0]['id_customer']."'")->queryAll();
    
            
            $return_array = array(
                'res_status'=>200,
                'msg'=>'user exist',
                'user_detail'=> $user_detail
            );
    
                 
            
            
        }else if($emp === true || $empmobile === true){
            $return_array = array(
                'res_status'=>203,
                'msg'=>$returnmsg,
                'user_detail'=> array()
            );
        } else{
            $user_detail = array(
                'email'=>$data['email'],
                'mobile'=>$data['mobile'],
                'name'=>$data['name'],
                'country_code'=>$data['country_code'],
            );
            if(!empty($data['country_code'])){
                $customer_detail['fk_tbl_customer_id_country_code'] = $data['country_code'];
                $customer_detail['mobile'] = $data['mobile'];

                $manage = SendOTP::generateOTP($customer_detail);
                $manage_msg = json_decode($manage, true);
            
                $msg = $manage_msg['message']['message']; 
               
            }else{
                $msg =" No Subscription found on the entered number, You have to purchase the subscription for discounted rate.";
                
            }

            $return_array = array(
                'res_status'=>201,
                'msg'=>$msg,
                'user_detail'=> $user_detail
            );
        }

        return $return_array;

    }

    public static function buysubscriptionbyuser($data){   
        $taken = array();
        $return_array = array();

        $country_code= Yii::$app->db->createCommand("select id_country_code
        from tbl_country_code 
        where status ='1' and country_code ='".$data['country_code']."' ")->queryOne();

        $user_detail = array();
        // check mobile format
        if(preg_match('/^[0-9]{10}+$/', $data['mobile'])) {
            
            $msg = " Valid Phone Number";
        } else {
            $msg =" Invalid Phone Number";
            $return_array = array(
                'res_status'=>201,
                'msg'=> $msg ,
                'user_detail'=> $user_detail
            );
            return $return_array ;
        }

        // check email format
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

            //echo("$data['email'] is a valid email address");
        } else {
            $return_array = array(
                'res_status'=>201,
                'msg'=> 'email is not valid',
                'user_detail'=> $user_detail
            );
            return $return_array ;
            
        }
       
        $empBoth = Employee::find()->where( ['mobile'=>$data['mobile'],'email' => $data['email']] )->one();
        $custBoth = Customer::find()->where( ['mobile'=>$data['mobile'],'email' => $data['email']] )->one();
        if(!empty($empBoth) && !empty($custBoth)){
            
            //code for customer exist but not mapped.
            if(empty($custBoth['fk_id_employee'])){
                $custBoth['fk_id_employee'] = $empBoth['id_employee'];
                $custBoth->save(false);
            }
            // get oath access token
            $tokens_cust = Yii::$app->db->createCommand("SELECT c.*,`oc`.* from tbl_customer `c` left join oauth_clients oc on oc.user_id = c.id_customer where c.id_customer  = '".$custBoth['id_customer']."'")->queryAll();
          
            $return_array = array(
                'res_status'=>200,
                'msg'=>'user exist',
                'user_detail'=> $empBoth,
                'token'=>$tokens_cust
            );
        } else if(!empty($empBoth) && empty($custBoth)){
            $customer_model = new Customer;
            $customer_model->name = $empBoth['name'];
            $customer_model->mobile =$empBoth['mobile'];
            $customer_model->email = $empBoth['email'];
            $customer_model->mobile_number_verification = '1';
            $customer_model->gender = '0';
            $customer_model->fk_id_employee = $empBoth['id_employee'];
            $customer_model->fk_tbl_customer_id_country_code = isset($country_code['id_country_code'])?$country_code['id_country_code']:'95';
            $customer_model->status = '1';
            $customer_model->date_created = date('Y-m-d H:i:s');
            $customer_model->save(false);
            $customer_id = Yii::$app->db->getLastInsertID(); 

            // check oath access token for employee
            $tokens  = Yii::$app->db->createCommand("SELECT e.id_employee,`oc`.*
            from tbl_employee `e`
            left join oauth_clients oc on oc.employee_id = e.id_employee 
            where e.id_employee  = '".$empBoth['id_employee']."'")->queryAll();
            if(empty($tokens)){
                $employee['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
                $employee['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999));
                $employee['employee_id']=$empBoth['id_employee'];
                User::addClient($employee);
            }
            // check oath access token for customer
            $client['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
            $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999));
            $client['user_id']=$customer_id;
            User::addClient($client);
            $tokens_cust = Yii::$app->db->createCommand("SELECT c.*,`oc`.* from tbl_customer `c` left join oauth_clients oc on oc.user_id = c.id_customer where c.id_customer  = '".$customer_id."'")->queryAll();
            $return_array = array(
                'res_status'=>200,
                'msg'=>'user exist',
                'user_detail'=> $empBoth,
                'token'=>$tokens_cust
            );
        } else if(empty($empBoth) && !empty($custBoth)){

            $emp_model = new Employee;
            $emp_model->name = $custBoth['name'];
            $emp_model->fk_tbl_employee_id_employee_role = '18';
            $emp_model->mobile = $custBoth['mobile'];
            $emp_model->email = $custBoth['email'];
            $emp_model->mobile_number_verification = '1';
            $emp_model->status = '1';
            $emp_model->date_created = date('Y-m-d H:i:s');
            $emp_model->date_modified =date('Y-m-d H:i:s');
            $emp_model->save(false);
            $id = Yii::$app->db->getLastInsertID();

            $detail_cust = Customer::find()->where(['id_customer' => $custBoth['id_customer'] ])->all();
            $detail_cust[0]->fk_id_employee = $id;
            $detail_cust[0]->save(false); 
             
          
            // check oath token for customer
            $tokens  = Yii::$app->db->createCommand("SELECT c.*,`oc`.* 
            from tbl_customer c 
            left join oauth_clients oc on oc.user_id = c.id_customer 
            where  c.id_customer  = '".$custBoth['id_customer']."'")->queryAll();
          
            if(empty($tokens)){ //insert oath token for customer
                $client['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
                $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999));
                $client['user_id']=$customer_id;
                User::addClient($client);
            }

            // check oath access token for employee
            $employee['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
            $employee['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999));
            $employee['employee_id']=$id;
            User::addClient($employee);

            $empBoth = Employee::find()->where(['id_employee' => $id ])->all();
    
            $return_array = array(
                'res_status'=>200,
                'msg'=>'user exist',
                'user_detail'=>  $empBoth,
                'token'=>$tokens
            );
        } else {
            $emp = Employee::find()->where( [ 'email' => $data['email']] )->exists();
            $empmobile = Employee::find()->where( ['mobile'=>$data['mobile']] )->exists();
            $cust = Customer::find()->where( [ 'email' => $data['email']] )->exists(); 
            $custmobile = Customer::find()->where( ['mobile'=>$data['mobile']] )->exists();
            if($empmobile){
                $user_detailmobile = Employee::find()
                ->where( ['mobile' => $data['mobile'] ] )
                ->all();
                $new_mail = Yii::$app->Common->emailhiddenformat($user_detailmobile['0']['email']);
                if($user_detailmobile['0']['email'] != $data['email']){
                    $returnmsg ="Entered Mobile Number is already existed and associated with the email id".' '.$new_mail .')';
                }
                $return_array = array(
                    'res_status'=>203,
                    'msg'=>$returnmsg,
                    'user_detail'=> array()
                );
            } else if($emp){
                $user_detailemail = Employee::find()
                ->where( [ 'email' => $data['email'] ] )
                ->all();
                $num = substr_replace($user_detailemail['0']['mobile'], 'XXXX', 0, 6);
                if($user_detailemail['0']['mobile'] != $data['mobile']){
                   
                    $returnmsg ="Entered Email ID is already existed and associated with the mobile number" .' '.$num.')';
                }
                $return_array = array(
                    'res_status'=>203,
                    'msg'=>$returnmsg,
                    'user_detail'=> array()
                );
            } else if($custmobile){
                $user_detailmobile = Customer::find()
                ->where( ['mobile' => $data['mobile'] ] )
                ->all();
                 $new_mail =  Yii::$app->Common->emailhiddenformat($user_detailmobile['0']['email']);
                    
                if($user_detailmobile['0']['email'] != $data['email']){
                    $returnmsg ="Entered Mobile Number is already existed and associated with the email id".' '.$new_mail .')';
                }
                $return_array = array(
                    'res_status'=>203,
                    'msg'=>$returnmsg,
                    'user_detail'=> array()
                );
            
            }else if($cust) {
                $user_detailemail = Customer::find()
                ->where( [ 'email' => $data['email'] ] )
                ->all();
                $num = substr_replace($user_detailemail['0']['mobile'], 'XXXXXX', 0, 6);
                if($user_detailemail['0']['mobile'] != $data['mobile']){
                   $returnmsg ="Entered Email ID is already existed and associated with the mobile number  ".' '.$num;
                }
                $return_array = array(
                    'res_status'=>203,
                    'msg'=>$returnmsg,
                    'user_detail'=> array()
                );

            }else {
                $user_detail = array(
                    'email'=>$data['email'],
                    'mobile'=>$data['mobile'],
                    'name'=>$data['name'],
                    'country_code'=>$data['country_code'],
                );
                if(!empty($data['country_code'])){
                    $customer_detail['fk_tbl_customer_id_country_code'] = $data['country_code'];
                    $customer_detail['mobile'] = $data['mobile'];
                    $manage = SendOTP::generateOTP($customer_detail);
                    $manage_msg = json_decode($manage, true);
                
                    $msg = $manage_msg['message']['message']; 
                   
                }else{
                    $msg =" No Subscription found on the entered number, You have to purchase the subscription for discounted rate.";
                    
                }

                $return_array = array(
                    'res_status'=>201,
                    'msg'=>$msg,
                    'user_detail'=> $user_detail
                );
            }
        }
        return $return_array;
    }

    public static function verifyusernumber($data){

        $user_detail = array();
        if(preg_match('/^[0-9]{10}+$/', $data['mobile'])) {
            
            $msg = " Valid Phone Number";
        } else {
            $msg =" Invalid Phone Number";
            $return_array = array(
                'status'=>'false',
                'msg'=> $msg ,
                'user_detail'=> $user_detail
            );
            return $return_array ;
        }

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

            //echo("$data['email'] is a valid email address");
        } else {
            $return_array = array(
                'status'=>false,
                'msg'=> 'email is not valid',
                'user_detail'=> $user_detail
            );
            return $return_array ;
            
        }

        $customer_detail['fk_tbl_customer_id_country_code'] = $data['country_code'];
        $request['customer_detail'] = $customer_detail;
        $request['mobile'] = $data['mobile'];
        $request['otp'] = $data['otp'];
        $response = SendOTP::verifyBySendOtpsubs($request);
        $return_array =array(
            'msg'=>$response['message'],
            'status'=>$response['status'],
            'user_detail'=>$data,
            
        );
        return $return_array;
    }
    public static function CreateCustomerEmployee($user_detail){

        
        $country_code= Yii::$app->db->createCommand("select id_country_code
        from tbl_country_code 
        where status ='1' and country_code ='".$user_detail['country_code']."' ")->queryOne();
       

        $emp_model = new Employee;

        $emp_model->name = $user_detail['name'];
        $emp_model->fk_tbl_employee_id_employee_role = '18';
        
        $emp_model->mobile = $user_detail['mobile'];
        $emp_model->email = $user_detail['email'];
        $emp_model->mobile_number_verification = '1';
        $emp_model->status = '1';
        $emp_model->date_created = date('Y-m-d H:i:s');
        $emp_model->date_modified =date('Y-m-d H:i:s');
        $emp_model->save(false);

        $id = Yii::$app->db->getLastInsertID();

        $customer_model = new Customer;

        $customer_model->name = $user_detail['name'];
        $customer_model->mobile = $user_detail['mobile'];
        $customer_model->email = $user_detail['email'];
        $customer_model->mobile_number_verification = '1';
        $customer_model->gender = '0';
        $customer_model->fk_id_employee = $id;
        $customer_model->fk_tbl_customer_id_country_code = isset($country_code['id_country_code'])?$country_code['id_country_code']:'95';
        $customer_model->status = '1';
        $customer_model->date_created = date('Y-m-d H:i:s');
        $customer_model->building_restriction =  NULL;
        
        $customer_model->save(false);
        $customer_id = Yii::$app->db->getLastInsertID();

        $client['client_id']=base64_encode($user_detail['email'].mt_rand(100000, 999999));
        $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($user_detail['email'].mt_rand(100000, 999999));
        $client['user_id']=$customer_id;
        User::addClient($client);

       

        /* Email and SMS on Registration of customer - Start*/
        User::sendemail($customer_model->email,"Verify Email Address",'verify_email_link',$customer_model);

        User::sendemail($customer_model->email,"Welcome to Carter X",'welcome_customer',$customer_model);

        $country_code = Customer::getcontrycode($user_detail['country_code']);

        User::sendsms($country_code.$customer_model->mobile,"Dear Customer, Thank you for registering with us and welcome to Carter X where luggage transfer is simplified. Get Carter & Travel Smart! .".PHP_EOL."Thanks carterx.in");
         /* End*/
         $address1 = Yii::$app->db->createCommand("SELECT c.address_line_1,c.address_line_2,c.area,c.pincode from tbl_customer c where c.id_customer='".$customer_id."'")->queryOne();
                $saved_address = ['registered_address'=>$address1,'last_order_address'=>false];

        return $return_array =array(
            'emp_id'=>$id,
            'customer_id'=>$customer_id,
            'saved_address'=> $saved_address,
            'status'=>'true',
            
        );
        return $return_array;
    }

    public static function buysubscriptionwithpurchase($data){
        $return_array = array();
        $user_attachment = array();
        $attachment_array =array();
        $cnf_array =array();
        $subs_array = array();
        $amt_array = array();
        $allemail= array();
        $pdf_array =array();
        $session_array = array();
        $subsnames =array();
        $phone_number = array($data['mobile']);
        
        $receipent = array();
        $receipent['user']['email'] =$data['email'];
        

        $today =date('Y-m-d :00:00:00');
        $expire_date = date('Y-m-d', strtotime('+1 year'));
        $emp = Employee::find()
        ->where( [ 'email' => $data['email'] , 'mobile'=>$data['mobile']] )
        ->all(); 
        if(!empty($emp)){
           // echo '<pre>'; print_r($data['purchase_detail'][0]['transaction_id']); exit;
        
            $count = count($data['purchase_detail']);
            foreach($data['purchase_detail'] as $pay){
                $pay_model = new SubscriptionPaymentLinkDetails;

                $pay_model->payment_short_link = $pay['transaction_id'];
                $pay_model->payment_invoice_id = "INV_".$pay['transaction_id'];
                $pay_model->payment_order_id = "Order_".$pay['transaction_id'];
                $pay_model->payment_subscription_id = $pay['subscription_id'];
                $pay_model->payment_unit = $pay['unit'];
                $pay_model->payment_amount = $pay['total_amount'];
                $pay_model->payment_status = $pay['status'];
                $pay_model->payment_link_create_date = date('Y-m-d H:i:s');
                $pay_model->save(false);
                
                
                for($i = 1; $i <= $pay['unit']; $i++){
                    $random_string = Yii::$app->Common->getrandomalphanum(6);
                    
                    $subscription_info = Yii::$app->db->createCommand("Select * from tbl_super_subscription where subscription_id = ".$pay['subscription_id'])->queryOne();
                    $gst_cost = ($subscription_info['subscription_cost'] * $subscription_info['subscription_GST']) / 100;
                    $remain=$subscription_info['subscription_cost']+$gst_cost;
                    $remain=round($remain,0);
                    $seprateAmount = $pay['total_amount'] / $pay['unit'];
                    $query = Yii::$app->db->createCommand("insert into tbl_subscription_transaction_details (subscription_id,confirmation_number,payment_invoice_id,paid_amount,redemption_cost,subscription_cost,no_of_usages,remaining_usages,gst_percent,payment_status,expire_date,payment_transaction_id,payment_date,remaining_balance,balence_value) VALUES ('".$pay['subscription_id']."','".$random_string."','".$pay['transaction_id']."','".$seprateAmount."','".$subscription_info['redemption_cost']."','".$subscription_info['subscription_cost']."','".$subscription_info['no_of_usages']."','".$subscription_info['no_of_usages']."','".$subscription_info['subscription_GST']."','".$pay['status']."', '".$expire_date."','".$pay['transaction_id']."','".date("Y-m-d H:i:s")."','".$remain."','0')")->execute();
                    $transection_id = Yii::$app->db->getLastInsertID();
                    $query = Yii::$app->db->createCommand("insert into tbl_employee_allocation (subscription_transaction_id,employee_id) VALUES ('".$transection_id."','".$emp[0]['id_employee']."')")->execute();
                
                    $email_content =array(
                        'user_detail'=>array(
                            'user_name'=>$emp[0]['name'],
                            
                        )
                    );
                    $subs_array[][] = $subscription_info['subscriber_name'];
                    $amt_array[$subscription_info['subscriber_name']] = $pay['total_amount'];
                    $session_array['amt_array'] =$amt_array;
                    $session_array['emp'] =$emp;
                    $session_array['subs_array'] =$subs_array;
                    $session_array['expire_date'] =$expire_date;

                    $total_cost = 0;
                    $gst_cost = 0;
                    $total_gst_cost = 0;
                    $total_cost = $pay['total_amount'] / $pay['unit'];
                    $gst_cost = ($subscription_info['subscription_cost'] * $subscription_info['subscription_GST']) / 100;
                    $totalcost_with_gst = $subscription_info['subscription_cost'] + $gst_cost;

                    $invoice_array=array(
                        'cnf_number'=> $random_string,
                        'number_of_useage'=>$subscription_info['no_of_usages'],
                        'redemption_cost'=> $subscription_info['redemption_cost'],
                        'subscription_cost'=>$subscription_info['subscription_cost'],
                        'subscription_gst'=> $subscription_info['subscription_GST'],
                        'gst_cost'=> $gst_cost,
                        'units'=> $i,
                        'total_subscription_cost'=> sprintf('%0.2f',$total_cost),
                        'total_gst_cost'=>$totalcost_with_gst,
                        'total_subscription_gst_cost'=> sprintf('%0.2f', $gst_cost),
                        'subscription_id' =>$transection_id,
                        'super_subscriber_name'=> $subscription_info['subscriber_name'],
                        'subscription_status'=>$pay['status'],
                        'expire_date'=>$expire_date,
                        'transection_id'=>$transection_id
                    );
                    array_push($pdf_array, $invoice_array);
                    array_push($subsnames,$subscription_info['subscriber_name']);
                    $session_array['pdf_detail'] =$pdf_array;

                    if(!empty($subscription_info['primary_contact'])){
                        array_push($phone_number,$subscription_info['primary_contact']);
                    }
                    if(!empty($subscription_info['secondary_contact'])){
                        array_push($phone_number,$subscription_info['secondary_contact']);
                     }
                    
                    
                    if(!empty($subscription_info['primary_email'])){
                        $receipent[$subscription_info['subscriber_name']]['email'][] = $subscription_info['primary_email'];
                         array_push($allemail,$subscription_info['primary_email']);
                    }
                    if(!empty($subscription_info['secondary_email'])){
                       $receipent[$subscription_info['subscriber_name']]['email'][] =$subscription_info['secondary_email'];
                     array_push($allemail,$subscription_info['secondary_email']);
                    }
                    $cnf_array[$subscription_info['subscriber_name']][] =$random_string;
                    
                    $session_array['subsnames'] =$subsnames;
                    $session_array['cnf_array'] =$cnf_array;
                    $session_array['receipent'] =$receipent;
                    $session_array['allemail'] =$allemail;
                    $session_array['phone_number'] =$phone_number;

                    



                }
            } 
           
           
            $subscription_detail = Yii::$app->db->createCommand("select s.subscriber_name,ea.subscription_transaction_id,st.confirmation_number,st.no_of_usages,st.paid_amount ,st.payment_status ,st.expire_date,UPPER(st.confirmation_number) as confirmation_number,oc.client_id,oc.client_secret,
            c.name,c.email,c.mobile,c.fk_tbl_customer_id_country_code,c.mobile_number_verification,c.id_customer
            from tbl_employee_allocation ea  
            left join tbl_employee e on e.id_employee = ea.employee_id
            left join tbl_customer c on c.fk_id_employee = e.id_employee
            left join oauth_clients oc on oc.user_id = e.id_employee
            left join tbl_subscription_transaction_details st on st.subscription_transaction_id = ea.subscription_transaction_id
            left join tbl_super_subscription s on s.subscription_id = st.subscription_id
            where e.id_employee ='".$emp[0]['id_employee']."' and st.payment_transaction_id='".$data['purchase_detail']['0']['transaction_id']."'")->queryAll();
            $session_array['subscription_detail'] =$subscription_detail;
            Yii::$app->session->set('invoicegenerate',$session_array);
            $return_array = array(
                'msg'=>'Your subscription purchase has been confirmed',
                'subscription_detail'=>  $subscription_detail,
                'session_array'=>$session_array,
            );
        }else{
            $return_array = array(
                'msg'=>'something went wrong',
               
                
            );
        }
        //return $supersubscription;
       
        return $return_array;

    }

    public static function sendmail($data){
       
        $attachment_array =array();
        $user_attachment =array();
        $no_of_times = count($data['pdf_detail']);
        for($i=0; $i < $no_of_times; $i++){

           $attachment_det =Yii::$app->Common->generatepurchaseinvoicepdf($data['pdf_detail'][$i],'purchase_subscription_invoice_template');
           $attachment_array[$data['pdf_detail'][$i]['super_subscriber_name']][]= $attachment_det;
           array_push($user_attachment ,$attachment_det);
        }
     
        $email_content['cnf']=$data['cnf_array'];
        $email_content['user_detail']['subscription'] = $data['subs_array'];
        $email_content['user_detail']['total_amount'] = $data['amt_array'];
       
        $no_of_times = count($email_content['cnf']);
        for($i=0; $i < $no_of_times; $i++){
            $key =array_keys($email_content['cnf']);
            $mail_data = array(
                'user_detail'=>array(
                    'user_name'=>$data['emp'][0]['name'],
                    'subscription'=>$key[$i],
                    'amount_paid'=>$email_content['user_detail']['total_amount'][$key[$i]],
                    'expiry_date'=>$data['expire_date']
                ),
                'cnf'=>$email_content['cnf'][$key[$i]],
                
            );
           User::sendemailInvoiceattachment($data['receipent'][$key[$i]],"CarterX Subscription Invoice #".$key[$i]."",'purchase_cnf',$mail_data,$attachment_array[$key[$i]]);
        }
            $new_array = array();
            $sub_name = array();
            $total_useage =0;
            $total_amt =0;
            $no_of_useage =array();
            if(isset($data['subscription_detail']) && !empty($data['subscription_detail'])){
                foreach($data['subscription_detail'] as $del){
                    $total_amt =$total_amt + $del['paid_amount'];
                    $total_useage  = $total_useage +$del['no_of_usages'];
                    $cinfir_n = $del['confirmation_number'];
                    array_push($no_of_useage ,$del['no_of_usages']);
                    array_push($new_array ,$del['confirmation_number']);
                    array_push($sub_name ,$del['subscriber_name']);
                }
            }
            $subname = array_unique($sub_name);
            $supername ="";
            if(!empty($subname)){
                $supername = implode(" ,",$subname);
            }
            $cnfnumber="";
            if(!empty($new_array)){
                $cnfnumber = implode(" ,",$new_array);
            }
            $sent_array = array(
                'Name_of_Subscription_Token'=>$supername,
                'CurrentDate'=>date('Y-m-d'),
                'DateofPurchase'=> date('Y-m-d'),
                'ValidTill'=>$data['expire_date'],
                'total_useage'=>$total_useage,
                'value_pending'=>"0",
                'number_of_useage_used'=>"0",
                'detail_of_useage'=>"NA",
                'cnf'=>$cnfnumber
            );
            $emailDetail =array(
                'subscription'=>$supername,
                'user_name'=>$data['emp'][0]['name']
            );
            
            $user_data = array(
                'user_detail'=>array(
                    'user_name'=>$data['emp'][0]['name'],
                    'subscription'=>strtoupper($supername),
                    'amount_paid'=>$total_amt,
                    'expiry_date'=>$data['expire_date'] 
                ),
                'cnf'=>$new_array,
                'subname'=>$data['subsnames'],
                'eachuseage'=>$no_of_useage
            );
            $sms_array =array(
                'confirmation_number'=>$cnfnumber,
                'subscription_name'=>$supername
            );

            User::sendemailInvoiceattachmenttouser($data['receipent']['user']['email'],"CarterX Subscription Invoice #".strtoupper($supername)."",'purchase_cnf',$user_data,$user_attachment);
            $attachment_cnf =Yii::$app->Common->genaratesubscriptionInvoicePdf($sent_array,'subscription_cnf_template');
            User::sendcnfemail($data['receipent']['user']['email'],"CarterX Confirmed Subscription #".strtoupper($supername)."",'sub_cnf_email',$emailDetail,$attachment_cnf,array_unique($data['allemail']));
            Yii::$app->Common->subscriptionSmsSent('purchase_of_subscription',$sms_array,array_unique($data['phone_number']));
        
            return  $return_array=array('msg'=>'email send succesfully');
              

    }

}
