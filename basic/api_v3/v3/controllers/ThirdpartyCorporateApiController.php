<?php

namespace app\api_v3\v3\controllers;
use Yii;
/* For APi-start */
use linslin\yii2\curl;
use yii\web\Response;
use yii\web\Request;
use yii\helpers\Json;
use yii\rest\ActiveController;
/* For APi-end */

use Razorpay\Api\Api;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\models\User;
use app\models\OrderPaymentDetails;
use app\models\ThirdpartyCorporate;
use app\models\AirportOfOperation;
use app\models\CityOfOperation;
use app\models\OrderItems;
use app\models\OrderSpotDetails;
use app\models\Order;
use app\models\OrderSearch;
use app\models\PickDropLocation;
use app\models\CustomerLoginForm;
use app\models\Customer;
use app\api_v3\v3\models\State;
use app\api_v3\v3\models\Pincodes;
use app\api_v3\v3\models\Zone;
use app\api_v3\v3\models\ZonePincodes;
use app\api_v3\v3\models\OutstationZonePrices;
use app\api_v3\v3\models\CityZonePrices;
use app\api_v3\v3\models\LuggageType;
use app\api_v3\v3\models\ThirdpartyCorporateAirports;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegion;
use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceCity;
use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceAirport;
use app\api_v3\v3\models\ThirdpartyCorporateOutstationAirport;
use app\api_v3\v3\models\ThirdpartyCorporateOutstationCity;
use app\api_v3\v3\models\ThirdpartyCorporateOrderMapping;
use app\api_v3\v3\models\FinserveTransactionDetails;
use app\api_v3\v3\models\OrderMetaDetails;
use app\api_v3\v3\models\OrderZoneDetails;
use OAuth2;
use app\components\SendOTP;
use yii\data\Pagination;
use yii\helpers\Html;
use yii\base\Exception;
use yii\base\Model;
class ThirdpartyCorporateApiController extends ActiveController
{
    public $modelClass = "app\models\ThirdpartyCorporate";
   
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

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin(){
        $corporateData = $this->CheckAccesstoken();
        if($corporateData['is_active'] == 1){
            Yii::$app->runAction('customer-api/login');       
        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        }
    }

    public function actionVerifyotp()
    {
        $corporateData = $this->CheckAccesstoken();
        $_POST = User::datatoJson();
        $model = new CustomerLoginForm();
        $customer_detail=$model->customerDetail();
        //print_r($customer_detail);exit;
        if(!empty($customer_detail)){
            if(empty($customer_detail['client_id'])){
                $customer_detail['client_id'] = $client['client_id']=base64_encode($customer_detail['email']);
                $customer_detail['client_secret'] = $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($customer_detail['email']);
                $client['user_id']=$customer_detail['id_customer'];
                User::addClient($client);
            }
            $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$customer_detail['fk_tbl_customer_id_country_code']."'")->queryOne();
            //print_R($CountryCode);exit;
            $customer_detail['id_country_code'] = $CountryCode['id_country_code'];
            $customer_detail['fk_tbl_customer_id_country_code'] = $CountryCode['country_code'];
            $request['customer_detail'] = $customer_detail;
            //$request['saved_address'] = $saved_address;
            $request['mobile'] = $_POST['mobile'];
            $request['otp'] = $_POST['otp'];
            SendOTP::verifyBySendOtp($request);
        }else{
            echo Json::encode(['status'=>false, 'message'=>'The service is restricted to registered users only. Please register and use. Inconvenience regretted. Thank you.','error'=>$model->geterrors()]);
        }
    }

    public function actionRegister($data)
    {
        $getMobile = Customer::find()->where(['mobile'=>$data['travell_passenger_contact']])->asArray()->one();
        if($getMobile){
            return $getMobile['id_customer'];
        }else{
            
        
        //$corporateData = $this->CheckAccesstoken();
    
       // if($corporateData['is_active'] == 1){
            $model = new Customer();
            // if (!empty($_POST)) {
            //     $model->attributes = $_POST;
                $model->fk_tbl_customer_id_country_code = 95;
                // if($model->validate())
                // {
                    // if(!empty($_FILES['customer_document']))
                    // {
                    //     $extension = explode(".", $_FILES["customer_document"]["name"]);
                    //     $rename_customer_document = "customer_document_".date('mdYHis').$extension[0].".".$extension[1];
                    //     $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_customer_document;
                    //     //print_r($path);exit;
                    //     move_uploaded_file($_FILES['customer_document']['tmp_name'],$path);
                    //     $model->document = $rename_customer_document;
                    
                    // }else{
                    $model->document = '';
                    //}
                    $model->name = isset($data['travell_passenger_name']) ? $data['travell_passenger_name'] : "";
                    $model->mobile = isset($data['travell_passenger_contact']) ? $data['travell_passenger_contact'] :"";
                    $model->gender = isset($data['gender']) ? $data['gender'] : 0 ;
                    $model->address_line_1 = isset($data['address_line_1']) ? $data['address_line_1'] : "";
                    $model->address_line_2 = isset($data['address_line_2']) ? $data['address_line_2'] : "";
                    $model->email = isset($data['email']) ? $data['email'] : "";
                    $model->landmark = isset($data['landmark']) ? $data['landmark'] : "";
                    $model->building_number = $data['building_number'];
                    $model->building_restriction = !empty($data['building_restriction']) ? serialize($data['building_restriction']) : '';
                    $model->other_comments = isset($data['other_comments']) ? $data['other_comments']:'';
                    $model->area = isset($data['area']) ? $data['area'] : "";
                    $model->id_proof_verification = 1;
                    $model->status = 1;
                    $model->date_created = date('Y-m-d H:i:s');
                    $model->pincode ='';
                    $model->mobile_number_verification=1;
                    $model->email_verification=1;
                    $model->save(false); 
                    $client['client_id']=base64_encode($data['email'].mt_rand(100000, 999999));
                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email'].mt_rand(100000, 999999));
                    $client['user_id']=$model->id_customer;
                    User::addClient($client);
                    return $model->id_customer;
        }
    
                    /* Email and SMS on Registration of customer - Start*/
                    // User::sendemail($model->email,"Verify Email Address",'verify_email_link',$model);

                    // User::sendemail($model->email,"Welcome to Carter X",'welcome_customer',$model);

                    // $country_code = Customer::getcontrycode($_POST['id_country_code']);

                    // User::sendsms($country_code.$model->mobile,"Dear Customer, Thank you for registering with us and welcome to Carter X where luggage transfer is simplified. Get Carter & Travel Smart! .".PHP_EOL."Thanks carterx.in");
                    // /* End*/

                    // echo Json::encode(['status'=>true, 'message'=>'Registration Successful']);
                /*}
                else
                {
                    echo Json::encode(['status'=>false, 'message'=>'Please upload a document']);
                }*/
                
                // }
                // else
                // {

                //     foreach ($model->getErrors() as $attribute => $errors) {
                //         foreach ($errors as $error) {
                //             $lines[$attribute] = Html::encode($error);
                //         }
                //     }
                //     echo Json::encode(['status'=>false, 'message'=>'Registration Failed','error'=>$lines]);
                // }
                
            // } else {
            //     echo Json::encode(['status'=>false, 'message'=>'Registration Failed,No Data']);
            // }
        // }else if($corporateData['is_active'] == 2){
        //     echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        // }
    }
    /**
     * Creating third-party corporates
     */
    public function actionCreateCorporate(){
        //$_POST = User::datatoJson();
        $_POST = Yii::$app->request->post();
       $model = new ThirdpartyCorporate;
        if(isset($_POST)){
            $model->attributes = $_POST;
            $model->access_token = $this->GenerateAccessToken();
            $model->created_on = date('Y-m-d H:i:s');
            if($model->validate()){  
                $model->corporate_name = $_POST['corporate_name']; 
                $model->is_active = $_POST['is_active'];
                $model->order_type = $_POST['order_type'];
                $model->bag_limit= $_POST['bag_limit'];
                $model->max_bag_weight = $_POST['max_bag_weight'];
                $model->excess_bag_weight = $_POST['excess_bag_weight'];
                $model->transfer_type = $_POST['transfer_type'];
                $model->excess_weight_enable = $_POST['excess_weight_enable'];
                $model->gst = $_POST['gst'];
                $model->is_white_labled_corporate = $_POST['is_white_labled_corporate'];
                
                $FolderExits = 'uploads/thirdparty_corporate_images';
                if (!file_exists($FolderExits)) {
                  mkdir($FolderExits, 0777);
                }
                if($_FILES['corporate_image']['name'] != NULL){
                    $def_filename = time('mdYHis').$_FILES["corporate_image"]["name"];
                    $url = $FolderExits.'/'.$def_filename;
                    $this->compress_image($_FILES['corporate_image']["tmp_name"], $url, 80);
                    $model->corporate_image = $def_filename ; 
                }
                    
                if($model->save(false)){
                    foreach($_POST['city_region_id'] as $key=>$value){
                        $model_city = new ThirdpartyCorporateCityRegion;
                        $model_city->thirdparty_corporate_id = $model->thirdparty_corporate_id;
                        $model_city->city_region_id = $value;
                        $model_city->created_on = date('Y-m-d H:i:s');
                        $model_city->save();
                    }
                    foreach($_POST['airport_id'] as $key=>$value){
                        $model_airport = new ThirdpartyCorporateAirports;
                        $model_airport->thirdparty_corporate_id = $model->thirdparty_corporate_id;
                        $model_airport->airport_id = $value;
                        $model_airport->created_on = date('Y-m-d H:i:s');
                        $model_airport->save();
                    }
                    echo Json::encode(['status'=>true,'access_token'=>$model->access_token,'message'=>"Successfully Inserted Thrid party Corporate "]);
                }
            }else{
                foreach ($model->getErrors() as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $lines[$attribute] = Html::encode($error);
                    }
                }
                echo Json::encode(['status'=>false,'message'=>"Creation of thrid party corporation is failed",'errors'=>$lines]);
            }
     
        }else{
            echo Json::encode(['status'=>false,'message'=>"Empty json data"]);
        }

    }

     /**
     * Updating third-party corporates
     */
    public function actionUpdateCorporate(){
       
        $_POST = Yii::$app->request->post();
        
        if(isset($_POST)){
            if(isset($_POST['corporate_id'])){
                $model = ThirdpartyCorporate::findOne($_POST['corporate_id']);
                $model_old = ThirdpartyCorporate::findOne($_POST['corporate_id']);
                if($model){
                    $model->corporate_name = $_POST['corporate_name']; 
                    $model->is_active = $_POST['is_active'];
                    $model->order_type = $_POST['order_type'];
                    $model->bag_limit= $_POST['bag_limit'];
                    $model->max_bag_weight = $_POST['max_bag_weight'];
                    $model->excess_bag_weight = $_POST['excess_bag_weight'];
                    $model->transfer_type = $_POST['transfer_type'];
                    $model->excess_weight_enable = $_POST['excess_weight_enable'];
                    $model->is_white_labled_corporate = $_POST['is_white_labled_corporate'];

                    $FolderExits = 'uploads/thirdparty_corporate_images';
                    if (!file_exists($FolderExits)) {
                      mkdir($FolderExits, 0777);
                    }
                    if(isset($_FILES['corporate_image']['name']) != NULL){
                        $def_filename = time('mdYHis').$_FILES["corporate_image"]["name"];
                        $url = $FolderExits.'/'.$def_filename;
                        $this->compress_image($_FILES['corporate_image']["tmp_name"], $url, 80);
                        $model->corporate_image = $def_filename ; 
                    }else{
                        $model->corporate_image = $model_old['corporate_image'];
                    }
    
                    if($model->save(false)){     
                        ThirdpartyCorporateAirports::deleteAll('thirdparty_corporate_id ='.$_POST['corporate_id']);
                        if(isset($_POST['airport_id']) && !empty($_POST['airport_id'])){
                            foreach($_POST['airport_id'] as $key=>$value){
                                $model_airport = new ThirdpartyCorporateAirports;
                                $model_airport->thirdparty_corporate_id = $_POST['corporate_id'];
                                $model_airport->airport_id = $value;
                                $model_airport->created_on = date('Y-m-d H:i:s');
                                $model_airport->save(false);
                            }
                        }

                        ThirdpartyCorporateCityRegion::deleteAll('thirdparty_corporate_id ='.$_POST['corporate_id']);
                        if(isset($_POST['city_region_id']) && !empty($_POST['city_region_id'])){
                            foreach($_POST['city_region_id'] as $key=>$value){
                                $model_city = new ThirdpartyCorporateCityRegion;
                                $model_city->thirdparty_corporate_id = $_POST['corporate_id'];
                                $model_city->city_region_id = $value;
                                $model_city->created_on = date('Y-m-d H:i:s');
                                $model_city->save(false);
                            }
                        }
                        echo Json::encode(['status'=>true,'message'=>"Successfully Updated Thrid party Corporate"]);
                    }
                }else{
                    
                    echo Json::encode(['status'=>false,'message'=>"This Thirdparty Corporate doesnot exist",'error'=>$lines]);
                }
            }else{
                echo Json::encode(['status'=>false,'message'=>"Thirdparty Corporate Id is required"]);

            }
        }else{
            echo Json::encode(['status'=>false,'message'=>"Empty json data"]);

        }

    }

    /**
     * Generating access token
     */
    public function GenerateAccessToken(){

        $date = date("Y-m-d H:i:s");
        $token = md5($date);
        return $token;
    }

    public function compress_image($source_url, $destination_url, $quality) {

        $info = getimagesize($source_url);

            if ($info['mime'] == 'image/jpeg')
                    $image = imagecreatefromjpeg($source_url);

            elseif ($info['mime'] == 'image/gif')
                    $image = imagecreatefromgif($source_url);

        elseif ($info['mime'] == 'image/png')
                    $image = imagecreatefrompng($source_url);

            imagejpeg($image, $destination_url, $quality);
        return $destination_url;
    }

    /**
     * Api for sending the airport list for particular thirdparty corporates
     */
    public function actionAirportList(){

        $corporateData = $this->CheckAccesstoken();
    
        if($corporateData['is_active'] == 1){

            $airportList = ThirdpartyCorporateAirports::find()
                        ->select(['airport_id','tbl_airport_of_operation.airport_name'])
                        ->leftJoin('tbl_airport_of_operation','airport_id = airport_name_id')
                        ->where(['thirdparty_corporate_id'=>$corporateData['thirdparty_corporate_id']])
                        ->asArray()->all();
                
            echo Json::encode(['status'=>true,'airport_list'=>$airportList]);

        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        } 
    }

    /**
     * Api for sending the City list for particular thirdparty corporates
     */

    public function actionCityList(){

        $corporateData = $this->CheckAccesstoken();
  
        if($corporateData['is_active'] == 1){
            $citylist = ThirdpartyCorporateCityRegion::find()
                        ->select(['city_region_id','tbl_city_of_operation.region_name'])
                        ->leftJoin('tbl_city_of_operation','city_region_id = id')
                        ->where(['thirdparty_corporate_id'=>$corporateData['thirdparty_corporate_id']])
                        ->asArray()->all();

            echo Json::encode(['status'=>true,'city_list'=>$citylist]);

        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        }
        
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

    public function getCorporate($id){        
        $corporate = ThirdpartyCorporate::find()
                        ->where(['fk_corporate_id'=>$id])
                        ->asArray()->one();
        if(!$corporate){
            echo Json::encode(['status'=>false,'message'=>"Corporate Doesnot exist"]); exit;

        }else{
            return $corporate;
        }
        
    }

    public function actionPorterCalculation(){
        // $this->actiongetResource();
        $_POST = User::datatoJson();
        if(isset($_POST['corporate_id']) && !empty($_POST['corporate_id'])){
            $corporateData = $this->getCorporate($_POST['corporate_id']);
            $status = "porter";
            $corporate_type = Yii::$app->Common->getCorporateType($_POST['corporate_id']);
            if($corporate_type == 4 || $corporate_type == 5){
                $price = $this->getPriceBagData($corporateData,$_POST);
            }else{
                $price = $this->getPriceDataPorter($corporateData,$status);
                $excess = $price['excess_weight_price']*($corporateData['gst']/100);
                $price['excess_weight_price'] = $price['excess_weight_price'] + $excess;
            }
            echo Json::encode(['status'=>true,'message'=>"Successfull",'price_details'=>$price]);
        }else{
            echo Json::encode(['status'=>false,'message'=>"Corporate_id is required"]);
        }
    }
    /**
     * Api For pricing list 
     */
    public function actionCalculation(){ 
        header('Access-Control-Allow-Origin: *');
        $corporateData = $this->CheckAccesstoken();
        $_POST = User::datatoJson(); 
        $status = "thirdparty";
        $price = $this->getPriceData($corporateData,$status);
        $conveyance_charges_list = $this->getConveyanceChargesList($corporateData);
        $corporate_charges_list = $this->getCorporateChargesList($corporateData);
        $corporate_airport_charges_list = $this->getCorporateAirportChargesList($corporateData);
        $corporate_bag_price = $this->corporatepriceInfo($corporateData,$status);
        echo Json::encode(['status'=>true,'message'=>"Successfull",'price_details'=>$price, 'corporate_price_details' => $corporate_bag_price, 'conveyance_charge' => $conveyance_charges_list,'corporate_charges' => $corporate_charges_list,'corporate_airport_charges' => $corporate_airport_charges_list]);
    }

    public function getConveyanceChargesList($corporateData){
        if(empty($corporateData)){
            return false; 
        } else {
            $result = Yii::$app->db->createCommand("select co.region_name,IF(tclpc.bag_price, tclpc.bag_price, 0) as bag_price from tbl_thirdparty_corporate_city_region tccr left join tbl_thirdparty_corporate_luggage_price_city tclpc on tclpc.thirdparty_corporate_city_id = tccr.thirdparty_corporate_city_id left join tbl_city_of_operation co on co.id = tccr.city_region_id where tccr.thirdparty_corporate_id = '".$corporateData['thirdparty_corporate_id']."' and co.region_name is not null;")->queryAll();// and  tclpc.thirdparty_corporate_city_id != ''
            if(!empty($result)){
                return $this->calculateConveyancePrice($corporateData,$result);
            } else {
                return false;
            }
        }
    }

    public function getPriceData($corporateData,$status){
        $_POST = User::datatoJson();
        if($corporateData['is_active'] == 1){
            $transfer = isset($_POST['transfer_type']) ? $_POST['transfer_type'] : $_POST['transfer_type'];
            $order = isset($_POST['order_type']) ? $_POST['order_type'] : $corporateData['order_type'];
            $corporate_id = $corporateData['thirdparty_corporate_id'];
            //$this->validateData($_POST,$status="calculation");
            $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : "";
            $airport_name = isset($_POST['airport_name']) ? $_POST['airport_name'] : "";
            $no_bags = isset($_POST['no_of_units']) ? $_POST['no_of_units'] : "";
            $bag_weight = isset($_POST['bag_weight']) ? $_POST['bag_weight'] : 0;
            $excess_purchased = isset($_POST['excess_weight_purchased']) ? strtolower($_POST['excess_weight_purchased']) : "";
            $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : "";
            $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : "";
            $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : "";
            //$pin = $this->Checkpincodeavailability($pincode,$airport_name,$service_type);
            $o_type = 0;
            if(!isset($_POST['admin_status'])){
                $pin = Yii::$app->Common->Checkpincodeavailability($pincode,$airport_name,$service_type,$city_name);
                if($pin == 1){
                    $o_type = 1;
                }else{
                    $o_type =2;
                } 
            }else{
                $o_type = 1;
                if($order == 2){
                    $pin = Yii::$app->Common->Checkpincodeavailability($pincode,$airport_name,$service_type,$city_name);
                    if($pin == 1){
                        echo Json::encode(['status'=>false,'message'=>'It seems like local order, check with other pincodes']);
                    }else{
                        $o_type =2;
                    }
                }
            }
            $order_type = $this->getOrderType($order,$o_type);
            $transfer_type = $this->getTransferType($transfer);
            if($order_type == 2){ 
                // $state_id = Pincodes::find()
                //         ->select(['stateId'])
                //         ->where(['pincode'=>$pincode])->asArray()
                //         ->one();
                //getNearestPincode
                $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                // if($state_id){
                //     $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : $state_id['stateId'];
                // }else{
                //     echo Json::encode(['status'=>false,'message'=>'Pincode not found for this outstation order']);exit;
                // }
            }
            $this->checkBagLimit($corporateData['bag_limit'],$no_bags);    

            if($excess_purchased == "yes"){
                $excess_weight = isset($_POST['excess_weight']) ? $_POST['excess_weight'] : "";
                if($status == "porter"){
                    $excess_weight_price = $this->getExcessWeightPurchased($bag_weight,$corporateData['excess_bag_weight']);
                }else{
                    $excess_weight_price = $this->getExcessWeightPurchased($excess_weight,$corporateData['excess_bag_weight']);
                }
            }else{
                $excess_weight_price = $this->getExcessWeightPrice($bag_weight,$corporateData,$status);
            }
            $price = $this->getBagPrice($corporateData,$order_type,$city_name,$airport_name,$state_name,$pincode,$transfer_type);
            $excessPrice = isset($excess_weight_price['price']) ? $excess_weight_price['price'] : 0;
            $total_price = $this->getPrice($price,$no_bags,$excessPrice,$corporateData['gst']);
            return $total_price;
        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);exit;
        }
    }

    public function getPriceDataPorter($corporateData,$status){
        $_POST = User::datatoJson();
        if($corporateData['is_active'] == 1){
            $transfer = isset($_POST['transfer_type']) ? $_POST['transfer_type'] : $_POST['transfer_type'];
            $order = isset($_POST['order_type']) ? $_POST['order_type'] : '';
            $corporate_id = $corporateData['thirdparty_corporate_id'];
            //$this->validateData($_POST,$status="calculation");
            $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : "";
            $airport_name = isset($_POST['airport_name']) ? $_POST['airport_name'] : "";
            $no_bags = isset($_POST['no_of_units']) ? $_POST['no_of_units'] : "";
            $bag_weight = isset($_POST['bag_weight']) ? $_POST['bag_weight'] : 0;
            $excess_purchased = isset($_POST['excess_weight_purchased']) ? strtolower($_POST['excess_weight_purchased']) : "";
            $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : "";
            $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : "";
            $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : "";
            //$pin = $this->Checkpincodeavailability($pincode,$airport_name,$service_type);
            $o_type = $corporateData['order_type'];
            // if(!isset($_POST['admin_status'])){
            //     $pin = Yii::$app->Common->Checkpincodeavailability($pincode,$airport_name,$service_type);
            //     if($pin == 1){
            //         $o_type = 1;
            //     }else{
            //         $o_type =2;
            //     }
            // }else{
            //     $o_type = 1;
            //     if($order == 2){
            //         $pin = Yii::$app->Common->Checkpincodeavailability($pincode,$airport_name,$service_type);
            //         if($pin == 1){
            //             echo Json::encode(['status'=>false,'message'=>'It seems like local order, check with other pincodes']);
            //         }else{
            //             $o_type =2;
            //         }
            //     }
            // }
            $order_type = $this->getOrderType($o_type,$order);
            $transfer_type = $this->getTransferType($transfer);
            // if($order_type == 2){
            //     $state_id = Pincodes::find()
            //             ->select(['stateId'])
            //             ->where(['pincode'=>$pincode])->asArray()
            //             ->one();
            //     if($state_id){
            //         $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : $state_id['stateId'];
            //     }else{
            //         echo Json::encode(['status'=>false,'message'=>'Pincode not found for this outstation order']);exit;
            //     }
            // }
            $this->checkBagLimit($corporateData['bag_limit'],$no_bags);    

            if($excess_purchased == "yes"){
                $excess_weight = isset($_POST['excess_weight']) ? $_POST['excess_weight'] : "";
                if($status == "porter"){
                    $excess_weight_price = $this->getExcessWeightPurchased($bag_weight,$corporateData['excess_bag_weight']);
                }else{
                    $excess_weight_price = $this->getExcessWeightPurchased($excess_weight,$corporateData['excess_bag_weight']);
                }
            }else{
                $excess_weight_price = $this->getExcessWeightPrice($bag_weight,$corporateData,$status);
            }
            $price = $this->getBagPrice($corporateData,$order_type,$city_name,$airport_name,$state_name,$pincode,$transfer_type);
            $total_price = $this->getPrice($price,$no_bags,$excess_weight_price['price'],$corporateData['gst']);
            return $total_price;
        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);exit;
        }
    }

    public function getPriceBagData($corporateData,$postData){
        if($corporateData['is_active'] == 1){
            $order_details = Order::findOne($_POST['order_id']);
            $transfer = isset($postData['transfer_type']) ? $postData['transfer_type'] : "";
            $order = isset($postData['order_type']) ? $postData['order_type'] : "";
            $corporate_id = $corporateData['thirdparty_corporate_id'];
            $city_name = isset($postData['city_name']) ? $postData['city_name'] : "";
            $airport_name = isset($postData['airport_name']) ? $postData['airport_name'] : "";
            $no_bags = isset($postData['no_of_units']) ? $postData['no_of_units'] : "";
            $bag_weight = isset($postData['bag_weight']) ? $postData['bag_weight'] : 0;
            $state_name = isset($postData['state_name']) ? $postData['state_name'] : "";   
            $service_Type = $order_details->service_type;
            if($order == 2 && $transfer == 1 && $service_Type == 2){
                $droppincode = OrderMetaDetails::find()->where(['orderId'=>$_POST['order_id']])->one();
                $pincode = $droppincode->dropPincode;
            }else{
                $pincode = isset($postData['pincode']) ? $postData['pincode'] : "";
            }
            
            $pin = Yii::$app->Common->Checkpincodeavailability($pincode,$airport_name,$service_Type,$city_name);
            $order_type = $this->getOrderType($order, $order);
            $transfer_type = $this->getTransferType($transfer);
            $this->checkBagLimit($corporateData['bag_limit'],$no_bags);
               
            // echo "<pre>";print_r($order_details);exit;
            if(isset($postData['items_order']) && !empty($postData['items_order'])){
                $items_details = [];
                $excess_price = 0;
                $excess_weight = 0;
                $receive_amount = 0;
                $total_deleted_bprice = 0;
                $current_baseprice = 0;
                $price = $this->getBagPrice($corporateData,$order_type,$city_name,$airport_name,$state_name,$pincode,$transfer_type);
                foreach($postData['items_order'] as $key=>$value){
                    if($value['new_luggage'] == 1){
                        $items_detail['new_luggage'] = 1;
                        $items_detail['deleted_status'] = 0;
                        $receive_amount += $price['bag_price'];
                        $current_baseprice += $price['bag_price'];
                    }else if($value['deleted_status'] == 1){
                        $items_detail['new_luggage'] = 0;
                        $items_detail['deleted_status'] = 1;
                        $total_deleted_bprice += $price['bag_price'];
                        $current_baseprice += $price['bag_price'];
                    }else{
                        $items_detail['new_luggage'] = 0;
                        $items_detail['deleted_status'] = 0;
                        $current_baseprice += $price['bag_price'];
                    }
                    $items_detail['id_order_item'] = $value['id_order_item'];
                    $items_detail['item_price'] = $price['bag_price'];
                    $items_detail['bag_weight'] = $value['bag_weight'];
                    if($value['deleted_status'] == 0){
                        if($value['bag_weight'] > $corporateData['max_bag_weight']){
                            $excessBagPrice = $this->getExcessWeightPrice($value['bag_weight'],$corporateData,$status="thirdparty");
                            $excess_price = $excess_price +  $excessBagPrice['price'];
                            $excess_weight = $excess_weight + $excessBagPrice['weight'];
                        }
                    }
                    array_push($items_details,$items_detail);
                }
                // echo "<pre>";print_r($bag_added);print_r($bag_deleted);print_r($remining);exit;
                $price_details['items'] = $items_details;
                $price_details['luggage_price'] = $price['outstation_price'] + $excess_price + ($price['bag_price'] * $no_bags);
                $price_details['gst_price'] = $price_details['luggage_price'] * ($corporateData['gst']/100);
                $price_details['price_with_gst'] = $price_details['gst_price'] + $price_details['luggage_price'];
                $gst_price = $excess_price * ($corporateData['gst']/100);
                $price_details['outstation_charge'] = $price['outstation_price'];
                $price_details['excess_weight_price'] = $excess_price + $gst_price; 
                $price_details['excess_weight'] = $excess_weight;
                $price_details['receive_amount'] = 0;
                $price_details['refund_amount'] = 0;

                $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()->select(['outstationCharge'])->where(['orderId'=>$_POST['order_id']])->one();
                $total_bag_price = $current_baseprice + $excess_price;
                $total_bp = $total_bag_price - $total_deleted_bprice;
                $service_tax = $total_bp * ($corporateData['gst']/100);
                $current_bag_tax = $service_tax; 
                
                $total_order_price = ($total_bp + $gst_price + $outstation_charge['outstationCharge'] + $order_details->service_tax_amount  + $excess_price);

                $price_item = \app\models\OrderItems::find()->select(['item_price'])->where(['fk_tbl_order_items_id_order'=>$_POST['order_id']])->all();
                $item_price = 0;
                if($price_item){
                    foreach ($price_item as $key => $value) {
                       $item_price += $value->item_price;
                    }
                }
                // $total_bp = $refund_amount - $receive_amount;
                // $total_order_price = ($price_details['luggage_price'] + $gst_price + $order_details->service_tax_amount);
                
                if($order_details->amount_paid > $total_order_price){
                    $price_details['receive_amount'] = 0;
                    $price_details['refund_amount'] = $item_price - $total_bp;
                }else{
                    // if($receive_amount){
                    //     $total_bps = ($receive_amount - $refund_amount);
                    //     $bag_excess = $total_bps + $excess_price;
                    // }else{
                    //     $total_bps = $excess_price - $refund_amount;
                    //     $bag_excess = $total_bps;
                    // }
                    $new_total = $total_bp - $item_price;
                    // $total_bps = $receive_amount - $refund_amount;
                    $bag_gst = $new_total * ($corporateData['gst']/100);
                    $price_details['receive_amount'] = round(floatval($new_total + $bag_gst),2);
                    $price_details['refund_amount'] = 0;
                }
                return $price_details;  
            }else{
                echo Json::encode(['status'=>false,'message'=>"Itmes order is required and cannot be empty"]);exit;
            }
        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);exit;
        }
    }
    /**
     * Api for Third party booking
     */
    public function actionBooking(){
        header('Access-Control-Allow-Origin: *');
        $corporateData = $this->CheckAccesstoken();
        $_POST = User::datatoJson();
        $payment = false;
        $this->orderBooking($corporateData,$_POST,$payment);
    
    }

    public function actionPorterEditBooking(){
        $this->actiongetResource();
        $_POST = User::datatoJson();
        if(isset($_POST['corporate_id']) && !empty($_POST['corporate_id'])){
            $corporateData = $this->getCorporate($_POST['corporate_id']);
            if($corporateData['is_active'] == 1){
                $this->checkOrders($_POST['order_id'],$corporateData['thirdparty_corporate_id']);
                $model = Order::findOne($_POST['order_id']);
                $model->no_of_units = $_POST['no_of_units'];
                $model->modified_amount = $_POST['modified_amount'];
                if($_POST['modified_amount'] > 0 || $_POST['modified_amount'] < 0){
                    $model->order_modified =1;
                    $model->porter_modified_datetime = date('Y-m-d H:i:s');
                }else{
                    $model->order_modified =0;
                }
                $model->excess_bag_amount = $model->excess_bag_amount + $_POST['excess_bag_amount'];
                $model->extra_weight_purched = $model->extra_weight_purched +  $_POST['excess_weight'];
                $model->save(false);
                $this->updateOrderItems($_POST);
                echo Json::encode(['status'=>true,'message'=>"Order Updated Sucessfully"]);
    
            }else{
                echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
            }
            
        }else{
            echo Json::encode(['status'=>false,'message'=>"Corporate_id is required"]);
        }
        
    }

    public function actionEditBooking(){ 
        $corporateData = $this->CheckAccesstoken();
        $_POST = User::datatoJson();
        $payment = false;
        if($corporateData['is_active'] == 1){
            $this->checkOrders($_POST['order_id'],$corporateData['thirdparty_corporate_id']);
            $this->editOrderBooking($corporateData,$_POST,$payment);
            echo Json::encode(['status'=>true,'message'=>"Order Updated Sucessfully"]);

        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        }

    }

    public function checkOrders($orderid,$corporate_id){
        $order = ThirdpartyCorporateOrderMapping::find()
                ->where(['order_id'=>$orderid])
                ->andWhere(['thirdparty_corporate_id'=>$corporate_id])
                ->one();
        if(!$order){
            echo Json::encode(['status'=>false,'message'=>"Order Not Found"]);exit;   
        }else{
            return 1;
        }

    }
    
    public function editOrderBooking($corporateData,$postData,$payment){
        $this->checkBagLimit($corporateData['bag_limit'],$postData['no_of_units']);
        $model = Order::findOne($postData['order_id']);
            if($model){
                $model->travell_passenger_name = isset($postData['travell_passenger_name']) ? $postData['travell_passenger_name'] : 0;
                if(!empty($postData['country_code']))
                {
                    $model->fk_tbl_order_id_country_code = $postData['country_code'];
                }
                $model->travell_passenger_contact = isset($postData['travell_passenger_contact']) ? $postData['travell_passenger_contact'] : null;
                $model->flight_verification = isset($postData['flight_verification']) ? $postData['flight_verification'] : 0;
                $model->flight_number = isset($postData['flight_number']) ? $postData['flight_number'] : null;
                $model->someone_else_document_verification = isset($postData['someone_else_document_verification']) ? $postData['someone_else_document_verification'] : 0;
                $model->date_modified = date('Y-m-d H:i:s');
                $model->departure_time = isset($postData['departure_time']) ? $postData['departure_time'] : null ;
                $model->departure_date = isset($postData['departure_date']) ? $postData['departure_date'] : null ;
                $model->arrival_time = isset($postData['arrival_time']) ? $postData['arrival_time'] : null ;
                $model->arrival_date = isset($postData['arrival_date']) ? $postData['arrival_date'] : null ;
                $model->meet_time_gate = isset($postData['meet_time_gate']) ? $postData['meet_time_gate'] : null ;
                //$model->corporate_price = isset($_POST['corporate_price']) ? $_POST['corporate_price'] : 0 ;
                $model->no_of_units = $postData['no_of_units'];   
                $model->service_tax_amount = $postData['gst_amount'];
                $model->luggage_price = $postData['total_luggage_price'];
                if(!empty($postData['location']))
                {
                    $model->location = $postData['location'] ;
                }  

                $model->save(false);
                // if(!empty($_FILES['flight_ticket']['name']))
                // {
                //     $up = $this->actionFileupload('flight_ticket');
                // }
                // if(!empty($_FILES['someone_else_document']['name']))
                // {
                //     $up = $this->actionFileupload('someone_else_document');
                // }
                $this->updateOrderItems($postData);
                $order_model=Order::findOne($postData['order_id']);
                $model_orderspot = OrderSpotDetails::find()->where(['fk_tbl_order_spot_details_id_order'=>$postData['order_id']])->one();
                $model_orderspot->person_name = $postData['location_contact_name'];
                $model_orderspot->person_mobile_number = $postData['location_contact_number'];
                $model_orderspot->landmark = $postData['landmark'];
                $model_orderspot->building_number = $postData['building_number'];
                $model_orderspot->area = $postData['area'];


                if(isset($postData['pincode']) && !empty($postData['pincode']))
                {
                        $pincode_id=PickDropLocation::find()->select('id_pick_drop_location')->where(['pincode'=>$postData['pincode']])->one();
                        //echo "<pre>";print_r($pincode_id);exit;
                        if($pincode_id){
                        $pincode_id=$pincode_id['id_pick_drop_location']; 

                        $sector = PickDropLocation::findOne(['pincode' => $postData['pincode']]);
                        }else{
                            $pincode_id='';
                            $sector = '';
                        }
                        //echo "<pre>";print_r($order_model->fk_tbl_order_id_pick_drop_location);exit;
                        $order_model->fk_tbl_order_id_pick_drop_location=$pincode_id;
                        $order_model->sector_name = ($sector) ? $sector->sector : '';
                        $order_model->save(false); 
                        
                } 
                $model_orderspot->pincode = $postData['pincode'];
                $model_orderspot->address_line_1 = $postData['address_line_1'];
                $model_orderspot->address_line_2 = $postData['address_line_2'];
                $model_orderspot->hotel_name = isset($postData['hotel_name']) ? $postData['hotel_name'] : null ;
                $model_orderspot->mall_name = isset($postData['mall_name']) ? $postData['mall_name'] : null ;
                $model_orderspot->store_name = isset($postData['store_name']) ? $postData['store_name'] : null ;
                $model_orderspot->business_name = isset($postData['business_name']) ? $postData['business_name'] : null ;
                $model_orderspot->business_contact_number = isset($postData['business_contact_number']) ? $postData['business_contact_number'] : null ;
                $model_orderspot->fk_tbl_order_spot_details_id_contact_person_hotel=isset($postData['contact_person_hotel']) ? $postData['contact_person_hotel'] : null ;
                $model_orderspot->hotel_booking_verification = isset($postData['hotel_booking_verification']) ? $postData['hotel_booking_verification'] : 0 ;
                $model_orderspot->invoice_verification = isset($postData['invoice_verification']) ? $postData['invoice_verification'] : 0 ;

                if(isset($postData['building_restriction']) && $postData['building_restriction'] != '')
                {
                    $model_orderspot->building_restriction = serialize($postData['building_restriction']);
                }
                
                $model_orderspot->other_comments = isset($postData['other_comments']) ? $postData['other_comments'] : null ;
                $model_orderspot->save();
                $flight_number = " ".$postData['flight_number'];
                $traveller_number = $postData['travell_passenger_contact'];
                $customer_number = $postData['travell_passenger_contact'];
                $location_contact = $postData['location_contact_number'];
                $customer_name = $postData['travell_passenger_name'];
                if($model->travel_person == 1){
                    if($model->corporate_id == 0){
                    // User::sendsms($traveller_number,"Hello, the Flexible Fields for  Order #".$model->order_number. " Reference " .$corp_ref_text." placed by ".$customer_name." was edited/updated. The ".$gate_meet_text." for the order is ".date('h:i A', strtotime($model->meet_time_gate)).". All changes made will reflect under 'Manage Orders'. Log in to your account or call customer care at ".PHP_EOL."+919110635588"." for support. ".PHP_EOL."Thanks carterx.in");
                    }else{
                    // User::sendsms($traveller_number,"Hello, the Flexible Fields for  Order #".$model->order_number. " Reference " .$corp_ref_text." placed by ".$customer_name." was edited/updated. All changes made will reflect under 'Manage Orders'. Log in to your account or call customer care at ".PHP_EOL."+919110635588"." for support. ".PHP_EOL."Thanks carterx.in");
                    }
                }  
                //return 1;  
            }else{
                echo Json::encode(['status'=>false,'message'=>"Order Not Found"]);exit;   

            }  
    }

    public function updateOrderItems($order_items){
        foreach ($order_items['items_order'] as $key => $value) {
            if(!empty($value['id_order_item'])){
              $model=OrderItems::find()->where(['id_order_item'=> $value['id_order_item']])->one();
              if(!empty($model))
              {
                $model->bag_type=isset($value['bag_type']) ? $value['bag_type'] : "";
                $model->item_weight=$value['bag_weight'];
                $model->item_price=$value['price'];
               ($value['isDeleted']=="true")? $model->deleted_status=1 : $model->deleted_status=0; 
               ($value['isNew']=="true")? $model->new_luggage=1 : $model->new_luggage=0; 
                $model->save(false);
              }
              
            }else{
               $model= new OrderItems();
               $model->bag_type=isset($value['bag_type']) ? $value['bag_type'] : "";
               $model->fk_tbl_order_items_id_order=$order_items['order_id'];
               $model->item_price=$value['price'];
               $model->item_weight=$value['bag_weight'];
              ($value['isDeleted']=="true")? $model->deleted_status=1 : $model->deleted_status=0; 
               ($value['isNew']=="true")? $model->new_luggage=1 : $model->new_luggage=0; 
               $model->save(false);

            } 
          }
          return 1;
    }
    public function orderBooking($corporateData,$postData,$payment){
        if ($corporateData['is_active'] == 1) {
            $cargo_status = Yii::$app->Common->checkCargoStatus($corporateData['fk_corporate_id']);
            $this->validateData($postData, $status = "booking");
            $corporate_id = $corporateData['thirdparty_corporate_id'];
            $fk_corporate_id = $corporateData['fk_corporate_id'];
            $transfer = isset($postData['transfer_type']) ? $postData['transfer_type'] : "";
            $order = isset($postData['order_type']) ? $postData['order_type'] : $corporateData['order_type'];
            $airport_id = isset($postData['airport_name']) ? $postData['airport_name'] : "";
            $city_name = isset($postData['city_name']) ? $postData['city_name'] : "";
            $no_bags = isset($postData['no_of_units']) ? $postData['no_of_units'] : "";
            $bag_weight = isset($postData['bag_weight']) ? $postData['bag_weight'] : "";
            $excess_purchased = isset($postData['excess_weight_purchased']) ? strtolower($postData['excess_weight_purchased']) : "";
            $state_name = isset($postData['state_name']) ? $postData['state_name'] : "";
            $pincode = isset($postData['pincode']) ? $postData['pincode'] : "";
            $service_type = isset($postData['service_type']) ? $postData['service_type'] : "";
            $second_pincode = isset($postData['second_pincode']) ? $postData['second_pincode'] : "";
            //$airport_id = $this->getAirportID($airport_name);
            $this->checkAirport($corporate_id, $airport_id);
            $o_type = 0;
            if (!isset($_POST['admin_status'])) {
                if (empty($second_pincode)) {
                    $pin = Yii::$app->Common->Checkpincodeavailability($pincode, $airport_id, $service_type, $city_name);
                    if ($pin == 1) {
                        $o_type = 1;
                    } else {
                        $o_type = 2;
                    }
                } else {
                    $pin = Yii::$app->Common->Checkpincodeordertype($pincode, $second_pincode, $airport_id, $service_type, $city_name);
                    if ($pin == 1) {
                        $o_type = 1;
                    } else {
                        $o_type = 2;
                    }
                }
            } else {
                $o_type = 1;
                if ($order == 2) {
                    $pin = Yii::$app->Common->Checkpincodeavailability($pincode, $airport_id, $service_type, $city_name);
                    if ($pin == 1) {
                        echo Json::encode(['status' => false, 'message' => 'It seems like local order, check with other pincodes']);
                    } else {
                        $o_type = 2;
                    }
                }
            }
            $order_type = $this->getOrderType($order, $o_type);
            $transfer_type = $this->getTransferType($transfer);
            $this->checkBagLimit($corporateData['bag_limit'], $no_bags);
            $status = "thirdparty";
            if ($excess_purchased == "yes") {
                $excess_weight = isset($postData['excess_weight']) ? $postData['excess_weight'] : "";
                $excess_weight_price = $this->getExcessWeightPurchased($excess_weight, $corporateData['excess_bag_weight']);
            } else {
                $excess_weight_price = $this->getExcessWeightPrice($bag_weight, $corporateData, $status);
            }
            $price = $this->getBagPrice($corporateData, $order_type, $city_name, $airport_id, $state_name, $pincode, $transfer_type);
            $excessPrice = isset($excess_weight_price['price']) ? $excess_weight_price['price'] : 0;
            $total_price = $this->getPrice($price, $no_bags, $excessPrice, $corporateData['gst']);
            //print_r($total_price);exit;
            $client_price = $postData['total_luggage_price'];
    
            //if($total_price['price_with_gst'] == $client_price ){
            $order_id = $this->insertIntoOrder($postData, $corporate_id, $airport_id, $order_type, $transfer_type, $fk_corporate_id);
            $this->mappingOrder($order_id, $corporate_id, $postData);
            if ($payment == true) {
                $api = new Api('rzp_test_VSvN3uILIxekzY', 'Flj35MJPZTJZ0WiTBlynY14k');
                $this->makePayment($api, $postData['luggage_price'], $order_id, $postData['travel_email'], $postData['travel_passenger_contact']);
            }
    
            // $customer_number = $new_order_details['order']['c_country_code'].$new_order_details['order']['customer_mobile'];
            // $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
            // $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
            // $customers  = Order::getcustomername($new_order_details['order']['travell_passenger_contact']);
            // $customer_name = ($customers) ? $customers->name : '';
            $new_order_details = Order::findOne($order_id);
            $new_order_details1 = Order::getorderdetails($order_id);
            // $customer_number = $new_order_details1['order']['c_country_code'].$new_order_details1['order']['travell_passenger_contact'];
            $code = ($postData['country_code']) ? $postData['country_code'] : '91';
            $customer_number = $code . $postData['travell_passenger_contact'];
            $flight_number = " " . $postData['flight_number'];
            // $traveller_number = $new_order_details1['order']['c_country_code'].$new_order_details1['order']['travell_passenger_contact'];
            $traveller_number = $code . $postData['travell_passenger_contact'];
            //$location_contact = $postData['location_contact_number'];
            $customer_name = $postData['travell_passenger_name'];
    
            $order_date = ($new_order_details1['order']['order_date']) ? date("Y-m-d", strtotime($new_order_details1['order']['order_date'])) : '';
            $date_created = ($new_order_details1['order']['date_created']) ? date("Y-m-d", strtotime($new_order_details1['order']['date_created'])) : '';
    
            $slot_start_time = date('h:i a', strtotime($new_order_details1['order']['slot_start_time']));
            $slot_end_time = date('h:i a', strtotime($new_order_details1['order']['slot_end_time']));
            $slot_scehdule = $slot_start_time . ' To ' . $slot_end_time;
            //$location_contact = $postData['location_contact_number'];
            //print_r($new_order_details1['order']['slot_start_time']);exit;
            $customer_name = $postData['travell_passenger_name'];
            $msg_to_airport = "Dear Customer, Welcome to CarterX! Login to www.carterx.in with your registered mobile number to track your order placed by " . $customer_name . " under 'Manage Orders'. For all service related queries contact our customer support on +91-6366835588  -CarterX";
            $msg_to_airport = urlencode($msg_to_airport);
            if ($new_order_details->order_transfer == 1) {
                $sms_content = Yii::$app->Common->generateCityTransferSms($new_order_details->id_order, 'OrderConfirmation', '');
            } else {
                if ($new_order_details->service_type == 1) { //to airport
                    // $msg_to_airport = "Dear Customer, Your Order #".$new_order_details->order_number."   placed by ".$customer_name."  for ".$flight_number." - ".$no_bags." bags via CarterX is confirmed. For all service related queries  contact our customer support on +91-9110635588.  -CarterX";
                    //Some One else
                    //if($new_order_details->travel_person==1){
    
                    // User::sendsms($traveller_number,$msg_to_airport );
                    //}
                    //User::sendsms($customer_number,"Dear Customer, your Order #".$new_order_details->order_number." for ".$no_bags." bags is confirmed. Web Check in is Mandatory. Security Declaration is MANDATORY, please keep the same filled before we arrive to pick the order.Luggage/package/items will have to be identified by passenger for delivery before entering the airport terminal. Meet CarterX personnel before entering the terminal is MANDATORY. -CarterX");
                    $service = ($new_order_details->order_transfer == 1) ? 'To City' : 'To Airport';
    
                    $bookingCustomer = 'Dear Customer, your Order #' . $new_order_details->order_number . ' ' . $service . '  placed on ' . $date_created . ' by ' . $customer_name . ' is confirmed for service on ' . $order_date . ' between ' . $slot_scehdule . '. Thanks carterx.in';
    
                    User::sendsms($traveller_number, $msg_to_airport);
                    //}
                    User::sendsms($customer_number, $bookingCustomer);
    
                    //Location Contact
                    // if($order_spot_details->assigned_person == 1){
                    //      User::sendsms($location_contact,$msg_to_airport );
                    // }
                } elseif ($new_order_details->service_type == 2) {
    
                    // $msg_from_airport = "Dear Customer, your Order #".$new_order_details->order_number." for ".$flight_number." placed by ".$customer_name." is confirmed. Local deliveries will be made on the same day for bags received before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX";
                    //Some One else
                    //if($new_order_details->travel_person == 1){
                    //     User::sendsms($traveller_number,$msg_to_airport);
                    // //}
                    // User::sendsms($customer_number,"Dear Customer, your Order #".$new_order_details->order_number." for ".$no_bags." bags is confirmed. Luggage/package/items will have to be identified by travelling passenger on arrival at the airport terminal. Meet with CarterX personnel at the terminal is MANDATORY.-CarterX");
    
                    $service = ($new_order_details->order_transfer == 1) ? 'From City' : 'From Airport';
    
                    $bookingCustomer = 'Dear Customer, your Order #' . $new_order_details->order_number . ' ' . $service . '  placed on ' . $date_created . ' by ' . $customer_name . ' is confirmed for service on ' . $order_date . ' between ' . $slot_scehdule . '. Thanks carterx.in';
    
                    // $msg_from_airport = "Dear Customer, your Order #".$new_order_details->order_number." for ".$flight_number." placed by ".$customer_name." is confirmed. Local deliveries will be made on the same day for bags received before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX";
                    //Some One else
                    //if($new_order_details->travel_person == 1){
                    User::sendsms($traveller_number, $msg_to_airport);
                    //}
                    User::sendsms($customer_number, $bookingCustomer);
    
                    //Location Contact
                    // if($order_spot_details->assigned_person == 1){
                    //     User::sendsms($location_contact,$msg_from_airport);
                    // }
                }
            }
            if ($fk_corporate_id == 43) {
                $flyportersms = "Dear Customer, thank you for booking the AirAsia's Flyporter Service with CarterX. Please read the information document enclosed carefully for a better travel experience: *Departure:* https://flyporter.carterporter.in/depature-details *Arrivals:* https://flyporter.carterporter.in/arrival-details. Travel safe and stress free from the get go.";
                User::sendsms($traveller_number, $flyportersms);
            }
            $model1['order_details'] = Order::getorderdetails($order_id);
            //confirmation mail
            $attachment_det = Yii::$app->Common->genarateOrderConfirmationThirdpartyCorporatePdf($model1, 'order_confirmation_corporate_pdf_template');
            // if($transfer_type == 2){
            if ($cargo_status) {
                $attachment_det['second_path'] = Yii::$app->params['document_root'] . 'basic/web/' . 'cargo_security.pdf';
            } else {
                $attachment_det['second_path'] = Yii::$app->params['document_root'] . 'basic/web/' . 'passenger_security.pdf';
            }
            // }
            User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'], "CarterX Confirmed Order #" . $new_order_details->order_number . "", 'order_confirmation', $model1, $attachment_det);
            //invoice mail
            $Invoice_attachment_det = Yii::$app->Common->genarateOrderConfirmationPdf($model1, 'order_payments_pdf_template');
            User::sendemailexpressattachment($model1['order_details']['order']['customer_email'], "Payment Done", 'after_any_kind_of_payment_made', $model1, $Invoice_attachment_det);
    
            echo Json::encode(['status' => true, 'message' => 'Booking is done', 'order_number' => $new_order_details->order_number, 'order_id' => $order_id]);
    
            // }else{
            //     echo Json::encode(['status'=>false,'message'=>'Pricing mismatch']);
            // }
    
        } else if ($corporateData['is_active'] == 2) {
            echo Json::encode(['status' => false, 'message' => "This Third party Corporate is Disabled"]);
        }
    }
    
    public function makePayment($api,$luggage_price,$order_id,$travel_email,$travel_contact){
        $time = strtotime('now');
        $endTime = strtotime(date("H:i", strtotime('+480 minutes', $time)));
        $post_amount = round($luggage_price) * 100;
        $string_amount = "$post_amount";
        $amount_payment_link = str_replace(".", "", $string_amount);

        $new_order_details = Order::getorderdetails($order_id);
        if(($new_order_details['order']['corporate_type'] == 3) || ($new_order_details['order']['corporate_type'] == 4) || ($new_order_details['order']['corporate_type'] == 5)){
            $res = Yii::$app->Common->getSetBank($new_order_details['order']['corporate_id']);
            if(empty($res)){
                $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $travel_contact)));
            } else {
                $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $travel_contact),'reminder_enable'=>true, "options" => $res['options']));
            }
        } else {
            $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $travel_contact)));
        }
        // $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $travel_contact)));
    
        $expire_date = date("Y-m-d h:i:s",$payment_link_details['expire_by']); 
        $amount = number_format($payment_link_details->amount_paid / 100, 2);

        $finserve_payment_details = new FinserveTransactionDetails();
        $finserve_payment_details->invoice_id = $payment_link_details->id;
        $finserve_payment_details->customer_id = $payment_link_details->customer_id;
        $finserve_payment_details->order_id = $order_id;
        $finserve_payment_details->payment_id = $payment_link_details->payment_id;

        $finserve_payment_details->transaction_status = $payment_link_details->status;
        $finserve_payment_details->expiry_date = $expire_date;
        $finserve_payment_details->paid_date  = $payment_link_details->paid_at;
        $finserve_payment_details->amount_paid = $amount;
        $finserve_payment_details->total_order_amount = $amount;

        $finserve_payment_details->payment_type_status  = 1;
        $finserve_payment_details->short_url = $payment_link_details->short_url;

        $finserve_payment_details->description = $payment_link_details->description;
        $finserve_payment_details->order_type  = 'create-thirdparty-corporate-kiosk';
        //$finserve_payment_details->notes = $payment_link_details->id;
        $finserve_payment_details->created_by = $role_id;
        if($finserve_payment_details->save()){
            $finserve_payment_details->finserve_number = 'FP'.date('mdYHis').$finserve_payment_details->id_finserve;
            $finserve_payment_details->save(false);
        }
        // if(!empty($_FILES['invoice']['name'])){
        //     //$up = $employee_model->actionFileupload('invoice',$order_id);
        //         $extension = explode(".", $_FILES['MallInvoices']['name']['invoice']);
        //         $rename_invoice_docname = "invoice_".date('mdYHis').".".$extension[1];
        //         $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_invoice_docname;
        //         move_uploaded_file($_FILES['MallInvoices']['tmp_name']['invoice'],$path);
        //         if(isset($orderId))
        //         {
        //             $invoice = ['invoice'=>$rename_invoice_docname,'fk_tbl_mall_invoices_id_order'=>$orderId];
        //             Yii::$app->db->createCommand()->insert('tbl_mall_invoices',$invoice)->execute();

        //             Yii::$app->db->createCommand('UPDATE tbl_order_spot_details set invoice_verification = 1 where fk_tbl_order_spot_details_id_order='.$orderId)->execute();
        //         }
        //        // echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_invoice_docname]);        
        // }
                    
    }     
    public function insertIntoOrder($data,$corporate_id,$airport_id,$order_type,$transfer_type,$fk_corporate_id){
        $customer = $this->actionRegister($data);
        $excess_purchased = isset($data['excess_weight_purchased']) ? strtolower($data['excess_weight_purchased']) : "";    
        $model = new Order();
        $model->airport_service = !empty($data['pick_drop_point']) ? $data['pick_drop_point'] : 0;
        $model->corporate_id = $fk_corporate_id;
        $model->fk_tbl_order_id_customer = isset($data['customer_id']) ? $data['customer_id'] :$customer ;
        $model->corporate_type = Yii::$app->Common->getCorporateType($fk_corporate_id);
        $model->fk_tbl_airport_of_operation_airport_name_id = $airport_id;
        $model->location = isset($data['location'])?$data['location']:""; //destination
        $model->sector = isset($data['sector']) ? $data['sector']: "north";
        $model->weight = ($excess_purchased == "yes") ? 1 : 0; //extra weight purchased
        $model->service_type = isset($data['service_type']) ? $data['service_type'] : "";
        $model->order_date = date('Y-m-d',strtotime($_POST['order_date']));     
        //$_POST['Order']['order_date'];
        $model->no_of_units = $data['no_of_units'];
        // if($data['service_type'] == 1){
        //     $model->fk_tbl_order_id_slot = 1;
        // }else{
        //     $model->fk_tbl_order_id_slot = 4;
        // }
        $model->fk_tbl_order_id_slot = $data['pickup_slot'];
        $model->service_tax_amount = $data['gst_amount'];
        $model->luggage_price = $data['luggage_price'] + $data['gst_amount'] - $data['outstation_charge'] - $data['excess_bag_amount'] ;
        $model->amount_paid = $data['total_luggage_price'];
        $model->outstation_extra_amount = $data['outstation_charge'];
        $model->extra_weight_purched = isset($data['excess_weight']) ? $data['excess_weight'] : "";
        $model->excess_bag_amount = $data['excess_bag_amount'];
        $model->travel_person = 1;
        $model->travell_passenger_name = $data['travell_passenger_name'];
        $model->delivery_type = $order_type;
        $model->order_transfer = $transfer_type;
        $model->city_id = isset($data['city_name']) ? $data['city_name'] : "";
        // $model->fk_tbl_order_id_country_code = $data['country_code'];
        $model->travell_passenger_contact = $data['travell_passenger_contact'];
        $model->dservice_type = isset($data['dservice_type'])?$data['dservice_type']:7; //delivery service type   //7 normal delivery              
        $model->flight_number = $data['flight_number'];
        $model->meet_time_gate = isset($data['meet_time_gate']) ? date("H:i", strtotime($data['meet_time_gate'])) : ""; 
        $model->insurance_price = 0; 
        $model->created_by_name = $data['travell_passenger_name'];
        $model->payment_method = "Online Payment";
        $model->someone_else_document_verification = 1;
        $model->flight_verification = 1;
        $model->delivery_datetime = isset($data['delivery_datetime']) ? date('Y-m-d H:i:s',strtotime($data['delivery_datetime'])) : "";
        $model->delivery_time_status = isset($data['delivery_time_status']) ? $data['delivery_time_status'] : "";
        $model->pnr_number = isset($data['pnr_number']) ? $data['pnr_number'] : NULL;
        $model->pickup_dropoff_point = isset($data['pick_drop_address']) ? $data['pick_drop_address'] : NULL;
        $model->terminal_type = isset($data['terminal_type']) ? $data['terminal_type'] : NULL;
        $model->order_type_str = isset($data['order_type_str']) ? $data['order_type_str'] : NULL;
        // if($data['service_type'] == 1){
        //     $model->pickup_pincode = $data['pincode'];
        // } else if($data['service_type'] == 2){
        //     $model->drop_pincode = $data['pincode'];
        // }

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
            $model->fk_tbl_order_id_slot = isset($data['pickup_slot']) ? $data['pickup_slot'] : 1;
            if($data['service_type'] == 1){
                $model->pickup_pincode = $data['pincode_first'];
                $model->drop_pincode = $data['pincode_second'];
            } else if($data['service_type'] == 2){
                $model->pickup_pincode = $data['pincode_second'];
                $model->drop_pincode = $data['pincode_first'];
            }
        } else if(($data['pick_drop_point'] == 2)){// Doorstep point
            $model->fk_tbl_order_id_slot = isset($data['pickup_slot']) ? $data['pickup_slot'] : 1;
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

        $customer_id = Yii::$app->Common->getCorporateCustomerId($data['customer_id'],$data['airline_id']);
        if(!empty($customer_id)){
            $model->corporate_customer_id = $customer_id;
        }

        if(isset($data['pincode']) && !empty($data['pincode']))
        {
            $pincode_id=PickDropLocation::find()->select('id_pick_drop_location')->where(['pincode'=>$data['pincode']])->one();
            if($pincode_id){
            $pincode_id=$pincode_id['id_pick_drop_location']; 
            }else{
                $pincode_id='';
            }
            $model->fk_tbl_order_id_pick_drop_location=$pincode_id;
            
            $sector = PickDropLocation::findOne(['pincode' => $data['pincode']]);

            $model->sector_name = ($sector) ? $sector->sector : '';                 
        }                
        if($data['service_type'] ==1){
            $model->departure_date = isset($data['departure_date']) ? $data['departure_date'] :null;
            $model->departure_time = isset($data['departure_time']) ? date("H:i", strtotime($data['departure_time'])) :null;
            $model->fk_tbl_order_status_id_order_status = 3;
            $model->order_status = 'Open';
        }else{
            $model->arrival_date = isset($data['arrival_date']) ? $data['arrival_date'] :null;
            $model->arrival_time = isset($data['arrival_time']) ? date("H:i", strtotime($data['arrival_time'])) :null;
            $model->fk_tbl_order_status_id_order_status = 2;
            $model->order_status = 'Confirmed';
        }

        $model->date_created = date('Y-m-d H:i:s');
        // $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        // $model->created_by = $role_id;
        // $model->created_by_name = Yii::$app->user->identity->name;
        if($model->save(false)){
            //echo json_encode(['status'=>true]);
            $model = Order::findOne($model->id_order);
            $model->order_number = 'ON'.date('mdYHis').$model->id_order;
            $model->save(false);

            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
            /*order history end*/
            $this->updateordertotal($model->id_order, $data['luggage_price'], $data['gst_amount'], $model->insurance_price);                
            /*$luggage_det = LuggageType::find()->where(['corporate_id'=>$_POST['Order']['corporate_id']])->one();*/
            
            if(!empty($_POST['items_order'])){
                foreach ($_POST['items_order'] as $key => $items) {
                    $order_items = new OrderItems();
                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                    $order_items->fk_tbl_order_items_id_luggage_type = 2;
                    $order_items->fk_tbl_order_items_id_luggage_type_old=2;
                    $order_items->item_price = $items['price'];
                    $order_items->bag_type = isset($items['bag_type']) ? $items['bag_type'] : "";   
                    //}
                    $order_items->save();
                }
                
            }

            if($order_type == 2){
                $OrderZoneDetails = new OrderZoneDetails;
                $OrderZoneDetails->orderId = $model->id_order;
                $OrderZoneDetails->outstationZoneId = isset($_POST['outstation_id']) ? $_POST['outstation_id'] : 0;
                $OrderZoneDetails->cityZoneId = isset($_POST['city_name']) ? $_POST['city_name'] : 0;
                $OrderZoneDetails->stateId = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                $OrderZoneDetails->extraKilometer = isset($_POST['extr_kms']) ? $_POST['extr_kms'] : 0; 
                $OrderZoneDetails->taxAmount = isset($_POST['luggageGST']) ? $_POST['luggageGST'] : 0;
                $OrderZoneDetails->outstationCharge = isset($_POST['outstation_charge']) ? $_POST['outstation_charge'] : 0;
                $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');
                $OrderZoneDetails->save(false);
            }

            if($transfer_type == 2){
                $order_spot_details = new OrderSpotDetails();
                $order_spot_details->fk_tbl_order_spot_details_id_order = $model->id_order;
                $order_spot_details->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($data['pick_drop_spots_type'])?$data['pick_drop_spots_type']:1;
                $order_spot_details->person_name = isset($data['location_contact_name']) ? $data['location_contact_name'] : "";
                $order_spot_details->person_mobile_number = isset($data['location_contact_number']) ? $data['location_contact_number'] : "";
                $order_spot_details->mall_name = isset($data['mall_name']) ? $data['mall_name'] : "" ;
                $order_spot_details->store_name = isset($data['store_name']) ? $data['store_name']:"" ;
                $order_spot_details->business_name = isset($data['business_name']) ? $data['business_name'] : "" ;
                $order_spot_details->business_contact_number = isset($data['business_contact_number']) ? $data['business_contact_number'] : "" ;
                if(isset($data['pick_drop_spots_type']) && $data['pick_drop_spots_type'] == 2){
                    $order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = $data['contact_person_hotel'];
                    $order_spot_details->hotel_name = $data['hotel_name'];
                }
                $order_spot_details->assigned_person = 0;
                $order_spot_details->address_line_1 = isset($data['address_line_1'])?$data['address_line_1']:null;
                $order_spot_details->address_line_2 = isset($data['address_line_2'])?$data['address_line_2']:null;
                $order_spot_details->area = isset($data['area'])?$data['area']:null;
                $order_spot_details->pincode = isset($data['pincode'])?$data['pincode']:null;
                $order_spot_details->landmark = isset($data['landmark'])?$data['landmark']:null;
                $order_spot_details->building_number = isset($data['building_number']) ? $data['building_number']:null;
                $order_spot_details->other_comments = isset($data['other_comments'])?$data['other_comments']:null;
                $order_spot_details->building_restriction = isset($data['building_restriction']) ? serialize($data['building_restriction']) : null;
                $order_spot_details->hotel_booking_verification  = 1;
                $order_spot_details->invoice_verification = 1;
                $order_spot_details->save(false);
            } else if(($transfer_type == 1) && ($data['service_type'] == 1)){
                $orderMetaDetails = new OrderMetaDetails;
                $orderMetaDetails->orderId = $model->id_order;
                $orderMetaDetails->pickupPersonAddressLine1 = isset($data['address_line_1'])?$data['address_line_1']:null;
                $orderMetaDetails->pickupPersonAddressLine2 = isset($data['address_line_2'])?$data['address_line_2']:null;
                $orderMetaDetails->pickupArea = isset($data['area'])?$data['area']:null;
                $orderMetaDetails->pickupPincode = isset($data['pincode'])?$data['pincode']:null;
                
                $orderMetaDetails->dropPersonAddressLine1 = isset($data['second_address_line_1'])?$data['second_address_line_1']:null;
                $orderMetaDetails->dropPersonAddressLine2 = isset($data['second_address_line_2'])?$data['second_address_line_2']:null;
                $orderMetaDetails->droparea = isset($data['second_area'])?$data['second_area']:null;
                $orderMetaDetails->dropPincode = isset($data['second_pincode'])?$data['second_pincode']:null;

                $orderMetaDetails->status = 'Active';
                $orderMetaDetails->stateId = 0;
                $orderMetaDetails->createdOn = date('Y-m-d H:i:s'); 
                $orderMetaDetails->save(false);

            } else if(($transfer_type == 1) && ($data['service_type'] == 2)){
                $orderMetaDetails = new OrderMetaDetails;
                $orderMetaDetails->orderId = $model->id_order;
                $orderMetaDetails->dropPersonAddressLine1 = isset($data['address_line_1'])?$data['address_line_1']:null;
                $orderMetaDetails->dropPersonAddressLine2 = isset($data['address_line_2'])?$data['address_line_2']:null;
                $orderMetaDetails->droparea = isset($data['area'])?$data['area']:null;
                $orderMetaDetails->dropPincode = isset($data['pincode'])?$data['pincode']:null;
                
                $orderMetaDetails->pickupPersonAddressLine1 = isset($data['second_address_line_1'])?$data['second_address_line_1']:null;
                $orderMetaDetails->pickupPersonAddressLine2 = isset($data['second_address_line_2'])?$data['second_address_line_2']:null;
                $orderMetaDetails->pickupArea = isset($data['second_area'])?$data['second_area']:null;
                $orderMetaDetails->pickupPincode = isset($data['second_pincode'])?$data['second_pincode']:null;

                $orderMetaDetails->status = 'Active';
                $orderMetaDetails->stateId = 0;
                $orderMetaDetails->createdOn = date('Y-m-d H:i:s'); 
                $orderMetaDetails->save(false);
            }
            
            // storing in order payment details
            $order_payment_details = new OrderPaymentDetails();
            $order_payment_details->id_order = $model->id_order;
            $order_payment_details->payment_type = 'Online Payment';
            $order_payment_details->id_employee = 0;
            $order_payment_details->payment_status = 'Success';
            $order_payment_details->amount_paid = $data['total_luggage_price'];
            $order_payment_details->value_payment_mode = 'Order Amount';
            $order_payment_details->date_created= date('Y-m-d H:i:s');
            $order_payment_details->date_modified= date('Y-m-d H:i:s');
            $order_payment_details->save(false);

            return $model->id_order;

        } 
    } 
    public function validateData($data,$status){
        if(!empty($data)){
            $o_date = date('Y-m-d',strtotime($data['order_date']));
            $to_date = date('Y-m-d');
            if($o_date < $to_date){
                echo Json::encode(['status'=>false,'message'=>"Please select proper date"]);exit;
            }
            if(!isset($data['order_date']) || empty($data['order_date'])){
                echo Json::encode(['status'=>false,'message'=>"Order Date is required"]);exit;
            }elseif (!isset($data['no_of_units']) || empty($data['no_of_units'])) {
                echo Json::encode(['status'=>false,'message'=>"Number of bags is required"]);exit;
            }elseif (!isset($data['service_type']) || empty($data['service_type'])) {
                echo Json::encode(['status'=>false,'message'=>"Service type is required"]);exit;
            }elseif (!isset($data['pincode']) || empty($data['pincode'])) {
                echo Json::encode(['status'=>false,'message'=>"Pincode is required"]);exit;
            }elseif (!isset($data['travell_passenger_name']) || empty($data['travell_passenger_name'])) {
                echo Json::encode(['status'=>false,'message'=>"travel passenger name is required"]);exit;
            }elseif (!isset($data['travell_passenger_contact']) || empty($data['travell_passenger_contact'])) {
                echo Json::encode(['status'=>false,'message'=>"travel passenger contact is required"]);exit;
            }elseif(!isset($data['airport_name']) || empty($data['airport_name'])){
                echo Json::encode(['status'=>false,'message'=>"Airport is required"]);exit;
            }elseif(!isset($data['city_name']) || empty($data['city_name'])){
                echo Json::encode(['status'=>false,'message'=>"City is required"]);exit;
            }else{
                return 1;
            }
        }else{
            echo Json::encode(['status'=>false,'message'=>"Data is empty"]);exit;
        }
    }
    /**
     * Function to Get the order type
     */
    public function getOrderType($order,$pin){
        if(!empty($order)){
            if($order==3 || ($order == $pin)){
                return $pin;
            }elseif($order != 3 && ($order != $pin)){
                echo Json::encode(['status'=>false,'message'=>'Thirdparty Corporate is not allowed for this type of order']);exit;
            }
        }else{
            echo Json::encode(['status'=>false,'message'=>'Order type is required']);exit;

        } 
    }

    /**
     * Function to Get the Transfer type
     */
    public function getTransferType($type){
        if(empty($type)){
            echo Json::encode(['status'=>false,'message'=>'Transfer Type is required']);exit;
        }else if($type == 1){ //city
            return 1;
        }else if($type == 2){//airport
            return 2;
        }else{
            echo Json::encode(['status'=>false,'message'=>"Invalid transfer type"]);exit;
        }
    }

    /**
     * Function to Get the city id 
     */
    public function getCityID($name){

        if(!empty($name)){
            // $id = CityOfOperation::find()
            //             ->select(['id'])
            //             ->where(['LIKE','region_name',$name])
            //             ->one();
            return $name;
        }else{
            echo Json::encode(['status'=>false,'message'=>'City name is required']);exit;
        }

    }

    /**
     * Function to Get the Airport id 
     */
    public function getAirportID($name){

        if(!empty($name)){
            // $id = AirportOfOperation::find()
            //             ->select(['airport_name_id'])
            //             ->where(['LIKE','airport_name',$name])
            //             ->one();
            return $name;    
        }else{
            echo Json::encode(['status'=>false,'message'=>'Airport is required']);exit;
        }
    }

    /**
     * Function to chcek whether the given city is allowed
     */
    public function checkCity($t_id,$c_id){

        $check_city = ThirdpartyCorporateCityRegion::find()
                            ->select(['thirdparty_corporate_city_id'])
                            ->where(['thirdparty_corporate_id'=>$t_id])
                            ->andWhere(['city_region_id'=>$c_id])
                            ->one();
        if(!$check_city){
            echo Json::encode(['status'=>false,'message'=>'Third party Corporate is not allowed for this city']);exit;
        }else{
            return $check_city;
        }                
    }

    /**
     * Function to chcek whether the given arport is allowed
     */
    public function checkAirport($t_id,$a_id){
        $check_airport = ThirdpartyCorporateAirports::find()
                        ->select(['thirdparty_corporate_airport_id'])
                        ->where(['thirdparty_corporate_id'=>$t_id])
                        ->andWhere(['airport_id'=>$a_id])
                        ->one();
       if(!$check_airport){
            echo Json::encode(['status'=>false,'message'=>'Third party Corporate is not allowed for this airport']);exit;
        }else{   
             return $check_airport;
        }    
    }
     
    /**
     * Function to chcek whether the bag limit is allowed
     */
    public function checkBagLimit($bag_limit,$bags) {
        if(empty($bags)){
            echo Json::encode(['status'=>false,'message'=>'Number of bags is required']);exit;
        }else if($bags > $bag_limit){
            echo Json::encode(['status'=>false,'message'=>'Third party Corporate bag limit is exceeded']);exit;
        }else{
            return true;
        }
    }

    /**
     * Function to get the excess wieght price if the excess_weight_purcchased 
     */
    public function getExcessWeightPurchased($excess_weight,$weight_price){
        //if($status == "thirdparty"){
            if(empty($excess_weight)){
                echo Json::encode(['status'=>false,'message'=>'Purchased Excess Bag Weight is required']);exit;
            }else{
                $excess = $excess_weight;
                // $excess = ceil($excess_weight);
                $excess_price['price'] = $excess * $weight_price;
                return $excess_price;
            }
    }

     /**
     * Function to get the excess wieght price 
     */
    public function getExcessWeightPrice($bag_weight,$corporateData,$status) {
        // if(empty($bag_weight)){
        //     echo Json::encode(['status'=>false,'message'=>'Bag Weight is required']);exit;
        // }else 
        if($status == "thirdparty"){    
            if($bag_weight > $corporateData['max_bag_weight']){
                if($corporateData['excess_weight_enable'] == 1){
                    $excess_price['weight'] = $bag_weight - $corporateData['max_bag_weight'];
                    $excess = ceil($excess_price['weight']);
                    $excess_price['price'] = $excess * $corporateData['excess_bag_weight'];
                    return $excess_price;
                }else{
                    echo Json::encode(['status'=>false,'message'=>'Third party Corporate bag weight has exceeded the limit']);exit;
                }
            }else{
                return 0;
            }
        }else{
            if($corporateData['excess_weight_enable'] == 1){
                $excess = ceil($bag_weight);
                $excess_price = $excess * $corporateData['excess_bag_weight'];
                return $excess_price;
            }else{
                echo Json::encode(['status'=>false,'message'=>'Third party Corporate bag weight has exceeded the limit']);exit;
            }
        }
    }

     /**
     * Function for to get the total price of the bags     
     * */
    public function getPrice($price,$no_bags,$excess_weight_price,$gst){

        $total_price = ($price['bag_price'] * $no_bags) + $excess_weight_price + $price['outstation_price'];
        
        for($i=1;$i<=$no_bags;$i++){
            $netprice['items']['bag'.$i] = $price['bag_price'];
        }
        $netprice['excess_weight_price'] = $excess_weight_price;
        $netprice['outstation_charge'] = $price['outstation_price'];
        if($gst > 0) {
            $netprice['gst_price'] = str_replace( ',', '',(number_format(($total_price * ($gst/ 100)),2)));
            $netprice['total_luggage_price'] = str_replace( ',', '',(number_format($total_price,2)));
            $netprice['price_with_gst'] = str_replace( ',', '',(number_format(($total_price + $netprice['gst_price']),2)));
        }else{
            $netprice['gst_price'] = 0;
            $netprice['total_luggage_price'] = $total_price;
            $netprice['price_with_gst'] = $total_price + $netprice['gst_price'];
        }
        return $netprice;
    }

     /**
     * Function to get the state id 
     */
    public function getState($airport_id,$city_id,$state_name){
        if(empty($state_name)){
            echo Json::encode(['status'=>false,'message'=>'State Name is required']);exit;    
        }else{
            $state_id = State::find()
                        ->select(['idState'])
                        ->where(['city_id'=>$city_id])
                        ->andWhere(['airport_id'=>$airport_id])
                        ->andWhere(['idState'=>$state_name])
                        ->one();
            if(!empty($state_id)){
                return $state_id;
            }else{
                echo Json::encode(['status'=>false,'message'=>'State Name not found']);exit;    
            }
        }
    }

    public function getPincode($pincode,$state_id){
        if(empty($pincode)){
            echo Json::encode(['status'=>false,'message'=>'Pincode is required']);exit;    
        }else{
            $pincodeId = Pincodes::find()
                        ->select(['idPincode'])
                        ->where(['stateId'=>$state_id])
                        ->andWhere(['pincode'=>$pincode])
                        ->one();
            if(!empty($pincodeId)){
                return $pincodeId;
            }else{
                $distance_array = [];
                $response_array = [];
                $pincode_details = \app\api_v3\v3\models\Pincodes::find()->where(['stateId' => $state_id])->all();
                //print_r($pincode_details);
                if($pincode_details){
                   foreach ($pincode_details as $key1 => $value) {
                       $distance = $this->Get_nearest_pincode_id($value->pincode, $pincode, 'K');
                       
                       $distance_array[$value->idPincode] = $distance;
                       
                   }
                   $remove_empty_array = array_filter($distance_array);
                   //print_r($remove_empty_array);
                   if(!empty($remove_empty_array)){
                       $min = array_keys($remove_empty_array, min($remove_empty_array));
                       $km = min($remove_empty_array);
                       $pincode_id = $min[0];
                       $response_array['pincode_id'] = $min[0];
                       $response_array['km'] = $km;
                       
                       return $min[0];

                   }else{
                       return '';
                   }
               }    
            }
        }
    }

    public function Get_nearest_pincode_id($addressFrom, $addressTo, $unit = '') {
       // Google API key 
       $apiKey = 'AIzaSyD9d0O6GwKmtXDbKtmWFIV2nhXrSmIOvik';
       
       $api = file_get_contents("https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=".$addressFrom."&destinations=".$addressTo."&key=".$apiKey);
       $data = json_decode($api);
       
       $unit = strtoupper($unit);
       if($data->rows[0]->elements[0]->status == 'OK'){
           if($unit == "K"){
               $distance = ((int)$data->rows[0]->elements[0]->distance->value / 1000);
               return $distance;
           }else{
               //return round($miles, 2).' miles';
           }
       }else{
           return false;
       }
    }

    public function getZoneIds($state_id,$pincode_id){

        $id = ZonePincodes::find()
                ->select(['zoneId'])
                ->where(['pincodeId'=>$pincode_id,'stateId'=>$state_id])
                ->all();
        
        return $id;
    }

    public function updateordertotal($id_order, $luggage_price, $gst_amount, $insurance_price)
    {

        $order_total_data = [ 
                                [
                                    'fk_tbl_order_total_id_order'=>$id_order,
                                    'title'=>'Sub Order Amount',
                                    'price'=>$luggage_price,
                                    'code'=>'sub_order_amount',
                                ],
                                [
                                    'fk_tbl_order_total_id_order'=>$id_order,
                                    'title'=>'GST Amount',
                                    'price'=>$gst_amount,
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

    public function getBagPrice($corporateData,$order_type,$city_id,$airport_id,$state_name,$pincode,$transfer_type) {
       
        if($corporateData['order_type'] == 3 || $order_type == $corporateData['order_type']){
            if($order_type == 1){ //local orders
                if($corporateData['transfer_type'] == 3 || $transfer_type == $corporateData['transfer_type']){
                    if($transfer_type == 1){ // city tranfer

                        //$city_id = $this->getCityID($city_name);
                        $this->checkCity($corporateData['thirdparty_corporate_id'],$city_id);

                        $city = ThirdpartyCorporateLuggagePriceCity::find()
                        ->select(['bag_price'])
                        ->leftJoin('tbl_thirdparty_corporate_city_region c','tbl_thirdparty_corporate_luggage_price_city.thirdparty_corporate_city_id = c.thirdparty_corporate_city_id')
                        ->where(['c.thirdparty_corporate_id'=>$corporateData['thirdparty_corporate_id']])
                        ->andWhere(['c.city_region_id'=>$city_id])
                        ->one();

                        $price['bag_price'] = $city['bag_price'];

                    }else{ //airport transfer
                        // $airport_id = $this->getAirportId($airport_name);
                        $this->checkAirport($corporateData['thirdparty_corporate_id'],$airport_id);
                        $airport = ThirdpartyCorporateLuggagePriceAirport::find()
                                    ->select(['bag_price'])
                                    ->leftJoin('tbl_thirdparty_corporate_airports a','tbl_thirdparty_corporate_luggage_price_airport.thirdparty_corporate_airport_id = a.thirdparty_corporate_airport_id')
                                    ->where(['a.thirdparty_corporate_id'=>$corporateData['thirdparty_corporate_id']])
                                    ->andWhere(['a.airport_id'=>$airport_id])
                                    ->one();

                        $price['bag_price'] = $airport['bag_price'] ;
                    }
                    $price['outstation_price']=0;
                    return $price;

                }else{
                     echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this transfer type"]);exit;
                }
            }else{ //outstation orders

               // $airport_id = $this->getAirportId($airport_name);
               // $city_id = $this->getCityID($city_name);

                if($corporateData['transfer_type'] == 3 || $transfer_type == $corporateData['transfer_type']){
                   
                    //$state_id = $this->getState($airport_id,$city_id,$state_name);
                    $state_id = $state_name;
                    $pincode_id = $this->getPincode($pincode,$state_id);
                    // echo "<pre>";print_r($pincode_id);exit;
                    $zoneid = $this->getZoneIds($state_id,$pincode_id); 
                    // print_r($zoneid);exit;
                    if($transfer_type == 1){ 
                        $thirdparty_city = $this->checkCity($corporateData['thirdparty_corporate_id'],$city_id);
                        // city tranfer
                       $city_outstation = CityZonePrices::find()
                                            ->select(['idCity'])
                                            ->where(['serviceCityId'=>$city_id])
                                            ->andWhere(['zoneId'=>$zoneid])
                                            ->one();
                        // print_r($city_outstation);exit;
                        $city_charge = ThirdpartyCorporateOutstationCity::find()
                                    ->select(['city_charge', 'city_base_price'])
                                    ->where(['third_party_corporate_city_region_id'=>$thirdparty_city])
                                    ->andWhere(['outstation_city_zone_price_id'=>$city_outstation])
                                    ->one();

                        $city = ThirdpartyCorporateLuggagePriceCity::find()
                                ->select(['bag_price'])
                                ->leftJoin('tbl_thirdparty_corporate_city_region c','tbl_thirdparty_corporate_luggage_price_city.thirdparty_corporate_city_id = c.thirdparty_corporate_city_id')
                                ->where(['c.thirdparty_corporate_id'=>$corporateData['thirdparty_corporate_id']])
                                ->andWhere(['c.city_region_id'=>$city_id])
                                ->one();

                        $price['outstation_price'] = $city_charge['city_charge'];
                        $price['bag_price']= $city_charge['city_base_price'];                 
                                
                    }else{ //airport
                        $thirdparty_airport = $this->checkAirport($corporateData['thirdparty_corporate_id'],$airport_id);

                        $airport_outstation = OutstationZonePrices::find()
                                    ->select(['idOutstation'])
                                    ->where(['airportId'=>$airport_id])
                                    ->andWhere(['zoneId'=>$zoneid])
                                    ->one();
                                   
                        $outstation_Charge = ThirdpartyCorporateOutstationAirport::find()
                                    ->select(['outstation_charge', 'airport_base_price'])
                                    ->where(['thirdparty_corporate_airport_id'=>$thirdparty_airport])     
                                    ->andWhere(['outstation_airport_zone_price_id'=>$airport_outstation])
                                    ->one();
                                   
                        $airport = ThirdpartyCorporateLuggagePriceAirport::find()
                                    ->select(['bag_price'])
                                    ->leftJoin('tbl_thirdparty_corporate_airports a','tbl_thirdparty_corporate_luggage_price_airport.thirdparty_corporate_airport_id = a.thirdparty_corporate_airport_id')
                                    ->where(['a.thirdparty_corporate_id'=>$corporateData['thirdparty_corporate_id']])
                                    ->andWhere(['a.airport_id'=>$airport_id])
                                    ->one();
                     
                        $price['outstation_price'] = $outstation_Charge['outstation_charge'];
                        $price['bag_price'] = $outstation_Charge['airport_base_price'];
                        
                    }
                    return $price;
                }else{
                    echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this transfer type"]);exit;
               }
            } 
        }else{
               echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this order type"]);exit;

            }
    }


    public function getNearestPincode($pincode_id, $state_id){ 
            // Get nearest pincode
            $distance_array = [];
            $response_array = [];
            $pincode_details = \app\api_v3\v3\models\Pincodes::find()->where(['stateId' => $state_id])->all();
            //print_r($pincode_details);
               if($pincode_details){
                   foreach ($pincode_details as $key1 => $value) {
                       $distance = $this->Get_nearest_pincode_id($value->pincode, $pincodes, 'K');
                       
                       $distance_array[$value->idPincode] = $distance;
                       
                   }
                   $remove_empty_array = array_filter($distance_array);
                   //print_r($remove_empty_array);
                   if(!empty($remove_empty_array)){
                       $min = array_keys($remove_empty_array, min($remove_empty_array));
                       $km = min($remove_empty_array);
                       $pincode_id = $min[0];
                       $response_array['pincode_id'] = $min[0];
                       $response_array['km'] = $km;
                       

                   }else{
                       $response_array['pincode_id'] = '';
                       $response_array['km'] = '';
                   }
               }else{
                   $response_array['pincode_id'] = '';
                   $response_array['km'] = '';
               } 
                $pincodeId = $response_array['pincode_id']; 
        
    }

    public function mappingOrder($order_id,$corporate_id,$data){

        $model_order_thirdparty = new ThirdpartyCorporateOrderMapping();
        $model_order_thirdparty->thirdparty_corporate_id = $corporate_id;
        $model_order_thirdparty->order_id = $order_id;
        $model_order_thirdparty->email = isset($data['email']) ? $data['email'] : "" ;
        $model_order_thirdparty->big_reward_point = isset($data['big_reward_point']) ? $data['big_reward_point'] : "";
        if(isset($data['other_details']) && !empty($data['other_details'])){
            $other_details = Json::encode($data['other_details']);
            $model_order_thirdparty->post_params = $other_details;
        }
        $model_order_thirdparty->created_on = date('Y-m-d H:i:s');;
        if($model_order_thirdparty->save(false)){
            return 1;
        }else{
            return 0;
        }
    }

    public function actionOrders(){
        $corporateData = $this->CheckAccesstoken();
        if($corporateData['is_active'] == 1){
            $orderids = $this->getOrderIds($corporateData['thirdparty_corporate_id']);
            $query = Order::find()->where(['id_order'=>$orderids])->orderby('id_order DESC')->asArray();
            $countQuery = clone $query;
            $pages = new Pagination(['totalCount' => $countQuery->count()]);
            $orders = $query->offset($pages->offset)
                ->limit(10)
                ->all();
           // print_r($orders);exit;
            echo Json::encode(['status'=>true,'message'=>"Success",'Order Details'=>$orders]);

        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        }
    }

    public function actionOrderDetails(){
        $corporateData = $this->CheckAccesstoken();
        if($corporateData['is_active'] == 1){
            $_POST = User::datatoJson();
            $model= new Order;
            $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : "";
            $this->checkOrder($order_id,$corporateData['thirdparty_corporate_id']);
            $order_details = Order::getorderdetails($order_id);
            $order_details['order']['airport_name'] = $model->getAirportName($order_details['order']['airport_id']);
            //print_r($order_details);exit;
            if($corporateData['is_white_labled_corporate'] == 1){
                $order_details['order']['other_details'] = ThirdpartyCorporateOrderMapping::find()->select(['email','big_reward_point','post_params'])->where(['order_id'=>$_POST['order_id']])->asArray()->one();
                $order_details['order']['other_details']['post_params'] = Json::decode($order_details['order']['other_details']['post_params']);
            }else{
                $order_details['order']['other_details'] = ThirdpartyCorporateOrderMapping::find()->select(['email'])->where(['order_id'=>$_POST['order_id']])->asArray()->one();
            }
            echo Json::encode(['status'=>true,'message'=>"Success",'Order_Detail'=>$order_details['order']]);

        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);
        }
    }

    public function checkOrder($id,$corporate_id){
        if(!empty($id)){
            $data = ThirdpartyCorporateOrderMapping::find()
                        ->where(['thirdparty_corporate_id'=>$corporate_id])
                        ->andWhere(['order_id'=>$id])
                        ->one();
            if($data){
                return 1;
            }else{
                echo Json::encode(['status'=>false,'message'=>"Order not found"]);exit;
            }
        }else{
            echo Json::encode(['status'=>false,'message'=>"Order Id is required"]);exit;

        }
    }

    public function getOrderIds($corporate_id){
        $orderids = ThirdpartyCorporateOrderMapping::find()
                        ->select(['order_id'])
                        ->where(['thirdparty_corporate_id'=>$corporate_id])
                        ->asArray()
                        ->all();
        $ids = [];
        if($orderids){
            foreach ($orderids as $key => $value) {
                array_push($ids,$value['order_id']);
            }
        }
        return $ids;
    }

    public function actionListWhitelabeled(){
        $list = ThirdpartyCorporate::find()
                ->select(['corporate_name','bag_limit','max_bag_weight','order_type','transfer_type'])
                ->where(['is_white_labled_corporate'=>1])
                ->asArray()
                ->all();
        $listall=[];       
        foreach($list as $value){
            $value['order_type'] = ($value['order_type'] == 1) ? "Local" : ($value['order_type'] == 2) ? "Outstation" : "Both";
            $value['transfer_type'] = ($value['transfer_type'] == 1) ? "City" : ($value['transfer_type'] == 2) ? "Airport" : "Both";
            $value['max_bag_weight'] = $value['max_bag_weight']."kg";
            array_push($listall,$value);
        }
        echo Json::encode(['status'=>true,'message'=>"Successfull",'List'=>$listall]);
        
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
    /**
     * Getting the token and thirdparty name
     */
    public function actionGetToken(){
        $name = Yii::$app->request->serverName;
        $getToken = ThirdpartyCorporate::find()->select(['access_token','corporate_name'])->where(['LIKE','domain_name',$name])->asArray()->all();
        if($getToken){
            echo json::encode(['status'=>true,'message'=>"successfull",'data'=>$getToken]);

        }else{
            echo json::encode(['status'=>false,'message'=>"No such thirdparty corporates are available"]);
        }
    }

    /* // public function Checkpincodeavailability($pincode,$a_id,$s_type)
    {
        if($s_type==1){
            $respincode=PickDropLocation::find()->where(['pincode'=>$pincode,'to_airport'=>1])->one();
        }else if($s_type==2){
            $respincode=PickDropLocation::find()->where(['pincode'=>$pincode,'from_airport'=>1])->one();
        }
        
        if(!empty($respincode)){
            if($a_id == $respincode['fk_tbl_airport_of_operation_airport_name_id']){
              $status = 1;
            }else{
                $status = 2;
            }
        }else{
            $status = 2;
        }

        return $status;
    // }*/

    public function actionThirdpartyStates(){
        // header('Access-Control-Allow-Origin: *');
        // $corporateData = $this->CheckAccesstoken();
        $_POST = User::datatoJson(); 
        if($_POST['transfer_type']==1){//City transfer
            $State=State::find()->select(['idState','stateName'])->where(['city_id'=>$_POST['city_id']])->asArray()->all();  
        }else{ //Airport transfer
            $State=State::find()->select(['idState','stateName'])->where(['airport_id'=>$_POST['airport_id']])->asArray()->all();  
        }
        echo Json::encode(['status'=>true,'message'=>"Successfull",'state'=>$State]);
    }

    //invoice pdf download
    //get method
    public function actionInvoicePdf($order_number){
       
        try{
            $orders=Order::find()->where(['order_number'=>$order_number])->one();

            $order_details['order_details']=Order::getorderdetails($orders['id_order']);
        
            ob_start();
            $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];

            echo Yii::$app->view->render("@app/mail/order_payments_pdf_template",array('data' => $order_details));

            $data = ob_get_clean();

            try
            {
                $html2pdf = new \mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=0,$mgr=0,$mgt=8,$mgb=8,$mgh=9,$mgf=0, $orientation='P');
                $html2pdf->setDefaultFont('dejavusans');
                $html2pdf->showImageErrors = false;

                $html2pdf->writeHTML($data);

                /*this footer will be added into the last of the page , if you want to display in all of the pages then cut this footer
                line and paster above the $html2pdf->writeHTML($data);,then footer will render in all pages*/

                $html2pdf->SetFooter('<div style="width:100%;padding: 16px; text-align: center;background: #2955a7;color: white;font-size: 15px;position: absolute;bottom: 0px;font-style:normal;font-weight:200;">Luggage Transfer Simplified</div>');

                $html2pdf->Output($path."order_".$order_details['order_details']['order']['order_number'].".pdf",'F');

                /*Preparing file path and folder path for response */
                $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
                $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
                //print_r($path);exit;
        

                echo JSON::encode(array('status' => true, 'path' =>$order_pdf['path']));

            }

            catch(\Exception $e) {
                echo JSON::encode(array('status' => false, 'message' => $e->getMessage().$e->getLine()));
            }

        } catch(\Exception $e) { 
            echo JSON::encode(array('status' => false, 'message' => $e->getMessage().$e->getLine()));
            /*echo $e;
            exit;*/
        }
    


    }


    // new api create for getting prices @Bj
    public function actionThirdpartyPriceCalculation(){
        header('Access-Control-Allow-Origin: *');
        $corporateData = $this->CheckAccesstoken();
        $_POST = User::datatoJson();
        $status = "thirdparty";
        $price = $this->priceInfo($corporateData,$status);
        $conveyance_charges_list = $this->getConveyanceChargesList($corporateData);
        $corporate_charges_list = $this->getCorporateChargesList($corporateData);
        $corporate_airport_charges_list = $this->getCorporateAirportChargesList($corporateData);
        $corporate_bag_price = $this->corporatepriceInfo($corporateData,$status);
        echo Json::encode(['status'=>true,'message'=>"Successfull",'price_details'=>$price, 'corporate_price_details' => $corporate_bag_price, 'conveyance_charge' => $conveyance_charges_list,'corporate_charges' => $corporate_charges_list,'corporate_airport_charges' => $corporate_airport_charges_list]);
    }

    public function priceInfo($corporateData,$status){
        $_POST = User::datatoJson();
        $this->Check_pincode($_POST['pincode']);
        if($corporateData['is_active'] == 1){
            $transfer = isset($_POST['transfer_type']) ? $_POST['transfer_type'] : $_POST['transfer_type'];
            $order_type = isset($_POST['order_type']) ? $_POST['order_type'] : $corporateData['order_type'];
            $corporate_id = $corporateData['thirdparty_corporate_id'];
            $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : "";
            $airport_name = isset($_POST['airport_name']) ? $_POST['airport_name'] : "";
            $no_bags = isset($_POST['no_of_units']) ? $_POST['no_of_units'] : "";
            $bag_weight = isset($_POST['bag_weight']) ? $_POST['bag_weight'] : 0;
            $excess_purchased = isset($_POST['excess_weight_purchased']) ? strtolower($_POST['excess_weight_purchased']) : "";
            $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : "";
            $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : "";
            $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : "";
            if(isset($_POST['admin_status'])){
                if($_POST['admin_status'] == 1){
                    if((($order_type == 2) && ($transfer == 1)) || (($order_type == 1) && ($transfer == 1))) {
                        $getAirportId = Yii::$app->db->createCommand("select * from tbl_city_of_operation co left join tbl_airport_of_operation ao ON ao.fk_tbl_city_of_operation_region_id = co.id where co.id = '".$city_name."' order by ao.airport_name_id asc limit 1")->queryone();
                        $airport_name = isset($getAirportId['airport_name_id']) ? $getAirportId['airport_name_id'] : "";
                        $_POST['airport_name'] = $airport_name;
                    }
                }
            }
            
            $this->checkBagLimit($corporateData['bag_limit'],$no_bags);    
            if(($corporateData['order_type'] == 3) || ($corporateData['order_type'] == $_POST['order_type'])) {
                if(($corporateData['transfer_type'] == 3) || ($corporateData['transfer_type'] == $_POST['transfer_type'])){
                    if($excess_purchased == "yes"){
                        $excess_weight = isset($_POST['excess_weight']) ? $_POST['excess_weight'] : "";
                        $excess_weight_after = $excess_weight / Yii::$app->params['excess_weight_per_kg'];
                        if($status == "porter"){
                            $excess_weight_price = $this->getExcessWeightPurchased($bag_weight,$corporateData['excess_bag_weight']);
                        }else{
                            $excess_weight_price = $this->getExcessWeightPurchased($excess_weight_after,$corporateData['excess_bag_weight']);
                        }
                    }else{
                        $excess_weight_price = $this->getExcessWeightPrice($bag_weight,$corporateData,$status);
                    }
                    
                    $excessPrice = isset($excess_weight_price['price']) ? $excess_weight_price['price'] : 0;
                    $price = $this->getluggagePrice($corporateData,$_POST);
                    $total_price = $this->getPrice($price,$no_bags,$excessPrice,$corporateData['gst']);
                    
                    return $total_price;
                } else {
                    echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this transfer type"]);exit;
                }
            } else {
                echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this order type"]);exit;
            }
        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);exit;
        }
    }

    public function getluggagePrice($corporateInfo,$post){
        if(($corporateInfo['order_type'] == 3) || ($post['order_type'] == $corporateInfo['order_type'])){
            // Open this condition according to local city and airport price
            // if($post['order_type'] == 1){ //local
            //     if(($corporateInfo['transfer_type'] == 3) || ($post['transfer_type'] == $corporateInfo['transfer_type'])){
            //         if($post['transfer_type'] == 1){ //city

            //         } else {//airport

            //         }
            //     } else {
            //         //error
            //     }
            // } else { //outstation
            //     if(($corporateInfo['transfer_type'] == 3) || ($post['transfer_type'] == $corporateInfo['transfer_type'])){

            //     } else {
            //         //error
            //     }
            // }

            if(($corporateInfo['transfer_type'] == 3) || ($post['transfer_type'] == $corporateInfo['transfer_type'])){
                $result = Yii::$app->db->createCommand("Select tclpa.bag_price, 0 as outstation_price from tbl_thirdparty_corporate_airports tca left join tbl_thirdparty_corporate_luggage_price_airport tclpa on tclpa.thirdparty_corporate_airport_id = tca.thirdparty_corporate_airport_id where tca.thirdparty_corporate_id='".$corporateInfo['thirdparty_corporate_id']."' and airport_id = '".$post['airport_name']."'")->queryone();
                return $result;
            } else {
                echo Json::encode(['status' => false,'message'=>"Thirdpraty token isn't valid for this transfer type."]);die;
            }
        } else {
            echo Json::encode(['status' => false,'message'=>"Thirdpraty token isn't valid for this order type."]);die;
        }
    }

    public function Check_pincode($pincode) {
        if(empty($pincode)){
            echo Json::encode(['status' => false, 'message' => 'Pincode is mandatory field']);exit;
        } else {
            $apiKey = 'AIzaSyD9d0O6GwKmtXDbKtmWFIV2nhXrSmIOvik';
            $api = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".$pincode."&key=".$apiKey);
            $data = json_decode($api);
            if(strtoupper($data->status) == 'OK'){
                return true;
            }else{
                echo Json::encode(['status'=>false,'message'=>"Pincode is not valid, Try another pincode."]);exit;
            }
        }
    }

    public function calculateConveyancePrice($corporateInfo,$result){
        $mainArray = array();
        $gst = $corporateInfo['gst'];
        
        foreach($result as $value){
            $bag_price = $value['bag_price'];
            $bag_gst = ($value['bag_price'] * $gst) / 100;
            $total_bag_gst_price = $bag_price + $bag_gst;
            $mainArray[] = array(
                'region_name' => $value['region_name'],
                'bag_price' => $value['bag_price'],
                'gst' => $gst,
                'gst_price' => $bag_gst,
                'total_price' => $total_bag_gst_price
            );
        }
        return $mainArray;
    }

    public function actionCheckSlots(){

        $_POST = User::datatoJson();
        if($_POST){
            $result = Yii::$app->Common->getTimeSlots($_POST);
            if($result){
                echo Json::encode(['status' => true, 'message' => 'Successfull', 'result' => $result]);
            } else {
                echo Json::encode(['status' => false, 'message' => 'No record found', 'result' => array()]);
            }
        } else {
            echo Json::encode(['status' => false, 'message' => 'Some Parameters missing']);
        }
    }

    public function actionUpdateOrderDate(){
        $statusArr = array(1,2,3);
        $_POST = User::datatoJson();
        if($_POST){
            $currentDate = date('Y-m-d');
            $date = isset($_POST['order_date']) ? date('Y-m-d',strtotime($_POST['order_date'])) : "";
            if(empty($_POST['order_date'])){
                echo Json::encode(['status' => false,'message' => "Please send Date"]);exit();
            } else if($currentDate > $date){
                echo Json::encode(['status' => false,'message' => 'Date Should Be greater Than To Current Date']);exit();
            }
            
            
            $model = Order::getorderdetails($_POST['order_id']);
            if(in_array($model['order']['id_order_status'],$statusArr)){
                Yii::$app->db->createCommand("update tbl_order set order_date = '".$date."' where id_order=".$_POST['order_id'])->execute();
                $emp_details = Yii::$app->db->createCommand("SELECT id_employee,name FROM tbl_employee where id_employee = ".$_POST['employee_id'])->queryOne();

                $saveData = [];
                $saveData['order_id'] = $_POST['order_id'];
                $saveData['description'] = 'Order modification date of service';
                $saveData['employee_id'] = $emp_details['id_employee'];
                $saveData['employee_name'] = $emp_details['name'];
                $saveData['module_name'] = 'Date of Service';
                Yii::$app->Common->ordereditHistory($saveData);
                echo Json::encode(['status'=>true, 'message'=>"Order date successfully changed"]);exit();
            } else {
                echo Json::encode(['status'=>false,'message'=>"Order is ".$model['order']['order_status']]);exit();
            }
        } else {
            echo Json::encode(['status'=>false,'message'=>"Please Send Require Details."]);exit();
        }
        
    }

    /**
     * Function for getting corporate outstation price listing for corporate user order
    */
    public function getCorporateChargesList($corporateData){
        if(empty($corporateData)){
            return false; 
        } else {
            $result = Yii::$app->db->createCommand("select co.region_name,IF(tcdpr.bag_price, tcdpr.bag_price, 0) as bag_price from tbl_thirdparty_corporate_city_region tccr left join tbl_thirdparty_corporate_discount_price_region tcdpr on tcdpr.thirdparty_corporate_region_id = tccr.thirdparty_corporate_city_id left join tbl_city_of_operation co on co.id = tccr.city_region_id where tccr.thirdparty_corporate_id = '".$corporateData['thirdparty_corporate_id']."' and co.region_name  IS NOT NULL order by co.id")->queryAll();// and  tcdpr.thirdparty_corporate_city_id != ''
            if(!empty($result)){
                return $this->calculateConveyancePrice($corporateData,$result);
            } else {
                return false;
            }
        }
    }

    /**
     * Function for getting corporate bug price listing for corporate user order
    */
    public function getCorporateAirportChargesList($corporateData){
        if(empty($corporateData)){
            return false; 
        } else {
            $result = Yii::$app->db->createCommand("select ao.airport_name,ao.airport_name_id as airport_id,ao.fk_tbl_city_of_operation_region_id as region_id,IF(tcdpa.bag_price, tcdpa.bag_price, 0) as bag_price from tbl_thirdparty_corporate_airports tca left join tbl_thirdparty_corporate_discount_price_airport tcdpa on tcdpa.thirdparty_corporate_airport_id = tca.thirdparty_corporate_airport_id left join tbl_airport_of_operation ao on ao.airport_name_id = tca.airport_id where tca.thirdparty_corporate_id = '".$corporateData['thirdparty_corporate_id']."' order by region_id,airport_id asc")->queryAll();// and  tclpc.thirdparty_corporate_city_id != ''
            if(!empty($result)){
                return $this->calculateConveyanceAirportPrice($corporateData,$result);
            } else {
                return false;
            }
        }
    }

    public function calculateConveyanceAirportPrice($corporateInfo,$result){
        $mainArray = array();
        $gst = $corporateInfo['gst'];
        
        foreach($result as $value){
            $bag_price = $value['bag_price'];
            $bag_gst = ($value['bag_price'] * $gst) / 100;
            $total_bag_gst_price = $bag_price + $bag_gst;
            $mainArray[] = array(
                'airport_name' => $value['airport_name'],
                'airport_id' => $value['airport_id'],
                'region_id' => $value['region_id'],
                'bag_price' => $value['bag_price'],
                'gst' => $gst,
                'gst_price' => $bag_gst,
                'total_price' => $total_bag_gst_price
            );
        }
        return $mainArray;
    }

    public function corporatepriceInfo($corporateData,$status){
        $_POST = User::datatoJson();
        $this->Check_pincode($_POST['pincode']);
        if($corporateData['is_active'] == 1){
            $transfer = isset($_POST['transfer_type']) ? $_POST['transfer_type'] : $_POST['transfer_type'];
            $order_type = isset($_POST['order_type']) ? $_POST['order_type'] : $corporateData['order_type'];
            $corporate_id = $corporateData['thirdparty_corporate_id'];
            $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : "";
            $airport_name = isset($_POST['airport_name']) ? $_POST['airport_name'] : "";
            $no_bags = isset($_POST['no_of_units']) ? $_POST['no_of_units'] : "";
            $bag_weight = isset($_POST['bag_weight']) ? $_POST['bag_weight'] : 0;
            $excess_purchased = isset($_POST['excess_weight_purchased']) ? strtolower($_POST['excess_weight_purchased']) : "";
            $pincode = isset($_POST['pincode']) ? $_POST['pincode'] : "";
            $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : "";
            $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : "";
            if(isset($_POST['admin_status'])){
                if($_POST['admin_status'] == 1){
                    if((($order_type == 2) && ($transfer == 1)) || (($order_type == 1) && ($transfer == 1))) {
                        $getAirportId = Yii::$app->db->createCommand("select * from tbl_city_of_operation co left join tbl_airport_of_operation ao ON ao.fk_tbl_city_of_operation_region_id = co.id where co.id = '".$city_name."' order by ao.airport_name_id asc limit 1")->queryone();
                        $airport_name = isset($getAirportId['airport_name_id']) ? $getAirportId['airport_name_id'] : "";
                        $_POST['airport_name'] = $airport_name;
                    }
                }
            }
            
            $this->checkBagLimit($corporateData['bag_limit'],$no_bags);    
            if(($corporateData['order_type'] == 3) || ($corporateData['order_type'] == $_POST['order_type'])) {
                if(($corporateData['transfer_type'] == 3) || ($corporateData['transfer_type'] == $_POST['transfer_type'])){
                    if($excess_purchased == "yes"){
                        $excess_weight = isset($_POST['excess_weight']) ? $_POST['excess_weight'] : "";
                        $excess_weight_after = $excess_weight / Yii::$app->params['excess_weight_per_kg'];
                        if($status == "porter"){
                            $excess_weight_price = $this->getExcessWeightPurchased($bag_weight,$corporateData['excess_bag_weight']);
                        }else{
                            $excess_weight_price = $this->getExcessWeightPurchased($excess_weight_after,$corporateData['excess_bag_weight']);
                        }
                    }else{
                        $excess_weight_price = $this->getExcessWeightPrice($bag_weight,$corporateData,$status);
                    }
                    
                    $excessPrice = isset($excess_weight_price['price']) ? $excess_weight_price['price'] : 0;
                    $price = $this->getCorporateluggagePrice($corporateData,$_POST);
                    $total_price = $this->getPrice($price,$no_bags,$excessPrice,$corporateData['gst']);
                    
                    return $total_price;
                } else {
                    echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this transfer type"]);exit;
                }
            } else {
                echo Json::encode(['status'=>false,'message'=>"This Third party Corporate not allowed for this order type"]);exit;
            }
        }else if($corporateData['is_active'] == 2){
            echo Json::encode(['status'=>false,'message'=>"This Third party Corporate is Disabled"]);exit;
        }
    }

    public function getCorporateluggagePrice($corporateInfo,$post){
        if(($corporateInfo['order_type'] == 3) || ($post['order_type'] == $corporateInfo['order_type'])){
            if(($corporateInfo['transfer_type'] == 3) || ($post['transfer_type'] == $corporateInfo['transfer_type'])){
                $result = Yii::$app->db->createCommand("Select if(tcdpa.bag_price=0,0,tcdpa.bag_price) as bag_price, 0 as outstation_price from tbl_thirdparty_corporate_airports tca left join tbl_thirdparty_corporate_discount_price_airport tcdpa on tcdpa.thirdparty_corporate_airport_id = tca.thirdparty_corporate_airport_id where tca.thirdparty_corporate_id = '".$corporateInfo['thirdparty_corporate_id']."' and airport_id = '".$post['airport_name']."'")->queryone();
                return $result;
            } else {
                echo Json::encode(['status' => false,'message'=>"Thirdpraty token isn't valid for this transfer type."]);die;
            }
        } else {
            echo Json::encode(['status' => false,'message'=>"Thirdpraty token isn't valid for this order type."]);die;
        }
    }
    // new api create for getting prices @Bj
}