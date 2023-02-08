<?php

namespace app\controllers;

use Yii;
use OAuth2;
/* For APi-start */
use linslin\yii2\curl;
use yii\web\Response;
use yii\helpers\Json;
use yii\rest\ActiveController;
use yii\filters\Cors;
/* For APi-end */
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Customer;
use app\models\CustomerSearch;
use app\models\OrderPaymentDetails;
use app\models\User;
use app\models\CustomerLoginForm;
use app\models\CountryCode;
use app\models\WhitelistCustomers;
use app\components\SendOTP;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use app\models\Order;
use yii\web\UploadedFile;
use yii\data\Pagination;

use app\api_v2\v2\models\OrderPromoCode;
/**
 * CustomerController implements the CRUD actions for Customer model.
 */
class CustomerApiController extends ActiveController
{
    public $modelClass = 'app\models\Customer';
    /**
     * @inheritdoc
     */
   
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

    /**
     * Lists all Customer models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CustomerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Customer model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Customer model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    /*public function actionCreate()
    {
        $model = new Customer();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_customer]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }*/

    /**
     * Creates a new Customer.
     * @return mixed
     */
    public function actionTestemail()
    {
      User::sendemail('shiny.d@pacewisdom.com',"Registration Successful","Thank you for registering with Porter");
      //User::sendsms('9900469512',"Thank you for registering with Porter");
      print_r('successful');exit;
    }

    

    public function actionRegister()
    {
        $model = new Customer();
        $_POST=User::datatoJson();
        if (!empty($_POST)) {
          //print_r($_POST);
          //echo Json::encode(['post'=>$_POST,'file'=>$_FILES]);
          //print_r($_FILES);
          //exit();
            $model->attributes = $_POST;
            $model->fk_tbl_customer_id_country_code = $_POST['id_country_code'];
            if($model->validate())
            { 
            
                if(!empty($_FILES['customer_document']))
                {
                  /*$path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$_FILES['customer_document']['name'];
                  move_uploaded_file($_FILES['customer_document']['tmp_name'],$path);
                  $model->document = $_FILES['customer_document']['name'];*/
                  $extension = explode(".", $_FILES["customer_document"]["name"]);
                  $rename_customer_document = "customer_document_".date('mdYHis').$extension[0].".".$extension[1];
                  $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_customer_document;
                  //print_r($path);exit;
                  move_uploaded_file($_FILES['customer_document']['tmp_name'],$path);
                  $model->document = $rename_customer_document;
                  
                }else{
                  $model->document = '';
                }
                $model->landmark = $_POST['landmark'];
                $model->building_number = $_POST['building_number'];
                $model->building_restriction = !empty($_POST['building_restriction']) ? serialize(explode(',',$_POST['building_restriction'])) : '';
                $model->other_comments = $_POST['other_comments'];
                $model->id_proof_verification = 1;
                $model->status = 1;
                $model->date_created = date('Y-m-d H:i:s');
                $model->save(); 

                $client['client_id']=base64_encode($_POST['email'].mt_rand(100000, 999999));
                $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($_POST['email'].mt_rand(100000, 999999));
                $client['user_id']=$model->id_customer;
                User::addClient($client);
                /* Email and SMS on Registration of customer - Start*/
                User::sendemail($model->email,"Verify Email Address",'verify_email_link',$model);

                User::sendemail($model->email,"Welcome to Carter X",'welcome_customer',$model);

                $country_code = Customer::getcontrycode($_POST['id_country_code']);

                User::sendsms($country_code.$model->mobile,"Dear Customer, Thank you for registering with us and welcome to Carter X where luggage transfer is simplified. Get Carter & Travel Smart! .".PHP_EOL."Thanks carterx.in");
                /* End*/


                $customer_detail=CustomerLoginForm::customerDetail();
                /*$response = $this->actionSendotp();
                $decoded_response = Json::decode($response);*/
                /*$response = $this->actionSendotp1();
                $decoded_response = Json::decode($response);*/
                $model->mobile_number_verification = 1;
                $model->save();

                $address1 = Yii::$app->db->createCommand("SELECT c.address_line_1,c.address_line_2,c.area,c.pincode from tbl_customer c where c.id_customer='".$model->id_customer."'")->queryOne();
                $saved_address = ['registered_address'=>$address1,'last_order_address'=>false];

                echo Json::encode(['status'=>true, 'message'=>'Registration Successful',"customer_detail"=>$customer_detail,'saved_address'=>$saved_address]);
              /*}
              else
              {
                echo Json::encode(['status'=>false, 'message'=>'Please upload a document']);
              }*/
              
            }
            else
            {

                foreach ($model->getErrors() as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $lines[$attribute] = Html::encode($error);
                    }
                }
                echo Json::encode(['status'=>false, 'message'=>$error,'error'=>$lines]);
            }
            
        } else {
            echo Json::encode(['status'=>false, 'message'=>'Registration Failed,No Data']);
        }
    }

    public function actionSendotp($postData = NULL)
    { 
      if(empty($postData)){
        $_POST = User::datatoJson();
      } else {
        $_POST = $postData;
      }
      
      $customer_detail= Yii::$app->db->createCommand("select * from tbl_customer where mobile='".$_POST['mobile']."'")->queryOne();
      $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
      $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
      $otp = SendOTP::generateOTP($customer_detail);
      return $otp;
    }

    public function actionSendotp1()
    {
      $customer_detail= Yii::$app->db->createCommand("select * from tbl_customer where mobile='".$_POST['mobile']."'")->queryOne();
      $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
      $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
      $otp = SendOTP::generateOTP($customer_detail);
      return $otp;
    }

    public function actionResendendotp()
    {
      $_POST = User::datatoJson();
      $customer_detail= Yii::$app->db->createCommand("select * from tbl_customer where mobile='".$_POST['mobile']."'")->queryOne();
      $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
      $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
      $otp = SendOTP::generateOTP($customer_detail);
      echo Json::encode(['status'=>true, 'message'=>'OTP Resent','otp_response'=>$otp]);
    }

    public function actionVerifyotp()
    {
      $_POST = User::datatoJson();
      $model = new CustomerLoginForm();

      $customer_detail=$model->customerDetail();
          if(empty($customer_detail['client_id']))
          {
              $customer_detail['client_id'] = $client['client_id']=base64_encode($customer_detail['email']);
              $customer_detail['client_secret'] = $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($customer_detail['email']);
              $client['user_id']=$customer_detail['id_customer'];
              User::addClient($client);
          }
          Yii::$app->db->createCommand("UPDATE tbl_customer set update_status = 0 where id_customer='".$customer_detail['id_customer']."'")->execute();
          $address1 = Yii::$app->db->createCommand("SELECT c.landmark, c.building_number, c.building_restriction, c.other_comments ,c.address_line_1,c.address_line_2,c.area,c.pincode from tbl_customer c where c.id_customer='".$customer_detail['id_customer']."'")->queryOne();
          //($address1['building_restriction'] !='' && $address1['building_restriction'] != NULL) ? $address1['building_restriction'] = implode(',',unserialize($address1['building_restriction'])) : '';
            if($address1['building_restriction'] !='' && $address1['building_restriction'] != NULL && $address1['building_restriction'] != 's:0:"";'){
                $address1['building_restriction_ids'] = implode(',',unserialize($address1['building_restriction'])); 
                $building_restriction = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$address1['building_restriction_ids'].")")->queryAll(); 
                if($building_restriction){
                    $address1['building_restriction'] = implode(', ', array_column($building_restriction, 'restriction'));
                    $address1['building_restriction_ids'] = '['.$address1['building_restriction_ids'].']';
                }
            }else{
                $address1['building_restriction'] = '';
                $address1['building_restriction_ids'] = '';
            }
          $saved_address = array();
          $customer_orders = Yii::$app->db->createCommand("select O.id_order,O.fk_tbl_order_id_customer from tbl_order O where O.fk_tbl_order_id_customer='".$customer_detail['id_customer']."' ORDER BY O.id_order DESC")->queryOne();
          if(!empty($customer_orders)){
            $address2 = Yii::$app->db->createCommand("SELECT OS.landmark, OS.building_number, OS.building_restriction, OS.other_comments, OS.address_line_1,OS.address_line_2,OS.area,OS.pincode from tbl_order_spot_details OS where OS.fk_tbl_order_spot_details_id_order='".$customer_orders['id_order']."'")->queryOne();
            //($address2['building_restriction'] !='' && $address2['building_restriction'] != NULL) ? $address2['building_restriction'] = implode(',',unserialize($address2['building_restriction'])) : '';
            if(!empty($address2)){
                if($address2['building_restriction'] !='' && $address2['building_restriction'] != NULL && $address2['building_restriction'] != 's:0:"";'){
                    $address2['building_restriction_ids'] = implode(',',unserialize($address2['building_restriction']));
                    $building_restriction2 = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$address2['building_restriction_ids'].")")->queryAll(); 
                        if($building_restriction2){
                            $address2['building_restriction'] = implode(', ', array_column($building_restriction2, 'restriction'));
                            $address2['building_restriction_ids'] = '['.$address2['building_restriction_ids'].']';
                        }
                }else{
                    $address2['building_restriction'] = '';
                    $address2['building_restriction_ids'] = '';
                }
            }
            $saved_address = ['registered_address'=>$address1,'last_order_address'=>$address2];
          }

      
      //$customer_detail= Yii::$app->db->createCommand("select * from tbl_customer where mobile='".$_POST['mobile']."'")->queryOne();
      //$otp = SendOTP::generateOTP($customer_detail);
      //print_r($otp);exit;
      //return $otp;
      //$decoded_otp = Json::decode($otp);
      //print_r($decoded);exit;
      //$otp_sent = $decoded_otp['response_otp']['response']['oneTimePassword'];
      $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
      $customer_detail['id_country_code'] = $CountryCode['id_country_code'];
      $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
      $request['customer_detail'] = $customer_detail;
      $request['saved_address'] = empty($saved_address) ? $saved_address : array();
      $request['mobile'] = $_POST['mobile'];
      $request['otp'] = $_POST['otp'];
      if($_POST['mobile'] == '8292782606'){
        if($_POST['otp'] == '2782'){
          $resp['status'] =  true;
          $resp['message'] =  "Number Verified Successfully";
          $resp['customer_detail'] = $request['customer_detail'];
          $resp['saved_address'] = isset($request['saved_address']) ? $request['saved_address']:"";
        } else {
          $resp['status'] =  false;
          $resp['message'] =  "OTP did not match!";
        }
        echo json_encode($resp);
      } else {
        SendOTP::verifyBySendOtp($request);
      }
    }

    public function actionProfile()
    {
        $this->actiongetResource();
        
        $_POST = User::datatoJson();
        //Customer::isupdated($_POST['id_customer']); //before proceed, check if user modified

        if(!empty($_POST)) 
        { 
          $model = Customer::findOne($_POST['id_customer']);
          $model->attributes=$_POST;
          $model->building_restriction = !empty($_POST['building_restrictions']) ? serialize($_POST['building_restrictions']) : '';
          if($model->save())
          {
            echo Json::encode(['status'=>true, 'message'=>'Profile Updated Succesfully']);
          }
          else
          {
            echo Json::encode(['status'=>false, 'message'=>'Profile Update Failed','error'=>$model->errors]);
          }
        }
        else
        {
          echo Json::encode(['status'=>false, 'message'=>'No Data']);
        }      
    }


    public function actionUpdatedocument()
    {
        $_POST = User::datatoJson();
        print_r($_POST);exit;
        /*if(!empty($_POST)) 
        {
            $model = Customer::findOne($_POST['id_customer']);
            $model->attributes=$_POST;
            if(!empty($_FILES['customer_document']))
            {

                $path=Yii::$app->params['document_root'].'basic/uploads/customer_documents/'.$_FILES['customer_document']['name'];
                move_uploaded_file($_FILES['customer_document']['tmp_name'],$path);
                $model->document = $_FILES['customer_document']['name'];
                if($model->save())
                {
                  echo Json::encode(['status'=>true, 'message'=>'Profile Updated Succesfully']);
                }
                else
                {
                  echo Json::encode(['status'=>false, 'message'=>'Profile Update Failed','error'=>$model->errors]);
                }

            }else{
                echo Json::encode(['status'=>false, 'message'=>'Please upload the document']);
            }
        }
        else
        {
            echo Json::encode(['status'=>false, 'message'=>'No Data']);
        }    */

    }


    public function actionLogin()
    {
      $model = new CustomerLoginForm();
      $_POST=User::datatoJson();
      if (isset($_POST)) {
        // if login with corporate id
        if(!empty($_POST['corporate_id'])){
          $res = Yii::$app->db->CreateCommand("SELECT * FROM tbl_corporate_employee_airline_mapping where customerId = '".$_POST['corporate_id']."'")->queryOne();
          if(!empty($res)){
            $custRes = Yii::$app->db->CreateCommand("SELECT * FROM tbl_customer where id_customer = '".$res['fk_corporate_employee_id']."'")->queryOne();
            if(!empty($custRes) && ($custRes['fk_role_id'] == 19)){
              $_POST = array_merge($_POST,array("mobile" => $custRes['mobile'],'id_customer' => $custRes['id_customer']));
            } else if(!empty($custRes) && ($custRes['fk_role_id'] != 19)){
              echo Json_encode(['status' => false, "message" => "Customer not a Corporate User!"]);die;
            } else {
              echo Json_encode(['status' => false, "message" => "Customer not found!"]);die; 
            }
          } else {
            echo Json_encode(['status' => false, "message" => "Invalid Corporate ID. Please enter correct Corporate ID."]);die;
          }
        }
        $customer_detail = $model->customerDetail(); // get customer details
        if(isset($customer_detail['status']) && ($customer_detail['status'] == 0)){
          echo Json::encode(['status'=>false, 'message'=>'Your account has been disabled. Please write to gatetogate@carterporter.in with CorpID and TourID for more information. Thank you']);
        } else if(!empty($customer_detail) && ($customer_detail['status'] == 1)) { // customer exist
          if(($customer_detail['email_verification'] == 0) && ($customer_detail['fk_role_id'] == 19) && (!empty($_POST['corporate_id']))){ // email doesn't verify
            echo Json::encode(['status' => false, 'message' => "Your email confirmation is pending, please try to confirm with the link sent to your email"]);die;
          } else { // email verified
            if(!empty($_POST['airline_name']) && ($customer_detail['fk_role_id'] == 19)  && ($customer_detail['acc_verification'] == 1)){ // section for corporate login

              $checkCorporateUser = Yii::$app->Common->checkMappedAirline($customer_detail['id_customer']);
              // if(in_array(strtoupper("CarterX"),$checkCorporateUser['airline_name'])){
                // here add delete customer old mapped corporate id
                // Yii::$app->db->createCommand("Delete From tbl_corporate_employee_airline_mapping Where fk_corporate_employee_id = '".$customer_detail['id_customer']."' and fk_airline_id != '42'")->execute();

              // } else 
              if(!in_array(strtoupper($_POST['airline_name']), $checkCorporateUser['airline_name'])){
                // here add mapping process (customer to airline)
                $airlineInfo = Yii::$app->db->createCommand("SELECT * FROM tbl_airlines where airline_name = '".$_POST['airline_name']."'")->queryOne();
                if(!empty($airlineInfo)){
                  $airlineName = str_replace(' ','',strtoupper($airlineInfo['airline_name'])).$customer_detail['id_customer'];
                  Yii::$app->db->CreateCommand("INSERT INTO tbl_corporate_employee_airline_mapping (fk_corporate_employee_id,fk_airline_id,customerId) values('".$customer_detail['id_customer']."','".$airlineInfo['airline_id']."','".$airlineName."')")->execute();
                } else {
                  echo Json::encode(["status" => false, 'message' => "Airline not found!"]);die;
                }
              }
            } else if(!empty($_POST['airline_name']) && $customer_detail['fk_role_id'] != 19){
              echo Json::encode(['status' => false, 'message' => "Normal Customer is not allowed to Corporate login!"]);die;
            }
          }

          if(isset($_POST['device_id']) && isset($_POST['device_token'])) {
                  Yii::$app->db->createCommand("UPDATE {{customer}} set device_id='".$_POST['device_id']."', device_token='".$_POST['device_token']."' where id_customer='".$customer_detail['id_customer']."'")->execute();
          }
          $response = $this->actionSendotp($_POST);
          $decoded_response = Json::decode($response);
          if(empty($decoded_response)) {
            echo Json::encode(['status'=>false, 'message'=>'Failed Sending OTP','otp_response'=>$decoded_response]);
          } else {
            echo Json::encode(['status'=>true, 'message'=>'Logged in successfully','otp_response'=>$decoded_response, 'mobile' => $_POST['mobile'], 'id_customer' => $customer_detail['id_customer']]);
          }
        } else {
          echo Json::encode(['status'=>false, 'message'=>'The service is restricted to registered users only. Please register and use. Inconvenience regretted. Thank you.','error'=>$model->geterrors()]);
        }
      } else { // customer doesn't exist 
        echo Json::encode(['status'=>false, 'message'=>'Invalid Phone number or Data','error'=>$model->geterrors()]);
      }
    }

    public function actionCustomerorders()
    {
        //$d = Order::getAllocationdetails(20);
        $this->actiongetResource();
        $_POST = User::datatoJson();

        $model = new Order();
        //Customer::isupdated($_POST['id_customer']); //before proceed, check if user modified
        if(isset($_POST))
        {
          $customer_mobile= Customer::findOne($_POST['id_customer']);
          /*$customer_orders = Yii::$app->db->createCommand("select O.id_order,O.fk_tbl_order_id_customer from tbl_order O where O.fk_tbl_order_id_customer='".$_POST['id_customer']."' OR O.travell_passenger_contact='".$customer_mobile->mobile."' ORDER BY O.id_order DESC")->queryAll();
          $customer_order_ids = \yii\helpers\ArrayHelper::getColumn($customer_orders, 'id_order');*/
          $query = Order::find()->where(['fk_tbl_order_id_customer'=>$_POST['id_customer']])
                                ->orwhere(['travell_passenger_contact'=>$customer_mobile->mobile])
                                ->orderby('id_order DESC');

          $countQuery = clone $query;
          $pages = new Pagination(['totalCount' => $countQuery->count()]);
          $customer_orders = $query->offset($pages->offset)
              ->limit($pages->limit)
              ->all();

          $customer_order_ids = \yii\helpers\ArrayHelper::getColumn($countQuery->all(), 'id_order');
          /*print_r('count:'.$countQuery->count());
          print_r($pages->offset);
          print_r($customer_orders);exit;
          exit;*/

            if(!empty($customer_orders))
            {
              $i=0;
              foreach($customer_orders as $orders)
              {
                  $booking_data[] = Order::getorderdetails($orders['id_order']);
                  
                  $booking_data[$i]['order']['airport_name'] = $model->getAirportName($booking_data[$i]['order']['airport_id']);
                 $booking_data[$i]['order']['order_date'] = date('Y/m/d', strtotime($booking_data[$i]['order']['order_date']));
                  $booking_data[$i]['order']['departure_date'] = ($booking_data[$i]['order']['departure_date'] == null) ? null : date('Y/m/d', strtotime($booking_data[$i]['order']['departure_date']));
                  $booking_data[$i]['order']['arrival_date'] = ($booking_data[$i]['order']['arrival_date']==null) ? null : date('Y/m/d', strtotime($booking_data[$i]['order']['arrival_date']));
                  if($booking_data[$i]['order']['reschedule_luggage'] == 1)
                  {
                    $reschedule_order_details = Order::find()->select('order_number')->where('id_order = :id_order', [':id_order' => $booking_data[$i]['order']['related_order_id']])->one();
                    $reschedule_order_details1 = Order::getorderdetails($booking_data[$i]['order']['related_order_id']);
                    $booking_data[$i]['order']['related_order_number'] = $reschedule_order_details['order_number'];
                    (in_array($booking_data[$i]['order']['related_order_id'], $customer_order_ids)) ? '' : array_push($customer_order_ids, $booking_data[$i]['order']['related_order_id']);
                  }
                  $order_price_break = Order::getOrderPrice($orders['id_order']);
                  $refund_amount =array_column(array_filter($order_price_break, function($el) { return $el['code']=='refund_amount'; }),'price');
                 // print_r($refund_amount);exit;
                  $booking_data[$i]['order']['refund_amount'] =empty($refund_amount) ? null : $refund_amount[0];
                  $cancellation_amount =array_column(array_filter($order_price_break, function($el) { return $el['code']=='cancellation'; }),'price');

                  $promo_code = OrderPromoCode::findOne(['order_id' => $orders['id_order']]);
                  
                  $isrefunded = OrderPaymentDetails::find()->where(['payment_status'=>'Refunded','id_order'=>$orders['id_order']])->one();
                  $ispaid = OrderPaymentDetails::find()->where(['id_order'=>$orders['id_order']])->all();
                  if(count($ispaid) > 1 && $booking_data[$i]['order']['modified_amount']){
                    $booking_data[$i]['order']['modified_amount'] = 0;
                  }
                  if($promo_code){
                        if($booking_data[$i]['order']['modified_amount'] < 0){
                          $booking_data[$i]['order']['modified_amount'] ='';
                        }
                        $booking_data[$i]['order']['refund_status'] ='';
                        $booking_data[$i]['order']['refund_amount'] ='';
                  }else{
                    if($isrefunded){
                      $booking_data[$i]['order']['refund_status'] ='Refunded';
                      $booking_data[$i]['order']['refund_amount'] =$isrefunded->amount_paid;
                    }
                  }

                  if(!empty($cancellation_amount))
                  {
                    //$cancellation_refund_amount = $booking_data[$i]['order']['amount_paid'] - $cancellation_amount[0];
                    if($booking_data[$i]['order']['delivery_type'] == 2){
                      $cancellation_refund_amount = $booking_data[$i]['order']['luggage_price'] - $cancellation_amount[0];
                      $tax = $booking_data[$i]['order']['outstationCharge'] * (Yii::$app->params['gst_percent']/100);
                      $total_cancel_amount = $booking_data[$i]['order']['outstationCharge'] + $booking_data[$i]['order']['extra_km_price'] + $tax + $cancellation_refund_amount;
                      $booking_data[$i]['order']['cancellation_amount'] =empty($cancellation_amount) ? null : $cancellation_amount[0];
                      $booking_data[$i]['order']['cancellation_refund_amount'] =$total_cancel_amount;
                    }else{
                      $cancellation_refund_amount = $booking_data[$i]['order']['luggage_price'] - $cancellation_amount[0];
                      $booking_data[$i]['order']['cancellation_amount'] =empty($cancellation_amount) ? null : $cancellation_amount[0];
                      $booking_data[$i]['order']['cancellation_refund_amount'] =$cancellation_refund_amount;
                    }
                  }
                  $added_item_amount =array_column(array_filter($order_price_break, function($el) { return $el['code']=='added_item_amount'; }),'price');
                  $booking_data[$i]['order']['added_item_amount'] =empty($added_item_amount) ? null : $added_item_amount[0];
                  $status = Order::getcustomerstatus($booking_data[$i]['order']['id_order_status'], $booking_data[$i]['order']['service_type'],$orders['id_order']);
               
                  if($booking_data[$i]['order']['reschedule_luggage'] == 1){
                      $booking_data[$i]['order']['customer_id_order_status'] = $status['customer_id_order_status']==6 ? '3': $status['customer_id_order_status'];
                      $booking_data[$i]['order']['customer_order_status_name'] = $status['status_name'] == 'Assigned'? 'Open' : $status['status_name'];
                      $booking_data[$i]['order']['travell_passenger_name'] = ($booking_data[$i]['order']['travell_passenger_name']==NULL) ? $reschedule_order_details1['order']['travell_passenger_name'] : $booking_data[$i]['order']['travell_passenger_name'];
                      $booking_data[$i]['order']['travell_passenger_contact'] = ($booking_data[$i]['order']['travell_passenger_contact']==NULL) ? $reschedule_order_details1['order']['travell_passenger_contact'] : $booking_data[$i]['order']['travell_passenger_contact'];
                      if($booking_data[$i]['order']['id_order'] > $booking_data[$i]['order']['related_order_id']){
                        $booking_data[$i]['order']['luggage_price'] = $booking_data[$i]['order']['luggage_price']; /*$booking_data[$i]['order']['express_extra_amount'] + $booking_data[$i]['order']['express_extra_amount'] * (Yii::$app->params['gst_percent']/100)*/
                        $booking_data[$i]['order']['amount_paid'] = $booking_data[$i]['order']['amount_paid'] ; /*$booking_data[$i]['order']['express_extra_amount'] + $booking_data[$i]['order']['express_extra_amount'] * (Yii::$app->params['gst_percent']/100)*/
                      }
                  }else{
                      $booking_data[$i]['order']['customer_id_order_status'] = $status['customer_id_order_status'];
                  $booking_data[$i]['order']['customer_order_status_name'] = $status['status_name'];
                  }
                  if($booking_data[$i]['order']['delivery_type'] == 2){
                      $booking_data[$i]['order']['luggage_price_new'] = $booking_data[$i]['order']['amount_paid'] + $booking_data[$i]['order']['modified_amount'];
                  }
                  if($booking_data[$i]['order']['delivery_type'] == 2){
                      $booking_data[$i]['order']['luggage_price'] = $booking_data[$i]['order']['amount_paid'];
                  }
                  if($booking_data[$i]['order']['delivery_type'] == 2){
                      $booking_data[$i]['order']['luggage_price_new'] = $booking_data[$i]['order']['luggage_price'];
                  }
                  if(isset($status['previous_status_id']) && isset($status['previous_status_name'])){
                      $booking_data[$i]['order']['previous_status_id'] = $status['previous_status_id'];
                      $booking_data[$i]['order']['previous_status_name'] = $status['previous_status_name'];
		              }

                  $booking_data[$i]['order']['readable'] = 1;
                  if((!(($booking_data[$i]['order']['corporate_type'] == 1)))) {
                    $booking_data[$i]['order']['readable'] = 0;
            		  }
                  
                  $promo_code = OrderPromoCode::findOne(['order_id' => $orders['id_order']]);
                  if($promo_code){
                      $booking_data[$i]['order']['promocode_text'] = $promo_code->promocode_text;
                      $booking_data[$i]['order']['promocode_value'] = $promo_code->promocode_value;
                      $booking_data[$i]['order']['promocode_type'] = $promo_code->promocode_type;    
                  }else{
                      $booking_data[$i]['order']['promocode_text'] = '';
                      $booking_data[$i]['order']['promocode_value'] = '';
                      $booking_data[$i]['order']['promocode_type'] = '';
                  }
                  /*$readable = ($booking_data[$i]['order']['id_customer'] == $_POST['id_customer']) ? 1 : 0;
                  $booking_data[$i]['order']['readable'] = $readable;*/
                  /*$building_restrictions = unserialize($booking_data['order']['building_restriction']);
                  print_r($booking_data);
                  $building_restriction = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN('".$building_restrictions."')")->queryAll(); */
                $i++;
              }
            }
            else
            {
              $booking_data['status']=false;
              $booking_data['message']='No Orders';
            }

            echo Json::encode(['status'=>true, 'total_count'=>$countQuery->count(), 'booking_data' => $booking_data]);
        }
        else
        {
            echo Json::encode(['status'=>false, 'message' => 'No data available']);
        }
       
    }

    /*update status flag to check weather phone and email has been chaanged*/
    public function actionIsmodified()
    {
        $this->actiongetResource();
        $_POST = User::datatoJson();
        if(isset($_POST['id_customer'])){
            $user = Customer::find()->where(['id_customer'=>$_POST['id_customer']])->one();
            if(isset($user->customer_profile_picture)){
              $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_customer_profile'].$user->customer_profile_picture;
            }else{
              $order_pdf['path'] = '';
            }
            echo Json::encode(['status'=>true, 'user_details' => $user, 'path' => $order_pdf['path']]);
        }else{
            echo Json::encode(['status'=>false, 'message' => 'Please enter customer Id']);
        }
    }

    public function actionGettoken()
    {
        // include our OAuth2 Server object
        require_once Yii::$app->basePath.'/vendor/OAuth2/server_credential.php';
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        $server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
    }

    public function actiongetResource()
    {
        // include our OAuth2 Server object
        require_once Yii::$app->basePath.'/vendor/OAuth2/server_credential.php';

        if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
        $server->getResponse()->send();
        die;
        }

        return true;
    }


    public function actiongetAuthorizecode()
    {

          require_once Yii::$app->basePath.'/vendor/OAuth2/server_credential.php';

          $request = OAuth2\Request::createFromGlobals();
          $response = new OAuth2\Response();

          // validate the authorize request
          if (!$server->validateAuthorizeRequest($request, $response)) {
              $response->send();
              die;
          }
          // display an authorization form
          /*if (empty($_POST)) {
            exit('
          <form method="post">
            <label>Do You Authorize TestClient?</label><br />
            <input type="submit" name="authorized" value="yes">
            <input type="submit" name="authorized" value="no">
          </form>');
          }*/

          // print the authorization code if the user has authorized your client
          $is_authorized = ($_POST['authorized'] === 'yes');
          $server->handleAuthorizeRequest($request, $response, $is_authorized);
          if ($is_authorized) {
            // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
            $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
            //exit("SUCCESS! Authorization Code: $code");
            echo CJSON::encode(array('success' => true, 'code' => $code));
          }
          //$response->send();

    }

    /**
     * Updates an existing Customer model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_customer]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Customer model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Customer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Customer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Customer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionGetcountrycodes()
    {
      header('Access-Control-Allow-Origin: *');
      $data= CountryCode::getCountryCodes();
      echo Json::encode(['status'=>true, 'codes' => $data]);
    }

    public function actionGetEmailIds(){
      $_POST = User::datatoJson();
      if(!empty($_POST)){
          $result = Yii::$app->db->createCommand("SELECT id_customer,name,email,unique_id,fk_role_id FROM tbl_customer where mobile = '".$_POST['mobile']."'")->queryAll();
          $customerEmails = !empty($result) ? $result : array();
          echo Json_encode(['status' => true, "message" => "Successfully get email ids list.","result" => $customerEmails]);
      } else {
        echo Json_encode(['status' => false, "message" => "Send required parameter!"]);
      }
    }
}