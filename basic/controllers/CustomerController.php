<?php

namespace app\controllers;

use Yii;
use app\models\Customer;
use app\models\Employee;
use app\models\OrderSpotDetails;
use app\models\Order;
use app\models\Slots;
use app\models\Vehicle;
use app\models\VehicleSlotAllocation;
use app\models\CustomerSearch;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use app\models\LabourVehicleAllocation;
use app\models\User;
use app\models\Airlines;
use app\models\CustomerLoginForm;


/**
 * CustomerController implements the CRUD actions for Customer model.
 */
class CustomerController extends Controller
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
        $dataProvider->pagination->pageSize=100; 
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCoprorateCustomer(){
      $searchModel = new CustomerSearch();
      $dataProvider = $searchModel->corporateSearch(Yii::$app->request->queryparams);
      $dataProvider->pagination->pageSize=100;
      return $this->render('corporate-employee-list', [
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
    public function actionCreate()
    {
        $model = new Customer();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_customer]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'document' =>'',
                'profile' => '',
            ]);
        }
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
        $cust = $this->findModel($id);
        $prev_document=$model['document'];
        $prev_profile=$model['customer_profile_picture'];
        $document = Yii::$app->params['site_url'].'uploads/customer_documents/'.$model['document'];
        $profile = Yii::$app->params['site_url'].'uploads/customer_profile_picture/'.$model['customer_profile_picture'];
        //print_r($model);exit;
        if ($model->load(Yii::$app->request->post())/* && $model->save(false)*/) {
            $model->update_date = date('Y-m-d');
            $model->update_status = 1;
            
            if($_FILES['Customer']['tmp_name']['document']!='')
            {
              $extension = explode(".", $_FILES["Customer"]["name"]["document"]);
              $rename_customer_document = "customer_document_".date('mdYHis').".".$extension[1];
              move_uploaded_file($_FILES['Customer']['tmp_name']['document'], Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_customer_document);
              $model->document = $rename_customer_document;
              $model->id_proof_verification = 1;
            }else{
              $model->document = $prev_document;
            }
            if($_FILES['Customer']['tmp_name']['customer_profile_picture']!='')
            {
              $extension = explode(".", $_FILES["Customer"]["name"]["customer_profile_picture"]);
              $rename_customer_profile_picture = "customer_profile_picture_".date('mdYHis').".".$extension[1];
              move_uploaded_file($_FILES['Customer']['tmp_name']['customer_profile_picture'], Yii::$app->params['document_root'].'basic/web/uploads/customer_profile_picture/'.$rename_customer_profile_picture);
              $model->customer_profile_picture = $rename_customer_profile_picture;
            }else{
              $model->customer_profile_picture = $prev_profile;
            }
              //$model->document = UploadedFile::getInstance($model, 'document');
            $model->building_restriction = !empty($_POST['Customer']['building_restriction']) ? serialize($_POST['Customer']['building_restriction']) : '';

            if($cust->email != $_POST['Customer']['email'] || $cust->mobile != $_POST['Customer']['mobile'])
            {
                $model->update_status = 1;
            }

            if($cust->id_proof_verification != $_POST['Customer']['id_proof_verification'])
            {
                if($_POST['Customer']['id_proof_verification'] == 2){
                  User::sendsms($model->mobile,"Dear Customer, your ID proof uploaded during registration is not of format requested. Kindly upload a clear copy of Aadhaar card/PanCard/Passport to get your account verified .".PHP_EOL."Thanks carterx.in");
                }elseif ($_POST['Customer']['id_proof_verification'] == 1) {
                  User::sendsms($model->mobile,"Dear Customer, Thank you for registering with CarterX Your account has been verified .".PHP_EOL."Thanks carterx.in");

                  /*start of update orders related to customer*/
                  $orders = Order::find()->where(['fk_tbl_order_id_customer'=>$id, 'fk_tbl_order_status_id_order_status'=>1 ])->all();
                  if(!empty($orders))
                  {
                    foreach ($orders as $order) {
                    $id_status = $order['service_type'] == 1 ? 3 : 2;
                    $status = $order['service_type'] == 1 ? 'Open' : 'Confirmed';
                    $bulkInsertArray[]=[
                                'fk_tbl_order_history_id_order'=>$order['id_order'],
                                'from_tbl_order_status_id_order_status'=>$order['fk_tbl_order_status_id_order_status'],
                                'from_order_status_name'=>$order['order_status'],
                                'to_tbl_order_status_id_order_status'=>$id_status,
                                'to_order_status_name'=>$status,
                                'date_created'=> new Expression('NOW()')
                    ];
                    }
                    //print_r($orders);exit;
                    $tableName='tbl_order_history';
                    $columnNameArray=['fk_tbl_order_history_id_order','from_tbl_order_status_id_order_status','from_order_status_name','to_tbl_order_status_id_order_status','to_order_status_name','date_created'];
                    $insertCount = Yii::$app->db->createCommand()
                                            ->batchInsert($tableName, $columnNameArray, $bulkInsertArray)
                                            ->execute();
                    $userIds = ArrayHelper::getColumn($orders, 'id_order');
                    Order::updateAll(['fk_tbl_order_status_id_order_status' => 2,'order_status'=>'Confirmed'], ['id_order' => $userIds,'service_type'=>2]);

                    Order::updateAll(['fk_tbl_order_status_id_order_status' => 3,'order_status'=>'Open'], ['id_order' => $userIds,'service_type'=>1]);                         
                  }
                  /*end of update orders related to customer*/



                }
            }
            if($model->save(false)){

              return $this->redirect(['view', 'id' => $model->id_customer]);
            }           
            
        } else {
          //print_r($model->errors);exit;
            return $this->render('update', [
                'model' => $model,
                'document' =>$document,
                'profile' => $profile,
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
      $custInfo = Customer::find()->where(['id_customer' => $id])->one();
      if(!empty($custInfo) && !empty($custInfo['fk_id_employee'])){
        Yii::$app->db->createCommand("delete from tbl_employee where id_employee = '".$custInfo['fk_id_employee']."'")->execute();
        Yii::$app->db->createCommand("delete from tbl_customer where id_customer = '".$id."'")->execute();
        Yii::$app->db->createCommand("delete from oauth_clients where user_id = '".$id."' or employee_id = '".$custInfo['fk_id_employee']."'")->execute();
      } else {
        Yii::$app->db->createCommand("delete from tbl_customer where id_customer = '".$id."'")->execute();
        Yii::$app->db->createCommand("delete from oauth_clients where user_id = '".$id."'")->execute();
      }
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

    public function actionTestemail()
    {
        $model = Customer::findOne(1);
      User::sendemail('shiny.d@pacewisdom.com',"Verify Email Address ",'verify_email_link',$model);
      //User::sendsms('9900469512',"Thank you for registering with Porter");
      print_r('successful');exit;
    }
    
    public function actionVerifyemail($id)
    {
      $model = Customer::findOne($id);
      $model->email_verification = 1;
      $model->acc_verification = 1;
      $model->save(false);
      return $this->renderPartial('emailverification');
    }  


    public function actionAllocate($slot_id)
    {
       $model = Order::find()->all();
       if($slot_id==1 || $slot_id==2 || $slot_id==3)
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

        if($slot_id==4){
          $order_date=date('Y-m-d', strtotime('+1 day', time()));
        }else{
          $order_date=date('Y-m-d');
        }
        $orders = Order::find()
                ->where("fk_tbl_order_id_slot = ".$slot_id." and DATE(`order_date`) = '".$order_date."' and fk_tbl_order_status_id_order_status = ".$status." and service_type=".$service_type." and allocation=0")
                ->orderBy(['no_of_units' => SORT_DESC])
                ->all();         

        $vehicles1 = Vehicle::find()
            ->where("id_route = ".$id_route)
            ->orderBy(['remaining_units' => SORT_DESC])
            //->asArray()
            ->all(); 

        $vehicles=Yii::$app->db->createCommand("SELECT v.*, vla.*, e.* FROM tbl_vehicle v inner join tbl_labour_vehicle_allocation vla on v.id_vehicle=vla.fk_tbl_labour_vehicle_allocation_id_vehicle inner join tbl_employee e on e.id_employee=vla.fk_tbl_labour_vehicle_allocation_id_employee where e.available=1 and v.id_route=".$id_route)->queryAll();
        
   
        $selected_slot = Slots::findOne($slot_id);
        $slot_order_limit=$selected_slot->order_limit;
        $slot_item_limit=$selected_slot->item_limit;
        $allocated_slot_items=0;
        $allocated_slot_orders=0;
        $employeeDet=[];
       
            foreach ($vehicles as $vehicle) {
                //$vehicle['no_of_units_remaining']=$vehicle['no_of_units_filled'];
                /*$employee = LabourVehicleAllocation::find()->where(['fk_tbl_labour_vehicle_allocation_id_vehicle'=>$vehicle['id_vehicle']])->One();
                
                if($employee){
                  $employeeDet=Employee::findOne($employee['fk_tbl_labour_vehicle_allocation_id_employee']);
                }*/
                foreach ($orders as $order) { 
                    $orderSpotDet=OrderSpotDetails::find()->where(['fk_tbl_order_spot_details_id_order'=>$order['id_order']])->one();
                    $custDet=Customer::findOne($order->fk_tbl_order_id_customer);
                    if($order['allocation']==0 && $order['no_of_units']<=$vehicle['remaining_units'] && $allocated_slot_orders <= $slot_order_limit && $allocated_slot_items < $slot_item_limit)
                    {                
                        $vehicle_slot_allocation= new VehicleSlotAllocation;
                        $vehicle_slot_allocation->order_date=$order->order_date;
                        $vehicle_slot_allocation->fk_tbl_vehicle_slot_allocation_id_slot=$selected_slot->id_slots;
                        $vehicle_slot_allocation->fk_tbl_vehicle_slot_allocation_id_vehicle =$vehicle['id_vehicle'];
                        $vehicle_slot_allocation->fk_tbl_vehicle_slot_allocation_id_employee =$vehicle['fk_tbl_labour_vehicle_allocation_id_employee'];
                        $vehicle_slot_allocation->fk_tbl_vehicle_slot_allocation_id_order =$order->id_order;
                        $vehicle_slot_allocation->fk_tbl_vehicle_slot_allocation_id_route = $id_route;
                        $vehicle_slot_allocation->status=1;
                        $vehicle_slot_allocation->date_created=date("Y-m-d H:i:s");
                        $vehicle_slot_allocation->save();
                        $allocated_slot_orders++;
                        $allocated_slot_items=$allocated_slot_items+$order['no_of_units'];
                        $vehicle['remaining_units']=$vehicle['remaining_units']-$order['no_of_units'];
                        Yii::$app->db->createCommand("UPDATE tbl_order set allocation=1, fk_tbl_order_status_id_order_status=6, order_status='Assigned' where id_order=".$order->id_order)->execute();
                        $new_order_history = [ 'fk_tbl_order_history_id_order'=>$order->id_order,
                            'from_tbl_order_status_id_order_status'=>$status,
                            'from_order_status_name'=>$previous_status,
                            'to_tbl_order_status_id_order_status'=>6,
                            'to_order_status_name'=>'Assigned',
                            'date_created'=> new Expression('NOW()')
                           ];

                        $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$new_order_history)->execute();
                        Yii::$app->db->createCommand("UPDATE tbl_vehicle set remaining_units=".$vehicle['remaining_units'].", in_use=1 where id_vehicle=".$vehicle['id_vehicle'])->execute();
                        Yii::$app->db->createCommand("UPDATE tbl_employee set available=0 where id_employee=".$vehicle['fk_tbl_labour_vehicle_allocation_id_employee'])->execute();
                        $order['allocation']=1;
                        // if($order->service_type == 1){
                        //     $msg_booking_pickup = "Dear Customer, your Order #".$order->order_number." is open.  ".$custDet->name."  is allocated to pick the order. -CarterX";

                        //     $location_contact = "Hello,  Order #".$order->order_number." placed by ".$custDet->name." is open.  ".$vehicle['name']." is allocated to pick the order. -CarterX";

                        //     $travel_passenger = "Dear Custlomer, your  Order #".$order->order_number." placed by ".$custDet->name." is open.  ".$vehicle['name']." is allocated to pick the order. -CarterX";
                        // }else{
                        //     $msg_booking_pickup = "Dear Customer, your Order #".$order->order_number." is open.  ".$vehicle['name']."  is allocated to pick-up your order at arrivals at the Airport. -CarterX";

                        //     $location_contact = "Dear Customer, your  Order #".$order->order_number." placed by ".$custDet->name." is open.  ".$vehicle['name']." is allocated to pick the order at arrivals at the Airport. -CarterX";

                        //     $travel_passenger = "Dear Customer, your  Order #".$order->order_number." placed by ".$custDet->name." is open.  ".$vehicle['name']." is allocated to pick the order at arrivals at the Airport. -CarterX";
                        // }
                        /*start of SMS to conserned person*/
                        $sms_content = Yii::$app->Common->getGeneralKioskSms($order->id_order, 'order_open_for_pickup', $vehicle['name']);

                        // if($orderSpotDet){
                        //     if($orderSpotDet['assigned_person']==1){
                        //        User::sendsms($orderSpotDet->person_mobile_number,$location_contact);
                        //     }
                        //     else{
                        //         User::sendsms($custDet->mobile,$msg_booking_pickup);
                        //     }
                        // }
                        /*end of SMS to concerned person*/
                    }
                }
            }

    }   

    public function actionCorporateEmployee() {
        $searchModel = new CustomerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=100;
        return $this->render('corporate-employee-list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreateCorporateEmployee() {
      $model = new Customer();
      if ($model->load(Yii::$app->request->post())) {
        $model->fk_role_id = '19';
        $model->gender = 0;
        $model->building_restriction = 'a:1:{i:0;s:1:"5";}';
        $model->mobile_number_verification = 1;
        $model->email_verification = 1;
        $model->id_proof_verification = 1;
        $model->fk_tbl_customer_id_country_code = 95;
        $model->date_created = date('Y-m-d');
        if($model->save(false)){
          $post = Yii::$app->request->post();

          $airlineName = Yii::$app->db->CreateCommand("SELECT UPPER(airline_name) as airline_name FROM tbl_airlines WHERE airline_id = '".$post['Airlines']['airline_id']."'")->queryOne()['airline_name'];
          $airlineName = str_replace(' ','',$airlineName).$model->id_customer;

          Yii::$app->db->CreateCommand("INSERT INTO tbl_corporate_employee_airline_mapping (fk_corporate_employee_id,fk_airline_id,customerId) values('".$model->id_customer."','".$post['Airlines']['airline_id']."','".$airlineName."')")->execute();

          $client['client_id']=base64_encode(Yii::$app->request->post('Customer')['email'].mt_rand(100000, 999999));
          $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash(Yii::$app->request->post('Customer')['email'].mt_rand(100000, 999999)); 
          $client['user_id']=$model->id_customer;
          User::addClient($client);
        }
          return $this->redirect(['view-corporate-employee', 'id' => $model->id_customer]);
      } else {
          return $this->render('create_employee', [
              'model' => $model,
              'document' =>'',
              'profile' => '',
          ]);
      }
    }

    public function actionUpdateCorporateEmployee($id) {
        $model = $this->findModel($id);
        $cust = $this->findModel($id);
        $prev_document=$model['document'];
        $prev_profile=$model['customer_profile_picture'];
        $document = Yii::$app->params['site_url'].'uploads/customer_documents/'.$model['document'];
        $profile = Yii::$app->params['site_url'].'uploads/customer_profile_picture/'.$model['customer_profile_picture'];
        $oldData = $cust;
        if ($model->load(Yii::$app->request->post())) {
            $model->update_date = date('Y-m-d');
            $model->update_status = 1;
            if(isset($_FILES['Customer']['tmp_name']['document'])&&($_FILES['Customer']['tmp_name']['document']!=''))
            {
              $extension = explode(".", $_FILES["Customer"]["name"]["document"]);
              $rename_customer_document = "customer_document_".date('mdYHis').".".$extension[1];
              move_uploaded_file($_FILES['Customer']['tmp_name']['document'], Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_customer_document);
              $model->document = $rename_customer_document;
              $model->id_proof_verification = 1;
            }else{
              $model->document = $prev_document;
            }
            if(isset($_FILES['Customer']['tmp_name']['customer_profile_picture'])&&($_FILES['Customer']['tmp_name']['customer_profile_picture']!=''))
            {
              $extension = explode(".", $_FILES["Customer"]["name"]["customer_profile_picture"]);
              $rename_customer_profile_picture = "customer_profile_picture_".date('mdYHis').".".$extension[1];
              move_uploaded_file($_FILES['Customer']['tmp_name']['customer_profile_picture'], Yii::$app->params['document_root'].'basic/web/uploads/customer_profile_picture/'.$rename_customer_profile_picture);
              $model->customer_profile_picture = $rename_customer_profile_picture;
            }else{
              $model->customer_profile_picture = $prev_profile;
            }
              //$model->document = UploadedFile::getInstance($model, 'document');
            $model->building_restriction = !empty($_POST['Customer']['building_restriction']) ? serialize($_POST['Customer']['building_restriction']) : '';

            if($cust->email != $_POST['Customer']['email'] || $cust->mobile != $_POST['Customer']['mobile'])
            {
                $model->update_status = 1;
            }

            // if($cust->id_proof_verification != $_POST['Customer']['id_proof_verification'])
            // {
                // if($_POST['Customer']['id_proof_verification'] == 2){
                //   User::sendsms($model->mobile,"Dear Customer, your ID proof uploaded during registration is not of format requested. Kindly upload a clear copy of Aadhaar card/PanCard/Passport to get your account verified .".PHP_EOL."Thanks carterx.in");
                // }elseif ($_POST['Customer']['id_proof_verification'] == 1) {
                //   User::sendsms($model->mobile,"Dear Customer, Thank you for registering with CarterX Your account has been verified .".PHP_EOL."Thanks carterx.in");

                  /*start of update orders related to customer*/
                    // $orders = Order::find()->where(['fk_tbl_order_id_customer'=>$id, 'fk_tbl_order_status_id_order_status'=>1 ])->all();
                    // if(!empty($orders))
                    // {
                    //   foreach ($orders as $order) {
                    //   $id_status = $order['service_type'] == 1 ? 3 : 2;
                    //   $status = $order['service_type'] == 1 ? 'Open' : 'Confirmed';
                    //   $bulkInsertArray[]=[
                    //               'fk_tbl_order_history_id_order'=>$order['id_order'],
                    //               'from_tbl_order_status_id_order_status'=>$order['fk_tbl_order_status_id_order_status'],
                    //               'from_order_status_name'=>$order['order_status'],
                    //               'to_tbl_order_status_id_order_status'=>$id_status,
                    //               'to_order_status_name'=>$status,
                    //               'date_created'=> new Expression('NOW()')
                    //   ];
                    //   }
                    //   //print_r($orders);exit;
                    //   $tableName='tbl_order_history';
                    //   $columnNameArray=['fk_tbl_order_history_id_order','from_tbl_order_status_id_order_status','from_order_status_name','to_tbl_order_status_id_order_status','to_order_status_name','date_created'];
                    //   $insertCount = Yii::$app->db->createCommand()
                    //                           ->batchInsert($tableName, $columnNameArray, $bulkInsertArray)
                    //                           ->execute();
                    //   $userIds = ArrayHelper::getColumn($orders, 'id_order');
                    //   Order::updateAll(['fk_tbl_order_status_id_order_status' => 2,'order_status'=>'Confirmed'], ['id_order' => $userIds,'service_type'=>2]);

                    //   Order::updateAll(['fk_tbl_order_status_id_order_status' => 3,'order_status'=>'Open'], ['id_order' => $userIds,'service_type'=>1]);                         
                    // }
                  /*end of update orders related to customer*/
                // }
            // }
            if($oldData['status'] != $model->status){
              $data = array(
                "customer_id" => $model->id_customer,
                "description" => ($model->status == 1) ? "Corporate Customer Status : Enable" : "Corporate Customer Status : Disable",
                "module_name" => "Corporate Customer",
                "edit_by" => Yii::$app->user->identity->id_employee,
                "edit_by_name" => Yii::$app->user->identity->name,
              );

              Yii::$app->Common->updateEditCustHistory($data);
            }
            if($model->save(false)){

              return $this->redirect(['view-corporate-employee', 'id' => $model->id_customer]);
            }           
            
        } else {
            return $this->render('update-corporate-employee', [
                'model' => $model,
                'document' =>$document,
                'profile' => $profile
            ]);
        }
    }

    public function actionViewCorporateEmployee($id) {
        return $this->render('view-corporate-employee', [
            'model' => $this->findModel($id),
        ]);
    }

    // public function actionDemo(){
    //   $res = Yii::$app->Common->setOrderStatus(31926);
    //   // corporate_id,corporate_type,confirmation_number,corporate_customer_id
    // }
}
