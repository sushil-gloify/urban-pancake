<?php

namespace app\models;

use Yii;
use app\models\PorterxAllocations;
use app\models\Customer;
use app\models\CorporateDetails;
use app\api_v3\v3\models\CorporateRoles;
use app\api_v3\v3\models\CorporateUser;
use yii\helpers\Json;
use app\models\EmployeeAllocation;
/**
 * This is the model class for table "tbl_employee".
 *
 * @property integer $id_employee
 * @property integer $fk_tbl_employee_id_employee_role
 * @property string $name
 * @property string $employee_profile_picture
 * @property string $mobile
 * @property string $email
 * @property string $password
 * @property string $adhar_card_number
 * @property string $document_id_proof
 * @property integer $mobile_number_verification
 * @property integer $status
 * @property string $date_created
 * @property string $date_modified
 *
 * @property EmployeeRole $fkTblEmployeeIdEmployeeRole
 * @property EmployeeOtp[] $employeeOtps
 * @property LabourVehicleAllocation[] $labourVehicleAllocations
 * @property OrderWaitingCharge[] $orderWaitingCharges
 */
class Employee extends \yii\db\ActiveRecord
{
    public $profile_picture;
    public $document_proof;
    public $region;
    public $airport;
    public $corporate_id;
    public $new_password;
    public $confirm_password;
 
    public $fk_tbl_airport_of_operation_airport_name_id;
    public $fk_tbl_city_of_operation_region_name_id;
    public $tman_country;
    public $airline;
    public $station;
    
    /** 
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_employee';
    }

    /*public function behaviors(){
            return [
                \nhkey\arh\ActiveRecordHistoryBehavior::className(),
            ];
        }*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $child = function($model) { return $model->fk_tbl_employee_id_employee_role != 5 && $model->fk_tbl_employee_id_employee_role != 6; };
        $corporate = function($model) { return $model->fk_tbl_employee_id_employee_role != 8; };
        $tman = function($model) { return $model->fk_tbl_employee_id_employee_role == 16; };
        return [ 

            [['name','mobile','adhar_card_number'],'required', 'when' => $corporate],
            //[['tman_country','airline','station'],'required','when'=>$tman],
            [['password'],'required', 'on' => 'corporate'],
            [['airport'],'required', 'on' => 'corporate_general'],  
            //[['password'], 'required', 'when' => $child],
            [['email', 'fk_tbl_employee_id_employee_role' , 'status'], 'required', 'on'=>'insert'],
            [['email', 'fk_tbl_employee_id_employee_role' , 'status'],'required', 'on'=>'corporate'],
            [['email', 'fk_tbl_employee_id_employee_role' , 'corporate_id',  'status'],'required', 'on'=>'third_corporate'],
            [['fk_tbl_employee_id_employee_role', 'mobile_number_verification', 'status'], 'integer'],
            [['date_created', 'date_modified', 'profile_picture', 'document_proof','is_tman_mapped'], 'safe'],
            //[['date_created', 'date_modified'], 'datetime'],
            ['email', 'required'],
            [['date_created'], 'default', 'value'=>date('Y-m-d H:i:s'),'on'=>'insert'],
            [['date_created'], 'default', 'value'=>date('Y-m-d H:i:s'),'on'=>'corporate'],
            [['date_modified'], 'default', 'value'=>date('Y-m-d H:i:s' ),'on'=>'update'],
            [['name', 'employee_profile_picture', 'email', 'adhar_card_number', 'document_id_proof'], 'string', 'max' => 255],
            [['profile_picture'],'file'],
            [['document_proof'],'file'],
            [['mobile'], 'string', 'max' => 10],
            [['email'],'email'],
            //[['email'],'unique'],
            [['email'], 'unique', 'on'=>'insert'],
            [['email'], 'unique', 'on'=>'corporate'],
            [['mobile'],'validateMobile'],
            [['mobile'], 'validateuserasadminmobile'],
            [['fk_tbl_employee_id_employee_role'], 'exist', 'skipOnError' => true, 'targetClass' => EmployeeRole::className(), 'targetAttribute' => ['fk_tbl_employee_id_employee_role' => 'id_employee_role']],
            [['fk_tbl_airport_of_operation_airport_name_id'], 'exist', 'skipOnError' => true, 'targetClass' => EmployeeRole::className(), 'targetAttribute' => ['fk_tbl_airport_of_operation_airport_name_id' => 'airport_name_id']],
            [['name'],'match','pattern'=>'/^[A-Za-z ]+$/u'],
        ];

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_employee' => 'Id Employee',
            'fk_tbl_employee_id_employee_role' => 'Employee Role',
            'fk_tbl_corporate_role' => 'Corporate Role',
            'name' => 'Name',
            'employee_profile_picture' => 'Profile Picture',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'corporate_id' => 'Corporate',
            'password' => 'Password',
            'adhar_card_number' => 'Adhar Card Number',
            'document_id_proof' => 'Document Id Proof',
            'mobile_number_verification' => 'Mobile Number Verification',
            'status' => 'Status',
            'fk_tbl_airport_of_operation_airport_name_id' =>'Region',
            'date_created' => 'Date Created',
            'date_modified' => 'Date Modified',
            'tman_country'=>'Country',
            'Airline'=>'airline',
        ];
    }

    public function validateMobile($attribute, $params)
    {
        $employee_id = isset($this->id_employee) ? $this->id_employee : 0;
        $customer_mobile = Employee::find()->where(['mobile'=>$this->mobile, 'status'=>1])
                                           ->andWhere(['!=', 'id_employee', $employee_id])
                                           ->all();
        //print_r($customer_mobile);exit;
        if(!empty($customer_mobile))
        {
            if($customer_mobile[0]['mobile'] != $this->mobile){
                return true;
            }else{
                    if(count($customer_mobile) == 2 && $this->id_employee != $customer_mobile[0]['id_employee'])
                    {
                        //print_r("isd");exit;
                        if($customer_mobile[0]['fk_tbl_employee_id_employee_role'] != $this->fk_tbl_employee_id_employee_role){
                            return true;
                        }else{
                            $this->addError($attribute, 'Mobile already exists for both porter and PorterX');
                        }
                        
                    }
                    else
                    {
                        //print_r("isd");exit;
                        foreach ($customer_mobile as $mobile) {
                            if($this->mobile == $mobile['mobile'] && $mobile['fk_tbl_employee_id_employee_role'] == $this->fk_tbl_employee_id_employee_role)
                            {
                                $this->addError($attribute, 'Mobile already exists for selected role!');
                            }/*
                            else if($this->mobile == $mobile['mobile'] && $mobile['fk_tbl_employee_id_employee_role'] == 5){
                                $this->addError($attribute, 'Mobile already exists for porterX!');
                            }*/
                        }
                    }
            }
            
        }/*
        else
        {
            print_r('else expression');exit;
        }*/
        //if()
         // $this->addError($attribute, 'Mobile already exists!');
    }


    /*check mobile if registered customer to create */
    public function validateuserasadminmobile($attribute, $params)
    {
        if($this->fk_tbl_employee_id_employee_role == 7 ){
            $iscustomer = Customer::find()->where(['mobile'=>$this->mobile])->all();
            if(empty($iscustomer))  
            {
                $this->addError($attribute, 'Not a registered customer');
            }
            
        }
        
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblEmployeeIdEmployeeRole()
    {
        return $this->hasOne(EmployeeRole::className(), ['id_employee_role' => 'fk_tbl_employee_id_employee_role']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblEmployeeIdCorporateEmployeeRole()
    {
        return $this->hasOne(CorporateRoles::className(), ['id_corporate_roles' => 'fk_tbl_corporate_role']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblCorporate()
    {
        return $this->hasOne(CorporateDetails::className(), ['employee_id' => 'id_employee']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployeeOtps()
    {
        return $this->hasMany(EmployeeOtp::className(), ['fk_tbl_employee_otp_id_employee' => 'id_employee']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLabourVehicleAllocations()
    {
        return $this->hasMany(LabourVehicleAllocation::className(), ['fk_tbl_labour_vehicle_allocation_id_employee' => 'id_employee']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderWaitingCharges()
    {
        return $this->hasMany(OrderWaitingCharge::className(), ['fk_tbl_order_waiting_charge_id_employee' => 'id_employee']);
    }
    /*
    * Function to get image url
    * $type - 1 for profile, 2 for document
    */
    public function getImageurl($type)
    {
        if($type==1){
            if(isset($this->employee_profile_picture) && !empty($this->employee_profile_picture)) { 
                 
                 return Yii::$app->params['site_url'].Yii::$app->params['employee_profile'].$this->employee_profile_picture;
            }
        }else if($type==2){
            if(isset($this->document_id_proof) && !empty($this->document_id_proof)) { 
                 
                 return Yii::$app->params['site_url'].Yii::$app->params['employee_document'].$this->document_id_proof;
            }
        }else{
            return '';
        }
    }

    public function getPorterDriver()
    {
        $employees = Employee::find()->where(['fk_tbl_employee_id_employee_role'=>6,])->all();
        return $employees;
    }


    public function isCorporateDetailExist()
    {
       $isexist = CorporateDetails::find()->where(['employee_id'=>$this->id_employee])->one();
       if($isexist)
       {
           return $isexist->corporate_detail_id;
       }else{
           return 0;

       }
    }


    public function getPorterx($id_order=NULL)
    {

        //$id_employees= PorterxAllocations::find()->select('tbl_porterx_allocations_id_employee')->where('tbl_porterx_allocations_id_order != '.$id_order)->asArray()->all();
        if(!empty($id_order)){
            $id_employees=Yii::$app->db->createCommand("SELECT pa.* , o.id_order FROM tbl_porterx_allocations pa JOIN tbl_order o ON pa.tbl_porterx_allocations_id_order=o.id_order where (o.fk_tbl_order_status_id_order_status=7 or o.fk_tbl_order_status_id_order_status=8 or o.fk_tbl_order_status_id_order_status=19) and pa.tbl_porterx_allocations_id_order!=".$id_order)->queryall();
            //print_r($id_employees);exit;
            $id = [];
            if(!empty($id_employees)){
                foreach ($id_employees as $id_employee) {
                    $id[]=$id_employee['tbl_porterx_allocations_id_employee'];
                }
            }
        }
       $employees = Employee::find()->where(['fk_tbl_employee_id_employee_role'=>5])
                                    ->andWhere(['status'=>1])
                                   //->andWhere(['not in','id_employee',$id])
                                   ->all();
       //print_r($employees);exit;
       return $employees;
    }

    public function getPorterxGroup()
    {
       $employees = Employee::find()->where(['fk_tbl_employee_id_employee_role'=>5])
                                    ->andWhere(['status'=>1])
                                   //->andWhere(['not in','id_employee',$id])
                                   ->all();
       //print_r($employees);exit;
       return $employees;
    }

    public function actionFileupload($option,$orderId)
    {
        //print_r($_FILES);exit;
        //$option = key($_FILES);

        switch ($option) {
            case "customer_profile_picture":
                            $extension = explode(".", $_FILES["customer_profile_picture"]["name"]);
                            $rename_customer_profile_picture = "customer_profile_picture_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_profile_picture/'.$rename_customer_profile_picture;
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
                            //print_r($path);exit;
                            move_uploaded_file($_FILES['customer_document']['tmp_name'],$path);
                            if(isset($_POST['id_customer']))
                            {
                                $result = Yii::$app->db->createCommand("UPDATE tbl_customer set document='".$rename_customer_document."' where id_customer='".$_POST['id_customer']."'")->execute();
                            }
                            echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_customer_document]);
                            break;


            case "someone_else_document":
                            
                            $extension = explode(".", $_FILES['Order']['name']['someone_else_document']);
                            $rename_someoneelse_docname = "someone_else_document_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_documents/'.$rename_someoneelse_docname;
                            move_uploaded_file($_FILES['Order']['tmp_name']['someone_else_document'],$path);
                            if(isset($orderId))
                            {
                                $result = Yii::$app->db->createCommand("UPDATE tbl_order set someone_else_document_verification = 1, someone_else_document='".$rename_someoneelse_docname."' where id_order='".$orderId."'")->execute();
                            }
                            
                            echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_someoneelse_docname]);
                            break;
            case "invoice":
                            $extension = explode(".", $_FILES['MallInvoices']['name']['invoice']);
                            $rename_invoice_docname = "invoice_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_invoice_docname;
                            move_uploaded_file($_FILES['MallInvoices']['tmp_name']['invoice'],$path);
                            if(isset($orderId))
                            {
                                $invoice = ['invoice'=>$rename_invoice_docname,'fk_tbl_mall_invoices_id_order'=>$orderId];
                                Yii::$app->db->createCommand()->insert('tbl_mall_invoices',$invoice)->execute();

                                Yii::$app->db->createCommand('UPDATE tbl_order_spot_details set invoice_verification = 1 where fk_tbl_order_spot_details_id_order='.$orderId)->execute();
                            }
                           // echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_invoice_docname]);
                            break;
            case "pickupInvoice":
                            $extension = explode(".", $_FILES['OrderMetaDetails']['name']['pickupInvoice']);
                            $rename_pickup_invoice_docname = "pickupInvoice_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_pickup_invoice_docname;
                            move_uploaded_file($_FILES['OrderMetaDetails']['tmp_name']['pickupInvoice'],$path);
                            if(isset($orderId))
                            {
                               Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set pickupInvoice="'.$rename_pickup_invoice_docname.'" where orderId='.$orderId)->execute();
                            }
                           // echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_invoice_docname]);
                            break;

            case "pickupBookingConfirmation":
                            
                            $extension = explode(".", $_FILES['OrderMetaDetails']['name']['pickupBookingConfirmation']);
                            $rename_pickup_hotel_booking_docname = "pickup_booking_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_pickup_hotel_booking_docname;
                            move_uploaded_file($_FILES['OrderMetaDetails']['tmp_name']['pickupBookingConfirmation'],$path);
                            if(isset($orderId))
                            {
                                Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set  pickupBookingConfirmation="'.$rename_pickup_hotel_booking_docname.'" where orderId='.$orderId)->execute();
                            }
                            break;

            case "dropInvoice":
                            $extension = explode(".", $_FILES['OrderMetaDetails']['name']['dropInvoice']);
                            $rename_drop_invoice_docname = "dropInvoice_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_order_invoices/'.$rename_drop_invoice_docname;
                            move_uploaded_file($_FILES['OrderMetaDetails']['tmp_name']['dropInvoice'],$path);
                            if(isset($orderId))
                            {
                               Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set dropInvoice="'.$rename_drop_invoice_docname.'" where orderId='.$orderId)->execute();
                            }
                           // echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_invoice_docname]);
                            break;

            case "dropBookingConfirmation":
                            
                            $extension = explode(".", $_FILES['OrderMetaDetails']['name']['dropBookingConfirmation']);
                            $rename_drop_hotel_booking_docname = "drop_booking_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_drop_hotel_booking_docname;
                            move_uploaded_file($_FILES['OrderMetaDetails']['tmp_name']['dropBookingConfirmation'],$path);
                            if(isset($orderId))
                            {
                                Yii::$app->db->createCommand('UPDATE tbl_order_meta_details set  dropBookingConfirmation="'.$rename_drop_hotel_booking_docname.'" where orderId='.$orderId)->execute();
                            }
                            break;

            case "booking_confirmation":
                            //$result = Yii::$app->db->createCommand("UPDATE tbl_order_spot_details set   booking_confirmation_file='".$_FILES['hotel_booking']['name']."' where fk_tbl_order_spot_details_id_order='".$_POST['id_order']."'")->execute();
                            $extension = explode(".", $_FILES['OrderSpotDetails']['name']['booking_confirmation_file']);
                            $rename_hotel_booking_docname = "hotel_booking_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_hotel_booking_docname;
                            move_uploaded_file($_FILES['OrderSpotDetails']['tmp_name']['booking_confirmation_file'],$path);
                            if(isset($orderId))
                            {
                                Yii::$app->db->createCommand('UPDATE tbl_order_spot_details set hotel_booking_verification = 1, booking_confirmation_file="'.$rename_hotel_booking_docname.'" where fk_tbl_order_spot_details_id_order='.$orderId)->execute();
                            }
                            //echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_hotel_booking_docname]);
                            break;
                                            
            case "ticket":
                            $extension = explode(".", $_FILES['Order']['name']['ticket']);
                            $rename_flight_ticket = "flight_ticket_".date('mdYHis').".".$extension[1];
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/customer_ticket/'.$rename_flight_ticket;
                            move_uploaded_file($_FILES['Order']['tmp_name']['ticket'],$path);
                            if(isset($orderId))
                            {
                                $result = Yii::$app->db->createCommand("UPDATE tbl_order set ticket='".$rename_flight_ticket."', flight_verification = 1 where id_order='".$orderId."'")->execute();
                            }
                            //echo Json::encode(['status'=>true,'message'=>'Upload Successful','file_name'=>$rename_flight_ticket]);
                            break;

            case "order_image":
                            ini_set('post_max_size', '64M');
                            ini_set('upload_max_filesize', '64M');
                            $extension = explode(".", $_FILES["order_image"]["name"]);
                            $before_pack = "before_pack_".date('mdYHis').$extension[0].".".$extension[1];
                            $after_pack = "after_pack_".date('mdYHis').$extension[0].".".$extension[1];
                            $damaged_item = "damaged_item_".date('mdYHis').$extension[0].".".$extension[1];
                            $delivered_items = "delivered_item_".date('mdYHis').$extension[0].".".$extension[1];
                            $rename_order_image = ($_POST['before_after_damaged'] == 0) ? $before_pack : (($_POST['before_after_damaged'] == 1) ? $after_pack : (($_POST['before_after_damaged'] == 2) ? $damaged_item : $delivered_items));
                            
                            $path=Yii::$app->params['document_root'].'basic/web/uploads/order_images/'.$rename_order_image;
                            if(move_uploaded_file($_FILES['order_image']['tmp_name'],$path))
                            {
                                $result = ['image'=>$rename_order_image,
                                        'fk_tbl_order_images_id_order'=>$_POST['id_order'],
                                        'before_after_damaged'=>$_POST['before_after_damaged']];
                                Yii::$app->db->createCommand()->insert('tbl_order_images',$result)->execute();
                                echo Json::encode(['status'=>true,'message'=>'upload Successful','id_image'=>Yii::$app->db->getLastInsertID()]);
                            }
                            else
                            {
                                echo Json::encode(['status'=>false,'message'=>'Upload Failed']);
                            }
                            return $this->redirect(['update', 'id' => $_GET['id_order']]);
                            //move_uploaded_file($_FILES['order_image']['tmp_name'],$path);
                            //echo Json::encode(['status'=>true,'message'=>'upload Successful','id_image'=>Yii::$app->db->getLastInsertID()]);
                            break;

            case "signature":
                            $order = Yii::$app->db->createCommand("SELECT o.signature1,o.signature2 FROM tbl_order o where id_order='".$_POST['id_order']."'")->queryOne();
                            if($order['signature1'] != null || isset($order['signature1']))
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
            default:
                            echo Json::encode(['status'=>false,'message'=>'Upload Failed']);
        }


    }

    public static function getEmployeeName($id){
      $emp =  Employee::findOne($id);
      return $emp->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorporateUserRelation()
    {
        return $this->hasOne(CorporateUser::className(), ['fk_tbl_employee_id' => 'id_employee']);
    }

    public function getRelationEmpAllocation(){
        return $this->hasOne(EmployeeAllocation::className(), ['employee_id' => 'id_employee']);
    }

}