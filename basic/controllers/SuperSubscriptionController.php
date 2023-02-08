<?php

namespace app\controllers;
use Yii;
use app\models\SuperSubscription;
use app\models\SuperSubscriptionSearch;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\SubscriptionAirport;
use app\models\SubscriptionRegion;
use app\models\SubscriptionPaymentRestriction;
use app\models\SubscriptionTokenMap;
use app\models\Employee;
use app\models\EmployeeSearch;
use Razorpay\Api\Api;
use app\models\SubscriptionTransactionDetails;
use app\models\SubscriptionTransactionDetailsSearch;
use app\models\SubscriptionPaymentLinkDetails;
use app\models\OrderSearch;
use yii\helpers\ArrayHelper;
use app\models\User;
use app\models\EmployeeAllocation;
use app\models\Order;
use app\models\CityOfOperation;
use app\models\OrderItems;
use app\models\OrderSpotDetails;
use app\api_v3\v3\models\OrderMetaDetails;
use app\api_v3\v3\models\State;
use app\models\MallInvoices;
use app\models\BagWeightType;
use app\api_v3\v3\models\OrderZoneDetails;
use app\models\CorporateDetails;
use app\models\Customer;
use\app\models\PickDropLocation;
use app\models\Slots;
use app\api_v3\v3\models\ThirdpartyCorporateOrderMapping;
use app\models\OrderPaymentDetails;
use app\models\LuggageType;
use app\models\CountryCode;
use app\models\LoginForm;



/**
 * SuperSubscriptionController implements the CRUD actions for SuperSubscription model.
 */
class SuperSubscriptionController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
                'access' => [
                    'class' => AccessControl::className(),
                    'ruleConfig' => [
                                'class' => \app\components\AccessRule::className(),
                            ],
                    'only' => ['super-subscriber-dashboard','subscription-details','purchase-subscription','manage-order'],
                    'rules' => [
                        [
                            'actions' => ['super-subscriber-dashboard','subscription-details','purchase-subscription','manage-order'],
                            'allow' => true,
                            'roles' => [17],
                        ],
                        [
                            'actions' => ['subscription-details'],
                            'allow' => true,
                            'roles' => [1,12,13,14,15],
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all SuperSubscription models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SuperSubscriptionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SuperSubscription model.
     * @param int $subscription_id Subscription ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new SuperSubscription model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SuperSubscription();
        $model_methods = array("netbanking" => 'Netbanking',"card"=>'Card','upi'=>'Upi','wallet'=>'Wallet');

        if (Yii::$app->request->post()) {
            if ($model->load(Yii::$app->request->post())) {
                $FolderExits = 'uploads/super_subscriber_logos';
                if (!file_exists($FolderExits)) {
                    mkdir($FolderExits, 0777);
                }
                $model->subscriber_name = ucwords($_POST['SuperSubscription']['subscriber_name']);
                $model->register_address = ucwords($_POST['SuperSubscription']['register_address']);
                $model->subscription_area = ucwords($_POST['SuperSubscription']['subscription_area']);
                $model->subscription_pincode = $_POST['SuperSubscription']['subscription_pincode'];
                $model->primary_contact = $_POST['SuperSubscription']['primary_contact'];
                $model->secondary_contact = $_POST['SuperSubscription']['secondary_contact'];
                $model->primary_email = $_POST['SuperSubscription']['primary_email'];
                $model->secondary_email = $_POST['SuperSubscription']['secondary_email'];
                $model->subscription_GST = $_POST['SuperSubscription']['subscription_GST'];
                $model->subscriber_status = $_POST['SuperSubscription']['subscriber_status'];
                $model->no_of_usages = $_POST['SuperSubscription']['no_of_usages'];
                $model->subscription_cost = $_POST['SuperSubscription']['subscription_cost'];
                $model->redemption_cost = $_POST['SuperSubscription']['redemption_cost'];
                $model->razorpay_status = (!empty($_POST['no_restriction'])) ? 0 : 1;
                $model->transfer_type = $_POST['SuperSubscription']['transfer_type'];

                if($_FILES["SuperSubscription"]["tmp_name"]['subscriber_logo'] != NULL){
                    $def_filename = time().$_FILES["SuperSubscription"]["name"]['subscriber_logo'];
                    $url = $FolderExits.'/'.$def_filename;
                    $logo_image = Yii::$app->Common->compress_image($_FILES["SuperSubscription"]["tmp_name"]['subscriber_logo'], $url, 80);
                    $model->subscriber_logo = $def_filename;
                }
                if($model->save(false)){
                    if($_POST['SuperSubscription']['primary_email']){
                        $model_emp = new Employee;
                        $model_emp->fk_tbl_employee_id_employee_role  = 17;
                        $model_emp->name = ucwords($_POST['SuperSubscription']['subscriber_name']);
                        $model_emp->email = $_POST['SuperSubscription']['primary_email'];
                        $model_emp->mobile = $_POST['SuperSubscription']['primary_contact'];
                        $model_emp->password = sha1($_POST['password']);
                        $model_emp->status = 1;
                        $model_emp->date_created = date("Y-m-d H:i:s");
                        $model_emp->date_modified = date("Y-m-d H:i:s");
                        $model_emp->save(false);
                    }
                    // if($_POST['SuperSubscription']['secondary_email']){
                    //     $model_emp2 = new Employee;
                    //     $model_emp2->fk_tbl_employee_id_employee_role  = 17;
                    //     $model_emp2->name = ucwords($_POST['SuperSubscription']['subscriber_name']);
                    //     $model_emp2->email = $_POST['SuperSubscription']['secondary_email'];
                    //     $model_emp2->mobile = $_POST['SuperSubscription']['secondary_contact'];
                    //     $model_emp2->password = sha1($_POST['password']);
                    //     $model_emp2->status = 1;
                    //     $model_emp2->date_created = date("Y-m-d H:i:s");
                    //     $model_emp2->date_modified = date("Y-m-d H:i:s");
                    //     $model_emp2->save(false);
                    // }

                    // foreach($_POST['SubscriptionRegion']['region_id'] as $key=>$value){
                    //     $model_city = new SubscriptionRegion;
                    //     $model_city->subscription_id = $model->subscription_id;
                    //     $model_city->region_id = $value;
                    //     $model_city->create_date = date('Y-m-d H:i:s');
                    //     $model_city->save();
                    // }
                    // foreach($_POST['SubscriptionAirport']['airport_id'] as $key=>$value){
                    //     $model_airport = new SubscriptionAirport;
                    //     $model_airport->subscription_id = $model->subscription_id;
                    //     $model_airport->airport_id = $value;
                    //     $model_airport->create_date = date('Y-m-d H:i:s');
                    //     $model_airport->save();
                    // }
                    foreach($_POST['SubscriptionTokenMap']['thirdparty_token_id'] as $key=>$value){
                        $model_tokens = new SubscriptionTokenMap;
                        $model_tokens->subscription_id = $model->subscription_id;
                        $model_tokens->thirdparty_token_id = $value;
                        $model_tokens->create_date = date('Y-m-d H:i:s');
                        $model_tokens->save();
                    }
                
                    $val = (isset($_POST['payment_method'])) ? (in_array('credit',$_POST['payment_method']) ? 1 : 0) : 0;
                    $val2 = (isset($_POST['payment_method'])) ? (in_array('debit',$_POST['payment_method']) ? 1 : 0) : 0;
                    $resVal = !empty($val) ? (!empty($val2) ? 3 : 1) : (!empty($val2) ? 2 : 0);

                    if(!empty($_POST['SubscriptionPaymentRestriction']['fk_bank_id']) && in_array("netbanking",$_POST['payment_method'])){
                        foreach($_POST['SubscriptionPaymentRestriction']['fk_bank_id'] as $key=>$value){                     
                            $model_bank = new SubscriptionPaymentRestriction;
                            $model_bank->subscription_id = $model->subscription_id;
                            $model_bank->fk_bank_id = $value;
                            $model_bank->method_id = 1;
                            $model_bank->fk_card_id = $resVal;
                            $model_bank->fk_upi_id = 0;
                            $model_bank->fk_wallet_id = 0;
                            $model_bank->status = 1;
                            $model_bank->create_date = date('Y-m-d H:i:s');
                            $model_bank->save(false);
                        }
                    }
                    if(!empty($_POST['SubscriptionPaymentRestriction']['fk_card_id']) && in_array("card",$_POST['payment_method'])){
                        foreach($_POST['SubscriptionPaymentRestriction']['fk_card_id'] as $key=>$value){                     
                            $model_bank = new SubscriptionPaymentRestriction;
                            $model_bank->subscription_id = $model->subscription_id;
                            $model_bank->fk_bank_id = 0;
                            $model_bank->method_id = 2;
                            $model_bank->fk_card_id = $value;
                            $model_bank->fk_upi_id = 0;
                            $model_bank->fk_wallet_id = 0;
                            $model_bank->status = 1;
                            $model_bank->create_date = date('Y-m-d H:i:s');
                            $model_bank->save(false);
                        }
                    }
                    if(!empty($_POST['SubscriptionPaymentRestriction']['fk_wallet_id']) && in_array("wallet",$_POST['payment_method'])){
                        foreach($_POST['SubscriptionPaymentRestriction']['fk_wallet_id'] as $key=>$value){                     
                            $model_bank = new SubscriptionPaymentRestriction;
                            $model_bank->subscription_id = $model->subscription_id;
                            $model_bank->fk_bank_id = 0;
                            $model_bank->method_id = 3;
                            $model_bank->fk_card_id = 0;
                            $model_bank->fk_upi_id = 0;
                            $model_bank->fk_wallet_id = $value;
                            $model_bank->status = 1;
                            $model_bank->create_date = date('Y-m-d H:i:s');
                            $model_bank->save(false);
                        }
                    }
                    if(!empty($_POST['SubscriptionPaymentRestriction']['fk_upi_id']) && in_array("upi",$_POST['payment_method'])){
                        foreach($_POST['SubscriptionPaymentRestriction']['fk_upi_id'] as $key=>$value){                     
                            $model_bank = new SubscriptionPaymentRestriction;
                            $model_bank->subscription_id = $model->subscription_id;
                            $model_bank->fk_bank_id = 0;
                            $model_bank->method_id = 4;
                            $model_bank->fk_card_id = 0;
                            $model_bank->fk_upi_id = $value;
                            $model_bank->fk_wallet_id = 0;
                            $model_bank->status = 1;
                            $model_bank->create_date = date('Y-m-d H:i:s');
                            $model_bank->save(false);
                        }
                    }
                    return $this->redirect(['view', 'id' => $model->subscription_id]);
                } else {
                    return $this->render('create', [
                        'model' => $model,
                    ]);
                }
                
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     * Function for update bank restriction
    */
    public function subscriberBankUpdate($data){
        if(!empty($data['payment_method'])){
            $list_bank = [];
            $list_card = [];
            $list_wallet = [];
            $list_upi = [];
            $bank_id = [];
            $card_id = [];
            $wallet_id = [];
            $upi_id = [];
            $netbanking = 0;
            $card = 0;
            $upi = 0;
            $wallet = 0;

            $val = in_array('credit',$data['payment_method']) ? 1 : 0;
            $val2 = in_array('debit',$data['payment_method']) ? 1 : 0;
            $resVal = !empty($val) ? (!empty($val2) ? 3 : 1) : (!empty($val2) ? 2 : 0); // Here Set credit and debit card for bank

            if(in_array("netbanking",$data['payment_method'])){
                $method_name = 1;// method is netbanking
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_bank_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 1])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_bank,$value['fk_bank_id']);
                    }
                }
                foreach($data['bank_list'] as $key=>$value){
                    array_push($bank_id,$value);
                }
                $dele_bank = array_diff($list_bank,$bank_id);
                $add_bank = array_diff($bank_id,$list_bank);
    
                $array_unmatch = array_diff($list_bank,$dele_bank);
                $array_unmatch = array_diff($array_unmatch,$add_bank);

                if(!empty($dele_bank)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_bank_id', $dele_bank]], 
                                    [':id' => $data['subscription_id']]);
                }

                if(!empty($array_unmatch)){
                    foreach($array_unmatch as $value){
                        $model_bank_ = SubscriptionPaymentRestriction::find()->where(['fk_bank_id'=>$value,'subscription_id'=>$data['subscription_id']])->one();
                        $model_bank_->method_id = $method_name;
                        $model_bank_->fk_bank_id = $value;
                        $model_bank_->fk_card_id = $resVal;
                        $model_bank_->fk_upi_id = 0;
                        $model_bank_->fk_wallet_id = 0;
                        $model_bank_->status = 1;
                        $model_bank_->create_date = date('Y-m-d H:i:s');
                        $model_bank_->save(false);
                    }
                }
    
                if(!empty($add_bank)){
                    foreach($add_bank as $key=>$value){                     
                        $model_bank = new SubscriptionPaymentRestriction;
                        $model_bank->subscription_id = $data['subscription_id'];
                        $model_bank->fk_bank_id = $value;
                        $model_bank->method_id = $method_name;
                        $model_bank->fk_card_id = $resVal;
                        $model_bank->fk_upi_id = 0;
                        $model_bank->fk_wallet_id = 0;
                        $model_bank->status = 1;
                        $model_bank->create_date = date('Y-m-d H:i:s');
                        $model_bank->save(false);
                    }
                }
            } else if(!empty($data['bank_list'])){
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_wallet_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 1])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_bank,$value['fk_bank_id']);
                    }
                }
                foreach($data['bank_list'] as $key=>$value){
                    array_push($bank_id,$value);
                }
                $dele_bank = array_diff($list_bank,$bank_id);
                if(!empty($dele_bank)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_bank_id', $dele_bank]], 
                                    [':id' => $data['subscription_id']]);
                }
            }

            if(in_array("card",$data['payment_method'])){
                $method_name = 2;// method is card
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_card_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 2])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_card,$value['fk_card_id']);
                    }
                }
                foreach($data['card_list'] as $key=>$value){
                    array_push($card_id,$value);
                }
                $dele_card = array_diff($list_card,$card_id);
                $add_card = array_diff($card_id,$list_card);
    
                $array_unmatch = array_diff($list_card,$dele_card);
                $array_unmatch = array_diff($array_unmatch,$add_card);

                if(!empty($dele_card)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_card_id', $dele_card]], 
                                    [':id' => $data['subscription_id']]);
                }
    
                if(!empty($add_card)){
                    foreach($add_card as $key=>$value){                     
                        $model_card = new SubscriptionPaymentRestriction;
                        $model_card->subscription_id = $data['subscription_id'];
                        $model_card->fk_bank_id = 0;
                        $model_card->method_id = $method_name;
                        $model_card->fk_card_id = $value;
                        $model_card->fk_upi_id = 0;
                        $model_card->fk_wallet_id = 0;
                        $model_card->status = 1;
                        $model_card->create_date = date('Y-m-d H:i:s');
                        $model_card->save(false);
                    }
                }
            } else if(!empty($data['card_list'])){
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_wallet_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 2])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_card,$value['fk_card_id']);
                    }
                }
                foreach($data['card_list'] as $key=>$value){
                    array_push($card_id,$value);
                }
                $dele_card = array_diff($list_card,$card_id);
                if(!empty($dele_card)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_card_id', $dele_card]], 
                                    [':id' => $data['subscription_id']]);
                }
            }
            
            if(in_array("wallet",$data['payment_method'])){
                $method_name = 3;// method is wallet
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_wallet_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 3])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_wallet,$value['fk_wallet_id']);
                    }
                }
                foreach($data['wallet_list'] as $key=>$value){
                    array_push($wallet_id,$value);
                }
                $dele_wallet = array_diff($list_wallet,$wallet_id);
                $add_wallet = array_diff($wallet_id,$list_wallet);
    
                $array_unmatch = array_diff($list_wallet,$dele_wallet);
                $array_unmatch = array_diff($array_unmatch,$add_wallet);
                if(!empty($dele_wallet)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_wallet_id', $dele_wallet]], 
                                    [':id' => $data['subscription_id']]);
                }
    
                if(!empty($add_wallet)){
                    foreach($add_wallet as $key=>$value){                     
                        $model_wallet = new SubscriptionPaymentRestriction;
                        $model_wallet->subscription_id = $data['subscription_id'];
                        $model_wallet->fk_bank_id = 0;
                        $model_wallet->method_id = $method_name;
                        $model_wallet->fk_card_id = 0;
                        $model_wallet->fk_upi_id = 0;
                        $model_wallet->fk_wallet_id = $value;
                        $model_wallet->status = 1;
                        $model_wallet->create_date = date('Y-m-d H:i:s');
                        $model_wallet->save(false);
                    }
                }
            } else if(!empty($data['wallet_list'])){
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_wallet_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 3])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_wallet,$value['fk_wallet_id']);
                    }
                }
                
                foreach($data['wallet_list'] as $key=>$value){
                    array_push($wallet_id,$value);
                }
                
                $dele_wallet = array_intersect($list_wallet,$wallet_id);
                if(!empty($dele_wallet)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_wallet_id', $dele_wallet]], 
                                    [':id' => $data['subscription_id']]);
                }
            }

            if(in_array("upi",$data['payment_method'])){
                $method_name = 4;// method is upi
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_upi_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 4])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_upi,$value['fk_upi_id']);
                    }
                }
                
                foreach($data['upi_list'] as $key=>$value){
                    array_push($upi_id,$value);
                }
                
                $dele_upi = array_diff($list_upi,$upi_id);
                $add_upi = array_diff($upi_id,$list_bank);

                $array_unmatch = array_diff($list_upi,$dele_upi);
                $array_unmatch = array_diff($array_unmatch,$add_upi);

                if(!empty($dele_upi)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_upi_id', $dele_upi]], 
                                    [':id' => $data['subscription_id']]);
                }
    
                if(!empty($add_upi)){
                    foreach($add_upi as $key=>$value){                     
                        $model_upi = new SubscriptionPaymentRestriction;
                        $model_upi->subscription_id = $data['subscription_id'];
                        $model_upi->fk_bank_id = 0;
                        $model_upi->method_id = $method_name;
                        $model_upi->fk_card_id = 0;
                        $model_upi->fk_upi_id = $value;
                        $model_upi->fk_wallet_id = 0;
                        $model_upi->status = 1;
                        $model_upi->create_date = date('Y-m-d H:i:s');
                        $model_upi->save(false);
                    }
                }
            } else if(!empty($data['upi_list'])){
                $Res = SubscriptionPaymentRestriction::find()
                        ->select(['fk_upi_id'])
                        ->where(['subscription_id'=>$data['subscription_id'],"method_id" => 4])
                        ->asArray()->all();
                if(!empty($Res)){
                    foreach ($Res as $key => $value) {
                        array_push($list_upi,$value['fk_upi_id']);
                    }
                }
                
                foreach($data['upi_list'] as $key=>$value){
                    array_push($upi_id,$value);
                }
                $dele_upi = array_intersect($list_upi,$upi_id);
                if(!empty($dele_upi)){
                    SubscriptionPaymentRestriction::deleteAll(['and', 
                                    'subscription_id = :id', 
                                    ['in', 'fk_upi_id', $dele_upi]], 
                                    [':id' => $data['subscription_id']]);
                }
            }
        }
        return 1;
    }

    /**
     * Function for update airport mapping
    */
    public function subscriberAirportUpdate($data){
        if(!empty($data['airport_id'])){
            $airports = SubscriptionAirport::find()
                                ->select(['airport_id'])
                                ->where(['subscription_id'=>$data['subscription_id']])
                                ->asArray()->all();
            $list_airports = [];
            $airports_ids=[];

            foreach ($airports as $key => $value) {
                array_push($list_airports,$value['airport_id']);
            }
            foreach($data['airport_id'] as $key=>$value){
                array_push($airports_ids,$value);
            }

            $dele_airport = array_diff($list_airports,$airports_ids);
            $add_airport = array_diff($airports_ids,$list_airports);

            if(!empty($dele_airport)){
                SubscriptionAirport::deleteAll(['and', 
                                'subscription_id = :id', 
                                ['in', 'airport_id', $dele_airport]], 
                                [':id' => $data['subscription_id']]);
            }

            if(!empty($add_airport)){
                foreach($add_airport as $key=>$value){                        
                    $model_airport = new SubscriptionAirport;
                    $model_airport->subscription_id = $data['subscription_id'];
                    $model_airport->airport_id = $value;
                    $model_airport->created_on = date('Y-m-d H:i:s');
                    $model_airport->save(false);
                }
            }
            
        }
        return 1;
    }

    /**
     * Function for update city mapping
    */
    public function subscriberCityUpdate($data){
        if(!empty($data['city_id'])){
            $cityid = SubscriptionRegion::find()
                    ->select('region_id')
                    ->where(['subscription_id'=>$data['subscription_id']])
                    ->all();

            $list_cities = [];
            $city_ids = [];

            foreach ($cityid as $key => $value) {
                array_push($list_cities,$value['region_id']);
            }

            foreach($data['city_id'] as $key=>$value){
                array_push($city_ids,$value);
            }

            $dele_city = array_diff($list_cities,$city_ids);
            $add_city = array_diff($city_ids,$list_cities);

            if(!empty($dele_city)){
                SubscriptionRegion::deleteAll(['and', 
                                'subscription_id = :id', 
                                ['in', 'region_id', $dele_city]], 
                                [':id' => $data['subscription_id']]);
            }

            if(!empty($add_city)){
                foreach($add_city as $key=>$value){
                    $model_city = new SubscriptionRegion;
                        $model_city->subscription_id = $data['subscription_id'];
                        $model_city->region_id = $value;
                        $model_city->created_on = date('Y-m-d H:i:s');
                        $model_city->save(false);
                }
            }
            
        }

        return 1;
    }

    /**
     * Function for update token mapping
    */
    public function subscriberTokensUpdate($data){
        if(!empty($data['token_id'])){
            $tokenid = SubscriptionTokenMap::find()
                    ->select('thirdparty_token_id')
                    ->where(['subscription_id'=>$data['subscription_id']])
                    ->all();

            $list_cities = [];
            $token_ids = [];

            foreach ($tokenid as $key => $value) {
                array_push($list_cities,$value['thirdparty_token_id']);
            }

            foreach($data['token_id'] as $key=>$value){
                array_push($token_ids,$value);
            }

            $dele_token = array_diff($list_cities,$token_ids);
            $add_token = array_diff($token_ids,$list_cities);

            if(!empty($dele_token)){
                SubscriptionTokenMap::deleteAll(['and', 
                                'subscription_id = :id', 
                                ['in', 'thirdparty_token_id', $dele_token]], 
                                [':id' => $data['subscription_id']]);
            }

            if(!empty($add_token)){
                foreach($add_token as $key=>$value){
                    $model_token = new SubscriptionTokenMap;
                        $model_token->subscription_id = $data['subscription_id'];
                        $model_token->thirdparty_token_id = $value;
                        $model_token->create_date = date('Y-m-d H:i:s');
                        $model_token->save(false);
                }
            }
        }
        return 1;
    }

    /**
     * Updates an existing SuperSubscription model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $subscription_id Subscription ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id){
        $model = $this->findModel($id);
        $old_model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $FolderExits = 'uploads/super_subscriber_logos';
            if (!file_exists($FolderExits)) {
                mkdir($FolderExits, 0777);
            }
            $model->subscriber_name = ucwords($_POST['SuperSubscription']['subscriber_name']);
            $model->register_address = ucwords($_POST['SuperSubscription']['register_address']);
            $model->subscription_area = ucwords($_POST['SuperSubscription']['subscription_area']);
            $model->subscription_pincode = $_POST['SuperSubscription']['subscription_pincode'];
            $model->primary_contact = $_POST['SuperSubscription']['primary_contact'];
            $model->secondary_contact = $_POST['SuperSubscription']['secondary_contact'];
            $model->primary_email = $_POST['SuperSubscription']['primary_email'];
            $model->secondary_email = $_POST['SuperSubscription']['secondary_email'];
            $model->subscription_GST = $_POST['SuperSubscription']['subscription_GST'];
            $model->subscriber_status = $_POST['SuperSubscription']['subscriber_status'];
            $model->transfer_type = $_POST['SuperSubscription']['transfer_type'];
            $model->razorpay_status = (isset($_POST['no_restriction']) && !empty($_POST['no_restriction'])) ? 0 : 1;

            if($_FILES["SuperSubscription"]["tmp_name"]['subscriber_logo'] != NULL){
                $def_filename = time().$_FILES["SuperSubscription"]["name"]['subscriber_logo'];
                $url = $FolderExits.'/'.$def_filename;
                $logo_image = Yii::$app->Common->compress_image($_FILES["SuperSubscription"]["tmp_name"]['subscriber_logo'], $url, 80);
                $model->subscriber_logo = $def_filename;
            } else {
                $model->subscriber_logo = $old_model->subscriber_logo;
            }

            if($model->save(false)){
                if(empty($_POST['no_restriction']) && !isset($_POST['no_restriction'])){
                    $this->subscriberBankUpdate(['payment_method'=>$_POST['payment_method'],'bank_list'=>$_POST['SubscriptionPaymentRestriction']['fk_bank_id'],'card_list'=>$_POST['SubscriptionPaymentRestriction']['fk_card_id'],'wallet_list' => $_POST['SubscriptionPaymentRestriction']["fk_wallet_id"],'upi_list'=>$_POST['SubscriptionPaymentRestriction']["fk_upi_id"],'subscription_id'=>$model->subscription_id]);
                }
                // $this->subscriberAirportUpdate(['airport_id' => $_POST['SubscriptionAirport']['airport_id'],'subscription_id'=>$model->subscription_id]);
                // $this->subscriberCityUpdate(['city_id' => $_POST['SubscriptionRegion']['region_id'],'subscription_id'=>$model->subscription_id]);
                $this->subscriberTokensUpdate(['token_id' => $_POST['SubscriptionTokenMap']['thirdparty_token_id'],'subscription_id'=>$model->subscription_id]);
            }

            return $this->redirect(['view', 'id' => $model->subscription_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing SuperSubscription model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $subscription_id Subscription ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id){
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SuperSubscription model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $subscription_id Subscription ID
     * @return SuperSubscription the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id){
        if (($model = SuperSubscription::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Function for open super subscriber dashboard
    */
    public function actionSuperSubscriberDashboard(){

        $id_employee = Yii::$app->user->identity->id_employee;
        $details = Yii::$app->Common->get_super_subscription_details($id_employee);
        return $this->render('super-subscriber-dashboard', [
           'supersubscriber_details' => $details
        ]);
    }

    /**
     * Function for get list of purchess subscription list
    */ 
    public function actionSubscriptionDetails(){
        $searchModel = new SubscriptionTransactionDetailsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        // if(Yii::$app->request->queryParams){
        //     
        // }
       // echo "<pre>";print_r(Yii::$app->request->queryParams);die;
        $dataProvider->pagination->pageSize=20;
        return $this->render('subscription-details',[
            'searchModel' => $searchModel, 
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Function for get subscription package list
    */
    public function actionPurchaseSubscription(){
        $id_employee = Yii::$app->user->identity->id_employee;
        $result = Yii::$app->Common->get_super_subscription_details($id_employee);
       return $this->render('purchase-subscription',[
            'purchase_details' => $result
       ]);
    }

    /**
     * Function for update razorpay status
    */
    public function actionRazorpayStatus($subscription_id){
        $api = new Api(Yii::$app->params['razorpay_api_key'], Yii::$app->params['razorpay_secret_key']);
        $result =  Yii::$app->db->createCommand("Select * from tbl_subscription_payment_link_details where payment_status = 'issued' and payment_subscription_id = ".$subscription_id." order by `payment_link_id` desc")->queryAll();
        if(!empty($result)){
            foreach($result as $value){
                $invoiceResult = $api->invoice->fetch($value['payment_invoice_id']);
                $random_string = Yii::$app->Common->getrandomalphanum(8);
                $confirmation_number = "carter_".sprintf("%05d", $subscription_id)."_".$random_string;//."_".substr($value['payment_invoice_id'], -4);
                if(isset($invoiceResult) && ($invoiceResult['status'] != 'issued')){
                    if($invoiceResult['payment_id']){
                        $payment_id = $invoiceResult['payment_id'];
                    } else {
                        $payment_id = null;
                    }
                    Yii::$app->db->createCommand("update `tbl_subscription_payment_link_details` set `payment_status` = '".$invoiceResult['status']."' where `payment_link_id` = '".$value['payment_link_id']."' and `payment_invoice_id` = '".$value['payment_invoice_id']."'")->execute();

                    $PayTransactionDetailRes = Yii::$app->db->createCommand("Select * from `tbl_subscription_transaction_details` where `payment_invoice_id` = '".$value['payment_invoice_id']."' and `subscription_id` = ".$subscription_id)->queryAll();

                    $paid_amount = number_format((($invoiceResult['amount_paid'] / 100) / $value['payment_unit']), '2','.','');
                    if(!empty($PayTransactionDetailRes)){
                        foreach($PayTransactionDetailRes as $value){
                            Yii::$app->db->createCommand("update `tbl_subscription_transaction_details` set `payment_transaction_id` = '".$payment_id."', `paid_amount` = '".$paid_amount."',`payment_status` = '".$invoiceResult['status']."' where `subscription_transaction_id` = '".$value['subscription_transaction_id']."'")->execute();
                        }
                    } else {
                        $paid_at = isset($invoiceResult['paid_at']) ? date("Y-m-d H:i:s",$invoiceResult['paid_at']) : NULL;
                        $expire_dt = isset($paid_at) ? date('Y-m-d', strtotime('+1 year', strtotime($paid_at)) ) : '';
                        $subscription_info = Yii::$app->db->createCommand("Select * from tbl_super_subscription where subscription_id = ".$subscription_id)->queryOne();
                        $query = Yii::$app->db->createCommand("insert into tbl_subscription_transaction_details (subscription_id,confirmation_number,payment_transaction_id,paid_amount,redemption_cost,subscription_cost,no_of_usages,payment_status,payment_date,expire_date) VALUES ('".$subscription_id."','".$confirmation_number."','".$payment_id."','".$paid_amount."','".$subscription_info['redemption_cost']."','".$subscription_info['subscription_cost']."','".$subscription_info['no_of_usages']."','".$invoiceResult['status']."','".$paid_at."','".$expire_dt."')")->execute();
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function for get orders list
    */
    public function actionManageOrder(){
        
        $employee_roleId = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->supersubscriberorderssearch(Yii::$app->request->queryParams,$employee_roleId);
      
        $dataProvider->pagination->pageSize=100;
        return $this->render('manage-order', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    }
    public function actionVieworder($id){
        return $this->render('vieworder', [
            'searchModel' => $this->findModelorder($id),
            'model' => $this->findModelorder($id),
           
        ]);
        

    }

    /**
     * Function for create employee
    */
    public function actionCreateEmployee(){
        $model = new Employee();
        $customer_model = new Customer();
        $model->scenario = 'insert';
        if (Yii::$app->request->post()) {
            $id_employee = Yii::$app->user->identity->id_employee;
            $customer_model->name = ucwords($_POST['Employee']['name']);
            $customer_model->email = $_POST['Employee']['email'];
            $customer_model->mobile = $_POST['Employee']['mobile'];
            $customer_model->gender = 0;
            $customer_model->fk_tbl_customer_id_country_code = ($_POST['country_code']) ? $_POST['country_code'] : "95";
            $customer_model->save(false);
            
            // $customer_model->document = $_POST['Employee']['document_proof'];
            // $customer_model->customer_profile_picture = $_POST['Employee']['customer_profile_picture'];

            $result = Yii::$app->Common->get_super_subscription_details($id_employee);
            if(isset($_POST['Employee']['password'])){
                $model->password = sha1($_POST['Employee']['password']);
            }
            $model->name = ucwords($_POST['Employee']['name']);
            $model->mobile = $_POST['Employee']['mobile'];
            $model->email = $_POST['Employee']['email'];
            $model->fk_tbl_employee_id_employee_role = $_POST['Employee']['fk_tbl_employee_id_employee_role'];
            $model->adhar_card_number = $_POST['Employee']['adhar_card_number'];
            $model->date_modified=date('Y-m-d H:i:s');
            $model->subscription_id = $result->subscription_id;
            $model->status = $_POST['Employee']['status'];

            if($model->validate()){ 
                $customer_model = Customer::find()->where(['id_customer'=> $customer_model->id_customer])->one();

                if($_FILES["Employee"]["tmp_name"]['employee_profile_picture'] != NULL){
                    $FolderExits = "uploads/employee_documents";
                    $custFolder = "uploads/customer_profile_picture";
                    $def_filename = time().$_FILES["Employee"]["name"]['employee_profile_picture'];
                    $url = $FolderExits.'/'.$def_filename;
                    $custUrl = $custFolder.'/'.$def_filename;
                    $logo_image = Yii::$app->Common->compress_image($_FILES["Employee"]["tmp_name"]['employee_profile_picture'], $url, 80);
                    $employee_logo_image = Yii::$app->Common->compress_image($_FILES["Employee"]["tmp_name"]['employee_profile_picture'], $custUrl, 80);
                    $model->employee_profile_picture = $def_filename;
                    $customer_model->customer_profile_picture = $def_filename;
                }
                if($_FILES["Employee"]["tmp_name"]['document_proof'] != NULL){
                    $FolderExits = "uploads/employee_documents";
                    $custFolder = "uploads/customer_documents";
                    $def_filename = time().$_FILES["Employee"]["name"]['document_proof'];
                    $url = $FolderExits.'/'.$def_filename;
                    $custUrl = $custFolder.'/'.$def_filename;
                    $logo_image = Yii::$app->Common->compress_image($_FILES["Employee"]["tmp_name"]['document_proof'], $url, 80);
                    $model->document_id_proof = $def_filename;
                    $customer_model->document = $def_filename;
                }
                
                $model->save(false);
                $customer_model->fk_id_employee = $model->id_employee;
                $customer_model->save(false);
                

                if($model->fk_tbl_employee_id_employee_role == 5 || $model->fk_tbl_employee_id_employee_role == 6 || $model->fk_tbl_employee_id_employee_role == 10 || $model->fk_tbl_employee_id_employee_role == 16)
                {        
                    $client['client_id']=base64_encode($model->email.mt_rand(100000, 999999));
                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($model->email.mt_rand(100000, 999999));
                    $client['employee_id']=$model->id_employee;
                    $client['grant_types']='client_credentials';
                    User::addClient($client);
                }
                // return $this->redirect(['view', 'id' => $model->id_employee]);
                return $this->redirect(['employees-list']);
            }
        }
        return $this->render('create-employee', [
            'model' => $model,
            'profile' => '',
            'document' => '',
        ]);
    }

    /**
     * Function for get employee info on view
    */
    public function actionEmployeeView($id){
        $result = Yii::$app->Common->get_super_subscription_details($id);
        $searchModel =  Employee::findOne($id);

        return $this->render('employee-view', [
            'model' => $searchModel,
        ]);
    }


    public function actionEmployeeDelete($id){
        

        Employee::deleteAll('id_employee ='.$id);
        Customer::deleteAll('fk_id_employee ='.$id);
        Yii::$app->db->createCommand()->delete('oauth_clients', ['user_id' => $id])->execute();
        Yii::$app->db ->createCommand()->delete('oauth_clients', ['employee_id' => $id])->execute();
        return $this->redirect(['employees-list']);
    }

    /**
     * Function for get subscriber employee list
    */
    public function actionEmployeesList(){
        $id_employee = Yii::$app->user->identity->id_employee;
        $result = Yii::$app->Common->get_super_subscription_details($id_employee);

        $searchModel = new EmployeeSearch;
        Yii::$app->request->queryParams['EmployeeSearch']['subscription_id'] = $result->subscription_id;
        $dataProvider = $searchModel->searchEmployee(Yii::$app->request->queryParams);

        return $this->render('employee-list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionEmployeeAllocation($id){
        $emp_model = new EmployeeAllocation();

        $confirmation_no = SubscriptionTransactionDetails::find()->where(['subscription_transaction_id' => $id])->one();
        if($emp_model->load(Yii::$app->request->post())){
            $emp_model->save();
            return $this->redirect(['subscription-details']);
        }
        return $this->render('employee-allocation',[
            'model' => $emp_model,
            'confirmation_no' => $confirmation_no,
        ]);
    }

    public function actionCreatePaymentLink(){
        $post = Yii::$app->request->post();
        $payment_link = Yii::$app->Common->createSubscriptionPaymentLink($post['subscription_id'],$post['razorpay_status'],round($post['total_gst_cost']),$post['unit'] ,$post['transaction_id']);
        $result = SubscriptionPaymentLinkDetails::find()->where(['payment_subscription_id' => $post['subscription_id'],'payment_status' => 'issued'])->one();
        $payment_url = $result['payment_short_link'];
        return json_encode(["status" => 1,"payment_url"=>$payment_url]);
    }

    public function actionCreateSubscriptionNumber(){
        $post = Yii::$app->request->post();
        $result = Yii::$app->Common->createZeroSubscriptionPayment($post['subscription_id'],$post['razorpay_status'],0,$post['unit']);
        return json_encode(['status' => 1]);
    }

    /**
     * Function for calculate prices
    */
    public function actionSubscriberPriceCalculation(){
        $status = 0;
        if($_GET['id_customer']){
            $customer_id = $_GET['id_customer'];
            $confirmation_no = $_GET['confirmation_no'];
            $corporate_id = $_GET['corporate_id'];
            $terminal_type = $_GET['terminal_type'];
            $bag_count = isset($_GET['no_of_unit']) ? $_GET['no_of_unit'] : 1;

            $priceResult = Yii::$app->db->CreateCommand("Select * from tbl_subscription_transaction_details where subscription_transaction_id = ".$confirmation_no)->queryOne();
            if(!empty($priceResult)){
                $status = 1;
                $usage_count = ($terminal_type == 1) ? ($bag_count * 2) : ($bag_count * 1);
                $redemption_cost = ($priceResult['redemption_cost'] * $usage_count);
                $gst_price = ($redemption_cost * $priceResult['gst_percent']) / 100;
                $total_price = $redemption_cost + $gst_price;
                $price_breakup = array(
                    "bag_count" => $bag_count,
                    "gst_percent" => isset($priceResult['gst_percent']) ? $priceResult['gst_percent'] : 0,
                    "no_of_usages" => isset($priceResult['no_of_usages']) ? $priceResult['no_of_usages'] : 0,
                    "remaining_usages" => isset($priceResult['remaining_usages']) ? $priceResult['remaining_usages'] : 0,
                    "exhaust_usages" => $usage_count,
                    "per_bag_redemption_cost" => $priceResult['redemption_cost'] ? $priceResult['redemption_cost'] : 0,
                    "redemption_cost" => $redemption_cost,
                    "gst_price" => $gst_price,
                    "total_price" => $total_price,
                );
                $finalResult = array(
                    "price_status" => 1,
                    "redemption_cost" => $redemption_cost,//$priceResult['redemption_cost'],
                    "gst_percent" => $priceResult['gst_percent'],
                    "gst_cost" => $gst_price,
                    "total_cost" => $total_price,
                    "price_breakup" => $price_breakup
                );
            } else {
                $status = 0;
                $price_breakup = array(
                    "bag_count" => $bag_count,
                    "gst_percent" => 0,
                    "no_of_usages" => 0,
                    "remaining_usages" => 0,
                    "exhaust_usages" => 0,
                    "per_bag_redemption_cost" => 0,
                    "redemption_cost" => 0,
                    "gst_price" => 0,
                    "total_price" => 0,
                );
                $finalResult = array(
                    "price_status" => 0,
                    "redemption_cost" => 0,
                    "gst_cost" => 0,
                    "gst_percent" => 0,
                    "total_cost" => 0,
                    "price_breakup" => $price_breakup
                );
            }
        } else {
            $status = 0;
            $price_breakup = array(
                "bag_count" => $bag_count,
                "gst_percent" => 0,
                "no_of_usages" => 0,
                "remaining_usages" => 0,
                "exhaust_usages" => 0,
                "per_bag_redemption_cost" => 0,
                "redemption_cost" => 0,
                "gst_price" => 0,
                "total_price" => 0,
            );
            $finalResult = array(
                "price_status" => 0,
                "redemption_cost" => 0,
                "gst_percent" => 0,
                "total_cost" => 0,
                "gst_cost" => 0,
                "price_breakup" => $price_breakup
            );
        }
        return json_encode(array("status" => $status,"price" => $finalResult));
    }

    /**
     * Function for Search employee
    */
    public function actionSearchEmployee(){
        $customer_details = new Customer();
        $employee_details = new Employee();
        $customer_details->scenario = 'search';
        $model = new Order();
        $role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $id_employee = Yii::$app->user->identity->id_employee;
        $result = Yii::$app->Common->get_super_subscription_details($id_employee);

        if ($customer_details->load(Yii::$app->request->post())) {
            $corporate_id = isset($_POST['Order']['corporate_id']) ? $_POST['Order']['corporate_id'] : 0;
            $search = Yii::$app->request->post()['search'];
            if($search == 'search'){
                $mobile = Yii::$app->request->post()['Customer']['mobile'];
                $employeeInfo = Employee::findOne(['mobile' => $mobile,"subscription_id" => $result->subscription_id,'fk_tbl_employee_id_employee_role' => 18]);
                $customers = Customer::findOne(['mobile' => $mobile,'fk_id_employee' => $employeeInfo->id_employee]);
   
                if($customers){
                    return $this->redirect(['create-super-subscriber-general-order','mobile'=>$mobile,'registered' =>0, 'corporate_id' => $corporate_id,'confirmation_number' => $_POST['Order']['confirmation_number']]);
                }else{
                    return $this->render('search-employee', [
                        'customer_details' => $customers,
                        'model' => $model
                    ]);
                }
            }
            if($search == 'register'){
                $customer   = Yii::$app->request->post()['Customer'];
                $employee   = Yii::$app->request->post()['Employee'];
                $order      = Yii::$app->request->post()['Order'];
                $subscription_id = Yii::$app->db->createCommand("select subscription_id from tbl_subscription_transaction_details where subscription_transaction_id = ".$order['confirmation_number'])->queryOne();

                $emp_model = new Employee;
                $emp_model->name = $employee['name'];
                $emp_model->email = $employee['email'];
                $emp_model->mobile = $employee['mobile'];
                $emp_model->date_created = date('Y-m-d H:i:s');
                $emp_model->date_modified = date('Y-m-d H:i:s');
                $emp_model->subscription_id = isset($subscription_id['subscription_id']) ? $subscription_id['subscription_id'] : "";
                $emp_model->fk_tbl_employee_id_employee_role = 18;
                $emp_model->password = sha1($employee['mobile']);
                if($emp_model->save(false)){
                    $customer_model = new Customer;
                    $customer_model->mobile   = $employee['mobile'];
                    $customer_model->name     = $employee['name'];
                    $customer_model->email    = $employee['email'];
                    $customer_model->gender   = $customer['gender'];
                    $customer_model->fk_id_employee = $emp_model->id_employee;  
                    $customer_model->fk_tbl_customer_id_country_code = ($_POST['country_code']) ? $_POST['country_code'] : "95";

                    if($customer_model->save(false)){
                        $data['name'] = $customer_model->name;
                        //$bookingCustomer_smsContent = 'Dear Customer, Welcome to CarterX! Login to www.carterx.in with registered mobile number to track your order placed by '.$customer_details->name.' under Manage Orders. For all delivery related queries contact our customer support on +91-9110635588. '.PHP_EOL.'CarterX';
                        // User::sendsms('8792509266',$bookingCustomer_smsContent);
                        //User::sendemail($customer_details->email,"Welcome to Carterx",'welcome_customer',$data);

                        // $client['client_id']=base64_encode($employee['email'].mt_rand(100000, 999999));
                        // $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($employee['email'].mt_rand(100000, 999999)); 
                        // $client['user_id']=$customer_model->id_customer;
                        // User::addClient($client);
                        return $this->redirect(['create-super-subscriber-general-order','mobile'=>$employee['mobile'],'registered' =>0, 'corporate_id' => $corporate_id,'confirmation_number' => $_POST['Order']['confirmation_number']]);
                    }
                }
            }
        }
        return $this->render('search-employee', [
            'customer_details' => $customer_details,
            'employee_details' => $employee_details,
            'model' => $model
        ]);
    }

    /**
     * Function for create subscriber order
    */
    public function actionCreateSuperSubscriberGeneralOrder($mobile){
    
        $model['o'] = new Order();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $model['om'] = new OrderMetaDetails();
        $model['sta'] = new State();
        $model['oi'] = new OrderItems();

        $role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $id_employee = Yii::$app->user->identity->id_employee;
        $result = Yii::$app->Common->get_super_subscription_details($id_employee);

        $employees = Employee::findOne(['mobile' => $mobile,"subscription_id" => $result->subscription_id,'fk_tbl_employee_id_employee_role' => 18]);
        $customers = Customer::findOne(['mobile' => $mobile,'fk_id_employee' => $employees->id_employee]);
        
        $model['o']->scenario = 'create_order_genral';
        $model['bw']=new BagWeightType();
        $OrderZoneDetails = new OrderZoneDetails();
        $employee_model = new Employee();
        $setCC = array();
        
        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) ) {//&& Model::validateMultiple([$model['o'], $model['osd']])
            $razorpay_api_key = isset(Yii::$app->params['razorpay_api_key']) ? Yii::$app->params['razorpay_api_key'] : "rzp_test_VSvN3uILIxekzY";
            $razorpay_secret_key = isset(Yii::$app->params['razorpay_secret_key']) ? Yii::$app->params['razorpay_secret_key'] : "Flj35MJPZTJZ0WiTBlynY14k";
            $api = new Api($razorpay_api_key, $razorpay_secret_key);
            $payment_type = isset($_POST['OrderPaymentDetails']['payment_type']) ? ($_POST['OrderPaymentDetails']['payment_type']) : $_POST['payment_type'];

            $primary_email = CorporateDetails::find()->select(['default_email','default_contact'])->where(['corporate_detail_id'=> Yii::$app->request->post()['Order']['corporate_id']])->One();
            if($primary_email['default_email']){
                array_push($setCC,$primary_email['default_email'],Yii::$app->params['customer_email']);
            }

            if(isset($_POST['Order']['fk_tbl_order_id_slot'])){
                $DeliveryRes = Yii::$app->Common->getExpectedDeliveryDateTime($_POST['Order']['delivery_type'],$_POST['Order']['service_type'],$_POST['Order']['fk_tbl_order_id_slot'],date('Y-m-d',strtotime($_POST['Order']['order_date'])));
            }

            $model = new Order();
            if(!empty($DeliveryRes)){
                $model->delivery_datetime = isset($DeliveryRes['delivery_date_time']) ? $DeliveryRes['delivery_date_time'] : "";
                $model->delivery_time_status = isset($DeliveryRes['delivery_status']) ? $DeliveryRes['delivery_status'] : "";
            }
            $corporate_id = Yii::$app->Common->getCorporates($_POST['Order']['corporate_id']);
            $model->corporate_id = $corporate_id->fk_corporate_id;
            // $model->fk_thirdparty_corporate_id = isset($_POST['Order']['corporate_id']) ? $_POST['Order']['corporate_id'] : 0;
            // $model->city_pincode = isset($_POST['Order']['city_pincode']) ? $_POST['Order']['city_pincode'] : (isset($_POST['Order']['validate_pincode']) ? ($_POST['Order']['validate_pincode']) : $_POST['state']['pincode']);
            $model->fk_tbl_order_id_customer = ($_POST['id_customer']) ? $_POST['id_customer'] : '';
            $model->city_id = $_POST['Order']['fk_tbl_airport_of_operation_airport_name_id'];
            $model->fk_tbl_airport_of_operation_airport_name_id=$_POST['Employee']['airport'];
            $model->location=$_POST['Order']['location'];
            $model->sector = $_POST['Order']['sector'];
            $model->weight = $_POST['Order']['weight'];
            $model->service_type = $_POST['Order']['service_type'];
            $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));
            $model->extra_weight_purched = $_POST['Order']['extra_weight_purched'];

            $model->delivery_type = $_POST['Order']['delivery_type'];
            $model->order_transfer = $_POST['Order']['order_transfer'];
            $model->terminal_type = $_POST['Order']['terminal_type'];
            $model->airport_service = isset($_POST['pick_drop_point']) ? $_POST['pick_drop_point'] : 0;

            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = isset($_POST['Order']['fk_tbl_order_id_slot']) ? $_POST['Order']['fk_tbl_order_id_slot'] : 1;
            $model->airport_slot_time = isset($_POST['Order']['airport_slot_time']) ? date("H:i:s", strtotime($_POST['Order']['airport_slot_time'])) : "00:00:00";
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['totalPrice'];//['luggage_price'];

            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $delivery_dates = Yii::$app->Common->selectedSlot($_POST['Order']['fk_tbl_order_id_slot'], $model->order_date, $model->delivery_type);
            
            $model->delivery_date = $delivery_dates['delivery_date'];
            $model->delivery_time = $delivery_dates['delivery_time'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = '7';

            $model->flight_number = $_POST['Order']['flight_number'];
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
            $model->confirmation_number = $_POST['confirmation_number'];
            $model->pnr_number = $_POST['Order']['pnr_number'];
            $model->remaining_usages = $_POST['remainUsages'];
            $model->extra_usages = $_POST['exhaustUsages'];

            if($_POST['remainUsages'] > $_POST['exhaustUsages']){
                $model->usages_used = $_POST['exhaustUsages'];
            } else if($_POST['remainUsages'] < $_POST['exhaustUsages']){
                $model->usages_used = $_POST['remainUsages'];
            }

            if($payment_type == "razorpay"){
                $model->payment_mode_excess = $payment_type;
            } else {
                $model->payment_mode_excess = $payment_type;
            }

            // pincode update in order table
            if(($_POST['Order']['service_type'] == 1) && ($_POST['Order']['delivery_type'] == 1) && ($_POST['Order']['order_transfer'] == 1)){
                $model->pickup_pincode = $_POST['Order']['validate_pincode'];
                $model->drop_pincode = $_POST['Order']['city_pincode'];
            } else if(($_POST['Order']['service_type'] == 1) && ($_POST['Order']['order_transfer'] == 1) && ($_POST['Order']['delivery_type'] == 2)){
                $model->pickup_pincode = $_POST['Order']['city_pincode'];
                $model->drop_pincode = $_POST['Order']['validate_pincode'];
            } else if(($_POST['Order']['service_type'] == 2) && ($_POST['Order']['order_transfer'] == 1) && ($_POST['Order']['delivery_type'] == 1)){
                $model->pickup_pincode = $_POST['State']['pincode'];
                $model->drop_pincode = $_POST['Order']['validate_pincode'];
            } else if(($_POST['Order']['service_type'] == 2) && ($_POST['Order']['order_transfer'] == 1) && ($_POST['Order']['delivery_type'] == 2)){
                $model->pickup_pincode = $_POST['Order']['validate_pincode'];
                $model->drop_pincode = $_POST['State']['pincode'];
            } else if(($_POST['Order']['service_type'] == 1) && ($_POST['Order']['order_transfer'] == 2) && ($_POST['Order']['delivery_type'] == 1)){
                $model->pickup_pincode = $_POST['Order']['validate_pincode'];
            } else if(($_POST['Order']['service_type'] == 1) && ($_POST['Order']['order_transfer'] == 2) && ($_POST['Order']['delivery_type'] == 2)){
                $model->drop_pincode = $_POST['Order']['validate_pincode'];
            } else if(($_POST['Order']['service_type'] == 2) && ($_POST['Order']['order_transfer'] == 2) && ($_POST['Order']['delivery_type'] == 1)){
                $model->pickup_pincode = $_POST['State']['pincode'];
            } else if(($_POST['Order']['service_type'] == 2) && ($_POST['Order']['order_transfer'] == 2) && ($_POST['Order']['delivery_type'] == 2)){
                $model->drop_pincode = $_POST['State']['pincode'];
            }

            if(($_POST['pick_drop_point'] == 1) && ($_POST['Order']['delivery_type'] == 1) && !empty($_POST['airport_static_pincode'])){
                $model->drop_pincode = $_POST['airport_static_pincode'];
            } else if(($_POST['pick_drop_point'] == 1) && ($_POST['Order']['delivery_type'] == 2) && !empty($_POST['airport_static_pincode'])){
                $model->pickup_pincode = $_POST['airport_static_pincode'];
            }

            $model->insurance_price = 0;

            if(isset($_POST['OrderSpotDetails']['pincode']) && !empty($_POST['OrderSpotDetails']['pincode']))
            {
                $pincode_id=PickDropLocation::find()->select('id_pick_drop_location')->where(['pincode'=>$_POST['OrderSpotDetails']['pincode']])->one();
                if($pincode_id){
                   $pincode_id=$pincode_id['id_pick_drop_location'];
                }else{
                    $pincode_id='';
                }
                $model->fk_tbl_order_id_pick_drop_location=$pincode_id;
                $sector = PickDropLocation::findOne(['pincode' => $_POST['OrderSpotDetails']['pincode']]);
                $model->sector_name = ($sector) ? $sector->sector : '';
            }

            $model->payment_method = $payment_type;
            if($payment_type == 'razorpay'){
                // $model->fk_tbl_order_status_id_order_status = 1;
                // $model->order_status = 'Yet to be confirmed';
                $model->fk_tbl_order_status_id_order_status = 2;
                $model->order_status = 'Confirmed';
                
            } else {
                $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'open' : 'Confirmed';
                if(!empty($_POST['total_convayance_amount'])){
                    $model->amount_paid = !empty($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : 0;
                }else {
                    $model->amount_paid = !empty($_POST['Order']['luggage_price']) ? $_POST['Order']['luggage_price'] : 0;
                }
            }

            if($_POST['Order']['service_type'] ==1){
                $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] :null;
                $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) :null;
            }else{
                $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] :null;
                $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) :null;
            }
            $model->date_created = date('Y-m-d H:i:s');
            /*Created by role id*/
            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            $model->created_by = $role_id;
            $model->created_by_name = Yii::$app->user->identity->name;
            $model->corporate_type = Yii::$app->Common->getCorporateType($model->corporate_id);
            // $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save(false)){
                if(($_POST['Order']['delivery_type'] == 2) || (!empty($_POST['convayance_price']))){
                    $outstation_id = isset($_POST['outstation_id']) ? $_POST['outstation_id'] : 0;
                    $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : 0;
                    $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                    $extr_kms = isset($_POST['extr_kms']) ? $_POST['extr_kms'] : 0;
                    $service_tax_amount = isset($_POST['service_tax_amount']) ? $_POST['service_tax_amount'] : 0;
                    $convayance_price = isset($_POST['convayance_price']) ? $_POST['convayance_price'] : 0;
                    $date = date('Y-m-d H:i:s');
                    Yii::$app->db->createCommand("insert into tbl_order_zone_details (orderId,outstationZoneId,cityZoneId,stateId,extraKilometer,taxAmount,outstationCharge,createdOn) values($model->id_order,$outstation_id,$city_name,$state_name,$extr_kms,$service_tax_amount,$convayance_price,'".$date."')")->execute();
                }    
                $thirdpartymapping = new ThirdpartyCorporateOrderMapping();
                $thirdpartymapping->thirdparty_corporate_id = $_POST['Order']['corporate_id'];
                $thirdpartymapping->order_id = $model->id_order;
                $thirdpartymapping->stateId = ($_POST['State']['idState']) ? $_POST['State']['idState'] : '';
                $thirdpartymapping->cityId = $_POST['Order']['fk_tbl_airport_of_operation_airport_name_id'];
                $thirdpartymapping->created_on = date('Y-m-d H:i:s');
                $thirdpartymapping->save();

                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                $model->save(false);
                if(!empty($_FILES['Order']['name']['ticket'])){
                    $up = $employee_model->actionFileupload('ticket',$model->id_order);
                }
                if(!empty($_FILES['Order']['name']['someone_else_document'])){
                    $up = $employee_model->actionFileupload('someone_else_document',$model->id_order);
                }

                $order_payment_details = new OrderPaymentDetails();
                $order_payment_details->id_order = $model->id_order;
                $order_payment_details->payment_type = $payment_type;
                $order_payment_details->id_employee = Yii::$app->user->identity->id_employee;
                if($payment_type == 'razorpay'){
                    $order_payment_details->payment_status = 'Not paid';
                }else{
                    $order_payment_details->payment_status = 'Success';
                }
                $order_payment_details->amount_paid = isset($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : $_POST['Order']['luggage_price'];
                $order_payment_details->value_payment_mode = 'Order Amount';
                $order_payment_details->date_created= date('Y-m-d H:i:s');
                $order_payment_details->date_modified= date('Y-m-d H:i:s');
                $order_payment_details->save();

                // 
                if(!empty($_POST['Order']['no_of_units'])){
                    for($i=0; $i < $_POST['Order']['no_of_units']; $i++){
                        $model_item = new OrderItems();
                        $model_item->fk_tbl_order_items_id_order = $model->id_order;
                        $model_item->fk_tbl_order_items_id_luggage_type = 2;
                        $model_item->fk_tbl_order_items_id_luggage_type_old = 2;
                        $model_item->item_price = ($_POST['Order']['totalPrice'] / $_POST['outstationExhaustUsages']);
                        $model_item->bag_type = "bag".$i;
                        $model_item->new_luggage = 0;
                        $model_item->save(false);
                    }
                }

                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                /*order history end*/

                /*order total table*/
                $this->updateordertotal($model->id_order, $_POST['Order']['luggage_price'], $_POST['Order']['service_tax_amount'], $model->insurance_price);
                /*order total*/

                //code OrderItems...
                $luggage_det = LuggageType::find()->where(['corporate_id'=>$_POST['Order']['corporate_id']])->one();

                if(!empty($_POST['OrderItems'])){
                    if($_POST['price_array']){
                        $item_price    = $_POST['price_array'];
                        $decoded_data  = json_decode($item_price);
                    }else{
                        $decoded_data = '';
                    }
                    foreach ($_POST['OrderItems'] as $key => $items) {
                        //print_r($items);exit;
                        $order_items = new OrderItems();
                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                        $order_items->fk_tbl_order_items_id_luggage_type_old=$items['fk_tbl_order_items_id_luggage_type'];
                        $order_items->item_price = $decoded_data->bag1 ? $decoded_data->bag1 : 0;
                        $order_items->bag_weight = 0;
                        $order_items->bag_type = '';
                        $order_items->save();
                    }
                }
                if($_POST['pick_drop_point'] == 2){
                    if($_POST['Order']['delivery_type'] == 2 || $_POST['Order']['delivery_type'] == 1){
                        if($_POST['Order']['order_transfer'] == 1){
                            $orderMetaDetails = new OrderMetaDetails();
                            $orderMetaDetails->stateId = 0;
                            $orderMetaDetails->orderId = $model->id_order;
                            $orderMetaDetails->pickupPersonName = $_POST['OrderMetaDetails']['pickupPersonName'];
                            $orderMetaDetails->pickupPersonNumber = $_POST['OrderMetaDetails']['pickupPersonNumber'];
                            $orderMetaDetails->pickupPersonAddressLine1 = $_POST['OrderMetaDetails']['pickupPersonAddressLine1'];
                            $orderMetaDetails->pickupPersonAddressLine2 = $_POST['OrderMetaDetails']['pickupPersonAddressLine2'];

                            $orderMetaDetails->pickupArea = $_POST['OrderMetaDetails']['pickupArea'];
                            $orderMetaDetails->pickupPincode = $_POST['OrderMetaDetails']['pickupPincode'];
                            $orderMetaDetails->pickupLocationType = $_POST['OrderMetaDetails']['pickupLocationType'];
                            $orderMetaDetails->pickupBuildingNumber = isset($_POST['OrderMetaDetails']['pickupBuildingNumber']) ? $_POST['OrderMetaDetails']['pickupBuildingNumber']:null;

                            $orderMetaDetails->dropBuildingNumber = isset($_POST['OrderMetaDetails']['dropBuildingNumber']) ? $_POST['OrderMetaDetails']['dropBuildingNumber']:null;

                            $orderMetaDetails->dropPersonName = $_POST['OrderMetaDetails']['dropPersonName'];
                            $orderMetaDetails->dropPersonNumber = $_POST['OrderMetaDetails']['dropPersonNumber'];
                            $orderMetaDetails->dropPersonAddressLine1 = $_POST['OrderMetaDetails']['dropPersonAddressLine1'];
                            $orderMetaDetails->dropPersonAddressLine2 = $_POST['OrderMetaDetails']['dropPersonAddressLine2'];
                            $orderMetaDetails->droparea = $_POST['OrderMetaDetails']['droparea'];
                            $orderMetaDetails->dropPincode = $_POST['OrderMetaDetails']['dropPincode'];

                            $orderMetaDetails->pickupLocationType = $_POST['OrderMetaDetails']['pickupLocationType'];

                            $orderMetaDetails->pickupBusinessName = $_POST['OrderMetaDetails']['pickupBusinessName'];
                            $orderMetaDetails->pickupMallName = $_POST['OrderMetaDetails']['pickupMallName'];
                            $orderMetaDetails->pickupStoreName = $_POST['OrderMetaDetails']['pickupStoreName'];

                            if($_POST['OrderMetaDetails']['pickupLocationType'] == 2){
                                $orderMetaDetails->pickupHotelType = $_POST['OrderMetaDetails']['pickupHotelType'];
                                $orderMetaDetails->PickupHotelName = $_POST['OrderMetaDetails']['PickupHotelName'];
                            }

                            //drop
                            $orderMetaDetails->dropLocationType = $_POST['OrderMetaDetails']['dropLocationType'];
                            $orderMetaDetails->dropBusinessName = $_POST['OrderMetaDetails']['dropBusinessName'];
                            $orderMetaDetails->dropMallName = $_POST['OrderMetaDetails']['dropMallName'];
                            $orderMetaDetails->dropStoreName = $_POST['OrderMetaDetails']['dropStoreName'];

                            if($_POST['OrderMetaDetails']['dropLocationType'] == 2){
                                $orderMetaDetails->dropHotelType = $_POST['OrderMetaDetails']['dropHotelType'];
                                $orderMetaDetails->dropHotelName = $_POST['OrderMetaDetails']['dropHotelName'];
                            }

                            $orderMetaDetails->save(false);
                            
                            $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'RazorPay', '');

                        }else{
                            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];
                            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
                            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];

                            $model->flight_number = $_POST['Order']['flight_number'];

                            if($_POST['Order']['service_type'] ==1){
                                $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] :null;
                                $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) :null;
                            }else{
                                $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] :null;
                                $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) :null;
                            }

                            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));


                            //code OrderSpotDetails...
                            $order_spot_details = new OrderSpotDetails();
                            $order_spot_details->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $order_spot_details->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                            $order_spot_details->person_name = $_POST['OrderSpotDetails']['person_name'];
                            $order_spot_details->person_mobile_number = $_POST['OrderSpotDetails']['person_mobile_number'];
                            $order_spot_details->mall_name = $_POST['OrderSpotDetails']['mall_name'];
                            $order_spot_details->store_name = $_POST['OrderSpotDetails']['store_name'];
                            $order_spot_details->business_name = $_POST['OrderSpotDetails']['business_name'];
                            if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                $order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'];
                                $order_spot_details->hotel_name = $_POST['OrderSpotDetails']['hotel_name'];
                            }

                            if($model->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $order_spot_details->assigned_person = 1;
                            }

                            $model->save(false);

                            $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])? $_POST['OrderSpotDetails']['pincode']:null;
                            $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                            $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;

                            if($order_spot_details->save(false)){
                                if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                    $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                                }
                            }
                        }
                    }
                } else {
                    $model->pickup_dropoff_point = $_POST['address'];
                    $model->save(false);
                }

                if($payment_type == 'razorpay'){
                    // $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'yet_to_be_confirmed', '');
                    // if(!empty($_POST['total_convayance_amount'])){
                    //     $amount_paid = !empty($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : 0;
                    // }else {
                    //     $amount_paid = !empty($_POST['Order']['luggage_price']) ? $_POST['Order']['luggage_price'] : 0;
                    // } 

                    Yii::$app->Common->updateSubscriptionTransaction($model->id_order,$_POST['transaction_id'],$_POST['razorpay_link']);
                    // $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                    // $razorpay = Yii::$app->Common->createRazorpayLink($_POST['travel_email'], $_POST['cutomer_mobile'], $amount_paid, $model->id_order, $role_id);
                } else { 
                    // $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                }

                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                // remove Usages form reamining usages
                $removing_usages = isset($_POST['outstationExhaustUsages']) ? $_POST['outstationExhaustUsages'] : $_POST['Order']['no_of_units'];
                $remaining_usages = Yii::$app->Common->updateConfirmationNumber($_POST['confirmation_number'],$_POST['Order']['terminal_type'],$removing_usages,$_POST['Order']['delivery_type']);

                // subscription email send here
                    $new_order_details = Order::getorderdetails($model->id_order);
                    $model1['order_details']=$new_order_details;
                    $model1['order_details']['subscription_details'] = Yii::$app->Common->getSubscriptionDetails($model1['order_details']['order']['confirmation_number']);

                    $customer_number = $new_order_details['order']['c_country_code'].$new_order_details['order']['customer_mobile'];
                    $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                    $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
                    $customers  = Order::getcustomername($model->travell_passenger_contact);
                    $customer_name = ($customers) ? $customers->name : '';
                    $flight_number = " ".$_POST['Order']['flight_number'];

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
                    User::sendcnfemail($customer_email,"CarterX Confirmed Subscription #".strtoupper($model1['order_details']['subscription_details']['confirmation_number']),'sub_cnf_email',$model1,$attachment_cnf,array_filter($emailSubscriberTo));      

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
                    Yii::$app->Common->subscriptionSmsSent('exhaustion_of_subscription',$sms_data,array_filter(array_unique($mobile_arr))); //Exhaustion sms
                    // Exhaustion of Subscription: confirmation update email
                    $model1['order_details']['order']['exhaust'] = 1;
                    $file_name = "subscription_confirmation_".time().'_'.$model1['order_details']['order']['order_number'].".pdf";
                    $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($model1,'subscription_cnf_template',$file_name);
                    User::sendcnfemail($customer_email,"CarterX Exhaustion of Subscription: Confirmation update for Subscription #".strtoupper($model1['order_details']['subscription_details']['confirmation_number']),'sub_cnf_email',$model1,$attachment_cnf,array_filter(array_unique(array_merge($emailSubscriberTo,$emailCustomerCareTo))));
                }

                // return $this->redirect(['update-kiosk-corporate-form', 'id' => $model->id_order, 'mobile' => $_POST['Order']['travell_passenger_contact']]);
                return $this->redirect(['manage-order']);
            }
        } else {
            // return $this->render('super_subscriber_general_order_create', [
            //     'model' => $model,
            //     'customer_details' => $customers,
            //     'employee_model' =>$employee_model,
            //     'regionModel' =>$model['re'],
            // ]);
            return $this->render('subscriber_general_order_create', [
                'model' => $model,
                'customer_details' => $customers,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
            // return $this->render('create_super_subscriber_general_order', [
            //     'model' => $model,
            //     'customer_details' => $customers,    
            //     'employee_model' =>$employee_model,
            //     'regionModel' =>$model['re'],
            // ]);
        }
    }

    /**
     * Function for get slot times
    */
    public function actionSelectSlotTimeCorporate($serviceTypeID, $order_date, $order_type = false){
        $date_now = date("Y-m-d");
        if($serviceTypeID==1){
            $rows=\app\models\Slots::find()
                ->where(['id_slots'=>[1,2,3,7,9]])
                ->all();
            $curDateTime = date("Y-m-d H:i:s");  
            $selected_date = date("Y-m-d", strtotime($order_date));
            echo "<option value=''>Select Slot....</option>";
            if(count($rows)>0){
                foreach($rows as $row){
                    if($order_type == 1){
                        $date_time = $selected_date.' '.$row->slot_start_time;
                        $myDate = date("Y-m-d H:i:s", strtotime($date_time));
                        if($myDate > $curDateTime){
                            echo "<option value='$row->id_slots'>$row->time_description $row->description</option>";
                        }
                    }else{
                        echo "<option value='$row->id_slots'>$row->time_description $row->description</option>";
                    }
                }
            }else{
                echo "<option value=''>No Slot Time</option>";
            }
        }elseif ($serviceTypeID==2){
            $rows=\app\models\Slots::find()
                ->where(['id_slots'=>[4,5]])
                ->all();
            echo "<option value=''>Select Slot ....</option>";
            if(count($rows)>0){
                $i = 0;
                foreach($rows as $row)
                {
                    echo "<option value='$row->id_slots'>$row->time_description</option>";
                }
            }else{
                echo "<option value=''>NO Slot</option>";
            }
        }
    }

    /**
     * Function for 
    */
    public function actionSelectDepArrDate($serviceTypeID, $order_date){
        if($serviceTypeID==1){
            $order_date = date('Y-m-d',strtotime($order_date));
            $order_date1 = date('Y-m-d', strtotime('+1 day', strtotime($order_date)));

            echo "<option value='$order_date'>".date($order_date)."</option>";
            echo "<option value='$order_date1'>".date($order_date1)."</option>";
        }else{
            $order_date = date('Y-m-d',strtotime($order_date));
            echo "<option value='$order_date'>".date($order_date)."</option>";
        }
    }

    /**
     * Function for get airport list
    */
    public function actionGetAssignAirportList($regionId){
        $employeeId = Yii::$app->user->identity->id_employee;
        $roleId = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        if(($roleId == 17) && !empty($regionId)){
            $empairport= Yii::$app->db->createCommand("SELECT t_ao.airport_name_id as fk_tbl_airport_id,t_ao.airport_name from tbl_airport_of_operation t_ao where t_ao.fk_tbl_city_of_operation_region_id = '".$regionId."'")->queryall();
            echo "<option >Select your airport</option>";
            foreach($empairport as $value){
                echo "<option value='".$value['fk_tbl_airport_id']."'>".$value['airport_name']."</option>";
            }
        }else if ($employeeId) {
            $empairport= Yii::$app->db->createCommand("SELECT t_cea.id_employee_airport,t_cea.fk_tbl_airport_id,t_ao.airport_name from tbl_corporate_employee_airport t_cea right join tbl_airport_of_operation t_ao ON t_ao.airport_name_id = t_cea.fk_tbl_airport_id where t_ao.fk_tbl_city_of_operation_region_id = '".$regionId."' and t_cea.fk_tbl_employee_id ='".$employeeId."'")->queryall();
            echo "<option >Select your airport</option>";
            foreach($empairport as $value){
                echo "<option value='".$value['fk_tbl_airport_id']."'>".$value['airport_name']."</option>";
            }
        }else{
            return '';
        }
    }

    /**
     * Function for 
    */
    public function updateordertotal($id_order, $luggage_price, $service_tax, $insurance_price){
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

    public function actionCreateOrderPaymentLink(){
        $_POST = Yii::$app->request->post();
        $role_id = 17;//Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $res = Yii::$app->Common->createSubscriberRazorpayLink($_POST['customer_email'], $_POST['customer_number'], $_POST['total_amount'], $_POST['confirmation_number'], $role_id,$_POST['transaction_id'],$_POST['pay_status']);
        return json_encode(array("status" => 1,"result" => $res));
    }

    public function actionCheckPaymentStatus(){
        $_POST = Yii::$app->request->post();
        echo "<prE>";print_r($_POST);die;
    }

    public function actionSelectOrderTransfer($subscription_id){
        $rows=\app\models\SuperSubscription::find()
                ->select(['transfer_type'])
                ->where(['subscription_id'=>$subscription_id])
                ->one();
            echo "<option value=''>Select Order Transfer</option>";
        if(!empty($rows)){
            if($rows->transfer_type == 1){
                echo "<option value='$rows->transfer_type'>City Transfer</option>";
            }elseif ($rows->transfer_type == 2) {
                echo "<option value='$rows->transfer_type'>Airport Transfer</option>";
            }elseif ($rows->transfer_type == 3) {
                echo "<option value='1'>City Transfer</option>";
                echo "<option value='2'>Airport Transfer</option>";
            }
        }else{
            echo "<option value=''>No Order Transfer</option>";
        }
    }

    public function actionGetPickDropAddress(){
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $service_type = ($post['service_type'] == 1) ? "arrival" : "departure";
            $result = Yii::$app->db->createCommand("select * from `tbl_departure_arrival_address_point` `add` LEFT JOIN `tbl_departure_arrival_airport_mapping` `map` ON `map`.`pickdrop_address_id` = `add`.`pick_drop_id` where `map`.`airport_id` = '".$post['airport_id']."' and `add`.`pick_drop_type` ='".$service_type."'")->queryAll();

            echo "<option value=''> Select Address </option>";
            if(!empty($result)){
                foreach($result as $value){
                    echo "<option value=".$value['pick_drop_id'].">".$value['pick_drop_address']."</option>";
                }
            }else{
                echo "<option value=''> No Address </option>";
            }
        } else {
            echo "<option value=''> No Address </option>";
        }

    }

    public function actionGetOutstationPrice(){
        $post = Yii::$app->request->post();
        if(isset($post)){
            $validate_pincode = $post['validate_pincode'];
            $airportId = isset($post['airportId']) ? $post['airportId'] : 0;
            if($post['transferType'] == 2){
                $res = Yii::$app->db->createCommand("select * from tbl_airport_of_operation where airport_name_id = ".$airportId)->queryOne();
                if(!empty($res)){
                    $validate_pincode = $res['airport_pincode'];
                } else {
                    $validate_pincode = "";
                }
            }
            $result = Yii::$app->Common->getOutstationCalculation($post['tokenNo'],$post['confirmationId'],$post['noOfBages'],$post['terminalType'],$post['state_pincode'],$validate_pincode);
            return json_encode(array(
                "status" => true,
                "message" => "Success",
                "result" => $result
            ));
        } else {
            return json_encode(array(
                "status" => false,
                "message" => "Failed",
                "result" => array()
            ));
        }

    }

    /**
     * Function for check pincode for subscription orders
    */
    public function actionCheckPincode(){
        $post = Yii::$app->request->post();
        $headers = Yii::$app->request->getHeaders();
        $response = array();
        if(isset($post)){
            $pincode = $post['pincode'];
            if((strlen($pincode) > 6) || (strlen($pincode) < 6)){
                return json_encode(['status'=>0, 'message'=>"Please enter correct pincode."]);
            }

            if(!empty($post['region'])){
                $region_name = Yii::$app->db->createCommand("SELECT region_name,region_pincode as pincode FROM `tbl_city_of_operation` WHERE `id`=".$post['region'])->queryColumn();
            } else if(!empty($post['airport'])) {
                $region_name = Yii::$app->db->createCommand("SELECT c.region_name,a.airport_pincode as pincode FROM `tbl_city_of_operation` c left join `tbl_airport_of_operation` a ON a.fk_tbl_city_of_operation_region_id = c.id where `airport_name_id`=".$post['airport'])->queryOne();
            }

            if(!empty($region_name)){
                if($pincode == $region_name['pincode']){
                    return json_encode(['status'=>0,'message'=>"Please enter another pincode."]);
                }

                $postalResult = Yii::$app->Common->getPostalInfo($pincode);

                $distance = round(Yii::$app->Common->getDistance($region_name['pincode'],$post['pincode'],'KM'));
                if($distance < 60){
                    $orderType = 1;//local order type
                } else {
                    $orderType = 2;//outstation order type
                }

                if(empty($postalResult)){
                    $response = array('status'=>0,'id' => $post['id'],'orderType' => 0,"default_pincode"=>"","message" => "Failed!");
                } else {
                    $response = array('status'=>1,'id'=>$post['id'],'orderType' => $orderType,"default_pincode"=> $region_name['pincode'],'message'=>"Success");
                }
            } else {
                $response = array('status' => 0, 'id' => $post['id'],'orderType' => 0,"default_pincode"=>"","message" => "Failed!");
            }
        }
        return json_encode($response);die;
    }

    public function actionGetRegionName(){
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $airport_id = $post['airport_id'];
            $result = Yii::$app->db->createCommand("SELECT * from `tbl_city_of_operation` `city` LEFT JOIN `tbl_airport_of_operation` `airport` ON `city`.`id` = `airport`.`fk_tbl_city_of_operation_region_id` where `airport`.`airport_name_id`=".$airport_id)->queryOne();
            if(!empty($result)){
                return json_encode(array("status" => 1, "message"=>"success !", "result"=>$result));
            } else {
                return json_encode(array("status" => 0, "message"=>"failed !"));
            }
        }
    }

    public function actionGetCountryCode(){
        $result = CountryCode::find()->all();
        if(!empty($result)){
            echo "<option > Select Country COde</option>";
            foreach($result as $val){
                $selected = ($val['id_country_code'] == 95) ? 'selected' : '';
                echo "<option value='".$val['id_country_code']."' $selected>+ ".$val['country_code']." ( ".$val['country_name']." )</option>";
            }
        } else {
            echo "<option > No Country Code Available</option>";
        }
    }

    protected function findModelorder($id)
    {
        if (($model = Order::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionUpdateSubscriptionUseage(){
        $postdata = $_POST;
        $return_array =array();
        $usages_model = SubscriptionTransactionDetails::find()->where( ['subscription_transaction_id'=>$postdata['subscription_transaction_id']] )->one();
        if(!empty($usages_model)){
            $remaining_usages = ($usages_model['remaining_usages']) ? $usages_model['remaining_usages'] : 0;
            $usages_model['remaining_usages'] = $remaining_usages + $postdata['usages_count'];
            $usages_model['add_usages_status'] = "disable";
            $usages_model->save(false);
            return json_encode(["status" => true, "message" => "useage updated successfully"]);
        } else {
            return json_encode(['status' => false, "message" => "Something went wrong"]);
        }
    }

    public function actionGetSubscriptionDetails(){
        $subscription_transaction_id = $_POST['subscription_transaction_id'];
        if($subscription_transaction_id){
            $res = Yii::$app->db->CreateCommand("Select * from tbl_subscription_transaction_details where subscription_transaction_id = ".$subscription_transaction_id)->queryOne();
            return json_encode(['status' => true, "result" => $res]);
        } else {
            return json_encode(['status' => false]);
        }
    }
}
