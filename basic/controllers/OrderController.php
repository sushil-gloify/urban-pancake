<?php

namespace app\controllers;
use Razorpay\Api\Api;
use Yii;
use yii\helpers\Json;
use app\models\Order;
use app\models\OrderTotal;
use app\models\OrderSearch;
use app\models\Vehicle;
use app\models\VehicleSlotAllocation;
use app\models\LabourVehicleAllocation;
use app\models\Customer;
use yii\helpers\Url;
use app\models\Employee;
use app\models\SecurityQuestionImage;
use app\models\User;
use app\models\OrderImages;
use app\models\CcQueries;
use app\models\OrderItems;
use app\models\OrderWaitingCharge;
use app\models\OrderHistory;
use app\models\OrderPaymentDetails;
use app\models\OrderSpotDetails;
use app\models\OrderGroup;
use app\models\Barcode;
use app\api_v3\v3\models\OrderMetaDetails;
use app\api_v3\v3\models\OrderModifiedAmountDetails;
use app\api_v2\v2\models\OrderPromoCode;
use app\api_v2\v2\models\OrderOffers;
use app\api_v2\v2\models\OrderGroupOffer;
use app\api_v2\v2\models\Answers;
use app\models\CorporateDocuments;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use app\models\PickDropLocation;
use app\components\DelhiAirport;
use app\models\SmsType;
use app\models\OrderSmsDetails;
use app\api_v3\v3\models\OrderEditHistory;
use app\models\AirportOfOperation;
use app\models\CityOfOperation;
use app\models\OrderModificationDetails;
use app\models\TableexportSearch;
use app\models\LoginForm;
date_default_timezone_set("Asia/Kolkata");
include(Yii::$app->basePath.'/vendor/tcpdf/HTML2PDF.php');
/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends Controller
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
                    'delete' => ['POST','get'],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                            'class' => \app\components\AccessRule::className(),
                        ],
                'only' => ['index','create','view','update','delete'],
                'rules' => [
                    [
                        'actions' => ['update','index','deleteorderimage'],
                        'allow' => true,
                        'roles' => [2,3,4],
                    ],
                    [
                        'actions' => ['update','delete','create','index','view','orderpdf','deleteorderimage'],
                        'allow' => true,
                        'roles' => [1],
                    ],
                    [
                        'actions' => ['user-orders','user-order-update'],
                        'allow' => true,
                        'roles' => [1],
                    ],
                    [
                        'actions' => ['cancel-order-status', 'updatepaymentdetails', 'manage-orders'],
                        'allow' => true,
                        'roles' => [10],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Order models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OrderSearch();
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);

        if(isset($_POST['OrderSearch'])){ 
          
            $airport = Yii::$app->request->post()['OrderSearch']['fk_tbl_airport_of_operation_airport_name_id'];
            $dataProvider = $searchModel->searchbyairport(Yii::$app->request->queryParams, $airport);
        }else{
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        }
        $dataProvider->pagination->pageSize=100;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    } 

    /**
     *   get dashboard orders
    */
    public function actionGetOrdersByDashboard($key)
    {
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->searchbydashboardkey(Yii::$app->request->queryParams, $key);
          //echo '<pre>';print_r(Yii::$app->request->queryParams); die;
        $dataProvider->pagination->pageSize=100;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    }


    public function actionManageOrders($id_order){
        if($_POST){
            $bustton = $_POST['manage_order'];
            $mobile  = $_POST['mobile'];
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

            if($bustton == 'admin'){
                if($payment_method == 'COD'){
                    $message = "Hello, Order ".$order_number."  reference ".$flight_number." placed by ".$customer_name." via CarterX on ".$order_date." is confirmed for service on ".$order_date.". Payment due for the order ".$luggage_price." as mode selected is Payment on delivery.  Kindly pay the same before/on delivery via RazorPay link sent to this number. Delivery will be made on receiving complete payment only. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                }else{
                    $message = "Hello, Order ".$order_number." refernce ".$flight_number." placed by ".$customer_name." via CarterX on ".$order_date." is confirmed for service on ".$order_date." between ".$slot_scehdule.". Login to www.carterx.in with this number to track the order placed by under Manage Orders. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                }
                User::sendsms($travell_passenger_contact,$message);
                User::sendsms($location_contact_number,$message);
                return $this->redirect(['index']);
            }else if($bustton == 'corporate-kiosk'){
                return $this->redirect(['corporate-kiosk-orders']);
            }else{
                if($payment_method == 'COD'){
                    $message = "Hello, Order ".$order_number."  reference ".$flight_number." placed by ".$customer_name." via CarterX on ".$order_date." is confirmed for service on ".$order_date.". Payment due for the order display ".$luggage_price." as mode selected is Payment on delivery.  Kindly pay the same before/on delivery through payment option on app or via RazorPay link sent to this number. Delivery will be made on receiving complete payment only. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                }else{
                    $message = "Hello, Order ".$order_number." refernce ".$flight_number." placed by ".$customer_name." via CarterX on ".$order_date." is confirmed for service on ".$order_date." between ".$slot_scehdule.". Login to www.carterx.in with this number to track the order placed by under Manage Orders. For all delivery related queries Log in to your account or contact customer care on +919110635588. Thanks carterx.in";
                }
                User::sendsms($travell_passenger_contact,$message);
                User::sendsms($location_contact_number,$message);

                return $this->redirect(['kiosk-orders']);
            }
        }
    }
    public function actionCustomercare()
    {        
        //print_r(Order::getOrderdetails(1601));exit;
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('customercare', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    /**
     * Lists all Order models.
     * @return mixed
     */
    public function actionUserOrders()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->userorderssearch(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=50;
        return $this->render('user-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCorporateOrders(){
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $copId =  Yii::$app->user->identity->id_employee;
        $cId = \app\models\CorporateDetails::find()
                ->select(['corporate_detail_id'])
                ->where(['employee_id'=>$copId])
                ->one();
                //echo $cId->corporate_detail_id;

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->usercorporateorderssearch(Yii::$app->request->queryParams,$cId->corporate_detail_id);
        $dataProvider->pagination->pageSize=50;
        return $this->render('corporate-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    } 

    public function actionKioskOrders(){
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $roleId =  Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        //$cId = \app\models\CorporateDetails::find()
                //->select(['corporate_detail_id'])
                //->where(['employee_id'=>$copId])
                //->one();
                //echo $cId->corporate_detail_id;

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->userkioskorderssearch(Yii::$app->request->queryParams,$roleId);
        $dataProvider->pagination->pageSize=100;
        
        return $this->render('kiosk-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=> $clients
        ]);
    }

    public function actionCorporateKioskOrders(){
        
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $roleId =  Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $corporate_id = Yii::$app->Common->getCorporateIds(Yii::$app->user->identity->id_employee);
        
        $corporate_details = Yii::$app->Common->getCorporatesAll($corporate_id);
        // $user_corporate_id = $corporate_details->fk_corporate_id;

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->usercorporatekioskorderssearch(Yii::$app->request->queryParams,$roleId, $corporate_details,$corporate_id);

        $dataProvider->pagination->pageSize=100;
        return $this->render('corporate-kiosk-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients
        ]);
    } 


    /**
     * Displays a single Order model.
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
     * Displays a single Order model.
     * @param integer $id
     * @return mixed
     */
    public function actionViewKiosk($id)
    {
        return $this->render('view-kiosk', [
            'model' => $this->findModel($id),
        ]);
    }


    /**
     * Creates a new Order model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Order();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_order]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Creates a new Order model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateInvoice()
    {
        $model = new Order();
        $model->scenario = 'create-invoice';

        if ($model->load(Yii::$app->request->post())) {

            $data['order_number'] = $_POST['Order']['order_number'];
            $data['order_date'] = date("d-m-Y", strtotime($_POST['Order']['order_date']));
            $data['arrival_date'] = date("d-m-Y", strtotime($_POST['Order']['arrival_date']));
            $data['departure_date'] = date("d-m-Y", strtotime($_POST['Order']['departure_date']));

            $data['service_type'] = $_POST['Order']['payment_mode_excess'];
            $data['order_transfer'] = $_POST['Order']['order_transfer'];
            $data['airline_name'] = isset($_POST['Order']['airline_name']) ? $_POST['Order']['airline_name'] : "";
            $data['city_id'] = isset($_POST['Order']['city_id']) ? $_POST['Order']['city_id'] : "";
            $data['location'] = $_POST['Order']['location'];
            $data['no_of_units'] = $_POST['Order']['no_of_units'];

            $data['luggage_price'] = $_POST['Order']['luggage_price'];
            $data['modified_amount'] = $_POST['Order']['modified_amount'];
            $data['payment_method'] = ucwords($_POST['Order']['payment_method']);
            $data['travell_passenger_name'] = $_POST['Customer']['email'];
            $attachment_det = Yii::$app->Common->genarateInvoicePdf($data,'order_invoice_express');

            // $pdf_det = $this->genaratepdfexpress($data);
            User::sendemailexpressattachment($_POST['Customer']['email'],"Order Invoice - Order #".$data['order_number']."",'express_invoice_email',$data, $attachment_det);
            Yii::$app->session->setFlash('success', "Successfully created invoice");
            return $this->redirect(['create-invoice']);
        }
        return $this->render('create-invoice', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Order model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
   


    /**
     * Order edit screen for existing orders.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $order_details = Order::getorderdetails($id);
        //print_r($order_details);exit; 
        if($order_details['order']['reschedule_luggage'] == 1)
        {
            $order_details['prev_order'] = [];
            $prev_order_id = min($order_details['order']['id_order'], $order_details['order']['related_order_id']);
            if($prev_order_id != $id){
                $order_details['prev_order'] = Order::getorderdetails($prev_order_id);
            }
        }
        $order_meta_details = OrderMetaDetails::findAll(['orderId'=>$id]);
        $order_history = Order::getOrderStatusHistory($id);
        $order_question_answers = Answers::getOrderQuestionAnswer($id);
        $order_images = SecurityQuestionImage::find()->where(['order_id'=>$id])->one();
        $order_price_break = Order::getOrderPrice($id);
        $order_promocode = OrderPromoCode::find()->where(['order_id'=>$id])->one();
        $order_offers = OrderOffers::findAll(['order_id'=>$id]);
        $group_offers = OrderGroupOffer::findAll(['order_id'=>$id]);
        $order_payment_history = Order::getPaymentHistory($id);
        $waiting_details = OrderWaitingCharge::find()->where(['fk_tbl_order_waiting_charge_id_order'=>$id])->one();
        $cc_queries = Order::getCCQuries($id);
        $ccomments = Order::getCComments($id);
        $cacomments = Order::getCAComments($id);
        $id_employee=Yii::$app->user->identity->id_employee;
        $lugTypeList = \app\models\LuggageType::find()->where(['corporate_id'=>$order_details["order"]["corporate_id"]])->all();
        $lugTypeDrp = ArrayHelper::map($lugTypeList,'id_luggage_type','luggage_type');
        
        $id_employee=Yii::$app->user->identity->id_employee;
        if ($model->load(Yii::$app->request->post())) {
            // echo '<pre>';print_r($_POST);exit;
            $order_items = OrderItems::findAll(['fk_tbl_order_items_id_order'=>$id]);
            $connection = \Yii::$app->db;
            Yii::$app->db->createCommand("UPDATE tbl_order_total set price='".$_POST['Order']['luggage_price']."' where code='sub_order_amount' and fk_tbl_order_total_id_order ='".$id."'")->execute();
            Yii::$app->db->createCommand("UPDATE tbl_order_total set price='".$_POST['Order']['service_tax_amount']."' where code='service_tax_amount' and fk_tbl_order_total_id_order ='".$id."'")->execute();
            Yii::$app->db->createCommand("UPDATE tbl_order_total set price='".$_POST['Order']['insurance_price']."' where code='insurance_amount' and fk_tbl_order_total_id_order ='".$id."'")->execute();

            $saveData = [];
            $saveData['order_id'] = $id;
            $saveData['description'] = 'Order modification luggage details';
            $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
            $saveData['employee_name'] = Yii::$app->user->identity->name;;
            $saveData['module_name'] = 'Luggage Update Details';

            Yii::$app->Common->ordereditHistory($saveData);
            
            $existing_item_prices = 0;
            $new_item = 0;

            if(!empty($_POST['OrderItems'])){
                //echo "<pre>";print_r($_POST);exit;
                if($_POST['price_array']){
                    $item_price    = $_POST['price_array'];
                    $decoded_data  = json_decode($item_price);
                }else{
                    $decoded_data  = $_POST['existing_price_array'];
                }
                // $x = Yii::$app->db->createCommand("DELETE FROM tbl_order_items WHERE fk_tbl_order_items_id_order = '$id'")->execute();

                // if($_POST['existing_price_array']){
                //     $existing_price = $_POST['existing_price_array'];
                // }else{
                //     $existing_price = [];
                // }
                // echo "<pre>";print_r($decoded_data);exit;
                foreach ($_POST['OrderItems'] as $key => $items) {
                    $weight_range = (isset($items->item_id_weight_range)) ? $items->item_id_weight_range : 0;
                    $weight = $weights=\app\models\WeightRange::find()
                                    ->select(['id_weight_range','weight_considered'])
                                    ->where(['id_weight_range'=>$weight_range])
                                    ->one();
                    if(isset($weight) && ($weight->weight_considered == 5)){
                        $item_weight = 5;
                    }else if(isset($weight) && ($weight->weight_considered)){
                        $item_weight = $weight->weight_considered;
                    }else{
                        $item_weight = 0;
                    }
                    // if(isset($items->luggage_id) && isset($items->item_id_weight_range)){
                    //     $connection->createCommand('INSERT INTO  tbl_order_items(fk_tbl_order_items_id_order, fk_tbl_order_items_id_luggage_type,items_old_weight,fk_tbl_order_items_id_luggage_type_old,item_weight, fk_tbl_order_items_id_weight_range,admin_new_luggage,item_price, new_luggage, deleted_status) VALUES ('.$id.','.$items->luggage_id.','.$items->item_id_weight_range.','.$items->luggage_id.','.$item_weight.', '.$items->item_id_weight_range.',"0", '.$items->item_price.', "0", '.$items->deleted_status.')')->execute();
                    // }
                    if(isset($items['fk_tbl_order_items_id_luggage_type']) && $items['item_id'] != 0){
                        $object = is_object($decoded_data) ? $decoded_data->$key : (object) $decoded_data[$key];
                        $existing_item_price = ($object) ? $object->item_price : '';
                        $existing_item_prices += ($object) ? $object->item_price : '';
                        $connection->createCommand('UPDATE tbl_order_items SET fk_tbl_order_items_id_order='.$id.', fk_tbl_order_items_id_luggage_type='.$items['fk_tbl_order_items_id_luggage_type'].',item_weight='.$item_weight.',fk_tbl_order_items_id_weight_range='.$items['fk_tbl_order_items_id_weight_range'].', item_price = '.$existing_item_price.' WHERE id_order_item = '.$items['item_id'].'')->execute();
                    }else if(isset($items['new_luggage']) && $items['item_id'] == 0){
                        $objects = is_object($decoded_data) ? $decoded_data->$key : (object) $decoded_data[$key];
                        $new_item_price = ($objects) ? $objects->item_price : '';
                        $new_item += ($objects) ? $objects->item_price : '';
                        $connection->createCommand('INSERT INTO  tbl_order_items(fk_tbl_order_items_id_order, fk_tbl_order_items_id_luggage_type,items_old_weight,fk_tbl_order_items_id_luggage_type_old,item_weight, fk_tbl_order_items_id_weight_range,admin_new_luggage,item_price, new_luggage) VALUES ('.$id.','.$items['fk_tbl_order_items_id_luggage_type'].',0,'.$items['fk_tbl_order_items_id_luggage_type'].','.$item_weight.', '.$items['fk_tbl_order_items_id_weight_range'].',"1", '.$new_item_price.', "1")')->execute();
                    }else{
                        $objects = is_object($decoded_data) ? $decoded_data->$key : (object) $decoded_data[$key];
                        $new_item_price = ($objects) ? $objects->item_price : '';
                        $connection->createCommand('UPDATE tbl_order_items SET fk_tbl_order_items_id_order='.$id.', deleted_status = 1,item_price = '.$new_item_price.' WHERE id_order_item = '.$items['item_id'].'')->execute();
                    }
                }
            }
            if(!empty($_POST['Order'])){
                $orders = $weights=\app\models\Order::find()
                                    ->select(['luggage_price', 'payment_method'])
                                    ->where(['id_order'=>$id])
                                    ->one();
                $order_promocode = $order_promocode['promocode_value'];
                $model->id_order = $id;
                $model->admin_edit_modified = 1;
                $model->admin_modified_datetime = date('Y-m-d H:i:s');
                if($order_promocode){
                    $old_luggage_count = count($order_items);
                    $current_luggage_count = count($_POST['OrderItems']);
                    if($old_luggage_count == $current_luggage_count){
                        $percentage = ($_POST['Order']['luggage_price'] * $order_promocode) / 100;
                        $price = $_POST['Order']['luggage_price'] - $percentage;
                        $model->luggage_price   = $price + $_POST['Order']['insurance_price'] + $_POST['Order']['tax'];
                    }else{
                        $promo_apply = $existing_item_prices + $existing_item_prices * (Yii::$app->params['gst_percent']/100);
                        $promo_applied = ($promo_apply * $order_promocode) / 100;
                        $value = $promo_apply - $promo_applied;
                        $luggage_price = $value + $new_item + $new_item * (Yii::$app->params['gst_percent']/100);
                        $model->luggage_price   = round($luggage_price, 2) + $_POST['Order']['insurance_price'] + $_POST['Order']['tax'];
                    }
                }else{
                    if($_POST['Order']['modified_amount'] > 0){
                        $model->luggage_price   = $_POST['Order']['luggage_price'];
                    }else{
                        if($orders->payment_method == 'COD'){
                            $model->luggage_price   = $_POST['Order']['luggage_price'];
                        }else{
                            $luggage_price = $_POST['Order']['totalPrice'] + $_POST['Order']['totalPrice'] * (Yii::$app->params['gst_percent']/100) + $_POST['Order']['insurance_price'] + $_POST['Order']['tax'];
                            $model->luggage_price = $luggage_price;
                        }
                    }
                }
                $model->modified_amount   = $_POST['Order']['modified_amount'];
                $model->admin_modified_amount   = $_POST['Order']['modified_amount'];
                $model->insurance_price = $_POST['Order']['insurance_price'] + $_POST['Order']['insurance_price'] * 0.18;
                $model->save(false);

                $orderUpdate = new OrderModifiedAmountDetails();
                $orderUpdate->id_order = $model->id_order;
                $orderUpdate->id_employee = $id_employee;
                $orderUpdate->payment_type = 'NC';
                $orderUpdate->payment_status = 'Not paid';
                $orderUpdate->payment_gateway = '';
                $orderUpdate->transaction_id = '';
                $orderUpdate->amount_paid = ($_POST['Order']['modified_amount']) ? $_POST['Order']['modified_amount'] : 0;

                $orderUpdate->payment_receipt = '';
                $orderUpdate->date_created = date('Y-m-d H:i:s');
                $orderUpdate->value_payment_mode = 'Modified Amount';
                $orderUpdate->save(false);

            }
            //pushing into queue
            $orders = Order::findOne($id);
            if($orders->corporate_type == 1){
                Yii::$app->queue->push(new DelhiAirport([
                    'order_id' => $id,
                    'order_status' => 'modified'
                ]));
            }
            return $this->redirect(['index']);
        } else {

            Yii::$app->db->createCommand()->update('tbl_cc_queries', ['is_read' => 1], ['fk_tbl_cc_queries_id_order'=>$id,'from_admin'=>0,'iscomment'=>1])->execute();

            return $this->render('update', [
                'model' => $model,'lugTypeDrp'=>$lugTypeDrp,'order_details'=>$order_details,'order_price_break'=>$order_price_break,'waiting_details'=>$waiting_details,'order_promocode' => $order_promocode, 'order_offers' => $order_offers,'group_offers' =>$group_offers, 'order_history'=>$order_history,'order_question_answers' => $order_question_answers,'order_images' =>$order_images, 'cc_queries'=>$cc_queries,'order_payment_history'=>$order_payment_history, 'ccomments'=> $ccomments,'cacomments'=>$cacomments, 'order_meta_details' => $order_meta_details]);
        }
    }


    /**
     * Order edit screen for existing orders.
     */
    public function actionThirdpartyUpdate($id)
    {
        $model = $this->findModel($id);
        $order_details = Order::getorderdetails($id);
        //print_r($order_details);exit; 
        if($order_details['order']['reschedule_luggage'] == 1)
        {
            $order_details['prev_order'] = [];
            $prev_order_id = min($order_details['order']['id_order'], $order_details['order']['related_order_id']);
            if($prev_order_id != $id){
                $order_details['prev_order'] = Order::getorderdetails($prev_order_id);
            }
        }
        $order_meta_details = OrderMetaDetails::findAll(['orderId'=>$id]);
        $order_history = Order::getOrderStatusHistory($id);
        $order_question_answers = Answers::getOrderQuestionAnswer($id);
        $order_images = SecurityQuestionImage::find()->where(['order_id'=>$id])->one();
        $order_price_break = Order::getOrderPrice($id);
        $order_promocode = OrderPromoCode::find()->where(['order_id'=>$id])->one();
        $order_offers = OrderOffers::findAll(['order_id'=>$id]);
        $group_offers = OrderGroupOffer::findAll(['order_id'=>$id]);
        $order_payment_history = Order::getPaymentHistory($id);
        $waiting_details = OrderWaitingCharge::find()->where(['fk_tbl_order_waiting_charge_id_order'=>$id])->one();
        $cc_queries = Order::getCCQuries($id);
        $ccomments = Order::getCComments($id);
        $cacomments = Order::getCAComments($id);
        $id_employee=Yii::$app->user->identity->id_employee;
        $lugTypeList = \app\models\LuggageType::find()->where(['corporate_id'=>$order_details["order"]["corporate_id"]])->all();
        $lugTypeDrp = ArrayHelper::map($lugTypeList,'id_luggage_type','luggage_type');
        
        $id_employee=Yii::$app->user->identity->id_employee;
        
        Yii::$app->db->createCommand()->update('tbl_cc_queries', ['is_read' => 1], ['fk_tbl_cc_queries_id_order'=>$id,'from_admin'=>0,'iscomment'=>1])->execute();

        return $this->render('thirdpartyUpdate', [
            'model' => $model,'lugTypeDrp'=>$lugTypeDrp,'order_details'=>$order_details,'order_price_break'=>$order_price_break,'waiting_details'=>$waiting_details,'order_promocode' => $order_promocode, 'order_offers' => $order_offers,'group_offers' =>$group_offers, 'order_history'=>$order_history,'order_question_answers' => $order_question_answers,'order_images' =>$order_images, 'cc_queries'=>$cc_queries,'order_payment_history'=>$order_payment_history, 'ccomments'=> $ccomments,'cacomments'=>$cacomments, 'order_meta_details' => $order_meta_details]);
        
    }

    /**
     * Order edit screen for existing orders.
     */
    public function actionKioskOrderUpdate($id)
    {//echo "<pre>";print_r($_POST);exit;
        $model = $this->findModel($id);
        $order_details = Order::getorderdetails($id);
        $waiting_details = OrderWaitingCharge::find()->where(['fk_tbl_order_waiting_charge_id_order'=>$id])->one();
        $order_meta_details = OrderMetaDetails::findAll(['orderId'=>$id]);
        $order_price_break = Order::getOrderPrice($id);
        $order_payment_history = Order::getPaymentHistory($id);
        $order_offers = OrderOffers::findAll(['order_id'=>$id]);
        $group_offers = OrderGroupOffer::findAll(['order_id'=>$id]);
        $ccomments = Order::getCComments($id);
        $cacomments = Order::getCAComments($id);
        if($order_details['order']['reschedule_luggage'] == 1)
        {
            $order_details['prev_order'] = [];
            $prev_order_id = min($order_details['order']['id_order'], $order_details['order']['related_order_id']);
            if($prev_order_id != $id){
                $order_details['prev_order'] = Order::getorderdetails($prev_order_id);
            }
        }
        $order_promocode = OrderPromoCode::find()->where(['order_id'=>$id])->one();

        $id_employee=Yii::$app->user->identity->id_employee;
        //print_r($waiting_details);exit;
        if ($model->load(Yii::$app->request->post())) {
            //echo '<pre>';print_r($_POST);exit;
            $order_items = OrderItems::findAll(['fk_tbl_order_items_id_order'=>$id]);
            $connection = \Yii::$app->db;
            if(!empty($_POST['OrderItems'])){
                //echo "<pre>";print_r($_POST);exit;
                if($_POST['price_array']){
                    $item_price    = $_POST['price_array'];
                    $decoded_data  = json_decode($item_price);
                }else{
                    $decoded_data  = [];
                }
                if($_POST['existing_price_array']){
                    $existing_price = $_POST['existing_price_array'];
                }else{
                    $existing_price = [];
                }
                foreach ($_POST['OrderItems'] as $key => $items) {
                    if(!empty($items['fk_tbl_order_items_id_luggage_type']) && !empty($items['fk_tbl_order_items_id_weight_range'])){
                        if(isset($items['fk_tbl_order_items_id_luggage_type']) && $items['item_id'] != 0){
                            $existing_item_price = ($existing_price) ? $existing_price[$key]['item_price'] : '';
                            $connection->createCommand('UPDATE tbl_order_items SET fk_tbl_order_items_id_order='.$id.', fk_tbl_order_items_id_luggage_type='.$items['fk_tbl_order_items_id_luggage_type'].',fk_tbl_order_items_id_weight_range='.$items['fk_tbl_order_items_id_weight_range'].', item_price = '.$existing_item_price.' WHERE id_order_item = '.$items['item_id'].'')->execute();
                        }else{
                            $new_item_price = ($decoded_data) ? $decoded_data[$key]->item_price : '';
                            $connection->createCommand('INSERT INTO  tbl_order_items(fk_tbl_order_items_id_order, fk_tbl_order_items_id_luggage_type, fk_tbl_order_items_id_weight_range,new_luggage,item_price) VALUES ('.$id.','.$items['fk_tbl_order_items_id_luggage_type'].', '.$items['fk_tbl_order_items_id_weight_range'].',"1", '.$new_item_price.')')->execute();
                        }
                    }else{
                        $connection->createCommand('UPDATE tbl_order_items SET fk_tbl_order_items_id_order='.$id.', deleted_status = 1 WHERE id_order_item = '.$items['item_id'].'')->execute();
                    }
                }
            }
            if(!empty($_POST['Order'])){
                $model->id_order = $id;
                $model->luggage_price   = $_POST['Order']['luggage_price'];
                $model->insurance_price = $_POST['Order']['insurance_price'];
                $model->save(false);
            }
            return $this->redirect(['kiosk-orders']);
        } else {
            Yii::$app->db->createCommand()->update('tbl_cc_queries', ['is_read' => 1], ['fk_tbl_cc_queries_id_order'=>$id,'fk_tbl_cc_queries_id_employee'=>$id_employee,'from_admin'=>0,'iscomment'=>1])->execute();

            return $this->render('kiossk-update', [
                'model' => $model,'order_details'=>$order_details, 'order_promocode' => $order_promocode, 'waiting_details' => $waiting_details, 'order_price_break' => $order_price_break, 'order_payment_history' => $order_payment_history, 'order_offers' => $order_offers, 'group_offers' => $group_offers, 'ccomments'=> $ccomments,'cacomments'=>$cacomments, 'order_meta_details' => $order_meta_details]);
        }
    }


    /*
     * This Method handles to change the success stories status
     */
    public function actionCancelOrderStatus($id) {
        if($id){
            $this->cancelorder($id);
            $data['order_details'] = Order::getorderdetails($id);
            //echo "<pre>";print_r($data['order_details']);exit;
            if($data['order_details']['order']['corporate_id'] == 0){
                $orderby= Customer::find()->select('mobile,name,fk_tbl_customer_id_country_code')
                                      ->joinwith(['customercountrycode'=>function($q){
                                                $q->from('tbl_country_code c1');
                                            }])
                                      ->where(['id_customer'=>$data['order_details']['order']['fk_tbl_order_id_customer']])->one();
                if($orderby){
                    $customer_name = $orderby->name;
                }else{
                    $customer_name = '';
                }                                      
            }else{
                if($data['order_details']['order']['corporate_type']==1){
                    $customer_name =  $data['order_details']['order']['travell_passenger_name'];
                } else{
                    $customer_name =  $data['order_details']['order']['customer_name'];
                }
            }
            // if($data['order_details']['order']['service_type'] == 1){
            //     $msg_cancel = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." has been cancelled. Please login to your account at www.carterx.in for any applicable refund details. Look forward to serving you soon. Thank you for choosing us.  -CarterX";
            // }else{
            //     $msg_cancel = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." has been cancelled. Please login to your account at www.carterx.in for any applicable refund details";
            // }

            if(!($data['order_details']['order']['corporate_type']==1)){
                $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
            }else{
                $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
            }

            $customers = Customer::findOne(['mobile' => $data['order_details']['order']['travell_passenger_contact']]);      
            User::sendemail($customers['email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'cancellation_email',$data);

           /* $pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
            User::sendemailasattachment($customers['email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data, $pdf_det);*/


            Yii::$app->session->setFlash('msg', Yii::t('app',"Order Successfully Cancelled"));
            return $this->redirect(['kiosk-orders']);
        }else{
            Yii::$app->session->setFlash('error', \Yii::t('app', 'Could not cancel the order, Please try again later.'));
            return $this->redirect(['kiosk-orders']);
        }
        return $this->redirect(['kiosk-orders']);
    }

    public function actionUpdateDeliveryService($id)
    {
        // print_r($_POST['corporate_admin']);exit;
         //print_r($_POST['Order']['dservice_type']);exit;
        $model = $this->findModel($id);
        $model->dservice_type=$_POST['Order']['dservice_type'];
        $model->save(false);

        $saveData = [];

        $saveData['order_id'] = $id;
        $saveData['description'] = 'Order modification service type';
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
        $saveData['employee_name'] = Yii::$app->user->identity->name;;
        $saveData['module_name'] = 'Delivery Service Type';

        Yii::$app->Common->ordereditHistory($saveData);
        if (isset($_POST['corporate_admin'])) {
            return $this->redirect(['thirdparty-update', 'id' => $id]);   
        }else{
            return $this->redirect(['update', 'id' => $id]);
        }
    }

    public function actionUpdateOrderDate($id){
        $model = $this->findModel($id);
        $model->order_date=$_POST['Order']['order_date'];
        $model->save(false);

        $saveData = [];

        $saveData['order_id'] = $id;
        $saveData['description'] = 'Order modification date of service';
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
        $saveData['employee_name'] = Yii::$app->user->identity->name;;
        $saveData['module_name'] = 'Date of Service';

        Yii::$app->Common->ordereditHistory($saveData);
        if (isset($_POST['corporate_admin'])) {
            return $this->redirect(['thirdparty-update', 'id' => $id]);   
        }else{
            return $this->redirect(['update', 'id' => $id]);
        }
        // return $this->redirect(['update', 'id' => $id]);
    }

    public function actionUpdateExcessPaymentMode($id)
    {
        $model = $this->findModel($id);
        $order_details = Order::getorderdetails($id);
        $customer_number = $order_details['order']['c_country_code'].$order_details['order']['customer_mobile'];
        
        $mobile_number=$order_details['order']['c_country_code'].$model->travell_passenger_contact;
        $customers  = Order::getcustomername($model->travell_passenger_contact);
        $customer_name = ($customers) ? $customers->name : '';
        if($_POST['total_excess_bag_amount']!=0 && $_POST['total_excess_bag_amount']!='' && $_POST['payment_mode_excess']=='Cash' || $_POST['payment_mode_excess']=='Card' || $_POST['payment_mode_excess']=='NC' || $_POST['payment_mode_excess']=='NA' ){
        $model->excess_bag_amount=$_POST['total_excess_bag_amount'];
        $model->payment_mode_excess=$_POST['payment_mode_excess'];
        $model->save();
        }
         if($order_details['order']['order_transfer'] != 1){ 
          if($_POST['total_excess_bag_amount'] && $_POST['payment_mode_excess']=='Cash' || $_POST['payment_mode_excess']=='Card'){

            //print_r("expression");exit;
          $message="Dear Customer, Excess weight has been purchased against your Order #".$model->order_number." placed by ".$customer_name." during pick up. Rs ". $_POST['total_excess_bag_amount'] . " has been levied and 'Collected' by " . $_POST['payment_mode_excess'] . " by  Carter-X Admin. Please treat this sms as confirmation and receipt for the payment made " .PHP_EOL. " Thanks carterx.in";

          
            User::sendsms($mobile_number,$message);  
            User::sendsms($customer_number,$message);
       
        }elseif($_POST['payment_mode_excess']=='NC') 
        {
          $message="Dear Customer, Excess weight has been purchased against your Order #".$model->order_number. " Reference " .$model->flight_number. " placed by AirAsia during pickup. Rs." .$_POST['total_excess_bag_amount']. " has been levied and 'Not collected'. Please treat this sms as a gentle reminder and make payment to AirAsia personnel directly. Thank you  CarterX.in ";

           if($mobile_number){
            User::sendsms($mobile_number,$message);  
        }
        if($customer_number){
            User::sendsms($customer_number,$message);
        }
        }

       }
       
        
        return $this->redirect(['update', 'id' => $id]);
    }
    public function actionUpdateExcessBagAmount($id)
    {
      // print_r($_POST);exit;
        if($_POST['luggage_modify'])
        {
                foreach ($_POST['luggage_modify'] as $key => $value) {

                   
                           if(!array_key_exists('luggage_type',$value)){
                            $item_order_id =$value['id_order_item'];
                                $result = Yii::$app->db->createCommand("UPDATE tbl_order_items set deleted_status=1 where id_order_item='".$item_order_id."'")->execute();
                         
                           }else{
                              
                               if(!array_key_exists('id_order_item',$value)){

                                $order_item_model = new OrderItems();
                                $order_item_model->bag_type =$value["bag_type"];
                                $order_item_model->fk_tbl_order_items_id_luggage_type =$value["luggage_type"];
                                $order_item_model->bag_weight =$value["weight_range_type"];
                                $order_item_model->excess_weight =$value["excess_bag"];
                                $order_item_model->new_luggage =1;
                                $order_item_model->fk_tbl_order_items_id_order=$id;
                                $order_item_model->save(false);

                                //echo "insert";

                               }else{

                                $update_item_model =OrderItems::findOne($value['id_order_item']);
                                $update_item_model->fk_tbl_order_items_id_luggage_type =$value["luggage_type"];
                                $update_item_model->excess_weight=$value["excess_bag"];
                                $update_item_model->bag_type=$value["bag_type"];
                                $update_item_model->save(false);
                                // echo "update";


                               }

                           }

                  //$i++;  # code...
                }
            }
        $model = $this->findModel($id);
         $order_details = Order::getorderdetails($id);
            $customer_number = $order_details['order']['c_country_code'].$order_details['order']['customer_mobile'];
           // print_r($customer_number);exit;
        
         $mobile_number=$order_details['order']['c_country_code'].$model->travell_passenger_contact;
        if($_POST['total_excess_bag_amount']!=0 && $_POST['total_excess_bag_amount']!='' && $_POST['payment_mode_excess']=='Cash' || $_POST['payment_mode_excess']=='Card' || $_POST['payment_mode_excess']=='NC' || $_POST['payment_mode_excess']=='NA' ){
        $model->excess_bag_amount=$_POST['total_excess_bag_amount'];
        $model->payment_mode_excess=$_POST['payment_mode_excess'];
        $model->save();
        }
          if($_POST['total_excess_bag_amount'] && $_POST['payment_mode_excess']=='Cash' || $_POST['payment_mode_excess']=='Card'){

            //print_r("expression");exit;
          $message="Dear Customer, Excess weight has been purchased against your Order #".$model->order_number." placed by AirAsia during pick up. Rs ". $_POST['total_excess_bag_amount'] . " has been levied and 'Collected' by " . $_POST['payment_mode_excess'] . " by  Carter-X Admin. Please treat this sms as confirmation and receipt for the payment made " .PHP_EOL. " Thanks carterx.in";

          
            User::sendsms($mobile_number,$message);  
            User::sendsms($customer_number,$message);
       
        }elseif($_POST['payment_mode_excess']=='NC') 
        {
          $message="Dear Customer, Excess weight has been purchased against your Order #".$model->order_number. " Reference " .$model->flight_number. " placed by AirAsia during pickup. Rs." .$_POST['total_excess_bag_amount']. " has been levied and 'Not collected'. Please treat this sms as a gentle reminder and make payment to AirAsia personnel directly. Thank you  CarterX.in ";

           if($mobile_number){
            User::sendsms($mobile_number,$message);  
        }
        if($customer_number){
            User::sendsms($customer_number,$message);
        }
        }
        //pushing into queue
        $orders = Order::findOne($id);
        if($orders->corporate_type == 1){
            Yii::$app->queue->push(new DelhiAirport([
                'order_id' => $id,
                'order_status' => 'modified'
            ]));
        }
        return $this->redirect(['update', 'id' => $id]);
    }
    /**
     * Order edit screen for existing orders.
     */
    public function actionUserOrderUpdate($id)
    {
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $model = $this->findModel($id);
        $order_details = Order::getorderdetails($id);
        //$order_item_details = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$id."'")->queryAll();
        //$order_details['order_items']=$order_item_details;
        $order_history = Order::getOrderStatusHistory($id);
        $order_question_answers = Answers::getOrderQuestionAnswer($id);
        $order_price_break = Order::getOrderPrice($id);
        $order_images = SecurityQuestionImage::find()->where(['order_id'=>$id])->one();
        $order_payment_history = Order::getPaymentHistory($id);
        $waiting_details = OrderWaitingCharge::find()->where(['fk_tbl_order_waiting_charge_id_order'=>$id])->one();
        $cc_queries = Order::getCCQuries($id);
        $ccomments = Order::getCComments($id);
        $cacomments = Order::getCAComments($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_order]);
        } else {
            if($role_id == 7){
                return $this->render('user-order-update', [
                    'model' => $model,'order_details'=>$order_details,'order_price_break'=>$order_price_break,'waiting_details'=>$waiting_details,'order_history'=>$order_history,'cc_queries'=>$cc_queries,'order_payment_history'=>$order_payment_history]);
            }else{

                $status = Order::getcustomerstatus($order_details['order']['id_order_status'], $order_details['order']['service_type'],$id);
                if($order_details['order']['reschedule_luggage'] == 1){
                    $order_details['order']['customer_id_order_status'] = $status['customer_id_order_status']==6 ? '3': $status['customer_id_order_status'];
                    $order_details['order']['customer_order_status_name'] = $status['status_name'] == 'Assigned'? 'Open' : $status['status_name'];
                }else{
                    $order_details['order']['customer_id_order_status'] = $status['customer_id_order_status'];
                    $order_details['order']['customer_order_status_name'] = $status['status_name'];
                }


                Yii::$app->db->createCommand()->update('tbl_cc_queries', ['is_read' => 1], ['fk_tbl_cc_queries_id_order'=>$id,'from_admin'=>1,'iscomment'=>1])->execute();

                return $this->render('corporate-order', [
                    'model' => $model,'order_details'=>$order_details,'order_price_break'=>$order_price_break,'waiting_details'=>$waiting_details,'order_history'=>$order_history,'order_question_answers' => $order_question_answers,'cc_queries'=>$cc_queries,'order_payment_history'=>$order_payment_history,'ccomments'=>$ccomments, 'cacomments'=>$cacomments, 'order_images' => $order_images]);

            }
        }
    }


    public function cancelindividualorder($id)
    {
        $info_order = Order::getorderdetails($id);
        if(!empty($info_order['order']['id_slot'])){
            $order_details=Yii::$app->db->createCommand("SELECT o.*, s.* FROM tbl_order o JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot WHERE o.id_order='".$id."'")->queryOne();
            
            $order_total = Yii::$app->db->createCommand("SELECT ot.* FROM tbl_order_total ot WHERE ot.fk_tbl_order_total_id_order='".$id."' AND ot.code='sub_order_amount'")->queryOne();
        
            $cancellation_allowed_status = [1,2,3,4,5,6,7];
            if(in_array($order_details['fk_tbl_order_status_id_order_status'], $cancellation_allowed_status)){
                $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status=21,order_status='Cancelled' where id_order='".$order_details['id_order']."'")->execute();
                $order_data = [ 'fk_tbl_order_history_id_order'=>$order_details['id_order'],
                    'from_tbl_order_status_id_order_status'=>$order_details['fk_tbl_order_status_id_order_status'],
                    'from_order_status_name'=>$order_details['order_status'],
                    'to_tbl_order_status_id_order_status'=>21,
                    'to_order_status_name'=>'Cancelled',
                    'date_created'=> date('Y-m-d H:i:s')
                    ];

                $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$order_data)->execute();

                    // from airport=>arrival_date
                    // to airport=>order date
                
                if($order_details['corporate_type'] == 3){
                    $date = ($order_details['arrival_date']) ? $order_details['arrival_date'] : date('Y-m-d', strtotime($order_details['order_date']));
                    $time = ($order_details['meet_time_gate']) ? $order_details['meet_time_gate'] : '05:00:00';
                    $order_date = (in_array($order_details['fk_tbl_order_id_slot'], [1,2,3,6,7,9])) ? date('Y-m-d', strtotime($order_details['order_date']))." ".$order_details['slot_start_time'] : $date." ".$time;

                }else{
                    $order_date = (in_array($order_details['fk_tbl_order_id_slot'], [1,2,3,7,9,6])) ? date('Y-m-d', strtotime($order_details['order_date']))." ".$order_details['slot_start_time'] : $order_details['arrival_date']." ".$order_details['meet_time_gate'];

                }
                
                $current_date = date('Y-m-d H:i:s');
                
                $date1 = $current_date;
                $date2 = $order_date;

                $timestamp1 = strtotime($date1);
                $timestamp2 = strtotime($date2);
                $diff = abs($timestamp2 - $timestamp1)/(60*60); 
                
                //$order_date = (in_array($order_details['fk_tbl_order_id_slot'], [1,2,3,6])) ? $order_details['date_created']." ".$order_details['slot_start_time'] : $order_details['arrival_date']." ".$order_details['meet_time_gate'];

                //$diff =((strtotime($order_date) - strtotime(date('Y-m-d H:i:s'))))/3600;
                //print_r($order_details['created_by']);echo "<br/>";
                //print_r($order_date);echo '<br/>';print_r(date('Y-m-d H:i:s'));echo '<br/>';
                // $diff =((strtotime(date('Y-m-d H:i:s') - strtotime($order_date) )))/3600;
                //print_r($diff);exit;
                if($order_details['amount_paid']){
                    if($order_details['delivery_type'] == 2){
                        $booking_date = date('Y-m-d', strtotime('-3 days', strtotime($order_details['order_date'])));
                        if($date1 <= $booking_date){
                                $cancellation_charge = $order_total['price'];
                                $cancellation_policy = '100% cancellation charge';
                        }else{
                                $cancellation_charge = 0;
                                $cancellation_policy = 'No cancellation charge';
                        }
                    }else{
                        if($date1 <= $date2){
                            if($diff > 4)
                            {
                                $cancellation_charge = $order_total['price'];
                                $cancellation_policy = '100% cancellation charge';
                                
                            }
                            else if($diff<4 && $diff>2)
                            {
                                $cancellation_charge = $order_total['price']*0.5;
                                $cancellation_policy = '50% cancellation charge'; 
                            }
                            else if($diff<2)
                            {
                                $cancellation_charge = $order_total['price']*0.5;
                                $cancellation_policy = '50% cancellation charge';   
                            }
                        }else{
                            $cancellation_charge = 0;
                            $cancellation_policy = 'No cancellation charge';
                        }
                    }
                }else{
                    $cancellation_charge = 0;
                    $cancellation_policy = 'No cancellation charge';
                }
                                
                // if($diff > 4)
                // {
                //     $cancellation_charge = 0;
                //     $cancellation_policy = 'No cancellation charge';
                // }
                // else if($diff<4 && $diff>2)
                // {
                //     $cancellation_charge = $order_total['price']*0.5;
                //     $cancellation_policy = '50% cancellation charge';
                // }
                // else if($diff<2)
                // {
                //     if(($order_details['corporate_type'] == 1) && !($order_details['payment_method'] == 'COD')){
                //         $cancellation_charge = 0;
                //         $cancellation_policy = 'No cancellation charge';
                //     }
                //     else{
                //         $cancellation_charge = $order_total['price'];
                //         $cancellation_policy = '100% cancellation charge';
                //     }
                // }
                // else if($diff<0)
                // {
                //     $cancellation_charge = $order_total['price'];
                //     $cancellation_policy = '100% cancellation charge';
                // }
                $order_total_data = [
                                            'fk_tbl_order_total_id_order'=>$order_details['id_order'],
                                            'title'=>'Cancellation Charge('.$cancellation_policy.')',
                                            'price'=>$cancellation_charge,
                                            'code'=>'cancellation',
                                ];
                
                $order_total = Yii::$app->db->createCommand()->insert('tbl_order_total',$order_total_data)->execute();
                if($cancellation_charge){
                    $modified_amount = "-".$cancellation_charge;
                }else{
                    $modified_amount = 0;
                }
                $result = Yii::$app->db->createCommand("UPDATE tbl_order set modified_amount='".$modified_amount."' where id_order='".$order_details['id_order']."'")->execute();

                //pushing into queue
                $orders = Order::findOne($id);

                if($orders->corporate_type == 1){
                    Yii::$app->queue->push(new DelhiAirport([
                        'order_id' => $order_details['id_order'],
                        'order_status' => 'cancelled'
                    ]));
                }
                $data['order_details'] = Order::getorderdetails($order_details['id_order']);
                $data['order_price_break'] = Order::getOrderPrice($order_details['id_order']);
                $cancellation_amount =array_column(array_filter($data['order_price_break'], function($el) { return $el['code']=='cancellation'; }),'price');
                $cancellation_refund_amount = $data['order_details']['order']['amount_paid'] - $cancellation_amount[0];
                
                $data['order_details']['order']['cancellation_refund_amount'] =empty($cancellation_refund_amount1) ? 0 : $cancellation_refund_amount1[0];
            }
        } else {
            $cancellation_allowed_status = [1,2,3,4,5,6,7];
            if(in_array($info_order['order']['id_order_status'], $cancellation_allowed_status)){
                $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status=21,order_status='Cancelled' where id_order='".$info_order['order']['id_order']."'")->execute();
                $order_data = [ 'fk_tbl_order_history_id_order'=>$info_order['order']['id_order'],
                    'from_tbl_order_status_id_order_status'=>$info_order['order']['id_order_status'],
                    'from_order_status_name'=>$info_order['order']['order_status'],
                    'to_tbl_order_status_id_order_status'=>21,
                    'to_order_status_name'=>'Cancelled',
                    'date_created'=> date('Y-m-d H:i:s')
                    ];

                $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$order_data)->execute();
            }
        }
        return true;

    }


    public function cancelorder($id)
    {
        $order_details=Yii::$app->db->createCommand("SELECT o.*, s.* FROM tbl_order o JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot WHERE o.id_order='".$id."'")->queryOne();
        $order_ids=[$id];
        $order_details1 = array();
        if(isset($order_details['round_trip']) && ($order_details['round_trip'] == 1))
        {
            $order_details1=Yii::$app->db->createCommand("SELECT o.*, s.* FROM tbl_order o JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot WHERE o.id_order='".$order_details['related_order_id']."'")->queryOne();
            array_push($order_ids, $order_details['related_order_id']);
        }
        foreach ($order_ids as $id) { 
            /*$iscancel = Order::getIscancelled($id);
            if($iscancel && $order_details['fk_tbl_order_status_id_order_status']!=21){
                $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status=21,order_status='Cancelled' where id_order='".$id."'")->execute();
                $order_data = [ 'fk_tbl_order_history_id_order'=>$id,
                    'from_tbl_order_status_id_order_status'=>$order_details['fk_tbl_order_status_id_order_status'],
                    'from_order_status_name'=>$order_details['order_status'],
                    'to_tbl_order_status_id_order_status'=>21,
                    'to_order_status_name'=>'Cancelled',
                    'date_created'=> date('Y-m-d H:i:s')
                   ];

                $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$order_data)->execute();
            }else{*/
                $this->cancelindividualorder($id);
            //}
        }       

      return true;
    }


    public function removecancel($id)
    {
        $cancellationrecord = OrderTotal::find()->where(['fk_tbl_order_total_id_order'=>$id, 'code'=>'cancellation'])->one();
        if($cancellationrecord){
          $cancellationrecord->delete();
        }
        return true;
    }


    /**
     * Order edit screen for existing orders.
     * function to change the status only for super admin. 
     */
    public function actionUpdatestatus($id)
    {
       
        $_POST = Yii::$app->request->post();
        if(!empty($_POST['id_order_status']))
        {
            if($_POST['id_order_status'] == 21){
                $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();
                if(in_array($last_order_status['to_tbl_order_status_id_order_status'], array(18,19,21,28,31,30))){
                    Yii::$app->session->setFlash('error', "Status can't be change, because off old status is '".$last_order_status['to_order_status_name']."'");
                } else {
                    $this->cancelorder($id);
                    $data['order_details'] = Order::getorderdetails($id);
                    if(!empty($data['order_details']['order']['confirmation_number'])){
                        Yii::$app->Common->setSubscriptionStatus($data['order_details']['order']['confirmation_number']);
                    }
                }
            } else if($_POST['id_order_status'] == 30){
                $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();

                if(in_array($last_order_status['to_tbl_order_status_id_order_status'], array(18,19,21,28,31,30))){
                    Yii::$app->session->setFlash('error', "Status can't be change, because off old status is '".$last_order_status['to_order_status_name']."'");
                     
                } else {
                    $this->orderCancellationWithRefund($id,$_POST);
                    $data['order_details'] = Order::getorderdetails($id);
                    if(!empty($data['order_details']['order']['confirmation_number'])){
                        Yii::$app->Common->setSubscriptionStatus($data['order_details']['order']['confirmation_number']);
                    }
                    // ------ start subscription setup------
                        $data['order_details']['subscription_details'] = Yii::$app->Common->getSubscriptionDetails($data['order_details']['order']['confirmation_number']);
                        if(!empty($data['order_details']['subscription_details'])){
                            $sms_data = array("confirmation_number" => !empty($data['order_details']['subscription_details']['confirmation_number']) ? strtoupper($data['order_details']['subscription_details']['confirmation_number']) : "",
                                    "subscription_name" => !empty($data['order_details']['subscription_details']['subscriber_name']) ? strtoupper($data['order_details']['subscription_details']['subscriber_name']) : "",
                                    "paid_amount" => !empty($data['order_details']['order']['amount_paid']) ? $data['order_details']['order']['amount_paid'] : 0,
                                    "pay_amount" => !empty($data['order_details']['subscription_details']['paid_amount']) ? $data['order_details']['subscription_details']['paid_amount'] : 0,
                                    "refund_amount"=> !empty($data['order_details']['order']['refund_amount']) ? $data['order_details']['order']['refund_amount'] : 0);

                            $mobile_arr = array_unique(array($data['order_details']['order']['customer_mobile'],$data['order_details']['order']['travell_passenger_contact'],$data['order_details']['subscription_details']['primary_contact'],$data['order_details']['subscription_details']['secondary_contact']));

                            // customer and super subscriber
                            $customer_email = !empty($data['order_details']['order']['travell_passenger_email']) ? $data['order_details']['order']['travell_passenger_email'] : (!empty($data['order_details']['order']['customer_email']) ? $data['order_details']['order']['customer_email'] : "");

                            $emailSubscriberTo = array_unique(array($data['order_details']['subscription_details']['primary_email'],
                                $data['order_details']['subscription_details']['secondary_email']
                            ));

                            $emailTokenTo = !empty($data['order_details']['corporate_details']['default_email']) ? array($data['order_details']['corporate_details']['default_email']) : "";

                            $emailCustomerCareTo = array(Yii::$app->params['customer_email']);

                        }
                    //------ start subscription setup------
                    // Cancellation with refund email invoice and sms
                        if(($_POST['id_order_status'] == 30) && (!empty($data['order_details']['order']['confirmation_number']))){
                            // cancellation sms with refund
                            Yii::$app->Common->subscriptionSmsSent("cancellations_with_refund",$sms_data,array_filter($mobile_arr));
                            // confirmation update email
                            $file_name = "subscription_cancel_confirmation_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                            $data['order_details']['order']['refund'] = 1;
                            $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data,'subscription_cnf_template',$file_name);
                            User::sendcnfemail($customer_email,"CarterX Cancellation Confirmed Subscription #".strtoupper($data['order_details']['subscription_details']['subscriber_name']),'sub_cnf_email',$data,$attachment_cnf,array_filter(array_unique($emailSubscriberTo)));
                            // Token confirmation email
                            $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'token_confirmation_pdf_template');
                            User::sendcnfemail($customer_email,"CarterX Cancellation Subscription Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_det,array_filter(array_unique(array_merge($emailTokenTo,$emailCustomerCareTo))),false);
                            
                            if($data['order_details']['order']['refund_amount'] != 0){
                                // Cancellation with refund
                                $file_name_ = "subscription_cancel_refund_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                                $attachment_cancel = Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data, "subscription_cancellation_with_refund_invoice",$file_name_);
                                User::sendcnfemail($customer_email,"CarterX Cancellation With Refund Invoice - Subscription Order  #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_cancel,array_filter(array_unique($emailSubscriberTo)));
                            }
                        }
                    // Cancellation with refund email invoice and sms
                }
                if(isset($_POST['kiosk_status'])){
                    return $this->redirect(['kiosk-order-update', 'id' => $id]);
                }else if(isset($_POST['thirdparty_status'])){
                    return $this->redirect(['thirdparty-update', 'id' => $id]);
                }else{
                    return $this->redirect(['update', 'id' => $id]);
                }
            } else {
                  $this->removecancel($id);
                  $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();

                  $model = new OrderHistory();
                  $model->fk_tbl_order_history_id_order = $id;
                  $model->from_tbl_order_status_id_order_status = $last_order_status['to_tbl_order_status_id_order_status'];
                  $model->from_order_status_name = $last_order_status['to_order_status_name'];
                  $model->to_tbl_order_status_id_order_status = $_POST['id_order_status'];
                  $to_order_status_name = Yii::$app->db->createCommand("select os.status_name from tbl_order_status os where os.id_order_status='".$model->to_tbl_order_status_id_order_status."' ")->queryOne();
                  $model->to_order_status_name = $to_order_status_name['status_name'];
                  $model->date_created = date('Y-m-d H:i:s');
                  $model->save();

                  $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status='".$_POST['id_order_status']."',order_status='".$model->to_order_status_name."' where id_order='".$id."'")->execute();
                  
            }

            $data['order_details'] = Order::getorderdetails($id);
            $data['order_price_break'] = Order::getOrderPrice($id);

           //echo "<pre>"; print_r($data['order_details']);exit;

            $travell_passenger_contact = $data['order_details']['order']['travell_passenger_contact'];
            $istravel_person = $data['order_details']['order']['travel_person'];
            $corp_ref_text = ($data['order_details']['order']['corporate_id'] == 0) ? "": " ".$data['order_details']['order']['flight_number'];

            if($data['order_details']['order']['reschedule_luggage']==1)
            {
               $prev_order_id = min($data['order_details']['order']['id_order'], $data['order_details']['order']['related_order_id']);
               $new_order_from_rescheduld = max($data['order_details']['order']['id_order'], $data['order_details']['order']['related_order_id']);
               $orderDet = Order::getOrderdetails($prev_order_id);
               $istravel_person = $orderDet['order']['travel_person'];
               $travell_passenger_contact = $orderDet['order']['travell_passenger_contact'];

            }


            $customer_number = $data['order_details']['order']['c_country_code'].$data['order_details']['order']['customer_mobile'];
            $traveller_number = $data['order_details']['order']['traveler_country_code'].$travell_passenger_contact;
            $location_contact = Yii::$app->params['default_code'].$data['order_details']['order']['location_contact_number'];

            /* SMS - Status - start */
            if($_POST['id_order_status'] == 8)
            {

                if($data['order_details']['order']['order_modified'] == 1){

                    if($data['order_details']['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_WithModification', '');
                    }
                    /*User::sendsms($customer_number,"Dear Customer, Your Order #".$data['order_details']['order']['order_number'].$corp_ref_text." picked up has under gone Order Modification. Further details can be found under Manage Orders of your account.".PHP_EOL." Thanks carterx.in");

                    $picked_msg = "Dear Customer Your Order #".$data['order_details']['order']['order_number'].$corp_ref_text." placed by ".$data['order_details']['order']['customer_name']." picked up has under gone Order Modification. Further details can be found under Manage Orders of your account.".PHP_EOL."Thanks carterx.in";

                    if($istravel_person)
                    {
                    //Status - Picked up  From and to Airport someone else
                    User::sendsms($traveller_number,$picked_msg);
                    }
                    if($data['order_details']['order']['assigned_person'] == 1)
                    {
                        //Status - pickedup sms to location contact
                        User::sendsms($location_contact,$picked_msg);
                    }
                    User::sendemail($data['order_details']['order']['customer_email'],"Order Modification on Order #".$data['order_details']['order']['order_number']." placed on Caterx",'order_modification',$data);*/
                } else {
                    if(!($data['order_details']['order']['corporate_type'] == 1)) {
                        if($data['order_details']['order']['order_transfer']==1) {
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_NoModification', '');
                        } else {
                            $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_pickup_no_modification', '');
                        }
                    }else{
                        // if($data['order_details']['order']['service_type'] == 1){
                        //     $customer_message = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." for ".$data['order_details']['order']['no_of_units']." bags has been picked Outstation delivery timelines: upto 3 days.  -CarterX";

                        //     $traveller_message = "Hello Order #".$data['order_details']['order']['order_number']." placed by ".$data['order_details']['order']['customer_name']." for ".$data['order_details']['order']['no_of_units']." bags has been picked Outstation delivery timelines: upto 3 days.  -CarterX";
                        // }else{
                        //     $customer_message = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." for ".$data['order_details']['order']['no_of_units']." bags has been picked Outstation delivery timelines: upto 3 days.  -CarterX";

                        //     $traveller_message = "Hello Order #".$data['order_details']['order']['order_number']." placed by ".$data['order_details']['order']['customer_name']." for ".$data['order_details']['order']['no_of_units']." bags has been picked Outstation delivery timelines: upto 3 days.  -CarterX";
                        // }

                        // User::sendsms($customer_number,$customer_message);

                        //   if($istravel_person)
                        //   {
                        //     //Status - Picked up  From and to Airport someone else
                        //     User::sendsms($traveller_number,$traveller_message);
                        //   }
                        //   if($data['order_details']['order']['assigned_person'])
                        //   {
                        //       User::sendsms($location_contact,$traveller_message);
                        //   }
                        if($data['order_details']['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_NoModification', '');
                        }else{
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_pickup_no_modification', '');
                        }
                    }
                }
            }

            //Status - Out for delivery at gate 1 to Airport
            /*if($_POST['id_order_status'] == 16)
            {
              if($data['order_details']['order']['travel_person'] == 1)
              {
                User::sendsms($data['order_details']['order']['travell_passenger_contact'],"Hello your order #".$data['order_details']['order']['order_number']." is ready for delivery. Carter ".$data['order_details']['allocation_details']['porter_name']." & ".$data['order_details']['allocation_details']['porter_contact']." is waiting at Gate1 (opposite to Café Noir) to deliver the order.Please pay Rs. XX by cash/card to deliver the luggage order on account of order modification. The booking customer will receive a confirmation notification once order is delivered.".PHP_EOL."Thanks carterx.in ");
              }
              else
              {
                User::sendsms($data['order_details']['order']['customer_mobile'],"Dear Customer, your Order ".$data['order_details']['order']['order_number']." is ready for delivery. Carter ".$data['order_details']['allocation_details']['porter_name']." & ".$data['order_details']['allocation_details']['porter_contact']." is waiting at Gate 1 (opposite to Café Noir) to deliver the order.Please pay Rs. XX by cash/card to deliver the luggage order on account of order modification.You will receive a confirmation notification once order is delivered.".PHP_EOL."Thanks carterx.in ");
              }
            }*/

            //Status - Out for delivery at customer location from Airport
            /*if($_POST['id_order_status'] == 17)
            {
              if($data['order_details']['order']['assigned_person'] == 1)
              {
                User::sendsms($data['order_details']['order']['location_contact_number'],"Hello, order #".$data['order_details']['order']['order_number']." placed by ".$data['order_details']['order']['customer_name']." via Carter X is out for delivery. Carter ".$data['order_details']['allocation_details']['porter_name']." & ".$data['order_details']['allocation_details']['porter_contact']." is allocated to deliver the luggage order between slot schedule time. You will be notified when the carter is approaching your location.".PHP_EOL."Thanks carterx.in ");
              }
              else
              {
                User::sendsms($data['order_details']['order']['customer_mobile'],"Dear Customer, your Order #".$data['order_details']['order']['order_number']." is out for delivery. Carter ".$data['order_details']['allocation_details']['porter_name']." & ".$data['order_details']['allocation_details']['porter_contact']." is waiting at Gate 1 (opposite to Café Noir) to deliver the order.Please pay ‘Rs. XX’ by cash/card to deliver the luggage order on account of order modification.You will receive a confirmation notification once order is delivered.".PHP_EOL."Thanks carterx.in ");              
              }
            }*/
            $customers  = Order::getcustomername($data['order_details']['order']['travell_passenger_contact']);
            $customer_name = ($customers) ? $customers->name : '';

            //rint_r($data['order_details']['order']['corporate_type']);exit;

            if($_POST['id_order_status'] == 18)
            {
              //Status - delivered to and to Airport
              if(!($data['order_details']['order']['corporate_type'] == 1)){
                if($data['order_details']['order']['order_transfer']==1){
                    $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderDelivered', '');
                        $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_WithModification', '');
                }else{
                    $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_delivered', '');
                }
              }else{
                if($data['order_details']['order']['order_transfer']==1){
                        $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderDelivered', '');
                        $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_WithModification', '');
                }else{
                   $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_delivered', '');
               }

                  //  $msg1 = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." has been delivered. We thank you for choosing us and look forward to serving you soon. Detailed Invoice will be sent to registered email address. -CarterX";
                  // User::sendsms($customer_number, $msg1); 
                  // //Status - delivered to and to Airport
                  // $msg2 = "Hello Order #".$data['order_details']['order']['order_number']." placed by ".$data['order_details']['order']['customer_name']."  has been delivered. We thank you for choosing us and look forward to serving you soon. -CarterX";
                  // if($istravel_person){
                    
                  //     User::sendsms($traveller_number,$msg2);

                  // } 
                  // if($data['order_details']['order']['assigned_person'] == 1)
                  // {
                  //     User::sendsms($location_contact,$msg2);
                  // }
              }
              

              if($data['order_details']['order']['round_trip'] != 0 )
              {
                  //if related round trip or return has to be completed yet
                  $data['related_order_details'] = Order::getorderdetails($data['order_details']['order']['related_order_id']);
                                  
                  $fetch_orders = Order::find()->where(['id_order'=>[ $data['order_details']['order']['id_order'], $data['order_details']['order']['related_order_id'] ]])
                                               //->andwhere(['>=', 'order_date', date('Y-m-d')])
                                               ->orderBy(['order_date' => SORT_ASC])
                                               ->all();
                  if(!empty($fetch_orders))
                  {
                     $delivered_order_number = $fetch_orders[0]->order_status == 'Delivered' ? $fetch_orders[0]->order_number : $fetch_orders[1]->order_number;
                     $rest_order_date = $fetch_orders[0]->order_status == 'Delivered' ? $fetch_orders[1]->order_date : $fetch_orders[0]->order_date;

                      // $msg1 = "Dear Customer, your Order #".$delivered_order_number." has been delivered. We thank you for choosing us and look forward to serving you soon on ".date("F j, Y", strtotime($rest_order_date))." for the return service booked.".PHP_EOL." Thank you Carterx.in";
                     // $msg1 = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." has been delivered. We thank you for choosing us and look forward to serving you soon. Detailed Invoice will be sent to registered email address. -CarterX";
                      User::sendsms($customer_number, $msg1); 
                  }
              }

            }

            if($data['order_details']['order']['corporate_type'] == 1){
                //echo "<pre>";print_r($data['order_details']);exit;
                if($data['order_details']['order']['order_transfer']==1){
                    $customers = Customer::findOne(['mobile' => $data['order_details']['order']['customer_mobile']]);
                }else{
                    $customers = Customer::findOne(['mobile' => $data['order_details']['order']['travell_passenger_contact']]);
                }
                
                if($_POST['id_order_status'] == 18)
                {
                //    $pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
                    User::sendemail($customers['email'],"Order check - Order #".$data['order_details']['order']['order_number']."",'delivery_confirmation',$data);
                }
                if($_POST['id_order_status'] == 21)
                {
                    // print_r($customers['email']);exit;
                   /* $pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
                    User::sendemailasattachment($customers['email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data, $pdf_det);*/
                    if(empty($data['order_details']['order']['confirmation_number'])){
                        User::sendemail($customers['email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'cancellation_email',$data);
                    } else {
                        // cancellation sms without refund
                        Yii::$app->Common->subscriptionSmsSent("cancellations_without_refund",$sms_data,array_filter($mobile_arr));
                        // confirmation update email
                        $data['order_details']['order']['refund'] = 1;
                        $file_name = "subscription_confirmation_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                        $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data,'subscription_cnf_template',$file_name);
                        User::sendcnfemail($customer_email,"CarterX Cancellation Confirmed Subscription #".strtoupper($data['order_details']['subscription_details']['subscriber_name']),'sub_cnf_email',$data,$attachment_cnf,array_filter(array_unique($emailSubscriberTo)));
                        // Token confirmation email
                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'token_confirmation_pdf_template');
                        User::sendcnfemail($customer_email,"CarterX Cancellation Subscription Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_det,array_filter(array_unique(array_merge($emailTokenTo,$emailCustomerCareTo))),false);
                    }
                }
            }else{
                if($_POST['id_order_status'] == 18)
                {
                //    $pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
                    User::sendemail($data['order_details']['order']['customer_email'],"Order check - Order #".$data['order_details']['order']['order_number']."",'delivery_confirmation',$data);
                }
                if($_POST['id_order_status'] == 21)
                {
                    /*$pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
                    User::sendemailasattachment($data['order_details']['order']['customer_email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data, $pdf_det);*/  
                    if(empty($data['order_details']['order']['confirmation_number'])){ 
                        User::sendemail($data['order_details']['order']['customer_email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'cancellation_email',$data);
                    } else {
                        // cancellation sms without refund
                        Yii::$app->Common->subscriptionSmsSent("cancellations_without_refund",$sms_data,array_filter($mobile_arr));
                        // confirmation update email
                        $data['order_details']['order']['refund'] = 1;
                        $file_name = "subscription_confirmation_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                        $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data,'subscription_cnf_template',$file_name);
                        User::sendcnfemail($customer_email,"CarterX Cancellation Confirmed Subscription #".strtoupper($data['order_details']['subscription_details']['subscriber_name']),'sub_cnf_email',$data,$attachment_cnf,array_filter(array_unique($emailSubscriberTo)));
                        // Token confirmation email
                        $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'token_confirmation_pdf_template');
                        User::sendcnfemail($customer_email,"CarterX Cancellation Subscription Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_det,array_filter(array_unique(array_merge($emailTokenTo,$emailCustomerCareTo))),false);
                    }
                }
            }
            if($_POST['id_order_status'] == 18)
            {
              User::sendemail($data['order_details']['order']['customer_email'],"Order Delivered - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'delivery_confirmation',$data);
            }
            $location = $data['order_details']['order']['service_type'] == 1 ? $data['order_details']['order']['location_address_line_1'] : 'Airport';
            // if($data['order_details']['order']['service_type'] == 1){
            //     $msg_cancel = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." has been cancelled. Please login to your account at www.carterx.in for any applicable refund details. Look forward to serving you soon. Thank you for choosing us.".PHP_EOL."-CarterX";
            // }else{
            //     $msg_cancel = "Dear Customer, your Order #".$data['order_details']['order']['order_number']." has been cancelled. Please login to your account at www.carterx.in for any applicable refund details";
            // }
            if($_POST['id_order_status'] == 21)
            {
                   // $msg = "Dear Customer, Your Order #".$data['order_details']['order']['order_number']." placed on ".date("F j, Y", strtotime($data['order_details']['order']['date_created']))." for service on ".date("F j, Y", strtotime($data['order_details']['order']['order_date']))." has been cancelled. Details of refund if applicable, can be found under 'Manage Orders'. We look forward to serving you soon. Thank you for choosing us.".PHP_EOL." Thanks carterx.in";

                /*.Details of refound if applicable, can be found under 'Manage Orders'. We look forward*/

                  // $msg1 = "Hello, Order #".$data['order_details']['order']['order_number']." Reference ".$corp_ref_text." placed by ".$customer_name. " on ".date("F j, Y", strtotime($data['order_details']['order']['date_created']))." for service on ".date("F j, Y", strtotime($data['order_details']['order']['order_date']))." has been cancelled. Details of refund if applicable, can be found under 'Manage Orders'. We look forward to serving you soon. Thank you for choosing us.".PHP_EOL." Thanks carterx.in";

                  // User::sendsms($customer_number,$msg);
                  // if($data['order_details']['order']['travel_person'] == 1)
                  // {                  
                  //   User::sendsms($traveller_number,$msg_cancel);
                  // }
                  // if($data['order_details']['order']['assigned_person'] == 1)
                  // {                  
                  //   User::sendsms($location_contact,$msg_cancel);
                  // } 
                // print_r($data['order_details']['order']['id_order']);exit;
                if(empty($data['order_details']['order']['confirmation_number'])){
                    if(!($data['order_details']['order']['corporate_type']==1)){
                        if($data['order_details']['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderCancelation', '');
                        }else{
                            $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
                        }
                    }else{
                        if($data['order_details']['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderCancelation', '');
                        }else{
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
                        }
                    }
                } else {
                    // cancellation sms without refund
                    Yii::$app->Common->subscriptionSmsSent("cancellations_without_refund",$sms_data,array_filter($mobile_arr));
                    // confirmation update email
                    $data['order_details']['order']['refund'] = 1;
                    $file_name = "subscription_confirmation_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                    $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data,'subscription_cnf_template',$file_name);
                    User::sendcnfemail($customer_email,"CarterX Cancellation Confirmed Subscription #".strtoupper($data['order_details']['subscription_details']['subscriber_name']),'sub_cnf_email',$data,$attachment_cnf,array_filter(array_unique($emailSubscriberTo)));
                    // Token confirmation email
                    $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'token_confirmation_pdf_template');
                    User::sendcnfemail($customer_email,"CarterX Cancellation Subscription Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_det,array_filter(array_unique(array_merge($emailTokenTo,$emailCustomerCareTo))),false);
                }
                 // $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
            }


            if($_POST['id_order_status'] == 23)
            {
                if(!($data['order_details']['order']['corporate_type'] == 1)){
                        if($data['order_details']['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'order_delivered_no_response', '');
                        }else{
                            $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_delivery_no_response', '');
                        } 
                }else{
                    $service_type = ($data['order_details']['order']['service_type'] == 1) ? 'airport' : $data['order_details']['order']['location_address_line_1'];
                    $location_passanger_name = ($data['order_details']['order']['service_type'] == 1) ? $data['order_details']['order']['travell_passenger_name'] : $data['order_details']['order']['location_contact_name'];
                    $location_passanger_contact = ($data['order_details']['order']['service_type'] == 1) ? $data['order_details']['order']['travell_passenger_contact'] : $data['order_details']['order']['location_contact_number'];
                    
                    $msg_undelivered = "Hello Customer Order#".$data['order_details']['order']['order_number']. ", Your order status has been pushed to Undelivered as per our our terms & conditions. Please call customer care at ".PHP_EOL." +919110635588"." within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot. ".PHP_EOL."Thank you carterx.in ";

                        if($data['order_details']['order']['order_transfer']==1){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'order_delivered_no_response', '');
                        }else{
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_delivered_no_response', '');
                        } 
                    //print_r($sms_content);exit;
                    // User::sendsms($customer_number,$msg_undelivered);
                    // if($data['order_details']['order']['travel_person'] == 1)
                    // {
                    //     User::sendsms($traveller_number,$msg_undelivered);
                    // }
                    // if($data['order_details']['order']['assigned_person'] == 1)
                    // {
                    //     User::sendsms($location_contact,$msg_undelivered);
                    // }
                }
                if($data['order_details']['order']['corporate_type'] == 3){
                    User::sendemail($data['order_details']['order']['customer_email'],"Undelivered on Order #".$data['order_details']['order']['order_number']." placed on Caterx",'order_delivery_no_response',$data);
                    
                    //User::sendemail($data['order_details']['order']['customer_email'],"Undelivered on Order #".$data['order_details']['order']['order_number']." placed on CaterX",'undelivered_order',$data);   
                }
                            
             // User::sendemail($data['order_details']['order']['customer_email'],"Undelivered on Order #".$data['order_details']['order']['order_number']." placed on CaterX",'reschedule_undelivered',$data);
            }

            if($_POST['id_order_status'] == 25)
            {
                $refund = OrderPaymentDetails::find()->where(['id_order'=>$data['order_details']['order']['id_order'], 'payment_status'=>'Refunded'])->one();
                if(!empty($refund)){
                    User::sendsms($customer_number,"Dear Customer, Order #".$data['order_details']['order']['order_number'].", refund of Rs. ".$refund->amount_paid." is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account. The receipt of the refund can be found under 'Manage Orders' of your account. ".PHP_EOL."Thanks carterx.in");
                }
            }


            $saveData = [];
            $saveData['order_id'] = $id; 
            $saveData['description'] = 'Order modification customer order status details';
            $saveData['employee_id'] = Yii::$app->user->identity->id_employee;
            $saveData['employee_name'] = Yii::$app->user->identity->name;
            $saveData['module_name'] = 'Customer Order Status';

            Yii::$app->Common->ordereditHistory($saveData);

            $orderInfo['order_details'] = Order::getorderdetails($id);
            if($orderInfo['order_details']['order']['corporate_type'] == 2){
                if(($orderInfo['order_details']['order']['id_order_status'] == 18) && ($orderInfo['order_details']['order']['order_status'] == 'Delivered')){
                    User::sendMHLemail($orderInfo['order_details']['corporate_details']['default_email'],"MHL Order Delivered","order_delivery_mhl",$orderInfo,true);
                } else if(($orderInfo['order_details']['order']['id_order_status'] == 23) && ($orderInfo['order_details']['order']['order_status'] == 'Undelivered')){
                    User::sendMHLemail($orderInfo['order_details']['corporate_details']['default_email'],"MHL Order Undelivered","order_undelivered_mhl",$orderInfo,true);
                }
            }
            
            if(isset($_POST['kiosk_status'])){
                return $this->redirect(['kiosk-order-update', 'id' => $id]);
            }else if(isset($_POST['thirdparty_status'])){
                return $this->redirect(['thirdparty-update', 'id' => $id]);
            }else{
                return $this->redirect(['update', 'id' => $id]);
            }
        }
        else
        {
            return $this->redirect(['update', 'id' => $id]);
        }
    }

    /**
     * Order edit screen for existing orders.
     * Enabling COD option for the orders by super admin
     */
    public function actionEnablecod($id)
    {
        $model = Order::findOne($id);
        $model->enable_cod = $_POST['enable_cod'];
        $model->date_modified = date('Y-m-d H:i:s');
        $model->save();
        return $this->redirect(['update', 'id' => $id]);
    }

    public function genaratepdfexpress($order_details)
    {
        // define('YII_ENABLE_ERROR_HANDLER', false);
        define('YII_ENABLE_EXCEPTION_HANDLER', false);
        error_reporting("");
          ob_start();
          $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'];

          
          echo $this->renderPartial("order-invoice-express",array('order_details' => $order_details));
          //echo $this->renderPartial("order-invoice",array('order_details' => $order_details));
          $data = ob_get_clean();

          try
          {
              $html2pdf = new \HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(0, 0, 0, 0));

              $html2pdf->setDefaultFont('dejavusans');
              $html2pdf->writeHTML($data);
              $html2pdf->Output($path."order_".$order_details['order_number'].".pdf",'F');  // To save the pdf to a specific folder
              $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['express_pdf_path'].'order_'.$order_details['order_number'].".pdf";
              $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'].'order_'.$order_details['order_number'].".pdf";
              return $order_pdf;
          }
          catch(HTML2PDF_exception $e) {
              echo JSON::encode(array('status' => false, 'message' => $e));
       
          }
    }
    public function genaratepdf($id_order)
    {
          ob_start();
          $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];

          $order_details = order::getorderdetails($id_order);
          //print_r($getorder);exit;         
                    
          $status = Order::getcustomerstatus($order_details['order']['id_order_status'], $order_details['order']['service_type'],$id_order);
               
          if($order_details['order']['reschedule_luggage'] == 1){
              $order_details['order']['customer_id_order_status'] = $status['customer_id_order_status']==6 ? '3': $status['customer_id_order_status'];
              $order_details['order']['customer_order_status_name'] = $status['status_name'] == 'Assigned'? 'Open' : $status['status_name'];              
          }else{
              $order_details['order']['customer_id_order_status'] = $status['customer_id_order_status'];
              $order_details['order']['customer_order_status_name'] = $status['status_name'];
          }
          echo $this->renderPartial("order-invoice-file",array('order_details' => $order_details));die;
          //echo $this->renderPartial("order-invoice",array('order_details' => $order_details));
          $data = ob_get_clean();

          try
          {
              $html2pdf = new \HTML2PDF('P', array(250,153), 'en', true, 'UTF-8', array(0, 0, 0, 0));
              $html2pdf->setDefaultFont('dejavusans');
              $html2pdf->writeHTML($data);
              $html2pdf->Output($path."order_".$order_details['order']['order_number'].".pdf",'F');  // To save the pdf to a specific folder
              $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order']['order_number'].".pdf";
              $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].'order_'.$order_details['order']['order_number'].".pdf";
              return $order_pdf;
          }
          catch(HTML2PDF_exception $e) {
              echo JSON::encode(array('status' => false, 'message' => $e));
          }
    }

    public function actionUpdateflightverification($id_order)
    { 
        $model = Order::findOne($id_order);
        //print_r($id_order);exit;
        $model->travell_passenger_name = isset($_POST['travell_passenger_name']) ? $_POST['travell_passenger_name'] : 0;
        if(!empty($_POST['Order']['fk_tbl_order_id_country_code']))
        {
            $model->fk_tbl_order_id_country_code = $_POST['Order']['fk_tbl_order_id_country_code'];
        }
        $model->travell_passenger_contact = isset($_POST['travell_passenger_contact']) ? $_POST['travell_passenger_contact'] : null;
        $model->flight_verification = isset($_POST['flight_verification']) ? $_POST['flight_verification'] : 0;
        $model->flight_number = isset($_POST['flight_number']) ? $_POST['flight_number'] : null;
        $model->someone_else_document_verification = isset($_POST['someone_else_document_verification']) ? $_POST['someone_else_document_verification'] : 0;
        $model->date_modified = date('Y-m-d H:i:s');
        $model->departure_time = isset($_POST['departure_time']) ? $_POST['departure_time'] : null ;
        $model->departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : null ;
        $model->arrival_time = isset($_POST['arrival_time']) ? $_POST['arrival_time'] : null ;
        $model->arrival_date = isset($_POST['arrival_date']) ? $_POST['arrival_date'] : null ;
        $model->meet_time_gate = isset($_POST['meet_time_gate']) ? $_POST['meet_time_gate'] : null ;
        //$model->corporate_price = isset($_POST['corporate_price']) ? $_POST['corporate_price'] : 0 ;
         if(!empty($_POST['Order']['location']))
        {
            $model->location = $_POST['Order']['location'] ;

        } 
        //print_r($model->location);exit;

        $model->save(false);      
        $saveData = [];
        $saveData['order_id'] = $id_order;
        $saveData['description'] = 'Order modification traveller details';
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
        $saveData['employee_name'] = Yii::$app->user->identity->name;;
        $saveData['module_name'] = 'Traveller Details';

        Yii::$app->Common->ordereditHistory($saveData);

        if(!empty($_FILES['flight_ticket']['name']))
        {
            $up = $this->actionFileupload('flight_ticket');
        }
        if(!empty($_FILES['someone_else_document']['name']))
        {
            $up = $this->actionFileupload('someone_else_document');
        }

        $gate_meet_text = ($model->service_type == 1) ? 'Gate1 time' : 'Arrival gate meet time';
        $orderby= Order::getOrderdetails($id_order);
        $customer_number = $orderby['order']['c_country_code'].$orderby['order']['customer_mobile'];
        $traveller_number = $orderby['order']['traveler_country_code'].$orderby['order']['travell_passenger_contact'];
        $location_contact = Yii::$app->params['default_code'].$orderby['order']['location_contact_number'];
        $corp_ref_text = ($model->corporate_id == 0) ? "": " ".$model->flight_number;

         /*flexible fields update msg to booked user*/            
        /*User::sendsms($customer_number,"Dear Customer, the Flexible Fields for your Order #".$orderby['order']['order_number'].$corp_ref_text." was edited/updated. All changes made will reflect under Manage Orders. The ".$gate_meet_text." for the order is ".date('h:i A', strtotime($_POST['meet_time_gate'])).". Log in to your account or call customer care at 9110635588 for support. ".PHP_EOL."Thanks carterx.in");*/

        /*Flexible field update SMS to travel person*/
        if($model->travel_person == 1){
            if($model->corporate_id == 0){
                User::sendsms($traveller_number,"Hello, the Flexible Fields for  Order #".$model->order_number. " Reference " .$corp_ref_text." placed by ".$orderby['order']['customer_name']." was edited/updated. The ".$gate_meet_text." for the order is ".date('h:i A', strtotime($model->meet_time_gate)).". All changes made will reflect under 'Manage Orders'. Log in to your account or call customer care at ".PHP_EOL."+919110635588"." for support. ".PHP_EOL."Thanks carterx.in");
            }else{
                User::sendsms($traveller_number,"Hello, the Flexible Fields for  Order #".$model->order_number. " Reference " .$corp_ref_text." placed by ".$orderby['order']['customer_name']." was edited/updated. All changes made will reflect under 'Manage Orders'. Log in to your account or call customer care at ".PHP_EOL."+919110635588"." for support. ".PHP_EOL."Thanks carterx.in");
            }
        }
        if(isset(Yii::$app->request->post()['kiosk_update_image'])){
            return $this->redirect(['kiosk-order-update', 'id' => $id_order]);
        }elseif(isset(Yii::$app->request->post()['thirdparty_travel_details'])){
            return $this->redirect(['thirdparty-update', 'id' => $id_order]);
        }else{
            return $this->redirect(['update', 'id' => $id_order]);
        }
        //return $this->redirect(['update', 'id' => $id_order]);
    }


    public function actionUpdatecorporateprice($id_order)
    {
        $model = Order::findOne($id_order);
        if($_POST['corporate_price'] != '')
        {
            $model->corporate_price = $_POST['corporate_price'];
        }
        $model->save(false);

        $saveData = [];
        $saveData['order_id'] = $id_order;
        $saveData['description'] = 'Order modification corporate price';
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
        $saveData['employee_name'] = Yii::$app->user->identity->name;;
        $saveData['module_name'] = 'Corporate Price';
        Yii::$app->Common->ordereditHistory($saveData);

        if(isset($_POST['thirdpary_update'])){
            return $this->redirect(['thirdparty-update', 'id' => $id_order]);
        }else{
            return $this->redirect(['update', 'id' => $id_order]);
        }
    }

    public function actionUpdatecustomer($id,$id_order)
    {
        $model = Customer::findOne($id);
        $model->name = $_POST['customer_name'];
        $model->mobile = $_POST['customer_mobile'];
        $model->email = $_POST['customer_email'];
        //$model->date_modified = new Expression('NOW()');
        $model->save();
        return $this->redirect(['update', 'id' => $id_order]);
    }

    /**
     * Order edit screen for existing orders..
     * Editing location details such as Hotel,residance and so on
     * @param integer $id order spot detail table primary key
     * @param integer $id_order is order id 
     */

    public function actionUpdatecityaddressdetails($id_order_meta_details,$id_order) 
    {
        $order_model=$this->findModel($id_order);
        $model = OrderMetaDetails::findOne($id_order_meta_details);
        // echo "<pre>";print_r($model);exit;
        $model->pickupPersonName = (isset($_POST['pickupPersonName'])) ? $_POST['pickupPersonName'] : $model->pickupPersonName;
        $model->pickupPersonNumber = (isset($_POST['pickupPersonNumber'])) ? $_POST['pickupPersonNumber'] : $model->pickupPersonNumber;
    
        $model->pickupBuildingNumber = (isset($_POST['pickupBuildingNumber'])) ? $_POST['pickupBuildingNumber'] : $model->pickupBuildingNumber;
        $model->pickupArea = (isset($_POST['pickupArea'])) ? $_POST['pickupArea'] : $model->pickupArea;

        $model->pickupPincode = (isset($_POST['pickupPincode'])) ? $_POST['pickupPincode'] : $model->pickupPincode;
        $model->pickupPersonAddressLine1 = (isset($_POST['pickupPersonAddressLine1'])) ? $_POST['pickupPersonAddressLine1'] : $model->pickupPersonAddressLine1;
        $model->pickupPersonAddressLine2 = (isset($_POST['pickupPersonAddressLine2'])) ? $_POST['pickupPersonAddressLine2'] : $model->pickupPersonAddressLine2;

        $model->PickupHotelName = isset($_POST['PickupHotelName']) ? $_POST['PickupHotelName'] : $model->PickupHotelName ;
        $model->pickupMallName = isset($_POST['pickupMallName']) ? $_POST['pickupMallName'] : $model->pickupMallName ;
        $model->pickupStoreName = isset($_POST['pickupStoreName']) ? $_POST['pickupStoreName'] : $model->pickupStoreName ;
        $model->pickupBusinessName = isset($_POST['pickupBusinessName']) ? $_POST['pickupBusinessName'] : $model->pickupBusinessName ;
        
        $model->pickupHotelType=isset($_POST['pickupHotelType']) ? $_POST['pickupHotelType'] : $model->pickupHotelType ;
        

        $model->dropPersonName = (isset($_POST['dropPersonName'])) ? $_POST['dropPersonName'] : $model->dropPersonName;
        $model->dropPersonNumber = (isset($_POST['dropPersonNumber'])) ? $_POST['dropPersonNumber'] : $model->dropPersonNumber;
    
        $model->dropBuildingNumber = (isset($_POST['dropBuildingNumber'])) ? $_POST['dropBuildingNumber'] : $model->dropBuildingNumber;
        $model->droparea = (isset($_POST['droparea'])) ? $_POST['droparea'] : $model->droparea;

        $model->dropPincode = (isset($_POST['dropPincode'])) ? $_POST['dropPincode'] : $model->dropPincode;
        $model->dropPersonAddressLine1 = (isset($_POST['dropPersonAddressLine1'])) ? $_POST['dropPersonAddressLine1'] : $model->dropPersonAddressLine1;
        $model->dropPersonAddressLine2 = (isset($_POST['dropPersonAddressLine2'])) ? $_POST['dropPersonAddressLine2'] : $model->dropPersonAddressLine2;

        $model->dropHotelName = isset($_POST['dropHotelName']) ? $_POST['dropHotelName'] : $model->dropHotelName ;
        $model->dropMallName = isset($_POST['dropMallName']) ? $_POST['dropMallName'] : $model->dropMallName ;
        $model->dropStoreName = isset($_POST['dropStoreName']) ? $_POST['dropStoreName'] : $model->dropStoreName ;
        $model->dropBusinessName = isset($_POST['dropBusinessName']) ? $_POST['dropBusinessName'] : $model->dropBusinessName ;
        
        $model->dropHotelType=isset($_POST['dropHotelType']) ? $_POST['dropHotelType'] : $model->dropHotelType ;
        
        $model->save();

        $saveData = [];
        if(!empty($_POST['pickupPersonName'])){
            $saveData['description'] = 'Order modification pickup details';
            $saveData['module_name'] = 'Pickup Details';
        }else if(!empty($_POST['dropPersonName'])){
            $saveData['description'] = 'Order modification pickup details';
            $saveData['module_name'] = 'Drop Details';
        }
        $saveData['order_id'] = $id_order;
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;
        $saveData['employee_name'] = Yii::$app->user->identity->name;

        Yii::$app->Common->ordereditHistory($saveData);

        if(!empty($_FILES['pickupBookingConfirmation']['name']))
        {
            $up = $this->actionFileupload('pickupBookingConfirmation');
        }
        if(!empty($_FILES['pickupInvoice']['name']))
        {
            $up = $this->actionFileupload('pickupInvoice');
        }

        if(!empty($_FILES['dropBookingConfirmation']['name']))
        {
            $up = $this->actionFileupload('dropBookingConfirmation');
        }
        if(!empty($_FILES['dropInvoice']['name']))
        {
            $up = $this->actionFileupload('dropInvoice');
        }
        if(isset(Yii::$app->request->post()['kiosk_pickup'])){
            return $this->redirect(['kiosk-order-update', 'id' => $id_order]);
        }else if(Yii::$app->user->identity->fk_tbl_employee_id_employee_role == 10){
            return $this->redirect(['kiosk-order-update', 'id' => $id_order]);
        }elseif(isset(Yii::$app->request->post()['thirdparty_pickup'])){
            return $this->redirect(['thirdparty-update', 'id' => $id_order]);
        }else{
            return $this->redirect(['update', 'id' => $id_order]);
        }
        
    }

    /**
     * Order edit screen for existing orders..
     * Editing location details such as Hotel,residance and so on
     * @param integer $id order spot detail table primary key
     * @param integer $id_order is order id 
     */

    public function actionUpdatespotdetails($id_order_spot_details,$id_order) 
    {//die('in');

        $order_model=$this->findModel($id_order);
        $model = OrderSpotDetails::findOne($id_order_spot_details);
        $model->person_name = $_POST['location_contact_name'];
        $model->person_mobile_number = $_POST['location_contact_number'];
        $model->landmark = $_POST['landmark'];
        $model->building_number = $_POST['building_number'];
        $model->area = $_POST['location_area'];


         if(isset($_POST['location_pincode']) && !empty($_POST['location_pincode']))
            {
                $pincode_id=PickDropLocation::find()->select('id_pick_drop_location')->where(['pincode'=>$_POST['location_pincode']])->one();
                //echo "<pre>";print_r($pincode_id);exit;
                if($pincode_id){
                   $pincode_id=$pincode_id['id_pick_drop_location']; 

                   $sector = PickDropLocation::findOne(['pincode' => $_POST['location_pincode']]);
                }else{
                    $pincode_id='';
                    $sector = '';
                }
                //echo "<pre>";print_r($order_model->fk_tbl_order_id_pick_drop_location);exit;
                $order_model->fk_tbl_order_id_pick_drop_location=$pincode_id;
                $order_model->sector_name = ($sector) ? $sector->sector : '';
                $order_model->save(false); 
                
            } 

        $model->pincode = $_POST['location_pincode'];
        $model->address_line_1 = $_POST['location_address_line_1'];
        $model->address_line_2 = $_POST['location_address_line_2'];
        $model->hotel_name = isset($_POST['hotel_name']) ? $_POST['hotel_name'] : null ;
        $model->mall_name = isset($_POST['mall_name']) ? $_POST['mall_name'] : null ;
        $model->store_name = isset($_POST['store_name']) ? $_POST['store_name'] : null ;
        $model->business_name = isset($_POST['business_name']) ? $_POST['business_name'] : null ;
        $model->business_contact_number = isset($_POST['business_contact_number']) ? $_POST['business_contact_number'] : null ;
        $model->fk_tbl_order_spot_details_id_contact_person_hotel=isset($_POST['id_contact_person_hotel']) ? $_POST['id_contact_person_hotel'] : null ;
        $model->hotel_booking_verification = isset($_POST['hotel_booking_verification']) ? $_POST['hotel_booking_verification'] : 0 ;
        $model->invoice_verification = isset($_POST['invoice_verification']) ? $_POST['invoice_verification'] : 0 ;

         if(isset($_POST['building_restriction']) && $_POST['building_restriction'] != '')
        {
           $model->building_restriction = serialize($_POST['building_restriction']);
        }
        $model->other_comments = isset($_POST['other_comments']) ? $_POST['other_comments'] : null ;
        $model->save();

        $saveData = [];
        $saveData['order_id'] = $id_order;
        $saveData['description'] = 'Order modification pickup details';
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
        $saveData['employee_name'] = Yii::$app->user->identity->name;;
        $saveData['module_name'] = 'Pickup Details';

        Yii::$app->Common->ordereditHistory($saveData);

        if(!empty($_FILES['hotel_booking']['name']))
        {
            $up = $this->actionFileupload('hotel_booking');
        }
        if(!empty($_FILES['invoice']['name']))
        {
            $up = $this->actionFileupload('invoice');
        }
        if(isset(Yii::$app->request->post()['kiosk_pickup'])){
            return $this->redirect(['kiosk-order-update', 'id' => $id_order]);
        }if(isset(Yii::$app->request->post()['thirdparty_pickup'])){
            return $this->redirect(['thirdparty-update', 'id' => $id_order]);
        }else{
            return $this->redirect(['update', 'id' => $id_order]);
        }
        
    }


    /**
     * Order edit screen for existing orders..
     * Editing location details such as Hotel,residance and so on
     * @param integer $id order spot detail table primary key
     * @param integer $id_order is order id
     */
    public function actionUpdatespotdetails1($id_order_item)
    {
       // echo $_GET['id_order'];exit;
        $model = OrderItems::findOne($id_order_item);
        
        $model->passive_tag = $_POST['passive_tag'];
        
        $model->save();

        $saveData = [];
        $saveData['order_id'] = $model['fk_tbl_order_items_id_order'];//$id_order_item;
        $saveData['description'] = 'Order modification passive tag details';
        $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
        $saveData['employee_name'] = Yii::$app->user->identity->name;;
        $saveData['module_name'] = 'Passive Tag';

        Yii::$app->Common->ordereditHistory($saveData);

        if(isset(Yii::$app->request->post()['kiosk_passive'])){
            return $this->redirect(['employee/update-kiosk', 'id' => $_GET['id_order']]);
        }else if(isset(Yii::$app->request->post()['kiosk_passive_edit'])){
                Yii::$app->session->setFlash('invalidBarcode','Invalid Barcode');
                return $this->redirect(['kiosk-order-update', 'id' => $_GET['id_order']]);
        }else{
            return $this->redirect(['update', 'id' => $_GET['id_order']]);
        }
       //return $this->redirect(['update', 'id' => $_GET['id_order']]);

    }

    public function actionUpdatebarcode($id_order_item)
    {
        //return $_GET['id_order'];
        $barcode_data=Barcode::find()
                             ->where(['barcode' =>$_POST['barcode_text'],'used'=>0])
                             ->one();
        if($barcode_data){
                $model = OrderItems::findOne($id_order_item);
                $model->barcode = $_POST['barcode_text'];
                $model->fk_tbl_order_items_id_barcode = $barcode_data->id_barcode;
            if($model->save(false)){
                Yii::$app->db->createCommand("UPDATE tbl_barcode set used=1 where id_barcode='".$barcode_data->id_barcode."'")->execute();

                $saveData = [];
                $saveData['order_id'] = $model['fk_tbl_order_items_id_order'];
                $saveData['description'] = 'Order modification barcode details';
                $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                $saveData['employee_name'] = Yii::$app->user->identity->name;;
                $saveData['module_name'] = 'Barcode';
                Yii::$app->Common->ordereditHistory($saveData);

                return $this->redirect(['update', 'id' => $_GET['id_order']]);
            }

        }else{
            if(isset(Yii::$app->request->post()['kiosk_update'])){
                Yii::$app->session->setFlash('invalidBarcode','Invalid Barcode');
                return $this->redirect(['employee/update-kiosk', 'id' => $_GET['id_order'], 'mobile' =>Yii::$app->request->post()['mobile']]);
            }else if(isset(Yii::$app->request->post()['kiosk_update_edit'])){
                Yii::$app->session->setFlash('invalidBarcode','Invalid Barcode');
                return $this->redirect(['kiosk-order-update', 'id' => $_GET['id_order']]);
            }else{
                Yii::$app->session->setFlash('invalidBarcode','Invalid Barcode');
                return $this->redirect(['update', 'id' => $_GET['id_order']]);
            }
        }                    
       return $this->redirect(['update', 'id' => $_GET['id_order']]);
       

    }

    public function actionUpdatebarcodekiosk($id_order_item)
    {   
        $barcode_data=Barcode::find()
                             ->where(['barcode' =>$_POST['barcode_text'],'used'=>0])
                             ->one();
        if($barcode_data){
            $model = OrderItems::findOne($id_order_item);
            $model->barcode = $_POST['barcode_text'];
            $model->fk_tbl_order_items_id_barcode = $barcode_data->id_barcode;
           if($model->save(false)){
            Yii::$app->db->createCommand("UPDATE tbl_barcode set used=1 where id_barcode='".$barcode_data->id_barcode."'")->execute();
            if($_POST['kiosk_update'] == 'corporate'){
                return $this->redirect(['employee/update-kiosk-corporate', 'id' => $_GET['id_order'], 'mobile' =>Yii::$app->request->post()['mobile']]);
            }else{
                return $this->redirect(['employee/update-kiosk', 'id' => $_GET['id_order'], 'mobile' =>Yii::$app->request->post()['mobile']]);
            }

            $saveData = [];
            $saveData['order_id'] = $id_order_item;
            $saveData['description'] = 'Order modification barcode details';
            $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
            $saveData['employee_name'] = Yii::$app->user->identity->name;;
            $saveData['module_name'] = 'Barcode';

            Yii::$app->Common->ordereditHistory($saveData);
           }
        }else{
            Yii::$app->session->setFlash('invalidBarcode','Invalid Barcode');
            if($_POST['kiosk_update'] == 'corporate'){
                Yii::$app->session->setFlash('msg', Yii::t('app',"Invalid Barcode"));
                return $this->redirect(['employee/update-kiosk-corporate', 'id' => $_GET['id_order'], 'mobile' =>Yii::$app->request->post()['mobile']]);
            }else{
                Yii::$app->session->setFlash('msg', Yii::t('app',"Invalid Barcode"));
                return $this->redirect(['employee/update-kiosk', 'id' => $_GET['id_order'], 'mobile' =>Yii::$app->request->post()['mobile']]);
            }
        }                    
        return $this->redirect(['update', 'id' => $_GET['id_order']]);
    }

    /**
     * Order edit screen for existing orders.
     * Adding queries on order raised by porter or customer via customer care
     */
    public function actionAddquery($id)
    {
        $model = new CcQueries();
        $model->fk_tbl_cc_queries_id_order = $id;
        $model->query = $_POST['cc_query'];
        $id_employee=Yii::$app->user->identity->id_employee;
        $model->fk_tbl_cc_queries_id_employee = $id_employee;
        //$model->query = $_POST['cc_query'];
        if($model->validate())
        {
            $model->save();
            return $this->redirect(['update', 'id' => $id]);
        }
        else
        {
            /*return $this->render('update', [
                'model' => $model,
            ]);*/
            return $this->redirect(['update', 'id' => $id]);
        }

    }



    /**
     * Order edit screen for existing orders.
     * Adding queries on order raised by porter or customer via customer care
     */
    public function actionAddcomment($id)
    {
        $model = new CcQueries();
        $model->fk_tbl_cc_queries_id_order = $id;
        $model->query = $_POST['cc_query'];
        $model->iscomment = 1;
        $id_employee=Yii::$app->user->identity->id_employee;
        $model->fk_tbl_cc_queries_id_employee = $id_employee;
        $model->from_admin = $_POST['from_admin'];
        $model->date_created = date('Y-m-d H:i:s');
        if($model->validate())
        {
            $model->save();

            $saveData = [];
            $saveData['order_id'] = $id;
            $saveData['description'] = 'Order modification admin comments';
            $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
            $saveData['employee_name'] = Yii::$app->user->identity->name;;
            $saveData['module_name'] = 'Add Comment';

            Yii::$app->Common->ordereditHistory($saveData);

            $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            if($role_id == 8){
                return $this->redirect(['user-order-update', 'id' => $id]);
            }elseif($role_id == 11){
                return $this->redirect(['kiosk-order-update', 'id' => $id]);
            }else{
                return $this->redirect(['update', 'id' => $id]);
            }
            
        }
        else
        {
            return $this->redirect(['update', 'id' => $id]);
        }

    }



    /**
     * Deletes an existing Order model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        if($id){         
            $result = Yii::$app->db->createCommand("UPDATE tbl_order set deleted_status=1 where id_order='".$id."'")->execute();
            if($result){
                return $this->redirect(['index']);
            }   
        }
    }

    /**
     * Finds the Order model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Order the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Order::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionOrderupdate($id)
    {
        
        return $this->render('order_update', [
                'model' => $model,
            ]);
    }

    public function actionPendingOrderAllocation()
    {

        $searchModel = new OrderSearch();
        $modelallocations = new LabourVehicleAllocation();
        if(Yii::$app->request->post()){ 
            $airport = Yii::$app->request->post()['OrderSearch']['fk_tbl_airport_of_operation_airport_name_id'];
            $dataProvider = $searchModel->allocationpendingorders(Yii::$app->request->queryParams, $airport);
        }else{
            $dataProvider = $searchModel->allocationpendingorders(Yii::$app->request->queryParams, false);
        }

        return $this->render('pending-order-allocation', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'modelallocation' => $modelallocations,
            ]);
    }



    public function actionPendingPorterAllocation($id_order)
    { 

        $modelallocations=new LabourVehicleAllocation();
        $orderDet=Order::find()->where(['id_order'=>$id_order])
                               ->andwhere(['fk_tbl_order_status_id_order_status'=>[1,3,9, 29, 2]])
                               ->with(['orderSpotDetails','fkTblOrderIdCustomer','fkTblOrderIdSlot'])->one();
           
        if(!empty($orderDet['orderSpotDetails'])){
            $address=$orderDet['orderSpotDetails']['area'].','.$orderDet['orderSpotDetails']['landmark'].','.$orderDet['orderSpotDetails']['address_line_1'];
        }else {
            $address="";
        }
        if($orderDet){
            if($orderDet->corporate_id != 0)
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
        }
        

        if(empty($orderDet)){
            return $this->redirect(array('pending-order-allocation'));
        }

        $slot_id=$orderDet['fk_tbl_order_id_slot'];

        if($slot_id==1 || $slot_id==2 || $slot_id==3 || $slot_id==7 || $slot_id==9)
        {
            if($orderDet->fk_tbl_order_status_id_order_status == 29){
                //for pick up orders with vehicle of forword route
                $service_type=1;
                $id_route=1;
                $status=28;
                $previous_status="Allocate for Delivery";
            }else{
                //for pick up orders with vehicle of forword route
                $service_type=1;
                $id_route=1;
                $status=3;
                $previous_status="open";
            }
        }else{
            if($orderDet->fk_tbl_order_status_id_order_status == 29){
                //for pick up orders with vehicle of forword route
                $service_type=1;
                $id_route=1;
                $status=28;
                $previous_status="Allocate for Delivery";
            }else{
                //for drop off orders with vehicle of backword route
                $service_type=2;
                $id_route=4;
                $status=9;
                $previous_status="Arrival into airport warehouse";
            }            
        } 


        if(!empty($_POST)){ 
         // print_r($orderDet->corporate_type);exit;
            $ispreviousassigned = VehicleSlotAllocation::find()->where(['fk_tbl_vehicle_slot_allocation_id_order'=>$id_order])->one();
            if(!empty($ispreviousassigned))
            {
                $ispreviousassigned->delete();
            }
            $id_emp = $_POST['LabourVehicleAllocation']['fk_tbl_labour_vehicle_allocation_id_employee'];
            $empVeh=LabourVehicleAllocation::find()->where(['fk_tbl_labour_vehicle_allocation_id_employee' => $_POST['LabourVehicleAllocation']['fk_tbl_labour_vehicle_allocation_id_employee']])->one();
            $vehId=$empVeh['fk_tbl_labour_vehicle_allocation_id_vehicle'];
            $vehDet=Vehicle::find()->where(['id_vehicle'=>$vehId])->one();            
            $empDet = Employee:: find()->where(['id_employee'=>$id_emp])->one();

            $newallocation = new VehicleSlotAllocation();
            $newallocation->order_date=$orderDet->order_date;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_slot=$slot_id;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_vehicle =$vehId;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_employee =$id_emp;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_order =$id_order;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_route = $vehDet->id_route;
            $newallocation->status=1;
            $newallocation->date_created=date("Y-m-d H:i:s");
            $newallocation->save();
            
            if($orderDet->fk_tbl_order_status_id_order_status == 29){
                //$remaining_units=$vehDet['remaining_units']-$orderDet['no_of_units'];
                Yii::$app->db->createCommand("UPDATE tbl_order set allocation=1, fk_tbl_order_status_id_order_status=28, order_status='Allocate for Delivery' where id_order=".$id_order)->execute();
                $new_order_history = [ 'fk_tbl_order_history_id_order'=>$id_order,
                    'from_tbl_order_status_id_order_status'=>$status,
                    'from_order_status_name'=>$previous_status,
                    'to_tbl_order_status_id_order_status'=>28,
                    'to_order_status_name'=>'Allocate for Delivery',
                    'date_created'=> date('Y-m-d H:i:s')
                   ];
            }else{
                //$remaining_units=$vehDet['remaining_units']-$orderDet['no_of_units'];
                Yii::$app->db->createCommand("UPDATE tbl_order set allocation=1, fk_tbl_order_status_id_order_status=6, order_status='Assigned' where id_order=".$id_order)->execute();
                $new_order_history = [ 'fk_tbl_order_history_id_order'=>$id_order,
                    'from_tbl_order_status_id_order_status'=>$status,
                    'from_order_status_name'=>$previous_status,
                    'to_tbl_order_status_id_order_status'=>6,
                    'to_order_status_name'=>'Assigned',
                    'date_created'=> date('Y-m-d H:i:s')
                   ];
            }

            $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$new_order_history)->execute();
            //Yii::$app->db->createCommand("UPDATE tbl_vehicle set remaining_units=".$remaining_units.", in_use=1 where id_vehicle=".$vehId)->execute();
            
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
            
            if($orderDet->reschedule_luggage!=1){
                if($orderDet->order_transfer==1){
                        if($status == 28){ 
                            $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForDelivery', $empDet['name']);
                        }else if(in_array($status, [9,3])){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForPickup', $empDet['name']);
                        } 
                    }
            }else{
                if($orderDet->order_transfer==1){
                        if($status == 28){ 
                            $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForDelivery', $empDet['name']);
                        }else if(in_array($status, [9,3])){
                            $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForPickup', $empDet['name']);
                        } 
                    }else{
                        $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);
                    }
            } 
           // print_r($orderDet->reschedule_luggage);exit;

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
                
                //$pending_amount = $order_1_pending_amount + $order_2_pending_amount;
                $pending_amount = $model1['reschedule_order_details']['order']['modified_amount'] + $orderDet->modified_amount;
                //$text = ($pending_amount == 0) ? '' : 'Amount pending Rs.'.$pending_amount.' due. Kindly pay the same before delivery.';
                $text = '';
                if($orderDet->corporate_id == 0){
                    $text = ($pending_amount == 0 ) ? '' : ($pending_amount > 0 ) ? 'Amount pending Rs.'.$pending_amount.' due to Order Modification under Order#'.$model1['reschedule_order_details']['order']['order_number'].' & this current service. Kindly pay the same before delivery' : 'refund of Rs. '.$pending_amount.' is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account.';
                }

                $if_previous_order_undelivered = Order::getIsundelivered($prev_order_id);
                if($if_previous_order_undelivered){ 
                      
                      User::sendsms($customer_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order at'.$address.'.'.$text.PHP_EOL.' Thanks Carterx.in');

                      if($istravel_person == 1)
                      {
                          
                          User::sendsms($traveller_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');
                      }
                      if($orderDet->orderSpotDetails->assigned_person==1){
                        
                        User::sendsms($location_contact,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');
                      }

                }else{
                    //$sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);

                    User::sendsms($customer_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by '.$customer_name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order at'.$address.'.'.$text.PHP_EOL.' Thanks carterx.in');

                      if($istravel_person == 1)
                      {
                          
                          User::sendsms($traveller_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by '.$customer_name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');
                      }
                      if($orderDet->orderSpotDetails->assigned_person==1){
                        
                        User::sendsms($location_contact,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by '.$customer_name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');
                      }

                }

            }
            else
            {  

                if($orderDet->orderSpotDetails){
                    
                    $amount_pending_msg = '';
                    if($orderDet->corporate_id == 0){
                        $amount_pending_msg = ($order_2_pending_amount == 0 ) ? '' : ($order_2_pending_amount > 0 ) ? 'Amount due for the order '.$order_2_pending_amount.'' : 'refund of Rs. '.$order_2_pending_amount.' is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account.';
                    }
                    if($orderDet->corporate_type == 1){
                        $amount_pending_msg = ($order_2_pending_amount == 0 ) ? '' : ($order_2_pending_amount > 0 ) ? 'Amount due for the order '.$order_2_pending_amount.'' : '';
                        
                    }

                    if($orderDet->order_transfer!=1){
                        
                        if($orderDet->corporate_type != 1){
                            //print_r($status);exit;
                        if($status == 9){
                            $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']); 
                           
                        }else{ 
                                $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_pickup', $empDet['name']); 
                        }
                    }else{  
                        // if($service_type==1){
                        //     $customer_message = 'Dear Customer, your Order #'.$orderDet->order_number. 'is  scheduled for Delivery at Airport .'.$amount_pending_msg.'. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';

                        //     $traveller_message = 'Dear Customer, your Order #'.$orderDet->order_number.' placed by '.$customer_name.' is  scheduled for Delivery at Airport .'.$amount_pending_msg.'. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';

                        //     $location_message = 'Dear Customer, your Order #'.$orderDet->order_number.' placed by '.$customer_name.' is  scheduled for Delivery at Airport .'.$amount_pending_msg.'.'.$empDet['name'].' is allocated to Deliver your order. -CarterX';
     
                        // }else{
                        //     $customer_message = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. '.$amount_pending_msg.'. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;

                        //     $traveller_message = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. '.$amount_pending_msg.'. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;

                        //      $location_message = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. '.$amount_pending_msg.'. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588';
                        // }
                      
                        if($status == 9){ 
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']); 
                        }else{ 
                                $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_pickup', $empDet['name']); 
                        }
                        // $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);
                        // $service_type==1 ? User::sendsms($customer_number,$customer_message) : User::sendsms($customer_number,$customer_message);


                        // /*SMS to assigned person*/
                        // if($orderDet->travel_person==1){     
                        //    $service_type==1 ? User::sendsms($traveller_number,$traveller_message) :User::sendsms($traveller_number,$traveller_message)  ;
                        // }

                        // if($orderDet->orderSpotDetails->assigned_person==1){
                            
                        //     $service_type==1 ? User::sendsms($location_contact,$location_message) : User::sendsms($location_contact,$location_message);
                        // }
                    }
                    }else{
                        if($status == 9){ 
                            $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']); 
                        }else{ 
                                $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_pickup', $empDet['name']); 
                        }
                    }
                    
                     

                }
            }

            return $this->redirect(array('vehicle-slot-allocation/index'));
        }
        return $this->render('pending-porter-allocation', [
            'model' => $this->findModel($id_order),
            'modelallocation' => $modelallocations,
        ]);
    }

    
    
    public function actionPendingPorterAllocation11($id_order)
    {
        $modelallocations=new LabourVehicleAllocation();
        $orderDet=Order::find()->where(['id_order'=>$id_order])
                               ->andwhere(['fk_tbl_order_status_id_order_status'=>[3,9]])
                               ->with(['orderSpotDetails','fkTblOrderIdCustomer','fkTblOrderIdSlot'])->one();
        if($orderDet->corporate_id != 0)
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

        if(empty($orderDet)){
              return $this->redirect(array('pending-order-allocation'));
        }

        $slot_id=$orderDet['fk_tbl_order_id_slot'];

        if($slot_id==1 || $slot_id==2 || $slot_id==3 || $slot_id==7 || $slot_id==9)
        {
            //for pick up orders with vehicle of forword route
            $service_type=1;
            $id_route=1;
            $status=3;
            $previous_status="open";
        }else{
            //for drop off orders with vehicle of backword route
            $service_type=2;
            $id_route=4;
            $status=9;
            $previous_status="Arrival into airport warehouse";            
        } 

        if(!empty($_POST)){

            $ispreviousassigned = VehicleSlotAllocation::find()->where(['fk_tbl_vehicle_slot_allocation_id_order'=>$id_order])->one();
            if(!empty($ispreviousassigned))
            {
                $ispreviousassigned->delete();
            }
            $id_emp = $_POST['LabourVehicleAllocation']['fk_tbl_labour_vehicle_allocation_id_employee'];
            $empVeh=LabourVehicleAllocation::find()->where(['fk_tbl_labour_vehicle_allocation_id_employee' => $_POST['LabourVehicleAllocation']['fk_tbl_labour_vehicle_allocation_id_employee']])->one();
            $vehId=$empVeh['fk_tbl_labour_vehicle_allocation_id_vehicle'];
            $vehDet=Vehicle::find()->where(['id_vehicle'=>$vehId])->one();            
            $empDet = Employee::find()->where(['id_employee'=>$id_emp])->one();

            $newallocation = new VehicleSlotAllocation();
            $newallocation->order_date=$orderDet->order_date;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_slot=$slot_id;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_vehicle =$vehId;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_employee =$id_emp;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_order =$id_order;
            $newallocation->fk_tbl_vehicle_slot_allocation_id_route = $vehDet->id_route;
            $newallocation->status=1;
            $newallocation->date_created=date("Y-m-d H:i:s");
            $newallocation->save();
            
            //$remaining_units=$vehDet['remaining_units']-$orderDet['no_of_units'];
            Yii::$app->db->createCommand("UPDATE tbl_order set allocation=1, fk_tbl_order_status_id_order_status=6, order_status='Assigned' where id_order=".$id_order)->execute();
            $new_order_history = [ 'fk_tbl_order_history_id_order'=>$id_order,
                'from_tbl_order_status_id_order_status'=>$status,
                'from_order_status_name'=>$previous_status,
                'to_tbl_order_status_id_order_status'=>6,
                'to_order_status_name'=>'Assigned',
                'date_created'=> date('Y-m-d H:i:s')
               ];

            $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$new_order_history)->execute();
            //Yii::$app->db->createCommand("UPDATE tbl_vehicle set remaining_units=".$remaining_units.", in_use=1 where id_vehicle=".$vehId)->execute();
            
            $order_2_pending_amount = $orderDet->luggage_price - $orderDet->amount_paid ;
            $travell_passenger_contact = $orderDet->travell_passenger_contact;
            $istravel_person = $orderDet->travel_person;
            if($orderDet->reschedule_luggage==1)
            {
                $prev_order_id = min($orderDet->id_order, $orderDet->related_order_id);
                $orderDet1 = Order::getOrderdetails($prev_order_id);
                $istravel_person = $orderDet1['order']['travel_person'];
                $travell_passenger_contact = $orderDet1['order']['travell_passenger_contact'];


                $model1['reschedule_order_details'] = Order::getorderdetails($orderDet->related_order_id);
                $order_1_pending_amount = $model1['reschedule_order_details']['order']['luggage_price']-$model1['reschedule_order_details']['order']['amount_paid'];
                
                //$pending_amount = $order_1_pending_amount + $order_2_pending_amount;
                $pending_amount = $model1['reschedule_order_details']['order']['luggage_price'] + $orderDet->modified_amount;
                //$text = ($pending_amount == 0) ? '' : 'Amount pending Rs.'.$pending_amount.' due. Kindly pay the same before delivery.';
                $text = ($pending_amount == 0 ) ? '' : 'Amount pending Rs.'.$pending_amount.' due to Order Modification under Order#'.$model1['reschedule_order_details']['order']['order_number'].' & this current service. Kindly pay the same before delivery';

                $if_previous_order_undelivered = Order::getIsundelivered($prev_order_id);
                if($if_previous_order_undelivered){
                      
                      User::sendsms($mobile,'Dear Customer, your Order#'.$orderDet->order_number.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');


                      if($istravel_person == 1)
                      {
                          
                          User::sendsms($travell_passenger_contact,'Dear Customer, your Order#'.$orderDet->order_number.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');

                          

                      }
                      if($orderDet->orderSpotDetails->assigned_person==1){
                        
                        User::sendsms($orderDet->orderSpotDetails->person_mobile_number,'Dear Customer, your Order#'.$orderDet->order_number.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');

                      }

                }else{

                    User::sendsms($mobile,'Dear Customer, your Order#'.$orderDet->order_number.' is open for re-scheduled delivery by '.$name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');

                      if($istravel_person == 1)
                      {
                          
                          User::sendsms($travell_passenger_contact,'Dear Customer, your Order#'.$orderDet->order_number.' is open for re-scheduled delivery by '.$name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');
                      }
                      if($orderDet->orderSpotDetails->assigned_person==1){
                        
                        User::sendsms($orderDet->orderSpotDetails->person_mobile_number,'Dear Customer, your Order#'.$orderDet->order_number.' is open for re-scheduled delivery by '.$name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');
                      }

                }

            }
            else
            {
              //  if($orderDet->service_type == 1){
                    // $msg_booking_pickup = "Dear Customer, your Order #".$orderDet->order_number." is open.  ".$name."  is allocated to pick the order. -CarterX";

                    // $location_contact = "Hello,  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order. -CarterX";

                    // $travel_passenger = "Dear Custlomer, your  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order. -CarterX";
                    
              //  }else{
                    // $msg_booking_pickup = "Dear Customer, your Order #".$orderDet->order_number." is open.  ".$empDet['name']."  is allocated to pick-up your order at arrivals at the Airport. -CarterX";

                    // $location_contact = "Dear Customer, your  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order at arrivals at the Airport. -CarterX";

                    // $travel_passenger = "Dear Customer, your  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order at arrivals at the Airport. -CarterX";
                   

                //}
                if($orderDet->orderSpotDetails){
                    /*SMS to registered user*/     
                    //Hello, Order #ORD12345 is ready scheduled for Delivery at "Location address".  Payment due for the order "display amount".  Carter "Name of porter/porterX allocated" - "Number" is allocated to Deliver your order. Thanks carterx.in     
                    /*$service_type==1 ? User::sendsms($orderDet->fkTblOrderIdCustomer->mobile,'Dear Customer, your Order #'.$orderDet->order_number.' is open'.' Carter '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to pick the order. '.PHP_EOL.' Thanks carterx.in') : User::sendsms($orderDet->fkTblOrderIdCustomer->mobile,'Dear Customer, your Order #'.$orderDet->order_number.' is ready scheduled for delivery between  '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.PHP_EOL.' Thanks carterx.in');*/
                    $amount_pending_msg = ($order_2_pending_amount > 0 ) ? 'Payment due for the order '.$order_2_pending_amount.'' : '';
                    // $service_type==1 ? User::sendsms($mobile,$msg_booking_pickup) : User::sendsms($mobile,$msg_booking_pickup);

                //     if($orderDet->service_type == 1){
                //     // $msg_booking_pickup = "Dear Customer, your Order #".$orderDet->order_number." is open.  ".$name."  is allocated to pick the order. -CarterX";

                //     // $location_contact = "Hello,  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order. -CarterX";

                //     // $travel_passenger = "Dear Custlomer, your  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order. -CarterX";
                //       $msg_booking_pickup = 'Dear Customer, your Order #'.$orderDet->order_number. 'is  scheduled for Delivery at Airport .'.$amount_pending_msg.' . Please make the payment immediately to avoid delays at delivery. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';
                //     $travel_passenger = 'Dear Customer, your Order #'.$orderDet->order_number.' placed by '.$customer_name.' is  scheduled for Delivery at Airport . '.$amount_pending_msg.'. Please make the payment immediately to avoid delays at delivery. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';

                //     $location_contact = 'Dear Customer, your Order #'.$orderDet->order_number.' placed by '.$customer_name.' is  scheduled for Delivery at Airport . '.$amount_pending_msg.'. Please make the payment immediately to avoid delays at delivery. '.$empDet['name'].' is allocated to Deliver your order. -CarterX';
                // }else{
                //     // $msg_booking_pickup = "Dear Customer, your Order #".$orderDet->order_number." is open.  ".$empDet['name']."  is allocated to pick-up your order at arrivals at the Airport. -CarterX";

                //     // $location_contact = "Dear Customer, your  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order at arrivals at the Airport. -CarterX";

                //     // $travel_passenger = "Dear Customer, your  Order #".$orderDet->order_number." placed by ".$name." is open.  ".$empDet['name']." is allocated to pick the order at arrivals at the Airport. -CarterX";
                //     $msg_booking_pickup = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. Payment due for the order is '.$amount_pending_msg.'. Please make the payment immediately for smoother delivery experience. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;

                //         $location_contact = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. Payment due for the order is '.$amount_pending_msg.'. Please make the payment immediately for smoother delivery experience. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588' ;

                //          $travel_passenger = 'Dear Customer, your Order #'.$orderDet->order_number.' is  scheduled for Delivery at '.$orderDet->orderSpotDetails->address_line_1.'. Payment due for the order is '.$amount_pending_msg.'. Please make the payment immediately for smoother delivery experience. '.$empDet['name'].' is allocated to Deliver your order. -CarterX +91-9110635588';

                // }
                    $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);
                    // /*SMS to assigned person*/
                    // if($orderDet->travel_person==1){
                                           
                    //    $service_type==1 ? User::sendsms($orderDet->travell_passenger_contact,$travel_passenger) : User::sendsms($orderDet->travell_passenger_contact,$travel_passenger);
                    // }

                    // if($orderDet->orderSpotDetails->assigned_person==1){
                        
                    //     $service_type==1 ? User::sendsms($orderDet->orderSpotDetails->person_mobile_number,$location_contact) : User::sendsms($orderDet->orderSpotDetails->person_mobile_number,$location_contact);
                    // }


                }
            }

            return $this->redirect(array('vehicle-slot-allocation/index'));
        }
        return $this->render('pending-porter-allocation', [
            'model' => $this->findModel($id_order),
            'modelallocation' => $modelallocations,
        ]);
    }

    public function actionOrderConfrimationPdf($order_number){

        try{
            $orders=Order::find()->where(['order_number'=>$order_number])->one();

            $order_details['order_details']=Order::getorderdetails($orders['id_order']);
            
            ob_start();
            $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];

            echo Yii::$app->view->render("@app/mail/order_confirmation_pdf_template",array('data' => $order_details));
        
            $data = ob_get_clean();

            try
            {
                $html2pdf = new \mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=0,$mgr=0,$mgt=8,$mgb=8,$mgh=9,$mgf=0, $orientation='P');
                $html2pdf->setDefaultFont('dejavusans');
                $html2pdf->showImageErrors = true;

                $html2pdf->writeHTML($data);

                /*this footer will be added into the last of the page , if you want to display in all of the pages then cut this footer
                line and paster above the $html2pdf->writeHTML($data);,then footer will render in all pages*/

                $html2pdf->SetFooter('<div style="width:100%;padding: 16px; text-align: center;background: #2955a7;color: white;font-size: 15px;position: absolute;bottom: 0px;font-style:normal;font-weight:200;">Luggage Transfer Simplified</div>');

                $html2pdf->Output($path."order_".$order_details['order_details']['order']['order_number'].".pdf",'F');

                /*Preparing file path and folder path for response */
                $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
                $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";

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

    public function actionOrderConfrimationCorporatePdf($order_number){
            
            try{
                $orders=Order::find()->where(['order_number'=>$order_number])->one();

                $order_details['order_details']=Order::getorderdetails($orders['id_order']);
            
                ob_start();
                $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];

                echo Yii::$app->view->render("@app/mail/order_confirmation_corporate_pdf_template",array('data' => $order_details));

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

    public function actionOrderpdf($order_number)
    {
            ob_start();
            //echo $this->renderPartial(“createnewpdf“,array(‘content’=>$content));
            /*$order_details=Order::find()->where(['order_number'=>$order_number])
                                        ->select(['tbl_order.*'])
                                        ->with('orderSpotDetails','fkTblOrderIdPickDropLocation','fkTblOrderIdSlot','fkTblOrderIdCustomer')->one();*/
            $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];
            $getorder=Order::find()->where(['order_number'=>$order_number])->all();
            
            //print_r($getorder);exit;
            $i=0;
            $order_details = [];
            foreach ($getorder as $orders) {
                $order_details[$i]=Order::getorderdetails($orders['id_order']);
                $status = Order::getcustomerstatus($order_details[$i]['order']['id_order_status'], $order_details[$i]['order']['service_type'],$orders['id_order']);
               
                  if($order_details[$i]['order']['reschedule_luggage'] == 1){
                      $order_details[$i]['order']['customer_id_order_status'] = $status['customer_id_order_status']==6 ? '3': $status['customer_id_order_status'];
                      $order_details[$i]['order']['customer_order_status_name'] = $status['status_name'] == 'Assigned'? 'Open' : $status['status_name'];
                      $order_details[$i]['order']['travell_passenger_name'] = ($order_details[$i]['order']['travell_passenger_name']==NULL) ? $reschedule_order_details1['order']['travell_passenger_name'] : $order_details[$i]['order']['travell_passenger_name'];
                      $order_details[$i]['order']['travell_passenger_contact'] = ($order_details[$i]['order']['travell_passenger_contact']==NULL) ? $reschedule_order_details1['order']['travell_passenger_contact'] : $order_details[$i]['order']['travell_passenger_contact'];
                  }else{
                      $order_details[$i]['order']['customer_id_order_status'] = $status['customer_id_order_status'];
                      $order_details[$i]['order']['customer_order_status_name'] = $status['status_name'];
                  }
                  $i++;
            }
            //print_r($order_details);exit;
            echo $this->renderPartial("order-pdf",array('order_details_pdf' => $order_details));
            $data = ob_get_clean();

            
            try
            {
                if(count($order_details) == 1)
                {
                    //$html2pdf = new \HTML2PDF('L', 'A4', 'en');
                    $html2pdf = new \HTML2PDF('P', array(110, 250), 'en', true, 'UTF-8', array(0, 0, 0, 0));
                }
                else
                {
                    //$html2pdf = new \HTML2PDF('L', 'A4', 'en');
                    $html2pdf = new \HTML2PDF('P', array(110, 420), 'en', true, 'UTF-8', array(0, 0, 0, 0));
                }
            //$html2pdf = new \HTML2PDF('L', 'A4', 'en');
            //$html2pdf = new \HTML2PDF('P', array(110, 320), 'en', true, 'UTF-8', array(0, 0, 0, 0));
            //$html2pdf->setModeDebug();
            $html2pdf->setDefaultFont('dejavusans');
            //$html2pdf->pdf->IncludeJS(“print(true);”);              // To open Printer dialog box
            $html2pdf->writeHTML($data);
            //$html2pdf->Output("name.pdf",'D');                     // To download PDF
            //$html2pdf->Output("order_".$order_number.".pdf",'D');                  // To display PDF in browser
            $html2pdf->Output($path."order_".$order_number.".pdf",'F');           // To save the pdf to a specific folder
                echo JSON::encode(array('status' => true, 'path' =>Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_number.'.pdf'));
            }
            catch(HTML2PDF_exception $e) {
                echo JSON::encode(array('status' => false, 'message' => $e));
            /*echo $e;
            exit;*/
            }

    }

    public function actionFileupload($option)
    {
        if(isset($_GET['id_order'])){
            $order_id = $_GET['id_order'];
        }
        if(isset($_GET['id'])){
            $order_id = $_GET['id'];
        }
        // $order_id = $_GET['id_order'] ? $_GET['id_order'] : $_GET['id'];
        switch ($option) {
            case "customer_profile_picture":
                $extension = explode(".", $_FILES["customer_profile_picture"]["name"]);
                $rename_customer_profile_picture = "customer_profile_picture_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_customer_profile_picture;
                //print_r($path);exit;
                move_uploaded_file($_FILES['customer_profile_picture']['tmp_name'],$path);
                if(isset($_POST['id_customer']))
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_customer set customer_profile_picture='".$rename_customer_profile_picture."' where id_customer='".$_POST['id_customer']."'")->execute();
                }
                echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_customer_profile_picture]);
                break;

            case "customer_document":
                $extension = explode(".", $_FILES["customer_document"]["name"]);
                $rename_customer_document = "customer_document_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_customer_document;
                move_uploaded_file($_FILES['customer_document']['tmp_name'],$path);
                if(isset($_POST['id_customer']))
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_customer set document='".$rename_customer_document."' where id_customer='".$_POST['id_customer']."'")->execute();
                }
                echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_customer_document]);
                break;

            case "someone_else_document":
                $extension = explode(".", $_FILES["someone_else_document"]["name"]);
                $rename_someoneelse_docname = "someone_else_document_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_someoneelse_docname;
                move_uploaded_file($_FILES['someone_else_document']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_order set someone_else_document='".$rename_someoneelse_docname."', someone_else_document_verification = 1 where id_order='".$_GET['id_order']."'")->execute();
                }
                
                return $this->redirect(['update', 'id' => $_GET['id_order']]);
                break;

            case "invoice":
                $extension = explode(".", $_FILES["invoice"]["name"]);
                $rename_invoice_docname = "invoice_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_invoice_docname;
                move_uploaded_file($_FILES['invoice']['tmp_name'],$path);

                if(isset($_GET['id_order']))
                {
                    $invoice = ['invoice'=>$rename_invoice_docname,'fk_tbl_mall_invoices_id_order'=>$_GET['id_order']];
                    Yii::$app->db->createCommand()->insert('tbl_mall_invoices',$invoice)->execute();
                    $result = Yii::$app->db->createCommand("UPDATE tbl_order_spot_details set invoice_verification = 1 where fk_tbl_order_spot_details_id_order='".$_GET['id_order']."'")->execute();

                }
                echo Json::encode(['status'=>true,'message'=>'upload Successful','file_name'=>$rename_invoice_docname]);
                break;

            case "pickupInvoice":
                $extension = explode(".", $_FILES['pickupInvoice']['name']);
                $rename_pickup_invoice_docname = "pickupInvoice_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_pickup_invoice_docname;
                move_uploaded_file($_FILES['pickupInvoice']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set pickupInvoice="'.$rename_pickup_invoice_docname.'" where orderId='.$_GET['id_order'])->execute();
                }
                // echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_invoice_docname]);
                break;

            case "pickupBookingConfirmation":
                $extension = explode(".", $_FILES['pickupBookingConfirmation']['name']);
                $rename_pickup_hotel_booking_docname = "pickup_booking_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_pickup_hotel_booking_docname;
                move_uploaded_file($_FILES['pickupBookingConfirmation']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set  pickupBookingConfirmation="'.$rename_pickup_hotel_booking_docname.'" where orderId='.$_GET['id_order'])->execute();
                }
                break;

            case "dropInvoice":
                $extension = explode(".", $_FILES['dropInvoice']['name']);
                $rename_drop_invoice_docname = "dropInvoice_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_drop_invoice_docname;
                move_uploaded_file($_FILES['dropInvoice']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set dropInvoice="'.$rename_drop_invoice_docname.'" where orderId='.$_GET['id_order'])->execute();
                }
                // echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_invoice_docname]);
                break;

            case "dropBookingConfirmation":
                $extension = explode(".", $_FILES['dropBookingConfirmation']['name']);
                $rename_drop_hotel_booking_docname = "drop_booking_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_drop_hotel_booking_docname;
                move_uploaded_file($_FILES['dropBookingConfirmation']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set  dropBookingConfirmation="'.$rename_drop_hotel_booking_docname.'" where orderId='.$_GET['id_order'])->execute();
                }
                break;

            case "hotel_booking":
                $extension = explode(".", $_FILES["hotel_booking"]["name"]);
                $rename_hotel_booking_docname = "hotel_booking_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_hotel_booking_docname;
                move_uploaded_file($_FILES['hotel_booking']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_order_spot_details set   booking_confirmation_file='".$rename_hotel_booking_docname."', hotel_booking_verification = 1 where fk_tbl_order_spot_details_id_order='".$_GET['id_order']."'")->execute();
                }
                return $this->redirect(['update', 'id' => $_GET['id_order']]);
                break;
                                            
            case "flight_ticket":
                $extension = explode(".", $_FILES["flight_ticket"]["name"]);
                $rename_flight_ticket = "flight_ticket_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_ticket/'.$rename_flight_ticket;
                move_uploaded_file($_FILES['flight_ticket']['tmp_name'],$path);
                if(isset($_GET['id_order']))
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_order set ticket='".$rename_flight_ticket."', flight_verification = 1 where id_order='".$_GET['id_order']."'")->execute();
                }
                return $this->redirect(['update', 'id' => $_GET['id_order']]);
                break;

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

                    $saveData = [];
                    $saveData['order_id'] = $order_id;
                    $saveData['description'] = 'Order modification order related images';
                    $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                    $saveData['employee_name'] = Yii::$app->user->identity->name;;
                    $saveData['module_name'] = 'Order Related Images';
                    Yii::$app->Common->ordereditHistory($saveData);

                    if(isset(Yii::$app->request->post()['image_upload'])){
                        
                        return $this->redirect(['kiosk-order-update', 'id' => $_GET['id_order']]); 
                    }else{
                    
                        return $this->redirect(['update', 'id' => $_GET['id_order']]); 
                    }
                } else {
                    if(isset(Yii::$app->request->post()['image_upload'])){
                        return $this->redirect(['kiosk-order-update', 'id' => $_GET['id_order']]); 
                    }else{
                        return $this->redirect(['update', 'id' => $_GET['id_order']]); 
                    }
                    //return $this->redirect(['update', 'id' => $_GET['id_order']]); 
                }
                                            
                break;

            case "corporate_order_image":
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
                    
                    $saveData = [];
                    $saveData['order_id'] = $order_id;
                    $saveData['description'] = 'Order modification order related images';
                    $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                    $saveData['employee_name'] = Yii::$app->user->identity->name;;
                    $saveData['module_name'] = 'Order Related Images';
                    Yii::$app->Common->ordereditHistory($saveData);

                    return $this->redirect(['thirdparty-update', 'id' => $_GET['id_order']]); 
                } else {
                    return $this->redirect(['thirdparty-update', 'id' => $_GET['id_order']]); 
                }                        
                break;

            case "signature":
                $order = Yii::$app->db->createCommand("SELECT o.signature1,o.signature2 FROM tbl_order o where id_order='".$_POST['id_order']."'")->queryOne();
                if($order['signature1'] != null && isset($order['signature1']))
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_order set signature2='".$_FILES['signature']['name']."' where id_order='".$_POST['id_order']."'")->execute();
                }
                else
                {
                    $result = Yii::$app->db->createCommand("UPDATE tbl_order set signature1='".$_FILES['signature']['name']."' where id_order='".$_POST['id_order']."'")->execute();
                }
                
                $path=Yii::$app->params['document_root'].'basic/web/uploads/signatures/'.$_FILES['signature']['name'];
                move_uploaded_file($_FILES['signature']['tmp_name'],$path);
                echo Json::encode(['status'=>true,'message'=>'Upload Successful']);
                break;

            case "order_receipt":
                $extension = explode(".", $_FILES["order_receipt"]["name"]);
                $rename_order_receipt_docname = "order_receipt_".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_receipts/'.$rename_order_receipt_docname;
                move_uploaded_file($_FILES['order_receipt']['tmp_name'],$path);
                return Json::encode(['status'=>true,'message'=>'upload Successful','file_name'=>$rename_order_receipt_docname]);
                break;

             case "airasia_receipt":
                $extension = explode(".", $_FILES["airasia_receipt"]["name"]);
                $airasia_receipt_docname = "airasia_receipt".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/airasia_receipt/'.$airasia_receipt_docname;
                move_uploaded_file($_FILES['airasia_receipt']['tmp_name'],$path);
                return Json::encode(['status'=>true,'message'=>'upload Successful','file_name'=>$airasia_receipt_docname]);
                break;

            case "security_image":
                $extension = explode(".", $_FILES["security_image"]["name"]);
                $rename_security_image_docname = "security_image".date('mdYHis').".".$extension[1];
                $path=Yii::$app->params['document_root'].'basic/web/uploads/security_image/'.$rename_security_image_docname;
                move_uploaded_file($_FILES['security_image']['tmp_name'],$path);
                return Json::encode(['status'=>true,'message'=>'upload Successful','file_name'=>$rename_security_image_docname]);
                break;

            default:
                echo Json::encode(['status'=>false,'message'=>'Upload Failed']);
        }
    }


    public function actionDeleteorderimage($type, $oii, $oi)
    {        
        $isorderimage = OrderImages::find()->where(['id_order_image'=>$oii])->one();
        if($isorderimage){
            if($isorderimage['image'] != '')
            {               
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$isorderimage['image'];
                unlink($path);                
                $isorderimage->delete();
                return $this->redirect(['update', 'id' => $oi ]); 
            }
        }

    }

    public function actionDeletecorporateorderimage($type, $oii, $oi)
    {        
        $isorderimage = OrderImages::find()->where(['id_order_image'=>$oii])->one();
        if($isorderimage){
            if($isorderimage['image'] != '')
            {               
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$isorderimage['image'];
                unlink($path);                
                $isorderimage->delete();
                return $this->redirect(['thirdparty-update', 'id' => $oi ]); 
            }
        }

    }

    public function actionDeleteorderimagekiosk($type, $oii, $oi)
    {        
        $isorderimage = OrderImages::find()->where(['id_order_image'=>$oii])->one();
        if($isorderimage){
            if($isorderimage['image'] != '')
            {               
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$isorderimage['image'];
                unlink($path);                
                $isorderimage->delete();
                return $this->redirect(['kiosk-order-update', 'id' => $oi ]); 
            }
        }

    }
    
    public function actionDeleteGroup()
    {
          $searchModel = new OrderGroup();
        // $dataProvider = $searchModel->searchcorporate(Yii::$app->request->queryParams);
        // $dataProvider =  OrderGroup::find()->where(['status'=>1])->all();
          $dataProvider = Yii::$app->db->createCommand("SELECT br.order_group_name,br.id_order from tbl_order_group br WHERE br.status=1")->queryAll();
         // print_r($dataProvider);exit;
        return $this->render('/order-group/index', [
             'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);

       
    }

    public function actionUpdatepaymentdetailskiosk(){
        $order_id = $_GET['id'];
        $order = Order::find()->where(['id_order'=>$order_id])->one();
        if($order){
            if($_FILES){
                $file_details = json_decode($this->actionFileupload('order_receipt'));
                $file_name = $file_details->file_name;
            }
            else{
                $file_name= '';
            }
            if($order->payment_method == 'COD'){
                $model = new OrderPaymentDetails();
                $model->amount_paid = $_POST['amount_paid'];
                $model->payment_receipt = $file_name;
                $model->id_order = $order_id;
                $model->payment_type = $_POST['payment_type'];
                $model->payment_status= 'Success';
                $model->value_payment_mode ='Amount Collected';
                $model->date_created= date('Y-m-d H:i:s'); 
                $model->date_modified= date('Y-m-d H:i:s'); 
                $model->save(false);
            }else{
                $model = OrderPaymentDetails::find()->where(['id_order'=>$order_id])->one();
                $model->payment_receipt = $file_name;
                $model->id_order = $order_id;
                $model->save(false);
            }
            if(isset($_POST['kiosk_update'])){
                return $this->redirect(['employee/update-kiosk', 'id' => $order_id, 'mobile' => Yii::$app->request->post()['mobile']]);
            }else{
                return $this->redirect(['update', 'id' => $order_id ]); 
            }
        }
        

    }
    public function actionUpdatepaymentdetailskioskcorporate(){
        $order_id = $_GET['id'];
        $order = Order::find()->where(['id_order'=>$order_id])->one();
        if($order){
            if($_FILES){
                $file_details = json_decode($this->actionFileupload('order_receipt'));
                $file_name = $file_details->file_name;
            }
            else{
                $file_name= '';
            }
            if($order->payment_method == 'COD'){
                $model = new OrderPaymentDetails();
                $model->amount_paid = $_POST['amount_paid'];
                $model->payment_receipt = $file_name;
                $model->id_order = $order_id;
                $model->payment_type = $_POST['payment_type'];
                $model->payment_status= 'Success';
                $model->value_payment_mode ='Amount Collected';
                $model->date_created= date('Y-m-d H:i:s'); 
                $model->date_modified= date('Y-m-d H:i:s'); 
                $model->save(false);
            }else{
                $model = OrderPaymentDetails::find()->where(['id_order'=>$order_id])->one();
                $model->payment_receipt = $file_name;
                $model->id_order = $order_id;
                $model->save(false);
            }
            if($_POST['kiosk_update'] == 'corporate'){
                return $this->redirect(['employee/update-kiosk-corporate', 'id' => $order_id, 'mobile' => Yii::$app->request->post()['mobile']]);
            }else{
                return $this->redirect(['employee/update-kiosk', 'id' => $_GET['id_order'], 'mobile' =>Yii::$app->request->post()['mobile']]);
            }
        }
        

    }
    public function actionUpdategeneralorderprice(){
        $order_id = $_GET['id'];
        $order = Order::find()->where(['id_order'=>$order_id])->one();
        $model = new OrderPaymentDetails();
        if($order){
            $model->amount_paid = $_POST['total_amount_paid'];
            $model->id_order = $order_id;
            $model->payment_type = $_POST['payment_mode_excess'];
            $model->payment_status= 'Success';
            $model->transaction_id ='';
            $model->value_payment_mode ='Modified Amount';
            $model->date_created= date('Y-m-d H:i:s'); 
            $model->date_modified= date('Y-m-d H:i:s'); 
            if($model->save()){
                $saveData = [];
                $saveData['order_id'] = $_GET['id'];
                $saveData['description'] = 'Order modification on amount paid details';
                $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                $saveData['employee_name'] = Yii::$app->user->identity->name;;
                $saveData['module_name'] = 'Amount Paid';

                Yii::$app->Common->ordereditHistory($saveData);

                $data['order_details'] = Order::getorderdetails($order_id);
                $total_amount_paid = $order->amount_paid + $_POST['total_amount_paid'];
                $order->amount_paid = $order->amount_paid + $_POST['total_amount_paid'];
                if(!($_POST['payment_mode_excess'] == 'COD')){
                    $order->modified_amount = $order->luggage_price - $total_amount_paid;
                }
                $customers  = Order::getcustomername($order->travell_passenger_contact);
                $customer_name = ($customers) ? $customers->name : '';
                $message = "Dear Customer, your Order ".$order->order_number." Reference ".$order->flight_number." placed by ".$customer_name." is levied a value of Rs ".$_POST['total_amount_paid'].". Payment has been collected by cash/card by kiosk representative. Please treat this sms as confirmation and receipt for the payment made. Thanks carterx.in";
                $traveller_number = $data['order_details']['order']['traveler_country_code'].$order->travell_passenger_contact;
                User::sendsms($traveller_number,$message);
                $order->save(false);
            }
            if(isset($_POST['thirdpary_update_amount_paid'])){
                return $this->redirect(['thirdparty-update', 'id' => $order_id ]);
            }else{
                return $this->redirect(['update', 'id' => $order_id ]);    
            }
             
        }
    }
    public function actionUpdatepaymentdetails(){
        $order_id = $_GET['id'];
        $order = Order::find()->where(['id_order'=>$order_id])->one();
        $model = new OrderPaymentDetails();
        if($order){
            if($_FILES){
                $file_details = json_decode($this->actionFileupload('order_receipt'));
                $file_name = $file_details->file_name;
            }
            else{
                $file_name= '';
            }
            $model->amount_paid = $_POST['amount_paid'];
            $model->payment_receipt = $file_name;
            $model->id_order = $order_id;
            $model->payment_type = 'Refund';
            $model->payment_status= 'Refunded';
            //$model->payment_transaction_id ='';
            $model->value_payment_mode = 'Refund Amount';
            $model->transaction_id ='';
            $model->date_created= date('Y-m-d H:i:s'); 
            $model->date_modified= date('Y-m-d H:i:s'); 
            if($model->save()){
                if($order->modified_amount){
                    $order->modified_amount = $order->modified_amount + $_POST['amount_paid'];
                }
                $order->save(false);

                $saveData = [];
                $saveData['order_id'] = $_GET['id'];
                $saveData['description'] = 'Order modification on payment details';
                $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                $saveData['employee_name'] = Yii::$app->user->identity->name;;
                $saveData['module_name'] = 'Payment Details';

                Yii::$app->Common->ordereditHistory($saveData);

                $data['order_details'] = Order::getorderdetails($order_id);
                $corp_ref_text = ($order->corporate_id == 0) ? "": " ".$order->flight_number;
                    User::sendsms($data['order_details']['order']['c_country_code'].$data['order_details']['order']['customer_mobile'],"Dear Customer, Order #".$data['order_details']['order']['order_number'].$corp_ref_text.", refund of Rs. ".$_POST['amount_paid']." is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account. The receipt of the refund can be found under 'Manage Orders' of your account. ".PHP_EOL."Thanks carterx.in");

                    $refund_content = "Dear Customer, the refund ".$_POST['amount_paid']." for your Order #".$data['order_details']['order']['order_number']." has been successfully refunded back to its source. Review and revert .Thank you for choosing us and we look forward to serving you soon!Thanks carterx.in";
                    $traveller_number = $data['order_details']['order']['traveler_country_code'].$order->travell_passenger_contact;
                    User::sendsms($traveller_number,$refund_content);

            }
            if(isset($_POST['kiosk_update'])){
                return $this->redirect(['employee/update-kiosk', 'id' => $order_id, 'mobile' => Yii::$app->request->post()['mobile']]);
            }else if(isset($_POST['kiosk_order_update'])){
                return $this->redirect(['kiosk-order-update', 'id' => $order_id ]);
            }else if(isset($_POST['thirdpary_update'])){
                return $this->redirect(['thirdparty-update', 'id' => $order_id ]);
            }
            else{
                return $this->redirect(['update', 'id' => $order_id ]); 
            }
        }
        

    }
    public function actionUpdatepassengersignature(){
        $order_id = $_GET['id'];
        $order = Order::find()->where(['id_order'=>$order_id])->one();
        if($order){
            if($_FILES){
                $file_details = json_decode($this->actionFileupload('signature'));
                $file_name = $_FILES['signature']['name'];
            }
            else{
                $file_name= '';
            }
            $order->signature1 = $file_name;
            $order->save(false);
            if($_POST['kiosk_update'] == ''){
                return $this->redirect(['employee/update-kiosk', 'id' => $order_id, 'mobile' => Yii::$app->request->post()['mobile']]);
            }elseif($_POST['kiosk_update'] == 'corporate'){
                return $this->redirect(['employee/update-kiosk-corporate', 'id' => $order_id, 'mobile' => Yii::$app->request->post()['mobile']]);
            }else{
                return $this->redirect(['update', 'id' => $order_id ]); 
            }
        }
        

    }


    public function actionUpdateSecurityQuestionImage(){
        $order_id = $_GET['id'];
        
        $order_images = SecurityQuestionImage::find()->where(['order_id'=>$order_id])->one();
        if($order_images){
            $model = SecurityQuestionImage::find()->where(['order_id'=>$order_id])->one();
        }else{
            $model = new SecurityQuestionImage();
        }
        if($order_id){
            if($_FILES){
                $file_details = json_decode($this->actionFileupload('security_image'));
                $file_name = $file_details->file_name;
            }
            else{
                $file_name= '';
            }
            $model->order_id = $order_id;
            $model->security_image = $file_name;

            $saveData = [];
            $saveData['order_id'] = $_GET['id'];
            $saveData['description'] = 'Order modification on security image';
            $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
            $saveData['employee_name'] = Yii::$app->user->identity->name;;
            $saveData['module_name'] = 'Question Answers';

            Yii::$app->Common->ordereditHistory($saveData);

            if($model->save()){
                $saveData = [];
                $saveData['order_id'] = $_GET['id'];
                $saveData['description'] = 'Order modification on security image';
                $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                $saveData['employee_name'] = Yii::$app->user->identity->name;;
                $saveData['module_name'] = 'Question Answers';

                Yii::$app->Common->ordereditHistory($saveData);

                return $this->redirect(['update', 'id' => $order_id ]);
            }
            return $this->redirect(['update', 'id' => $order_id ]); 
        }
        

    }

    public function actionUpdateAirasiaReceipt($id){
        $order_id = $_GET['id'];
        
       //print_r($id);exit;
       $model = $this->findModel($id);
       //print_r($model);exit;
        if($order_id){
            if($_FILES){
                $file_details = json_decode($this->actionFileupload('airasia_receipt'));
                $file_name = $file_details->file_name;
            }
            else{
                $file_name= '';
            }
            $model->airasia_receipt = $file_name;
            if($model->save(false)){
                return $this->redirect(['update', 'id' => $order_id ]);
            }
            //print_r('expression');exit;
            return $this->redirect(['update', 'id' => $order_id ]); 
        }
        

    }


    public function actionDeletepayrecimage($pr)
    {
        $pci = $_GET['pr'];
        $payrecdet = OrderPaymentDetails::findone($pci);
        if(!empty($payrecdet))
        {
            if(!empty($payrecdet->payment_receipt)){
                $path=Yii::$app->params['document_root'].'basic/web/uploads/order_receipts/'.$payrecdet->payment_receipt;
                unlink($path); 
                $payrecdet->payment_receipt = NULL;
                $payrecdet->save(false);
                return $this->redirect(['update', 'id' => $payrecdet->id_order]);      
            }
            return $this->redirect(['update', 'id' => $payrecdet->id_order]);
        }
    }


    /*function to delete corporate document image*/
    public function actionDeletecorporatedocument($cii, $ci)
    {
        $isdocument = CorporateDocuments::find()->where(['corporate_document_id'=>$cii])->one();
        if($isdocument)
        {
            $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['corporate_document'].$isdocument->document;
            unlink($path); 
            $isdocument->delete();
            return $this->redirect(['corporate-details/update', 'id' => $ci]);
        }else{
            return $this->redirect(['corporate-details/update', 'id' => $ci]);
        }
    }

    /*function to download order*/
    public function actionOrderDownload($id_order)
    {
	    ini_set('max_execution_time', 300);
        ob_start();

        $path=Yii::$app->params['document_root'].Yii::$app->params['order_pdf_path'];
        $order_details = order::getorderdetails($id_order);
              
        $order_details['beforeimages']=array();
        $order_details['afterimages']=array();
        $order_details['damagedimages']=array();
        $order_details['deliveredimages']=array();
        //print_r($order_details['deliveredimages']);exit;
        $i=0;$j=0;$k=0;$m=0;
        if(!empty($order_details['order_images'])){ 
            foreach ($order_details['order_images'] as $key => $value) {
                if($value['before_after_damaged']==0){
                    $order_details['beforeimages'][$i]['id_order_image']=$value['id_order_image'];
                    $order_details['beforeimages'][$i]['image']=$value['image_name'];
                    $i++;
                }else if($value['before_after_damaged']==1){
                    $order_details['afterimages'][$j]['id_order_image']=$value['id_order_image'];
                    $order_details['afterimages'][$j]['image']=$value['image_name'];
                    $j++;
                }else if($value['before_after_damaged']==2){
                    $order_details['damagedimages'][$k]['id_order_image']=$value['id_order_image'];
                    $order_details['damagedimages'][$k]['image']=$value['image_name'];
                    $k++;
                }
                else{
                    $order_details['deliveredimages'][$m]['id_order_image']=$value['id_order_image'];
                    $order_details['deliveredimages'][$m]['image']=$value['image_name'];
                    $m++;
                }
            }        
        }

        $status = Order::getcustomerstatus($order_details['order']['id_order_status'], $order_details['order']['service_type'],$id_order);
               
        if($order_details['order']['reschedule_luggage'] == 1){
            $order_details['order']['customer_id_order_status'] = $status['customer_id_order_status']==6 ? '3': $status['customer_id_order_status'];
            $order_details['order']['customer_order_status_name'] = $status['status_name'] == 'Assigned'? 'Open' : $status['status_name'];              
        }else{
            $order_details['order']['customer_id_order_status'] = $status['customer_id_order_status'];
            $order_details['order']['customer_order_status_name'] = $status['status_name'];
        }
        //print_r($order_details);exit;
        echo $this->renderPartial("//order/order-download",array('order_details' => $order_details));
        //print_r($order_details);exit;
        $data = ob_get_clean();

        try
        {
            $html2pdf = new \HTML2PDF('P','A4', 'en', true, 'UTF-8', array(0, 0, 0, 0));
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->writeHTML($data);
        //    $html2pdf->Output("order_".$order_details['order']['order_number'].".pdf",'1');
            $html2pdf->Output("order_".$order_details['order']['order_number'].".pdf", 'D');
		// exit;
            return $order_pdf;
        }
        catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));       
        }

    }

    /**
     * Function for send sms @Bj 
    */ 
    public function actionSendOrderSms(){
        $post = $_POST;
        if(isset($post)){
            $order_id = $_GET['id'];
            $role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            $customerInfo = Order::getorderdetails($order_id);
            $country_code = (isset($customerInfo['order']['c_country_code'])) ? $customerInfo['order']['c_country_code'] : ((isset($customerInfo['corporate_details']['countrycode']['country_code'])) ? $customerInfo['corporate_details']['countrycode']['country_code'] : '91');
            $smsDetails = new OrderSmsDetails;
            $smsDetails->order_sms_title_id = $post['OrderSmsDetails']['order_sms_title_id'];
            $smsDetails->order_sms_order_id = $order_id;
            $smsDetails->order_sms_customer_id = $customerInfo['order']['id_customer'];
            $smsDetails->order_sms_days = isset($post['OrderSmsDetails']['order_sms_days']) ? $post['OrderSmsDetails']['order_sms_days'] : 0;
            $smsDetails->order_sms_time_start = isset($post['OrderSmsDetails']['order_sms_time_start']) ? date('H:i:s',strtotime($post['OrderSmsDetails']['order_sms_time_start'])) : "00:00:00";
            $smsDetails->order_sms_time_end = isset($post['OrderSmsDetails']['order_sms_time_end']) ? date('H:i:s',strtotime($post['OrderSmsDetails']['order_sms_time_end'])) : "00:00:00";
            $smsDetails->order_sms_text = isset($post['OrderSmsDetails']['order_sms_text']) ? $post['OrderSmsDetails']['order_sms_text'] : "";
            $smsDetails->order_sms_extra_text = isset($post['OrderSmsDetails']['order_sms_extra_text']) ? $post['OrderSmsDetails']['order_sms_extra_text'] : "";
            $smsDetails->order_sms_customer_email = $customerInfo['order']['customer_email'];
            $smsDetails->order_sms_customer_mobile = (isset($customerInfo['order']['customer_mobile'])) ? $country_code.$customerInfo['order']['customer_mobile'] : "";
            $smsDetails->order_sms_created_by = Yii::$app->user->identity->id_employee;
            $smsDetails->order_sms_create_date = date('Y-m-d H:i:s');
            $smsDetails->order_sms_traveller_passenger_mobile = $customerInfo['order']['travell_passenger_contact'] ? ((isset($customerInfo['order']['traveler_country_code']) ? $customerInfo['order']['traveler_country_code'] : $country_code).$customerInfo['order']['travell_passenger_contact']) : "";
            $smsDetails->order_sms_location_contact_mobile = $customerInfo['order']['location_contact_number'] ? ((isset($customerInfo['order']['traveler_country_code']) ? $customerInfo['order']['traveler_country_code'] : $country_code).$customerInfo['order']['location_contact_number']) : "";
            if($smsDetails->save(false)){
                $smsId = $smsDetails->pk_order_sms_id;
                $contactDetails = array(
                    "order_sms_pickup" => !empty($customerInfo['order']['pickupPersonNumber']) ? $country_code.$customerInfo['order']['pickupPersonNumber'] : "",
                    "order_sms_dropup" => !empty($customerInfo['order']['dropPersonNumber']) ? $country_code.$customerInfo['order']['dropPersonNumber'] : ""
                );
                $response = Yii::$app->Common->sendOrderSms($smsDetails,$customerInfo['order']['order_number'],$contactDetails);
                if($response){
                    $smsDetails->order_sms_status = "sent";
                    $smsDetails->save();
                } else {
                    $smsDetails->order_sms_status = "not send";
                    $smsDetails->save();
                }
                $smsText = SmsType::find()->select('sms_title')->where(['pk_sms_id' => $post['OrderSmsDetails']['order_sms_title_id']])->one();
                
                $addHistory = new OrderEditHistory();
                $addHistory->fk_tbl_order_id = $order_id;
                $addHistory->description = $smsText['sms_title'];
                $addHistory->edited_by_employee_id = Yii::$app->user->identity->id_employee;
                $addHistory->module_name = 'Order Sms';
                $addHistory->edited_by_employee_name = Yii::$app->user->identity->name;
                $addHistory->save(false);
            }
            Yii::$app->session->setFlash('success', "Successfully Sent Sms");
            if($role_id == 10){
                $this->redirect(['kiosk-order-update','id'=>$_GET['id']]);
            } else {
                $this->redirect(['update','id'=>$_GET['id']]);
            }
        }
    }

    public function actionGetConvayancePrice(){
        $post = Yii::$app->request->post();
        $headers = Yii::$app->request->getHeaders();
        
        if(!empty($post)){
            $order_type = $post['order_type'];
            $order_transfer = $post['order_transfer'];
            $service_type = $post['service_type'];
            $corporate_id = $post['corporate_id'];
            $access_token = $headers['token'];
            if(($order_type == 2) && ($order_transfer == 1)){
                $to_pincode = $post['to_pincode'];
                $from_pincode = $post['from_pincode'];
                $distance = Yii::$app->Common->getDistance($from_pincode,$to_pincode,'KM');
                $postal = Yii::$app->Common->getPostalInfo($to_pincode);
                $city = $postal['District'] ? $postal['District'] : explode(' ',$postal['Region'])[0];
                $state = $postal['State'];
                $convayance_price_arr = Yii::$app->Common->getConveyanceCharge($access_token,$distance,$state,$city);
                return json_encode($convayance_price_arr);
            } else if(($order_type == 2) && ($order_transfer == 2)) {
                $airport_id = $post['airport_id'];
                $airportInfo = AirportOfOperation::findOne(['airport_name_id'=>$airport_id]);
                $to_pincode = $post['to_pincode'];
                $from_pincode = isset($airportInfo) ? $airportInfo['airport_pincode'] : "";
                $distance = Yii::$app->Common->getDistance($from_pincode,$to_pincode,'KM');
                $postal = Yii::$app->Common->getPostalInfo($to_pincode);
                $city = $postal['District'] ? $postal['District'] : explode(' ',$postal['Region'])[0];
                $state = $postal['State'];
                $convayance_price_arr = Yii::$app->Common->getConveyanceCharge($access_token,$distance,$state,$city);
                return json_encode($convayance_price_arr);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function actionCheckPincode(){
        $post = Yii::$app->request->post();
        $headers = Yii::$app->request->getHeaders();
        $response = array();
        if(isset($post)){
            $pincode = $post['pincode'];
            if((strlen($pincode) > 6) || (strlen($pincode) < 6)){
                return json_encode(['status'=>0, 'message'=>"Please enter correct pincode."]);die;
            }
            // $postalResult = Yii::$app->Common->getPostalData($pincode);
            if(!empty($post['region'])){
                $region_name = Yii::$app->db->createCommand("SELECT region_name FROM `tbl_city_of_operation` WHERE `id`=".$post['region'])->queryColumn();
            } else if(!empty($post['airport'])) {
                $region_name = Yii::$app->db->createCommand("SELECT c.region_name FROM `tbl_city_of_operation` c left join `tbl_airport_of_operation` a ON a.fk_tbl_city_of_operation_region_id = c.id where `airport_name_id`=".$post['airport'])->queryColumn();
            }
            $postalResult = Yii::$app->Common->getPostalData($pincode,$region_name[0]);
            
            if(($post['order_type'] == 1) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'drop') && (!$postalResult) && ($post['service_type'] == 1)) {

                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'drop') && (!$postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Do not enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'pick') && (!$postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'pick') && (!$postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Do not enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'drop') && (!$postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'drop') && (!$postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'pick') && (!$postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 1) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'pick') && (!$postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'drop') && ($postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Do not enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'drop') && (!$postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'pick') && (!$postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 1) && (strtolower($post['status']) == 'pick') && ($postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Do not enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'drop') && (!$postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'drop') && ($postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Do not enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'pick') && ($postalResult) && ($post['service_type'] == 1)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Do not enter pincode of ".$region_name[0]);
            
            } else if(($post['order_type'] == 2) && ($post['order_transfer'] == 2) && (strtolower($post['status']) == 'pick') && ($postalResult) && ($post['service_type'] == 2)) {
            
                $response = array('status'=>0,'line-no'=>__LINE__, 'id'=>$post['id'], 'message'=>"Please enter pincode of ".$region_name[0]);
            
            } else {
                $response = array('status'=>1,'id'=>$post['id'], 'message'=>"Success");
            }

            
        }
        return json_encode($response);die;
    }

    /**
     * Function create for select multiple order and send sms 
     * 
    */
    public function actionSelectMultiOrderSms(){
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
            $order_ids = $post['selected_order_id'];
            $selected_order_id = explode(',',$order_ids);
            foreach($selected_order_id as $order_id){
                // $order_id = $_GET['id'];
                $customerInfo = Order::getorderdetails($order_id);
                $country_code = (isset($customerInfo['order']['c_country_code'])) ? $customerInfo['order']['c_country_code'] : ((isset($customerInfo['corporate_details']['countrycode']['country_code'])) ? $customerInfo['corporate_details']['countrycode']['country_code'] : '91');
                $smsDetails = new OrderSmsDetails;
                $smsDetails->order_sms_title_id = $post['OrderSmsDetails']['order_sms_title_id'];
                $smsDetails->order_sms_order_id = $order_id;
                $smsDetails->order_sms_customer_id = $customerInfo['order']['id_customer'];
                $smsDetails->order_sms_days = isset($post['OrderSmsDetails']['order_sms_days']) ? $post['OrderSmsDetails']['order_sms_days'] : 0;
                $smsDetails->order_sms_time_start = isset($post['OrderSmsDetails']['order_sms_time_start']) ? date('H:i:s',strtotime($post['OrderSmsDetails']['order_sms_time_start'])) : "00:00:00";
                $smsDetails->order_sms_time_end = isset($post['OrderSmsDetails']['order_sms_time_end']) ? date('H:i:s',strtotime($post['OrderSmsDetails']['order_sms_time_end'])) : "00:00:00";
                $smsDetails->order_sms_text = isset($post['OrderSmsDetails']['order_sms_text']) ? $post['OrderSmsDetails']['order_sms_text'] : "";
                $smsDetails->order_sms_extra_text = isset($post['OrderSmsDetails']['order_sms_extra_text']) ? $post['OrderSmsDetails']['order_sms_extra_text'] : "";
                $smsDetails->order_sms_customer_email = $customerInfo['order']['customer_email'];
                $smsDetails->order_sms_customer_mobile = (isset($customerInfo['order']['customer_mobile'])) ? $country_code.$customerInfo['order']['customer_mobile'] : "";
                $smsDetails->order_sms_created_by = Yii::$app->user->identity->id_employee;
                $smsDetails->order_sms_create_date = date('Y-m-d H:i:s');
                $smsDetails->order_sms_traveller_passenger_mobile = $customerInfo['order']['travell_passenger_contact'] ? ((isset($customerInfo['order']['traveler_country_code']) ? $customerInfo['order']['traveler_country_code'] : $country_code).$customerInfo['order']['travell_passenger_contact']) : "";
                $smsDetails->order_sms_location_contact_mobile = $customerInfo['order']['location_contact_number'] ? ((isset($customerInfo['order']['traveler_country_code']) ? $customerInfo['order']['traveler_country_code'] : $country_code).$customerInfo['order']['location_contact_number']) : "";
                if($smsDetails->save(false)){
                    $smsId = $smsDetails->pk_order_sms_id;
                    $contactDetails = array(
                        "order_sms_pickup" => !empty($customerInfo['order']['pickupPersonNumber']) ? $country_code.$customerInfo['order']['pickupPersonNumber'] : "",
                        "order_sms_dropup" => !empty($customerInfo['order']['dropPersonNumber']) ? $country_code.$customerInfo['order']['dropPersonNumber'] : ""
                    );
                    $response = Yii::$app->Common->sendOrderSms($smsDetails,$customerInfo['order']['order_number'],$contactDetails);
                    if($response){
                        $smsDetails->order_sms_status = "sent";
                        $smsDetails->save();
                    } else {
                        $smsDetails->order_sms_status = "not send";
                        $smsDetails->save();
                    }
                    $smsText = SmsType::find()->select('sms_title')->where(['pk_sms_id' => $post['OrderSmsDetails']['order_sms_title_id']])->one();
                    
                    $addHistory = new OrderEditHistory();
                    $addHistory->fk_tbl_order_id = $order_id;
                    $addHistory->description = $smsText['sms_title'];
                    $addHistory->edited_by_employee_id = Yii::$app->user->identity->id_employee;
                    $addHistory->module_name = 'Order Sms';
                    $addHistory->edited_by_employee_name = Yii::$app->user->identity->name;
                    $addHistory->save(false);
                }

            }
            Yii::$app->session->setFlash('success', "Successfully Sent Sms");
            if($role_id == 10){
                return $this->redirect(array('order/kiosk-orders'));
            } else {
                return $this->redirect(array('order/index'));
            }
            
        } else {
            Yii::$app->session->setFlash('error', "Please send required data.");
            return $this->redirect(['order/index']);
        }
    }

    /**
     * Function create for allocate multiple orders to porters
     * 
    */
    public function actionSelectMultiOrderPorterAllocation(){
            $_POST = Yii::$app->request->post();
            if(!empty($_POST)){
                $id_emp = $_POST['LabourVehicleAllocation']['fk_tbl_labour_vehicle_allocation_id_employee'];
                $order_ids = $_POST['selected_order_id'];
                $selected_order_id = explode(',',$order_ids);
                foreach($selected_order_id as $value){

                    $orderDet=Order::find()->where(['id_order'=>$value])
                               ->andwhere(['fk_tbl_order_status_id_order_status'=>[1,3,9, 29, 2]])
                               ->with(['orderSpotDetails','fkTblOrderIdCustomer','fkTblOrderIdSlot'])->one();
           
                    if(!empty($orderDet['orderSpotDetails'])){
                        $address=$orderDet['orderSpotDetails']['area'].','.$orderDet['orderSpotDetails']['landmark'].','.$orderDet['orderSpotDetails']['address_line_1'];
                    }else {
                        $address="";
                    }

                    if($orderDet){
                        if($orderDet->corporate_id != 0)
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
                    }

                    $slot_id=$orderDet['fk_tbl_order_id_slot'];
                    
                    if($slot_id==1 || $slot_id==2 || $slot_id==3 || $slot_id==7 || $slot_id==9) {
                        if($orderDet->fk_tbl_order_status_id_order_status == 29){
                            //for pick up orders with vehicle of forword route
                            $service_type=1;
                            $id_route=1;
                            $status=28;
                            $previous_status="Allocate for Delivery";
                        }else{
                            //for pick up orders with vehicle of forword route
                            $service_type=1;
                            $id_route=1;
                            $status=3;
                            $previous_status="open";
                        }
                    }else{
                        if($orderDet->fk_tbl_order_status_id_order_status == 29){
                            //for pick up orders with vehicle of forword route
                            $service_type=1;
                            $id_route=1;
                            $status=28;
                            $previous_status="Allocate for Delivery";
                        }else{
                            //for drop off orders with vehicle of backword route
                            $service_type=2;
                            $id_route=4;
                            $status=9;
                            $previous_status="Arrival into airport warehouse";
                        }            
                    } 
                    $ispreviousassigned = VehicleSlotAllocation::find()->where(['fk_tbl_vehicle_slot_allocation_id_order'=>$value])->one();
                    if(!empty($ispreviousassigned))
                    {
                        $ispreviousassigned->delete();
                    }
                    
                    $empVeh=LabourVehicleAllocation::find()->where(['fk_tbl_labour_vehicle_allocation_id_employee' => $_POST['LabourVehicleAllocation']['fk_tbl_labour_vehicle_allocation_id_employee']])->one();
                    $vehId=$empVeh['fk_tbl_labour_vehicle_allocation_id_vehicle'];
                    $vehDet=Vehicle::find()->where(['id_vehicle'=>$vehId])->one();            
                    $empDet = Employee:: find()->where(['id_employee'=>$id_emp])->one();

                    $newallocation = new VehicleSlotAllocation();
                    $newallocation->order_date=$orderDet->order_date;
                    $newallocation->fk_tbl_vehicle_slot_allocation_id_slot=$slot_id;
                    $newallocation->fk_tbl_vehicle_slot_allocation_id_vehicle =$vehId;
                    $newallocation->fk_tbl_vehicle_slot_allocation_id_employee =$id_emp;
                    $newallocation->fk_tbl_vehicle_slot_allocation_id_order =$value;
                    $newallocation->fk_tbl_vehicle_slot_allocation_id_route = $vehDet->id_route;
                    $newallocation->status=1;
                    $newallocation->date_created=date("Y-m-d H:i:s");
                    $newallocation->save();
                    
                    if($orderDet->fk_tbl_order_status_id_order_status == 29){
                        Yii::$app->db->createCommand("UPDATE tbl_order set allocation=1, fk_tbl_order_status_id_order_status=28, order_status='Allocate for Delivery' where id_order=".$value)->execute();
                        $new_order_history = [ 'fk_tbl_order_history_id_order'=>$value,
                            'from_tbl_order_status_id_order_status'=>$status,
                            'from_order_status_name'=>$previous_status,
                            'to_tbl_order_status_id_order_status'=>28,
                            'to_order_status_name'=>'Allocate for Delivery',
                            'date_created'=> date('Y-m-d H:i:s')
                        ];
                    }else{
                        Yii::$app->db->createCommand("UPDATE tbl_order set allocation=1, fk_tbl_order_status_id_order_status=6, order_status='Assigned' where id_order=".$value)->execute();
                        $new_order_history = [ 'fk_tbl_order_history_id_order'=>$value,
                            'from_tbl_order_status_id_order_status'=>$status,
                            'from_order_status_name'=>$previous_status,
                            'to_tbl_order_status_id_order_status'=>6,
                            'to_order_status_name'=>'Assigned',
                            'date_created'=> date('Y-m-d H:i:s')
                        ];
                    }

                    $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$new_order_history)->execute();
                    
                    $order_2_pending_amount = $orderDet->modified_amount ;
                    $travell_passenger_contact = $orderDet->travell_passenger_contact;
                    $istravel_person = $orderDet->travel_person;

                    $model1['order_details']=Order::getorderdetails($value);
                    if($model1['order_details']['order']['corporate_type']==1){
                        $customer_name = $model1['order_details']['order']['travell_passenger_name'];
                    } else{
                        $customer_name =  $model1['order_details']['order']['customer_name'];
                    }
                    $customer_number = $model1['order_details']['order']['c_country_code'].$model1['order_details']['order']['customer_mobile'];
                    $traveller_number = $model1['order_details']['order']['traveler_country_code'].$model1['order_details']['order']['travell_passenger_contact'];
                    $location_contact = Yii::$app->params['default_code'].$model1['order_details']['order']['location_contact_number'];
                    $corp_ref_text = ($orderDet->corporate_id == 0) ? "": " ".$orderDet->flight_number;
                    
                    if($orderDet->reschedule_luggage!=1){
                        if($orderDet->order_transfer==1){
                            if($status == 28){ 
                                $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForDelivery', $empDet['name']);
                            }else if(in_array($status, [9,3])){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForPickup', $empDet['name']);
                            } 
                        }
                    }else{
                        if($orderDet->order_transfer==1){
                            if($status == 28){ 
                                $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForDelivery', $empDet['name']);
                            }else if(in_array($status, [9,3])){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($orderDet->id_order, 'OpenForPickup', $empDet['name']);
                            } 
                        }else{
                            $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']);
                        }
                    }

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
                        
                        //$pending_amount = $order_1_pending_amount + $order_2_pending_amount;
                        $pending_amount = $model1['reschedule_order_details']['order']['modified_amount'] + $orderDet->modified_amount;
                        //$text = ($pending_amount == 0) ? '' : 'Amount pending Rs.'.$pending_amount.' due. Kindly pay the same before delivery.';
                        $text = '';
                        if($orderDet->corporate_id == 0){
                            $text = ($pending_amount == 0 ) ? '' : ($pending_amount > 0 ) ? 'Amount pending Rs.'.$pending_amount.' due to Order Modification under Order#'.$model1['reschedule_order_details']['order']['order_number'].' & this current service. Kindly pay the same before delivery' : 'refund of Rs. '.$pending_amount.' is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account.';
                        }

                        $if_previous_order_undelivered = Order::getIsundelivered($prev_order_id);
                        if($if_previous_order_undelivered){ 
                            
                            User::sendsms($customer_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order at'.$address.'.'.$text.PHP_EOL.' Thanks Carterx.in');

                            if($istravel_person == 1)
                            {
                                
                                User::sendsms($traveller_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');
                            }
                            if($orderDet->orderSpotDetails->assigned_person==1){
                            
                                User::sendsms($location_contact,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-schedule delivery by CarterX management due to no response between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks Carterx.in');
                            }

                        }else{

                            User::sendsms($customer_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by '.$customer_name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order at'.$address.'.'.$text.PHP_EOL.' Thanks carterx.in');

                            if($istravel_person == 1){

                                User::sendsms($traveller_number,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by '.$customer_name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');
                            } if($orderDet->orderSpotDetails->assigned_person==1){
                                
                                User::sendsms($location_contact,'Dear Customer, your Order#'.$orderDet->order_number.$corp_ref_text.' is open for re-scheduled delivery by '.$customer_name.' between '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_start_time)).' and '.date('h:i A', strtotime($orderDet->fkTblOrderIdSlot->slot_end_time)).' on '.date("F j, Y", strtotime($orderDet->order_date)).'. Carter  '.$empDet['name'].' - '.$empDet['mobile'].' is allocated to deliver your order. '.$text.PHP_EOL.' Thanks carterx.in');
                            }
                        }
                    }
                    else
                    {  
                        if($orderDet->orderSpotDetails){
                            
                            $amount_pending_msg = '';
                            if($orderDet->corporate_id == 0){
                                $amount_pending_msg = ($order_2_pending_amount == 0 ) ? '' : ($order_2_pending_amount > 0 ) ? 'Amount due for the order '.$order_2_pending_amount.'' : 'refund of Rs. '.$order_2_pending_amount.' is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account.';
                            }
                            if($orderDet->corporate_type == 1){
                                $amount_pending_msg = ($order_2_pending_amount == 0 ) ? '' : ($order_2_pending_amount > 0 ) ? 'Amount due for the order '.$order_2_pending_amount.'' : '';
                            }

                            if($orderDet->order_transfer!=1){
                                if($orderDet->corporate_type != 1){
                                if($status == 9){
                                    $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']); 
                                }else{ 
                                    $sms_content = Yii::$app->Common->getCorporateSms($orderDet->id_order, 'order_open_for_pickup', $empDet['name']); 
                                }
                            }else{                        
                                if($status == 9){ 
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']); 
                                }else{ 
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_pickup', $empDet['name']); 
                                }
                            }
                            }else{
                                if($status == 9){ 
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_deliverey', $empDet['name']); 
                                }else{ 
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($orderDet->id_order, 'order_open_for_pickup', $empDet['name']); 
                                }
                            }
                        }
                    }
                }
                Yii::$app->session->setFlash('success', "Successfully allocated orders to porter");
                return $this->redirect(array('vehicle-slot-allocation/index'));
            } else {
                Yii::$app->session->setFlash('error', "Please send required data.");
                return $this->redirect(['order/pending-order-allocation']);
            }
    }

    /**
     * Function for change order status of multiple orders
     * 
    */
    public function actionChangeOrderStatus() {
        // echo "<pre>";print_r($_POST);die;
        $role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $post = Yii::$app->request->post();
        if(!empty($post)){
            $order_ids = $post['selected_orderIds'];
            $selected_order_id = explode(',',$order_ids);
            if(!empty($post['id_order_status'])){
                foreach($selected_order_id as $id) {
                    if($post['id_order_status'] == 21){
                        $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();
        
                        if(in_array($last_order_status['to_tbl_order_status_id_order_status'], array(18,19,21,28,31,30))){
                            Yii::$app->session->setFlash('error', "Status can't be change, because off old status is '".$last_order_status['to_order_status_name']."'");
                             
                        } else {
                            $this->cancelorder($id);
                            $data['order_details'] = Order::getorderdetails($id);
                            if(!empty($data['order_details']['order']['confirmation_number'])){
                                Yii::$app->Common->setSubscriptionStatus($data['order_details']['order']['confirmation_number']);
                            }
                        }
                    }else if($_POST['id_order_status'] == 30){
                        $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();
        
                        if(in_array($last_order_status['to_tbl_order_status_id_order_status'], array(18,19,21,28,31,30))){
                            Yii::$app->session->setFlash('error', "Status can't be change, because off old status is '".$last_order_status['to_order_status_name']."'");
                             
                        } else {
                            $this->orderCancellationWithRefund($id,$_POST);
                            $data['order_details'] = Order::getorderdetails($id);
                            if(!empty($data['order_details']['order']['confirmation_number'])){
                                Yii::$app->Common->setSubscriptionStatus($data['order_details']['order']['confirmation_number']);
                            }
                            // ------ start subscription setup------
                            $data['order_details']['subscription_details'] = Yii::$app->Common->getSubscriptionDetails($data['order_details']['order']['confirmation_number']);
                            if(!empty($data['order_details']['subscription_details'])){
                                $sms_data = array("confirmation_number" => !empty($data['order_details']['subscription_details']['confirmation_number']) ? strtoupper($data['order_details']['subscription_details']['confirmation_number']) : "",
                                        "subscription_name" => !empty($data['order_details']['subscription_details']['subscriber_name']) ? strtoupper($data['order_details']['subscription_details']['subscriber_name']) : "",
                                        "paid_amount" => !empty($data['order_details']['order']['amount_paid']) ? $data['order_details']['order']['amount_paid'] : 0,
                                        "pay_amount" => !empty($data['order_details']['subscription_details']['paid_amount']) ? $data['order_details']['subscription_details']['paid_amount'] : 0,
                                        "refund_amount"=> !empty($data['order_details']['order']['refund_amount']) ? $data['order_details']['order']['refund_amount'] : 0);

                                $mobile_arr = array_unique(array($data['order_details']['order']['customer_mobile'],$data['order_details']['order']['travell_passenger_contact'],$data['order_details']['subscription_details']['primary_contact'],$data['order_details']['subscription_details']['secondary_contact']));

                                // customer and super subscriber
                                $customer_email = !empty($data['order_details']['order']['travell_passenger_email']) ? $data['order_details']['order']['travell_passenger_email'] : (!empty($data['order_details']['order']['customer_email']) ? $data['order_details']['order']['customer_email'] : "");

                                $emailSubscriberTo = array_unique(array($data['order_details']['subscription_details']['primary_email'],
                                    $data['order_details']['subscription_details']['secondary_email']
                                ));

                                $emailTokenTo = !empty($data['order_details']['corporate_details']['default_email']) ? array($data['order_details']['corporate_details']['default_email']) : "";

                                $emailCustomerCareTo = array(Yii::$app->params['customer_email']);

                            }
                            //------ start subscription setup------
                            // Cancellation with refund email invoice and sms
                            if(($_POST['id_order_status'] == 30) && (!empty($data['order_details']['order']['confirmation_number']))){
                                // cancellation sms with refund
                                Yii::$app->Common->subscriptionSmsSent("cancellations_with_refund",$sms_data,array_filter($mobile_arr));
                                // confirmation update email
                                $file_name = "subscription_cancel_confirmation_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                                $data['order_details']['order']['refund'] = 1;
                                $attachment_cnf =Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data,'subscription_cnf_template',$file_name);
                                User::sendcnfemail($customer_email,"CarterX Cancellation Confirmed Subscription #".strtoupper($data['order_details']['subscription_details']['subscriber_name']),'sub_cnf_email',$data,$attachment_cnf,array_filter(array_unique($emailSubscriberTo)));
                                // Token confirmation email
                                $attachment_det =Yii::$app->Common->genarateOrderConfirmationPdf($data,'token_confirmation_pdf_template');
                                User::sendcnfemail($customer_email,"CarterX Cancellation Subscription Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_det,array_filter(array_unique(array_merge($emailTokenTo,$emailCustomerCareTo))),false);
                                
                                if($data['order_details']['order']['refund_amount'] != 0){
                                    // Cancellation with refund
                                    $file_name_ = "subscription_cancel_refund_".time().'_'.$data['order_details']['order']['order_number'].".pdf";
                                    $attachment_cancel = Yii::$app->Common->genarateSubscriptionInvoicePdfTemp($data, "subscription_cancellation_with_refund_invoice",$file_name_);
                                    User::sendcnfemail($customer_email,"CarterX Cancellation With Refund Invoice - Subscription Order  #".$data['order_details']['order']['order_number']."",'cancellation_email',$data,$attachment_cancel,array_filter(array_unique($emailSubscriberTo)));
                                }
                            }
                            // Cancellation with refund email invoice and sms
                        }
                        if(isset($_POST['kiosk_status'])){
                            return $this->redirect(['kiosk-order-update', 'id' => $id]);
                        }else if(isset($_POST['thirdparty_status'])){
                            return $this->redirect(['thirdparty-update', 'id' => $id]);
                        }else{
                            return $this->redirect(['update', 'id' => $id]);
                        }
                    } else{
                        $this->removecancel($id);
                        $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();

                        $model = new OrderHistory();
                        $model->fk_tbl_order_history_id_order = $id;
                        $model->from_tbl_order_status_id_order_status = $last_order_status['to_tbl_order_status_id_order_status'];
                        $model->from_order_status_name = $last_order_status['to_order_status_name'];
                        $model->to_tbl_order_status_id_order_status = $post['id_order_status'];
                        $to_order_status_name = Yii::$app->db->createCommand("select os.status_name from tbl_order_status os where os.id_order_status='".$model->to_tbl_order_status_id_order_status."' ")->queryOne();
                        $model->to_order_status_name = $to_order_status_name['status_name'];
                        $model->date_created = date('Y-m-d H:i:s');
                        $model->save();

                        $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status='".$post['id_order_status']."',order_status='".$model->to_order_status_name."' where id_order='".$id."'")->execute();  
                    }

                    $data['order_details'] = Order::getorderdetails($id);
                    $data['order_price_break'] = Order::getOrderPrice($id);

                    $travell_passenger_contact = $data['order_details']['order']['travell_passenger_contact'];
                    $istravel_person = $data['order_details']['order']['travel_person'];
                    $corp_ref_text = ($data['order_details']['order']['corporate_id'] == 0) ? "": " ".$data['order_details']['order']['flight_number'];

                    if($data['order_details']['order']['reschedule_luggage']==1) {
                        $prev_order_id = min($data['order_details']['order']['id_order'], $data['order_details']['order']['related_order_id']);
                        $new_order_from_rescheduld = max($data['order_details']['order']['id_order'], $data['order_details']['order']['related_order_id']);
                        $orderDet = Order::getOrderdetails($prev_order_id);
                        $istravel_person = $orderDet['order']['travel_person'];
                        $travell_passenger_contact = $orderDet['order']['travell_passenger_contact'];

                    }

                    $customer_number = $data['order_details']['order']['c_country_code'].$data['order_details']['order']['customer_mobile'];
                    $traveller_number = $data['order_details']['order']['traveler_country_code'].$travell_passenger_contact;
                    $location_contact = Yii::$app->params['default_code'].$data['order_details']['order']['location_contact_number'];
                    
                    /* SMS - Status - start */
                    if($post['id_order_status'] == 8) {
                        if($data['order_details']['order']['order_modified'] == 1) {
                            if($data['order_details']['order']['order_transfer']==1) {
                                    $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_WithModification', '');
                            }
                        } else {
                            if(!($data['order_details']['order']['corporate_type'] == 1)){
                                if($data['order_details']['order']['order_transfer']==1){
                                    $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_NoModification', '');
                                }else{
                                    $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_pickup_no_modification', '');
                                }
                            } else {
                                if($data['order_details']['order']['order_transfer']==1) {
                                    $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_NoModification', '');
                                } else {
                                    $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_pickup_no_modification', '');
                                }
                            }    
                        }
                    }

                    $customers  = Order::getcustomername($data['order_details']['order']['travell_passenger_contact']);
                    $customer_name = ($customers) ? $customers->name : '';

                    if($post['id_order_status'] == 18) {
                        //Status - delivered to and to Airport
                        if(!($data['order_details']['order']['corporate_type'] == 1)){
                            if($data['order_details']['order']['order_transfer']==1){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderDelivered', '');
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_WithModification', '');
                            } else {
                                $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_delivered', '');
                            }
                        } else {
                            if($data['order_details']['order']['order_transfer']==1){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderDelivered', '');
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'PickedUp_WithModification', '');
                            } else {
                                $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_delivered', '');
                            }
                        }

                        if($data['order_details']['order']['round_trip'] != 0 ) {
                            //if related round trip or return has to be completed yet
                            $data['related_order_details'] = Order::getorderdetails($data['order_details']['order']['related_order_id']);   
                            $fetch_orders = Order::find()->where(['id_order'=>[ $data['order_details']['order']['id_order'], $data['order_details']['order']['related_order_id'] ]])
                                            //->andwhere(['>=', 'order_date', date('Y-m-d')])
                                            ->orderBy(['order_date' => SORT_ASC])
                                            ->all();
                            if(!empty($fetch_orders)) {
                                $delivered_order_number = $fetch_orders[0]->order_status == 'Delivered' ? $fetch_orders[0]->order_number : $fetch_orders[1]->order_number;
                                $rest_order_date = $fetch_orders[0]->order_status == 'Delivered' ? $fetch_orders[1]->order_date : $fetch_orders[0]->order_date;
                                User::sendsms($customer_number, $msg1); 
                            }
                        }

                    }

                    if($data['order_details']['order']['corporate_type'] == 1) {
                        //echo "<pre>";print_r($data['order_details']);exit;
                        if($data['order_details']['order']['order_transfer']==1){
                            $customers = Customer::findOne(['mobile' => $data['order_details']['order']['customer_mobile']]);
                        }else{
                            $customers = Customer::findOne(['mobile' => $data['order_details']['order']['travell_passenger_contact']]);
                        }
                        
                        if($post['id_order_status'] == 18) {
                            User::sendemail($customers['email'],"Order check - Order #".$data['order_details']['order']['order_number']."",'delivery_confirmation',$data);
                        }
                        if($post['id_order_status'] == 21) {
                            User::sendemail($customers['email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'cancellation_email',$data);
                        }
                    }else{
                        if($post['id_order_status'] == 18)
                        {
                            //    $pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
                            User::sendemail($data['order_details']['order']['customer_email'],"Order check - Order #".$data['order_details']['order']['order_number']."",'delivery_confirmation',$data);
                        } if($post['id_order_status'] == 21) {
                            /*$pdf_det = $this->genaratepdf($data['order_details']['order']['id_order']);
                            User::sendemailasattachment($data['order_details']['order']['customer_email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']."",'cancellation_email',$data, $pdf_det);*/   
                            User::sendemail($data['order_details']['order']['customer_email'],"Order Cancelled - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'cancellation_email',$data);
                        }
                    }
                    if($post['id_order_status'] == 18) {
                        User::sendemail($data['order_details']['order']['customer_email'],"Order Delivered - Order #".$data['order_details']['order']['order_number']." placed on Caterx",'delivery_confirmation',$data);
                    }
                    $location = $data['order_details']['order']['service_type'] == 1 ? $data['order_details']['order']['location_address_line_1'] : 'Airport';
                    if($post['id_order_status'] == 21) {
                        if(!($data['order_details']['order']['corporate_type']==1)){
                            if($data['order_details']['order']['order_transfer']==1){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderCancelation', '');
                            }else{
                                $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
                            }
                        }else{
                            if($data['order_details']['order']['order_transfer']==1){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'OrderCancelation', '');
                            }else{
                                $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_cancelation', '');
                            }
                        }
                    }

                    if($post['id_order_status'] == 23) {
                        if(!($data['order_details']['order']['corporate_type'] == 1)) {
                            if($data['order_details']['order']['order_transfer']==1) {
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'order_delivered_no_response', '');
                            } else {
                                $sms_content = Yii::$app->Common->getCorporateSms($data['order_details']['order']['id_order'], 'order_delivery_no_response', '');
                            } 
                        } else {
                            $service_type = ($data['order_details']['order']['service_type'] == 1) ? 'airport' : $data['order_details']['order']['location_address_line_1'];
                            $location_passanger_name = ($data['order_details']['order']['service_type'] == 1) ? $data['order_details']['order']['travell_passenger_name'] : $data['order_details']['order']['location_contact_name'];
                            $location_passanger_contact = ($data['order_details']['order']['service_type'] == 1) ? $data['order_details']['order']['travell_passenger_contact'] : $data['order_details']['order']['location_contact_number'];
                            
                            $msg_undelivered = "Hello Customer Order#".$data['order_details']['order']['order_number']. ", Your order status has been pushed to Undelivered as per our our terms & conditions. Please call customer care at ".PHP_EOL." +919110635588"." within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot. ".PHP_EOL."Thank you carterx.in ";

                            if($data['order_details']['order']['order_transfer']==1){
                                $sms_content = Yii::$app->Common->generateCityTransferSms($data['order_details']['order']['id_order'], 'order_delivered_no_response', '');
                            }else{
                                $sms_content = Yii::$app->Common->getGeneralKioskSms($data['order_details']['order']['id_order'], 'order_delivered_no_response', '');
                            } 
                        }
                        if($data['order_details']['order']['corporate_type'] == 3){
                            User::sendemail($data['order_details']['order']['customer_email'],"Undelivered on Order #".$data['order_details']['order']['order_number']." placed on Caterx",'order_delivery_no_response',$data);  
                        }
                    }

                    if($post['id_order_status'] == 25) {
                        $refund = OrderPaymentDetails::find()->where(['id_order'=>$data['order_details']['order']['id_order'], 'payment_status'=>'Refunded'])->one();
                        if(!empty($refund)) {
                            User::sendsms($customer_number,"Dear Customer, Order #".$data['order_details']['order']['order_number'].", refund of Rs. ".$refund->amount_paid." is initiated into your source account of payment. Refunds may take upto 7 workings to reflect in source account. The receipt of the refund can be found under 'Manage Orders' of your account. ".PHP_EOL."Thanks carterx.in");
                        }
                    }

                    $saveData = [];
                    $saveData['order_id'] = $id; 
                    $saveData['description'] = 'Order modification customer order status details';
                    $saveData['employee_id'] = Yii::$app->user->identity->id_employee;;
                    $saveData['employee_name'] = Yii::$app->user->identity->name;;
                    $saveData['module_name'] = 'Customer Order Status';

                    Yii::$app->Common->ordereditHistory($saveData);

                    $orderInfo['order_details'] = Order::getorderdetails($id);
                    if($orderInfo['order_details']['order']['corporate_type'] == 2){
                        if(($orderInfo['order_details']['order']['id_order_status'] == 18) && ($orderInfo['order_details']['order']['order_status'] == 'Delivered')) {
                            User::sendMHLemail($orderInfo['order_details']['corporate_details']['default_email'],"MHL Order Delivered","order_delivery_mhl",$orderInfo,true);
                        } else if(($orderInfo['order_details']['order']['id_order_status'] == 23) && ($orderInfo['order_details']['order']['order_status'] == 'Undelivered')) {
                            User::sendMHLemail($orderInfo['order_details']['corporate_details']['default_email'],"MHL Order Undelivered","order_undelivered_mhl",$orderInfo,true);
                        }
                    }
                }
                Yii::$app->session->setFlash('success', "Successfully change order status");
                if($role == 10) {
                    return $this->redirect(['order/kiosk-orders']);
                } else if($role == 14) {
                    return $this->redirect(['order/corporate-kiosk-orders']);
                } else {
                    return $this->redirect(['order/index']);
                }
            } else {
                Yii::$app->session->setFlash('error', "Please send required data");
                if($role == 10) {
                    return $this->redirect(['order/kiosk-orders']);
                } else if($role == 14) {
                    return $this->redirect(['order/corporate-kiosk-orders']);
                } else {
                    return $this->redirect(['order/index']);
                }
            }
        }
    }

    public function actionGetOrderList(){

        // $result = Yii::$app->db->createCommand("SELECT `t`.* FROM `tbl_order` `t` LEFT JOIN `tbl_order_meta_details` ON `t`.`id_order` = `tbl_order_meta_details`.`orderId` WHERE `t`.`deleted_status`=0")->queryAll();
        // $result = Yii::$app->db->createCommand("SELECT `t`.* FROM `tbl_order` `t` LEFT JOIN `tbl_customer` `c1` ON `t`.`fk_tbl_order_id_customer` = `c1`.`id_customer` LEFT JOIN `tbl_slots` `c2` ON `t`.`fk_tbl_order_id_slot` = `c2`.`id_slots` LEFT JOIN `tbl_vehicle_slot_allocation` ON `t`.`id_order` = `tbl_vehicle_slot_allocation`.`fk_tbl_vehicle_slot_allocation_id_order` LEFT JOIN `tbl_employee` `c3` ON `tbl_vehicle_slot_allocation`.`fk_tbl_vehicle_slot_allocation_id_employee` = `c3`.`id_employee` LEFT JOIN `tbl_porterx_allocations` ON `t`.`id_order` = `tbl_porterx_allocations`.`tbl_porterx_allocations_id_order` LEFT JOIN `tbl_employee` `c4` ON `tbl_porterx_allocations`.`tbl_porterx_allocations_id_employee` = `c4`.`id_employee` LEFT JOIN `tbl_order` `c5` ON `t`.`related_order_id` = `c5`.`id_order` WHERE (t.corporate_id =2) AND (t.deleted_status = 0)")->queryAll();

        // $result = Yii::$app->db->createCommand("select t.id_order,t.corporate_id,t.fk_tbl_order_id_customer,t.order_number,t.fk_tbl_airport_of_operation_airport_name_id,
        // t.payment_mode_excess,t.service_type,t.delivery_type,t.order_transfer,t.related_order_id,t.round_trip,t.reschedule_luggage,
        // t.order_date,t.fk_tbl_order_id_pick_drop_location,t.no_of_units,t.fk_tbl_order_id_slot,t.airline_name,t.flight_number,
        // t.departure_time,t.departure_date,t.arrival_time,t.arrival_date,t.meet_time_gate,t.meet_date,t.delivery_time,t.delivery_date,
        // t.travel_person,t.travell_passenger_name,t.travell_passenger_contact,t.flight_verification,t.fk_tbl_order_status_id_order_status,
        // t.order_status,t.service_tax_amount,t.corporate_price,t.luggage_price,t.insurance_number,t.insurance_price,t.updated_insurance,
        // t.payment_method,t.payment_transaction_id,t.payment_status,t.amount_paid,t.discount_amount,t.outstation_amount_paid,
        // t.express_extra_amount,t.outstation_extra_amount,t.dservice_type,t.status,t.allocation,t.date_created,t.created_by,
        // t.created_by_name,t.deleted_status,t.minutes_to_reach,t.corporate_type,t.delivery_datetime,t.delivery_time_status,
        // c1.name,c1.corporate_detail_id,
        // c2.mobile,c2.email,c2.gender,
        // c3.slot_name,c3.time_description,c3.description,
        // c4.region_name,c4.region_pincode,
        // c5.airport_name,c5.airport_pincode,
        // c6.region_name as city_name,c6.region_pincode as city_pincode
        
        // from tbl_order t 
        // left join tbl_corporate_details c1 On c1.corporate_detail_id = t.corporate_id
        // left join tbl_customer c2 On c2.id_customer = t.fk_tbl_order_id_customer
        // left join tbl_slots c3 On c3.id_slots = t.fk_tbl_order_id_slot
        // left join tbl_city_of_operation c4 On c4.id = t.city_id
        // left join tbl_airport_of_operation c5 On c5.airport_name_id = t.fk_tbl_airport_of_operation_airport_name_id
        // left join tbl_city_of_operation c6 On c6.id = c5.fk_tbl_city_of_operation_region_id
        // where t.deleted_status = 0
        // order by t.id_order desc limit 10")->queryAll();

        $result = Yii::$app->db->createCommand("SELECT `t`.* FROM `tbl_order` `t` LEFT JOIN `tbl_customer` `c1` ON `t`.`fk_tbl_order_id_customer` = `c1`.`id_customer` LEFT JOIN `tbl_slots` `c2` ON `t`.`fk_tbl_order_id_slot` = `c2`.`id_slots` LEFT JOIN `tbl_vehicle_slot_allocation` ON `t`.`id_order` = `tbl_vehicle_slot_allocation`.`fk_tbl_vehicle_slot_allocation_id_order` LEFT JOIN `tbl_employee` `c3` ON `tbl_vehicle_slot_allocation`.`fk_tbl_vehicle_slot_allocation_id_employee` = `c3`.`id_employee` LEFT JOIN `tbl_porterx_allocations` ON `t`.`id_order` = `tbl_porterx_allocations`.`tbl_porterx_allocations_id_order` LEFT JOIN `tbl_employee` `c4` ON `tbl_porterx_allocations`.`tbl_porterx_allocations_id_employee` = `c4`.`id_employee` LEFT JOIN `tbl_order` `c5` ON `t`.`related_order_id` = `c5`.`id_order` WHERE (t.corporate_id =2) AND (t.deleted_status = 0) limit 100")->queryAll();
        // echo "<pre>";print_r($result);die;
        $countRes = Yii::$app->db->createCommand("SELECT count(*) as total_count FROM `tbl_order` `t` WHERE (t.corporate_id =2) AND (t.deleted_status = 0)")->queryAll();
        echo json_encode(array("status"=>2,'msg'=>'success','result'=>$result,'Count'=>$countRes));die;
    }

    public function orderCancellationWithRefund($id,$data){
        $order_details = Order::getorderdetails($id);
        if(!empty($order_details) && (!empty($order_details['order']['confirmation_number']))){
            $confirmation_details = Yii::$app->Common->getSubscriptionDetails($order_details['order']['confirmation_number']);
            if($order_details['order']['terminal_type'] ==1){
                $usages_used = $order_details['order']['usages_used'];
                $remaining_usage = $order_details['order']['remaining_usages'];
                $extra_usage = $order_details['order']['extra_usages'];
                $amt_paid = $order_details['order']['amount_paid'];
                $update_usage = $confirmation_details['remaining_usages'] + $usages_used;
                
                $subscription_amt = $confirmation_details['subscription_cost'];
                $gst_subscription_amt = ($subscription_amt * $confirmation_details['gst_percent']) / 100;
                $total_gst_subscription_amt = $gst_subscription_amt + $subscription_amt;
                $total_usages = $confirmation_details['no_of_usages'];
                $per_usage_cost = round($total_gst_subscription_amt / $total_usages);
                $update_amt = ($extra_usage - $usages_used) * $per_usage_cost;
                // $update_amt = $amt_paid;

                Yii::$app->db->CreateCommand("UPDATE tbl_subscription_transaction_details set remaining_usages = '".$update_usage."' where subscription_transaction_id =".$confirmation_details['subscription_transaction_id'])->execute();
                
            } else if($order_details['order']['terminal_type'] ==2){
                $usages_used = $order_details['order']['usages_used'];
                $remaining_usage = $order_details['order']['remaining_usages'];
                $exhuast_usage = $order_details['order']['extra_usages'];
                $amt_paid = $order_details['order']['amount_paid'];
                $update_usage = $confirmation_details['remaining_usages'] + $usages_used;
                $subscription_amt = $confirmation_details['subscription_cost'];
                $gst_subscription_amt = ($subscription_amt * $confirmation_details['gst_percent']) / 100;
                $total_gst_subscription_amt = $gst_subscription_amt + $subscription_amt;
                $total_usages = $confirmation_details['no_of_usages'];
                $per_usage_cost = round($total_gst_subscription_amt / $total_usages);
                $update_amt = ($exhuast_usage - $usages_used) * $per_usage_cost;

                Yii::$app->db->CreateCommand("UPDATE tbl_subscription_transaction_details set remaining_usages = '".$update_usage."' where subscription_transaction_id =".$confirmation_details['subscription_transaction_id'])->execute();
            }

            $this->removecancel($id);
            $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();

            $model = new OrderHistory();
            $model->fk_tbl_order_history_id_order = $id;
            $model->from_tbl_order_status_id_order_status = $last_order_status['to_tbl_order_status_id_order_status'];
            $model->from_order_status_name = $last_order_status['to_order_status_name'];
            $model->to_tbl_order_status_id_order_status = $data['id_order_status'];
            $to_order_status_name = Yii::$app->db->createCommand("select os.status_name from tbl_order_status os where os.id_order_status='".$model->to_tbl_order_status_id_order_status."' ")->queryOne();
            $model->to_order_status_name = $to_order_status_name['status_name'];
            $model->date_created = date('Y-m-d H:i:s');
            $model->save();

            $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status='".$data['id_order_status']."',order_status='".$model->to_order_status_name."', refund_amount = '".$update_amt."' where id_order='".$id."'")->execute();
        } else {

            $this->removecancel($id);
            $last_order_status = Yii::$app->db->createCommand("select oh.* from tbl_order_history oh where oh.fk_tbl_order_history_id_order='".$id."' ORDER BY oh.id_order_history DESC")->queryOne();

            $model = new OrderHistory();
            $model->fk_tbl_order_history_id_order = $id;
            $model->from_tbl_order_status_id_order_status = $last_order_status['to_tbl_order_status_id_order_status'];
            $model->from_order_status_name = $last_order_status['to_order_status_name'];
            $model->to_tbl_order_status_id_order_status = $data['id_order_status'];
            $to_order_status_name = Yii::$app->db->createCommand("select os.status_name from tbl_order_status os where os.id_order_status='".$model->to_tbl_order_status_id_order_status."' ")->queryOne();
            $model->to_order_status_name = $to_order_status_name['status_name'];
            $model->date_created = date('Y-m-d H:i:s');
            $model->save();

            $result = Yii::$app->db->createCommand("UPDATE tbl_order set fk_tbl_order_status_id_order_status='".$data['id_order_status']."',order_status='".$model->to_order_status_name."' where id_order='".$id."'")->execute();
        }
    }

    public function actionExport(){
        $id_employee = Yii::$app->user->identity->id_employee;
        $searchModel = new TableexportSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams ,$id_employee);

        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        return $this->render('export', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    }

    public function actionMhlOrder()
    {
        $searchModel = new OrderSearch();
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $dataProvider = $searchModel->getmhlorder(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=100;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    }

    public function actionCorporateOrder(){
        $searchModel = new OrderSearch();
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $dataProvider = $searchModel->getmhlcorporateorder(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=100;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);
    }

    public function actionNormalOrder(){
        $searchModel = new OrderSearch();
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $dataProvider = $searchModel->getnormalorder(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=100;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=>$clients,
        ]);

    }

    public function actionKioskMhlorders(){
        $searchModel = new OrderSearch();
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $roleId =  Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $dataProvider = $searchModel->getkioskmhlorder(Yii::$app->request->queryParams , $roleId);
        $dataProvider->pagination->pageSize=100;
        
        return $this->render('kiosk-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=> $clients
        ]);
    }

    public function actionKioskmadecorporateorder(){
        $searchModel = new OrderSearch();
        $id_employee = Yii::$app->user->identity->id_employee;
        $clients = LoginForm::getClients($id_employee);
        $roleId =  Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $dataProvider = $searchModel->getkioskcorporateorder(Yii::$app->request->queryParams,$roleId);
        $dataProvider->pagination->pageSize=100;

        return $this->render('kiosk-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'client'=> $clients
        ]);
    }
}