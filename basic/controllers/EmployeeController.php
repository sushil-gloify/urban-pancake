<?php

namespace app\controllers;

use Yii;
use app\models\Employee;
use yii\helpers\Json;
use app\models\EmployeeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use app\models\User;
use yii\filters\AccessControl;
use app\models\Order;
use app\models\Customer;
use Razorpay\Api\Api;
use app\models\CorporateDetailsSearch;
use app\models\OrderPaymentDetails;
use app\models\PorterxAllocations;
use app\models\OrderImages;
use app\models\OrderSpotDetails;
use app\api_v2\v2\models\OrderOffers;
use app\api_v3\v3\models\OrderMetaDetails;
use app\api_v3\v3\models\State;
use app\models\ThirdpartyCorporate;
use app\api_v3\v3\models\OrderZoneDetails;
use app\models\OrderItems;
use app\models\OrderGroup;
use app\models\OrderHistory;
use app\models\CorporateDetails;
use app\models\LuggageType;
use app\models\BagWeightType;
use app\models\MallInvoices;
use app\models\Slots;
use app\models\LocationTracking;
use app\models\AirportOfOperation;
use app\models\CityOfOperation;
use yii\helpers\ArrayHelper;
use app\models\EmployeeAirportRegion;
use\app\models\PickDropLocation;
use app\api_v3\v3\models\DeliveryServiceType;
use app\api_v3\v3\models\ThirdpartyCorporateOrderMapping;
use app\api_v3\v3\models\CorporateUser;
use app\api_v3\v3\models\CorporateEmployeeAirport;
use app\api_v3\v3\models\CorporateEmployeeRegion;
use app\api_v3\v3\models\FinserveTransactionDetails;
use app\api_v3\v3\models\WebhookLog;
use app\api_v3\v3\models\CorporateLuggagePriceDetails;
use app\api_v2\v2\models\OrderGroupOffer;
use app\api_v2\v2\models\OrderPromoCode;
use app\components\DelhiAirport;
use app\models\SubscriptionUserMapping;
//use app\api_v2\v2\models\OrderOffers;

use yii\base\Model;
date_default_timezone_set("Asia/Kolkata");
/**
 * EmployeeController implements the CRUD actions for Employee model.
 */
class EmployeeController extends Controller
{
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

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                            'class' => \app\components\AccessRule::className(),
                        ],
                'only' => ['index','create','view','update','delete','selected-slot'],
                'rules' => [
                    [
                        'actions' => ['index','view','selected-slot'],
                        'allow' => true,
                        'roles' => [3,4,10,11],
                    ],
                    [
                        'actions' => ['update','delete','create','index','view','selected-slot'],
                        'allow' => true,
                        'roles' => [1,2,10,11],
                    ],

                ],
            ],
        ];
    }

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
    /**
     * Lists all Employee models.
     * @return mixed
     */
    public function actionIndex()
    {

        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Employee model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionDelhiCron(){
        // $orders = Yii::$app->db->createCommand("SELECT o.id_order FROM tbl_order o  where DATE(o.order_date) = '2020-01-20' AND id_order != 29961 AND corporate_id IN (37,43,44,45,46) AND order_status != 'Rescheduled' AND order_status != 'Closed' AND order_status != 'Cancelled' AND order_status != 'Undelivered' ")->queryAll();
        $orders = Yii::$app->db->createCommand("SELECT o.id_order FROM tbl_order o where id_order IN(33706, 33911) ")->queryAll();
        // echo "<pre>";print_r($orders);exit;
        if($orders){
            foreach ($orders as $key => $value) {
                Yii::$app->queue->push(new DelhiAirport([
                    'order_id' => $value['id_order'],
                    'order_status' => 'confirmed'
                ]));
            }
        }
    }

    /**
     * Creates a new Employee model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    { 

        $model = new Employee();
        $employeeAirportRegion = new EmployeeAirportRegion();
         $employeeCountryMapping = new \app\api_v3\v3\models\EmployeeCountryMapping();
        $countryList= \app\models\CountryCode::find()->where(['status'=>1])->all();
        $countrylist=ArrayHelper::map($countryList,'id_country_code','country_name');

        $airlineList = \app\api_v3\v3\models\CreateAirline::find()->where(['status'=>1])->all();
        $airlineList=ArrayHelper::getColumn($airlineList,'corporate_id');  

         $CorporateName = \app\models\CorporateDetails::find() 
                   ->select(['corporate_detail_id','name'])->where(['corporate_detail_id'=>$airlineList])->all();
                   
        $airlineList=ArrayHelper::map($CorporateName,'corporate_detail_id','name');


        $stationList = \app\api_v3\v3\models\Stations::find()->where(['status'=>1])->all();
        $stationList=ArrayHelper::map($stationList,'station_id','station_name'); 

        $model->scenario = 'insert';
        if ($model->load(Yii::$app->request->post())) {
            //print_r($_POST);exit;
            if(isset($_POST['Employee']['password'])){
                $model->password = sha1($_POST['Employee']['password']);
            }

            $model->date_modified=date('Y-m-d H:i:s');

            //return $this->redirect(['view', 'id' => $model->id_employee]);
            $model->status = $_POST['Employee']['status'];
            if($model->validate())
            { //print_r($model);exit;

            $model->save();
            if($_POST['EmployeeAirportRegion']['fk_tbl_airport_of_operation_airport_name_id']){
                 foreach ($_POST['EmployeeAirportRegion']['fk_tbl_airport_of_operation_airport_name_id'] as $airport) {
            $employee_region = new EmployeeAirportRegion();
            $employee_region->fk_tbl_airport_of_operation_airport_name_id=$airport;
           // $employee_region->fk_tbl_city_of_operation_region_name_id=$_POST['Employee']['region'];
                 $employee_region->fk_tbl_employee_id=$model->id_employee;
                 $employee_region->save();
                }
            }

            if($model->fk_tbl_employee_id_employee_role == 16){
                $emploeeCountryModel = new \app\api_v3\v3\models\EmployeeCountryMapping();
                $emploeeCountryModel->fk_employee_id = $model->id_employee;
                $emploeeCountryModel->fk_country_id = $_POST['Employee']['tman_country'];
                $emploeeCountryModel->fk_station_id = $_POST['Employee']['station'];
                $emploeeCountryModel->fk_airline_id = $_POST['Employee']['airline'];
                $emploeeCountryModel->save(false);
            }
         
                // $model = new Employee();
                if($model->fk_tbl_employee_id_employee_role == 5 || $model->fk_tbl_employee_id_employee_role == 6 || $model->fk_tbl_employee_id_employee_role == 10 || $model->fk_tbl_employee_id_employee_role == 16)
                {        
                    $client['client_id']=base64_encode($model->email.mt_rand(100000, 999999));
                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($model->email.mt_rand(100000, 999999));
                    //print_r($client['client_secret']);exit;
                    $client['employee_id']=$model->id_employee;
                   $client['grant_types']='client_credentials';
                    User::addClient($client);
                           }  //print_r($client['client_id']);exit;
                return $this->redirect(['view', 'id' => $model->id_employee]);
            }
        }
        return $this->render('create', [
            'model' => $model,
            'profile' => '',
            'document' => '',
            'employeeAirportRegion' => $employeeAirportRegion,
            'countrylist'=>$countrylist,
            'employeeCountryMapping'=>$employeeCountryMapping,
            'airlineList'=>$airlineList,
            'stationList'=>$stationList,
        ]);

    }

    /**
     * Creates a new Employee model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateThirdPartyEmployee()
    {
        $model = new Employee();
        $employeeAirport = new CorporateEmployeeAirport();
        $employeeRegion = new CorporateEmployeeRegion();

        $model->scenario = 'third_corporate';
        if ($model->load(Yii::$app->request->post())) {
            $employee_id=Yii::$app->user->identity->id_employee;
            if(isset($_POST['Employee']['password'])){
                $model->password = sha1($_POST['Employee']['password']);
            }
            $model->created_by = $employee_id;
            $model->date_modified=date('Y-m-d H:i:s');
            $model->status = $_POST['Employee']['status'];
            if($model->validate())
            {
                $model->save();
                // Insert maped airport ids here
                if($_POST['CorporateEmployeeAirport']['fk_tbl_airport_id']){
                     foreach ($_POST['CorporateEmployeeAirport']['fk_tbl_airport_id'] as $airport) {
                        $employee_airport = new CorporateEmployeeAirport();
                        $employee_airport->fk_tbl_airport_id=$airport;
                        $employee_airport->fk_tbl_employee_id=$model->id_employee;
                        $employee_airport->save(false);
                    }
                }else{
                    $airport_id = Yii::$app->Common->getAirportIds(Yii::$app->user->identity->id_employee);
                    foreach ($airport_id as $airport) {
                        $employee_airport = new CorporateEmployeeAirport();
                        $employee_airport->fk_tbl_airport_id=$airport;
                        $employee_airport->fk_tbl_employee_id=$model->id_employee;
                        $employee_airport->save(false);
                    }
                }
                // Insert maped region ids here
                if($_POST['CorporateEmployeeRegion']['fk_tbl_region_id']){
                     foreach ($_POST['CorporateEmployeeRegion']['fk_tbl_region_id'] as $region) {
                        $employee_region = new CorporateEmployeeRegion();
                        $employee_region->fk_tbl_region_id=$region;
                        $employee_region->fk_tbl_employee_id=$model->id_employee;
                        $employee_region->save();
                    }
                }else{
                    $region_id = Yii::$app->Common->getRegionIds(Yii::$app->user->identity->id_employee);
                    foreach ($region_id as $region) {
                        $employee_region = new CorporateEmployeeRegion();
                        $employee_region->fk_tbl_region_id=$region;
                        $employee_region->fk_tbl_employee_id=$model->id_employee;
                        $employee_region->save();
                    }
                }
                // Insert maped corporate ids here
                if(isset($_POST['Employee']['corporate_id'])){
                    if(is_array($_POST['Employee']['corporate_id'])){
                        foreach($_POST['Employee']['corporate_id'] as $value){
                            $corporate_user = new CorporateUser();
                            $corporate_user->fk_tbl_employee_id = $model->id_employee;
                            $corporate_user->corporate_id   =  $value;
                            $corporate_user->status   = 1;
                            $corporate_user->save();
                        }
                    }else {
                        $corporate_user = new CorporateUser();
                        $corporate_user->fk_tbl_employee_id = $model->id_employee;
                        $corporate_user->corporate_id   =  $_POST['Employee']['corporate_id'];
                        $corporate_user->status   = 1;
                        $corporate_user->save();
                    }
                }else{
                    $corporate_id = Yii::$app->Common->getCorporateId(Yii::$app->user->identity->id_employee);
                    $corporate_user = new CorporateUser();
                    $corporate_user->fk_tbl_employee_id = $model->id_employee;
                    $corporate_user->corporate_id   =  $corporate_id;
                    $corporate_user->status   = 1;
                    $corporate_user->save();
                }
                // Inert maped subscription ids here
                if(isset($_POST['Employee']['subscription_id'])){
                    if(is_array($_POST['Employee']['subscription_id'])){
                        foreach($_POST['Employee']['subscription_id'] as $value){
                            $subscription_user = new SubscriptionUserMapping();
                            $subscription_user->fk_id_employee = $model->id_employee;
                            $subscription_user->fk_subscription_id   =  $value;
                            $subscription_user->status = 1;
                            $subscription_user->fk_employee_role_id = $_POST['Employee']['fk_tbl_employee_id_employee_role'];
                            $subscription_user->create_date = date("Y-m-d H:i:s");
                            $subscription_user->save();
                        }
                    }else {
                        $subscription_user = new SubscriptionUserMapping();
                        $subscription_user->fk_id_employee = $model->id_employee;
                        $subscription_user->fk_subscription_id   =  $_POST['Employee']['subscription_id'];
                        $subscription_user->status = 1;
                        $subscription_user->fk_employee_role_id = $_POST['Employee']['fk_tbl_employee_id_employee_role'];
                        $subscription_user->create_date = date("Y-m-d H:i:s");
                        $subscription_user->save();
                    }
                }else{
                    $subscription_id = Yii::$app->Common->getCorporateId(Yii::$app->user->identity->id_employee);
                    $subscription_user = new SubscriptionUserMapping();
                    $subscription_user->fk_id_employee = $model->id_employee;
                    $subscription_user->fk_subscription_id = $fk_subscription_id;//$corporate_id;
                    $subscription_user->status = 1;
                    $subscription_user->fk_employee_role_id = $_POST['Employee']['fk_tbl_employee_id_employee_role'];
                    $subscription_user->create_date = date("Y-m-d H:i:s");
                    $subscription_user->save();
                }


                $client['client_id']=base64_encode($model->email.mt_rand(100000, 999999));
                $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($model->email.mt_rand(100000, 999999));
                //print_r($client['client_secret']);exit;
                $client['employee_id']=$model->id_employee;
                $client['grant_types']='client_credentials';
                User::addClient($client);

                return $this->redirect(['thirdparty-corporate/users-list']);
            }
        }
        return $this->render('corporate-form', [
            'model' => $model,
            'profile' => '',
            'document' => '',
            'employeeAirport' => $employeeAirport,
            'employeeRegion' => $employeeRegion,
        ]);

    }

    public function actionCorporateCreate()
    {
        $model = new Employee();
        $model->scenario = 'corporate';
        if ($model->load(Yii::$app->request->post())) { //print_r($_POST);exit;
                $model->date_modified=date('Y-m-d H:i:s');
                 $model->status=1;
                if($model->validate())
                    {

                        $model->password = sha1($_POST['Employee']['password']);
                        $model->save();
                        return $this->redirect(['corporate-list']);
                    }
                else
                {
                    // print_r($model->geterrors());exit;
                        return $this->render('corporate-create', [
                        'model' => $model,
                    ]);
                }
        }

        return $this->render('corporate-create', [
                'model' => $model,
            ]);
    }

    public function actionGroupOrder()
    {
        $v = date("Y-m-d-h-m-s");
       // print_r($_POST['keylist']);exit;
         $airport[]=$this->getAirportId($_POST['keylist']);
         $airport_mached = $this->getAirportMached($airport);
          //print_r($airport_mached);exit;
         if($airport_mached==1){

            foreach ($_POST['keylist'] as $value) {
                $model = new OrderGroup;
                $model->id_order = $value;
                if($_POST['porter']==1){
                    $model->order_group_name = "PX".$v."";
                }else{
                    $model->order_group_name = "PT".$v."";
                }
                $model->status = 1;
                $model->date_created = $v;
                $save=$model->save(false);
            }

                if($save){
                    echo "sucess";
                }else{
                    echo "failure";
                }

        }else{
            echo "failure";
         }

    }

    public function getAirportMached($arr) {
        $firstValue = current($arr[0]);
        foreach ($arr[0] as $key => $val) {
            if ($firstValue != $val) {
                return 0;
            }
        }
        return 1;
    }
    public function getAirportId($order_id){
        if($order_id){
            foreach ($order_id as $key => $id) {
            $airport=Order::find()->select('fk_tbl_airport_of_operation_airport_name_id')->where(['id_order'=>$id])->one();

            $airport_name[]=$airport['fk_tbl_airport_of_operation_airport_name_id'];
            }
            return $airport_name;
        }


    }



    public function actionSalesDashboard(){
       $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->searchcorporate(Yii::$app->request->queryParams);

        return $this->render('corporate-list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    public function actionCorporateUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) { //print_r($_POST);exit;
            $model->date_modified=date('Y-m-d H:i:s');
                 $model->status=1;
                if($model->validate())
                    {
                        $model->password = sha1($_POST['Employee']['password']);
                        $model->save();
                        return $this->redirect(['corporate-list']);
                    }
                else
                {
                    // print_r($model->geterrors());exit;
                        return $this->render('corporate-create', [
                        'model' => $model,
                    ]);
                }
        }

        return $this->render('corporate-create', [
                'model' => $model,
            ]);
    }

    public function actionCorporateList()
    {
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->searchcorporate(Yii::$app->request->queryParams);
        // print_r($dataProvider);exit;
        return $this->render('corporate-list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCorporateDashboard(){

        $id_employee = Yii::$app->user->identity->id_employee;
        $corporate_details = CorporateDetails::find()
                                                ->select('corporate_detail_id,corporate_logo')
                                                ->where(['employee_id'=>$id_employee])->one();
        return $this->render('corporate-dashboard', [
           'corporate_details' => $corporate_details
        ]);
    }

    public function actionKioskDashboard(){

        $id_employee = Yii::$app->user->identity->id_employee;
        //$kiosk_details = CorporateDetails::find()
                                                //->select('corporate_detail_id,corporate_logo')
                                                //->where(['employee_id'=>$id_employee])->one();
        return $this->render('kiosk-dashboard');
    }
    public function actionCorporateSuperadminDashboard(){
        return $this->render('corporate-superadmin-dashboard');
    }
    public function actionCorporateDashboards(){
        return $this->render('corporate-superadmin-dashboard');
    }

    /*
        * Corporate Kisok Dashboard
    */
    public function actionCorporateKioskDashboard(){
        return $this->render('corporate-kiosk-dashboard');
    }

    public function actionChangePassword()
    {   
        $model = new Employee();
        if ($model->load(Yii::$app->request->post())) { 
        
            $usermodel=$this->findModel(Yii::$app->user->identity->id_employee); 
            $usermodel->password = sha1($_POST['Employee']['new_password']);
            if($usermodel->save(false)){ 
                Yii::$app->getSession()->setFlash('msg', '<div class="alert alert-success alert-dismissable"><i class="icon fa fa-check"></i>Password Changed Successfully!</div>');
                return $this->redirect(['change-password']); 
            }          
        } 
        return $this->render('change-password', ['model'=>$model]);
    }

    public function actionForgotPasswordValidation()
    {
      $new_pass=$_POST['new_pass'];  
      $confirm_pass=$_POST['confirm_pass']; 
      if($new_pass == $confirm_pass){
        return 1;
      } 
      return 0;
    }

    /**
     * Updates an existing Employee model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
       // $d = new \DateTime();
       // print_r($d);exit;
        $model = $this->findModel($id); 
        $employeeAirportRegion= new EmployeeAirportRegion();

        $employeecorAirport = new CorporateEmployeeAirport();
        $employeeRegion = new CorporateEmployeeRegion();

         $employeeCountryMapping = \app\api_v3\v3\models\EmployeeCountryMapping::find()->where(['fk_employee_id'=>$id])->one();
        //print_r($employeeCountryMapping);exit;
        $countryList= \app\models\CountryCode::find()->where(['status'=>1])->all();
        $countrylist=ArrayHelper::map($countryList,'id_country_code','country_name');

         $airlineList = \app\api_v3\v3\models\CreateAirline::find()->where(['status'=>1])->all();
        $airlineList=ArrayHelper::getColumn($airlineList,'corporate_id');  

         $CorporateName = \app\models\CorporateDetails::find() 
                   ->select(['corporate_detail_id','name'])->where(['corporate_detail_id'=>$airlineList])->all();
                   
        $airlineList=ArrayHelper::map($CorporateName,'corporate_detail_id','name');
        //print_r($airlineList);exit;
        $stationList = \app\api_v3\v3\models\Stations::find()->where(['status'=>1])->all();
        $stationList=ArrayHelper::map($stationList,'station_id','station_name');


        $profile = Yii::$app->params['site_url'].Yii::$app->params['employee_profile'].$model->employee_profile_picture;
        $document = Yii::$app->params['site_url'].Yii::$app->params['employee_document'].$model->document_id_proof;
        $employeecorAirport = new CorporateEmployeeAirport();
        $employeeRegion = new CorporateEmployeeRegion();
        $model->scenario = 'update';
        if ($model->load(Yii::$app->request->post())) { //echo"<pre>";print_r($_POST);exit;
            // if(isset($_POST['Employee']['password'])){
            //     $model->password = sha1($_POST['Employee']['password']);
            // }
            $model->profile_picture = UploadedFile::getInstance($model, 'profile_picture');
            $model->document_proof = UploadedFile::getInstance($model, 'document_proof');
            
            if($model->fk_tbl_employee_id_employee_role == 16){
                \app\api_v3\v3\models\EmployeeCountryMapping::deleteAll('fk_employee_id  =' . $id);
                $emploeeCountryModel = new \app\api_v3\v3\models\EmployeeCountryMapping();
                $emploeeCountryModel->fk_employee_id = $id;
                $emploeeCountryModel->fk_country_id = $_POST['Employee']['tman_country'];
                $emploeeCountryModel->fk_station_id = $_POST['Employee']['station'];
                $emploeeCountryModel->fk_airline_id = $_POST['Employee']['airline'];
                $emploeeCountryModel->save(false);
            }
         

            if(!empty($model->profile_picture))
            {
                $model->profile_picture->saveAs(Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['employee_profile'].$model->profile_picture->name);
                $model->employee_profile_picture = $model->profile_picture->name;
            }
            if( !empty($model->document_proof) )
            {
                $model->document_proof->saveAs(Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['employee_document'].$model->document_proof->name);
                $model->document_id_proof = $model->document_proof->name;
            }
            $model->status = $_POST['Employee']['status'];
            if($model->validate())
            {
                $model->save();
                if(isset($_POST['EmployeeAirportRegion'])){
                    $this->employeeRegionUpdate(array( 'fk_tbl_airport_of_operation_airport_name_id' => $_POST['EmployeeAirportRegion']['fk_tbl_airport_of_operation_airport_name_id'],'fk_tbl_employee_id'=>$model->id_employee));
                }else{
                    CorporateEmployeeAirport::deleteAll('fk_tbl_employee_id  =' . $model->id_employee);
                    if($_POST['CorporateEmployeeAirport']['fk_tbl_airport_id']){
                         foreach ($_POST['CorporateEmployeeAirport']['fk_tbl_airport_id'] as $airport) {
                            $employee_airport = new CorporateEmployeeAirport();
                            $employee_airport->fk_tbl_airport_id=$airport;
                            $employee_airport->fk_tbl_employee_id=$model->id_employee;
                            $employee_airport->save(false);
                        }
                    }
                    CorporateEmployeeRegion::deleteAll('fk_tbl_employee_id  =' . $model->id_employee);
                    if($_POST['CorporateEmployeeRegion']['fk_tbl_region_id']){
                         foreach ($_POST['CorporateEmployeeRegion']['fk_tbl_region_id'] as $region) {
                            $employee_region = new CorporateEmployeeRegion();
                            $employee_region->fk_tbl_region_id=$region;
                            $employee_region->fk_tbl_employee_id=$model->id_employee;
                            $employee_region->save();
                        }
                    }
                }
                return $this->redirect(['view', 'id' => $model->id_employee]);
            }else{
                return $this->render('update', [
                'model' => $model,
                'profile' => $profile,
                'document' => $document,
                'employeecorAirport' => $employeecorAirport,
                'employeeRegion' => $employeeRegion,
                 'employeeAirportRegion' => $employeeAirportRegion,
                 'countrylist'=>$countrylist,
            'employeeCountryMapping'=>$employeeCountryMapping,
            'airlineList'=>$airlineList,
            'stationList'=>$stationList,
                ]);
            }
        } else {
            //print_r($model->errors);exit();
            return $this->render('update', [
                'model' => $model,
                'profile' => $profile,
                'document' => $document,
                'employeecorAirport' => $employeecorAirport,
                'employeeRegion' => $employeeRegion,
                'employeeAirportRegion' => $employeeAirportRegion,
                'countrylist'=>$countrylist,
            'employeeCountryMapping'=>$employeeCountryMapping,
            'airlineList'=>$airlineList,
            'stationList'=>$stationList,
                //'employeeAirportRegion' => $employeeAirportRegion,
            ]);
        }
    }
    public function employeeRegionUpdate($data) {
        EmployeeAirportRegion::deleteAll('fk_tbl_employee_id  =' . $data['fk_tbl_employee_id']);
        if(!empty($data['fk_tbl_airport_of_operation_airport_name_id'])){
            //print_r($data['fk_tbl_airport_of_operation_airport_name_id']);exit;
             foreach ($data['fk_tbl_airport_of_operation_airport_name_id'] as $key => $airport) {


                $employee_region= new EmployeeAirportRegion();
                $employee_region->fk_tbl_airport_of_operation_airport_name_id  = $airport;
                //$employee_region->fk_tbl_city_of_operation_region_name_id  = $data['fk_tbl_city_of_operation_region_name_id'];
                $employee_region->fk_tbl_employee_id  = $data['fk_tbl_employee_id'];
                $employee_region->save(false);
             }
        }
    }
    /**
     * Deletes an existing Employee model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        // EmployeeAirportRegion::deleteAll('fk_tbl_employee_id  =' . $id);
        // CorporateEmployeeAirport::deleteAll('fk_tbl_employee_id  =' . $id);
        // CorporateEmployeeRegion::deleteAll('fk_tbl_employee_id  =' . $id);

        $this->findModel($id)->delete();
        //$delete_oauth = Yii::$app->db->createCommand("DELETE FROM oauth_clients WHERE employee_id=".$id)->execute();
        return $this->redirect(['index']);
    }

    /**
     * Finds the Employee model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Employee the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($employee_region = Employee::findOne($id)) !== null) {
            return $employee_region;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function findChildModel($id)
    {
        if (($model = EmployeeAirportRegion::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionCreateCorporateOrder(){
        $model['o'] = new Order();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $model['osd']->scenario = 'create_order';
        $model['o']->scenario = 'create_order';
        $model['bw']=new BagWeightType();
        $employee_model = new Employee();
        $model['om'] = new OrderMetaDetails();
        $setCC = array();

        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o']])) {//, $model['osd']
            // echo "<pre>";print_r(Yii::$app->request->post());die;
            $primary_email = CorporateDetails::find()->select(['default_email'])->where(['corporate_detail_id'=> Yii::$app->request->post()['Order']['corporate_id']])->One();           
            if($primary_email['default_email']){
                array_push($setCC,$primary_email['default_email'],Yii::$app->params['customer_email']);
            }
            //if (!empty($_POST) && $model['o']->validate() && $model['osd']->validate()) {
            // print_r($_POST);print_r($_FILES);exit;
            // print_r($_POST['Order']['sector']);
            // echo '<pre>';print_r($_POST);exit;
            $model = new Order();
            $model->corporate_id = $_POST['Order']['corporate_id'];
            $model->corporate_type = Yii::$app->Common->getCorporateType($_POST['Order']['corporate_id']);
            $model->fk_tbl_airport_of_operation_airport_name_id=$_POST['Employee']['airport'];
            $model->location=$_POST['Order']['location'];
            $model->sector = $_POST['Order']['sector'];
            $model->weight = $_POST['Order']['weight'];
            $model->service_type = $_POST['Order']['service_type'];
            $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));     //$_POST['Order']['order_date'];
            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['luggage_price'];
            $model->order_transfer = $_POST['Order']['order_transfer'];
            $model->city_id = $_POST['Order']['fk_tbl_airport_of_operation_airport_name_id'];
 
            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = $_POST['Order']['dservice_type'];

            $model->flight_number = $_POST['Order']['flight_number'];
            $model->pnr_number = isset($_POST['Order']['pnr_number']) ? $_POST['Order']['pnr_number'] : NULL;
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
            if($_POST['Order']['insurance_price'] == 1){
                $insurance_price = 4 * count($_POST['OrderItems']);
                $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
            }else{
               $model->insurance_price = 0;
            } //print_r($_POST['OrderSpotDetails']['pincode']);exit;
            $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
            if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                $model->someone_else_document_verification = 1;
                $model->flight_verification = 1;
            }
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

            $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
            $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';

            /*$departureDetails = explode(' ', $_POST['Order']['departure_date']);
            $arrivalDetails = explode(' ', $_POST['Order']['arrival_date']);
            $model->departure_date = isset($departureDetails[0])?$departureDetails[0]:null;
            $model->arrival_date = isset($arrivalDetails[0])?$arrivalDetails[0]:null;
            $model->departure_time = isset($departureDetails[1])?$departureDetails[1]:null;
            $model->arrival_time = isset($arrivalDetails[1])?$arrivalDetails[1]:null;*/
            if($_POST['Order']['service_type'] ==1){
                $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] :null;
                $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) :null;
            }else{
                $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] :null;
                $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) :null;
            }
            /*$model->fk_tbl_order_id_customer = $_POST['id_customer'];
            $model->status = $_POST['status'];
            $model->allocation = $_POST['allocation'];
            $model->enable_cod = $_POST['enable_cod'];*/
            $model->date_created = date('Y-m-d H:i:s');
            /*Created by role id*/
            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            $model->created_by = $role_id;
            $model->created_by_name = Yii::$app->user->identity->name;

            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                if($_POST['Order']['order_transfer'] == 1){
                    
                    $orderMetaDetails = new OrderMetaDetails();
                    $orderMetaDetails->orderId = $model->id_order; 
                    $orderMetaDetails->stateId = 0;
                    $orderMetaDetails->pickupPersonName = isset($_POST['OrderMetaDetails']['pickupPersonName']) ? $_POST['OrderMetaDetails']['pickupPersonName'] : "";
                    $orderMetaDetails->pickupPersonNumber = isset($_POST['OrderMetaDetails']['pickupPersonNumber']) ? $_POST['OrderMetaDetails']['pickupPersonNumber'] : "";
                    $orderMetaDetails->pickupPersonAddressLine1 = isset($_POST['OrderMetaDetails']['pickupPersonAddressLine1']) ? $_POST['OrderMetaDetails']['pickupPersonAddressLine1'] : "";
                    $orderMetaDetails->pickupPersonAddressLine2 = isset($_POST['OrderMetaDetails']['pickupPersonAddressLine2']) ? $_POST['OrderMetaDetails']['pickupPersonAddressLine2'] : "";
                    $orderMetaDetails->pickupArea = isset($_POST['OrderMetaDetails']['pickupArea']) ? $_POST['OrderMetaDetails']['pickupArea'] : "";
                    $orderMetaDetails->pickupPincode = isset($_POST['OrderMetaDetails']['pickupPincode']) ? $_POST['OrderMetaDetails']['pickupPincode'] : "";
                    $orderMetaDetails->dropPersonName = isset($_POST['OrderMetaDetails']['dropPersonName']) ? $_POST['OrderMetaDetails']['dropPersonName'] : "";
                    $orderMetaDetails->dropPersonNumber = isset($_POST['OrderMetaDetails']['dropPersonNumber']) ? $_POST['OrderMetaDetails']['dropPersonNumber'] : "";
                    $orderMetaDetails->dropPersonAddressLine1 = isset($_POST['OrderMetaDetails']['dropPersonAddressLine1']) ? $_POST['OrderMetaDetails']['dropPersonAddressLine1'] : "";
                    $orderMetaDetails->dropPersonAddressLine2 = isset($_POST['OrderMetaDetails']['dropPersonAddressLine2']) ? $_POST['OrderMetaDetails']['dropPersonAddressLine2'] : "";
                    $orderMetaDetails->droparea = isset($_POST['OrderMetaDetails']['droparea']) ? $_POST['OrderMetaDetails']['droparea'] : "";
                    $orderMetaDetails->dropPincode = isset($_POST['OrderMetaDetails']['dropPincode']) ? $_POST['OrderMetaDetails']['dropPincode'] : "";
                    $orderMetaDetails->dropBuildingNumber = isset($_POST['OrderMetaDetails']['dropBuildingNumber']) ? $_POST['OrderMetaDetails']['dropBuildingNumber'] : "";
                    $orderMetaDetails->pickupBuildingNumber = isset($_POST['OrderMetaDetails']['pickupBuildingNumber']) ? $_POST['OrderMetaDetails']['pickupBuildingNumber'] : "";
                    $orderMetaDetails->pickupLocationType = isset($_POST['OrderMetaDetails']['pickupLocationType']) ? $_POST['OrderMetaDetails']['pickupLocationType'] : "";
                    $orderMetaDetails->dropLocationType = isset($_POST['OrderMetaDetails']['dropLocationType']) ? $_POST['OrderMetaDetails']['dropLocationType'] : "";
                    $orderMetaDetails->dropHotelType = isset($_POST['OrderMetaDetails']['dropHotelType']) ? $_POST['OrderMetaDetails']['dropHotelType'] : "";
                    $orderMetaDetails->pickupHotelType = isset($_POST['OrderMetaDetails']['pickupHotelType']) ? $_POST['OrderMetaDetails']['pickupHotelType'] : "";
                    $orderMetaDetails->save(false);
                }

                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                $model->save(false);
                if(!empty($_FILES['Order']['name']['ticket'])){
                    $up = $employee_model->actionFileupload('ticket',$model->id_order);
                }
                if(!empty($_FILES['Order']['name']['someone_else_document'])){
                    $up = $employee_model->actionFileupload('someone_else_document',$model->id_order);
                }

                /*order history*/
                /*$order_data = ['fk_tbl_order_history_id_order'=>$model->id_order,'to_tbl_order_status_id_order_status'=>$model->fk_tbl_order_status_id_order_status,'to_order_status_name'=>$model->order_status,'date_created'=>date('Y-m-d H:i:s')];
                $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$order_data)->execute();*/
                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                /*order history end*/

                /*order total table*/
                $this->updateordertotal($model->id_order, $_POST['Order']['luggage_price'], $_POST['Order']['service_tax_amount'], $model->insurance_price);
                /*order total*/

                //code OrderItems...
                $luggage_det = LuggageType::find()->where(['corporate_id'=>$_POST['Order']['corporate_id']])->one();
                // print_r($_POST['OrderItems']);exit;
                //echo '<pre>';print_r($_POST['OrderItems']);exit;

                   if(!empty($_POST['OrderItems'])){
                    foreach ($_POST['OrderItems'] as $key => $items) {
                        //print_r($items);exit;
                        $order_items = new OrderItems();
                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                        $order_items->item_price = $luggage_det['base_price'];
                        if($model->weight==1 && $model->corporate_id == 7){
                        $order_items->bag_weight = $items['fk_tbl_order_items_id_bag_range'];
                        $order_items->bag_type = $items['fk_tbl_order_items_id_bag_type'];
                    }else{
                        $order_items->bag_weight = '<15';
                        $order_items->bag_type = 'checkin';

                    }
                    $order_items->save();
                    }

                }

            //   if($model->weight==1&& $model->corporate_id == 2){
            // $rr = array_keys($_POST['OrderBagRange']);
            // // print_r($_POST['OrderBagRange']);die();
            // for ($i=0; $i<count($rr); $i++)
            // {

            //  $rows[]=['corporate_id'=>$model->corporate_id,'id_order' => $model->id_order,
            //  'bag_weight'=>$_POST['OrderBagRange'][$i]['fk_tbl_order_items_id_bag_range'],'bag_type'=>$_POST['OrderBagRange'][$i]['fk_tbl_order_items_id_bag_type']];


            // }

            // Yii::$app->db->createCommand()->batchInsert('tbl_bag_weight_type', ['corporate_id','id_order','bag_weight','bag_type'], $rows)->execute();
            // }

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

                //if($model->service_type == 1){

                if($model->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                {
                    $order_spot_details->assigned_person = 1;
                }
                    $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                    $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;

                    if($_POST['Order']['order_transfer'] == 2){
                        $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                        $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                        $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                        $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                        $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                    }
                    //$order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                //}
                $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
                if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                    $order_spot_details->hotel_booking_verification  = 1;
                    $order_spot_details->invoice_verification = 1;
                }
                if($order_spot_details->save()){
                    if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                        $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                    }
                }
                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = Yii::$app->params['default_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];

                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];
                /*COde for Mail and sms start Dt:02/09/2017J*/
                //Corporate SMS                     if($model->corporate_id != 7){
                $sms_content = Yii::$app->Common->getCorporateSms($model->id_order, 'order_confirmation_mhl', '');

                $model1['order_details']=Order::getorderdetails($model->id_order);
                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                $data['order_details'] =  Order::getorderdetails($model->id_order);
                if(empty($data['order_details']['corporate_details']['email1'])){
                    $MHL_email_ids = array($data['order_details']['corporate_details']['default_email']);
                } else if(empty($data['order_details']['corporate_details']['default_email']) && !empty($data['order_details']['corporate_details']['email1'])){
                    $MHL_email_ids = array($data['order_details']['corporate_details']['email1']);
                } else {
                    $MHL_email_ids = array($data['order_details']['corporate_details']['default_email'],$data['order_details']['corporate_details']['email1']);
                }
                User::sendMHLemail($MHL_email_ids,"MHL order Confirmation","order_confirmation_mhl",$data,true);
 
                // User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det,$setCC);

                /*COde for Mail and sms End*/
                return $this->redirect(['update-kiosk-corporate', 'id' => $model->id_order, 'mobile' => $_POST['Order']['travell_passenger_contact']]);
            }
        } else {
            if(!empty($model['o']->geterrors()) || !empty($model['osd']->geterrors()))
            { //print_r('in');
                print_r($model['o']->geterrors());
                print_r($model['osd']->geterrors());exit;
            }
            return $this->render('corporate_create_order', [
                'model' => $model,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        } 
    }
 
    public function actionTmanCorporateOrderUpdate($id){ 
        $model['o'] =  Order::findOne($id);
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $model['osd']->scenario = 'create_order';
        $model['o']->scenario = 'create_order';
        $model['bw']=new BagWeightType();
        $employee_model = new Employee();

 
        //print_r($_POST);exit;
        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o'], $model['osd']])) {
            //print_r($_POST);exit;
            //if (!empty($_POST) && $model['o']->validate() && $model['osd']->validate()) { 
           // print_r($_POST);print_r($_FILES);exit;
             // print_r($_POST['Order']['sector']);
              //echo '<pre>';print_r($_POST);exit;
            $model = Order::findOne($id);
            $model->corporate_id = $_POST['Order']['corporate_id'];
            $model->fk_tbl_airport_of_operation_airport_name_id=$_POST['Employee']['airport'];
            $model->location=$_POST['Order']['location'];
            $model->sector = $_POST['Order']['sector'];
            $model->weight = $_POST['Order']['weight'];
            $model->excess_bag_amount=0;
            $model->service_type = $_POST['Order']['service_type'];
            $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));     //$_POST['Order']['order_date'];
            //$model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['luggage_price'];
           
            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = $_POST['Order']['dservice_type'];
            
            $model->flight_number = $_POST['Order']['flight_number'];
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
            if($_POST['Order']['insurance_price'] == 1){
                $insurance_price = 4 * count($_POST['OrderItems']); 
                $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
            }else{
               $model->insurance_price = 0; 
            } //print_r($_POST['OrderSpotDetails']['pincode']);exit;
            $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
            if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                $model->someone_else_document_verification = 1;
                $model->flight_verification = 1;
            }
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
            
            $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
            $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';

            /*$departureDetails = explode(' ', $_POST['Order']['departure_date']);
            $arrivalDetails = explode(' ', $_POST['Order']['arrival_date']);
            $model->departure_date = isset($departureDetails[0])?$departureDetails[0]:null;
            $model->arrival_date = isset($arrivalDetails[0])?$arrivalDetails[0]:null;
            $model->departure_time = isset($departureDetails[1])?$departureDetails[1]:null;
            $model->arrival_time = isset($arrivalDetails[1])?$arrivalDetails[1]:null;*/
            if($_POST['Order']['service_type'] ==1){
                $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] :null;
                $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) :null;
            }else{
                $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] :null;
                $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) :null;
            }
            /*$model->fk_tbl_order_id_customer = $_POST['id_customer'];
            $model->status = $_POST['status'];
            $model->allocation = $_POST['allocation'];
            $model->enable_cod = $_POST['enable_cod'];*/
            $model->date_created = date('Y-m-d H:i:s');
            /*Created by role id*/
            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            $model->created_by = $role_id;
            $model->created_by_name = Yii::$app->user->identity->name;

            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                $model->save(false);
                if(!empty($_FILES['Order']['name']['ticket'])){
                    $up = $employee_model->actionFileupload('ticket',$model->id_order);
                }
                if(!empty($_FILES['Order']['name']['someone_else_document'])){
                    $up = $employee_model->actionFileupload('someone_else_document',$model->id_order);
                }

                /*order history*/
                /*$order_data = ['fk_tbl_order_history_id_order'=>$model->id_order,'to_tbl_order_status_id_order_status'=>$model->fk_tbl_order_status_id_order_status,'to_order_status_name'=>$model->order_status,'date_created'=>date('Y-m-d H:i:s')];
                $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$order_data)->execute();*/
                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                /*order history end*/

                /*order total table*/
                $this->updateordertotal($model->id_order, $_POST['Order']['luggage_price'], $_POST['Order']['service_tax_amount'], $model->insurance_price);                
                /*order total*/ 
                                       
                //code OrderItems...
                $luggage_det = LuggageType::find()->where(['corporate_id'=>$_POST['Order']['corporate_id']])->one();
                // print_r($_POST['OrderItems']);exit;
                //echo '<pre>';print_r($_POST['OrderItems']);exit;
 
                   if(!empty($_POST['OrderItems'])){
                        OrderItems::deleteAll('fk_tbl_order_items_id_order  =' . $model->id_order);
                    foreach ($_POST['OrderItems'] as $key => $items) {
                        //print_r($items);exit;
                        $order_items = new OrderItems();
                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                        $order_items->item_price = $luggage_det['base_price'];
                        if($model->weight==1 && $model->corporate_id == 7){
                        $order_items->bag_weight = $items['fk_tbl_order_items_id_bag_range'];
                        $order_items->bag_type = $items['fk_tbl_order_items_id_bag_type'];
                    }else{
                        $order_items->bag_weight = '<15';
                        $order_items->bag_type = 'checkin';
                        
                    }
                    $order_items->save();
                    }
                    
                }

            //   if($model->weight==1&& $model->corporate_id == 2){
            // $rr = array_keys($_POST['OrderBagRange']);
            // // print_r($_POST['OrderBagRange']);die();
            // for ($i=0; $i<count($rr); $i++) 
            // {  

            //  $rows[]=['corporate_id'=>$model->corporate_id,'id_order' => $model->id_order,
            //  'bag_weight'=>$_POST['OrderBagRange'][$i]['fk_tbl_order_items_id_bag_range'],'bag_type'=>$_POST['OrderBagRange'][$i]['fk_tbl_order_items_id_bag_type']];
             
                                     
            // }                       
                                     
            // Yii::$app->db->createCommand()->batchInsert('tbl_bag_weight_type', ['corporate_id','id_order','bag_weight','bag_type'], $rows)->execute();
            // }
        
                //code OrderSpotDetails...
                $order_spot_details = OrderSpotDetails::find()->where(['fk_tbl_order_spot_details_id_order'=>$model->id_order])->one();
                //print_r($order_spot_details);exit;
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

                //if($model->service_type == 1){

                if($model->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                {
                    $order_spot_details->assigned_person = 1;
                }
                    $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                    $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                    $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                    $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                    $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                    $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                    $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;
                    //$order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;  
                //}
                $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
                if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                    $order_spot_details->hotel_booking_verification  = 1;
                    $order_spot_details->invoice_verification = 1;
                }
                if($order_spot_details->save(false)){
                    if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                        $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                    }
                }
                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = Yii::$app->params['default_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];

                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];
                /*COde for Mail and sms start Dt:02/09/2017J*/
                //Corporate SMS
                $sms_content = Yii::$app->Common->getCorporateSms($model->id_order, 'order_confirmation', '');
                $model1['order_details']=Order::getorderdetails($model->id_order);

                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                /*COde for Mail and sms End*/

               

                return $this->redirect(['order/index']);
            }
        } else {
            if(!empty($model['o']->geterrors()) || !empty($model['osd']->geterrors()))
            { //print_r('in');
                print_r($model['o']->geterrors());
                print_r($model['osd']->geterrors());exit;
            }
             

            return $this->render('tman_corporate_order_update', [
                'model' => $model,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        }
    }



    public function actionCreateCorporateOrderKiosk(){
        $model['o'] = new Order();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $model['osd']->scenario = 'create_order';
        $model['o']->scenario = 'create_order';
        $model['bw']=new BagWeightType();
        $employee_model = new Employee();
        $model['om'] = new OrderMetaDetails();


        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o']])) {//, $model['osd']

            $model = new Order();
            $model->corporate_id = $_POST['Order']['corporate_id'];
            $model->corporate_type = Yii::$app->Common->getCorporateType($_POST['Order']['corporate_id']);
            $model->fk_tbl_airport_of_operation_airport_name_id=$_POST['Employee']['airport'];
            $model->location=$_POST['Order']['location'];
            $model->sector = $_POST['Order']['sector'];
            $model->weight = $_POST['Order']['weight'];
            $model->service_type = $_POST['Order']['service_type'];
            $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  //$_POST['Order']['order_date'];
            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['luggage_price'];
            $model->order_transfer = $_POST['Order']['order_transfer'];
            $model->city_id = $_POST['Order']['fk_tbl_airport_of_operation_airport_name_id'];

            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = $_POST['Order']['dservice_type'];

            $model->flight_number = $_POST['Order']['flight_number'];
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
            if($_POST['Order']['insurance_price'] == 1){
                $insurance_price = 4 * count($_POST['OrderItems']);
                $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
            }else{
               $model->insurance_price = 0;
            }
            $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
            if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                $model->someone_else_document_verification = 1;
                $model->flight_verification = 1;
            }
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
            //$model->payment_method = $_POST['OrderPaymentDetails']['payment_type'];
            $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
            $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
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

            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                if($_POST['Order']['order_transfer'] == 1){
                    
                    $orderMetaDetails = new OrderMetaDetails();
                    $orderMetaDetails->orderId = $model->id_order; 
                    $orderMetaDetails->stateId = 0;
                    $orderMetaDetails->pickupPersonName = isset($_POST['OrderMetaDetails']['pickupPersonName']) ? $_POST['OrderMetaDetails']['pickupPersonName'] : "";
                    $orderMetaDetails->pickupPersonNumber = isset($_POST['OrderMetaDetails']['pickupPersonNumber']) ? $_POST['OrderMetaDetails']['pickupPersonNumber'] : "";
                    $orderMetaDetails->pickupPersonAddressLine1 = isset($_POST['OrderMetaDetails']['pickupPersonAddressLine1']) ? $_POST['OrderMetaDetails']['pickupPersonAddressLine1'] : "";
                    $orderMetaDetails->pickupPersonAddressLine2 = isset($_POST['OrderMetaDetails']['pickupPersonAddressLine2']) ? $_POST['OrderMetaDetails']['pickupPersonAddressLine2'] : "";
                    $orderMetaDetails->pickupArea = isset($_POST['OrderMetaDetails']['pickupArea']) ? $_POST['OrderMetaDetails']['pickupArea'] : "";
                    $orderMetaDetails->pickupPincode = isset($_POST['OrderMetaDetails']['pickupPincode']) ? $_POST['OrderMetaDetails']['pickupPincode'] : "";
                    $orderMetaDetails->dropPersonName = isset($_POST['OrderMetaDetails']['dropPersonName']) ? $_POST['OrderMetaDetails']['dropPersonName'] : "";
                    $orderMetaDetails->dropPersonNumber = isset($_POST['OrderMetaDetails']['dropPersonNumber']) ? $_POST['OrderMetaDetails']['dropPersonNumber'] : "";
                    $orderMetaDetails->dropPersonAddressLine1 = isset($_POST['OrderMetaDetails']['dropPersonAddressLine1']) ? $_POST['OrderMetaDetails']['dropPersonAddressLine1'] : "";
                    $orderMetaDetails->dropPersonAddressLine2 = isset($_POST['OrderMetaDetails']['dropPersonAddressLine2']) ? $_POST['OrderMetaDetails']['dropPersonAddressLine2'] : "";
                    $orderMetaDetails->droparea = isset($_POST['OrderMetaDetails']['droparea']) ? $_POST['OrderMetaDetails']['droparea'] : "";
                    $orderMetaDetails->dropPincode = isset($_POST['OrderMetaDetails']['dropPincode']) ? $_POST['OrderMetaDetails']['dropPincode'] : "";
                    $orderMetaDetails->dropBuildingNumber = isset($_POST['OrderMetaDetails']['dropBuildingNumber']) ? $_POST['OrderMetaDetails']['dropBuildingNumber'] : "";
                    $orderMetaDetails->pickupBuildingNumber = isset($_POST['OrderMetaDetails']['pickupBuildingNumber']) ? $_POST['OrderMetaDetails']['pickupBuildingNumber'] : "";
                    $orderMetaDetails->pickupLocationType = isset($_POST['OrderMetaDetails']['pickupLocationType']) ? $_POST['OrderMetaDetails']['pickupLocationType'] : "";
                    $orderMetaDetails->dropLocationType = isset($_POST['OrderMetaDetails']['dropLocationType']) ? $_POST['OrderMetaDetails']['dropLocationType'] : "";
                    $orderMetaDetails->dropHotelType = isset($_POST['OrderMetaDetails']['dropHotelType']) ? $_POST['OrderMetaDetails']['dropHotelType'] : "";
                    $orderMetaDetails->pickupHotelType = isset($_POST['OrderMetaDetails']['pickupHotelType']) ? $_POST['OrderMetaDetails']['pickupHotelType'] : "";
                    $orderMetaDetails->save(false);
                }

                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                $model->save(false);
                if(!empty($_FILES['Order']['name']['ticket'])){
                    $up = $employee_model->actionFileupload('ticket',$model->id_order);
                }
                if(!empty($_FILES['Order']['name']['someone_else_document'])){
                    $up = $employee_model->actionFileupload('someone_else_document',$model->id_order);
                }
                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                /*order history end*/

                /*order total table*/
                $this->updateordertotal($model->id_order, $_POST['Order']['luggage_price'], $_POST['Order']['service_tax_amount'], $model->insurance_price);
                /*order total*/

                //code OrderItems...
                $luggage_det = LuggageType::find()->where(['corporate_id'=>$_POST['Order']['corporate_id']])->one();

               if(!empty($_POST['OrderItems'])){
                foreach ($_POST['OrderItems'] as $key => $items) {
                    //print_r($items);exit;
                    $order_items = new OrderItems();
                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                    $order_items->item_price = $luggage_det['base_price'];
                    if($model->weight==1 && $model->corporate_id == 7){
                        $order_items->bag_weight = $items['fk_tbl_order_items_id_bag_range'];
                        $order_items->bag_type = $items['fk_tbl_order_items_id_bag_type'];
                    }else{
                        $order_items->bag_weight = '<15';
                        $order_items->bag_type = 'checkin';

                    }
                    $order_items->save();
                }

              }
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
                    $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                    $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;

                    if($_POST['Order']['order_transfer'] == 2){
                        $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                        $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                        $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                        $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                        $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                    }
                    //$order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                //}
                $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
                if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                    $order_spot_details->hotel_booking_verification  = 1;
                    $order_spot_details->invoice_verification = 1;
                }
                if($order_spot_details->save()){
                    if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                        $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                    }
                }
                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = Yii::$app->params['default_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];
                /*COde for Mail and sms start Dt:02/09/2017J*/
                $sms_content = Yii::$app->Common->getCorporateSms($model->id_order, 'order_confirmation_mhl', '');
                $model1['order_details']=Order::getorderdetails($model->id_order);

                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                $data['order_details'] =  Order::getorderdetails($model->id_order);
                if(empty($data['order_details']['corporate_details']['email1'])){
                    $MHL_email_ids = array($data['order_details']['corporate_details']['default_email']);
                } else if(empty($data['order_details']['corporate_details']['default_email']) && !empty($data['order_details']['corporate_details']['email1'])){
                    $MHL_email_ids = array($data['order_details']['corporate_details']['email1']);
                } else {
                    $MHL_email_ids = array($data['order_details']['corporate_details']['default_email'],$data['order_details']['corporate_details']['email1']);
                }

                User::sendMHLemail($MHL_email_ids,"MHL order Confirmation","order_confirmation_mhl",$data,true);

                // User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                /*COde for Mail and sms End*/
                return $this->redirect(['update-kiosk-corporate', 'id' => $model->id_order, 'mobile' => $_POST['Order']['travell_passenger_contact']]);
                //return $this->redirect(['order/kiosk-orders']);
            }
        } else {
            if(!empty($model['o']->geterrors()) || !empty($model['osd']->geterrors()))
            { //print_r('in');
                print_r($model['o']->geterrors());
                print_r($model['osd']->geterrors());exit;
            }

            return $this->render('corporate_create_order', [
                'model' => $model,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        }
    }

    /*
    * This function handles the kiosk general order create
    */
    public function actionCreateGeneralOrderKiosk($mobile)
    {
        $model['o'] = new Order();
        $model['om'] = new OrderMetaDetails();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['pd'] = new OrderPaymentDetails();
        $model['mi'] = new MallInvoices();
        $model['sta'] = new State();
        // $model['osd']->scenario = 'create_order';
        $model['o']->scenario = 'create_order_genral';
        $model['bw']=new BagWeightType();
        $employee_model = new Employee();
        $OrderZoneDetails = new OrderZoneDetails();
        $customers = Customer::findOne(['mobile' => $mobile]);
        $setCC = array();

        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o'], $model['osd']])) {

            $primary_email = CorporateDetails::find()->select(['default_email','default_contact'])->where(['corporate_detail_id'=> Yii::$app->request->post()['Order']['corporate_id']])->One();
            if($primary_email['default_email']){
                array_push($setCC,$primary_email['default_email'],Yii::$app->params['customer_email']);
            }
            $DeliveryRes = Yii::$app->Common->getExpectedDeliveryDateTime($_POST['Order']['delivery_type'],$_POST['Order']['service_type'],$_POST['Order']['fk_tbl_order_id_slot'],date('Y-m-d',strtotime($_POST['Order']['order_date'])));

            $model = new Order();
            if(!empty($DeliveryRes)){
                $model->delivery_datetime = isset($DeliveryRes['delivery_date_time']) ? $DeliveryRes['delivery_date_time'] : "";
                $model->delivery_time_status = isset($DeliveryRes['delivery_status']) ? $DeliveryRes['delivery_status'] : "";
            }
            $model->corporate_id = $_POST['Order']['corporate_id'];
            $model->corporate_type = Yii::$app->Common->getCorporateType($_POST['Order']['corporate_id']);
            $model->city_pincode = $_POST['Order']['city_pincode'];
            $model->city_id = $_POST['Order']['fk_tbl_airport_of_operation_airport_name_id'];
            $model->fk_tbl_airport_of_operation_airport_name_id=$_POST['Employee']['airport'];
            $model->fk_tbl_order_id_customer = ($_POST['id_customer']) ? $_POST['id_customer'] : '';
            $model->service_type = $_POST['Order']['service_type'];
            $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));
            if (isset($_POST['OrderSpotDetails']['pincode'])) {
                $sector = PickDropLocation::findOne(['pincode' => $_POST['OrderSpotDetails']['pincode']]);

                $model->sector_name = ($sector) ? $sector->sector : '';
                if(isset($sector)){
                  $model->fk_tbl_order_id_pick_drop_location = $sector->id_pick_drop_location;
                }
            }
            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];

            $model->delivery_type = $_POST['Order']['delivery_type'];
            $model->order_transfer = $_POST['Order']['order_transfer'];

            $model->travel_person = 1;

            $model->dservice_type = $_POST['Order']['dservice_type'];

            if(isset($_POST['Order']['insurance_price']) && $_POST['Order']['insurance_price'] == 1){
                $in_price = ($_POST['Order']['in_price']) ? $_POST['Order']['in_price'] : 0;
                $tax = ($_POST['Order']['tax']) ? $_POST['Order']['tax'] : 0;
                $insurance_price = $in_price;
                $model->insurance_price = $insurance_price + $tax; //18% of insurance amount is total insurance
            }else{
               $model->insurance_price = 0;
            }

            if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                $model->fk_tbl_order_status_id_order_status = 1;
                $model->order_status = 'Yet to be confirmed';
            }else{
                $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
            }

            if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                $model->enable_cod = 1;
            }
            if($_POST['OrderPaymentDetails']['payment_type'] == 'cash' || $_POST['OrderPaymentDetails']['payment_type'] == 'Card'){
                $model->amount_paid = $_POST['Order']['luggage_price'];
                $model->outstation_amount_paid = $_POST['Order']['luggage_price'];
            }
            if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                $model->fk_tbl_order_status_id_order_status = 1;
                $model->order_status = 'Yet to be confirmed';
            }else{
                $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
            }

            if($_POST['Order']['delivery_type'] == 2){
                $luggage_price = $_POST['Order']['totalPrice'] + $_POST['Order']['service_tax_amount'] + $_POST['Order']['in_price'] + $_POST['Order']['tax'];
                $model->luggage_price = $luggage_price;
            }else{
                $model->luggage_price = $_POST['Order']['luggage_price'];
            }

            $delivery_dates = Yii::$app->Common->selectedSlot($_POST['Order']['fk_tbl_order_id_slot'], $model->order_date, $model->delivery_type);
            
            $model->delivery_date = $delivery_dates['delivery_date'];
            $model->delivery_time = $delivery_dates['delivery_time'];
            
            $model->payment_method = $_POST['OrderPaymentDetails']['payment_type'];
            $model->date_created = date('Y-m-d H:i:s');
            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            $model->created_by = $role_id;
            $model->created_by_name = Yii::$app->user->identity->name;

            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                if($_POST['Order']['extra_charges'] && $_POST['Order']['outstation_extra_charges']){
                    $model->express_extra_amount = $_POST['Order']['extra_charges'];
                    $model->outstation_extra_amount = $_POST['Order']['outstation_extra_charges'];
                }else if($_POST['Order']['extra_charges'] && !$_POST['Order']['outstation_extra_charges']){
                    $model->express_extra_amount = $_POST['Order']['extra_charges'];
                }
                //$model->express_extra_amount = $_POST['Order']['extra_charges'];
                $model->save(false);
                if(!empty($_FILES['Order']['name']['ticket'])){
                    $up = $employee_model->actionFileupload('ticket',$model->id_order);
                }
                if(!empty($_FILES['Order']['name']['someone_else_document'])){
                    $up = $employee_model->actionFileupload('someone_else_document',$model->id_order);
                }

                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                /*order history end*/

                /*order total table*/

                $this->updateordertotal($model->id_order, $_POST['Order']['luggage_price'], $_POST['Order']['service_tax_amount'], $model->insurance_price);
                /*order total*/
                //if($_POST['OrderPaymentDetails']['payment_type'] != 'COD'){
                    $order_payment_details = new OrderPaymentDetails();
                    $order_payment_details->id_order = $model->id_order;
                    $order_payment_details->payment_type = (isset($_POST['OrderPaymentDetails'])) ? $_POST['OrderPaymentDetails']['payment_type'] : '';
                    $order_payment_details->id_employee = Yii::$app->user->identity->id_employee;

                    if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                        $order_payment_details->payment_status = 'Not paid';
                    }else{
                        $order_payment_details->payment_status = 'Success';
                    }
                    // $order_payment_details->payment_status = 'Not paid';
                    $order_payment_details->amount_paid = $_POST['Order']['luggage_price'];
                    $order_payment_details->value_payment_mode = 'Order Amount';
                    $order_payment_details->date_created= date('Y-m-d H:i:s');
                    $order_payment_details->date_modified= date('Y-m-d H:i:s');
                    $order_payment_details->save();

                $order_spot_details = new OrderSpotDetails();

                if($_POST['Order']['delivery_type'] == 2 || $_POST['Order']['delivery_type'] == 1){
                    if($_POST['Order']['order_transfer'] == 1){ 
                        if($_POST['Order']['delivery_type'] == 2 && ($_POST['Order']['order_transfer'] == 1)){
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $_POST['outstation_id'];
                            $OrderZoneDetails->cityZoneId = $_POST['city_id'];
                            $OrderZoneDetails->stateId = $_POST['State']['idState'];
                            $OrderZoneDetails->extraKilometer = $_POST['Order']['extr_kms'];
                            $OrderZoneDetails->taxAmount = $_POST['Order']['luggageGST'];;
                            $OrderZoneDetails->outstationCharge = $_POST['Order']['outstation_charge'];
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                            $OrderZoneDetails->save(false);
                        }
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
                        if($model->payment_method == 'COD'){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'RazorPay', '');
                        }else{
                            $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                        }

                    }else{
                        if($_POST['Order']['delivery_type'] == 2 && ($_POST['Order']['order_transfer'] == 2)){
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $_POST['outstation_id'];
                            $OrderZoneDetails->cityZoneId = $_POST['city_id'];
                            $OrderZoneDetails->stateId = $_POST['State']['idState'];
                            $OrderZoneDetails->extraKilometer = $_POST['Order']['extr_kms'];
                            $OrderZoneDetails->taxAmount = $_POST['Order']['luggageGST'];;
                            $OrderZoneDetails->outstationCharge = $_POST['Order']['outstation_charge'];
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');
                            
                            $OrderZoneDetails->save(false);
                        }
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
                        // $order_spot_details = new OrderSpotDetails();
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
                        $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                    }
                }
               
                if(!empty($_FILES['OrderMetaDetails']['name']['pickupInvoice'])){
                    $up = $employee_model->actionFileupload('pickupInvoice',$model->id_order);
                }

                if(!empty($_FILES['OrderMetaDetails']['name']['pickupBookingConfirmation'])){
                    $up = $employee_model->actionFileupload('pickupBookingConfirmation',$model->id_order);
                }
                if(!empty($_FILES['OrderMetaDetails']['name']['dropInvoice'])){
                    $up = $employee_model->actionFileupload('dropInvoice',$model->id_order);
                }

                if(!empty($_FILES['OrderMetaDetails']['name']['dropBookingConfirmation'])){
                    $up = $employee_model->actionFileupload('dropBookingConfirmation',$model->id_order);
                }
                if(!empty($_POST['OrderItems'])){
                    if($_POST['price_array']){
                        $item_price    = $_POST['price_array'];
                        $decoded_data  = json_decode($item_price);
                    }else{
                        $decoded_data = '';
                    }
                    if(is_object($decoded_data)){
                        $array_of_data = get_object_vars($decoded_data);
                    }else{
                        $array_of_data = $decoded_data;
                    }
                    foreach ($_POST['OrderItems'] as $key => $items) {
                        $weight = $weights=\app\models\WeightRange::find()
                                    ->select(['id_weight_range','weight_considered'])
                                    ->where(['id_weight_range'=>$items['fk_tbl_order_items_id_weight_range']])
                                    ->one();
                        if(!empty($items['fk_tbl_order_items_id_luggage_type'])){
                            $order_items = new OrderItems();
                            $order_items->fk_tbl_order_items_id_order = $model->id_order;
                            $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                            $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                            if(isset($weight) && ($items['fk_tbl_order_items_id_weight_range'] == 8)){
                                $item_weight = ($items['item_weight']) ? $items['item_weight'] : $weight->weight_considered;
                            }else if(isset($weight) && ($weight->weight_considered)){
                                $item_weight = $weight->weight_considered;
                            }else{
                                $item_weight = 0;
                            }
                            $order_items->fk_tbl_order_items_id_luggage_type_old = $items['fk_tbl_order_items_id_luggage_type'];
                            $order_items->items_old_weight = $item_weight;
                            $order_items->item_weight = $item_weight;
                            // $order_items->item_weight = ($weight->weight_considered == 5) ? $items['item_weight'] : $weight->weight_considered;

                            $order_items->item_price = (isset($array_of_data[$key]->item_price)) ? $array_of_data[$key]->item_price : 0;

                            $order_items->save(false);
                        }
                    }
                }
                if(isset($_POST['Employee']['airport']) && !empty($_POST['Employee']['airport']))
                {
                    $order_offer= new OrderOffers;
                    if($_POST['Order']['delivery_type'] == 1){
                        $order_offer_items = Yii::$app->db->createCommand("SELECT luggage_type,base_price,offer_price FROM tbl_luggage_offers WHERE airport='".$_POST['Employee']['airport']."' AND status='enabled'")->queryAll();
                    }else{
                        $order_offer_items = Yii::$app->db->createCommand("SELECT luggage_type,base_price,offer_price FROM tbl_luggage_offers WHERE airport=3 AND status='enabled' AND (luggage_type = 2 OR luggage_type = 4)")->queryAll();

                    }
                    // echo "<pre>";    print_r($order_offer_items);exit;

                    if ($order_offer_items) {
                       foreach($order_offer_items as $id)
                        {
                            $order_offer= new OrderOffers;
                            $order_offer->order_id= $model->id_order;
                            $order_offer->luggage_type=$id['luggage_type'];
                            $order_offer->base_price=$id['base_price'];
                            $order_offer->offer_price=$id['offer_price'];
                            $order_offer->save();
                        }
                    }

                    $group_offer= new OrderGroupOffer;
                    if($_POST['Order']['delivery_type'] == 1){
                        $group_offer_items = Yii::$app->db->createCommand("SELECT group_id,subsequent_price FROM tbl_group_offers WHERE airport='".$_POST['Employee']['airport']."' AND status='enabled'")->queryAll();
                    }else{
                        $group_offer_items = Yii::$app->db->createCommand("SELECT group_id,subsequent_price FROM tbl_group_offers WHERE airport=3 AND status='enabled' AND (group_id = 1 OR group_id = 2)")->queryAll();
                    }
                     //print_r($group_offer_items);exit;
                     if ($group_offer_items) {

                       foreach($group_offer_items as $id)
                        {
                            $group_offer= new OrderGroupOffer;
                            $group_offer->order_id= $model->id_order;
                            $group_offer->group_id=$id['group_id'];
                            $group_offer->subsequent_price=$id['subsequent_price'];
                            $group_offer->save(false);
                        }
                    }

                }else{
                    if($_POST['Order']['delivery_type'] == 1){
                        $order_offer_items = Yii::$app->db->createCommand("SELECT luggage_type,base_price,offer_price FROM tbl_city_luggage_offers WHERE city_id='".$_POST['Order']['fk_tbl_airport_of_operation_airport_name_id']."' AND status='enabled'")->queryAll();
                    }else{
                        $order_offer_items = Yii::$app->db->createCommand("SELECT luggage_type,base_price,offer_price FROM tbl_city_luggage_offers WHERE city_id=1 AND status='enabled' AND (luggage_type = 2 OR luggage_type = 4)")->queryAll();

                    }
                    $order_offer= new OrderOffers;
                    if ($order_offer_items) {
                       foreach($order_offer_items as $id)
                        {
                            $order_offer= new OrderOffers;
                            $order_offer->order_id= $model->id_order;
                            $order_offer->luggage_type=$id['luggage_type'];
                            $order_offer->base_price=$id['base_price'];
                            $order_offer->offer_price=$id['offer_price'];
                            $order_offer->save();
                        }
                    }
                    $group_offer= new OrderGroupOffer;
                    if($_POST['Order']['delivery_type'] == 1){
                        $group_offer_items = Yii::$app->db->createCommand("SELECT group_id,subsequent_price FROM tbl_city_group_offers WHERE city_id='".$_POST['Order']['fk_tbl_airport_of_operation_airport_name_id']."' AND status='enabled'")->queryAll();
                    }else{
                        $group_offer_items = Yii::$app->db->createCommand("SELECT group_id,subsequent_price FROM tbl_city_group_offers WHERE city_id=1 AND status='enabled' AND (group_id = 1 OR group_id = 2)")->queryAll();
                    }
                     if ($group_offer_items) {
                       foreach($group_offer_items as $id)
                        {
                            $group_offer= new OrderGroupOffer;
                            $group_offer->order_id= $model->id_order;
                            $group_offer->group_id=$id['group_id'];
                            $group_offer->subsequent_price=$id['subsequent_price'];
                            $group_offer->save(false);
                        }
                    }
                }
                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }
                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = $new_order_details['order']['c_country_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = Yii::$app->params['default_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];


                //array_push(array, var)ing all the orders to queue
                    Yii::$app->queue->push(new DelhiAirport([
                           'order_id' => $model->id_order,
                           'order_status' => 'confirmed'
                    ]));
                    // Yii::$app->queue->push(new DelhiAirport([
                    //     'order_id' => $model->id_order,
                    //     'order_status' => 'confirmed'
                    // ]));
                if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                    $razorpay = Yii::$app->Common->createRazorpayLink($_POST['travel_email'], $_POST['cutomer_mobile'], $_POST['Order']['luggage_price'], $model->id_order, $role_id);

                    /*after new mail Integration  */
                    $cust_order_details['order_details'] = $new_order_details;
                    User::sendemail($_POST['email'],"Order Pre Payment Text",'order_pre_payment_tax',$cust_order_details);
                } 
                $model1['order_details']=Order::getorderdetails($model->id_order);
                if($model->payment_method == 'COD'){

                    /*User::sendconfirmationemail($_POST['email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation_kiosk',$model1);*/

                    $registered = $_GET['registered'];
                    if($registered == 1){
                        User::sendemail($model1['order_details']['order']['customer_email'],"Welcome to Carter X",'welcome_customer',$customers);
                    }
                }else{

                    /*User::sendconfirmationemail($_POST['email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1);*/
                    // if($_POST['Order']['order_transfer']==1){
                    //     $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirms_pdf_city_transfer');
                    // }else{
                    //     $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                    // }
                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');

                    
                    $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                    User::sendEmailExpressMultipleAttachment($_POST['email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det,$setCC);

                    /* invoice attachments */
                    $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_payments_pdf_template');
                    User::sendemailexpressattachment($model1['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det,$setCC);


                    $registered = $_GET['registered'];
                    if($registered == 1){
                        User::sendemail($_POST['email'],"Welcome to Carter X",'welcome_customer',$customers);
                    }
                }
                $mobile = $_GET['mobile'];
                /*COde for Mail and sms End*/
                return $this->redirect(['update-kiosk', 'id' => $model->id_order, 'mobile' => $mobile]);
                //return $this->redirect(['order/kiosk-orders']);
            }
        }else{
            if(!empty($model['o']->geterrors()) || !empty($model['osd']->geterrors()))
            {
                return $this->render('corporate_create_general_order_kiosk', [
                    'model' => $model,
                    'customer_details' => $customers,
                    'employee_model' =>$employee_model,
                    'regionModel' =>$model['re'],
                ]);
            }
            return $this->render('corporate_create_general_order_kiosk', [
                'model' => $model,
                'customer_details' => $customers,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        }
    }

    public function actionCreateCorporateGeneralOrderKiosk($mobile){
        $model['o'] = new Order();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $customers = Customer::findOne(['mobile' => $mobile]);
        $model['osd']->scenario = 'create_order_general';
        $model['o']->scenario = 'crate_corporate_general';
        $model['bw']=new BagWeightType();
        $employee_model = new Employee();
        $employee_model->scenario = 'corporate_general';

        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o'], $model['osd']])) {
          //  print_r($_POST);exit;
            $DeliveryRes = Yii::$app->Common->getExpectedDeliveryDateTime($_POST['Order']['delivery_type'],$_POST['Order']['service_type'],$_POST['Order']['fk_tbl_order_id_slot'],date('Y-m-d',strtotime($_POST['Order']['order_date'])));
            $api = new Api('rzp_test_VSvN3uILIxekzY', 'Flj35MJPZTJZ0WiTBlynY14k');
            $model = new Order();
            if(!empty($DeliveryRes)){
                $model->delivery_datetime = isset($DeliveryRes['delivery_date_time']) ? $DeliveryRes['delivery_date_time'] : "";
                $model->delivery_time_status = isset($DeliveryRes['delivery_status']) ? $DeliveryRes['delivery_status'] : "";
            }
            $model->corporate_id = $_POST['Order']['corporate_id'];
            $model->corporate_type = Yii::$app->Common->getCorporateType($_POST['Order']['corporate_id']);
            $model->fk_tbl_airport_of_operation_airport_name_id=$_POST['Employee']['airport'];
            $model->location=$_POST['Order']['location'];
            $model->sector = $_POST['Order']['sector'];
            $model->weight = $_POST['Order']['weight'];
            $model->service_type = $_POST['Order']['service_type'];
            $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));
            //$_POST['Order']['order_date'];
            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['luggage_price'];

            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = $_POST['Order']['dservice_type'];

            $model->flight_number = $_POST['Order']['flight_number'];
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));

            $model->insurance_price = 0;
            $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
            if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                $model->someone_else_document_verification = 1;
                $model->flight_verification = 1;
            }
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
            //$model->payment_method = $_POST['OrderPaymentDetails']['payment_type'];
            // $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
            // $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
            $model->fk_tbl_order_status_id_order_status = 1;
            $model->order_status = 'Yet to be confirmed';
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

            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                $model = Order::findOne($model->id_order);
                $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                $model->save(false);
                if(!empty($_FILES['Order']['name']['ticket'])){
                    $up = $employee_model->actionFileupload('ticket',$model->id_order);
                }
                if(!empty($_FILES['Order']['name']['someone_else_document'])){
                    $up = $employee_model->actionFileupload('someone_else_document',$model->id_order);
                }
                Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                /*order history end*/

                /*order total table*/
                $this->updateordertotal($model->id_order, $_POST['Order']['luggage_price'], $_POST['Order']['service_tax_amount'], $model->insurance_price);
                /*order total*/

                //code OrderItems...
                $luggage_det = LuggageType::find()->where(['corporate_id'=>$_POST['Order']['corporate_id']])->one();

               if(!empty($_POST['OrderItems'])){
                foreach ($_POST['OrderItems'] as $key => $items) {
                    //print_r($items);exit;
                    $order_items = new OrderItems();
                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                    $order_items->item_price = $luggage_det['base_price'];
                    if($model->weight==1 && $model->corporate_id == 7){
                        $order_items->bag_weight = $items['fk_tbl_order_items_id_bag_range'];
                        $order_items->bag_type = $items['fk_tbl_order_items_id_bag_type'];
                    }else{
                        $order_items->bag_weight = '<15';
                        $order_items->bag_type = 'checkin';

                    }
                    $order_items->save();
                }

              }
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
                $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;
                $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
                if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                    $order_spot_details->hotel_booking_verification  = 1;
                    $order_spot_details->invoice_verification = 1;
                }
                if($order_spot_details->save()){
                    if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                        $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                    }
                }
                $razorpay = Yii::$app->Common->createRazorpayLink($_POST['travel_email'], $_POST['cutomer_mobile'], $_POST['Order']['luggage_price'], $model->id_order, $role_id);
                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = $new_order_details['order']['c_country_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];
                /*COde for Mail and sms start Dt:02/09/2017J*/
                if($model->corporate_id != 7){
                    if($model->service_type == 1){
                        User::sendsms($customer_number,"Dear Customer, your Order for ".$model->no_of_units." bags is confirmed and your reference number is #".$model->order_number.".-CarterX");

                        $msg_to_airport = "Dear Customer, Your Order #".$model->order_number."   placed by ".$customer_name."  for ".$flight_number." - ".$model->no_of_units." bags via CarterX is confirmed. For all service related queries  contact our customer support on +91-9110635588.  -CarterX";
                                    //Some One else
                        if($model->travel_person==1){

                            User::sendsms($traveller_number,$msg_to_airport );
                        }
                        //Location Contact
                        if($order_spot_details->assigned_person == 1){
                            User::sendsms($location_contact,$msg_to_airport );
                        }
                    }elseif ($model->service_type == 2) {
                        User::sendsms($customer_number,"Dear Customer, your Order #".$model->order_number." placed on is confirmed. Local deliveries will be made on the same day if bags are picked before 3pm. Outstation transfer will be delivered before 3 days  at the most.Thanks carterx.in");

                        $msg_from_airport = "Dear Customer, your Order #".$model->order_number." for ".$flight_number." placed by ".$customer_name." is confirmed. Local deliveries will be made on the same day for bags received before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX";
                        //Some One else
                        if($model->travel_person==1){
                            User::sendsms($traveller_number,$msg_from_airport);
                        }
                        //Location Contact
                        if($order_spot_details->assigned_person == 1){
                            User::sendsms($location_contact,$msg_from_airport);
                        }
                    }
                }else{
                    if($model->service_type == 1){
                        User::sendsms($customer_number,"Dear Customer, your Order #".$model->order_number." for ".$model->no_of_units." bags is confirmed.-CarterX");

                        $msg_to_airport = "Dear Customer, Your Order #".$model->order_number." for ".$flight_number." placed by ".$customer_name." for ".$model->no_of_units." bags via CarterX is confirmed. For all service related queries Log in to your account or contact customer care on +91-6366835588-CarterX";
                                    //Some One else
                        if($model->travel_person==1){

                            User::sendsms($traveller_number,$msg_to_airport );
                        }
                        //Location Contact
                        if($order_spot_details->assigned_person == 1){
                            User::sendsms($location_contact,$msg_to_airport );
                        }
                    }elseif ($model->service_type == 2) {
                        User::sendsms($customer_number,"Dear Customer, your Order #".$model->order_number." for ".$model->no_of_units." bags is confirmed. -CarterX");

                        $msg_from_airport = "Dear Customer, your Order #".$model->order_number." for ".$flight_number." for ".$model->no_of_units." bags placed by ".$customer_name." is confirmed for service. For all service related queries contact our customer support on +91-6366835588-CarterX";
                        //Some One else
                        if($model->travel_person==1){
                            User::sendsms($traveller_number,$msg_from_airport);
                        }
                        //Location Contact
                        if($order_spot_details->assigned_person == 1){
                            User::sendsms($location_contact,$msg_from_airport);
                        }
                    }
                }
                $model1['order_details']=Order::getorderdetails($model->id_order);

                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                /*COde for Mail and sms End*/


                return $this->redirect(['update-kiosk-corporate-form', 'id' => $model->id_order, 'mobile' => $_POST['Order']['travell_passenger_contact']]);
                //return $this->redirect(['order/kiosk-orders']);
            }
        } else {

            return $this->render('corporate_general_kiosk_create_order', [
                'model' => $model,
                'customer_details' => $customers,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        }
    }

    public function actionCreateThirdpartyCorporateGeneralOrderKiosk($mobile){
        $model['o'] = new Order();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $model['om'] = new OrderMetaDetails();
        $model['sta'] = new State();
        $customers = Customer::findOne(['mobile' => $mobile]);
        // $model['osd']->scenario = 'create_order_general';
        $model['o']->scenario = 'create_order_genral';
        $model['bw']=new BagWeightType();
        $OrderZoneDetails = new OrderZoneDetails();
        $employee_model = new Employee();
        // $employee_model->scenario = 'corporate_general';
        $setCC = array();

        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o'], $model['osd']])) {
            // echo "<pre>";print_r($_POST);exit;
            $razorpay_api_key = isset(Yii::$app->params['razorpay_api_key']) ? Yii::$app->params['razorpay_api_key'] : "rzp_test_VSvN3uILIxekzY";
            $razorpay_secret_key = isset(Yii::$app->params['razorpay_secret_key']) ? Yii::$app->params['razorpay_secret_key'] : "Flj35MJPZTJZ0WiTBlynY14k";
            $api = new Api($razorpay_api_key, $razorpay_secret_key);

            $primary_email = CorporateDetails::find()->select(['default_email','default_contact'])->where(['corporate_detail_id'=> Yii::$app->request->post()['Order']['corporate_id']])->One();
            if($primary_email['default_email']){
                array_push($setCC,$primary_email['default_email'],Yii::$app->params['customer_email']);
            }
            $DeliveryRes = Yii::$app->Common->getExpectedDeliveryDateTime($_POST['Order']['delivery_type'],$_POST['Order']['service_type'],$_POST['Order']['fk_tbl_order_id_slot'],date('Y-m-d',strtotime($_POST['Order']['order_date'])));

            $model = new Order();
            if(!empty($DeliveryRes)){
                $model->delivery_datetime = isset($DeliveryRes['delivery_date_time']) ? $DeliveryRes['delivery_date_time'] : "";
                $model->delivery_time_status = isset($DeliveryRes['delivery_status']) ? $DeliveryRes['delivery_status'] : "";
            }
            $corporate_id = Yii::$app->Common->getCorporates($_POST['Order']['corporate_id']);
            $model->corporate_id = $corporate_id->fk_corporate_id;
            // $model->fk_thirdparty_corporate_id = isset($_POST['Order']['corporate_id']) ? $_POST['Order']['corporate_id'] : 0;
            $model->city_pincode = $_POST['Order']['city_pincode'];
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

            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['luggage_price'];

            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $delivery_dates = Yii::$app->Common->selectedSlot($_POST['Order']['fk_tbl_order_id_slot'], $model->order_date, $model->delivery_type);
            
            $model->delivery_date = $delivery_dates['delivery_date'];
            $model->delivery_time = $delivery_dates['delivery_time'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = $_POST['Order']['dservice_type'];

            $model->flight_number = $_POST['Order']['flight_number'];
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));

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

            $model->payment_method = $_POST['OrderPaymentDetails']['payment_type'];
            if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                $model->fk_tbl_order_status_id_order_status = 1;
                $model->order_status = 'Yet to be confirmed';
                
            } else {
                $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'open' : 'Confirmed';
                if(!empty($_POST['total_convayance_amount'])){
                    $model->amount_paid = !empty($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : 0;
                }else {
                    $model->amount_paid = !empty($_POST['Order']['luggage_price']) ? $_POST['Order']['luggage_price'] : 0;
                }
                // $model->amount_paid = ($_POST['Order']['delivery_type'] == 2) ? $_POST['total_convayance_amount'] : $_POST['Order']['luggage_price'];
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
            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                if(($_POST['Order']['delivery_type'] == 2) || (!empty($_POST['convayance_price']))){
                    $outstation_id = isset($_POST['outstation_id']) ? $_POST['outstation_id'] : 0;
                    $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : 0;
                    $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                    $extr_kms = isset($_POST['extr_kms']) ? $_POST['extr_kms'] : 0;
                    $service_tax_amount = isset($_POST['service_tax_amount']) ? $_POST['service_tax_amount'] : 0;
                    $convayance_price = isset($_POST['convayance_price']) ? $_POST['convayance_price'] : 0;
                    $date = date('Y-m-d H:i:s');
                    Yii::$app->db->createCommand("insert into tbl_order_zone_details (orderId,outstationZoneId,cityZoneId,stateId,extraKilometer,taxAmount,outstationCharge,createdOn) values($model->id_order,$outstation_id,$city_name,$state_name,$extr_kms,$service_tax_amount,$convayance_price,'".$date."')")->execute();

                    // $OrderZoneDetails = new OrderZoneDetails;
                    // $OrderZoneDetails->orderId = $model->id_order;
                    // $OrderZoneDetails->outstationZoneId = isset($_POST['outstation_id']) ? $_POST['outstation_id'] : 0;
                    // $OrderZoneDetails->cityZoneId = isset($_POST['city_name']) ? $_POST['city_name'] : 0;
                    // $OrderZoneDetails->stateId = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                    // $OrderZoneDetails->extraKilometer = isset($_POST['extr_kms']) ? $_POST['extr_kms'] : 0; 
                    // $OrderZoneDetails->taxAmount = isset($_POST['service_tax_amount']) ? $_POST['service_tax_amount'] : 0;
                    // $OrderZoneDetails->outstationCharge = isset($_POST['convayance_price']) ? $_POST['convayance_price'] : 0;
                    // $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');
                    // $OrderZoneDetails->save(true);
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
                $order_payment_details->payment_type = $_POST['OrderPaymentDetails']['payment_type'];
                $order_payment_details->id_employee = Yii::$app->user->identity->id_employee;
                if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                    $order_payment_details->payment_status = 'Not paid';
                }else{
                    $order_payment_details->payment_status = 'Success';
                }
                $order_payment_details->amount_paid = $_POST['total_convayance_amount'] ? $_POST['total_convayance_amount'] : $_POST['Order']['luggage_price'];
                $order_payment_details->value_payment_mode = 'Order Amount';
                $order_payment_details->date_created= date('Y-m-d H:i:s');
                $order_payment_details->date_modified= date('Y-m-d H:i:s');
                $order_payment_details->save();

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
                if($_POST['Order']['delivery_type'] == 2 || $_POST['Order']['delivery_type'] == 1){
                    if($_POST['Order']['order_transfer'] == 1){
                        // if($_POST['Order']['delivery_type'] == 2 && ($_POST['Order']['order_transfer'] == 1)){
                        //     $OrderZoneDetails->orderId = $model->id_order;
                        //     $OrderZoneDetails->outstationZoneId = $_POST['outstation_id'];
                        //     $OrderZoneDetails->cityZoneId = $_POST['city_id'];
                        //     $OrderZoneDetails->stateId = $_POST['State']['idState'];
                        //     $OrderZoneDetails->extraKilometer = $_POST['Order']['extr_kms'];
                        //     $OrderZoneDetails->taxAmount = $_POST['Order']['luggageGST'];;
                        //     $OrderZoneDetails->outstationCharge = $_POST['outstation_charge'];
                        //     $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                        //     $OrderZoneDetails->save(false);
                        // }
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
                        // if($_POST['Order']['delivery_type'] == 2 && ($_POST['Order']['order_transfer'] == 2)){
                        //     $OrderZoneDetails->orderId = $model->id_order;
                        //     $OrderZoneDetails->outstationZoneId = $_POST['outstation_id'];
                        //     $OrderZoneDetails->cityZoneId = $_POST['city_id'];
                        //     $OrderZoneDetails->stateId = $_POST['State']['idState'];
                        //     $OrderZoneDetails->extraKilometer = $_POST['Order']['extr_kms'];
                        //     $OrderZoneDetails->taxAmount = $_POST['Order']['luggageGST'];;
                        //     $OrderZoneDetails->outstationCharge = $_POST['outstation_charge'];
                        //     $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');
                            
                        //     $OrderZoneDetails->save(false);
                        // }
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
                // //code OrderSpotDetails...
                // $order_spot_details = new OrderSpotDetails();
                // $order_spot_details->fk_tbl_order_spot_details_id_order = $model->id_order;
                // $order_spot_details->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                // $order_spot_details->person_name = $_POST['OrderSpotDetails']['person_name'];
                // $order_spot_details->person_mobile_number = $_POST['OrderSpotDetails']['person_mobile_number'];
                // $order_spot_details->mall_name = $_POST['OrderSpotDetails']['mall_name'];
                // $order_spot_details->store_name = $_POST['OrderSpotDetails']['store_name'];
                // $order_spot_details->business_name = $_POST['OrderSpotDetails']['business_name'];
                // if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                //     $order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'];
                //     $order_spot_details->hotel_name = $_POST['OrderSpotDetails']['hotel_name'];
                // }
                // if($model->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                // {
                //     $order_spot_details->assigned_person = 1;
                // }
                // $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                // $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                // $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                // $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                // $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                // $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                // $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;
                // $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
                // if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                //     $order_spot_details->hotel_booking_verification  = 1;
                //     $order_spot_details->invoice_verification = 1;
                // }
                // if($order_spot_details->save()){
                //     if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                //         $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                //     }
                // }

                if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'yet_to_be_confirmed', '');
                    if(!empty($_POST['total_convayance_amount'])){
                        $amount_paid = !empty($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : 0;
                    }else {
                        $amount_paid = !empty($_POST['Order']['luggage_price']) ? $_POST['Order']['luggage_price'] : 0;
                    }
                    $razorpay = Yii::$app->Common->createRazorpayLink($_POST['travel_email'], $_POST['cutomer_mobile'], $amount_paid, $model->id_order, $role_id);
                } else { 
                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                }
                // User::sendEmailExpressMultipleAttachment($_POST['travel_email'],"CarterX Confirmed Order #".$model->order_number."",'yet_to_be_confirmed',$model1,$attachment_det,$setCC);

                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = $new_order_details['order']['c_country_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];
                /*COde for Mail and sms start Dt:02/09/2017J*/

                    $model1['order_details']=$new_order_details;
                    $cargo_status = Yii::$app->Common->checkCargoStatus($new_order_details['corporate_details']['corporate_detail_id']);
                    //confirmation mail
                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationThirdpartyCorporatePdf($model1,'order_confirmation_corporate_pdf_template');
                    // $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'passenger_security.pdf';
                    if($cargo_status){
                        $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'cargo_security.pdf';    
                    } else {
                        $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'passenger_security.pdf';
                    }
                    User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$new_order_details->order_number."",'order_confirmation',$model1,$attachment_det);
                    //invoice mail
                    $Invoice_attachment_det = Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_payments_pdf_template');
                    User::sendemailexpressattachment($model1['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);

                // $model1['order_details']=Order::getorderdetails($model->id_order);
                // $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                // $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                // User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                /*COde for Mail and sms End*/
                return $this->redirect(['update-kiosk-corporate-form', 'id' => $model->id_order, 'mobile' => $_POST['Order']['travell_passenger_contact']]);
                //return $this->redirect(['order/kiosk-orders']);
            }
        } else {

            return $this->render('corporate_thirdparty_general_kiosk_create_order', [
                'model' => $model,
                'customer_details' => $customers,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        }
    }


    public function actionSearchCustomer(){
        $customer_details = new Customer();
        $customer_details->scenario = 'search';
        $model = new Order();//Yii::$app->Common->getCorporateIds(Yii::$app->user->identity->id_employee);
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;

        if ($customer_details->load(Yii::$app->request->post())) {
            $corporate_id = isset($_POST['Order']['corporate_id']) ? $_POST['Order']['corporate_id'] : 0;
            $id_employee = Yii::$app->user->identity->id_employee;
            $search = Yii::$app->request->post()['search'];
            if($search == 'search'){
                $mobile = Yii::$app->request->post()['Customer']['mobile'];
                $customers = Customer::findOne(['mobile' => $mobile]);
                if($customers){
                    if($role_id == 11){
                        return $this->redirect(['create-corporate-general-order-kiosk', 'mobile' => $mobile, 'registered' =>0]);
                    }else if(($role_id > 11) && ($role_id < 17)){
                        return $this->redirect(['create-thirdparty-corporate-general-order-kiosk', 'mobile' => $mobile, 'registered' =>0, 'corporate_id' => $corporate_id]);
                    }else if($role_id == 17){
                        return $this->redirect(['create-super-subscriber-general-order','mobile'=>$mobile,'registered' =>0, 'corporate_id' => $corporate_id,'confirmation_number' => $_POST['Order']['confirmation_number']]);
                    }else{
                        return $this->redirect(['create-general-order-kiosk', 'mobile' => $mobile, 'registered' =>0]);
                    }
                }else{
                    return $this->render('create_customer', [
                        'customer_details' => $customer_details,
                        'model' => $model
                    ]);
                }
            }
            if($search == 'register'){
                $post = Yii::$app->request->post()['Customer'];
                $mobile = $post['mobile'];
                $customer_details->mobile = $post['mobile'];
                $customer_details->name = $post['name'];
                $customer_details->gender = $post['gender'];
                $customer_details->email = $post['email'];
                $customer_details->fk_tbl_customer_id_country_code = $post['fk_tbl_customer_id_country_code'];
                if($customer_details->save()){
                    $data['name'] = $customer_details->name;
                    $bookingCustomer_smsContent = 'Dear Customer, Welcome to CarterX! Login to www.carterx.in with registered mobile number to track your order placed by '.$customer_details->name.' under Manage Orders. For all delivery related queries contact our customer support on +91-9110635588. '.PHP_EOL.'CarterX';
                    User::sendsms('8792509266',$bookingCustomer_smsContent);
                    User::sendemail($customer_details->email,"Welcome to Carterx",'welcome_customer',$data);
                    //print_r('expression');exit;

                    $client['client_id']=base64_encode($post['email'].mt_rand(100000, 999999));
                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($post['email'].mt_rand(100000, 999999)); 
                    $client['user_id']=$customer_details->id_customer;
                    User::addClient($client);
                    if($role_id == 11){
                        return $this->redirect(['create-corporate-general-order-kiosk', 'mobile' => $mobile, 'registered' =>1]);
                    } else if($role_id > 11){
                        return $this->redirect(['create-thirdparty-corporate-general-order-kiosk', 'mobile' => $mobile, 'registered' =>1,'corporate_id' => $corporate_id]);
                    } else if($role_id == 17){
                        return $this->redirect(['CreateSuperSubscriberGeneralOrder','mobile'=>$modile,'registered' =>0, 'corporate_id' => $corporate_id]);
                    } else{
                        return $this->redirect(['create-general-order-kiosk', 'mobile' => $mobile, 'registered' =>1]);
                    }
                    // return $this->redirect(['create-general-order-kiosk', 'mobile' => $mobile, 'registered' =>1 ]);
                }
            }
        }
        return $this->render('create_customer', [
            'customer_details' => $customer_details,
            'model' => $model
        ]);

    }

    public function actionUpdateKiosk($id){
        $order_details = Order::getorderdetails($id);
        $payment_details = OrderPaymentDetails::find()->where(['id_order' => $id])->one();
        $model = new PorterxAllocations();

        return $this->render('update-kiosk-form', [
                'order_details'=>$order_details, 'model' => $model, 'id_order'=>$id, 'payment_details'=>$payment_details]);
    }

    public function actionUpdateKioskCorporate($id){
        $order_details = Order::getorderdetails($id);
        $payment_details = OrderPaymentDetails::find()->where(['id_order' => $id])->one();
        $model = new PorterxAllocations();

        return $this->render('update-kiosk-form-corporate', [
                'order_details'=>$order_details, 'model' => $model, 'id_order'=>$id, 'payment_details'=>$payment_details]);
    }

    public function actionUpdateKioskCorporateForm($id){
        $order_details = Order::getorderdetails($id);
        $payment_details = OrderPaymentDetails::find()->where(['id_order' => $id])->one();
        $model = new PorterxAllocations();

        return $this->render('kiosk_general_update_corporate_form', [
                'order_details'=>$order_details, 'model' => $model, 'id_order'=>$id, 'payment_details'=>$payment_details]);
    }

    public function actionNoPorterxAllocation($id_order){
        if($_POST){
            $bustton = $_POST['cancel'];
            $mobile  = $_POST['mobile'];
            if($bustton){
            $order = Order::find()->where(['id_order'=>$id_order])->one();
            $order->fk_tbl_order_status_id_order_status = 9;
            $order->order_status = 'Arrival into airport warehouse';
            $order->porter_modified_datetime = date('Y-m-d H:i:s');
            $order->save(false);

            $model = new PorterxAllocations();
            $model->tbl_porterx_allocations_id_employee = $_POST['employee_id'];
            $model->tbl_porterx_allocations_id_order = $id_order;
            $model->date_created = date('Y-m-d H:i:s');
            $model->date_modified = date('Y-m-d H:i:s');
            $model->save();
            // $orderHistory = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order])->one();
            // $orderHistory->to_tbl_order_status_id_order_status = 8;
            // $orderHistory->to_order_status_name = 'Picked Up';
            // $orderHistory->save(false);
            $orderHistory = new OrderHistory();
            $orderHistory->fk_tbl_order_history_id_order = $id_order;
            $orderHistory->to_tbl_order_status_id_order_status = 8;
            $orderHistory->to_order_status_name = 'Picked Up';
            $orderHistory->date_created = date('Y-m-d H:i:s');
            if($orderHistory->save(false)){
                $orderHistoryNew = new OrderHistory();
                $orderHistoryNew->fk_tbl_order_history_id_order = $id_order;
                $orderHistoryNew->to_tbl_order_status_id_order_status = 9;
                $orderHistoryNew->to_order_status_name = 'Arrival into airport warehouse';
                $orderHistoryNew->date_created = date('Y-m-d H:i:s');
                $orderHistoryNew->save(false);
            }

            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            if($role_id == 10){
                $customer_details = Order::getorderdetails($id_order);
                $customers  = Order::getcustomername($mobile);

                $order_number = $customer_details['order']['order_number'];
                $payment_method = $customer_details['order']['payment_method'];
                $flight_number = $customer_details['order']['flight_number'];
                $luggage_price = $customer_details['order']['luggage_price'];
                $order_date = date('Y-m-d', strtotime($customer_details['order']['order_date']));
                //$delivery_service = $customer_details['order']['order_date'];
                $slot_scehdule = $customer_details['order']['slot_start_time'].' To'.$customer_details['order']['slot_end_time'];
                $arrival_gate_time = $customer_details['order']['meet_time_gate'];
                $customer_name = ($customers) ? $customers->name : '';
                $country_code = $customer_details['order']['c_country_code'];

                $travell_passenger_contact = $country_code.$customer_details['order']['travell_passenger_contact'];
                $location_contact_number = $country_code.$customer_details['order']['location_contact_number'];

                if($customer_details['order']['service_type'] == 1){
                    $msg_to_travell_passenger_person_order_placed = "Hello, Order".$order_number." reference ".$flight_number." placed by ".$customer_name." is confirmed for service on ".$order_date." between ".$slot_scehdule.". Login to www.carterx.in with number: ".$mobile." to track the order placed by ".$customer_name." under 'Manage Orders'. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";

                    $msg_to_location_contact_person_order_placed = "Dear Customer, Welcome to CarterX where luggage transfer is simplified.  Order".$order_number." reference ".$flight_number." placed by ".$customer_name."  is confirmed for service on ".$order_date." between ".$slot_scehdule.". Login to www.carterx.in with number: ".$mobile." to track the order placed by ".$customer_name." under 'Manage Orders'. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";


                    User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_order_placed);

                    User::sendsms($location_contact_number,$msg_to_location_contact_person_order_placed);
                }
                if($customer_details['order']['service_type'] == 2){
                        if($payment_method == 'COD'){
                            $msg_to_travell_passenger_person_order_placed = "Hello, Order ".$order_number."  reference ".$flight_number." placed by ".$customer_name." via CarterX on ".date('Y-m-d', strtotime($order_date))." is confirmed and picked for service on ".date('Y-m-d', strtotime($order_date)).". Payment due for the order ".$luggage_price." as mode selected is Payment on delivery.  Kindly pay the same before/on delivery  via RazorPay link sent to this number.  Delivery will be made on receiving complete payment only. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                        }else{
                            $msg_to_travell_passenger_person_order_placed = "Hello, Order ".$order_number." reference ".$flight_number." placed by ".$customer_name." via CarterX on ".date('Y-m-d', strtotime($order_date))." is confirmed & picked on ".date('Y-m-d', strtotime($order_date))."  between ".$slot_scehdule.".  Login to www.carterx.in with this number to track the order under 'Manage Orders'. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";
                        }
                        User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_order_placed);
                        User::sendsms($location_contact_number,$msg_to_travell_passenger_person_order_placed);
                }
             }
             if($bustton == 1){
                return $this->redirect(['order/index']);
             }else{
                return $this->redirect(['order/kiosk-orders']);
             }
            }
        }
    }
    public function actionAssignPorterx($id_order)
    {

        $model = new PorterxAllocations();
        //$model->scenario = 'insert';

        $ispreviousassigned = PorterxAllocations::find()->where(['tbl_porterx_allocations_id_order'=>$id_order])->one();

        if(!empty($ispreviousassigned))
        {
            $ispreviousassigned->delete();
        }

        if (Yii::$app->request->post()) {
            if(isset($_POST['mobile'])){
                $mobile  = $_POST['mobile'];
            }else{
                $mobile  = '';
            }
            $model->tbl_porterx_allocations_id_employee = $_POST['tbl_porterx_allocations_id_employee'];
            $model->tbl_porterx_allocations_id_order = $_POST['tbl_porterx_allocations_id_order'];
            $model->date_created = date('Y-m-d H:i:s');
            $model->date_modified = date('Y-m-d H:i:s');
            //echo "<pre>";print_r($model);exit;
            $model->save();

            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            if($role_id == 10){
                $customer_details = Order::getorderdetails($id_order);
                $customers  = Order::getcustomername($mobile);
                //echo "<pre>";print_r($customer_details);exit;
                $payment_method = $customer_details['order']['payment_method'];
                $order_number = $customer_details['order']['order_number'];
                $number_of_bags = $customer_details['order']['no_of_units'];
                $flight_number = $customer_details['order']['flight_number'];
                $order_date = $customer_details['order']['order_date'];

                $slot_start_time = date('h:i a', strtotime($customer_details['order']['slot_start_time']));
                $slot_end_time = date('h:i a', strtotime($customer_details['order']['slot_end_time']));
                $slot_scehdule = $slot_start_time.' To'.$slot_end_time;

                $arrival_gate_time = $customer_details['order']['meet_time_gate'];
                $customer_name = ($customers) ? $customers->name : '';
                $country_code = $customer_details['order']['c_country_code'];
                $luggage_price = $customer_details['order']['luggage_price'];

                $travell_passenger_contact = $country_code.$customer_details['order']['travell_passenger_contact'];
                $location_contact_number = $country_code.$customer_details['order']['location_contact_number'];

                if($customer_details['order']['service_type'] == 1){
                    $msg_to_travell_passenger_person_order_placed = "Dear Customer, your Order #".$order_number." for ".$number_of_bags." placed by ".$customer_nam." is confirmed. Order Value: ".$customer_details['order']['amount_paid']." All receipts for cash/Card/razorpay transactions will be sent on successful delivery. Outstation delivery timelines: upto 3 days. -CarterX";

                    $msg_to_location_contact_person_order_placed = "Dear Customer, your Order #".$order_number." for ".$number_of_bags." placed by ".$customer_nam." is confirmed. Order Value: ".$customer_details['order']['amount_paid']." All receipts for cash/Card/razorpay transactions will be sent on successful delivery. Outstation delivery timelines: upto 3 days. -CarterX";

                    $msg_to_travell_passenger_person_pickup = "Dear Customer, Welcome to CarterX where luggage transfer is simplified. Your Order".$order_number." reference ".$flight_number." placed by ".$customer_name." is confirmed & Picked up. Login to www.carterx.in with number: ".$mobile." to track the order placed by ".$customer_name." under Manage Orders. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";

                    $msg_location_contact_person_pickup = "Dear Customer, Welcome to CarterX where luggage transfer is simplified. Your Order".$order_number." reference ".$flight_number." placed by ".$customer_name." is confirmed & Picked up. Login to www.carterx.in with number: ".$mobile." to track the order placed by ".$customer_name." under Manage Orders. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";

                    User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_order_placed);

                    User::sendsms($location_contact_number,$msg_to_location_contact_person_order_placed);

                    User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_pickup);

                    User::sendsms($location_contact_number,$msg_location_contact_person_pickup);
                }

                if($customer_details['order']['service_type'] == 2){
                    $msg_to_travell_passenger_person_order_placed = "Dear Customer, your Order #".$order_number." placed by ".$customer_name."  for ".$number_of_bags." bags placed is confirmed. Order Value: ".$customer_details['order']['amount_paid']."  Delivery Timeline for outstation bookings: upto 3 days.All receipts for cash/Card/razorpay transactions will be sent on successful delivery. -CarterX";

                    $msg_to_location_contact_person_order_placed  = "Dear Customer, your Order #".$order_number." placed by ".$customer_name."  for ".$number_of_bags." bags placed is confirmed. Order Value: ".$customer_details['order']['amount_paid']."  Delivery Timeline for outstation bookings: upto 3 days.All receipts for cash/Card/razorpay transactions will be sent on successful delivery. -CarterX";

                    $msg_to_travell_passenger_person_pickup = "Dear Customer, Welcome to CarterX where luggage transfer is simplified. Your Order ".$order_number." reference ".$flight_number." placed by ".$customer_name." on ".$order_date." is confirmed & picked up on Date of service.  Login to www.carterx.in with number: ".$mobile." to track the order placed by ".$customer_name." under 'Manage Orders'. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";

                    $msg_location_contact_person_pickup = "Dear Customer, Welcome to CarterX where luggage transfer is simplified. Your Order ".$order_number." reference ".$flight_number." placed by ".$customer_name." on ".$order_date." is confirmed & Picked up on Date of service .  Login to www.carterx.in with number: ".$mobile." to track the order placed by ".$customer_name." under 'Manage Orders'. For all delivery related queries Log in to your account or contact customer care on +919110635588.".PHP_EOL."Thanks carterx.in ";
                    $current_date = date("Y-m-d 00:00:00");
                    if($current_date == $order_date){
                        if($payment_method == 'COD'){
                            $msg_to_travell_passenger_person_order_placed = "Hello, Order ".$order_number."  reference ".$flight_number." placed by ".$customer_name." via CarterX on ".date('Y-m-d', strtotime($order_date))." is confirmed and picked for service on ".date('Y-m-d', strtotime($order_date)).". Payment due for the order ".$luggage_price." as mode selected is Payment on delivery.  Kindly pay the same before/on delivery via RazorPay link sent to this number.  Delivery will be made on receiving complete payment only. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                        }else{
                            $msg_to_travell_passenger_person_order_placed = "
                            Hello, Order ".$order_number." refernce ".$flight_number." placed by ".$customer_name." via CarterX on ".date('Y-m-d', strtotime($order_date))." is confirmed for service on ".date('Y-m-d', strtotime($order_date))." between ".$slot_scehdule.". Login to www.carterx.in with this number to track the order placed by under 'Manage Orders'. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                        }
                        User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_order_placed);
                        User::sendsms($location_contact_number,$msg_to_travell_passenger_person_order_placed);
                    }else{
                        User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_order_placed);

                        User::sendsms($location_contact_number,$msg_to_location_contact_person_order_placed);

                        User::sendsms($travell_passenger_contact,$msg_to_travell_passenger_person_pickup);

                        User::sendsms($location_contact_number,$msg_location_contact_person_pickup);
                    }
                }
            }
            $orderDet=Order::find()->where(['id_order'=>$id_order])->with(['orderSpotDetails','fkTblOrderIdCustomer'])->one();

            if(empty($orderDet->fkTblOrderIdCustomer))
            {
                $corp_det = Order::getcorporatedetails($orderDet->corporate_id);
                $email = $corp_det['default_email'];
                $name = $corp_det['name'];
                $mobile = $corp_det['default_contact'];
            }else{
                $email = $orderDet->fkTblOrderIdCustomer->email;
                $name = $orderDet->fkTblOrderIdCustomer->name;
                $mobile =$orderDet->fkTblOrderIdCustomer->mobile;
            }

            $service_type=$orderDet->service_type;
            $empDet = Employee::find()->where(['id_employee'=>$_POST['tbl_porterx_allocations_id_employee']])->one();

            if($orderDet->service_type==2)
            {
            Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status=3, order_status='Open' where id_order=".$id_order)->execute();
            $new_order_history = [ 'fk_tbl_order_history_id_order'=>$id_order,
                'from_tbl_order_status_id_order_status'=>$orderDet['fk_tbl_order_status_id_order_status'], //2,
                'from_order_status_name'=>$orderDet['order_status'],  //'Confirmed',
                'to_tbl_order_status_id_order_status'=>3,
                'to_order_status_name'=>'Open',
                'date_created'=> date('Y-m-d H:i:s')
               ];

            $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$new_order_history)->execute();
            }

            //$order_2_pending_amount = $orderDet->luggage_price - $orderDet->amount_paid ;
            $order_2_pending_amount = $orderDet->modified_amount ;

            $travell_passenger_contact = $orderDet->travell_passenger_contact;
            $istravel_person = $orderDet->travel_person;


            $model1['order_details']=Order::getorderdetails($id_order);
            if($model1['order_details']['order']['corporate_type']==1){
                $customer_name = $model1['order_details']['order']['travell_passenger_name'];
            } else{
                $customer_name =  $model1['order_details']['order']['customer_name'];
            }
            $customer_number = $model1['order_details']['order']['c_country_code'].$model1['order_details']['order']['customer_mobile'];
            $traveller_number = $model1['order_details']['order']['traveler_country_code'].$model1['order_details']['order']['travell_passenger_contact'];
            $location_contact = Yii::$app->params['default_code'].$model1['order_details']['order']['location_contact_number'];
            $corp_ref_text = ($orderDet->corporate_id == 0) ? "": " ".$orderDet->flight_number;

            if($orderDet->reschedule_luggage==1)
            {

                $prev_order_id = min($orderDet->id_order, $orderDet->related_order_id);
                $orderDet1 = Order::getOrderdetails($prev_order_id);
                $istravel_person = $orderDet1['order']['travel_person'];
                $travell_passenger_contact = $orderDet1['order']['travell_passenger_contact'];

                $traveller_number = $orderDet1['order']['traveler_country_code'].$orderDet1['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$orderDet1['order']['location_contact_number'];

                $model1['reschedule_order_details'] = Order::getorderdetails($orderDet->related_order_id);
                $order_1_pending_amount = $model1['reschedule_order_details']['order']['luggage_price']-$model1['reschedule_order_details']['order']['amount_paid'];
                $order_2_pending_amount = $orderDet->luggage_price - $orderDet->amount_paid ;
                $pending_amount = $orderDet->luggage_price + $orderDet1['order']['modified_amount'];

                $text = '';
                if($orderDet->corporate_id == 0)
                {
                $text = ($pending_amount == 0 ) ? '' : ($pending_amount >0) ? 'Amount pending Rs.'.$pending_amount.' due to Order Modification under Order#'.$model1['reschedule_order_details']['order']['order_number'].' & this current service. Kindly pay the same before delivery.' : 'Refund of Rs.'.abs($pending_amount).' is initiated into your source account of payment';
                }

                $if_previous_order_undelivered = Order::getIsundelivered($prev_order_id);

                if($if_previous_order_undelivered)
                {
                    $msg_reschedule_forced = 'Dear Customer, your Order #'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by CarterPorter due to no response between  '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order.'.$text.''.PHP_EOL.' Thanks carterx.in';

                    User::sendsms($customer_number,$msg_reschedule_forced);
                    if($istravel_person==1)
                    {
                        User::sendsms($traveller_number,$msg_reschedule_forced);
                    }
                    //location contact
                    if($orderDet->orderSpotDetails->assigned_person==1)
                    {
                        User::sendsms($location_contact,$msg_reschedule_forced);
                    }
                }
                else
                {
                    $text1 = '';
                    if($orderDet->corporate_id == 0)
                    {
                        if($orderDet1['order']['modified_amount']){
                            $text1 = '';
                        }else{
                            $text1 = ($pending_amount == 0 ) ? '' : ($pending_amount > 0 ) ? 'Amount pending Rs.'.$pending_amount.' due. Kindly pay the same before delivery.' : 'Refund of Rs.'.abs($pending_amount).' is initiated into your source account of payment';
                        }
                    }
                    $msg_reschedule_voluntary = 'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is ready scheduled for delivery at '.date('h:i A', strtotime($orderDet->meet_time_gate)).' at GATE1 (opp Cafe Noir, Airport Departures section) on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text1.' '.PHP_EOL.'Thanks Carterx.in';

                    User::sendsms($customer_number,$msg_reschedule_voluntary);
                    if($istravel_person==1)
                    {
                        User::sendsms($traveller_number,$msg_reschedule_voluntary);
                    }
                    //location contact
                    if($orderDet->orderSpotDetails->assigned_person==1)
                    {
                        User::sendsms($location_contact,$msg_reschedule_voluntary);
                    }
                }
            }
            else
            {

                if($orderDet->orderSpotDetails){
                    $amount_msg = '';
                    if($orderDet->corporate_id == 0)
                    {
                        $amount_msg = ($order_2_pending_amount > 0) ? "Payment due for the order Rs. ".$order_2_pending_amount." ." : " ";
                    }
                    if($orderDet->corporate_type == 1){
                        $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);

                    }else{
                        $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);
                    }
                    // if($service_type==1){
                    //     $customer_message = 'Dear Customer, your Order #'.$orderDet->order_number. 'is  scheduled for Delivery at Airport .Amount due for the order' .$amount_msg.' . Please make the payment immediately to avoid delays at delivery. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';
                    //     $traveller_message = 'Dear Customer, your Order #'.$orderDet->order_number.' placed by '.$customer_name.' is  scheduled for Delivery at Airport .Amount due for the order '.$amount_msg.'. Please make the payment immediately to avoid delays at delivery. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';
                    //     $location_message = 'Dear Customer, your Order #'.$orderDet->order_number.' placed by '.$customer_name.' is  scheduled for Delivery at Airport .Amount due for the order '.$amount_msg.'. Please make the payment immediately to avoid delays at delivery. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';
                    // }else{
                    //     $customer_message ='Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. '.$amount_msg.'. Please make the payment immediately for smoother delivery experience. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;
                    //     $traveller_message = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. '.$amount_msg.'. Please make the payment immediately for smoother delivery experience. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;
                    //     $location_message = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. '.$amount_msg.'. Please make the payment immediately for smoother delivery experience. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;
                    // }
                    // $service_type==1 ? User::sendsms($customer_number,$customer_message) : User::sendsms($customer_number,$customer_message);
                    // /*SMS to assigned person*/
                    // if($orderDet->travel_person==1){
                    //    $service_type==1 ? User::sendsms($traveller_number,$traveller_message) : User::sendsms($traveller_number,$traveller_message);
                    // }
                    // if($orderDet->orderSpotDetails->assigned_person==1)
                    // {
                    //     $service_type==1 ? User::sendsms($location_contact,$location_message) : User::sendsms($location_contact,$location_message);
                    // }
                }
            }
            return $this->redirect(['order/kiosk-orders']);
        }
    }
    public function actionUploadImages($option){
        switch ($option) {
            case "order_image":
                if(!empty($_FILES['order_image']['name']))
                {
                    foreach($_FILES["order_image"]["tmp_name"] as $key=>$tmp_name)
                    {
                        $extension = explode(".", $_FILES["order_image"]["name"][$key]);
                        $extension[0] = str_replace(' ', '', $extension[0]);
                        $before_pack = "before_pack_".time().$extension[0].".".$extension[1];
                        $after_pack = "after_pack_".time().$extension[0].".".$extension[1];
                        $damaged_item = "damaged_item_".time().$extension[0].".".$extension[1];
                        $rename_order_image = ($_POST['before_after_damaged'] == 0) ? $before_pack : (($_POST['before_after_damaged'] == 1) ? $after_pack : $damaged_item);
                        $delivered_items = "delivered_item_".time().$extension[0].".".$extension[1];
                        $rename_order_image = ($_POST['before_after_damaged'] == 0) ? $before_pack : (($_POST['before_after_damaged'] == 1) ? $after_pack : (($_POST['before_after_damaged'] == 2) ? $damaged_item : $delivered_items));

                        $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_order_image;
                        $upload = move_uploaded_file($_FILES['order_image']['tmp_name'][$key],$path);

                        $result = ['image'=>$rename_order_image,
                                'fk_tbl_order_images_id_order'=>$_GET['id_order'],
                                'before_after_damaged'=>$_POST['before_after_damaged']];
                        Yii::$app->db->createCommand()->insert('tbl_order_images',$result)->execute();
                    }
                    if($result){
                        if(isset(Yii::$app->request->post()['image_upload'])){
                            return $this->redirect(['order/kiosk-order-update', 'id' => $_GET['id_order']]);
                        }elseif ($_POST['kiosk_update'] == 'corporate') {
                            return $this->redirect(['update-kiosk-corporate', 'id' => $_GET['id_order'], 'mobile' => Yii::$app->request->post()['mobile']]);
                        }else{
                            return $this->redirect(['update-kiosk', 'id' => $_GET['id_order'], 'mobile' => Yii::$app->request->post()['mobile']]);
                        }
                    }else{
                        echo Json::encode(['status'=>false,'message'=>'Upload Failed']);
                    }
                    /*$extension = explode(".", $_FILES["order_image"]["name"]);
                    $extension[0] = str_replace(' ', '', $extension[0]);
                    $before_pack = "before_pack_".time().$extension[0].".".$extension[1];
                    $after_pack = "after_pack_".time().$extension[0].".".$extension[1];
                    $damaged_item = "damaged_item_".time().$extension[0].".".$extension[1];
                    $rename_order_image = ($_POST['before_after_damaged'] == 0) ? $before_pack : (($_POST['before_after_damaged'] == 1) ? $after_pack : $damaged_item);
                    $delivered_items = "delivered_item_".time().$extension[0].".".$extension[1];
                    $rename_order_image = ($_POST['before_after_damaged'] == 0) ? $before_pack : (($_POST['before_after_damaged'] == 1) ? $after_pack : (($_POST['before_after_damaged'] == 2) ? $damaged_item : $delivered_items));


                    $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_order_image;
                    if(move_uploaded_file($_FILES['order_image']['tmp_name'],$path))
                    {
                        $result = ['image'=>$rename_order_image,
                                'fk_tbl_order_images_id_order'=>$_GET['id_order'],
                                'before_after_damaged'=>$_POST['before_after_damaged']];
                        Yii::$app->db->createCommand()->insert('tbl_order_images',$result)->execute();
                    }
                    else
                    {
                        echo Json::encode(['status'=>false,'message'=>'Upload Failed']);
                    }
                    if(isset(Yii::$app->request->post()['image_upload'])){
                        return $this->redirect(['order/kiosk-order-update', 'id' => $_GET['id_order']]);
                    }else{
                        return $this->redirect(['update-kiosk', 'id' => $_GET['id_order'], 'mobile' => Yii::$app->request->post()['mobile']]);
                    }*/
                }
                else
                {
                    return $this->redirect(['update-kiosk', 'id' => $_GET['id_order'], 'mobile' => Yii::$app->request->post()['mobile']]);
                }
                break;
        }
    }
    public function actionDeleteorderimage($type, $oii, $oi, $mobile)
    {
        $isorderimage = OrderImages::find()->where(['id_order_image'=>$oii])->one();
        if($isorderimage){
            if($isorderimage['image'] != '')
            {
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$isorderimage['image'];
                unlink($path);
                $isorderimage->delete();
                return $this->redirect(['update-kiosk', 'id' => $oi, 'mobile' => $mobile]);
            }
        }

    }

public function actionAirport($id){
    $countAirport=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>$id])
            ->count();
    $airports=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>$id])
            ->all();
     // $airports = ArrayHelper::map($airports,'airport_name_id','airport_name');
            echo "<option value=''>Select Airport</option>";
            if($countAirport > 0){
                foreach ($airports as $airport) {
                    //print_r($airports);exit;
                    echo "<option value='$airport->airport_name_id'>$airport->airport_name</option>";
                   // echo "<option value='$row->id_slots'>$row->time_description</option>";
                }
            }else{
                echo "<option> - </option>";
            }
}

public function actionDeliveryType($id){
            $countDelivery=DeliveryServiceType::find()
                    ->where(['order_type'=>$id])
                    ->count();
            $delivery=DeliveryServiceType::find()
                    ->where(['order_type'=>$id])
                    ->all();
            // echo "<option value=''>Delivery Service Type</option>";
            if($countDelivery > 0){
                foreach ($delivery as $row) {
                    echo "<option value='$row->id_delivery_type'>$row->delivery_category</option>";
                }
            }else{
                echo "<option> - </option>";
            }
}

public function actionGetState($id){
            $countState=State::find()
                    ->where(['airport_id'=>$id])
                    ->count();
            $state=State::find()
                    ->where(['airport_id'=>$id])
                    ->all();
            if(empty($countState)){
                $citycountState=State::find()
                    ->where(['city_id'=>$id])
                    ->count();
                $cityState=State::find()
                        ->where(['city_id'=>$id])
                        ->all();
            }else{
                $citycountState=State::find()
                    ->where(['airport_id'=>$id])
                    ->count();
                $cityState=State::find()
                        ->where(['airport_id'=>$id])
                        ->all();
            }
            echo "<option value=''>Select State</option>";
            if($citycountState > 0){
                foreach ($cityState as $row) {
                    echo "<option value='$row->idState'>$row->stateName</option>";
                }
            }else{
                echo "<option> - </option>";
            }
}

public function actionGetStateRegion($id){
            $countState=State::find()
                    ->where(['city_id'=>$id])
                    ->count();
            $state=State::find()
                    ->where(['city_id'=>$id])
                    ->all();
            echo "<option value=''>Select State</option>";
            if($countState > 0){
                foreach ($state as $row) {
                    echo "<option value='$row->idState'>$row->stateName</option>";
                }
            }else{
                echo "<option> - </option>";
            }
}
public function actionRegion($id){
    if($id == 20){
        $countRegion=CityOfOperation::find()->where('region_id = 1')
            ->count();
        $regions=CityOfOperation::find()->where('region_id = 1')
                ->all();
    }else if($id == 19){
        $countRegion=CityOfOperation::find()->where('region_id = 2')
            ->count();
        $regions=CityOfOperation::find()->where('region_id = 2')
                ->all();
    }else if($id == 30){
        $countRegion=CityOfOperation::find()->where('region_id = 3')
            ->count();
        $regions=CityOfOperation::find()->where('region_id = 3')
                ->all();
    }else if($id == 31){
        $countRegion=CityOfOperation::find()->where('region_id = 4')
            ->count();
        $regions=CityOfOperation::find()->where('region_id = 4')
                ->all();
    }else if($id == 37){
        $countRegion=CityOfOperation::find()->where(['in', 'region_id',[5]])
            ->count();
        $regions=CityOfOperation::find()->where(['in', 'region_id',[5]])
                ->all();
    }else if($id == 43){
        $countRegion=CityOfOperation::find()->where(['in', 'region_id',[6]])
            ->count();
        $regions=CityOfOperation::find()->where(['in', 'region_id',[6]])
                ->all();
    }else if($id == 44){
        $countRegion=CityOfOperation::find()->where(['in', 'region_id',[7]])
            ->count();
        $regions=CityOfOperation::find()->where(['in', 'region_id',[7]])
                ->all();
    }else if($id == 45){
        $countRegion=CityOfOperation::find()->where(['in', 'region_id',[8]])
            ->count();
        $regions=CityOfOperation::find()->where(['in', 'region_id',[8]])
                ->all();
    }else if($id == 46){
        $countRegion=CityOfOperation::find()->where(['in', 'region_id',[9]])
            ->count();
        $regions=CityOfOperation::find()->where(['in', 'region_id',[9]])
                ->all();
    }else{
        $countRegion = '';
        $regions = [];
    }
    echo "<option value=''>Select Region</option>";
    if($countRegion > 0){
        foreach ($regions as $region) {
            //print_r($airports);exit;
            echo "<option value='$region->region_id'>$region->region_name</option>";
           // echo "<option value='$row->id_slots'>$row->time_description</option>";
        }
    }else{
        echo "<option> - </option>";
    } 
}
public function actionCorporateAirport(){
    $id = $_POST['corporate_id'];
    if($id == 20){
        $countAirport=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>1])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>1])
            ->all();
    }else if($id == 19){
        $countAirport=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>2])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>2])
            ->all();
    }else if($id == 30){
        $countAirport=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>3])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>3])
            ->all();
    }else if($id == 31){
        $countAirport=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>4])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['fk_tbl_city_of_operation_region_id'=>4])
            ->all();
    }else if($id == 37){
        $countAirport=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[5]])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[5]])
            ->all();
    }else if($id == 43){
        $countAirport=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[6]])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[6]])
            ->all();
    }else if($id == 44){
        $countAirport=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[7]])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[7]])
            ->all();
    }else if($id == 45){
        $countAirport=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[8]])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[8]])
            ->all();
    }else if($id == 46){
        $countAirport=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[9]])
            ->count();
        $airports=AirportOfOperation::find()
            ->where(['in', 'fk_tbl_city_of_operation_region_id',[9]])
            ->all();
    }else{
        $countAirport = '';
        $airports = [];
    }
    echo "<option value=''>Select Airport</option>";
    if($countAirport > 0){
        foreach ($airports as $airport) {
            echo "<option value='$airport->airport_name_id'>$airport->airport_name</option>";
        }
    }else{
        echo "<option> - </option>";
    }
}
    /*select slot time with validation of time, order date and service type*/
    public function actionSelectSlotTime1($serviceTypeID, $order_date){
        if($serviceTypeID==1){
            $rows=\app\models\Slots::find()
                //->select(['id_slots','time_description'])
                ->where(['id_slots'=>[1,2,3]])
                ->all();
            echo "<option value=''>Select Slot....</option>";
            if(count($rows)>0){
                foreach($rows as $row){
                    $time = date('H:i:s', strtotime($row['slot_start_time']) - 5400); //slot should be disabled 1.30 hr prior slot start time
                    $time2 =  strtotime($time) - strtotime(date('H:i:s'));
                    if($time2 > 0 || (strtotime(date($order_date)) - strtotime(date('Y-m-d')) > 0) ){
                      echo "<option value='$row->id_slots'>$row->time_description</option>";
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
                foreach($rows as $row){
                    if ($i = 0) {
                        $time = date('H:i:s', strtotime($row['slot_end_time']));
                    }else{
                        $time = date('H:i:s', strtotime($row['slot_end_time']) - 1);
                    }

                    $time2 = strtotime($time) - strtotime(date('H:i:s'));
                    if($time2 > 0 || (strtotime(date($order_date)) - strtotime(date('Y-m-d')) > 0) ){
                        echo "<option value='$row->id_slots'>$row->time_description</option>";
                    }
                    $i++;
                }
            }else{
                echo "<option value=''>NO Slot</option>";
            }
        }
    }

    public function actionSelectSlotTimeCorporate($serviceTypeID, $order_date, $order_type = false){
        $date_now = date("Y-m-d");
        // print_r($serviceTypeID);  print_r($order_date); exit;
        // if($order_date > $date_now){
            if($serviceTypeID==1){
                $rows=\app\models\Slots::find()
                    //->select(['id_slots','time_description'])
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
        // }else{
        //     echo "<option value=''>No Available Slot</option>";
        // }
    }


    public function actionSelectSlotTime($serviceTypeID, $order_date, $order_type = false, $order_transfer = false){
        if($order_type == 2 && $order_transfer == 1){
            $rows=\app\models\Slots::find()
                //->select(['id_slots','time_description'])
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
        }else{
            if($serviceTypeID==1){ 
                $rows=\app\models\Slots::find()
                    //->select(['id_slots','time_description'])
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
    }

    public function actionSelectLuggageOffers($airport_id){
        $luggage_types = LuggageType::getLuggageTypesAirportVerionTwo($airport_id);

        echo '<button type="button" class="btn btn-success btn-lg" data-toggle="modal" data-target="#myModal">Order Offers</button>';
        $a = '<div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">
      
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Order Offers</h4>
        </div>
        <div class="modal-body">
          <div class="table-responsive">          
          <table class="table table-bordered">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th class="text-center">Luggage Name</th>
                <th class="text-center">Base Price</th>
                <th class="text-center">Offer Price</th> 
              </tr>
              ';

              $i=1;
              if($luggage_types){
            foreach ($luggage_types as $key => $row) {
            $a .= '<tbody>
              <tr>
                <td class="text-center"> '.$i.' </td>
                <td class="text-center"> '.$row['luggage_type'].' </td>
                <td class="text-center"> '.$row['base_price'].' </td>
                <td class="text-center"> '.$row['offer_price'].'</td>
              </tr>
            </tbody>';
             $i++; } }

          $a .= '</table>
        </div>
        <div class="table-responsive">          
          <table class="table table-bordered">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th class="text-center">Subsequent Price</th>
              </tr>';
                $i=1;
                if($luggage_types){
                foreach ($luggage_types as $key1 => $group) {
                        $a .= '<tbody>
                              <tr>
                                <td class="text-center"> '.$i.' </td>
                                <td class="text-center"> '.$group['subsequent_price'].' </td>
                              </tr>
                            </tbody>';
                    $i++; }
                }
          $a .= '</table>
        </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
      
    </div>
  </div>';

        echo $a;
    }

    public function actionSelectDepArrDate($serviceTypeID, $order_date)
    {
        if($serviceTypeID==1)
        {
            $order_date = date('Y-m-d',strtotime($order_date));
            $order_date1 = date('Y-m-d', strtotime('+1 day', strtotime($order_date)));

            echo "<option value='$order_date'>".date($order_date)."</option>";
            echo "<option value='$order_date1'>".date($order_date1)."</option>";
        }else{
            $order_date = date('Y-m-d',strtotime($order_date));
            echo "<option value='$order_date'>".date($order_date)."</option>";
        }
    }


    public function actionSelectedSlot()
    {
        if(!empty($_POST)){
            $selrow=\app\models\Slots::find()
                ->where(['id_slots'=>$_POST['id_slot']])
                ->asArray()
                ->one();
            if($_POST['id_slot']==1)
            {
                $response['meet_time_gate'] = '12:00 PM';
                $response['departure_time'] = '01:00 PM';
            }
            if($_POST['id_slot']==2)
            {
                $response['meet_time_gate'] = '03:00 PM';
                $response['departure_time'] = '04:00 PM';
            }
            if($_POST['id_slot']==3)
            {
                $response['meet_time_gate'] = '06:00 PM';
                $response['departure_time'] = '07:00 PM';
            }
            if($_POST['id_slot']==4)
            {
                $response['meet_time_gate'] = '03:30 PM';
                $response['arrival_time'] = '03:00 PM';
            }
            if($_POST['id_slot']==5)
            {
                $response['meet_time_gate'] = '12:25 AM';
                $response['arrival_time'] = '11:55 PM'; //date change
            }
            if($_POST['id_slot']==7)
            {
                $response['meet_time_gate'] = '02:00 AM';
                $response['departure_time'] = '03:00 AM'; //date change
            }
            if($_POST['id_slot']==9)
            {
                $response['meet_time_gate'] = '09:00 AM';
                $response['departure_time'] = '10:00 AM'; //date change
            }

            echo json_encode(array('status' => true, 'response' => $response ));
        }

    }

    public function actionSelectedSlotCorporate()
    {
        if(!empty($_POST)){
            $selrow=\app\models\Slots::find()
                ->where(['id_slots'=>$_POST['id_slot']])
                ->asArray()
                ->one();
            if($_POST['id_slot']==1)
            {
                $response['meet_time_gate'] = '02:00 PM';
                $response['departure_time'] = '03:00 PM';
            }
            if($_POST['id_slot']==2)
            {
                $response['meet_time_gate'] = '06:00 PM';
                $response['departure_time'] = '07:00 PM';
            }
            if($_POST['id_slot']==3)
            {
                $response['meet_time_gate'] = '10:00 PM';
                $response['departure_time'] = '11:00 PM';
            }
            if($_POST['id_slot']==4)
            {
                $response['meet_time_gate'] = '03:30 PM';
                $response['arrival_time'] = '03:00 PM';
            }
            if($_POST['id_slot']==5)
            {
                $response['meet_time_gate'] = '12:25 AM';
                $response['arrival_time'] = '11:55 PM'; //date change
            }
            if($_POST['id_slot']==7)
            {
                $response['meet_time_gate'] = '12:00 AM';
                $response['departure_time'] = '01:00 PM'; //date change
            }
            if($_POST['id_slot']==9)
            {
                $response['meet_time_gate'] = '07:00 AM';
                $response['departure_time'] = '08:00 AM'; //date change
            }

            echo json_encode(array('status' => true, 'response' => $response ));
        }

    }


    public function actionSelectOrderDate()
    {
        echo '2017-09-10';
    }

    public function actionSelectLuggageType($corporateId){
        $rows=\app\models\LuggageType::find()
                ->select(['id_luggage_type','luggage_type'])
                ->where(['corporate_id'=>$corporateId])
                ->all();
            //echo "<option value=''>Select Luggage Type....</option>";
            if(count($rows)>0){
                foreach($rows as $row){
                  echo "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
                }
            }else{
                echo "<option value=''>No Luggage</option>";
            }
    }

    public function actionSelectCorporateLuggageType($corporateId){
        $rows=\app\models\ThirdpartyCorporate::find()
                ->select(['corporate_name', 'thirdparty_corporate_id'])
                ->where(['thirdparty_corporate_id'=>$corporateId])
                ->one();
        echo "<option value='2'>$rows->corporate_name</option>";
    }

    public function actionSelectOrderType($corporateId){
        $rows=\app\models\ThirdpartyCorporate::find()
                ->select(['order_type'])
                ->where(['thirdparty_corporate_id'=>$corporateId])
                ->one();
        // echo "<pre>";print_r($rows);exit;
        echo "<option value=''>Select Order Type</option>";
        // if(count($rows)>0){
        if(!empty($rows)){
            if($rows->order_type == 1){
                echo "<option value='$rows->order_type'>Local</option>";
            }elseif ($rows->order_type == 2) {
                echo "<option value='$rows->order_type'>Outstation</option>";
            }elseif ($rows->order_type == 3) {
                echo "<option value='1'>Local</option>";
                echo "<option value='2'>Outstation</option>";
            }
        }else{
            echo "<option value=''>No Order Type</option>";
        }
    }

    public function actionSelectOrderTransfer($corporateId){
        $rows=\app\models\ThirdpartyCorporate::find()
                ->select(['transfer_type'])
                ->where(['thirdparty_corporate_id'=>$corporateId])
                ->one();
        // echo "<pre>";print_r($rows);exit;
        echo "<option value=''>Select Order Transfer</option>";
        // if(count($rows)>0){
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

    public function actionSelectLuggageTypeGeneralOrder($corporateId){
        $rows=\app\models\LuggageType::find()
                ->select(['id_luggage_type','luggage_type'])
                ->where(['corporate_id'=>$corporateId])
                ->all();
            echo "<option value=''>Select Luggage Type</option>";
            if(count($rows)>0){
                foreach($rows as $row){
                  echo "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
                }
            }else{
                echo "<option value=''>No Luggage</option>";
            }
    }

    public function actionLuggageType(){
      // echo $_POST['corporateId'];echo $_POST['idd'];exit;
        $strr='';
       $rows=\app\models\LuggageType::find()
                ->select(['id_luggage_type','luggage_type'])
                ->where(['corporate_id'=>$_POST['corporateId']])
                ->all();
        if(count($rows)>0){
                foreach($rows as $row){
                  $strr= "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
                }
            }else{
                $strr= "<option value=''>No Luggage</option>";
            }
        $str = "<div class='row'><div class='col-sm-4 col-md-3'><div class='form-group field-orderitems-fk_tbl_order_items_id_luggage_type'><label class='control-label' for='orderitems-fk_tbl_order_items_id_luggage_type'>Luggage Type </label><select id='orderitems-fk_tbl_order_items_id_luggage_type' class='form-control clsnoluggage' name=OrderItems[".($_POST['idd']-1)."][fk_tbl_order_items_id_luggage_type]>".$strr."</select><input class='btn btn-danger clsdelete' value='X' style='margin-left:263px;margin-top:-54px;' type='button' id=".$_POST['idd']."></div></div></div>";

            echo $str;
    }
    public function actionCorporateLuggageType(){
        $rows=\app\models\ThirdpartyCorporate::find()
                ->select(['corporate_name', 'thirdparty_corporate_id'])
                ->where(['thirdparty_corporate_id'=>$_POST['corporateId']])
                ->one();
        $strr='';

        $strr= "<option value='2'>$rows->corporate_name</option>";
        $str = "<div class='row'><div class='col-sm-4 col-md-3'><div class='form-group field-orderitems-fk_tbl_order_items_id_luggage_type'><label class='control-label' for='orderitems-fk_tbl_order_items_id_luggage_type'>Luggage Type </label><select id='orderitems-fk_tbl_order_items_id_luggage_type' class='form-control clsnoluggage' name=OrderItems[".($_POST['idd']-1)."][fk_tbl_order_items_id_luggage_type]>".$strr."</select><input class='btn btn-danger clsdelete' value='X' style='margin-left:263px;margin-top:-54px;' type='button' id=".$_POST['idd']."></div></div></div>";

        echo $str;
    }


    public function actionLuggageType1(){
        // echo $_POST['corporateId'];echo $_POST['idd'];exit;
        echo "<script>
        $(document).ready(function(){
            // var weight_id = $('#added_weight_id_'+add_luggage_count).val();
            // if(weight_id == 8){
            //     $('.above_weight'+add_luggage_count).show();
            // }else{
            //     $('.above_weight'+add_luggage_count).hide();
            // }
        });</script>";
            $strr= '';
            //print_r($_POST['luggageId']);exit;
            if($_POST['delivery_type'] == 2){
                $rows = LuggageType::find()
                    ->select(['id_luggage_type','luggage_type'])
                    ->where(['id_luggage_type'=>2])->orWhere(['id_luggage_type' => 4])
                    ->all();
            }else{
                $rows=\app\models\LuggageType::find()
                    ->select(['id_luggage_type','luggage_type'])
                    ->where(['corporate_id'=>$_POST['corporateId']])
                    ->andWhere(['status' => 1])
                    ->all();
            }


                $strr .= "<option value=''>Select Luggage Type</option>";
                if(count($rows)>0){
                    foreach($rows as $row){
                      $strr .= "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
                    }
                }else{
                    $strr= "<option value=''>No Luggage</option>";
                }
                $str = "<div id='add_luggage_count_".$_POST['totalLuggage']."' class='col-md-12 add_luggage'><div class='col-sm-4 luggage_ipad'><div class='form-group field-orderitems-fk_tbl_order_items_id_luggage_type'><label class='control-label' for='orderitems-fk_tbl_order_items_id_luggage_type'>Luggage Type </label><select id='orderitems-fk_tbl_order_items_id_luggage_type' required='required' class='form-control clsnoluggage luggage_".$_POST['totalLuggage']."' name=OrderItems[".($_POST['totalLuggage'])."][fk_tbl_order_items_id_luggage_type] onchange='get_weight_range_luggage(".$_POST['totalLuggage'].");'>".$strr."</select></div></div>";
               $wee= '';
               $extra_weight= '';
               $weights=\app\models\WeightRange::find()
                        ->select(['id_weight_range','weight_range'])
                        ->where(['fk_tbl_weight_range_id_luggage_type'=>$_POST['luggageId']])
                        ->all();
                $wee .= "<option value=''>Select Weight Range</option>";
                    if(count($weights)>0){
                        foreach($weights as $weight){
                          $wee .= "<option value='$weight->id_weight_range'>$weight->weight_range</option>";
                        }
                    }else{
                        $wee= "<option value=''>No WeightRange</option>";
                    }
                    $extra_weight .= "<option value=''>Select Extra</option>";
                    for ($i=6; $i < 20; $i++) {
                        $extra_weight .= "<option value='$i'>$i</option>";
                    }
                $we = "<div class='col-sm-4'><div class='form-group field-orderitems-fk_tbl_order_items_id_weight_range'><label class='control-label' for='orderitems-fk_tbl_order_items_id_weight_range'>Luggage Weight </label><select id='orderitems-fk_tbl_order_items_id_weight_range' required='required' class='form-control added_weight_id_".$_POST['totalLuggage']."' name=OrderItems[".($_POST['totalLuggage'])."][fk_tbl_order_items_id_weight_range] onchange='get_weight_range(".$_POST['totalLuggage'].", ".$_POST['luggageId'].");'>".$wee."</select><div class='col-sm-2'><input onclick='remove_luggage(".$_POST['totalLuggage'].");' class='btn btn-danger clsdelete' value='X' style='margin-left:263px;margin-top:-54px;' type='button' id=".$_POST['idd']."></div></div></div><div class='col-sm-2 above_weight' style='display: block;padding-top: 25px;'><select class='form-control above_weight_".$_POST['totalLuggage']."' id='extra_weight' name=OrderItems[".($_POST['totalLuggage'])."][item_weight] onchange='get_weight_range(".$_POST['totalLuggage'].", ".$_POST['luggageId'].");'> ".$extra_weight." </select></div></div>";
            // }else{
            //     $rows=\app\models\LuggageType::find()
            //         ->select(['id_luggage_type','luggage_type'])
            //         ->where(['corporate_id'=>$_POST['corporateId']])
            //         ->all();

            //     $strr .= "<option value=''>Select Luggage Type</option>";
            //     if(count($rows)>0){
            //         foreach($rows as $row){
            //           $strr .= "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
            //         }
            //     }else{
            //         $strr= "<option value=''>No Luggage</option>";
            //     }
            //     $str = "<div id='add_luggage_count_".$_POST['add_luggage_count']."' class='col-md-12 add_luggage'><div class='col-sm-4 luggage_ipad'><div class='form-group field-orderitems-fk_tbl_order_items_id_luggage_type'><label class='control-label' for='orderitems-fk_tbl_order_items_id_luggage_type'>Luggage Type </label><select id='orderitems-fk_tbl_order_items_id_luggage_type' required='required' class='form-control clsnoluggage luggage_".$_POST['add_luggage_count']."' name=OrderItems[".($_POST['idd']-1)."][fk_tbl_order_items_id_luggage_type] onchange='get_weight_range_luggage(".$_POST['add_luggage_count'].");'>".$strr."</select></div></div>";
            //    $wee= '';
            //    $extra_weight= '';
            //    $weights=\app\models\WeightRange::find()
            //             ->select(['id_weight_range','weight_range'])
            //             ->where(['fk_tbl_weight_range_id_luggage_type'=>$_POST['luggageId']])
            //             ->all();
            //     $wee .= "<option value=''>Select Weight Range</option>";
            //         if(count($weights)>0){
            //             foreach($weights as $weight){
            //               $wee .= "<option value='$weight->id_weight_range'>$weight->weight_range</option>";
            //             }
            //         }else{
            //             $wee= "<option value=''>No WeightRange</option>";
            //         }
            //         $extra_weight .= "<option value=''>Select Extra</option>";
            //         for ($i=6; $i < 20; $i++) {
            //             $extra_weight .= "<option value='$i'>$i</option>";
            //         }
            //     $we = "<div class='col-sm-4'><div class='form-group field-orderitems-fk_tbl_order_items_id_weight_range'><label class='control-label' for='orderitems-fk_tbl_order_items_id_weight_range'>Luggage Weight </label><select id='orderitems-fk_tbl_order_items_id_weight_range' required='required' class='form-control added_weight_id_".$_POST['add_luggage_count']."' name=OrderItems[".($_POST['idd']-1)."][fk_tbl_order_items_id_weight_range] onchange='get_weight_range(".$_POST['add_luggage_count'].", ".$_POST['luggageId'].");'>".$wee."</select><div class='col-sm-2'><input onclick='remove_luggage(".$_POST['add_luggage_count'].");' class='btn btn-danger clsdelete' value='X' style='margin-left:263px;margin-top:-54px;' type='button' id=".$_POST['idd']."></div></div></div><div class='col-sm-3 above_weight' style='display: block;padding-top: 25px;'><select class='form-control above_weight_".$_POST['add_luggage_count']."' id='extra_weight' name=OrderItems[".($_POST['idd']-1)."][item_weight] onchange='get_weight_range(".$_POST['add_luggage_count'].", ".$_POST['luggageId'].");'> ".$extra_weight." </select></div></div>";
            // }





            echo $str;
            echo $we;
    }
    public function actionLuggageTypeUpdate(){
        // echo $_POST['corporateId'];echo $_POST['idd'];exit;
        $strr= '';
            if($_POST['delivery_type'] == 2){
                $rows = LuggageType::find()
                    ->select(['id_luggage_type','luggage_type'])
                    ->where(['id_luggage_type'=>2])->orWhere(['id_luggage_type' => 4])
                    ->all();
            }else{
                $rows=\app\models\LuggageType::find()
                    ->select(['id_luggage_type','luggage_type'])
                    ->where(['corporate_id'=>$_POST['corporateId']])
                    ->andWhere(['status' => 1])
                    ->all();
            }
            $strr .= "<option value=''>Select Luggage Type</option>";
            if(count($rows)>0){
                foreach($rows as $row){
                  $strr .= "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
                }
            }else{
                $strr= "<option value=''>No Luggage</option>";
            }
        $str = "<div id='add_luggage_count_".$_POST['totalLuggage']."' class='col-md-8'><div class='col-sm-5'><div class='form-group field-orderitems-fk_tbl_order_items_id_luggage_type'><label class='control-label' for='orderitems-fk_tbl_order_items_id_luggage_type'>Luggage Type </label><select id='orderitems-fk_tbl_order_items_id_luggage_type' required='required' class='form-control clsnoluggage luggage_".$_POST['totalLuggage']."' name=OrderItems[".($_POST['totalLuggage'])."][fk_tbl_order_items_id_luggage_type] onchange='get_weight_range_luggage(".$_POST['totalLuggage'].");'>".$strr."</select></div></div>";

       $wee= '';
       $weights=\app\models\WeightRange::find()
                ->select(['id_weight_range','weight_range'])
                ->where(['fk_tbl_weight_range_id_luggage_type'=>$_POST['luggageId']])
                ->all();
            $wee .= "<option value=''>Select Weight Range</option>";
            if(count($weights)>0){
                foreach($weights as $weight){
                  $wee .= "<option value='$weight->id_weight_range'>$weight->weight_range</option>";
                }
            }else{
                $wee= "<option value=''>No WeightRange</option>";
            }
        $we = "<div class='col-sm-5'><div class='form-group field-orderitems-fk_tbl_order_items_id_weight_range'><label class='control-label' for='orderitems-fk_tbl_order_items_id_weight_range'>Luggage Weight </label><select id='orderitems-fk_tbl_order_items_id_weight_range' required='required' class='form-control added_weight_id_".$_POST['totalLuggage']."' name=OrderItems[".($_POST['totalLuggage'])."][fk_tbl_order_items_id_weight_range] onchange='get_weight_range(".$_POST['totalLuggage'].", ".$_POST['luggageId'].");'>".$wee."</select><div class='col-sm-2'>
            <input type='hidden' name=OrderItems[".($_POST['totalLuggage'])."][item_id] value='0'>
            <input type='hidden' name=OrderItems[".($_POST['totalLuggage'])."][new_luggage] value='1'>
            <input onclick='remove_luggage(".$_POST['totalLuggage'].");' class='btn btn-danger clsdelete' value='X' style='margin-left:176px;margin-top:-54px;' type='button' id=".$_POST['totalLuggage']."></div></div></div></div>";

            echo $str;
            echo $we;
    }

    public function actionBagType(){
      // echo $_POST['corporateId'];echo $_POST['idd'];exit;
        $strr='';
       // $rows=\app\models\LuggageType::find()
       //          ->select(['id_luggage_type','luggage_type'])
       //          ->where(['corporate_id'=>$_POST['corporateId']])
       //          ->all();
       //  if(count($rows)>0){
       //          foreach($rows as $row){
       //            $strr= "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
       //          }
       //      }else{
       //          $strr= "<option value=''>No Luggage</option>";
       //      }
      $arr = $_POST['idd'];
         //print_r($_POST['idd']);exit;
        $str = "<div class='row' id=delete_".$arr."><div class='col-md-2'>AirAsia Bag $arr</div><div class='col-md-3'><div class='form-group field-orderitems-fk_tbl_order_items_id_bag_range'><label class='control-label' for='orderitems-fk_tbl_order_items_id_bag_range'>Bag Range</label><select id='orderitems-fk_tbl_order_items_id_bag_range' class='form-control' name=OrderItems[".($_POST['idd']-1)."][fk_tbl_order_items_id_bag_range]>
        <option value='<15'><15Kgs</option>
        <option value='15-20'>15-20</option>
        <option value='20-25'>20-25</option>
        <option value='25-30'>25-30</option>
        <option value='30-40'>30-40</option>
        </select></div></div><div class='col-md-3'><div class='form-group field-orderitems-fk_tbl_order_items_id_bag_type'><label class='control-label' for='orderitems-fk_tbl_order_items_id_bag_type'>Bag Type</label><select id='orderitems-fk_tbl_order_items_id_bag_type' class='form-control' name=OrderItems[".($_POST['idd']-1)."][fk_tbl_order_items_id_bag_type]><option value='checkin'>CheckIn</option><option value='sports'>Sports Bag</option></select></div></div></div>";


            echo $str;
    }


    public function updateordertotal($id_order, $luggage_price, $service_tax, $insurance_price)
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

    public function actionPriceDetails(){
        if(isset($_POST['oty'])){
            $whereCond = ['corporate_id'=>$_POST['cid'],'order_type'=>$_POST['oty']];
        } else {
            $whereCond = ['corporate_id'=>$_POST['cid']];
        }
        $row = LuggageType::find()
                        ->select(['base_price'])
                        ->where($whereCond)
                        ->one();

        // if(count($row)>0){
        if(!empty($row)){
            echo $row->base_price;
        }
    }

    public function actionCheckCorporate(){
        $corporate_id = ThirdpartyCorporate::find()
                        ->select(['fk_corporate_id'])
                        ->where(['thirdparty_corporate_id'=>$_POST['cid']])
                        ->one();
        $row = CorporateDetails::find()
                        ->select(['corporate_type'])
                        ->where(['corporate_detail_id'=>$corporate_id])
                        ->one();
        // if(count($row)>0){
        if(!empty($row)){
            if(!($row->corporate_type == 5 || $row->corporate_type == 4)){
                echo true;
            }else{
                echo false;
            }
        }
    }

    public function actionAirportPriceDetails(){
        $pricedetails = CorporateLuggagePriceDetails::find()
                        ->select(['base_price'])
                        ->where(['airport'=>$_POST['airportId']])
                        ->one();

        if(count($pricedetails)>0){
            echo $pricedetails->base_price;
        }
    }

    public function actionInsuranceCalculation(){
        $ids = $_POST['luggage_ids'];
        if($ids){
           foreach ($ids as $key => $value) {
                $row = LuggageType::find()
                        ->select(['group_type'])
                        ->where(['id_luggage_type'=>$value['luggagetype']])
                        ->groupBy(['id_luggage_type'])
                        ->one();
                if($row){
                    if($row['group_type'] == 1){
                        $insurance[] = 4;
                    }else if($row['group_type'] == 2){
                        $insurance[] = 8;
                    }else if($row['group_type'] == 3){
                        $insurance[] = 4;
                    }
                }
            }
            $count = array_sum($insurance);
            echo $count;
        }else{
            echo 0;
        }
    }
    public function actionGetItemPrice(){
        $luggage_id = $_POST['luggage_id'];
        $weight_id = $_POST['weight_id'];
        $order_id = $_POST['order_id'];
        $data = $_POST['datas'];

        $prices = array_column($data, 'item_price');
        $min_price = min($prices);

        $deleted_amount = '';
        if($data){
            foreach ($data as $key => $row) {
                if(isset($row['deleted'])){
                    $deleted_amount += $row['item_price'];
                }
            }
            echo $deleted_amount;
        }
        // if($luggage_id && $weight_id){
        //     $result = OrderItems::find()
        //             ->select(['item_price'])
        //             ->where(['fk_tbl_order_items_id_luggage_type'=>$luggage_id, 'fk_tbl_order_items_id_weight_range'=>$weight_id, 'fk_tbl_order_items_id_order'=>$order_id])
        //             ->one();
        //     if($result){
        //         $item_price = $result['item_price'];
        //         echo $item_price;
        //     }else{
        //         echo 0;
        //     }
        // }
        else{
            echo 0;
        }
    }

    public function actionPriceDetailsLuggageId(){
        $luggage_id = $_POST['luid'];
        $weight_id = $_POST['weightId'];
        $airport_id = $_POST['airport_id'];
        if(isset($_POST['extra_weight_id'])){
            $extra_weight_id = $_POST['extra_weight_id'];
        }else{
            $extra_weight_id = '';
        }
        //print_r($row);
        //exit;
        if($luggage_id && $weight_id != 0 && $airport_id){
            $row = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price, wr.weight_range_price FROM tbl_luggage_offers lo LEFT JOIN tbl_weight_range wr ON lo.luggage_type = wr.fk_tbl_weight_range_id_luggage_type where lo.luggage_type =$luggage_id AND lo.airport =$airport_id AND wr.fk_tbl_weight_range_id_luggage_type = $luggage_id AND wr.id_weight_range =$weight_id ")->queryOne();

            $base_price = $row['base_price'];
            $offer_price = $row['offer_price'];
            $weight_range_price = $row['weight_range_price'];

            if($weight_id == 8 && $extra_weight_id){
                if($base_price && $offer_price){
                    if($extra_weight_id){
                        $fragile_count = $extra_weight_id - 5;
                        $price = 100 * $fragile_count;

                        $total_price = $offer_price + $weight_range_price + $price;
                    }else{
                        $total_price = $offer_price + $weight_range_price;
                    }
                    echo $total_price;
                }else if($base_price && !$offer_price){
                    if($extra_weight_id){
                        $fragile_count = $extra_weight_id - 5;
                        $price = 100 * $fragile_count;

                        $total_price = $base_price + $weight_range_price + $price;
                    }else{
                        $total_price = $base_price + $weight_range_price;
                    }
                    echo $total_price;
                }else{
                    echo 0;
                }
            }else if($weight_id == 8 && !$extra_weight_id){
                if($base_price && $offer_price){
                    $total_price = $offer_price + $weight_range_price;
                    echo $total_price;
                }else if($base_price && !$offer_price){
                    $total_price = $base_price + $weight_range_price;
                    echo $total_price;
                }else{
                    echo 0;
                }
            }else{
                if($base_price && $offer_price){
                    $total_price = $offer_price + $weight_range_price;
                    echo $total_price;
                }else if($base_price && !$offer_price){
                    $total_price = $base_price + $weight_range_price;
                    echo $total_price;
                }else{
                    echo 0;
                }
            }
        }else{
            $row = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price FROM tbl_luggage_offers lo where lo.luggage_type =$luggage_id AND lo.airport =$airport_id")->queryOne();
            if(count($row)>0){
                $base_price = $row['base_price'];
                $offer_price = $row['offer_price'];

                if($base_price && $offer_price){
                    $total_price = $offer_price;
                    echo $total_price;
                }else if($base_price && !$offer_price){
                    $total_price = $base_price;
                    echo $total_price;
                }else{
                    echo 0;
                }
            }
        }

    }

    public function actionPriceDetailsLuggageIdUpdate(){
        $luggage_details = json_decode($_POST['data']);
        $action = $_POST['action'];
        $item_price = '';
        if($luggage_details && $action == 'delete'){
              //print_r($luggage_details);exit;
              $old_count = $_POST['old_count'];
              $new_count = $_POST['new_count'];

              $airport_id = $_POST['airport_id'];
              $insurance = (isset($_POST['insurance'])) ? $_POST['insurance'] : '';

              $existing_luggages = $_POST['existing'];
              $existing_luggage_price = $existing_luggages + $existing_luggages * (Yii::$app->params['gst_percent']/100);

              $total_price = 0;
              $extra_price = 0;
              $weight_range_price = 0;

              $modified_amount = '';
              $total_luggage_price = '';
              $subsequent = 0;
              $total_insurance = 0;

              if($old_count == $new_count){
                foreach ($luggage_details as $key => $delete) {
                   if(($delete->luggagetype == 1 || $delete->luggagetype == 2 || $delete->luggagetype == 3 || $delete->luggagetype == 5 || $delete->luggagetype == 6) && $delete->deleted == 0){
                      $insurance_value = 4;
                   }else if($delete->luggagetype == 4 && $delete->deleted == 0){
                      $insurance_value = 8;
                   }else{
                      $insurance_value = 0;
                   }
                   if($delete->deleted == 0 && $delete->luggagetype){
                      $total_price = $total_price + $delete->item_price;
                   }
                   if($insurance){
                     $total_insurance += $insurance_value;
                   }else{
                    $total_insurance = 0;
                   }
                   if($delete->deleted == 1){
                      $deleteArray[$key] = $_POST['item_price'];
                   }
                }
                $delete_value = min($deleteArray);
                $modified_amount_with_tax = "-".number_format($delete_value, 2);
                $amount = $total_price;
              }else{
                $airport_id = $_POST['airport_id'];
                $insurance = (isset($_POST['insurance'])) ? $_POST['insurance'] : '';

                $existing_luggages = $_POST['existing'];
                $existing_luggage_price = $existing_luggages + $existing_luggages * (Yii::$app->params['gst_percent']/100);

                $total_price = 0;
                $extra_price = 0;
                $weight_range_price = 0;

                $modified_amount = '';
                $total_luggage_price = '';
                $subsequent = 0;
                $total_insurance = 0;

                foreach ($luggage_details as $k => $update) {
                   $value = (object) $update;
                   $subsequent_price = '';
                   $offer_price = '';
                   if(($insurance) && ($value->luggagetype == 1 || $value->luggagetype == 2 || $value->luggagetype == 3 || $value->luggagetype == 5 || $value->luggagetype == 6)){
                      $insurance_value = 4;
                   }else if($insurance && $value->luggagetype == 4){
                      $insurance_value = 8;
                   }else{
                      $insurance_value = 0;
                   }
                   if($insurance){
                     $total_insurance += $insurance_value;
                   }else{
                    $total_insurance = 0;
                   }

                   if($value->luggagetype && $value->weight_id != 0 && $airport_id){
                        $result = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price, wr.weight_range_price FROM tbl_luggage_offers lo LEFT JOIN tbl_weight_range wr ON lo.luggage_type = wr.fk_tbl_weight_range_id_luggage_type where lo.luggage_type =$value->luggagetype AND lo.airport =$airport_id AND wr.fk_tbl_weight_range_id_luggage_type = $value->luggagetype AND wr.id_weight_range =$value->weight_id ")->queryOne();

                        $weight_range_price = $weight_range_price + $result['weight_range_price'];
                     }else{
                        $result = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price FROM tbl_luggage_offers lo where lo.luggage_type =$value->luggagetype AND lo.airport =$airport_id")->queryOne();
                        $result['weight_range_price'] = 0;
                        $weight_range_price = 0;
                     }

                  if ($value->luggage_price_type == 'base' && $value->luggagetype) {
                      $base_price = '';
                      if($result['base_price'] && $result['offer_price']){
                          $base_price = $result['offer_price'];
                      }
                      if($result['base_price'] && !$result['offer_price']){
                          $base_price = $result['base_price'];
                      }
                      if(!$result){
                        $row = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price FROM tbl_luggage_offers lo where lo.luggage_type =$value->luggagetype AND lo.airport =$airport_id")->queryOne();
                        $base_price = $row['base_price'];
                      }


                      $total_price = $total_price + $base_price;
                      // $item_price[$k]['item_price'] = $base_price + $result['weight_range_price'];

                  }else if($value->luggage_price_type == 'subsequent' && $value->luggagetype){
                        $offer_price = \app\models\GroupOffers::find()->select(['subsequent_price'])->where(['group_id'=>$value->group_type])->andWhere(['airport' => $airport_id])->andWhere(['status' => 'enabled'])->one();

                        $subsequent_price = $offer_price->subsequent_price;
                        $subsequent = $subsequent + $subsequent_price;
                        // $item_price[$k]['item_price'] = $subsequent + $result['weight_range_price'];
                  }else{
                      $base_price = '';
                      //$item_price = '';
                  }
                  if($base_price){
                    $item_price[$k]['item_price'] = $base_price + $weight_range_price;
                  }
                  if(!$base_price && !$subsequent_price){
                    $item_price[$k]['item_price'] = 0;
                  }
                  if($subsequent_price){
                    $item_price[$k]['item_price'] = $subsequent_price + $weight_range_price;
                  }

                }
                $insurance_tax = $total_insurance + $total_insurance * 0.18;
                $added_bag_insurance = $insurance_tax - $insurance;

                $amount = $total_price + $subsequent + $weight_range_price;
                $amount_with_tax = $amount * (Yii::$app->params['gst_percent']/100);
                $added_amount = $amount + $amount_with_tax;

                $modified_amount = $amount - $existing_luggages;
                if ($modified_amount > 0 && $insurance) {
                    $old_insurance = $insurance/1.18;
                    $current_insurance = $total_insurance - $old_insurance;
                    $modified_amount_with_tax = $modified_amount + $current_insurance + $modified_amount * (Yii::$app->params['gst_percent']/100);
                } else {
                    $modified_amount_with_tax = $modified_amount;
                }
              }
              echo Json::encode(['modified_amount' => $modified_amount_with_tax, 'insurance' => $total_insurance, 'total_luggage_price' => $amount, 'item_price' => $item_price]);
            }

            if($luggage_details && $action == 'update-kiosk'){

              $airport_id = $_POST['airport_id'];
              $insurance = (isset($_POST['insurance'])) ? $_POST['insurance'] : '';

              $existing_luggages = $_POST['existing'];
              $existing_luggage_price = $existing_luggages + $existing_luggages * (Yii::$app->params['gst_percent']/100);

              $total_price = 0;
              $extra_price = 0;
              $weight_range_price = 0;

              $modified_amount = '';
              $total_luggage_price = '';
              $subsequent = 0;
              $total_insurance = 0;

              foreach ($luggage_details as $k => $update) {
                 $value = (object) $update;
                 $subsequent_price = '';
                 $offer_price = '';
                 if(($insurance) && ($value->luggagetype == 1 || $value->luggagetype == 2 || $value->luggagetype == 3 || $value->luggagetype == 5 || $value->luggagetype == 6)){
                    $insurance_value = 4;
                 }else if($insurance && $value->luggagetype == 4){
                    $insurance_value = 8;
                 }else{
                    $insurance_value = 0;
                 }

                 $total_insurance += $insurance_value;
                 if($value->luggagetype && $value->weight_id != 0 && $airport_id){
                    $result = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price, wr.weight_range_price FROM tbl_luggage_offers lo LEFT JOIN tbl_weight_range wr ON lo.luggage_type = wr.fk_tbl_weight_range_id_luggage_type where lo.luggage_type =$value->luggagetype AND lo.airport =$airport_id AND wr.fk_tbl_weight_range_id_luggage_type = $value->luggagetype AND wr.id_weight_range =$value->weight_id ")->queryOne();

                    $weight_range_price = $weight_range_price + $result['weight_range_price'];
                 }else{
                    $result = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price FROM tbl_luggage_offers lo where lo.luggage_type =$value->luggagetype AND lo.airport =$airport_id")->queryOne();
                    $result['weight_range_price'] = 0;
                    $weight_range_price = 0;
                 }

                if ($value->luggage_price_type == 'base' && $value->luggagetype) {
                    $base_price = '';
                    if($result['base_price'] && $result['offer_price']){
                        $base_price = $result['offer_price'];
                    }
                    if($result['base_price'] && !$result['offer_price']){
                        $base_price = $result['base_price'];
                    }
                    if(!$result){
                      $row = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price FROM tbl_luggage_offers lo where lo.luggage_type =$value->luggagetype AND lo.airport =$airport_id")->queryOne();
                      $base_price = $row['base_price'];
                    }
                    $total_price = $total_price + $base_price;
                }else if($value->luggage_price_type == 'subsequent' && $value->luggagetype){
                      $offer_price = \app\models\GroupOffers::find()->select(['subsequent_price'])->where(['group_id'=>$value->group_type])->andWhere(['airport' => $airport_id])->andWhere(['status' => 'enabled'])->one();

                      $subsequent_price = $offer_price->subsequent_price;
                      $subsequent = $subsequent + $subsequent_price;
                }else{
                    $base_price = '';
                }
                if($base_price){
                  $item_price[$k]['item_price'] = $base_price + $result['weight_range_price'];
                }
                if(!$base_price && !$subsequent_price){
                  $item_price[$k]['item_price'] = 0;
                }
                if($subsequent_price){
                  $item_price[$k]['item_price'] = $subsequent_price + $result['weight_range_price'];
                }

              }
              $insurance_tax = $total_insurance + $total_insurance * 0.18;
              $added_bag_insurance = $insurance_tax - $insurance;

              $amount = $total_price + $subsequent + $weight_range_price;
              $amount_with_tax = $amount * (Yii::$app->params['gst_percent']/100);
              $added_amount = $amount + $amount_with_tax;

              $modified_amount = $amount - $existing_luggages;
              if ($modified_amount < 0 && $insurance) {
                  $old_insurance = $insurance/1.18;
                  $current_insurance = $old_insurance - $total_insurance;
                  $modified_amount_with_tax = $modified_amount - $current_insurance  - $current_insurance * 0.18;
              } else if($modified_amount > 0){
                  $old_insurance = $insurance/1.18;
                  $current_insurance = $old_insurance - $total_insurance;
                  if($current_insurance < 0){
                    $modified_amount_with_tax = $modified_amount + $modified_amount * (Yii::$app->params['gst_percent']/100) - $current_insurance - $current_insurance * 0.18;
                  }else{
                    $modified_amount_with_tax = $modified_amount + $modified_amount * (Yii::$app->params['gst_percent']/100) + $current_insurance + $current_insurance * 0.18;
                  }
              }else {
                  $modified_amount_with_tax = $modified_amount;
              }
              echo Json::encode(['modified_amount' => $modified_amount_with_tax, 'total_luggage_price' => $amount, 'insurance' => $total_insurance, 'item_price' => $item_price ]);
            }
    }

    public function actionPriceDetailsGroupOffer(){
        $luggage_id = $_POST['luid'];
        $weight_id = $_POST['weightId'];
        $airport_id = $_POST['airport_id'];
        if(isset($_POST['extra_weight_id'])){
            $extra_weight_id = $_POST['extra_weight_id'];
        }
        $row = Yii::$app->db->createCommand("SELECT lo.base_price, lo.offer_price, wr.weight_range_price FROM tbl_luggage_offers lo LEFT JOIN tbl_weight_range wr ON lo.luggage_type = wr.fk_tbl_weight_range_id_luggage_type where lo.luggage_type =$luggage_id AND lo.airport =$airport_id AND wr.fk_tbl_weight_range_id_luggage_type = $luggage_id AND wr.id_weight_range =$weight_id ")->queryOne();

        $base_price = $row['base_price'];
        $offer_price = $row['offer_price'];

        if($luggage_id == 1 || $luggage_id == 2 || $luggage_id == 3 || $luggage_id == 6){
            $price = \app\models\GroupOffers::find()
                    ->select(['subsequent_price'])
                    ->where(['group_id'=>1])
                    ->andWhere(['airport' => $airport_id])
                    ->andWhere(['status' => 'enabled'])
                    ->one();
            if($price){
                $subsequent_price = $price->subsequent_price;
            }else if($offer_price ){
                $subsequent_price = $offer_price;
            }else if($base_price ){
                $subsequent_price = $base_price;
            }else{
                $subsequent_price = 0;
            }
        }
        if($luggage_id == 4){
            $price = \app\models\GroupOffers::find()
                    ->select(['subsequent_price'])
                    ->where(['group_id'=>2])
                    ->andWhere(['airport' => $airport_id])
                    ->andWhere(['status' => 'enabled'])
                    ->one();
            if($price){
                if($weight_id == 8 && $extra_weight_id){
                    $fragile_count = $extra_weight_id - 5;
                    $price_weight = 100 * $fragile_count;

                    $subsequent_price = $price->subsequent_price + $price_weight;
                }else{
                    $subsequent_price = $price->subsequent_price;
                }
            }else if($offer_price ){
                if($weight_id == 8 && $extra_weight_id){
                    $fragile_count = $extra_weight_id - 5;
                    $price_weight = 100 * $fragile_count;

                    $subsequent_price = $offer_price + $price_weight;
                }else{
                    $subsequent_price = $offer_price;
                }
            }else if($base_price ){
                if($weight_id == 8 && $extra_weight_id){
                    $fragile_count = $extra_weight_id - 5;
                    $price_weight = 100 * $fragile_count;

                    $subsequent_price = $base_price + $price_weight;
                }else{
                    $subsequent_price = $base_price;
                }
            }else{
                $subsequent_price = 0;
            }
        }
        if($luggage_id == 5){
            $price = \app\models\GroupOffers::find()
                    ->select(['subsequent_price'])
                    ->where(['group_id'=>3])
                    ->andWhere(['airport' => $airport_id])
                    ->andWhere(['status' => 'enabled'])
                    ->one();
            if($price){
                $subsequent_price = $price->subsequent_price;
            }else if($offer_price ){
                $subsequent_price = $offer_price;
            }else if($base_price ){
                $subsequent_price = $base_price;
            }else{
                $subsequent_price = 0;
            }
        }
        if($weight_id){
            $offset_price = \app\models\WeightRange::find()
                    ->select(['weight_range_price'])
                    ->where(['id_weight_range'=>$weight_id, 'fk_tbl_weight_range_id_luggage_type'=>$luggage_id])
                    ->one();
            if($offset_price){
                $weight_price = $offset_price->weight_range_price;
            }else{
                $weight_price = 0;
            }
        }else{
            $weight_price = '';
        }
        $total_price = $subsequent_price + $weight_price;
        echo $total_price;
    }



public function actionReCorporateOrder21212($id_order){
        $model['o'] = new Order();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $employee_model = new Employee();
        $order_promocode = OrderPromoCode::find()->where(['order_id'=>$id_order])->one();
        $order_details = Order::getorderdetails($id_order);
        $customer_details = Customer::find()->where(['id_customer'=>$order_details['order']['fk_tbl_order_id_customer']])->one();
        $order_details['order_items'] = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.excess_weight,oi.fk_tbl_order_items_id_weight_range,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$order_details['order']['id_order']."' AND deleted_status = 0 ")->queryAll();
        $order_undelivered = Order::getIsundelivered($id_order);
        if($customer_details){
            $customer_email = $customer_details->email;
        }else{
            $customer_email = $order_details['order']['customer_email'];
        }
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        //echo"<pre>";print_r($order_details);exit;
        if (!empty($_POST)) {
           // $this->CorporateReschduleMailSms(1404,1412);
            //echo"<pre>";print_r($_POST);exit;
            $insurance = 0;
            $_POST['Order']['totalPrice'] = ($_POST['Order']['totalPrice']) ? $_POST['Order']['totalPrice'] : 0;
            if($order_details['order_items']){
                foreach ($order_details['order_items'] as $key => $value) {
                    if($value['id_luggage_type'] == 1 || $value['id_luggage_type'] == 2 || $value['id_luggage_type'] == 3 || $value['id_luggage_type'] == 5 || $value['id_luggage_type'] == 6){
                        $insurance += 4;
                    }else{
                        $insurance += 8;
                    }
                }
            }
            if($_POST['Order']['hiddenServiceType'] == 1){
                $order_query = Order::findOne($id_order);
                $model = new Order( $order_query->getAttributes());
                $model->id_order = null;
                $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  //$_POST['Order']['order_date'];
                $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
                $model->insurance_price = 0;

                if($_POST['Order']['fk_tbl_order_id_slot'] == 4)
                {
                    $model->arrival_time = date('H:i:s', strtotime('03:00 PM'));
                    $model->meet_time_gate = date('H:i:s', strtotime('03:30 PM'));
                }else if($_POST['Order']['fk_tbl_order_id_slot'] == 5){
                    $model->arrival_time = date('H:i:s', strtotime('11:55 PM'));
                    $model->meet_time_gate = date('H:i:s', strtotime('12:25 AM'));
                }
                $model->dservice_type = $_POST['Order']['dservice_type'];
                $model->order_modified = 0;
                $model->payment_method = $_POST['payment_type'];
                // if($_POST['payment_type'] == 'cash'){
                //     $model->modified_amount = 0;
                // }
                $insurance_price = $insurance;
                $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                if($order_undelivered){
                    $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                }

                if(isset($_POST['Order']['insurance_price'])){
                    if($_POST['Order']['insurance_price'] == 1){
                        $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                        $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                        $totainsurance_price = $insurance + (.18 * $insurance) ;
                        $model->insurance_price = $totainsurance_price;
                    }else{
                       $model->insurance_price = $order_query['insurance_price'];
                    }
                }
                $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                //$model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                $model->amount_paid = $order_details['order']['amount_paid'];
                $model->reschedule_luggage = 1;
                $model->admin_edit_modified = 0;
                $model->related_order_id = $id_order;
                $model->no_of_units = $order_details['order']['luggage_count'];
                if($_POST['payment_type']=='COD'){
                    $model->amount_paid=0;
                }
                // if($_POST['payment_type'] == 'cash'){

                //     $order_payment = new OrderPaymentDetails();
                //     $order_payment->id_order = $order_query['id_order'];
                //     $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                //     $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                //     $order_payment->payment_status = 'Success';
                //     $order_payment->amount_paid = $_POST['old_price'];
                //     $order_payment->value_payment_mode = 'Reschdule Order Amount';
                //     $order_payment->date_created= date('Y-m-d H:i:s');
                //     $order_payment->date_modified= date('Y-m-d H:i:s');
                //     $order_payment->save(false);

                // }
                if($model->save(false)){

                    if($order_details['order']['modified_amount'] >0){
                           $total_price_colleted = $order_details['order']['amount_paid']+$_POST['old_price'];
                    }else if($order_details['order']['modified_amount'] < 0){
                        $total_price_colleted = $order_details['order']['amount_paid']-$_POST['old_price'];
                    }else{
                        $total_price_colleted = $order_details['order']['amount_paid'];
                    }

                            $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()
                            ->where(['orderId'=>$id_order])
                            ->one();
                        if($outstation_charge){
                            $OrderZoneDetails = new \app\api_v3\v3\models\OrderZoneDetails();
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $outstation_charge->outstationZoneId;
                            $OrderZoneDetails->cityZoneId = $outstation_charge->cityZoneId;
                            $OrderZoneDetails->stateId = $outstation_charge->stateId;
                            $OrderZoneDetails->extraKilometer = $outstation_charge->extraKilometer;
                            $OrderZoneDetails->taxAmount = $outstation_charge->taxAmount;
                            $OrderZoneDetails->outstationCharge = $outstation_charge->outstationCharge;
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                            $OrderZoneDetails->save(false);
                        }
                           


                    $model = Order::findOne($model->id_order);
                    $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                    //$model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                    //$model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
                    $model->service_type = ($order_query->service_type == 1) ? 2 : 1;
                    // if($_POST['payment_type'] == 'COD'){
                    //     $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 1) ? 2 : 3;
                    //     $model->order_status = ($order_query->service_type) ? 'Confirmed' : 'Open';
                    // }else{
                    //     $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 1) ? 3 : 2;
                    //     $model->order_status = ($order_query->service_type == 1) ? 'Open' : 'Confirmed';
                    // }
                    $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 2) ? 3 : 2;
                    $model->order_status = ($order_query->service_type == 2) ? 'Open' : 'Confirmed';
                    $model->signature1 = $model->signature1;
                    $model->express_extra_amount = $order_query->express_extra_amount;
                    $model->signature2 = $model->signature2;
                    $model->save(false);

                    $model1['order_details']=Order::getorderdetails($model->id_order);
                    if($_POST['payment_type'] == 'COD'){

                        /*for razor payment*/
                        $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $total_price_colleted, $model->id_order, $role_id);

                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                        $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                        User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                    }else{

//                        print_r('hello');exit;
                        /*for cash order confirmation*/
                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                        $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                        User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                        $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                        User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                    }
                    $order_query = Order::findOne($id_order);
                    $order_query->reschedule_luggage = 1;
                    $order_query->related_order_id = $model->id_order;
                    $order_query->fk_tbl_order_status_id_order_status = 20;
                    $order_query->order_status = 'Rescheduled';
                    $order_query->save(false);
 
                    Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                    Order::updatestatus($id_order,20,'Rescheduled');

                    /*order total table*/
                    $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                    /*order total*/

                    if($_POST['Order']['readdress'] == 0){
                        $orderSpotDetails = OrderSpotDetails::find()
                                            ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                            ->one();
                        /*$orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $orderSpotDetails->save(false);*/
                        $newspotDet = new OrderSpotDetails();
                        $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                        $newspotDet->person_name = $orderSpotDetails->person_name;
                        $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                        $newspotDet->area = $orderSpotDetails->area;
                        $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                        $newspotDet->building_number = $orderSpotDetails->building_number;
                        $newspotDet->landmark = $orderSpotDetails->landmark;
                        $newspotDet->pincode = $orderSpotDetails->pincode;
                        $newspotDet->business_name = $orderSpotDetails->business_name;
                        $newspotDet->mall_name = $orderSpotDetails->mall_name;
                        $newspotDet->store_name = $orderSpotDetails->store_name;
                        $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                        $newspotDet->other_comments = $orderSpotDetails->other_comments;
                        if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                        $newspotDet->save(false);

                    }elseif ($_POST['Order']['readdress'] == 1) {
                        // $orderSpotDetails = OrderSpotDetails::find()
                        //                     ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                        //                     ->one();
                        // $orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails = new OrderSpotDetails();
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                        $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                        $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                        $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                        $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                        $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                        $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                        $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                        $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                        $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                        $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                        $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                        $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                        if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                        }



                        if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                        {
                            $orderSpotDetails->assigned_person = 1;
                        }
                        $orderSpotDetails->save(false);
                    }
                    $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                    if(!empty($oItems)){
                        foreach ($oItems as $items) {
                            $order_items = new OrderItems();
                            $order_items->fk_tbl_order_items_id_order = $model->id_order;
                            $order_items->barcode = $items['barcode'];
                            $order_items->new_luggage = $items['new_luggage'];
                            $order_items->deleted_status = $items['deleted_status'];
                            $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                            $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                            $order_items->item_price = $items['item_price'];
                            $order_items->bag_weight = $items['bag_weight'];
                            $order_items->bag_type = $items['bag_type'];
                            $order_items->item_weight = $items['item_weight'];
                            $order_items->items_old_weight = $items['items_old_weight'];
                            $order_items->save(false);
                        }
                    }

                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        // $order_payment->payment_status = 'Not paid';
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        }
                        $order_payment->amount_paid = $total_price_colleted;
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

             // echo '<pre>';print_r($_POST);exit;

                        foreach ($orderOffer as $key => $offer) {
//                            print_r($offer['luggage_type']);exit;
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save(false);
                        }
                    }

                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_order;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save(false);
                        }
                    }

                    // $orderPromocode=OrderPromoCode::find()
                    //                             ->where(['order_id'=>$id_order])
                    //                             ->all();
                    // if(!empty($orderPromocode)){

                    //     foreach ($orderPromocode as $key => $promocode) {
                    //         $order_promocode=new OrderPromoCode();
                    //         $order_promocode->order_id=$model->id_order;
                    //         $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                    //         $order_promocode->promocode_text=$promocode['promocode_text'];
                    //         $order_promocode->promocode_value=$promocode['promocode_value'];
                    //         $order_promocode->promocode_type=$promocode['promocode_type'];
                    //         $order_promocode->save(false);
                    //     }
                    // }

                    /*$oItems->isNewRecord = true;
                    $oItems->id_order_item = null;
                    $oItems->fk_tbl_order_items_id_order = $model->id_order;*/
                    //if($oItems->save(false)){
                        $mallInvoice = MallInvoices::find()
                                       ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                       ->one() ;
                            if(!empty($mallInvoice)){
                                $mallInvoice->isNewRecord = true;
                                $mallInvoice->id_mall_invoices = null;
                                $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                $mallInvoice->save(false);
                        }
                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                   // }
                }



            }else if ($_POST['Order']['hiddenServiceType'] == 2) {
                if($_POST['Order']['rescheduleType'] == 0){
                    $order_query = Order::findOne($id_order);
                    //$model->isNewRecord = true;
                    $model = new Order(
                                    $order_query->getAttributes() // get all attributes and copy them to the new instance
                                );
                    $model->id_order = null;
                    //$departureDetails = explode(' ', $_POST['Order']['departure_date']);
                    $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] : null;
                    $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) : null;
                    $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] : null;
                    $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) : null;
                    $model->flight_number = $_POST['Order']['flight_number'];
                    $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
                    //$model->luggage_price = $order_query->luggage_price;

                    // $insurance_price = 4 * $order_details['order']['luggage_count'];
                    // $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                    // if($order_undelivered){
                    //     $model->insurance_price = $model->insurance_price + $model->insurance_price;
                    // }
                    $insurance_price = $insurance;
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }

                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $model->amount_paid  = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }

                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price)+ ($model->insurance_price);
                    $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->reschedule_luggage = 1;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->fk_tbl_order_id_slot = 6;
                    $model->service_type = 2;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    $model->order_modified = 0;
                    $model->payment_method = $_POST['payment_type'];
                    // if($_POST['payment_type'] == 'cash'){
                    //     $model->modified_amount = 0;
                    // }
                    if($_POST['payment_type']=='COD'){
                        $model->amount_paid=0;
                    }
                    // if($_POST['payment_type'] == 'cash'){

                    //     $order_payment = new OrderPaymentDetails();
                    //     $order_payment->id_order = $order_query['id_order'];
                    //     $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                    //     $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                    //     $order_payment->payment_status = 'Success';
                    //     $order_payment->amount_paid = $_POST['old_price'];
                    //     $order_payment->value_payment_mode = 'Reschdule Order Amount';
                    //     $order_payment->date_created= date('Y-m-d H:i:s');
                    //     $order_payment->date_modified= date('Y-m-d H:i:s');
                    //     $order_payment->save(false);

                    // }
                    if($model->save(false)){
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                        $model->fk_tbl_order_status_id_order_status = 2;
                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->dservice_type = $_POST['Order']['dservice_type'];
                        $model->order_status = 'Confirmed';
                        $model->save(false);

                        $model1['order_details']=Order::getorderdetails($model->id_order);

                        if($_POST['payment_type'] == 'COD'){
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }
                        if(!empty($_FILES['Order']['name']['ticket'])){
                            $up = $employee_model->actionFileupload('ticket',$model->id_order);
                        }

                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);

                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/

                        //Records Insert into OrderSpotDetails;
                        $orderSpotDetails = OrderSpotDetails::find()
                                            ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                            ->one();
                        $orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;



                        if($_POST['Order']['readdress1'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress1'] == 1) {
                            /*$orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;*/
                            $orderSpotDetails = new OrderSpotDetails();
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;
                            //$orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;

                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                            }

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }

                            $orderSpotDetails->save(false);
                        }


                        if($orderSpotDetails->save(false)){
                            // Records Insert into OrderItems;
                            $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                            if(!empty($oItems)){
                                foreach ($oItems as $items) {
                                    $order_items = new OrderItems();
                                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                    $order_items->barcode = $items['barcode'];
                                    $order_items->new_luggage = $items['new_luggage'];
                                    $order_items->deleted_status = $items['deleted_status'];
                                    $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                    $order_items->item_price = $items['item_price'];
                                 $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                                $order_items->item_weight = $items['item_weight'];
                                $order_items->items_old_weight = $items['items_old_weight'];
                            $order_items->save(false);
                        }
                    }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save(false);
                        }
                    }
                    // if($_POST['payment_type'] == 'cash'){

                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        }
                        $order_payment->amount_paid = $total_price_colleted;
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                    // }
                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_order;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save(false);
                        }
                    }

                    // $orderPromocode=OrderPromoCode::find()
                    //                             ->where(['order_id'=>$id_order])
                    //                             ->all();
                    // if(!empty($orderPromocode)){

                    //     foreach ($orderPromocode as $key => $promocode) {
                    //         $order_promocode=new OrderPromoCode();
                    //         $order_promocode->order_id=$model->id_order;
                    //         $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                    //         $order_promocode->promocode_text=$promocode['promocode_text'];
                    //         $order_promocode->promocode_value=$promocode['promocode_value'];
                    //         $order_promocode->promocode_type=$promocode['promocode_type'];
                    //         $order_promocode->save(false);
                    //     }
                    // }
                            //if($oItems->save()){
                                $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                            //}
                        }
                    }
                }elseif ($_POST['Order']['rescheduleType'] == 1) {
                     $order_query = Order::findOne($id_order);
                    //$model->isNewRecord = true;
                    $model = new Order(
                                    $order_query->getAttributes() // get all attributes and copy them to the new instance
                                );
                    $model->id_order = null;
                    $model->reschedule_luggage = 1;
                    //$model->luggage_price = $order_query->luggage_price;
                    $model->departure_date = null;
                    $model->departure_time = null;
                    $model->arrival_date = null;
                    $model->arrival_time = null;
                    $model->flight_number = null;
                    $model->meet_time_gate = null;
                    // $insurance_price = 4 * $order_details['order']['luggage_count'];
                    // $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                    // if($order_undelivered){
                    //     $model->insurance_price = $model->insurance_price + $model->insurance_price;
                    // }
                    $insurance_price = $insurance;
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }

                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }
                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->service_type = 2 ;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->fk_tbl_order_id_slot = 4;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    $model->order_modified = 0;
                   $model->payment_method = $_POST['payment_type'];
                   // if($_POST['payment_type'] == 'cash'){
                   //      $model->modified_amount = 0;
                   //  }
                    if($_POST['payment_type']=='COD'){
                        $model->amount_paid=0;
                    }
                    // if($_POST['payment_type'] == 'cash'){

                    //     $order_payment = new OrderPaymentDetails();
                    //     $order_payment->id_order = $order_query['id_order'];
                    //     $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                    //     $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                    //     $order_payment->payment_status = 'Success';
                    //     $order_payment->amount_paid = $_POST['old_price'];
                    //     $order_payment->value_payment_mode = 'Reschdule Order Amount';
                    //     $order_payment->date_created= date('Y-m-d H:i:s');
                    //     $order_payment->date_modified= date('Y-m-d H:i:s');
                    //     $order_payment->save(false);

                    // }
                    if($model->save(false)){
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;

                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->fk_tbl_order_status_id_order_status =  2;
                        $model->order_status = 'Confirmed';
                        $model->dservice_type = $_POST['Order']['dservice_type'];
                        $model->save(false);
/*
                        if($_POST['payment_type'] == 'COD'){
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);
                        }*/

                        /*new email Integration*/
                        $model1['order_details']=Order::getorderdetails($model->id_order);
                        if($_POST['payment_type'] == 'COD'){
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }

                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);

                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/


                        //Records Insert into OrderSpotDetails;
                        if($_POST['Order']['readdress1'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress1'] == 1) {
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            //$orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                            if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                            }

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }
                        }

                        if($orderSpotDetails->save(false)){
                            if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                            }
                            // Records Insert into OrderItems;
                            $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                            if(!empty($oItems)){
                                foreach ($oItems as $items) {
                                    $order_items = new OrderItems();
                                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                    $order_items->barcode = $items['barcode'];
                                    $order_items->new_luggage = $items['new_luggage'];
                                    $order_items->deleted_status = $items['deleted_status'];
                                    $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                    $order_items->item_price = $items['item_price'];
                                    $order_items->bag_weight = $items['bag_weight'];
                                    $order_items->bag_type = $items['bag_type'];
                                    $order_items->item_weight = $items['item_weight'];
                                    $order_items->items_old_weight = $items['items_old_weight'];
                            $order_items->save(false);
                        }
                    }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save(false);
                        }
                    }

                    // if($_POST['payment_type'] == 'cash'){

                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;

                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        }
                        $order_payment->amount_paid = $total_price_colleted;
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                    // }
                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_order;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save(false);
                        }
                    }

                    // $orderPromocode=OrderPromoCode::find()
                    //                             ->where(['order_id'=>$id_order])
                    //                             ->all();
                    // if(!empty($orderPromocode)){

                    //     foreach ($orderPromocode as $key => $promocode) {
                    //         $order_promocode=new OrderPromoCode();
                    //         $order_promocode->order_id=$model->id_order;
                    //         $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                    //         $order_promocode->promocode_text=$promocode['promocode_text'];
                    //         $order_promocode->promocode_value=$promocode['promocode_value'];
                    //         $order_promocode->promocode_type=$promocode['promocode_type'];
                    //         $order_promocode->save(false);
                    //     }
                    // }

                            //if($oItems->save()){
                                 if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                                }else{
                                    $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                    if(!empty($mallInvoice)){
                                        $mallInvoice->isNewRecord = true;
                                        $mallInvoice->id_mall_invoices = null;
                                        $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                        $mallInvoice->save(false);
                                    }
                                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                        return $this->redirect(['order/index']);
                               // }
                            }
                        }
                    }
                }

            }

        }else{
            $luggage_price = LuggageType::find()->where(['corporate_id'=>$order_details['order']['corporate_id']])->one();
            $order_details['order']['no_of_units'] = $order_details['order']['luggage_count'];
            $order_details['order']['totalPrice'] = $order_details['order']['luggage_count'] * $luggage_price['base_price'];
            //print_r($luggage_price);exit;
            return $this->render('reschedule_corporate_update', [
                'order_details'=>$order_details,'model'=>$model, 'order_promocode' => $order_promocode]);
        }
    }

    public function actionReCorporateOrder($id_order){
        $model['o'] = new Order();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $employee_model = new Employee();
        $order_promocode = OrderPromoCode::find()->where(['order_id'=>$id_order])->one();
        $order_details = Order::getorderdetails($id_order);
        $customer_details = Customer::find()->where(['id_customer'=>$order_details['order']['fk_tbl_order_id_customer']])->one();
        $order_details['order_items'] = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.excess_weight,oi.fk_tbl_order_items_id_weight_range,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$order_details['order']['id_order']."' AND deleted_status = 0 ")->queryAll();
        $order_undelivered = Order::getIsundelivered($id_order);
        if($customer_details){
            $customer_email = $customer_details->email;
        }else{
            $customer_email = $order_details['order']['customer_email'];
        }
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        //echo"<pre>";print_r($order_details);exit;
        if (!empty($_POST)) { 
            $total_price_colleted = $_POST['old_price']+$_POST['originalamount']+$_POST['refundamount']; 
           // $this->CorporateReschduleMailSms(1404,1412);
        //echo"<pre>";print_r($_POST['Order']['insurance_price']);exit;
        //$_POST['Order']['totalPrice'] = $_POST['old_price']; 

            $insurance = 0;
            $_POST['Order']['totalPrice'] = ($_POST['Order']['totalPrice']) ? $_POST['Order']['totalPrice'] : 0;
            //echo"<pre>";print_r($_POST['Order']['totalPrice']);exit;
            if($order_details['order_items']){
                foreach ($order_details['order_items'] as $key => $value) {
                    if($value['id_luggage_type'] == 1 || $value['id_luggage_type'] == 2 || $value['id_luggage_type'] == 3 || $value['id_luggage_type'] == 5 || $value['id_luggage_type'] == 6){
                        $insurance += 4;
                    }else{
                        $insurance += 8;
                    }
                }
            }
            if($_POST['Order']['hiddenServiceType'] == 1){
                $order_query = Order::findOne($id_order);
                $model = new Order( $order_query->getAttributes());
                $model->id_order = null;
                $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  //$_POST['Order']['order_date'];amount_paid
                $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
                $model->insurance_price = 0;

                if($_POST['Order']['fk_tbl_order_id_slot'] == 4)
                {
                    $model->arrival_time = date('H:i:s', strtotime('03:00 PM'));
                    $model->meet_time_gate = date('H:i:s', strtotime('03:30 PM'));
                }else if($_POST['Order']['fk_tbl_order_id_slot'] == 5){
                    $model->arrival_time = date('H:i:s', strtotime('11:55 PM'));
                    $model->meet_time_gate = date('H:i:s', strtotime('12:25 AM'));
                }
                $model->dservice_type = $_POST['Order']['dservice_type'];
                $model->order_modified = 0;
                $model->modified_amount = null;
                $model->payment_method = $_POST['payment_type'];
                // if($_POST['payment_type'] == 'cash'){
                //     $model->modified_amount = 0;
                // }amount_paid
                $insurance_price = $insurance;
                $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                if($order_undelivered){
                    $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                }

                if(isset($_POST['Order']['insurance_price'])){
                    if($_POST['Order']['insurance_price'] == 1){
                        $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                        $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                        $totainsurance_price = $insurance + (.18 * $insurance) ;
                        $model->insurance_price = $totainsurance_price;
                    }else{
                       $model->insurance_price = $order_query['insurance_price'];
                    }
                }
                $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
               // echo"<pre>";print_r($_POST['Order']['insurance_price']);exit;
                //$model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                $model->amount_paid = $order_details['order']['amount_paid'];
                $model->reschedule_luggage = 1;
                $model->admin_edit_modified = 0;
                $model->related_order_id = $id_order;
                $model->no_of_units = $order_details['order']['luggage_count'];
                if($_POST['payment_type']=='COD'){
                    $model->amount_paid=0;
                }
                // if($_POST['payment_type'] == 'cash'){

                //     $order_payment = new OrderPaymentDetails();
                //     $order_payment->id_order = $order_query['id_order'];
                //     $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                //     $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                //     $order_payment->payment_status = 'Success';
                //     $order_payment->amount_paid = $_POST['old_price'];
                //     $order_payment->value_payment_mode = 'Reschdule Order Amount';
                //     $order_payment->date_created= date('Y-m-d H:i:s');
                //     $order_payment->date_modified= date('Y-m-d H:i:s');
                //     $order_payment->save(false);

                // }
                if($model->save(false)){
                    $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                     //print_r($oItems);exit;
                    if($oItems){
                        foreach ($oItems as $items) { 
                            $order_items = new OrderItems();
                            $order_items->fk_tbl_order_items_id_order = $model->id_order;
                            $order_items->barcode = $items['barcode'];
                            $order_items->new_luggage = $items['new_luggage'];
                            $order_items->deleted_status = $items['deleted_status'];
                            $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                            $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                            $order_items->item_price = $items['item_price'];
                            $order_items->bag_weight = $items['bag_weight'];
                            $order_items->bag_type = $items['bag_type'];
                            $order_items->item_weight = $items['item_weight'];
                            $order_items->items_old_weight = $items['items_old_weight'];
                            $order_items->save(false);
                        }
                    }

                    $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        // $order_payment->payment_status = 'Not paid';
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        } 
                        $order_payment->amount_paid = $total_price_colleted; 
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

             // echo '<pre>';print_r($_POST);exit;

                        foreach ($orderOffer as $key => $offer) {
                        //    print_r($offer['luggage_type']);exit;
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save(false);
                        }
                    }

                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_order;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save(false);
                        }
                    }

                    
                           
                    

                            $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()
                            ->where(['orderId'=>$id_order])
                            ->one();
                        if($outstation_charge){
                            $OrderZoneDetails = new \app\api_v3\v3\models\OrderZoneDetails();
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $outstation_charge->outstationZoneId;
                            $OrderZoneDetails->cityZoneId = $outstation_charge->cityZoneId;
                            $OrderZoneDetails->stateId = $outstation_charge->stateId;
                            $OrderZoneDetails->extraKilometer = $outstation_charge->extraKilometer;
                            $OrderZoneDetails->taxAmount = $outstation_charge->taxAmount;
                            $OrderZoneDetails->outstationCharge = $outstation_charge->outstationCharge;
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                            $OrderZoneDetails->save(false);
                        }
                            


                    $model = Order::findOne($model->id_order);
                    $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                    //$model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                    //$model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
                    $model->service_type = ($order_query->service_type == 1) ? 2 : 1;
                    // if($_POST['payment_type'] == 'COD'){
                    //     $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 1) ? 2 : 3;
                    //     $model->order_status = ($order_query->service_type) ? 'Confirmed' : 'Open';
                    // }else{
                    //     $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 1) ? 3 : 2;
                    //     $model->order_status = ($order_query->service_type == 1) ? 'Open' : 'Confirmed';
                    // }
                    $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 2) ? 3 : 2;
                    $model->order_status = ($order_query->service_type == 2) ? 'Open' : 'Confirmed';
                    $model->signature1 = $model->signature1;
                    $model->express_extra_amount = $order_query->express_extra_amount;
                    $model->signature2 = $model->signature2;
                    $model->save(false);

                    $model1['order_details']=Order::getorderdetails($model->id_order);
                    if($_POST['payment_type'] == 'COD'){

                        /*for razor payment*/
                        $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $total_price_colleted, $model->id_order, $role_id);

                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                        $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                        User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                    }else{

                    //    print_r('hello');exit;
                        /*for cash order confirmation*/
                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                        $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                        User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                        $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                        User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                    }
                    $order_query = Order::findOne($id_order);
                    $order_query->reschedule_luggage = 1;
                    $order_query->related_order_id = $model->id_order;
                    $order_query->fk_tbl_order_status_id_order_status = 20;
                    $order_query->order_status = 'Rescheduled';
                    $order_query->save(false);
 
                    Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                    Order::updatestatus($id_order,20,'Rescheduled');

                    /*order total table*/
                    $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                    /*order total*/
                    if($order_query->corporate_type == 1){
                        Yii::$app->queue->push(new DelhiAirport([
                           'order_id' => $model->id_order,
                           'order_status' => 'confirmed'
                       ]));
                    }
                    if($_POST['Order']['readdress'] == 0){
                        $orderSpotDetails = OrderSpotDetails::find()
                                            ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                            ->one();
                        /*$orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $orderSpotDetails->save(false);*/
                        $newspotDet = new OrderSpotDetails();
                        $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                        $newspotDet->person_name = $orderSpotDetails->person_name;
                        $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                        $newspotDet->area = $orderSpotDetails->area;
                        $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                        $newspotDet->building_number = $orderSpotDetails->building_number;
                        $newspotDet->landmark = $orderSpotDetails->landmark;
                        $newspotDet->pincode = $orderSpotDetails->pincode;
                        $newspotDet->business_name = $orderSpotDetails->business_name;
                        $newspotDet->mall_name = $orderSpotDetails->mall_name;
                        $newspotDet->store_name = $orderSpotDetails->store_name;
                        $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                        $newspotDet->other_comments = $orderSpotDetails->other_comments;
                        if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                        $newspotDet->save(false);

                    }elseif ($_POST['Order']['readdress'] == 1) {
                        // $orderSpotDetails = OrderSpotDetails::find()
                        //                     ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                        //                     ->one();
                        // $orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails = new OrderSpotDetails();
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                        $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                        $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                        $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                        $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                        $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                        $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                        $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                        $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                        $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                        $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                        $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                        $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                        if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                        }

 

                        if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                        {
                            $orderSpotDetails->assigned_person = 1;
                        }
                        $orderSpotDetails->save(false);
                    }


                        
                    // $orderPromocode=OrderPromoCode::find()
                    //                             ->where(['order_id'=>$id_order])
                    //                             ->all();
                    // if(!empty($orderPromocode)){

                    //     foreach ($orderPromocode as $key => $promocode) {
                    //         $order_promocode=new OrderPromoCode();
                    //         $order_promocode->order_id=$model->id_order;
                    //         $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                    //         $order_promocode->promocode_text=$promocode['promocode_text'];
                    //         $order_promocode->promocode_value=$promocode['promocode_value'];
                    //         $order_promocode->promocode_type=$promocode['promocode_type'];
                    //         $order_promocode->save(false);
                    //     }
                    // }

                    /*$oItems->isNewRecord = true;
                    $oItems->id_order_item = null;
                    $oItems->fk_tbl_order_items_id_order = $model->id_order;*/
                    //if($oItems->save(false)){
                        $mallInvoice = MallInvoices::find()
                                       ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                       ->one() ;
                            if(!empty($mallInvoice)){
                                $mallInvoice->isNewRecord = true;
                                $mallInvoice->id_mall_invoices = null;
                                $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                $mallInvoice->save(false);
                        }
                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                   // }
                }



            }else if ($_POST['Order']['hiddenServiceType'] == 2) {
                if($_POST['Order']['rescheduleType'] == 0){
                    $order_query = Order::findOne($id_order);
                    //$model->isNewRecord = true;
                    $model = new Order(
                                    $order_query->getAttributes() // get all attributes and copy them to the new instance
                                );
                    $model->id_order = null;
                    //$departureDetails = explode(' ', $_POST['Order']['departure_date']);
                    $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] : null;
                    $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) : null;
                    $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] : null;
                    $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) : null;
                    $model->flight_number = $_POST['Order']['flight_number'];
                    $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
                    //$model->luggage_price = $order_query->luggage_price;

                    // $insurance_price = 4 * $order_details['order']['luggage_count'];
                    // $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                    // if($order_undelivered){
                    //     $model->insurance_price = $model->insurance_price + $model->insurance_price;
                    // }
                    $insurance_price = $insurance;
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }

                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $model->amount_paid  = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    } 

                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price)+ ($model->insurance_price);
                    $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->reschedule_luggage = 1;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->fk_tbl_order_id_slot = 6;
                    $model->service_type = 2;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    $model->order_modified = 0;
                     $model->modified_amount = null;
                    $model->payment_method = $_POST['payment_type'];
                    // if($_POST['payment_type'] == 'cash'){
                    //     $model->modified_amount = 0;
                    // }
                    if($_POST['payment_type']=='COD'){
                        $model->amount_paid=0;
                    }
                    // if($_POST['payment_type'] == 'cash'){

                    //     $order_payment = new OrderPaymentDetails();
                    //     $order_payment->id_order = $order_query['id_order'];
                    //     $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                    //     $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                    //     $order_payment->payment_status = 'Success';
                    //     $order_payment->amount_paid = $_POST['old_price'];
                    //     $order_payment->value_payment_mode = 'Reschdule Order Amount';
                    //     $order_payment->date_created= date('Y-m-d H:i:s');
                    //     $order_payment->date_modified= date('Y-m-d H:i:s');
                    //     $order_payment->save(false);

                    // }
                    if($model->save(false)){
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                        $model->fk_tbl_order_status_id_order_status = 2;
                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->dservice_type = $_POST['Order']['dservice_type'];
                        $model->order_status = 'Confirmed';
                        $model->save(false);

                        $model1['order_details']=Order::getorderdetails($model->id_order);

                        if($_POST['payment_type'] == 'COD'){
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }
                        if(!empty($_FILES['Order']['name']['ticket'])){
                            $up = $employee_model->actionFileupload('ticket',$model->id_order);
                        }

                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);

                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/
                        if($order_query->corporate_type == 1){
                            Yii::$app->queue->push(new DelhiAirport([
                               'order_id' => $model->id_order,
                               'order_status' => 'confirmed'
                           ]));
                        }
                        //Records Insert into OrderSpotDetails;
                        $orderSpotDetails = OrderSpotDetails::find()
                                            ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                            ->one();
                        $orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;



                        if($_POST['Order']['readdress1'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress1'] == 1) {
                            /*$orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;*/
                            $orderSpotDetails = new OrderSpotDetails();
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;
                            //$orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;

                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                            }

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }

                            $orderSpotDetails->save(false);
                        }


                        if($orderSpotDetails->save(false)){
                            // Records Insert into OrderItems;
                            $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                            if($oItems){
                                foreach ($oItems as $items) {
                                    $order_items = new OrderItems();
                                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                    $order_items->barcode = $items['barcode'];
                                    $order_items->new_luggage = $items['new_luggage'];
                                    $order_items->deleted_status = $items['deleted_status'];
                                    $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                    $order_items->item_price = $items['item_price'];
                                    $order_items->bag_weight = $items['bag_weight'];
                                    $order_items->bag_type = $items['bag_type'];
                                    $order_items->item_weight = $items['item_weight'];
                                    $order_items->items_old_weight = $items['items_old_weight'];
                                    $order_items->save(false);
                                }
                            }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save(false);
                        }
                    }
                    // if($_POST['payment_type'] == 'cash'){

                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        }
                        $order_payment->amount_paid = $total_price_colleted;
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                    // }
                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_order;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save(false);
                        }
                    }

                    // $orderPromocode=OrderPromoCode::find()
                    //                             ->where(['order_id'=>$id_order])
                    //                             ->all();
                    // if(!empty($orderPromocode)){

                    //     foreach ($orderPromocode as $key => $promocode) {
                    //         $order_promocode=new OrderPromoCode();
                    //         $order_promocode->order_id=$model->id_order;
                    //         $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                    //         $order_promocode->promocode_text=$promocode['promocode_text'];
                    //         $order_promocode->promocode_value=$promocode['promocode_value'];
                    //         $order_promocode->promocode_type=$promocode['promocode_type'];
                    //         $order_promocode->save(false);
                    //     }
                    // }
                            //if($oItems->save()){
                                $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                            //}
                        }
                    }
                }elseif ($_POST['Order']['rescheduleType'] == 1) {
                     $order_query = Order::findOne($id_order);
                    //$model->isNewRecord = true;
                    $model = new Order(
                                    $order_query->getAttributes() // get all attributes and copy them to the new instance
                                );
                    $model->id_order = null;
                    $model->reschedule_luggage = 1;
                    //$model->luggage_price = $order_query->luggage_price;
                    $model->departure_date = null;
                    $model->departure_time = null;
                    $model->arrival_date = null;
                    $model->arrival_time = null;
                    $model->flight_number = null;
                    $model->meet_time_gate = null;
                    // $insurance_price = 4 * $order_details['order']['luggage_count'];
                    // $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                    // if($order_undelivered){
                    //     $model->insurance_price = $model->insurance_price + $model->insurance_price;
                    // }
                    $insurance_price = $insurance;
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }

                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }
                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->service_type = 2 ;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->fk_tbl_order_id_slot = 4;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    $model->order_modified = 0;
                 $model->modified_amount = null;
                   $model->payment_method = $_POST['payment_type'];
                   // if($_POST['payment_type'] == 'cash'){
                   //      $model->modified_amount = 0;
                   //  }
                    if($_POST['payment_type']=='COD'){
                        $model->amount_paid=0;
                    }
                    // if($_POST['payment_type'] == 'cash'){

                    //     $order_payment = new OrderPaymentDetails();
                    //     $order_payment->id_order = $order_query['id_order'];
                    //     $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                    //     $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                    //     $order_payment->payment_status = 'Success';
                    //     $order_payment->amount_paid = $_POST['old_price'];
                    //     $order_payment->value_payment_mode = 'Reschdule Order Amount';
                    //     $order_payment->date_created= date('Y-m-d H:i:s');
                    //     $order_payment->date_modified= date('Y-m-d H:i:s');
                    //     $order_payment->save(false);

                    // }
                    if($model->save(false)){
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;

                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->fk_tbl_order_status_id_order_status =  2;
                        $model->order_status = 'Confirmed';
                        $model->dservice_type = $_POST['Order']['dservice_type'];
                        $model->save(false);
/*
                        if($_POST['payment_type'] == 'COD'){
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);
                        }*/

                        /*new email Integration*/
                        $model1['order_details']=Order::getorderdetails($model->id_order);
                        if($_POST['payment_type'] == 'COD'){
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }

                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);

                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/
                        if($order_query->corporate_type == 1){
                            Yii::$app->queue->push(new DelhiAirport([
                               'order_id' => $model->id_order,
                               'order_status' => 'confirmed'
                           ]));
                        }
                        //Records Insert into OrderSpotDetails;
                        if($_POST['Order']['readdress1'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress1'] == 1) {
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            //$orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                            if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                            }

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }
                        }

                        if($orderSpotDetails->save(false)){
                            if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                            }
                            // Records Insert into OrderItems;
                            $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                            if($oItems){
                                foreach ($oItems as $items) {
                                    $order_items = new OrderItems();
                                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                    $order_items->barcode = $items['barcode'];
                                    $order_items->new_luggage = $items['new_luggage'];
                                    $order_items->deleted_status = $items['deleted_status'];
                                    $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                    $order_items->item_price = $items['item_price'];
                                    $order_items->bag_weight = $items['bag_weight'];
                                    $order_items->bag_type = $items['bag_type'];
                                    $order_items->item_weight = $items['item_weight'];
                                    $order_items->items_old_weight = $items['items_old_weight'];
                                    $order_items->save(false);
                                }
                            }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save(false);
                        }
                    }

                    // if($_POST['payment_type'] == 'cash'){

                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;

                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        }
                        $order_payment->amount_paid = $total_price_colleted;
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                    // }
                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_order;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save(false);
                        }
                    }

                    // $orderPromocode=OrderPromoCode::find()
                    //                             ->where(['order_id'=>$id_order])
                    //                             ->all();
                    // if(!empty($orderPromocode)){

                    //     foreach ($orderPromocode as $key => $promocode) {
                    //         $order_promocode=new OrderPromoCode();
                    //         $order_promocode->order_id=$model->id_order;
                    //         $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                    //         $order_promocode->promocode_text=$promocode['promocode_text'];
                    //         $order_promocode->promocode_value=$promocode['promocode_value'];
                    //         $order_promocode->promocode_type=$promocode['promocode_type'];
                    //         $order_promocode->save(false);
                    //     }
                    // }

                            //if($oItems->save()){
                                 if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                                }else{
                                    $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                    if(!empty($mallInvoice)){
                                        $mallInvoice->isNewRecord = true;
                                        $mallInvoice->id_mall_invoices = null;
                                        $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                        $mallInvoice->save(false);
                                    }
                                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                        return $this->redirect(['order/index']);
                               // }
                            }
                        }
                    }
                }

            }

        }else{
            $luggage_price = LuggageType::find()->where(['corporate_id'=>$order_details['order']['corporate_id']])->one();
            $order_details['order']['no_of_units'] = $order_details['order']['luggage_count'];
            $order_details['order']['totalPrice'] = $order_details['order']['luggage_count'] * $luggage_price['base_price'];
            //print_r($luggage_price);exit;
            return $this->render('reschedule_corporate_update', [
                'order_details'=>$order_details,'model'=>$model, 'order_promocode' => $order_promocode]);
        }
    } 


    public function actionReCorporateOrderRevised($id_order){
        $model['o'] = new Order();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $employee_model = new Employee();
        $order_promocode = OrderPromoCode::find()->where(['order_id'=>$id_order])->one();
        $order_details = Order::getorderdetails($id_order);

        $order_query = Order::findOne($id_order);
        if($order_query['order_transfer']==1){
            $customer_details = Customer::find()->where(['id_customer'=>$order_details['order']['fk_tbl_order_id_customer']])->one();
        $order_details['order_items'] = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.excess_weight,oi.fk_tbl_order_items_id_weight_range,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$order_details['order']['id_order']."' AND deleted_status = 0 ")->queryAll();

        $order_details['meta_data']=OrderMetaDetails::find()
                                                ->where(['orderId'=>$id_order])
                                                ->asArray()->one();
        }else{
            $customer_details = Customer::find()->where(['id_customer'=>$order_details['order']['fk_tbl_order_id_customer']])->one();
        $order_details['order_items'] = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.excess_weight,oi.fk_tbl_order_items_id_weight_range,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$order_details['order']['id_order']."' AND deleted_status = 0 ")->queryAll();
        }

        
        $order_undelivered = Order::getIsundelivered($id_order);
        if($customer_details){
            $customer_email = $customer_details->email;
        }else{  
            $customer_email = $order_details['order']['customer_email'];
        }
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        if(!empty($_POST)){ 
             //echo"<pr>";print_r($order_details['corporate_details']['corporate_type']);exit; 
            // TOTAL AMOUNT COLLECTED FROM THE CUSTOMER
            $total_price_colleted = $_POST['total_amount'];  
            // TOTAL AMOUNT COLLECTED FROM THE CUSTOMER
            //$total_price_colleted = $_POST['old_price']+$_POST['originalamount']+$_POST['refundamount'];  
            // INSURANCE CALCULATION FUNCTION
            $insurance = Order::getInsuranceAmount($order_details['order_items']); 
            // ORDER TOTAL PRICE
            
            $_POST['Order']['totalPrice'] = ($_POST['Order']['totalPrice']) ? $_POST['Order']['totalPrice'] : 0;
            //print_r($order_details['corporate_details']['corporate_type']);exit;
            // RESCHEDULE CODE FOR GENERAL ORDER AND MHL ORDER STARTS HERE (1=>GENERAL,2=>MHL)
             
            
            if(in_array($order_details['corporate_details']['corporate_type'], [1,2])){
                if($_POST['Order']['hiddenServiceType']==1){ // TO AIRPORT SCENARIO
                    $order_query = Order::findOne($id_order);
                    $model = new Order( $order_query->getAttributes());
                    $model->id_order = null;
                    $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  
                    $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
                    $model->insurance_price = 0;

                    if($_POST['Order']['fk_tbl_order_id_slot'] == 4)
                    {
                        $model->arrival_time = date('H:i:s', strtotime('03:00 PM'));
                        $model->meet_time_gate = date('H:i:s', strtotime('03:30 PM'));
                    }else if($_POST['Order']['fk_tbl_order_id_slot'] == 5){
                        $model->arrival_time = date('H:i:s', strtotime('11:55 PM'));
                        $model->meet_time_gate = date('H:i:s', strtotime('12:25 AM'));
                    }

                   // print_r($order_query['order_transfer']);exit;
                    $model->dservice_type = $_POST['Order']['dservice_type'];
                    $model->order_modified = 0;
                    $model->modified_amount = null;
                    $model->payment_method = $_POST['payment_type'];
                    $insurance_price = $insurance; 
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }
                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                            if($order_query['order_transfer']==1){ 
                                $total_price_colleted +=  $totainsurance_price;
                            }
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }

                    //echo"<pr>";print_r($total_price_colleted);exit;  
                    //print_r($model->insurance_price);exit;
                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    if($order_query['order_transfer']==1){ 
                        $model->amount_paid = $order_details['order']['amount_paid'];
                    } 

                    
                    $model->reschedule_luggage = 1;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    if($_POST['payment_type']=='COD'){
                        if($order_query['order_transfer']==1){ 
                        $model->amount_paid = $total_price_colleted;
                    } else{
                        $model->amount_paid=0;
                    }

                        
                    }
                    if($model->save(false)){
                        $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                        //print_r($oItems);exit;
                        if($oItems){
                            foreach ($oItems as $items) { 
                                $order_items = new OrderItems();
                                $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                $order_items->barcode = $items['barcode'];
                                $order_items->new_luggage = $items['new_luggage'];
                                $order_items->deleted_status = $items['deleted_status'];
                                $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                $order_items->item_price = $items['item_price'];
                                $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                                $order_items->item_weight = $items['item_weight'];
                                $order_items->items_old_weight = $items['items_old_weight'];
                                $order_items->save(false);
                            }
                        }
                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        } 
                        $order_payment->amount_paid = $total_price_colleted; 
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                        $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                        if(!empty($orderOffer)){
                            foreach ($orderOffer as $key => $offer) { 
                                $order_offer_item = new OrderOffers();
                                $order_offer_item->order_id=$model->id_order;
                                $order_offer_item->luggage_type =$offer['luggage_type'];
                                $order_offer_item->base_price=$offer['base_price'];
                                $order_offer_item->offer_price=$offer['offer_price'];
                                $order_offer_item->save(false);
                            }
                        }
                        $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                        if(!empty($orderGroup)){ 
                            foreach ($orderGroup as $key => $group) {
                                $order_group_offer= new OrderGroupOffer();
                                $order_group_offer->order_id=$model->id_order;
                                $order_group_offer->group_id=$group['group_id'];
                                $order_group_offer->subsequent_price=$group['subsequent_price'];
                                $order_group_offer->save(false);
                            }
                        }
                        $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()
                            ->where(['orderId'=>$id_order])
                            ->one();
                        if($outstation_charge){
                            $OrderZoneDetails = new \app\api_v3\v3\models\OrderZoneDetails();
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $outstation_charge->outstationZoneId;
                            $OrderZoneDetails->cityZoneId = $outstation_charge->cityZoneId;
                            $OrderZoneDetails->stateId = $outstation_charge->stateId;
                            $OrderZoneDetails->extraKilometer = $outstation_charge->extraKilometer;
                            $OrderZoneDetails->taxAmount = $outstation_charge->taxAmount;
                            $OrderZoneDetails->outstationCharge = $outstation_charge->outstationCharge;
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                            $OrderZoneDetails->save(false);
                        }
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                        $model->service_type = ($order_query->service_type == 1) ? 2 : 1;
                        $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 2) ? 3 : 2;
                        $model->order_status = ($order_query->service_type == 2) ? 'Open' : 'Confirmed';
                        $model->signature1 = $model->signature1;
                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->signature2 = $model->signature2;
                        $model->save(false); 
                        //print_r($model->id_order);exit;

                        
                        $model1['order_details']=Order::getorderdetails($model->id_order);
                        if($_POST['payment_type'] == 'COD'){

                            /*for razor payment*/
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $total_price_colleted, $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{ 
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }
                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);
     
                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/
                        if($order_query->corporate_type == 1){
                            Yii::$app->queue->push(new DelhiAirport([
                               'order_id' => $model->id_order,
                               'order_status' => 'confirmed'
                           ]));
                        }
  
                        

                            if($order_query['order_transfer']==1){ 
                                if($_POST['Order']['readdress'] == 0){
                            $orderSpotDetails = OrderMetaDetails::find()
                                                ->where(['orderId'=>$id_order])
                                                ->asArray()->one();  
                            $orderMetaDetails = new OrderMetaDetails();
                            $orderMetaDetails->stateId = 0;
                        $orderMetaDetails->orderId = $model->id_order;
                        $orderMetaDetails->pickupPersonName = $orderSpotDetails['pickupPersonName'];
                        $orderMetaDetails->pickupPersonNumber = $orderSpotDetails['pickupPersonNumber'];
                        $orderMetaDetails->pickupPersonAddressLine1 = $orderSpotDetails['pickupPersonAddressLine1'];
                        $orderMetaDetails->pickupPersonAddressLine2 = $orderSpotDetails['pickupPersonAddressLine2'];

                        $orderMetaDetails->pickupArea = $orderSpotDetails['pickupArea'];
                        $orderMetaDetails->pickupPincode = $orderSpotDetails['pickupPincode'];
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];
                        $orderMetaDetails->pickupBuildingNumber = isset($orderSpotDetails['pickupBuildingNumber']) ? $orderSpotDetails['pickupBuildingNumber']:null;

                        
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];

                        $orderMetaDetails->pickupBusinessName = $orderSpotDetails['pickupBusinessName'];
                        $orderMetaDetails->pickupMallName = $orderSpotDetails['pickupMallName'];
                        $orderMetaDetails->pickupStoreName = $orderSpotDetails['pickupStoreName'];

                        if($orderSpotDetails['pickupLocationType'] == 2){
                            $orderMetaDetails->pickupHotelType = $orderSpotDetails['pickupHotelType'];
                            $orderMetaDetails->PickupHotelName = $orderSpotDetails['PickupHotelName'];
                        }


                        $orderMetaDetails->dropPersonName = $orderSpotDetails['dropPersonName'];
                        $orderMetaDetails->dropPersonNumber = $orderSpotDetails['dropPersonNumber'];
                        $orderMetaDetails->dropPersonAddressLine1 = $orderSpotDetails['dropPersonAddressLine1'];
                        $orderMetaDetails->dropPersonAddressLine2 = $orderSpotDetails['dropPersonAddressLine2'];
                        $orderMetaDetails->droparea = $orderSpotDetails['droparea'];
                        $orderMetaDetails->dropPincode = $orderSpotDetails['dropPincode'];
                        $orderMetaDetails->dropBuildingNumber = isset($orderSpotDetails['dropBuildingNumber']) ? $orderSpotDetails['dropBuildingNumber']:null; 

                        //drop
                        $orderMetaDetails->dropLocationType = $orderSpotDetails['dropLocationType'];
                        $orderMetaDetails->dropBusinessName = $orderSpotDetails['dropBusinessName'];
                        $orderMetaDetails->dropMallName = $orderSpotDetails['dropMallName'];
                        $orderMetaDetails->dropStoreName = $orderSpotDetails['dropStoreName'];

                        if($orderSpotDetails['dropLocationType'] == 2){
                            $orderMetaDetails->dropHotelType =$orderSpotDetails['dropHotelType'];
                            $orderMetaDetails->dropHotelName = $orderSpotDetails['dropHotelName'];
                        }
                            $orderMetaDetails->save(false); 

                        }elseif ($_POST['Order']['readdress'] == 1) {
                       
                       $orderSpotDetails = OrderMetaDetails::find()
                                                ->where(['orderId'=>$id_order])
                                                ->asArray()->one(); 
 
                            $orderMetaDetails = new OrderMetaDetails();
                            $orderMetaDetails->stateId = 0;
                        $orderMetaDetails->orderId = $model->id_order;
                        $orderMetaDetails->pickupPersonName = $orderSpotDetails['pickupPersonName'];
                        $orderMetaDetails->pickupPersonNumber = $orderSpotDetails['pickupPersonNumber'];
                        $orderMetaDetails->pickupPersonAddressLine1 = $orderSpotDetails['pickupPersonAddressLine1'];
                        $orderMetaDetails->pickupPersonAddressLine2 = $orderSpotDetails['pickupPersonAddressLine2'];

                        $orderMetaDetails->pickupArea = $orderSpotDetails['pickupArea'];
                        $orderMetaDetails->pickupPincode = $orderSpotDetails['pickupPincode'];
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];
                        $orderMetaDetails->pickupBuildingNumber = isset($orderSpotDetails['pickupBuildingNumber']) ? $orderSpotDetails['pickupBuildingNumber']:null;

                        
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];

                        $orderMetaDetails->pickupBusinessName = $orderSpotDetails['pickupBusinessName'];
                        $orderMetaDetails->pickupMallName = $orderSpotDetails['pickupMallName'];
                        $orderMetaDetails->pickupStoreName = $orderSpotDetails['pickupStoreName'];

                        if($orderSpotDetails['pickupLocationType'] == 2){
                            $orderMetaDetails->pickupHotelType = $orderSpotDetails['pickupHotelType'];
                            $orderMetaDetails->PickupHotelName = $orderSpotDetails['PickupHotelName'];
                        }



                        $orderMetaDetails->dropBuildingNumber = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;

                        $orderMetaDetails->dropPersonName = $_POST['OrderSpotDetails']['person_name'];
                        $orderMetaDetails->dropPersonNumber = $_POST['OrderSpotDetails']['person_mobile_number'];
                        $orderMetaDetails->dropPersonAddressLine1 = $_POST['OrderSpotDetails']['address_line_1'];
                        $orderMetaDetails->dropPersonAddressLine2 = null;
                        $orderMetaDetails->droparea = $_POST['OrderSpotDetails']['area'];
                        $orderMetaDetails->dropPincode = $_POST['OrderSpotDetails']['pincode'];    

                        //drop
                        $orderMetaDetails->dropLocationType = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                        $orderMetaDetails->dropBusinessName = $_POST['OrderSpotDetails']['business_name'];
                        $orderMetaDetails->dropMallName = $_POST['OrderSpotDetails']['mall_name'];
                        $orderMetaDetails->dropStoreName = $_POST['OrderSpotDetails']['store_name'];

                        if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                            $orderMetaDetails->dropHotelType = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'];
                            $orderMetaDetails->dropHotelName = $_POST['OrderSpotDetails']['hotel_name'];
                        }

                        $orderMetaDetails->save(false);
                        }

                            }else{


                                if($_POST['Order']['readdress'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();

                                               // print_r($orderSpotDetails);exit;
                            /*$orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->save(false);*/
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress'] == 1) {
                            // $orderSpotDetails = OrderSpotDetails::find()
                            //                     ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                            //                     ->one();
                            // $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails = new OrderSpotDetails();
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                            }

     

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }
                            $orderSpotDetails->save(false);
                        }



                            }


                        $mallInvoice = MallInvoices::find()
                                       ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                       ->one() ;
                            if(!empty($mallInvoice)){
                                $mallInvoice->isNewRecord = true;
                                $mallInvoice->id_mall_invoices = null;
                                $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                $mallInvoice->save(false);
                        }
                        if($order_details['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                        }else{
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                        }
                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                        return $this->redirect(['order/index']);
                    }

                }else if ($_POST['Order']['hiddenServiceType'] == 2){ // FROM AIRPORT SCENARIO
                    if($_POST['Order']['rescheduleType'] == 0){
                        $order_query = Order::findOne($id_order); 
                        $model = new Order(
                                        $order_query->getAttributes() // get all attributes and copy them to the new instance
                                    );
                        $model->id_order = null; 
                        $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] : null;
                        $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) : null;
                        $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] : null;
                        $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) : null;
                        $model->flight_number = $_POST['Order']['flight_number'];
                        $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate'])); 
                        $insurance_price = $insurance;
                        $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                        if($order_undelivered){
                            $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                        }

                        if(isset($_POST['Order']['insurance_price'])){
                            if($_POST['Order']['insurance_price'] == 1){
                                $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $model->amount_paid  = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $totainsurance_price = $insurance + (.18 * $insurance) ;
                                $model->insurance_price = $totainsurance_price;
                            }else{
                               $model->insurance_price = $order_query['insurance_price'];
                            }
                        } 
                        //print_r($model->insurance_price);exit;
 
                        $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price)+ ($model->insurance_price);
                        $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->reschedule_luggage = 1;
                        $model->admin_edit_modified = 0;
                        $model->related_order_id = $id_order;
                        $model->fk_tbl_order_id_slot = 6;
                        $model->service_type = 2;
                        $model->no_of_units = $order_details['order']['luggage_count'];
                        $model->order_modified = 0;
                         $model->modified_amount = null;
                        $model->payment_method = $_POST['payment_type']; 
                        if($_POST['payment_type']=='COD'){
                            $model->amount_paid=0;
                        } 
                        if($model->save(false)){
                            $model = Order::findOne($model->id_order);
                            $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                            $model->fk_tbl_order_status_id_order_status = 2;
                            $model->express_extra_amount = $order_query->express_extra_amount;
                            $model->dservice_type = $_POST['Order']['dservice_type'];
                            $model->order_status = 'Confirmed';
                            $model->save(false);

                            $model1['order_details']=Order::getorderdetails($model->id_order);

                            $oItems = OrderItems::find()
                                        ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                        ->all();
                                if($oItems){
                                    foreach ($oItems as $items) {
                                        $order_items = new OrderItems();
                                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                        $order_items->barcode = $items['barcode'];
                                        $order_items->new_luggage = $items['new_luggage'];
                                        $order_items->deleted_status = $items['deleted_status'];
                                        $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                        $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                        $order_items->item_price = $items['item_price'];
                                        $order_items->bag_weight = $items['bag_weight'];
                                        $order_items->bag_type = $items['bag_type'];
                                        $order_items->item_weight = $items['item_weight'];
                                        $order_items->items_old_weight = $items['items_old_weight'];
                                        $order_items->save(false);
                                    }
                                }

                                $orderOffer = OrderOffers::find()
                                                        ->where(['order_id'=>$id_order])
                                                        ->all();
                                if(!empty($orderOffer)){

                                    foreach ($orderOffer as $key => $offer) {
                                        $order_offer_item = new OrderOffers();
                                        $order_offer_item->order_id=$model->id_order;
                                        $order_offer_item->luggage_type =$offer['luggage_type'];
                                        $order_offer_item->base_price=$offer['base_price'];
                                        $order_offer_item->offer_price=$offer['offer_price'];
                                        $order_offer_item->save(false);
                                    }
                                }  
                                $order_payment = new OrderPaymentDetails();
                                $order_payment->id_order = $model->id_order;
                                $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                                $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                                if($_POST['payment_type'] == 'cash'){
                                    $order_payment->payment_status = 'Success';
                                }else{
                                    $order_payment->payment_status = 'Not paid';
                                }
                                $order_payment->amount_paid = $total_price_colleted;
                                $order_payment->value_payment_mode = 'Reschdule Order Amount';
                                $order_payment->date_created= date('Y-m-d H:i:s');
                                $order_payment->date_modified= date('Y-m-d H:i:s');
                                $order_payment->save(false);
                                // }
                                $orderGroup= OrderGroupOffer:: find()
                                                            ->where(['order_id'=>$id_order])
                                                            ->all();
                                if(!empty($orderGroup)){

                                    foreach ($orderGroup as $key => $group) {
                                        $order_group_offer= new OrderGroupOffer();
                                        $order_group_offer->order_id=$model->id_order;
                                        $order_group_offer->group_id=$group['group_id'];
                                        $order_group_offer->subsequent_price=$group['subsequent_price'];
                                        $order_group_offer->save(false);
                                    }
                                } 


                            if($_POST['payment_type'] == 'COD'){
                                $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                            }else{
                                /*for cash order confirmation*/
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                                User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                            }
                            if(!empty($_FILES['Order']['name']['ticket'])){
                                $up = $employee_model->actionFileupload('ticket',$model->id_order);
                            }

                            $order_query = Order::findOne($id_order);
                            $order_query->reschedule_luggage = 1;
                            $order_query->related_order_id = $model->id_order;
                            $order_query->fk_tbl_order_status_id_order_status = 20;
                            $order_query->order_status = 'Rescheduled';
                            $order_query->save(false);

                            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                            Order::updatestatus($id_order,20,'Rescheduled');

                            /*order total table*/
                            $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                            /*order total*/
                            if($order_query->corporate_type == 1){
                                Yii::$app->queue->push(new DelhiAirport([
                                   'order_id' => $model->id_order,
                                   'order_status' => 'confirmed'
                               ]));
                            }
                            //Records Insert into OrderSpotDetails;
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                             if($order_query['order_transfer']==1){ 
                                if($_POST['Order']['readdress'] == 0){
                            $orderSpotDetails = OrderMetaDetails::find()
                                                ->where(['orderId'=>$id_order])
                                                ->asArray()->one();  
                            $orderMetaDetails = new OrderMetaDetails();
                            $orderMetaDetails->stateId = 0;
                        $orderMetaDetails->orderId = $model->id_order;
                        $orderMetaDetails->pickupPersonName = $orderSpotDetails['pickupPersonName'];
                        $orderMetaDetails->pickupPersonNumber = $orderSpotDetails['pickupPersonNumber'];
                        $orderMetaDetails->pickupPersonAddressLine1 = $orderSpotDetails['pickupPersonAddressLine1'];
                        $orderMetaDetails->pickupPersonAddressLine2 = $orderSpotDetails['pickupPersonAddressLine2'];

                        $orderMetaDetails->pickupArea = $orderSpotDetails['pickupArea'];
                        $orderMetaDetails->pickupPincode = $orderSpotDetails['pickupPincode'];
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];
                        $orderMetaDetails->pickupBuildingNumber = isset($orderSpotDetails['pickupBuildingNumber']) ? $orderSpotDetails['pickupBuildingNumber']:null;

                        
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];

                        $orderMetaDetails->pickupBusinessName = $orderSpotDetails['pickupBusinessName'];
                        $orderMetaDetails->pickupMallName = $orderSpotDetails['pickupMallName'];
                        $orderMetaDetails->pickupStoreName = $orderSpotDetails['pickupStoreName'];

                        if($orderSpotDetails['pickupLocationType'] == 2){
                            $orderMetaDetails->pickupHotelType = $orderSpotDetails['pickupHotelType'];
                            $orderMetaDetails->PickupHotelName = $orderSpotDetails['PickupHotelName'];
                        }


                        $orderMetaDetails->dropPersonName = $orderSpotDetails['dropPersonName'];
                        $orderMetaDetails->dropPersonNumber = $orderSpotDetails['dropPersonNumber'];
                        $orderMetaDetails->dropPersonAddressLine1 = $orderSpotDetails['dropPersonAddressLine1'];
                        $orderMetaDetails->dropPersonAddressLine2 = $orderSpotDetails['dropPersonAddressLine2'];
                        $orderMetaDetails->droparea = $orderSpotDetails['droparea'];
                        $orderMetaDetails->dropPincode = $orderSpotDetails['dropPincode'];
                        $orderMetaDetails->dropBuildingNumber = isset($orderSpotDetails['dropBuildingNumber']) ? $orderSpotDetails['dropBuildingNumber']:null; 

                        //drop
                        $orderMetaDetails->dropLocationType = $orderSpotDetails['dropLocationType'];
                        $orderMetaDetails->dropBusinessName = $orderSpotDetails['dropBusinessName'];
                        $orderMetaDetails->dropMallName = $orderSpotDetails['dropMallName'];
                        $orderMetaDetails->dropStoreName = $orderSpotDetails['dropStoreName'];

                        if($orderSpotDetails['dropLocationType'] == 2){
                            $orderMetaDetails->dropHotelType =$orderSpotDetails['dropHotelType'];
                            $orderMetaDetails->dropHotelName = $orderSpotDetails['dropHotelName'];
                        }
                            $orderMetaDetails->save(false); 

                        }elseif ($_POST['Order']['readdress'] == 1) {
                       
                       $orderSpotDetails = OrderMetaDetails::find()
                                                ->where(['orderId'=>$id_order])
                                                ->asArray()->one(); 
 
                            $orderMetaDetails = new OrderMetaDetails();
                            $orderMetaDetails->stateId = 0;
                        $orderMetaDetails->orderId = $model->id_order;
                        $orderMetaDetails->pickupPersonName = $orderSpotDetails['pickupPersonName'];
                        $orderMetaDetails->pickupPersonNumber = $orderSpotDetails['pickupPersonNumber'];
                        $orderMetaDetails->pickupPersonAddressLine1 = $orderSpotDetails['pickupPersonAddressLine1'];
                        $orderMetaDetails->pickupPersonAddressLine2 = $orderSpotDetails['pickupPersonAddressLine2'];

                        $orderMetaDetails->pickupArea = $orderSpotDetails['pickupArea'];
                        $orderMetaDetails->pickupPincode = $orderSpotDetails['pickupPincode'];
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];
                        $orderMetaDetails->pickupBuildingNumber = isset($orderSpotDetails['pickupBuildingNumber']) ? $orderSpotDetails['pickupBuildingNumber']:null;

                        
                        $orderMetaDetails->pickupLocationType = $orderSpotDetails['pickupLocationType'];

                        $orderMetaDetails->pickupBusinessName = $orderSpotDetails['pickupBusinessName'];
                        $orderMetaDetails->pickupMallName = $orderSpotDetails['pickupMallName'];
                        $orderMetaDetails->pickupStoreName = $orderSpotDetails['pickupStoreName'];

                        if($orderSpotDetails['pickupLocationType'] == 2){
                            $orderMetaDetails->pickupHotelType = $orderSpotDetails['pickupHotelType'];
                            $orderMetaDetails->PickupHotelName = $orderSpotDetails['PickupHotelName'];
                        }



                        $orderMetaDetails->dropBuildingNumber = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;

                        $orderMetaDetails->dropPersonName = $_POST['OrderSpotDetails']['person_name'];
                        $orderMetaDetails->dropPersonNumber = $_POST['OrderSpotDetails']['person_mobile_number'];
                        $orderMetaDetails->dropPersonAddressLine1 = $_POST['OrderSpotDetails']['address_line_1'];
                        $orderMetaDetails->dropPersonAddressLine2 = null;
                        $orderMetaDetails->droparea = $_POST['OrderSpotDetails']['area'];
                        $orderMetaDetails->dropPincode = $_POST['OrderSpotDetails']['pincode'];    

                        //drop
                        $orderMetaDetails->dropLocationType = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                        $orderMetaDetails->dropBusinessName = $_POST['OrderSpotDetails']['business_name'];
                        $orderMetaDetails->dropMallName = $_POST['OrderSpotDetails']['mall_name'];
                        $orderMetaDetails->dropStoreName = $_POST['OrderSpotDetails']['store_name'];

                        if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                            $orderMetaDetails->dropHotelType = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'];
                            $orderMetaDetails->dropHotelName = $_POST['OrderSpotDetails']['hotel_name'];
                        }

                        $orderMetaDetails->save(false);
                        }

                            }else{

                            if($_POST['Order']['readdress1'] == 0){
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $newspotDet = new OrderSpotDetails();
                                $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                                $newspotDet->person_name = $orderSpotDetails->person_name;
                                $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                                $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                                $newspotDet->building_number = $orderSpotDetails->building_number;
                                $newspotDet->landmark = $orderSpotDetails->landmark;
                                $newspotDet->area = $orderSpotDetails->area;
                                $newspotDet->pincode = $orderSpotDetails->pincode;
                                $newspotDet->business_name = $orderSpotDetails->business_name;
                                $newspotDet->mall_name = $orderSpotDetails->mall_name;
                                $newspotDet->store_name = $orderSpotDetails->store_name;
                                $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                                $newspotDet->other_comments = $orderSpotDetails->other_comments;
                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                                $newspotDet->save(false);

                            }elseif ($_POST['Order']['readdress1'] == 1) { 
                                $orderSpotDetails = new OrderSpotDetails();
                                $orderSpotDetails->id_order_spot_details = null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                                $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                                $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                                $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                                $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                                $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                                $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                                $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                                $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                                $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                                $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                                $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                                $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null; 

                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                }

                                if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                                {
                                    $orderSpotDetails->assigned_person = 1;
                                }

                                $orderSpotDetails->save(false);
                            }
                        }


                            
                                
                                $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }
                                if($order_details['order']['order_transfer']==1){
                                    $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                }else{
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                            
                            
                        }
                    }elseif ($_POST['Order']['rescheduleType'] == 1) {
                        $order_query = Order::findOne($id_order); 
                        $model = new Order(
                                        $order_query->getAttributes() // get all attributes and copy them to the new instance
                                    );
                        $model->id_order = null;
                        $model->reschedule_luggage = 1; 
                        $model->departure_date = null;
                        $model->departure_time = null;
                        $model->arrival_date = null;
                        $model->arrival_time = null;
                        $model->flight_number = null;
                        $model->meet_time_gate = null; 
                        $insurance_price = $insurance;
                        $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                        if($order_undelivered){
                            $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                        }

                        if(isset($_POST['Order']['insurance_price'])){
                            if($_POST['Order']['insurance_price'] == 1){
                                $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $totainsurance_price = $insurance + (.18 * $insurance) ;
                                $model->insurance_price = $totainsurance_price;
                            }else{
                               $model->insurance_price = $order_query['insurance_price'];
                            }
                        }
                        $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->service_type = 2;
                        $model->admin_edit_modified = 0;
                        $model->related_order_id = $id_order;
                        $model->fk_tbl_order_id_slot = 4;
                        $model->no_of_units = $order_details['order']['luggage_count'];
                        $model->order_modified = 0;
                        $model->modified_amount = null;
                        $model->payment_method = $_POST['payment_type']; 
                        if($_POST['payment_type']=='COD'){
                            $model->amount_paid=0;
                        } 
                        if($model->save(false)){
                            $model = Order::findOne($model->id_order);
                            $model->order_number = 'ON'.date('mdYHis').$model->id_order;

                            $model->express_extra_amount = $order_query->express_extra_amount;
                            $model->fk_tbl_order_status_id_order_status =  2;
                            $model->order_status = 'Confirmed';
                            $model->dservice_type = $_POST['Order']['dservice_type'];
                            $model->save(false);
  
                            /*new email Integration*/
                            $model1['order_details']=Order::getorderdetails($model->id_order);


                            // Records Insert into OrderItems;
                                $oItems = OrderItems::find()
                                        ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                        ->all();
                                if($oItems){
                                    foreach ($oItems as $items) {
                                        $order_items = new OrderItems();
                                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                        $order_items->barcode = $items['barcode'];
                                        $order_items->new_luggage = $items['new_luggage'];
                                        $order_items->deleted_status = $items['deleted_status'];
                                        $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                        $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                        $order_items->item_price = $items['item_price'];
                                        $order_items->bag_weight = $items['bag_weight'];
                                        $order_items->bag_type = $items['bag_type'];
                                        $order_items->item_weight = $items['item_weight'];
                                        $order_items->items_old_weight = $items['items_old_weight'];
                                        $order_items->save(false);
                                    }
                                }

                                $orderOffer = OrderOffers::find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                                if(!empty($orderOffer)){

                                    foreach ($orderOffer as $key => $offer) {
                                        $order_offer_item = new OrderOffers();
                                        $order_offer_item->order_id=$model->id_order;
                                        $order_offer_item->luggage_type =$offer['luggage_type'];
                                        $order_offer_item->base_price=$offer['base_price'];
                                        $order_offer_item->offer_price=$offer['offer_price'];
                                        $order_offer_item->save(false);
                                    }
                                } 

                                $order_payment = new OrderPaymentDetails();
                                $order_payment->id_order = $model->id_order;
                                $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                                $order_payment->id_employee = Yii::$app->user->identity->id_employee;

                                if($_POST['payment_type'] == 'cash'){
                                    $order_payment->payment_status = 'Success';
                                }else{
                                    $order_payment->payment_status = 'Not paid';
                                }
                                $order_payment->amount_paid = $total_price_colleted;
                                $order_payment->value_payment_mode = 'Reschdule Order Amount';
                                $order_payment->date_created= date('Y-m-d H:i:s');
                                $order_payment->date_modified= date('Y-m-d H:i:s');
                                $order_payment->save(false);
                        
                                $orderGroup= OrderGroupOffer:: find()
                                                            ->where(['order_id'=>$id_order])
                                                            ->all();
                                if(!empty($orderGroup)){

                                    foreach ($orderGroup as $key => $group) {
                                        $order_group_offer= new OrderGroupOffer();
                                        $order_group_offer->order_id=$model->id_order;
                                        $order_group_offer->group_id=$group['group_id'];
                                        $order_group_offer->subsequent_price=$group['subsequent_price'];
                                        $order_group_offer->save(false);
                                    }
                                } 


                            if($_POST['payment_type'] == 'COD'){
                                $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                            }else{
                                /*for cash order confirmation*/
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                                User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                            }

                            $order_query = Order::findOne($id_order);
                            $order_query->reschedule_luggage = 1;
                            $order_query->related_order_id = $model->id_order;
                            $order_query->fk_tbl_order_status_id_order_status = 20;
                            $order_query->order_status = 'Rescheduled';
                            $order_query->save(false);

                            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                            Order::updatestatus($id_order,20,'Rescheduled');

                            /*order total table*/
                            $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                            /*order total*/
                            if($order_query->corporate_type == 1){
                                Yii::$app->queue->push(new DelhiAirport([
                                   'order_id' => $model->id_order,
                                   'order_status' => 'confirmed'
                               ]));
                            }

                            //Records Insert into OrderSpotDetails;
                            if($_POST['Order']['readdress1'] == 0){
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $newspotDet = new OrderSpotDetails();
                                $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                                $newspotDet->person_name = $orderSpotDetails->person_name;
                                $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                                $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                                $newspotDet->building_number = $orderSpotDetails->building_number;
                                $newspotDet->landmark = $orderSpotDetails->landmark;
                                $newspotDet->area = $orderSpotDetails->area;
                                $newspotDet->pincode = $orderSpotDetails->pincode;
                                $newspotDet->business_name = $orderSpotDetails->business_name;
                                $newspotDet->mall_name = $orderSpotDetails->mall_name;
                                $newspotDet->store_name = $orderSpotDetails->store_name;
                                $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                                $newspotDet->other_comments = $orderSpotDetails->other_comments;
                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                                $newspotDet->save(false);

                            }elseif ($_POST['Order']['readdress1'] == 1) {
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $orderSpotDetails->isNewRecord = true;
                                $orderSpotDetails->id_order_spot_details = null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                                $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                                $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                                $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                                $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                                $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                                $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                                $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                                $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                                $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                                $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                                $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                                $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                                $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null; 
                                if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                        $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                        $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                }

                                if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                                {
                                    $orderSpotDetails->assigned_person = 1;
                                }
                            }

                            if($orderSpotDetails->save(false)){

                                if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                    $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                                }
                                
                                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                                    if($order_details['order']['order_transfer']==1){
                                        $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                    }else{
                                        $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                    }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                                }else{
                                    $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                    if(!empty($mallInvoice)){
                                        $mallInvoice->isNewRecord = true;
                                        $mallInvoice->id_mall_invoices = null;
                                        $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                        $mallInvoice->save(false);
                                    }
                                    if($order_details['order']['order_transfer']==1){
                                        $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                    }else{
                                        $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                    }
                                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                        return $this->redirect(['order/index']);
                                    
                                }
                            }
                        }
                    }
                }

            }
            // *************** RESCHEDULE CODE FOR GENERAL AND MHL ORDER ENDS HERE ********************

            // RESCHEDULE CODE FOR THIRD PARTY CORPORATE STARTS HERE 
            else if(in_array($order_details['corporate_details']['corporate_type'], [3,4,5])){
                $corporate_data = \app\models\ThirdpartyCorporate::find()->where(['fk_corporate_id'=>$order_details['corporate_details']['corporate_detail_id']])->one();
                if($corporate_data){
                    $corporate_gst = $corporate_data->gst/100;
                }else{
                    $corporate_gst = (Yii::$app->params['gst_percent']/100);
                }
                if($_POST['Order']['hiddenServiceType']==1){ // TO AIRPORT SCENARIO
                    $order_query = Order::findOne($id_order);
                    $model = new Order( $order_query->getAttributes());
                    $model->id_order = null;
                    $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  
                    $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
                    $model->insurance_price = 0;

                    if($_POST['Order']['fk_tbl_order_id_slot'] == 4)
                    {
                        $model->arrival_time = date('H:i:s', strtotime('03:00 PM'));
                        $model->meet_time_gate = date('H:i:s', strtotime('03:30 PM'));
                    }else if($_POST['Order']['fk_tbl_order_id_slot'] == 5){
                        $model->arrival_time = date('H:i:s', strtotime('11:55 PM'));
                        $model->meet_time_gate = date('H:i:s', strtotime('12:25 AM'));
                    }
                    $model->dservice_type = $_POST['Order']['dservice_type'];
                    $model->order_modified = 0;
                    $model->modified_amount = null;
                    $model->payment_method = $_POST['payment_type'];
                    $insurance_price = $insurance;
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }
                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst;
                            $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst;
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }
                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->amount_paid = $order_details['order']['amount_paid'];
                    $model->reschedule_luggage = 1;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    if($_POST['payment_type']=='COD'){
                        $model->amount_paid=0;
                    }
                    if($model->save(false)){
                        $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                        //print_r($oItems);exit;
                        if($oItems){
                            foreach ($oItems as $items) { 
                                $order_items = new OrderItems();
                                $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                $order_items->barcode = $items['barcode'];
                                $order_items->new_luggage = $items['new_luggage'];
                                $order_items->deleted_status = $items['deleted_status'];
                                $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                $order_items->item_price = $items['item_price'];
                                $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                                $order_items->item_weight = $items['item_weight'];
                                $order_items->items_old_weight = $items['items_old_weight'];
                                $order_items->save(false);
                            }
                        }
                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        } 
                        $order_payment->amount_paid = $total_price_colleted; 
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                        $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                        if(!empty($orderOffer)){
                            foreach ($orderOffer as $key => $offer) { 
                                $order_offer_item = new OrderOffers();
                                $order_offer_item->order_id=$model->id_order;
                                $order_offer_item->luggage_type =$offer['luggage_type'];
                                $order_offer_item->base_price=$offer['base_price'];
                                $order_offer_item->offer_price=$offer['offer_price'];
                                $order_offer_item->save(false);
                            }
                        }
                        $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                        if(!empty($orderGroup)){ 
                            foreach ($orderGroup as $key => $group) {
                                $order_group_offer= new OrderGroupOffer();
                                $order_group_offer->order_id=$model->id_order;
                                $order_group_offer->group_id=$group['group_id'];
                                $order_group_offer->subsequent_price=$group['subsequent_price'];
                                $order_group_offer->save(false);
                            }
                        }
                        $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()
                            ->where(['orderId'=>$id_order])
                            ->one();
                        if($outstation_charge){
                            $OrderZoneDetails = new \app\api_v3\v3\models\OrderZoneDetails();
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $outstation_charge->outstationZoneId;
                            $OrderZoneDetails->cityZoneId = $outstation_charge->cityZoneId;
                            $OrderZoneDetails->stateId = $outstation_charge->stateId;
                            $OrderZoneDetails->extraKilometer = $outstation_charge->extraKilometer;
                            $OrderZoneDetails->taxAmount = $outstation_charge->taxAmount;
                            $OrderZoneDetails->outstationCharge = $outstation_charge->outstationCharge;
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                            $OrderZoneDetails->save(false);
                        }
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                        $model->service_type = ($order_query->service_type == 1) ? 2 : 1;
                        $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 2) ? 3 : 2;
                        $model->order_status = ($order_query->service_type == 2) ? 'Open' : 'Confirmed';
                        $model->signature1 = $model->signature1;
                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->signature2 = $model->signature2;
                        $model->save(false); 
                        $model1['order_details']=Order::getorderdetails($model->id_order);
                        if($_POST['payment_type'] == 'COD'){

                            /*for razor payment*/
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $total_price_colleted, $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{ 
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }
                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);
     
                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/

                        if($_POST['Order']['readdress'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            /*$orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->save(false);*/
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress'] == 1) {
                            // $orderSpotDetails = OrderSpotDetails::find()
                            //                     ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                            //                     ->one();
                            // $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails = new OrderSpotDetails();
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                            }

     

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }
                            $orderSpotDetails->save(false);
                        }
                        $mallInvoice = MallInvoices::find()
                                       ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                       ->one() ;
                            if(!empty($mallInvoice)){
                                $mallInvoice->isNewRecord = true;
                                $mallInvoice->id_mall_invoices = null;
                                $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                $mallInvoice->save(false);
                        }
                        if($order_details['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                        }else{
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                        }
                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                        return $this->redirect(['order/index']);
                    }

                }else if ($_POST['Order']['hiddenServiceType'] == 2){ // FROM AIRPORT SCENARIO
                    if($_POST['Order']['rescheduleType'] == 0){
                        $order_query = Order::findOne($id_order); 
                        $model = new Order(
                                        $order_query->getAttributes() // get all attributes and copy them to the new instance
                                    );
                        $model->id_order = null; 
                        $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] : null;
                        $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) : null;
                        $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] : null;
                        $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) : null;
                        $model->flight_number = $_POST['Order']['flight_number'];
                        $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate'])); 
                        $insurance_price = $insurance;
                        $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                        if($order_undelivered){
                            $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                        }

                        if(isset($_POST['Order']['insurance_price'])){
                            if($_POST['Order']['insurance_price'] == 1){
                                $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst;
                                $model->amount_paid  = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst;
                                $totainsurance_price = $insurance + (.18 * $insurance) ;
                                $model->insurance_price = $totainsurance_price;
                            }else{
                               $model->insurance_price = $order_query['insurance_price'];
                            }
                        } 
 
                        $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst) - ($order_query->insurance_price) + ($model->insurance_price)+ ($model->insurance_price);
                        $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->reschedule_luggage = 1;
                        $model->admin_edit_modified = 0;
                        $model->related_order_id = $id_order;
                        $model->fk_tbl_order_id_slot = 6;
                        $model->service_type = 2;
                        $model->no_of_units = $order_details['order']['luggage_count'];
                        $model->order_modified = 0;
                         $model->modified_amount = null;
                        $model->payment_method = $_POST['payment_type']; 
                        if($_POST['payment_type']=='COD'){
                            $model->amount_paid=0;
                        } 
                        if($model->save(false)){
                            $model = Order::findOne($model->id_order);
                            $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                            $model->fk_tbl_order_status_id_order_status = 2;
                            $model->express_extra_amount = $order_query->express_extra_amount;
                            $model->dservice_type = $_POST['Order']['dservice_type'];
                            $model->order_status = 'Confirmed';
                            $model->save(false);

                            $model1['order_details']=Order::getorderdetails($model->id_order);

                            $oItems = OrderItems::find()
                                        ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                        ->all();
                                if($oItems){
                                    foreach ($oItems as $items) {
                                        $order_items = new OrderItems();
                                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                        $order_items->barcode = $items['barcode'];
                                        $order_items->new_luggage = $items['new_luggage'];
                                        $order_items->deleted_status = $items['deleted_status'];
                                        $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                        $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                        $order_items->item_price = $items['item_price'];
                                        $order_items->bag_weight = $items['bag_weight'];
                                        $order_items->bag_type = $items['bag_type'];
                                        $order_items->item_weight = $items['item_weight'];
                                        $order_items->items_old_weight = $items['items_old_weight'];
                                        $order_items->save(false);
                                    }
                                }

                                $orderOffer = OrderOffers::find()
                                                        ->where(['order_id'=>$id_order])
                                                        ->all();
                                if(!empty($orderOffer)){

                                    foreach ($orderOffer as $key => $offer) {
                                        $order_offer_item = new OrderOffers();
                                        $order_offer_item->order_id=$model->id_order;
                                        $order_offer_item->luggage_type =$offer['luggage_type'];
                                        $order_offer_item->base_price=$offer['base_price'];
                                        $order_offer_item->offer_price=$offer['offer_price'];
                                        $order_offer_item->save(false);
                                    }
                                }  
                                $order_payment = new OrderPaymentDetails();
                                $order_payment->id_order = $model->id_order;
                                $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                                $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                                if($_POST['payment_type'] == 'cash'){
                                    $order_payment->payment_status = 'Success';
                                }else{
                                    $order_payment->payment_status = 'Not paid';
                                }
                                $order_payment->amount_paid = $total_price_colleted;
                                $order_payment->value_payment_mode = 'Reschdule Order Amount';
                                $order_payment->date_created= date('Y-m-d H:i:s');
                                $order_payment->date_modified= date('Y-m-d H:i:s');
                                $order_payment->save(false);
                                // }
                                $orderGroup= OrderGroupOffer:: find()
                                                            ->where(['order_id'=>$id_order])
                                                            ->all();
                                if(!empty($orderGroup)){

                                    foreach ($orderGroup as $key => $group) {
                                        $order_group_offer= new OrderGroupOffer();
                                        $order_group_offer->order_id=$model->id_order;
                                        $order_group_offer->group_id=$group['group_id'];
                                        $order_group_offer->subsequent_price=$group['subsequent_price'];
                                        $order_group_offer->save(false);
                                    }
                                } 


                            if($_POST['payment_type'] == 'COD'){
                                $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                            }else{
                                /*for cash order confirmation*/
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                                User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                            }
                            if(!empty($_FILES['Order']['name']['ticket'])){
                                $up = $employee_model->actionFileupload('ticket',$model->id_order);
                            }

                            $order_query = Order::findOne($id_order);
                            $order_query->reschedule_luggage = 1;
                            $order_query->related_order_id = $model->id_order;
                            $order_query->fk_tbl_order_status_id_order_status = 20;
                            $order_query->order_status = 'Rescheduled';
                            $order_query->save(false);

                            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                            Order::updatestatus($id_order,20,'Rescheduled');

                            /*order total table*/
                            $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                            /*order total*/
                            if($order_query->corporate_type == 1){
                                Yii::$app->queue->push(new DelhiAirport([
                                   'order_id' => $model->id_order,
                                   'order_status' => 'confirmed'
                               ]));
                            }
                            //Records Insert into OrderSpotDetails;
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;



                            if($_POST['Order']['readdress1'] == 0){
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $newspotDet = new OrderSpotDetails();
                                $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                                $newspotDet->person_name = $orderSpotDetails->person_name;
                                $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                                $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                                $newspotDet->building_number = $orderSpotDetails->building_number;
                                $newspotDet->landmark = $orderSpotDetails->landmark;
                                $newspotDet->area = $orderSpotDetails->area;
                                $newspotDet->pincode = $orderSpotDetails->pincode;
                                $newspotDet->business_name = $orderSpotDetails->business_name;
                                $newspotDet->mall_name = $orderSpotDetails->mall_name;
                                $newspotDet->store_name = $orderSpotDetails->store_name;
                                $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                                $newspotDet->other_comments = $orderSpotDetails->other_comments;
                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                                $newspotDet->save(false);

                            }elseif ($_POST['Order']['readdress1'] == 1) { 
                                $orderSpotDetails = new OrderSpotDetails();
                                $orderSpotDetails->id_order_spot_details = null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                                $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                                $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                                $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                                $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                                $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                                $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                                $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                                $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                                $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                                $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                                $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                                $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null; 

                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                }

                                if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                                {
                                    $orderSpotDetails->assigned_person = 1;
                                }

                                $orderSpotDetails->save(false);
                            }


                            
                                
                                $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }
                                if($order_details['order']['order_transfer']==1){
                                    $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                }else{
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                            
                            
                        }
                    }elseif ($_POST['Order']['rescheduleType'] == 1) {
                        $order_query = Order::findOne($id_order); 
                        $model = new Order(
                                        $order_query->getAttributes() // get all attributes and copy them to the new instance
                                    );
                        $model->id_order = null;
                        $model->reschedule_luggage = 1; 
                        $model->departure_date = null;
                        $model->departure_time = null;
                        $model->arrival_date = null;
                        $model->arrival_time = null;
                        $model->flight_number = null;
                        $model->meet_time_gate = null; 
                        $insurance_price = $insurance;
                        $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                        if($order_undelivered){
                            $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                        }

                        if(isset($_POST['Order']['insurance_price'])){
                            if($_POST['Order']['insurance_price'] == 1){
                                $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst;
                                $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst;
                                $totainsurance_price = $insurance + (.18 * $insurance) ;
                                $model->insurance_price = $totainsurance_price;
                            }else{
                               $model->insurance_price = $order_query['insurance_price'];
                            }
                        }
                        $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * $corporate_gst) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->service_type =2 ;
                        $model->admin_edit_modified = 0;
                        $model->related_order_id = $id_order;
                        $model->fk_tbl_order_id_slot = 4;
                        $model->no_of_units = $order_details['order']['luggage_count'];
                        $model->order_modified = 0;
                        $model->modified_amount = null;
                        $model->payment_method = $_POST['payment_type']; 
                        if($_POST['payment_type']=='COD'){
                            $model->amount_paid=0;
                        } 
                        if($model->save(false)){
                            $model = Order::findOne($model->id_order);
                            $model->order_number = 'ON'.date('mdYHis').$model->id_order;

                            $model->express_extra_amount = $order_query->express_extra_amount;
                            $model->fk_tbl_order_status_id_order_status =  2;
                            $model->order_status = 'Confirmed';
                            $model->dservice_type = $_POST['Order']['dservice_type'];
                            $model->save(false);
  
                            /*new email Integration*/
                            $model1['order_details']=Order::getorderdetails($model->id_order);


                            // Records Insert into OrderItems;
                                $oItems = OrderItems::find()
                                        ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                        ->all();
                                if($oItems){
                                    foreach ($oItems as $items) {
                                        $order_items = new OrderItems();
                                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                        $order_items->barcode = $items['barcode'];
                                        $order_items->new_luggage = $items['new_luggage'];
                                        $order_items->deleted_status = $items['deleted_status'];
                                        $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                        $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                        $order_items->item_price = $items['item_price'];
                                        $order_items->bag_weight = $items['bag_weight'];
                                        $order_items->bag_type = $items['bag_type'];
                                        $order_items->item_weight = $items['item_weight'];
                                        $order_items->items_old_weight = $items['items_old_weight'];
                                        $order_items->save(false);
                                    }
                                }

                                $orderOffer = OrderOffers::find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                                if(!empty($orderOffer)){

                                    foreach ($orderOffer as $key => $offer) {
                                        $order_offer_item = new OrderOffers();
                                        $order_offer_item->order_id=$model->id_order;
                                        $order_offer_item->luggage_type =$offer['luggage_type'];
                                        $order_offer_item->base_price=$offer['base_price'];
                                        $order_offer_item->offer_price=$offer['offer_price'];
                                        $order_offer_item->save(false);
                                    }
                                } 

                                $order_payment = new OrderPaymentDetails();
                                $order_payment->id_order = $model->id_order;
                                $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                                $order_payment->id_employee = Yii::$app->user->identity->id_employee;

                                if($_POST['payment_type'] == 'cash'){
                                    $order_payment->payment_status = 'Success';
                                }else{
                                    $order_payment->payment_status = 'Not paid';
                                }
                                $order_payment->amount_paid = $total_price_colleted;
                                $order_payment->value_payment_mode = 'Reschdule Order Amount';
                                $order_payment->date_created= date('Y-m-d H:i:s');
                                $order_payment->date_modified= date('Y-m-d H:i:s');
                                $order_payment->save(false);
                        
                                $orderGroup= OrderGroupOffer:: find()
                                                            ->where(['order_id'=>$id_order])
                                                            ->all();
                                if(!empty($orderGroup)){

                                    foreach ($orderGroup as $key => $group) {
                                        $order_group_offer= new OrderGroupOffer();
                                        $order_group_offer->order_id=$model->id_order;
                                        $order_group_offer->group_id=$group['group_id'];
                                        $order_group_offer->subsequent_price=$group['subsequent_price'];
                                        $order_group_offer->save(false);
                                    }
                                } 


                            if($_POST['payment_type'] == 'COD'){
                                $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                            }else{
                                /*for cash order confirmation*/
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                                User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                            }

                            $order_query = Order::findOne($id_order);
                            $order_query->reschedule_luggage = 1;
                            $order_query->related_order_id = $model->id_order;
                            $order_query->fk_tbl_order_status_id_order_status = 20;
                            $order_query->order_status = 'Rescheduled';
                            $order_query->save(false);

                            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                            Order::updatestatus($id_order,20,'Rescheduled');

                            /*order total table*/
                            $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                            /*order total*/
                            if($order_query->corporate_type == 1){
                                Yii::$app->queue->push(new DelhiAirport([
                                   'order_id' => $model->id_order,
                                   'order_status' => 'confirmed'
                               ]));
                            }

                            //Records Insert into OrderSpotDetails;
                            if($_POST['Order']['readdress1'] == 0){
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $newspotDet = new OrderSpotDetails();
                                $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                                $newspotDet->person_name = $orderSpotDetails->person_name;
                                $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                                $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                                $newspotDet->building_number = $orderSpotDetails->building_number;
                                $newspotDet->landmark = $orderSpotDetails->landmark;
                                $newspotDet->area = $orderSpotDetails->area;
                                $newspotDet->pincode = $orderSpotDetails->pincode;
                                $newspotDet->business_name = $orderSpotDetails->business_name;
                                $newspotDet->mall_name = $orderSpotDetails->mall_name;
                                $newspotDet->store_name = $orderSpotDetails->store_name;
                                $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                                $newspotDet->other_comments = $orderSpotDetails->other_comments;
                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                                $newspotDet->save(false);

                            }elseif ($_POST['Order']['readdress1'] == 1) {
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $orderSpotDetails->isNewRecord = true;
                                $orderSpotDetails->id_order_spot_details = null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                                $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                                $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                                $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                                $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                                $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                                $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                                $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                                $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                                $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                                $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                                $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                                $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                                $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null; 
                                if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                        $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                        $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                }

                                if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                                {
                                    $orderSpotDetails->assigned_person = 1;
                                }
                            }

                            if($orderSpotDetails->save(false)){
                                if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                    $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                                }
                                
                                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                                    if($order_details['order']['order_transfer']==1){
                                        $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                    }else{
                                        $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                    }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                                }else{
                                    $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                    if(!empty($mallInvoice)){
                                        $mallInvoice->isNewRecord = true;
                                        $mallInvoice->id_mall_invoices = null;
                                        $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                        $mallInvoice->save(false);
                                    }

                                    if($order_details['order']['order_transfer']==1){
                                        $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                    }else{
                                        $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                    }
                                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                        return $this->redirect(['order/index']);
                                    
                                }
                            }
                        }
                    }
                }

            }else{ //CUSTOMER ORDERS
                if($_POST['Order']['hiddenServiceType']==1){ // TO AIRPORT SCENARIO
                    $order_query = Order::findOne($id_order);
                    $model = new Order( $order_query->getAttributes());
                    $model->id_order = null;
                    $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  
                    $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
                    $model->insurance_price = 0;

                    if($_POST['Order']['fk_tbl_order_id_slot'] == 4)
                    {
                        $model->arrival_time = date('H:i:s', strtotime('03:00 PM'));
                        $model->meet_time_gate = date('H:i:s', strtotime('03:30 PM'));
                    }else if($_POST['Order']['fk_tbl_order_id_slot'] == 5){
                        $model->arrival_time = date('H:i:s', strtotime('11:55 PM'));
                        $model->meet_time_gate = date('H:i:s', strtotime('12:25 AM'));
                    }
                    $model->dservice_type = $_POST['Order']['dservice_type'];
                    $model->order_modified = 0;
                    $model->modified_amount = null;
                    $model->payment_method = $_POST['payment_type'];
                    $insurance_price = $insurance; 
                    $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                    }
                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                            $totainsurance_price = $insurance + (.18 * $insurance) ;
                            $model->insurance_price = $totainsurance_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }
                    //print_r($model->insurance_price);exit;
                    $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                    $model->amount_paid = $order_details['order']['amount_paid'];
                    $model->reschedule_luggage = 1;
                    $model->admin_edit_modified = 0;
                    $model->related_order_id = $id_order;
                    $model->no_of_units = $order_details['order']['luggage_count'];
                    if($_POST['payment_type']=='COD'){
                        $model->amount_paid=0;
                    }
                    if($model->save(false)){
                        $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                        //print_r($oItems);exit;
                        if($oItems){
                            foreach ($oItems as $items) { 
                                $order_items = new OrderItems();
                                $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                $order_items->barcode = $items['barcode'];
                                $order_items->new_luggage = $items['new_luggage'];
                                $order_items->deleted_status = $items['deleted_status'];
                                $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                $order_items->item_price = $items['item_price'];
                                $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                                $order_items->item_weight = $items['item_weight'];
                                $order_items->items_old_weight = $items['items_old_weight'];
                                $order_items->save(false);
                            }
                        }
                        $order_payment = new OrderPaymentDetails();
                        $order_payment->id_order = $model->id_order;
                        $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                        $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                        if($_POST['payment_type'] == 'cash'){
                            $order_payment->payment_status = 'Success';
                        }else{
                            $order_payment->payment_status = 'Not paid';
                        } 
                        $order_payment->amount_paid = $total_price_colleted; 
                        $order_payment->value_payment_mode = 'Reschdule Order Amount';
                        $order_payment->date_created= date('Y-m-d H:i:s');
                        $order_payment->date_modified= date('Y-m-d H:i:s');
                        $order_payment->save(false);
                        $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                        if(!empty($orderOffer)){
                            foreach ($orderOffer as $key => $offer) { 
                                $order_offer_item = new OrderOffers();
                                $order_offer_item->order_id=$model->id_order;
                                $order_offer_item->luggage_type =$offer['luggage_type'];
                                $order_offer_item->base_price=$offer['base_price'];
                                $order_offer_item->offer_price=$offer['offer_price'];
                                $order_offer_item->save(false);
                            }
                        }
                        $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                        if(!empty($orderGroup)){ 
                            foreach ($orderGroup as $key => $group) {
                                $order_group_offer= new OrderGroupOffer();
                                $order_group_offer->order_id=$model->id_order;
                                $order_group_offer->group_id=$group['group_id'];
                                $order_group_offer->subsequent_price=$group['subsequent_price'];
                                $order_group_offer->save(false);
                            }
                        }
                        $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()
                            ->where(['orderId'=>$id_order])
                            ->one();
                        if($outstation_charge){
                            $OrderZoneDetails = new \app\api_v3\v3\models\OrderZoneDetails();
                            $OrderZoneDetails->orderId = $model->id_order;
                            $OrderZoneDetails->outstationZoneId = $outstation_charge->outstationZoneId;
                            $OrderZoneDetails->cityZoneId = $outstation_charge->cityZoneId;
                            $OrderZoneDetails->stateId = $outstation_charge->stateId;
                            $OrderZoneDetails->extraKilometer = $outstation_charge->extraKilometer;
                            $OrderZoneDetails->taxAmount = $outstation_charge->taxAmount;
                            $OrderZoneDetails->outstationCharge = $outstation_charge->outstationCharge;
                            $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                            $OrderZoneDetails->save(false);
                        }
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                        $model->service_type = ($order_query->service_type == 1) ? 2 : 1;
                        $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 2) ? 3 : 2;
                        $model->order_status = ($order_query->service_type == 2) ? 'Open' : 'Confirmed';
                        $model->signature1 = $model->signature1;
                        $model->express_extra_amount = $order_query->express_extra_amount;
                        $model->signature2 = $model->signature2;
                        $model->save(false); 
                        $model1['order_details']=Order::getorderdetails($model->id_order);
                        if($_POST['payment_type'] == 'COD'){

                            /*for razor payment*/
                            $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $total_price_colleted, $model->id_order, $role_id);

                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                        }else{ 
                            /*for cash order confirmation*/
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                            User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                        }
                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);
     
                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/
                        if($order_query->corporate_type == 1){
                            Yii::$app->queue->push(new DelhiAirport([
                               'order_id' => $model->id_order,
                               'order_status' => 'confirmed'
                           ]));
                        }
                        if($_POST['Order']['readdress'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            /*$orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->save(false);*/
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress'] == 1) {
                            // $orderSpotDetails = OrderSpotDetails::find()
                            //                     ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                            //                     ->one();
                            // $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails = new OrderSpotDetails();
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                            }

     

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }
                            $orderSpotDetails->save(false);
                        }
                        $mallInvoice = MallInvoices::find()
                                       ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                       ->one() ;
                            if(!empty($mallInvoice)){
                                $mallInvoice->isNewRecord = true;
                                $mallInvoice->id_mall_invoices = null;
                                $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                $mallInvoice->save(false);
                        }
                        if($order_details['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                        }else{
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                        }
                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                        return $this->redirect(['order/index']);
                    }

                }else if ($_POST['Order']['hiddenServiceType'] == 2){ // FROM AIRPORT SCENARIO
                    if($_POST['Order']['rescheduleType'] == 0){
                        $order_query = Order::findOne($id_order); 
                        $model = new Order(
                                        $order_query->getAttributes() // get all attributes and copy them to the new instance
                                    );
                        $model->id_order = null; 
                        $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] : null;
                        $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) : null;
                        $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] : null;
                        $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) : null;
                        $model->flight_number = $_POST['Order']['flight_number'];
                        $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate'])); 
                        $insurance_price = $insurance;
                        $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                        if($order_undelivered){
                            $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                        }

                        if(isset($_POST['Order']['insurance_price'])){
                            if($_POST['Order']['insurance_price'] == 1){
                                $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $model->amount_paid  = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $totainsurance_price = $insurance + (.18 * $insurance) ;
                                $model->insurance_price = $totainsurance_price;
                            }else{
                               $model->insurance_price = $order_query['insurance_price'];
                            }
                        } 
                        //print_r($model->insurance_price);exit;
 
                        $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price)+ ($model->insurance_price);
                        $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->reschedule_luggage = 1;
                        $model->admin_edit_modified = 0;
                        $model->related_order_id = $id_order;
                        $model->fk_tbl_order_id_slot = 6;
                        $model->service_type = 2;
                        $model->no_of_units = $order_details['order']['luggage_count'];
                        $model->order_modified = 0;
                         $model->modified_amount = null;
                        $model->payment_method = $_POST['payment_type']; 
                        if($_POST['payment_type']=='COD'){
                            $model->amount_paid=0;
                        } 
                        if($model->save(false)){
                            $model = Order::findOne($model->id_order);
                            $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                            $model->fk_tbl_order_status_id_order_status = 2;
                            $model->express_extra_amount = $order_query->express_extra_amount;
                            $model->dservice_type = $_POST['Order']['dservice_type'];
                            $model->order_status = 'Confirmed';
                            $model->save(false);

                            $model1['order_details']=Order::getorderdetails($model->id_order);

                            $oItems = OrderItems::find()
                                        ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                        ->all();
                                if($oItems){
                                    foreach ($oItems as $items) {
                                        $order_items = new OrderItems();
                                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                        $order_items->barcode = $items['barcode'];
                                        $order_items->new_luggage = $items['new_luggage'];
                                        $order_items->deleted_status = $items['deleted_status'];
                                        $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                        $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                        $order_items->item_price = $items['item_price'];
                                        $order_items->bag_weight = $items['bag_weight'];
                                        $order_items->bag_type = $items['bag_type'];
                                        $order_items->item_weight = $items['item_weight'];
                                        $order_items->items_old_weight = $items['items_old_weight'];
                                        $order_items->save(false);
                                    }
                                }

                                $orderOffer = OrderOffers::find()
                                                        ->where(['order_id'=>$id_order])
                                                        ->all();
                                if(!empty($orderOffer)){

                                    foreach ($orderOffer as $key => $offer) {
                                        $order_offer_item = new OrderOffers();
                                        $order_offer_item->order_id=$model->id_order;
                                        $order_offer_item->luggage_type =$offer['luggage_type'];
                                        $order_offer_item->base_price=$offer['base_price'];
                                        $order_offer_item->offer_price=$offer['offer_price'];
                                        $order_offer_item->save(false);
                                    }
                                }  
                                $order_payment = new OrderPaymentDetails();
                                $order_payment->id_order = $model->id_order;
                                $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                                $order_payment->id_employee = Yii::$app->user->identity->id_employee;
                                if($_POST['payment_type'] == 'cash'){
                                    $order_payment->payment_status = 'Success';
                                }else{
                                    $order_payment->payment_status = 'Not paid';
                                }
                                $order_payment->amount_paid = $total_price_colleted;
                                $order_payment->value_payment_mode = 'Reschdule Order Amount';
                                $order_payment->date_created= date('Y-m-d H:i:s');
                                $order_payment->date_modified= date('Y-m-d H:i:s');
                                $order_payment->save(false);
                                // }
                                $orderGroup= OrderGroupOffer:: find()
                                                            ->where(['order_id'=>$id_order])
                                                            ->all();
                                if(!empty($orderGroup)){

                                    foreach ($orderGroup as $key => $group) {
                                        $order_group_offer= new OrderGroupOffer();
                                        $order_group_offer->order_id=$model->id_order;
                                        $order_group_offer->group_id=$group['group_id'];
                                        $order_group_offer->subsequent_price=$group['subsequent_price'];
                                        $order_group_offer->save(false);
                                    }
                                } 


                            if($_POST['payment_type'] == 'COD'){
                                $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                            }else{
                                /*for cash order confirmation*/
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                                User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                            }
                            if(!empty($_FILES['Order']['name']['ticket'])){
                                $up = $employee_model->actionFileupload('ticket',$model->id_order);
                            }

                            $order_query = Order::findOne($id_order);
                            $order_query->reschedule_luggage = 1;
                            $order_query->related_order_id = $model->id_order;
                            $order_query->fk_tbl_order_status_id_order_status = 20;
                            $order_query->order_status = 'Rescheduled';
                            $order_query->save(false);

                            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                            Order::updatestatus($id_order,20,'Rescheduled');

                            /*order total table*/
                            $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                            /*order total*/
                            if($order_query->corporate_type == 1){
                                Yii::$app->queue->push(new DelhiAirport([
                                   'order_id' => $model->id_order,
                                   'order_status' => 'confirmed'
                               ]));
                            }
                            //Records Insert into OrderSpotDetails;
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;



                            if($_POST['Order']['readdress1'] == 0){
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $newspotDet = new OrderSpotDetails();
                                $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                                $newspotDet->person_name = $orderSpotDetails->person_name;
                                $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                                $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                                $newspotDet->building_number = $orderSpotDetails->building_number;
                                $newspotDet->landmark = $orderSpotDetails->landmark;
                                $newspotDet->area = $orderSpotDetails->area;
                                $newspotDet->pincode = $orderSpotDetails->pincode;
                                $newspotDet->business_name = $orderSpotDetails->business_name;
                                $newspotDet->mall_name = $orderSpotDetails->mall_name;
                                $newspotDet->store_name = $orderSpotDetails->store_name;
                                $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                                $newspotDet->other_comments = $orderSpotDetails->other_comments;
                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                                $newspotDet->save(false);

                            }elseif ($_POST['Order']['readdress1'] == 1) { 
                                $orderSpotDetails = new OrderSpotDetails();
                                $orderSpotDetails->id_order_spot_details = null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                                $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                                $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                                $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                                $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                                $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                                $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                                $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                                $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                                $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                                $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                                $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                                $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null; 

                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                }

                                if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                                {
                                    $orderSpotDetails->assigned_person = 1;
                                }

                                $orderSpotDetails->save(false);
                            }


                            
                                
                                $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }
                                $orderDetailsRe= Order::getorderdetails($model->id_order);
                                $date_created = ($model->date_created) ? date("Y-m-d", strtotime($model->date_created)) : '';
                                $order_date = ($order_details['order_date']) ? date("Y-m-d", strtotime($order_details['order_date'])) : '';
                                $slot_start_time = date('h:i a', strtotime($orderDetailsRe['order']['slot_start_time']));
                                $slot_end_time = date('h:i a', strtotime($orderDetailsRe['order']['slot_end_time']));
                                $slot_scehdule = $slot_start_time.' To '.$slot_end_time;
                                $servive = ' To Airport ';
                                $customerName = ($orderDetailsRe['order']['customer_name']) ? $orderDetailsRe['order']['customer_name'] : '';
                                $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$model->order_number.$servive.'placed on '.$date_created.' by '.$customerName.' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';

                                   $orderContactDetails = \app\api_v3\v3\models\OrderMetaDetails::find()->where(['orderId'=>$model->id_order])->one();
                                    $customer_number = $orderDetailsRe['order']['c_country_code'].$orderDetailsRe['order']['customer_mobile'];
        
                                    $traveller_number = $orderDetailsRe['order']['c_country_code'].$orderContactDetails->pickupPersonNumber;
                                    $location_contact = $orderDetailsRe['order']['c_country_code'].$orderContactDetails->dropPersonNumber;
                                     
                                    //echo"<pr>";print_r($customer_number);exit; 
                                   //print_r($customer_number);exit;
                                    if ($customer_number == $traveller_number){
                                        if($traveller_number == $location_contact){
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                        }else{
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                          User::sendsms($location_contact,$bookingCustomer_smsContent);
                                        }
                                            
                                      }else{
                                        if($traveller_number == $location_contact){
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                          User::sendsms($traveller_number,$bookingCustomer_smsContent);
                                        }else{
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                          User::sendsms($traveller_number,$bookingCustomer_smsContent);
                                          User::sendsms($location_contact,$bookingCustomer_smsContent);
                                        }

                                      }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                            
                            
                        }
                    }elseif ($_POST['Order']['rescheduleType'] == 1) {

                        $order_query = Order::findOne($id_order); 
                        $model = new Order(
                                        $order_query->getAttributes() // get all attributes and copy them to the new instance
                                    );
                        $model->id_order = null;
                        $model->reschedule_luggage = 1; 
                        $model->departure_date = null;
                        $model->departure_time = null;
                        $model->arrival_date = null;
                        $model->arrival_time = null;
                        $model->flight_number = null;
                        $model->meet_time_gate = null; 
                        $insurance_price = $insurance;
                        $model->insurance_price = $insurance + $insurance * .18; //18% of insurance amount is total insurance
                        if($order_undelivered){
                            $model->insurance_price = (isset($_POST['if_insurance'])) ? $_POST['if_insurance'] : 0;
                        }

                        if(isset($_POST['Order']['insurance_price'])){
                            if($_POST['Order']['insurance_price'] == 1){
                                $model->luggage_price =  $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $model->amount_paid   = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100);
                                $totainsurance_price = $insurance + (.18 * $insurance) ;
                                $model->insurance_price = $totainsurance_price;
                            }else{
                               $model->insurance_price = $order_query['insurance_price'];
                            }
                        }
                        $model->luggage_price = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->amount_paid = ($_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100)) - ($order_query->insurance_price) + ($model->insurance_price);
                        $model->service_type = 2;
                        $model->admin_edit_modified = 0;
                        $model->related_order_id = $id_order;
                        $model->fk_tbl_order_id_slot = 4;
                        $model->no_of_units = $order_details['order']['luggage_count'];
                        $model->order_modified = 0;
                        $model->modified_amount = null;
                        $model->payment_method = $_POST['payment_type']; 
                        if($_POST['payment_type']=='COD'){
                            $model->amount_paid=0;
                        } 
                        if($model->save(false)){
                            $model = Order::findOne($model->id_order);
                            $model->order_number = 'ON'.date('mdYHis').$model->id_order;

                            $model->express_extra_amount = $order_query->express_extra_amount;
                            $model->fk_tbl_order_status_id_order_status =  2;
                            $model->order_status = 'Confirmed';
                            $model->dservice_type = $_POST['Order']['dservice_type'];
                            $model->save(false);
  
                            /*new email Integration*/
                            $model1['order_details']=Order::getorderdetails($model->id_order);


                            // Records Insert into OrderItems;
                                $oItems = OrderItems::find()
                                        ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                        ->all();
                                if($oItems){
                                    foreach ($oItems as $items) {
                                        $order_items = new OrderItems();
                                        $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                        $order_items->barcode = $items['barcode'];
                                        $order_items->new_luggage = $items['new_luggage'];
                                        $order_items->deleted_status = $items['deleted_status'];
                                        $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                        $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                        $order_items->fk_tbl_order_items_id_weight_range = $items['fk_tbl_order_items_id_weight_range'];
                                        $order_items->item_price = $items['item_price'];
                                        $order_items->bag_weight = $items['bag_weight'];
                                        $order_items->bag_type = $items['bag_type'];
                                        $order_items->item_weight = $items['item_weight'];
                                        $order_items->items_old_weight = $items['items_old_weight'];
                                        $order_items->save(false);
                                    }
                                }

                                $orderOffer = OrderOffers::find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                                if(!empty($orderOffer)){

                                    foreach ($orderOffer as $key => $offer) {
                                        $order_offer_item = new OrderOffers();
                                        $order_offer_item->order_id=$model->id_order;
                                        $order_offer_item->luggage_type =$offer['luggage_type'];
                                        $order_offer_item->base_price=$offer['base_price'];
                                        $order_offer_item->offer_price=$offer['offer_price'];
                                        $order_offer_item->save(false);
                                    }
                                } 

                                $order_payment = new OrderPaymentDetails();
                                $order_payment->id_order = $model->id_order;
                                $order_payment->payment_type = (isset($_POST['payment_type'])) ? $_POST['payment_type'] : '';
                                $order_payment->id_employee = Yii::$app->user->identity->id_employee;

                                if($_POST['payment_type'] == 'cash'){
                                    $order_payment->payment_status = 'Success';
                                }else{
                                    $order_payment->payment_status = 'Not paid';
                                }
                                $order_payment->amount_paid = $total_price_colleted;
                                $order_payment->value_payment_mode = 'Reschdule Order Amount';
                                $order_payment->date_created= date('Y-m-d H:i:s');
                                $order_payment->date_modified= date('Y-m-d H:i:s');
                                $order_payment->save(false);
                        
                                $orderGroup= OrderGroupOffer:: find()
                                                            ->where(['order_id'=>$id_order])
                                                            ->all();
                                if(!empty($orderGroup)){

                                    foreach ($orderGroup as $key => $group) {
                                        $order_group_offer= new OrderGroupOffer();
                                        $order_group_offer->order_id=$model->id_order;
                                        $order_group_offer->group_id=$group['group_id'];
                                        $order_group_offer->subsequent_price=$group['subsequent_price'];
                                        $order_group_offer->save(false);
                                    }
                                } 


                            if($_POST['payment_type'] == 'COD'){
                                $razorpay = Yii::$app->Common->createRazorpayLink($order_details['order']['customer_email'], $order_details['order']['travell_passenger_contact'], $_POST['old_price'], $model->id_order, $role_id);

                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
                            }else{
                                /*for cash order confirmation*/
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
                                $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                User::sendEmailExpressMultipleAttachment($order_details['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);

                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'reschedule_order_payments_pdf_template');
                                User::sendemailexpressattachment($order_details['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                            }

                            $order_query = Order::findOne($id_order);
                            $order_query->reschedule_luggage = 1;
                            $order_query->related_order_id = $model->id_order;
                            $order_query->fk_tbl_order_status_id_order_status = 20;
                            $order_query->order_status = 'Rescheduled';
                            $order_query->save(false);

                            Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                            Order::updatestatus($id_order,20,'Rescheduled');

                            /*order total table*/
                            $this->updateordertotal($model->id_order, $_POST['Order']['totalPrice'], $order_query->service_tax_amount, $model->insurance_price);
                            /*order total*/
                            if($order_query->corporate_type == 1){
                                Yii::$app->queue->push(new DelhiAirport([
                                   'order_id' => $model->id_order,
                                   'order_status' => 'confirmed'
                               ]));
                            }

                            //Records Insert into OrderSpotDetails;
                            if($_POST['Order']['readdress1'] == 0){
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                $newspotDet = new OrderSpotDetails();
                                $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                                $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                                $newspotDet->person_name = $orderSpotDetails->person_name;
                                $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                                $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                                $newspotDet->building_number = $orderSpotDetails->building_number;
                                $newspotDet->landmark = $orderSpotDetails->landmark;
                                $newspotDet->area = $orderSpotDetails->area;
                                $newspotDet->pincode = $orderSpotDetails->pincode;
                                $newspotDet->business_name = $orderSpotDetails->business_name;
                                $newspotDet->mall_name = $orderSpotDetails->mall_name;
                                $newspotDet->store_name = $orderSpotDetails->store_name;
                                $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                                $newspotDet->other_comments = $orderSpotDetails->other_comments;
                                if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                        $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                        $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                                }
                                $newspotDet->save(false);

                            }elseif ($_POST['Order']['readdress1'] == 1) {
                                $orderSpotDetails = OrderSpotDetails::find()
                                                    ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                    ->one();
                                // $orderSpotDetails->isNewRecord = true;
                                $orderSpotDetails->id_order_spot_details = null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                                $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                                $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                                $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                                $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                                $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                                $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                                $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                                $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                                $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                                $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                                $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                                $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                                $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null; 
                                if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                        $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                        $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                }

                                if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                                {
                                    $orderSpotDetails->assigned_person = 1;
                                }

                                $orderSpotDetails->save(false);
                            }

                            
                            if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                            }
                            
                            if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                                $up = $employee_model->actionFileupload('invoice',$model->id_order);
                                if($order_details['order']['order_transfer']==1){
                                    $sms_content = Yii::$app->Common->generateCityTransferSms($model->id_order, 'OrderConfirmation', '');
                                }else{
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                }
                                $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                return $this->redirect(['order/index']);
                            }else{
                                $mallInvoice = MallInvoices::find()
                                           ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                           ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }

                                //if($order_details['order']['order_transfer']==1){
                                $orderDetailsRe= Order::getorderdetails($model->id_order);
                                $date_created = ($model->date_created) ? date("Y-m-d", strtotime($model->date_created)) : '';
                                $order_date = ($order_details['order_date']) ? date("Y-m-d", strtotime($order_details['order_date'])) : '';
                                $slot_start_time = date('h:i a', strtotime($orderDetailsRe['order']['slot_start_time']));
                                $slot_end_time = date('h:i a', strtotime($orderDetailsRe['order']['slot_end_time']));
                                $slot_scehdule = $slot_start_time.' To '.$slot_end_time;
                                $servive = ' To City ';
                                $customerName = ($orderDetailsRe['order']['customer_name']) ? $orderDetailsRe['order']['customer_name'] : '';
                                $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$model->order_number.$servive.'placed on '.$date_created.' by '.$customerName.' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';

                                   $orderContactDetails = \app\api_v3\v3\models\OrderMetaDetails::find()->where(['orderId'=>$model->id_order])->one();
                                    $customer_number = $orderDetailsRe['order']['c_country_code'].$orderDetailsRe['order']['customer_mobile'];
        
                                    $traveller_number = $orderDetailsRe['order']['c_country_code'].$orderContactDetails->pickupPersonNumber;
                                    $location_contact = $orderDetailsRe['order']['c_country_code'].$orderContactDetails->dropPersonNumber;
                                     
                                    //echo"<pr>";print_r($customer_number);exit; 
                                   //print_r($customer_number);exit;
                                    if ($customer_number == $traveller_number){
                                        if($traveller_number == $location_contact){
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                        }else{
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                          User::sendsms($location_contact,$bookingCustomer_smsContent);
                                        }
                                            
                                      }else{
                                        if($traveller_number == $location_contact){
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                          User::sendsms($traveller_number,$bookingCustomer_smsContent);
                                        }else{
                                          User::sendsms($customer_number,$bookingCustomer_smsContent);
                                          User::sendsms($traveller_number,$bookingCustomer_smsContent);
                                          User::sendsms($location_contact,$bookingCustomer_smsContent);
                                        }

                                      }
                                // }else{
                                //     $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                                // }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                                
                            }
                            
                        }
                    }
                }
 
             
            }
            // *************** RESCHEDULE CODE FOR THIRD PARTY ORDER ENDS HERE ********************
           // print_r($_POST['Order']['hiddenServiceType']);exit;
        }else{
            $luggage_price = LuggageType::find()->where(['corporate_id'=>$order_details['order']['corporate_id']])->one();
            $order_details['order']['no_of_units'] = $order_details['order']['luggage_count'];
            $order_details['order']['totalPrice'] = $order_details['order']['luggage_count'] * $luggage_price['base_price'];
            //print_r($luggage_price);exit;
            return $this->render('reschedule_corporate_update', [
                'order_details'=>$order_details,'model'=>$model, 'order_promocode' => $order_promocode]);
        }

    }
  
    /*
        Reschdule Order for kiosk
    */
    public function actionReCorporateOrderKiosk($id_order){
        $model['o'] = new Order();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $employee_model = new Employee();
        $order_details = Order::getorderdetails($id_order);
        $order_undelivered = Order::getIsundelivered($id_order);

        if (!empty($_POST)) { //echo "<pre>";print_r($_POST);exit;
           // $this->CorporateReschduleMailSms(1404,1412);
            if($_POST['Order']['hiddenServiceType'] == 1){
                $order_query = Order::findOne($id_order);
                $model = new Order( $order_query->getAttributes());
                $model->id_order = null;
                $model->order_date = date('Y-m-d',strtotime($_POST['Order']['order_date']));  //$_POST['Order']['order_date'];
                $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
                $model->insurance_price = 0;

                if($_POST['Order']['fk_tbl_order_id_slot'] == 4)
                {
                    $model->arrival_time = date('H:i:s', strtotime('03:00 PM'));
                    $model->meet_time_gate = date('H:i:s', strtotime('03:30 PM'));
                }else if($_POST['Order']['fk_tbl_order_id_slot'] == 5){
                    $model->arrival_time = date('H:i:s', strtotime('11:55 PM'));
                    $model->meet_time_gate = date('H:i:s', strtotime('12:25 AM'));
                }
                $model->dservice_type = $_POST['Order']['dservice_type'];

                $insurance_price = 4 * $order_details['order']['luggage_count'];
                $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                if($order_undelivered){
                    $model->insurance_price = $model->insurance_price + $model->insurance_price;
                }

                if(isset($_POST['Order']['insurance_price'])){
                    if($_POST['Order']['insurance_price'] == 1){
                        $model->luggage_price =  $model->luggage_price;
                    }else{
                       $model->insurance_price = $order_query['insurance_price'];
                    }
                }
                $model->luggage_price = ($order_query->luggage_price) - ($order_query->insurance_price) + ($model->insurance_price);

                $model->reschedule_luggage = 1;
                $model->related_order_id = $id_order;
                $model->no_of_units = $order_details['order']['luggage_count'];

                if($model->save(false)){
                    $model = Order::findOne($model->id_order);
                    $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                    //$model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                    //$model->order_status = ($_POST['Order']['service_type'] == 1) ? 'Open' : 'Confirmed';
                    $model->service_type = ($order_query->service_type == 1) ? 2 : 1;
                    $model->fk_tbl_order_status_id_order_status = ($order_query->service_type == 2) ? 3 : 2;
                    $model->order_status = ($order_query->service_type == 2) ? 'Open' : 'Confirmed';
                    $model->signature1 = $model->signature1;
                    $model->signature2 = $model->signature2;
                    $model->save(false);

                    $order_query = Order::findOne($id_order);
                    $order_query->reschedule_luggage = 1;
                    $order_query->related_order_id = $model->id_order;
                    $order_query->fk_tbl_order_status_id_order_status = 20;
                    $order_query->order_status = 'Rescheduled';
                    $order_query->save(false);

                    Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                    Order::updatestatus($id_order,20,'Rescheduled');

                    /*order total table*/
                    $this->updateordertotal($model->id_order, $model->luggage_price, $order_query->service_tax_amount, $model->insurance_price);
                    /*order total*/

                    if($_POST['Order']['readdress'] == 0){
                        $orderSpotDetails = OrderSpotDetails::find()
                                            ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                            ->one();
                        /*$orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $orderSpotDetails->save(false);*/
                        $newspotDet = new OrderSpotDetails();
                        $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                        $newspotDet->person_name = $orderSpotDetails->person_name;
                        $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                        $newspotDet->area = $orderSpotDetails->area;
                        $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                        $newspotDet->building_number = $orderSpotDetails->building_number;
                        $newspotDet->landmark = $orderSpotDetails->landmark;
                        $newspotDet->pincode = $orderSpotDetails->pincode;
                        $newspotDet->business_name = $orderSpotDetails->business_name;
                        $newspotDet->mall_name = $orderSpotDetails->mall_name;
                        $newspotDet->store_name = $orderSpotDetails->store_name;
                        $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                        $newspotDet->other_comments = $orderSpotDetails->other_comments;
                        if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                        $newspotDet->save(false);

                    }elseif ($_POST['Order']['readdress'] == 1) {
                        // $orderSpotDetails = OrderSpotDetails::find()
                        //                     ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                        //                     ->one();
                        // $orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails = new OrderSpotDetails();
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                        $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                        $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                        $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                        $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                        $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                        $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                        $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                        $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                        $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                        $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                        $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                        $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                        if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                        }



                        if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                        {
                            $orderSpotDetails->assigned_person = 1;
                        }
                        $orderSpotDetails->save(false);
                    }
                    $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                    if(!empty($oItems)){
                        foreach ($oItems as $items) {
                            $order_items = new OrderItems();
                            $order_items->fk_tbl_order_items_id_order = $model->id_order;
                            $order_items->barcode = $items['barcode'];
                            $order_items->new_luggage = $items['new_luggage'];
                            $order_items->deleted_status = $items['deleted_status'];
                            $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                            $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                            $order_items->item_price = $items['item_price'];
                              $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                            $order_items->save();
                        }
                    }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save();
                        }
                    }

                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_oredr;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save();
                        }
                    }

                    $orderPromocode=OrderPromoCode::find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderPromocode)){

                        foreach ($orderPromocode as $key => $promocode) {
                            $order_promocode=new OrderPromoCode();
                            $order_promocode->id_order=$model->id_order;
                            $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                            $order_promocode->promocode_text=$promocode['promocode_text'];
                            $order_promocode->promocode_value=$promocode['promocode_value'];
                            $order_promocode->promocode_type=$promocode['promocode_type'];
                            $order_promocode->save();
                        }
                    }

                    /*$oItems->isNewRecord = true;
                    $oItems->id_order_item = null;
                    $oItems->fk_tbl_order_items_id_order = $model->id_order;*/
                    //if($oItems->save(false)){
                        $mallInvoice = MallInvoices::find()
                                       ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                       ->one() ;
                            if(!empty($mallInvoice)){
                                $mallInvoice->isNewRecord = true;
                                $mallInvoice->id_mall_invoices = null;
                                $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                $mallInvoice->save(false);
                        }
                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                   // }
                }
            }else if ($_POST['Order']['hiddenServiceType'] == 2) {
                if($_POST['Order']['rescheduleType'] == 0){
                    $order_query = Order::findOne($id_order);
                    //$model->isNewRecord = true;
                    $model = new Order(
                                    $order_query->getAttributes() // get all attributes and copy them to the new instance
                                );
                    $model->id_order = null;
                    //$departureDetails = explode(' ', $_POST['Order']['departure_date']);
                    $model->departure_date = isset($_POST['Order']['departure_date']) ? $_POST['Order']['departure_date'] : null;
                    $model->departure_time = isset($_POST['Order']['departure_time']) ? date("H:i", strtotime($_POST['Order']['departure_time'])) : null;
                    $model->arrival_date = isset($_POST['Order']['arrival_date']) ? $_POST['Order']['arrival_date'] : null;
                    $model->arrival_time = isset($_POST['Order']['arrival_time']) ? date("H:i", strtotime($_POST['Order']['arrival_time'])) : null;
                    $model->flight_number = $_POST['Order']['flight_number'];
                    $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
                    //$model->luggage_price = $order_query->luggage_price;

                    $insurance_price = 4 * $order_details['order']['luggage_count'];
                    $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = $model->insurance_price + $model->insurance_price;
                    }

                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $model->luggage_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }

                    $model->luggage_price = ($order_query->luggage_price) - ($order_query->insurance_price) + ($model->insurance_price);

                    $model->reschedule_luggage = 1;
                    $model->related_order_id = $id_order;
                    $model->fk_tbl_order_id_slot = 6;
                    $model->service_type = 2;
                    $model->no_of_units = $order_details['order']['luggage_count'];

                    if($model->save(false)){
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;
                        $model->fk_tbl_order_status_id_order_status = 2;
                        $model->order_status = 'Confirmed';
                        $model->save(false);

                        if(!empty($_FILES['Order']['name']['ticket'])){
                            $up = $employee_model->actionFileupload('ticket',$model->id_order);
                        }

                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);

                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $model->luggage_price, $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/

                        //Records Insert into OrderSpotDetails;
                        $orderSpotDetails = OrderSpotDetails::find()
                                            ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                            ->one();
                        $orderSpotDetails->isNewRecord = true;
                        $orderSpotDetails->id_order_spot_details = null;
                        $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;



                        if($_POST['Order']['readdress1'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress1'] == 1) {
                            /*$orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;*/
                            $orderSpotDetails = new OrderSpotDetails();
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'])?$_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type']:null;
                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;
                            //$orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;

                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                                $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                                $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                            }

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }

                            $orderSpotDetails->save(false);
                        }


                        if($orderSpotDetails->save(false)){
                            // Records Insert into OrderItems;
                            $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                            if(!empty($oItems)){
                                foreach ($oItems as $items) {
                                    $order_items = new OrderItems();
                                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                    $order_items->barcode = $items['barcode'];
                                    $order_items->new_luggage = $items['new_luggage'];
                                    $order_items->deleted_status = $items['deleted_status'];
                                    $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                    $order_items->item_price = $items['item_price'];
                                 $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                            $order_items->save();
                        }
                    }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save();
                        }
                    }

                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_oredr;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save();
                        }
                    }

                    $orderPromocode=OrderPromoCode::find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderPromocode)){

                        foreach ($orderPromocode as $key => $promocode) {
                            $order_promocode=new OrderPromoCode();
                            $order_promocode->id_order=$model->id_order;
                            $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                            $order_promocode->promocode_text=$promocode['promocode_text'];
                            $order_promocode->promocode_value=$promocode['promocode_value'];
                            $order_promocode->promocode_type=$promocode['promocode_type'];
                            $order_promocode->save();
                        }
                    }
                            //if($oItems->save()){
                                $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                if(!empty($mallInvoice)){
                                    $mallInvoice->isNewRecord = true;
                                    $mallInvoice->id_mall_invoices = null;
                                    $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                    $mallInvoice->save(false);
                                }
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                            //}
                        }
                    }
                }elseif ($_POST['Order']['rescheduleType'] == 1) {
                     $order_query = Order::findOne($id_order);
                    //$model->isNewRecord = true;
                    $model = new Order(
                                    $order_query->getAttributes() // get all attributes and copy them to the new instance
                                );
                    $model->id_order = null;
                    $model->reschedule_luggage = 1;
                    //$model->luggage_price = $order_query->luggage_price;
                    $model->departure_date = null;
                    $model->departure_time = null;
                    $model->arrival_date = null;
                    $model->arrival_time = null;
                    $model->flight_number = null;
                    $model->meet_time_gate = null;
                    $insurance_price = 4 * $order_details['order']['luggage_count'];
                    $model->insurance_price = $insurance_price + (.18 * $insurance_price) ; //18% of insurance amount is total insurance
                    if($order_undelivered){
                        $model->insurance_price = $model->insurance_price + $model->insurance_price;
                    }

                    if(isset($_POST['Order']['insurance_price'])){
                        if($_POST['Order']['insurance_price'] == 1){
                            $model->luggage_price =  $model->luggage_price;
                        }else{
                           $model->insurance_price = $order_query['insurance_price'];
                        }
                    }
                    $model->luggage_price = ($order_query->luggage_price) - ($order_query->insurance_price) + ($model->insurance_price);

                    $model->service_type = 2 ;
                    $model->related_order_id = $id_order;
                    $model->fk_tbl_order_id_slot = 4;
                    $model->no_of_units = $order_details['order']['luggage_count'];

                    if($model->save(false)){
                        $model = Order::findOne($model->id_order);
                        $model->order_number = 'ON'.date('mdYHis').$model->id_order;

                        $model->fk_tbl_order_status_id_order_status =  2;
                        $model->order_status = 'Confirmed';

                        $model->save(false);
                        $order_query = Order::findOne($id_order);
                        $order_query->reschedule_luggage = 1;
                        $order_query->related_order_id = $model->id_order;
                        $order_query->fk_tbl_order_status_id_order_status = 20;
                        $order_query->order_status = 'Rescheduled';
                        $order_query->save(false);

                        Order::updatestatus($model->id_order, $model->fk_tbl_order_status_id_order_status, $model->order_status);
                        Order::updatestatus($id_order,20,'Rescheduled');

                        /*order total table*/
                        $this->updateordertotal($model->id_order, $model->luggage_price, $order_query->service_tax_amount, $model->insurance_price);
                        /*order total*/


                        //Records Insert into OrderSpotDetails;
                        if($_POST['Order']['readdress1'] == 0){
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $newspotDet = new OrderSpotDetails();
                            $newspotDet->fk_tbl_order_spot_details_id_order = $model->id_order;
                            $newspotDet->fk_tbl_order_spot_details_id_pick_drop_spots_type = $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type;
                            $newspotDet->person_name = $orderSpotDetails->person_name;
                            $newspotDet->person_mobile_number = $orderSpotDetails->person_mobile_number;
                            $newspotDet->address_line_1 = $orderSpotDetails->address_line_1;
                            $newspotDet->building_number = $orderSpotDetails->building_number;
                            $newspotDet->landmark = $orderSpotDetails->landmark;
                            $newspotDet->area = $orderSpotDetails->area;
                            $newspotDet->pincode = $orderSpotDetails->pincode;
                            $newspotDet->business_name = $orderSpotDetails->business_name;
                            $newspotDet->mall_name = $orderSpotDetails->mall_name;
                            $newspotDet->store_name = $orderSpotDetails->store_name;
                            $newspotDet->building_restriction = $orderSpotDetails->building_restriction;
                            $newspotDet->other_comments = $orderSpotDetails->other_comments;
                            if($orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type == 2){
                                    $newspotDet->fk_tbl_order_spot_details_id_contact_person_hotel = $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel;
                                    $newspotDet->hotel_name = $orderSpotDetails->hotel_name;
                            }
                            $newspotDet->save(false);

                        }elseif ($_POST['Order']['readdress1'] == 1) {
                            $orderSpotDetails = OrderSpotDetails::find()
                                                ->where(['fk_tbl_order_spot_details_id_order'=>$id_order])
                                                ->one();
                            $orderSpotDetails->isNewRecord = true;
                            $orderSpotDetails->id_order_spot_details = null;
                            $orderSpotDetails->fk_tbl_order_spot_details_id_order = $model->id_order;

                            $orderSpotDetails->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                            $orderSpotDetails->person_name = isset($_POST['OrderSpotDetails']['person_name'])?$_POST['OrderSpotDetails']['person_name']:null;
                            $orderSpotDetails->person_mobile_number = isset($_POST['OrderSpotDetails']['person_mobile_number'])?$_POST['OrderSpotDetails']['person_mobile_number']:null;
                            $orderSpotDetails->mall_name = isset($_POST['OrderSpotDetails']['mall_name'])?$_POST['OrderSpotDetails']['mall_name']:null;
                            $orderSpotDetails->store_name = isset($_POST['OrderSpotDetails']['store_name'])?$_POST['OrderSpotDetails']['store_name']:null;
                            $orderSpotDetails->business_name = isset($_POST['OrderSpotDetails']['business_name'])?$_POST['OrderSpotDetails']['business_name']:null;

                            $orderSpotDetails->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                            $orderSpotDetails->building_number = isset($_POST['OrderSpotDetails']['building_number'])?$_POST['OrderSpotDetails']['building_number']:null;
                            $orderSpotDetails->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                            $orderSpotDetails->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                            $orderSpotDetails->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                            $orderSpotDetails->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                            $orderSpotDetails->building_restriction = isset($_POST['building_restriction']) ? serialize($_POST['building_restriction']) : null;
                            //$orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel']) ? $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'] : null ;
                            if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                                    $orderSpotDetails->fk_tbl_order_spot_details_id_contact_person_hotel = isset($_POST['OrderSpotDetails']['hotel_type']) ? $_POST['OrderSpotDetails']['hotel_type'] : null;
                                    $orderSpotDetails->hotel_name = isset($_POST['OrderSpotDetails']['hotel_name']) ? $_POST['OrderSpotDetails']['hotel_name'] : null;
                            }

                            if($order_query->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                            {
                                $orderSpotDetails->assigned_person = 1;
                            }
                        }

                        if($orderSpotDetails->save(false)){
                            if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                                $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                            }
                            // Records Insert into OrderItems;
                            $oItems = OrderItems::find()
                                    ->where(['fk_tbl_order_items_id_order'=>$id_order])
                                    ->all();
                            if(!empty($oItems)){
                                foreach ($oItems as $items) {
                                    $order_items = new OrderItems();
                                    $order_items->fk_tbl_order_items_id_order = $model->id_order;
                                    $order_items->barcode = $items['barcode'];
                                    $order_items->new_luggage = $items['new_luggage'];
                                    $order_items->deleted_status = $items['deleted_status'];
                                    $order_items->fk_tbl_order_items_id_barcode = $items['fk_tbl_order_items_id_barcode'];
                                    $order_items->fk_tbl_order_items_id_luggage_type = $items['fk_tbl_order_items_id_luggage_type'];
                                    $order_items->item_price = $items['item_price'];
                                    $order_items->bag_weight = $items['bag_weight'];
                                $order_items->bag_type = $items['bag_type'];
                            $order_items->save();
                        }
                    }

                    $orderOffer = OrderOffers::find()
                                            ->where(['order_id'=>$id_order])
                                            ->all();
                    if(!empty($orderOffer)){

                        foreach ($orderOffer as $key => $offer) {
                            $order_offer_item = new OrderOffers();
                            $order_offer_item->order_id=$model->id_order;
                            $order_offer_item->luggage_type =$offer['luggage_type'];
                            $order_offer_item->base_price=$offer['base_price'];
                            $order_offer_item->offer_price=$offer['offer_price'];
                            $order_offer_item->save();
                        }
                    }


                    $orderGroup= OrderGroupOffer:: find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderGroup)){

                        foreach ($orderGroup as $key => $group) {
                            $order_group_offer= new OrderGroupOffer();
                            $order_group_offer->order_id=$model->id_oredr;
                            $order_group_offer->group_id=$group['group_id'];
                            $order_group_offer->subsequent_price=$group['subsequent_price'];
                            $order_group_offer->save();
                        }
                    }

                    $orderPromocode=OrderPromoCode::find()
                                                ->where(['order_id'=>$id_order])
                                                ->all();
                    if(!empty($orderPromocode)){

                        foreach ($orderPromocode as $key => $promocode) {
                            $order_promocode=new OrderPromoCode();
                            $order_promocode->id_order=$model->id_order;
                            $order_promocode->fk_tbl_order_promocode_id_customer=$promocode['fk_tbl_order_promocode_id_customer'];
                            $order_promocode->promocode_text=$promocode['promocode_text'];
                            $order_promocode->promocode_value=$promocode['promocode_value'];
                            $order_promocode->promocode_type=$promocode['promocode_type'];
                            $order_promocode->save();
                        }
                    }

                            //if($oItems->save()){
                                 if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                                    $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                    return $this->redirect(['order/index']);
                                }else{
                                    $mallInvoice = MallInvoices::find()
                                               ->where(['fk_tbl_mall_invoices_id_order'=>$id_order])
                                               ->one() ;
                                    if(!empty($mallInvoice)){
                                        $mallInvoice->isNewRecord = true;
                                        $mallInvoice->id_mall_invoices = null;
                                        $mallInvoice->fk_tbl_mall_invoices_id_order = $model->id_order;
                                        $mallInvoice->save(false);
                                    }
                                        $this->CorporateReschduleMailSms($order_query->id_order,$model->id_order);
                                        return $this->redirect(['order/index']);
                               // }
                            }
                        }
                    }
                }
            }
        }else{
            $luggage_price = LuggageType::find()->where(['corporate_id'=>$order_details['order']['corporate_id']])->one();
            $order_details['order']['no_of_units'] = $order_details['order']['luggage_count'];
            $order_details['order']['totalPrice'] = $order_details['order']['luggage_count'] * $luggage_price['base_price'];
            //print_r($luggage_price);exit;
            return $this->render('reschedule_corporate_update_kiosk', [
                'order_details'=>$order_details,'model'=>$model]);
        }
    }

    function CorporateReschduleMailSms($old_id_order,$new_id_order){ 

        $model = Order::findOne($new_id_order);
        $modelOld = Order::findOne($old_id_order);
        $osd = OrderSpotDetails::find()
                        ->where(['fk_tbl_order_spot_details_id_order'=>$new_id_order])
                        ->one();
        $corporatr = \app\models\CorporateDetails::find()
                        ->where(['corporate_detail_id'=>$model->corporate_id])
                        ->one();
        $customer = \app\models\Customer::find()
                        ->where(['id_customer'=>$model->fk_tbl_order_id_customer])
                        ->one();
        $corporate_name = ($corporatr) ? $corporatr->name : '';
        $corporate_email = ($corporatr) ? $corporatr->default_email : $customer->email;
       //echo $osd->address_line_1;exit;
        //echo $corporatr->name;exit;
        $new_fn = " ".$model->flight_number;
        $old_fn = " ".$modelOld->flight_number;

        $location = ($model->fk_tbl_order_id_slot == 1 || $model->fk_tbl_order_id_slot == 2 || $model->fk_tbl_order_id_slot == 3 || $model->fk_tbl_order_id_slot == 6 ) ? ' the airport' : $osd->address_line_1;
        // $msg_to_airport_voluntary = "Dear Customer, Your Order #".$modelOld->order_number.$old_fn." has been rescheduled to # ".$model->order_number.$new_fn." which is confirmed for service to ".$location." on ".date("F j, Y", strtotime($model->order_date)).".".PHP_EOL." Thanks carterx.in";

        $msg_to_airport_voluntary ="Dear Customer, your Order #".$modelOld->order_number." has been rescheduled to #".$model->order_number." which is confirmed for service to ".$location.".".PHP_EOL." Thanks carterx.in";

        // $msg_to_airport_voluntary_other = "Hello, Order #".$modelOld->order_number.$old_fn." has been rescheduled by ".$corporate_name." to # ".$model->order_number.$new_fn." which is confirmed for service to ".$location." on ".date("F j, Y", strtotime($model->order_date)).".".PHP_EOL." Thanks carterx.in";

        $msg_to_airport_voluntary_other = "Dear Customer, your Order #".$modelOld->order_number." placed by ".$corporate_name." has been rescheduled to #".$model->order_number." which is confirmed for service to ".$location.".".PHP_EOL." Thanks carterx.in";

        $msg_from_airport_voluntary = "Dear Customer, Your Order #".$modelOld->order_number.$old_fn." has been rescheduled to # ".$model->order_number.$new_fn." which is confirmed for service to ".$location.".".PHP_EOL." Thanks carterx.in";

        $msg_from_airport_voluntary_other = "Hello, Order #".$modelOld->order_number.$old_fn." has been rescheduled by ".$corporate_name." to # ".$model->order_number.$new_fn." which is confirmed for service to ".$location.".".PHP_EOL." Thanks carterx.in";

        // $msg_to_airport_forced = "Dear Customer, your Order #".$modelOld->order_number.$old_fn." has been FORCED rescheduled to #".$model->order_number.$new_fn." which is confirmed for service to ".$location." on ".date("F j, Y", strtotime($model->order_date)).".".PHP_EOL.". Thanks carterx.in";
        $msg_to_airport_forced = "Dear Customer, your Order #".$modelOld->order_number." has been FORCED rescheduled to #".$model->order_number." which is confirmed for service to ".$location.".".PHP_EOL." Thanks carterx.in";
 
        // $msg_to_airport_forced_other = "Hello, Order #".$modelOld->order_number.$old_fn." placed by ".$corporate_name." has been FORCED rescheduled to #".$model->order_number.$new_fn." which is confirmed for service to ".$location." on ".date("F j, Y", strtotime($model->order_date)).".".PHP_EOL.". Thanks carterx.in";
        $msg_to_airport_forced_other = "Dear Customer, your Order #".$modelOld->order_number." placed by ".$corporate_name." has been FORCED rescheduled to #".$model->order_number." which is confirmed for service to ".$location.".".PHP_EOL." Thanks carterx.in";

        $msg_from_airport_forced = $msg_to_airport_forced;
        $msg_from_airport_forced_other = $msg_to_airport_forced_other;

        $order_undelivered = Order::getIsundelivered($modelOld->id_order);
        $reschedule_sms_from_airport = ($order_undelivered) ? $msg_from_airport_forced : $msg_from_airport_voluntary;

        $reschedule_sms_from_airport_other = ($order_undelivered) ? $msg_from_airport_forced_other : $msg_from_airport_voluntary_other;

        $reschedule_sms_to_airport = ($order_undelivered) ? $msg_to_airport_forced : $msg_to_airport_voluntary;

        $reschedule_sms_to_airport_other = ($order_undelivered) ? $msg_to_airport_forced_other : $msg_to_airport_voluntary_other;
        $model1['order_details']=Order::getorderdetails($model->id_order);
        $model1['order_details']['order']['round_additinal_msg']='';
        $model1['reschedule_order_details']=Order::getorderdetails($model->related_order_id);

        $customer_number = $model1['order_details']['order']['c_country_code'].$model1['order_details']['order']['customer_mobile'];
        $traveller_number = $model1['order_details']['order']['traveler_country_code'].$model1['order_details']['order']['travell_passenger_contact'];
        $location_contact = Yii::$app->params['default_code'].$model1['order_details']['order']['location_contact_number'];

    //    $reschedule_mail = ($order_undelivered) ? User::sendemail($corporate_email,"Forced Reschedule on Order #".$model->order_number." placed on Caterx",'reschedule_order_forced',$model1) : User::sendemail($corporate_email,"Reschedule on Order #".$model->order_number." placed on Caterx",'reschedule_order',$model1);

        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_confirmation_pdf_template');
        $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

        User::sendEmailExpressMultipleAttachment($_POST['email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$model1,$attachment_det);
       

       
         $slot_start_time = date('h:i a', strtotime($model1['order_details']['order']['slot_start_time']));
        $slot_end_time = date('h:i a', strtotime($model1['order_details']['order']['slot_end_time']));
        $slot_scehdule = $slot_start_time.' To '.$slot_end_time;
        //echo"<pre>";print_r($model1['order_details']);exit; 
        $date_created = ($model1['order_details']['order']['date_created']) ? date("Y-m-d", strtotime($model1['order_details']['order']['date_created'])) : '';

        $order_date = ($model1['order_details']['order']['order_date']) ? date("Y-m-d", strtotime($model1['order_details']['order']['order_date'])) : '';

        if($model->service_type == 1){
             $service = 'To Airport';

             $reschedule_sms_to_airport = 'Dear Customer, your Order #'.$model->order_number.' '.$service.'  placed on '.$date_created.' by '.$model1['order_details']['order']['customer_name'].' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
            $reschedule_sms_to_airport_other = 'Dear Customer, your Order #'.$model->order_number.' '.$service.'  placed on '.$date_created.' by '.$model1['order_details']['order']['customer_name'].' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
 


            //User::sendsms($customer_number, $reschedule_sms_to_airport);

            //User::sendsms($traveller_number,$reschedule_sms_to_airport);
                                            //location contact
            if($osd->assigned_person == 1){
               // User::sendsms($location_contact,$reschedule_sms_to_airport_other);
            }
        }else{
             $service = 'From Airport';

            $reschedule_sms_from_airport = 'Dear Customer, your Order #'.$model->order_number.' '.$service.'  placed on '.$date_created.' by '.$model1['order_details']['order']['customer_name'].' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
            $reschedule_sms_from_airport_other = 'Dear Customer, your Order #'.$model->order_number.' '.$service.'  placed on '.$date_created.' by '.$model1['order_details']['order']['customer_name'].' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';


            //User::sendsms($customer_number,$reschedule_sms_from_airport);

            //User::sendsms($traveller_number,$reschedule_sms_from_airport_other);
                                            //location contact
            if($osd->assigned_person == 1){
                //User::sendsms($location_contact,$reschedule_sms_from_airport_other);
            }
        }

        // if($order_undelivered){
        //     Yii::$app->Common->getGeneralKioskSms($model1['reschedule_order_details']['order']['id_order'], 'order_confirmation_voluntary_reschdule', '');
        // }else{
        //     Yii::$app->Common->getGeneralKioskSms($model1['reschedule_order_details']['order']['id_order'], 'order_confirmation_forced_reschdule', '');
        // }  
    }
    public function actionCorporateView(){
        $copId =  Yii::$app->user->identity->id_employee;
        $cId = \app\models\CorporateDetails::find()
                ->where(['employee_id'=>$copId])
                ->one();
        $luggages = LuggageType::find()
                ->where(['corporate_id'=>$cId->corporate_detail_id])
                ->one();
        return $this->render('corporate-view', [
            'model' => $cId,
            'luggages' => $luggages,

        ]);
    }


    public function actionPorterLocation()
    {

       /*$locations = Yii::$app->db->createCommand("SELECT * from (SELECT * from tbl_location_tracking ORDER BY id_location_tracking DESC) AS x GROUP BY fk_tbl_location_tracking_id_employee")->queryall();*/
       $locations = Yii::$app->db->createCommand("SELECT x.*, e.id_employee,r.role, e.fk_tbl_employee_id_employee_role, e.name, e.mobile,e.employee_profile_picture,v.id_vehicle, v.vehicle_number from (SELECT * from tbl_location_tracking ORDER BY id_location_tracking DESC) AS x LEFT JOIN tbl_employee e ON e.id_employee = x.fk_tbl_location_tracking_id_employee LEFT JOIN tbl_vehicle v on v.id_vehicle = x.fk_tbl_location_tracking_id_vehicle LEFT JOIN tbl_employee_role r on r.id_employee_role = e.fk_tbl_employee_id_employee_role WHERE x.date_created > subdate(current_date, 1) AND e.status = 1 GROUP BY x.fk_tbl_location_tracking_id_employee")->queryall();
        //print_r($locations);exit;
        return $this->render('porter-location', [
            'locations'=> $locations,
        ]);
    }

    /*
    To Save the payment details when the click on payment link.
    */
    public function actionWebhook(){
          $get_post_data = file_get_contents('php://input');
          if(isset($get_post_data)){
              ob_start();
              $json_to_array = json_decode($get_post_data, true);
              if($json_to_array['event'] == 'invoice.paid'){
                    $payment_details = $json_to_array['payload']['payment']['entity'];
                    $invoice_id = $payment_details['invoice_id'];
                    $payment_method = $payment_details['method'];
                    $finserveUpdate = FinserveTransactionDetails::findOne(['invoice_id' => $invoice_id]);
                    if($finserveUpdate->transaction_status == 'issued'){
                        if($finserveUpdate){
                            $finserveUpdate->payment_method = $payment_method;
                            $finserveUpdate->save(false);
                        }
                        $invoice_details = $json_to_array['payload']['invoice']['entity'];
                        $finserveUpdate1 = FinserveTransactionDetails::findOne(['invoice_id' => $invoice_id]);
                        if($finserveUpdate1){
                              $finserveUpdate1->payment_id = $invoice_details['payment_id'];
                              $finserveUpdate1->transaction_status = $invoice_details['status'];
                              $paid_date = date("Y-m-d h:i:s",$invoice_details['paid_at']);
                              $expire_date = date("Y-m-d h:i:s",$invoice_details['expire_by']);
                              $finserveUpdate->paid_date = $paid_date;
                              $finserveUpdate->expiry_date = $expire_date;
                              $amount = $invoice_details['amount_paid'] / 100;
                              $finserveUpdate1->amount_paid = $amount;
                              $finserveUpdate1->save(false);

                              $model = Order::findOne($finserveUpdate1->order_id);
                              $model->amount_paid = $amount;
                              $model->fk_tbl_order_status_id_order_status = ($model->service_type == 1) ? 3 : 2;
                              $model->order_status = ($model->service_type == 1) ? "Open" : "Confirmed";
                              $model->save(false);

                              $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$finserveUpdate1->order_id."' ORDER BY oh.id_order_history DESC")->queryOne();

                              $modelHistory = new OrderHistory();
                              $modelHistory->fk_tbl_order_history_id_order = $finserveUpdate1->order_id;
                              $modelHistory->from_tbl_order_status_id_order_status = $last_order_status['to_tbl_order_status_id_order_status'];
                              $modelHistory->from_order_status_name = $last_order_status['to_order_status_name'];
                              $modelHistory->to_tbl_order_status_id_order_status = ($model->service_type == 1) ? 3 : 2;
                              $to_order_status_name = Yii::$app->db->createCommand("select os.status_name from tbl_order_status os where os.id_order_status='".$modelHistory->to_tbl_order_status_id_order_status."' ")->queryOne();
                              $modelHistory->to_order_status_name = $to_order_status_name['status_name'];
                              $modelHistory->date_created = date('Y-m-d H:i:s');
                              $modelHistory->save(false);
                              $data['order_details'] = Order::getorderdetails($finserveUpdate1->order_id);

                            // $sms_content = Yii::$app->Common->getGeneralKioskSms($finserveUpdate1->order_id, 'order_confirmation', '');

                              $orderPaymentDetails = OrderPaymentDetails::findOne(['id_order' => $finserveUpdate1->order_id]);
                              if($orderPaymentDetails){
                                    // $model->amount_paid = $orderPaymentDetails->amount_paid;
                                    // $model->save(false);
                                    $orderPaymentDetails->payment_status = 'Success';
                                    $orderPaymentDetails->save(false);
                              }

                              $WebhookLog = new WebhookLog();
                              $WebhookLog->description = $payment_details['method'];
                              $WebhookLog->save(false);
                              $WebhookLogUpdate = WebhookLog::findOne(['id_webhook' => $WebhookLog->id_webhook]);
                              $issued_at = date("Y-m-d h:i:s",$invoice_details['issued_at']);
                              $paid_at = date("Y-m-d h:i:s",$invoice_details['paid_at']);

                              $WebhookLogUpdate->payment_id = $invoice_details['payment_id'];
                              // $WebhookLogUpdate->order_id = $finserveUpdate1->order_id;
                              $WebhookLogUpdate->invoice_id = $invoice_id;
                              $WebhookLogUpdate->customer_id = $invoice_details['customer_id'];
                              // $WebhookLogUpdate->customer_name = $invoice_details['customer_details']['name'];
                              $WebhookLogUpdate->customer_number = $invoice_details['customer_details']['contact'];
                              $WebhookLogUpdate->status = $invoice_details['status'];
                              $WebhookLogUpdate->issued_date = $issued_at;
                              $WebhookLogUpdate->paid_date = $paid_at; 
                              $WebhookLogUpdate->sms_status = $invoice_details['sms_status'];
                              $WebhookLogUpdate->email_status = $invoice_details['email_status']; 
                              $WebhookLogUpdate->amount_paid = $invoice_details['amount'];
                              $WebhookLogUpdate->short_url = $invoice_details['short_url'];
                              // $WebhookLogUpdate->user_id = $invoice_details['user_id'];
                              $WebhookLogUpdate->save(false);
                              if($model->order_transfer==1){ 
                                $sms_content = Yii::$app->Common->generateCityTransferSms($finserveUpdate1->order_id, 'OrderConfirmation', '');
                                 

                                 $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_confirmation_pdf_template');
                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                            User::sendEmailExpressMultipleAttachment($data['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$data,$attachment_det);


                                $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);
                              }else{
                                  if($model->corporate_type == 3){
                                    if($model->corporate_id == 43){
                                        $traveller_number = $data['order_details']['order']['c_country_code'].$data['order_details']['order']['travell_passenger_contact'];
                                        $flyportersms = "Dear Customer, thank you for booking the AirAsia's Flyporter Service with CarterX. Please read the information document enclosed carefully for a better travel experience: *Departure:* https://flyporter.carterporter.in/depature-details *Arrivals:* https://flyporter.carterporter.in/arrival-details. Travel safe and stress free from the get go.";
                                            User::sendsms($traveller_number,$flyportersms );   
                                    }
                                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_confirmation_pdf_template');
                                    $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';
                    
                                    User::sendEmailExpressMultipleAttachment($data['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$data,$attachment_det);
                                    $sms_content = Yii::$app->Common->getCorporateSms($finserveUpdate1->order_id, 'order_confirmation', '');
                                    $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                    User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);

                                  }else{
                                    //$sms_content = Yii::$app->Common->getGeneralKioskSms($finserveUpdate1->order_id, 'order_confirmation', '');
                                    $sms_content = Yii::$app->Common->getCorporateSms($finserveUpdate1->order_id, 'order_confirmation', '');
                                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_confirmation_pdf_template');
                                    $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';
                                    User::sendEmailExpressMultipleAttachment($data['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$data,$attachment_det);
                                    $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                    User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);
                                  }
                              }
                              
                               

                            $model1['order_details']=Order::getorderdetails($finserveUpdate1->order_id);
                            // echo "<pre>";print_r($model1);exit;
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_payments_pdf_template');

                            //User::sendemailexpressattachment($model1['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$attachment_det);

                        }
                        ob_end_clean();
                        echo Json::encode(['status'=>true,'message'=>'Sucess']);
                    }else{
                        ob_end_clean();
                        echo Json::encode(['status'=>false,'message'=>'No Data']);
                    }
              }
          }
          if(isset($get_post_data)){
              $json_to_array = json_decode($get_post_data, true);
              if($json_to_array['event'] == 'invoice.expired'){
                  $expity_details = $json_to_array['payload']['invoice']['entity'];
                  $invoice_id = $expity_details['id'];
                  $finserveUpdate2 = FinserveTransactionDetails::findOne(['invoice_id' => $invoice_id]);
                  if($finserveUpdate2){
                      $finserveUpdate2->payment_id = $expity_details['payment_id'];
                      $finserveUpdate2->transaction_status = $expity_details['status'];
                      $finserveUpdate2->description = "Payment Link Expired";
                      $finserveUpdate2->save(false);

                      $WebhookLogUpdate = new WebhookLog();
                      $expiry_by = date("Y-m-d h:i:s",$invoice_details['expiry_by']);
                      $issued_at = date("Y-m-d h:i:s",$invoice_details['issued_at']);
                      $paid_at = date("Y-m-d h:i:s",$invoice_details['paid_at']);

                      $WebhookLogUpdate->payment_id = $invoice_details['payment_id'];
                      $WebhookLogUpdate->order_id = $finserveUpdate2->order_id;
                      $WebhookLogUpdate->invoice_id = $invoice_details['id'];
                      $WebhookLogUpdate->customer_id = $invoice_details['customer_id'];
                      $WebhookLogUpdate->customer_name = $invoice_details['customer_details']['name'];
                      $WebhookLogUpdate->customer_number = $invoice_details['customer_details']['contact'];
                      $WebhookLogUpdate->status = $invoice_details['status'];

                      $WebhookLogUpdate->expiry_by = $expiry_by;
                      $WebhookLogUpdate->issued_date = $issued_at;
                      $WebhookLogUpdate->paid_date = $paid_at;

                      $WebhookLogUpdate->sms_status = $invoice_details['sms_status'];
                      $WebhookLogUpdate->email_status = $invoice_details['email_status'];

                      $WebhookLogUpdate->amount_paid = $invoice_details['amount_paid'];
                      $WebhookLogUpdate->short_url = $invoice_details['short_url'];
                      $WebhookLogUpdate->save(false);
                  }
            }
        }
    }

    public function actionGetCorporateInfo(){
        $post = Yii::$app->request->post();
        if(!empty($post['corporate_id'])){
            $details = Yii::$app->Common->getCorporates($post['corporate_id']);
            if($details){
                $result = array(
                    "thirdparty_corporate_id" => $details->thirdparty_corporate_id,
                    "fk_corporate_id" => $details->fk_corporate_id,
                    "corporate_name" => $details->corporate_name,
                    "access_token" => $details->access_token,
                    "bag_limit" => $details->bag_limit,
                    "gst" => $details->gst,
                    "is_active" => $details->is_active,
                    "order_type" => $details->order_type,
                    "max_bag_weight" => $details->max_bag_weight,
                    "excess_bag_weight" => $details->excess_bag_weight
                );
                return json_encode($result);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function actionGetAssignAirportList($regionId) {
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

    public function actionCheckRazorpayStatus($order_id=null,$status=null){
        $result = array();
        $api = new Api(Yii::$app->params['razorpay_api_key'], Yii::$app->params['razorpay_secret_key']);
        if(!empty($order_id) && $status=='single'){
            // $result =  Yii::$app->db->createCommand("Select * from tbl_finserve_transaction_details where transaction_status = 'issued' and order_id='".$order_id."' and order_type != 'create-order-modification-porter-porterx' order by `id_finserve` desc")->queryAll();
            $result =  Yii::$app->db->createCommand("Select * from tbl_finserve_transaction_details where transaction_status = 'issued' and order_id='".$order_id."' order by `id_finserve` desc")->queryAll();
        } else if(empty($order_id) && $status=='multiple'){
            // $result =  Yii::$app->db->createCommand("Select * from tbl_finserve_transaction_details where transaction_status = 'issued' and order_type != 'create-order-modification-porter-porterx' order by `id_finserve` desc")->queryAll();
            $result =  Yii::$app->db->createCommand("Select * from tbl_finserve_transaction_details where transaction_status = 'issued' order by `id_finserve` desc")->queryAll();
        }
        if(!empty($result)){
            foreach($result as $value){
                $invoiceResult = $api->invoice->fetch($value['invoice_id']);
                if(isset($invoiceResult) && isset($invoiceResult['error'])){
                    // echo json_encode(['status'=>'0','message'=>$invoiceResult['error']['description']." in  razorpay"]);exit;
                }else{
                    if($invoiceResult['status'] == 'paid'){
                        $invoice_id = $invoiceResult['id'];
                        $payment_method = $invoiceResult['payments']['items'][0]['method'];
                        $finserveUpdate = FinserveTransactionDetails::findOne(['invoice_id' => $invoice_id]);
                        if($finserveUpdate->transaction_status == 'issued'){
                            if($finserveUpdate){
                                $finserveUpdate->payment_method = $payment_method;
                                $finserveUpdate->save(false);
                            }
                            $finserveUpdate1 = FinserveTransactionDetails::findOne(['invoice_id' => $invoice_id]);
                            if($finserveUpdate1){
                                $finserveUpdate1->payment_id = $invoiceResult['payments']['items'][0]['id'];
                                $finserveUpdate1->transaction_status = $invoiceResult['status'];
                                $paid_date = date("Y-m-d h:i:s",$invoiceResult['paid_at']);
                                $expire_date = isset($invoiceResult['expire_by']) ? date("Y-m-d h:i:s",$invoiceResult['expire_by']) : "";
                                $finserveUpdate->paid_date = $paid_date;
                                $finserveUpdate->expiry_date = $expire_date;
                                $amount = $invoiceResult['amount_paid'] / 100;
                                $finserveUpdate1->amount_paid = $amount;
                                $finserveUpdate1->save(false);

                                $WebhookLog = new WebhookLog();
                                $WebhookLog->description = $invoiceResult['payments']['items'][0]['method'];
                                $WebhookLog->save(false);
                                $WebhookLogUpdate = WebhookLog::findOne(['id_webhook' => $WebhookLog->id_webhook]);
                                $issued_at = date("Y-m-d h:i:s",$invoiceResult['issued_at']);
                                $paid_at = date("Y-m-d h:i:s",$invoiceResult['paid_at']);

                                $WebhookLogUpdate->payment_id = $invoiceResult['payment_id'];
                                // $WebhookLogUpdate->order_id = $finserveUpdate1->order_id;
                                $WebhookLogUpdate->invoice_id = $invoice_id;
                                $WebhookLogUpdate->customer_id = $invoiceResult['customer_id'];
                                // $WebhookLogUpdate->customer_name = $invoiceResult['customer_details']['name'];
                                $WebhookLogUpdate->customer_number = $invoiceResult['customer_details']['contact'];
                                $WebhookLogUpdate->status = $invoiceResult['status'];
                                $WebhookLogUpdate->issued_date = $issued_at;
                                $WebhookLogUpdate->paid_date = $paid_at; 
                                $WebhookLogUpdate->sms_status = $invoiceResult['sms_status'];
                                $WebhookLogUpdate->email_status = $invoiceResult['email_status']; 
                                $WebhookLogUpdate->amount_paid = $invoiceResult['payments']['items'][0]['amount'];
                                $WebhookLogUpdate->short_url = $invoiceResult['short_url'];
                                // $WebhookLogUpdate->user_id = $invoiceResult['user_id'];
                                $WebhookLogUpdate->save(false);

                                $data['order_details'] = Order::getorderdetails($finserveUpdate1->order_id);

                                $orderPaymentDetails = OrderPaymentDetails::findOne(['id_order' => $finserveUpdate1->order_id]);
                                if($orderPaymentDetails){
                                    $orderPaymentDetails->payment_status = 'Success';
                                    $orderPaymentDetails->save(false);
                                }

                                if($value['order_type'] != "create-order-modification-porter-porterx") {
                                    $model = Order::findOne($finserveUpdate1->order_id);
                                    $model->amount_paid = $amount;
                                    $model->fk_tbl_order_status_id_order_status = ($model->service_type == 1) ? 3 : 2;
                                    $model->order_status = ($model->service_type == 1) ? "Open" : "Confirmed";
                                    $model->save(false);

                                    $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$finserveUpdate1->order_id."' ORDER BY oh.id_order_history DESC")->queryOne();

                                    $modelHistory = new OrderHistory();
                                    $modelHistory->fk_tbl_order_history_id_order = $finserveUpdate1->order_id;
                                    $modelHistory->from_tbl_order_status_id_order_status = $last_order_status['to_tbl_order_status_id_order_status'];
                                    $modelHistory->from_order_status_name = $last_order_status['to_order_status_name'];
                                    $modelHistory->to_tbl_order_status_id_order_status = ($model->service_type == 1) ? 3 : 2;
                                    $to_order_status_name = Yii::$app->db->createCommand("select os.status_name from tbl_order_status os where os.id_order_status='".$modelHistory->to_tbl_order_status_id_order_status."' ")->queryOne();
                                    $modelHistory->to_order_status_name = $to_order_status_name['status_name'];
                                    $modelHistory->date_created = date('Y-m-d H:i:s');
                                    $modelHistory->save(false);

                                    if($model->order_transfer==1){ 
                                        $sms_content = Yii::$app->Common->generateCityTransferSms($finserveUpdate1->order_id, 'OrderConfirmation', '');

                                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_confirmation_pdf_template');
                                        $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';

                                        User::sendEmailExpressMultipleAttachment($data['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$data,$attachment_det);

                                        $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                        User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);
                                    } else {
                                        if($model->corporate_type == 3){
                                            if($model->corporate_id == 43){
                                                $traveller_number = $data['order_details']['order']['c_country_code'].$data['order_details']['order']['travell_passenger_contact'];
                                                $flyportersms = "Dear Customer, thank you for booking the AirAsia's Flyporter Service with CarterX. Please read the information document enclosed carefully for a better travel experience: *Departure:* https://flyporter.carterporter.in/depature-details *Arrivals:* https://flyporter.carterporter.in/arrival-details. Travel safe and stress free from the get go.";
                                                User::sendsms($traveller_number,$flyportersms );   
                                            }
                                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_confirmation_pdf_template');
                                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';
                            
                                            User::sendEmailExpressMultipleAttachment($data['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$data,$attachment_det);
                                            $sms_content = Yii::$app->Common->getCorporateSms($finserveUpdate1->order_id, 'order_confirmation', '');
                                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                            User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);

                                        } else {
                                            $sms_content = Yii::$app->Common->getCorporateSms($finserveUpdate1->order_id, 'order_confirmation', '');
                                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_confirmation_pdf_template');
                                            $attachment_det['second_path'] = Yii::$app->params['site_url'].'passenger_security.pdf';
                                            User::sendEmailExpressMultipleAttachment($data['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$model->order_number."",'order_confirmation',$data,$attachment_det);
                                            $Invoice_attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                            User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);
                                        }
                                    }
                                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_payments_pdf_template');
                                } else {                                    
                                    $orderPayDetailRes = Yii::$app->db->createCommand("select t_opd.* from `tbl_order_modification_details` t_omd left join `tbl_order_payment_details` t_opd ON t_opd.id_order_payment_details = t_omd.fk_id_order_payment_details where t_omd.order_id = '".$finserveUpdate1['order_id']."' and t_omd.razorpay_link = '".$finserveUpdate1['short_url']."'")->queryOne();
                                    
                                    if(!empty($orderPayDetailRes)){
                                        $res = Yii::$app->db->createCommand("UPDATE `tbl_order_payment_details` set `payment_status` = 'Success' where `id_order_payment_details` = '".$orderPayDetailRes['id_order_payment_details']."'")->execute();
                                    } 

                                    $Invoice_attachment_det = Yii::$app->Common->genarateOrderConfirmationPdf($data,'order_modification_amount_info',"order_modification");
                                    User::sendemailexpressattachment($data['order_details']['order']['customer_email'],"Order Modification Payment",'after_any_kind_of_payment_made',$data,$Invoice_attachment_det);
                                }
                            }
                            // ob_end_clean();
                            // echo Json::encode(['status'=>true,'message'=>'Sucess']);
                        }else{
                            // ob_end_clean();
                            // echo Json::encode(['status'=>false,'message'=>'No Data']);
                        }
                    }
                }
            }
        }
        // if(isset($get_post_data)){
        //     $json_to_array = json_decode($get_post_data, true);
        //     if($json_to_array['event'] == 'invoice.expired'){
        //         $expity_details = $json_to_array['payload']['invoice']['entity'];
        //         $invoice_id = $expity_details['id'];
        //         $finserveUpdate2 = FinserveTransactionDetails::findOne(['invoice_id' => $invoice_id]);
        //         if($finserveUpdate2){
        //             $finserveUpdate2->payment_id = $expity_details['payment_id'];
        //             $finserveUpdate2->transaction_status = $expity_details['status'];
        //             $finserveUpdate2->description = "Payment Link Expired";
        //             $finserveUpdate2->save(false);

        //             $WebhookLogUpdate = new WebhookLog();
        //             $expiry_by = date("Y-m-d h:i:s",$invoice_details['expiry_by']);
        //             $issued_at = date("Y-m-d h:i:s",$invoice_details['issued_at']);
        //             $paid_at = date("Y-m-d h:i:s",$invoice_details['paid_at']);

        //             $WebhookLogUpdate->payment_id = $invoice_details['payment_id'];
        //             $WebhookLogUpdate->order_id = $finserveUpdate2->order_id;
        //             $WebhookLogUpdate->invoice_id = $invoice_details['id'];
        //             $WebhookLogUpdate->customer_id = $invoice_details['customer_id'];
        //             $WebhookLogUpdate->customer_name = $invoice_details['customer_details']['name'];
        //             $WebhookLogUpdate->customer_number = $invoice_details['customer_details']['contact'];
        //             $WebhookLogUpdate->status = $invoice_details['status'];

        //             $WebhookLogUpdate->expiry_by = $expiry_by;
        //             $WebhookLogUpdate->issued_date = $issued_at;
        //             $WebhookLogUpdate->paid_date = $paid_at;

        //             $WebhookLogUpdate->sms_status = $invoice_details['sms_status'];
        //             $WebhookLogUpdate->email_status = $invoice_details['email_status'];

        //             $WebhookLogUpdate->amount_paid = $invoice_details['amount_paid'];
        //             $WebhookLogUpdate->short_url = $invoice_details['short_url'];
        //             $WebhookLogUpdate->save(false);
        //         }
        //     }
        // }    
    }

    public function actionSelectMhlOrderTransfer($corporateId){
        $rows=CorporateDetails::find()
                ->select(['order_type'])
                ->where(['corporate_detail_id'=>$corporateId])
                ->all();      
        if(count($rows)>0){
            foreach($rows as $row){
                if($row['order_type'] == 1){
                    echo "<option>Select Order Type</option><option value='1'>City Transfer</option>";
                } else if($row['order_type'] == 2){
                    echo "<option>Select Order Type</option><option value='2'>Airport Transfer</option>";
                } else if($row['order_type'] == 3){
                    echo "<option>Select Order Type</option><option value='1'>City Transfer</option><option value='2'>Airport Transfer</option>";
                } else {
                    echo "<option>No Order Transfer</option>";
                }
            }
        }else{
            echo "<option value=''>No Order Transfer</option>";
        }
    }

    public function actionCheckMhlPincode(){
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $pincode = $post['pincode'];
            $region_id = $post['region'];
            if(isset($post['type']) && ($post['type'] == "state")){
                $region_name = Yii::$app->db->createCommand("SELECT stateName FROM tbl_state where idState =".$region_id)->queryOne();
                if(!empty($region_name)){
                    $res = Yii::$app->Common->getPostalData($pincode,strtolower($region_name['stateName']));
                    if(!empty($res)){
                        echo json_encode(array('status' => 1, "message" => "pincode exist in region."));die;
                    } else {
                        echo json_encode(array('status' => 0,"message" => "Pincode not exist."));die;
                    }
                } else {
                    echo json_encode(array('status' => 0,"message" => "Region not exist."));die;
                }
            } else if(isset($post['type']) && ($post['type'] == "airport")){
                $region_name = Yii::$app->db->createCommand("SELECT CO.region_name FROM tbl_city_of_operation as CO left join tbl_airport_of_operation as AO ON AO.fk_tbl_city_of_operation_region_id = CO.id where AO.airport_name_id =".$region_id)->queryOne();
                if(!empty($region_name)){
                    $res = Yii::$app->Common->getPostalData($pincode,strtolower($region_name['region_name']));
                    if(!empty($res)){
                        echo json_encode(array('status' => 1, "message" => "pincode exist in region."));die;
                    } else {
                        echo json_encode(array('status' => 0,"message" => "Pincode not exist."));die;
                    }
                } else {
                    echo json_encode(array('status' => 0,"message" => "Region not exist."));die;
                }
            } else {
                $region_name = Yii::$app->db->createCommand("SELECT region_name FROM tbl_city_of_operation where id =".$region_id)->queryOne();
                if(!empty($region_name)){
                    $res = Yii::$app->Common->getPostalData($pincode,strtolower($region_name['region_name']));
                    if(!empty($res)){
                        echo json_encode(array('status' => 1, "message" => "pincode exist in region."));die;
                    } else {
                        echo json_encode(array('status' => 0,"message" => "Pincode not exist."));die;
                    }
                } else {
                    echo json_encode(array('status' => 0,"message" => "Region not exist."));die;
                }
            }
        } else {
            echo json_encode(array('status' => 0, "message" => "Please send post data."));die;
        }
    }

    public function actionSelectedState(){
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $cityId = $post['city_id'];
            $airportId = $post['airport_id'];
            $type = $post['type'];
            if($type == 'city'){
                $state = State::find()->select(['idState','stateName'])->where(['status'=> 'Active','city_id' => $cityId])->all();
            } else {
                $state = State::find()->select(['idState','stateName'])->where(['status'=> 'Active','airport_id' => $airportId])->all();
            }

            if(!empty($state)){
                foreach($state as $value){
                    echo "<option value='".$value['idState']."'>".$value['stateName'] ."</option>";
                }
            } else {
                echo "<option> No State allot. </option>";die;
            }
        } else {
            echo "<option> No State allot. </option>";die;
        }
    }

    public function actionCreateSuperSubscriberGeneralOrder($mobile){
        $model['o'] = new Order();
        $model['re'] = new CityOfOperation();
        $model['osd'] = new OrderSpotDetails();
        $model['oi'] = new OrderItems();
        $model['mi'] = new MallInvoices();
        $model['om'] = new OrderMetaDetails();
        $model['sta'] = new State();
        $customers = Customer::findOne(['mobile' => $mobile]);
        // $model['osd']->scenario = 'create_order_general';
        $model['o']->scenario = 'create_order_genral';
        $model['bw']=new BagWeightType();
        $OrderZoneDetails = new OrderZoneDetails();
        $employee_model = new Employee();
        // $employee_model->scenario = 'corporate_general';
        $setCC = array();

        // if (Yii::$app->request->post()) {
        if ($model['o']->load(Yii::$app->request->post()) && $model['osd']->load(Yii::$app->request->post()) && Model::validateMultiple([$model['o'], $model['osd']])) {
            // echo "<pre>";print_r($_POST);exit;
            $razorpay_api_key = isset(Yii::$app->params['razorpay_api_key']) ? Yii::$app->params['razorpay_api_key'] : "rzp_test_VSvN3uILIxekzY";
            $razorpay_secret_key = isset(Yii::$app->params['razorpay_secret_key']) ? Yii::$app->params['razorpay_secret_key'] : "Flj35MJPZTJZ0WiTBlynY14k";
            $api = new Api($razorpay_api_key, $razorpay_secret_key);

            $primary_email = CorporateDetails::find()->select(['default_email','default_contact'])->where(['corporate_detail_id'=> Yii::$app->request->post()['Order']['corporate_id']])->One();
            if($primary_email['default_email']){
                array_push($setCC,$primary_email['default_email'],Yii::$app->params['customer_email']);
            }
            $DeliveryRes = Yii::$app->Common->getExpectedDeliveryDateTime($_POST['Order']['delivery_type'],$_POST['Order']['service_type'],$_POST['Order']['fk_tbl_order_id_slot'],date('Y-m-d',strtotime($_POST['Order']['order_date'])));

            $model = new Order();
            if(!empty($DeliveryRes)){
                $model->delivery_datetime = isset($DeliveryRes['delivery_date_time']) ? $DeliveryRes['delivery_date_time'] : "";
                $model->delivery_time_status = isset($DeliveryRes['delivery_status']) ? $DeliveryRes['delivery_status'] : "";
            }
            $corporate_id = Yii::$app->Common->getCorporates($_POST['Order']['corporate_id']);
            $model->corporate_id = $corporate_id->fk_corporate_id;
            // $model->fk_thirdparty_corporate_id = isset($_POST['Order']['corporate_id']) ? $_POST['Order']['corporate_id'] : 0;
            $model->city_pincode = $_POST['Order']['city_pincode'];
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

            $model->no_of_units = $_POST['Order']['no_of_units'];
            $model->fk_tbl_order_id_slot = $_POST['Order']['fk_tbl_order_id_slot'];
            $model->service_tax_amount = $_POST['Order']['service_tax_amount'];
            $model->luggage_price = $_POST['Order']['luggage_price'];

            $model->travel_person = 1;
            $model->travell_passenger_name = $_POST['Order']['travell_passenger_name'];

            $delivery_dates = Yii::$app->Common->selectedSlot($_POST['Order']['fk_tbl_order_id_slot'], $model->order_date, $model->delivery_type);
            
            $model->delivery_date = $delivery_dates['delivery_date'];
            $model->delivery_time = $delivery_dates['delivery_time'];

            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
            $model->travell_passenger_contact = $_POST['Order']['travell_passenger_contact'];
            $model->dservice_type = $_POST['Order']['dservice_type'];

            $model->flight_number = $_POST['Order']['flight_number'];
            $model->meet_time_gate = date("H:i", strtotime($_POST['Order']['meet_time_gate']));
            $model->confirmation_number = $_POST['confirmation_number'];

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

            $model->payment_method = $_POST['OrderPaymentDetails']['payment_type'];
            if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                $model->fk_tbl_order_status_id_order_status = 1;
                $model->order_status = 'Yet to be confirmed';
                
            } else {
                $model->fk_tbl_order_status_id_order_status = ($_POST['Order']['service_type'] == 1) ? 3 : 2;
                $model->order_status = ($_POST['Order']['service_type'] == 1) ? 'open' : 'Confirmed';
                if(!empty($_POST['total_convayance_amount'])){
                    $model->amount_paid = !empty($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : 0;
                }else {
                    $model->amount_paid = !empty($_POST['Order']['luggage_price']) ? $_POST['Order']['luggage_price'] : 0;
                }
                // $model->amount_paid = ($_POST['Order']['delivery_type'] == 2) ? $_POST['total_convayance_amount'] : $_POST['Order']['luggage_price'];
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
            $slot_det = Slots::findOne($_POST['Order']['fk_tbl_order_id_slot']);
            $corporate_det = \app\models\CorporateDetails::findOne($_POST['Order']['corporate_id']);
            if($model->save()){
                if(($_POST['Order']['delivery_type'] == 2) || (!empty($_POST['convayance_price']))){
                    $outstation_id = isset($_POST['outstation_id']) ? $_POST['outstation_id'] : 0;
                    $city_name = isset($_POST['city_name']) ? $_POST['city_name'] : 0;
                    $state_name = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                    $extr_kms = isset($_POST['extr_kms']) ? $_POST['extr_kms'] : 0;
                    $service_tax_amount = isset($_POST['service_tax_amount']) ? $_POST['service_tax_amount'] : 0;
                    $convayance_price = isset($_POST['convayance_price']) ? $_POST['convayance_price'] : 0;
                    $date = date('Y-m-d H:i:s');
                    Yii::$app->db->createCommand("insert into tbl_order_zone_details (orderId,outstationZoneId,cityZoneId,stateId,extraKilometer,taxAmount,outstationCharge,createdOn) values($model->id_order,$outstation_id,$city_name,$state_name,$extr_kms,$service_tax_amount,$convayance_price,'".$date."')")->execute();

                    // $OrderZoneDetails = new OrderZoneDetails;
                    // $OrderZoneDetails->orderId = $model->id_order;
                    // $OrderZoneDetails->outstationZoneId = isset($_POST['outstation_id']) ? $_POST['outstation_id'] : 0;
                    // $OrderZoneDetails->cityZoneId = isset($_POST['city_name']) ? $_POST['city_name'] : 0;
                    // $OrderZoneDetails->stateId = isset($_POST['state_name']) ? $_POST['state_name'] : 0;
                    // $OrderZoneDetails->extraKilometer = isset($_POST['extr_kms']) ? $_POST['extr_kms'] : 0; 
                    // $OrderZoneDetails->taxAmount = isset($_POST['service_tax_amount']) ? $_POST['service_tax_amount'] : 0;
                    // $OrderZoneDetails->outstationCharge = isset($_POST['convayance_price']) ? $_POST['convayance_price'] : 0;
                    // $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');
                    // $OrderZoneDetails->save(true);
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
                $order_payment_details->payment_type = $_POST['OrderPaymentDetails']['payment_type'];
                $order_payment_details->id_employee = Yii::$app->user->identity->id_employee;
                if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                    $order_payment_details->payment_status = 'Not paid';
                }else{
                    $order_payment_details->payment_status = 'Success';
                }
                $order_payment_details->amount_paid = $_POST['total_convayance_amount'] ? $_POST['total_convayance_amount'] : $_POST['Order']['luggage_price'];
                $order_payment_details->value_payment_mode = 'Order Amount';
                $order_payment_details->date_created= date('Y-m-d H:i:s');
                $order_payment_details->date_modified= date('Y-m-d H:i:s');
                $order_payment_details->save();

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
                if($_POST['Order']['delivery_type'] == 2 || $_POST['Order']['delivery_type'] == 1){
                    if($_POST['Order']['order_transfer'] == 1){
                        // if($_POST['Order']['delivery_type'] == 2 && ($_POST['Order']['order_transfer'] == 1)){
                        //     $OrderZoneDetails->orderId = $model->id_order;
                        //     $OrderZoneDetails->outstationZoneId = $_POST['outstation_id'];
                        //     $OrderZoneDetails->cityZoneId = $_POST['city_id'];
                        //     $OrderZoneDetails->stateId = $_POST['State']['idState'];
                        //     $OrderZoneDetails->extraKilometer = $_POST['Order']['extr_kms'];
                        //     $OrderZoneDetails->taxAmount = $_POST['Order']['luggageGST'];;
                        //     $OrderZoneDetails->outstationCharge = $_POST['outstation_charge'];
                        //     $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');

                        //     $OrderZoneDetails->save(false);
                        // }
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
                        // if($_POST['Order']['delivery_type'] == 2 && ($_POST['Order']['order_transfer'] == 2)){
                        //     $OrderZoneDetails->orderId = $model->id_order;
                        //     $OrderZoneDetails->outstationZoneId = $_POST['outstation_id'];
                        //     $OrderZoneDetails->cityZoneId = $_POST['city_id'];
                        //     $OrderZoneDetails->stateId = $_POST['State']['idState'];
                        //     $OrderZoneDetails->extraKilometer = $_POST['Order']['extr_kms'];
                        //     $OrderZoneDetails->taxAmount = $_POST['Order']['luggageGST'];;
                        //     $OrderZoneDetails->outstationCharge = $_POST['outstation_charge'];
                        //     $OrderZoneDetails->createdOn= date('Y-m-d H:i:s');
                            
                        //     $OrderZoneDetails->save(false);
                        // }
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
                // //code OrderSpotDetails...
                // $order_spot_details = new OrderSpotDetails();
                // $order_spot_details->fk_tbl_order_spot_details_id_order = $model->id_order;
                // $order_spot_details->fk_tbl_order_spot_details_id_pick_drop_spots_type = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'];
                // $order_spot_details->person_name = $_POST['OrderSpotDetails']['person_name'];
                // $order_spot_details->person_mobile_number = $_POST['OrderSpotDetails']['person_mobile_number'];
                // $order_spot_details->mall_name = $_POST['OrderSpotDetails']['mall_name'];
                // $order_spot_details->store_name = $_POST['OrderSpotDetails']['store_name'];
                // $order_spot_details->business_name = $_POST['OrderSpotDetails']['business_name'];
                // if($_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_pick_drop_spots_type'] == 2){
                //     $order_spot_details->fk_tbl_order_spot_details_id_contact_person_hotel = $_POST['OrderSpotDetails']['fk_tbl_order_spot_details_id_contact_person_hotel'];
                //     $order_spot_details->hotel_name = $_POST['OrderSpotDetails']['hotel_name'];
                // }
                // if($model->travell_passenger_contact != $_POST['OrderSpotDetails']['person_mobile_number'])
                // {
                //     $order_spot_details->assigned_person = 1;
                // }
                // $order_spot_details->address_line_1 = isset($_POST['OrderSpotDetails']['address_line_1'])?$_POST['OrderSpotDetails']['address_line_1']:null;
                // $order_spot_details->area = isset($_POST['OrderSpotDetails']['area'])?$_POST['OrderSpotDetails']['area']:null;
                // $order_spot_details->pincode = isset($_POST['OrderSpotDetails']['pincode'])?$_POST['OrderSpotDetails']['pincode']:null;
                // $order_spot_details->landmark = isset($_POST['OrderSpotDetails']['landmark'])?$_POST['OrderSpotDetails']['landmark']:null;
                // $order_spot_details->building_number = isset($_POST['OrderSpotDetails']['building_number']) ? $_POST['OrderSpotDetails']['building_number']:null;
                // $order_spot_details->other_comments = isset($_POST['OrderSpotDetails']['other_comments'])?$_POST['OrderSpotDetails']['other_comments']:null;
                // $order_spot_details->building_restriction = isset($_POST['OrderSpotDetails']['building_restriction']) ? serialize($_POST['OrderSpotDetails']['building_restriction']) : null;
                // $corporate_id = CorporateDetails::find()->where(['name'=>'AIRASIA CP BLR'])->one();
                // if($_POST['Order']['corporate_id'] == $corporate_id->corporate_detail_id){
                //     $order_spot_details->hotel_booking_verification  = 1;
                //     $order_spot_details->invoice_verification = 1;
                // }
                // if($order_spot_details->save()){
                //     if(!empty($_FILES['OrderSpotDetails']['name']['booking_confirmation_file'])){
                //         $up = $employee_model->actionFileupload('booking_confirmation',$model->id_order);
                //     }
                // }

                if($_POST['OrderPaymentDetails']['payment_type'] == 'COD'){
                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'yet_to_be_confirmed', '');
                    if(!empty($_POST['total_convayance_amount'])){
                        $amount_paid = !empty($_POST['total_convayance_amount']) ? $_POST['total_convayance_amount'] : 0;
                    }else {
                        $amount_paid = !empty($_POST['Order']['luggage_price']) ? $_POST['Order']['luggage_price'] : 0;
                    }
                    $razorpay = Yii::$app->Common->createRazorpayLink($_POST['travel_email'], $_POST['cutomer_mobile'], $amount_paid, $model->id_order, $role_id);
                } else { 
                    $sms_content = Yii::$app->Common->getGeneralKioskSms($model->id_order, 'order_confirmation', '');
                }

                //code for MallInvoices
                if(!empty($_FILES['MallInvoices']['name']['invoice'])){
                    $up = $employee_model->actionFileupload('invoice',$model->id_order);
                }

                $new_order_details = Order::getorderdetails($model->id_order);
                $customer_number = $new_order_details['order']['c_country_code'].$new_order_details['order']['customer_mobile'];
                $traveller_number = $new_order_details['order']['traveler_country_code'].$new_order_details['order']['travell_passenger_contact'];
                $location_contact = Yii::$app->params['default_code'].$new_order_details['order']['location_contact_number'];
                $customers  = Order::getcustomername($model->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $flight_number = " ".$_POST['Order']['flight_number'];
                /*COde for Mail and sms start Dt:02/09/2017J*/

                    $model1['order_details']=$new_order_details;
                    $cargo_status = Yii::$app->Common->checkCargoStatus($new_order_details['corporate_details']['corporate_detail_id']);
                    //confirmation mail
                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationThirdpartyCorporatePdf($model1,'order_confirmation_corporate_pdf_template');
                    // $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'passenger_security.pdf';
                    if($cargo_status){
                        $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'cargo_security.pdf';    
                    } else {
                        $attachment_det['second_path'] = Yii::$app->params['document_root'].'basic/web/'.'passenger_security.pdf';
                    }
                    User::sendEmailExpressMultipleAttachment($model1['order_details']['order']['customer_email'],"CarterX Confirmed Order #".$new_order_details->order_number."",'order_confirmation',$model1,$attachment_det);
                    //invoice mail
                    $Invoice_attachment_det = Yii::$app->Common->genarateOrderConfirmationPdf($model1,'order_payments_pdf_template');
                    User::sendemailexpressattachment($model1['order_details']['order']['customer_email'],"Payment Done",'after_any_kind_of_payment_made',$model1,$Invoice_attachment_det);
                    Yii::$app->Common->updateConfirmationNumber($_POST['confirmation_number']);
                return $this->redirect(['update-kiosk-corporate-form', 'id' => $model->id_order, 'mobile' => $_POST['Order']['travell_passenger_contact']]);
            }
        } else {
            return $this->render('create_super_subscriber_general_order', [
                'model' => $model,
                'customer_details' => $customers,
                'employee_model' =>$employee_model,
                'regionModel' =>$model['re'],
            ]);
        }
    }

}
