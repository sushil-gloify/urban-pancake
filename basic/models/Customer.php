<?php

namespace app\models;

use Yii;
use yii\helpers\Json;

/**
 * This is the model class for table "tbl_customer".
 *
 * @property integer $id_customer
 * @property string $name
 * @property string $mobile
 * @property string $email
 * @property string $address
 * @property string $document
 * @property integer $mobile_number_verification
 * @property integer $email_verification
 * @property integer $status
 * @property string $date_of_birth
 * @property string $date_created
 *
 * @property Order[] $orders
 * @property Otp[] $otps
 */
class Customer extends \yii\db\ActiveRecord
{
    public $register;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_customer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $matchArr = array('customer/create-corporate-employee');
        if(in_array(Yii::$app->controller->module->requestedRoute,$matchArr)){
            return [
                [['name','mobile','email'], 'required'],
                [['mobile'], 'match','pattern' => '/^[0-9]+$/u','message' => "Mobile Number is Invalid"],
                [['mobile'], 'required', 'on' => 'search'],
                [['mobile'], 'unique', 'on' => 'register'],
                [['mobile'],'string', 'max' => 10],
                [['mobile'],'unique','message'=>'Mobile Number has been taken already'],
                [['name','email', 'gender'], 'checkValidation', 'skipOnEmpty' => false],
                [['name'], 'string', 'max' => 35],
                [['email'],'email'],
                [['email'],'unique','message'=>'Email has been taken already'],
                [['mobile_number_verification', 'email_verification', 'status', 'mobile','acc_verification'], 'integer'],
                [['date_of_birth', 'area','date_created','gender','device_id','device_token','id_proof_verification','landmark','building_number','building_restriction','other_comments', 'register'], 'safe'],
                [['gst_number'],'string','min' =>15, 'max'=>15],
                [['gst_number'],'match','pattern' => '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}+$/u','message'=>'please enter correct GST Number'],
                // [['airline_id'], 'required'],
            ];
        } else {
            return [
                //[['name','mobile','email','date_of_birth', 'address_line_1','gender','pincode','fk_tbl_customer_id_country_code'], 'required'],
                [['mobile'], 'required', 'on' => 'search'],
                //[['mobile'], 'unique', 'on' => 'register'],
                [['name','email', 'gender'], 'checkValidation', 'skipOnEmpty' => false],

                [['mobile_number_verification', 'email_verification', 'status', 'mobile','acc_verification'], 'integer'],
                [['date_of_birth', 'area','date_created','gender','device_id','device_token','id_proof_verification','landmark','building_number','building_restriction','other_comments', 'register'], 'safe'],
                [['name'], 'string', 'max' => 45],
                [['customerId'], 'string', 'max' => 45],

                //[['mobile'], 'string', 'max' => 10],
                [['email'],'email'],
                [['email'],'unique','message'=>'Email has been taken already'],
                [['mobile'],'unique','message'=>'Mobile Number has been taken already'],
                [['document'], 'string', 'max' => 255],
                [['gst_number'],'string','min'=> 6, 'max'=>15],
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_customer' => 'Id Customer',
            'name' => 'Name',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'document' => 'Document',
            'mobile_number_verification' => 'Mobile Number Verification',
            'id_proof_verification' => 'Id proof Verification',
            'email_verification' => 'Email Verification',
            'status' => 'Status',
            'date_of_birth' => 'Date Of Birth',
            'date_created' => 'Date Created',
            'fk_tbl_customer_id_country_code'=>'Country Code',
            'acc_verification' => 'Account Verification',
            'gst_number' => 'GST Number',
            'tour_id' => 'Tour ID',
            'fk_role_id' => 'Role Id',
            'customerId'=>'Corporate ID'
        ];
    }

    /*
     * To validate when cash is checked
     */
    public function checkValidation($attribute, $params){ 
        if(!$this->$attribute && $this->register == 'Yes'){
            $this->addError($attribute,'Required fields can not be blank.');
            return false;
        } 
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['fk_tbl_order_id_customer' => 'id_customer']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOtps()
    {
        return $this->hasMany(Otp::className(), ['fk_tbl_otp_id_customer' => 'id_customer']);
    }

    public function getCustomercountrycode()
    {
        return $this->hasone(CountryCode::className(), ['id_country_code' => 'fk_tbl_customer_id_country_code']);
    }


    /**
    *function for image urls
    *type - 1 for profile, 2 for id proof
    */
    public function getImageUrl($type) 
    {
        if($type==1){
            if( $this->customer_profile_picture != NULL &&  !empty($this->customer_profile_picture) ){
                return Yii::$app->params['site_url'].'uploads/customer_profile_picture/'.$this->customer_profile_picture;
            }
        }
        else if($type==2){
            if( $this->document != NULL && !empty($this->document)){
                return Yii::$app->params['site_url'].'uploads/customer_documents/'.$this->document;
            }
        }else{
            return '';
        }
    }



    public function getcontrycode($id)
    {
        $CountryCode = Yii::$app->db->createCommand("select * from tbl_country_code where id_country_code='".$id."'")->queryOne();
        if(!empty($CountryCode))
        {
            return $CountryCode['country_code'];
        }else{
            return '';
        }
    }


    public static function isupdated($id)
    {
        if($id !=0 && $id !='')
        {
            $customer = Customer::find()->where(['id_customer'=>$id])->select('id_customer,update_status')->one();
            if($customer['update_status'] == 1)
            {
                /*http_response_code(403);
                echo Json::encode(['status'=>false, 'update_status' => 'Customer details has been updated!']);
                die;*/
                return true;
            }else{
                return true;
            }

        }else{
            return true;
        }
    }

    public static function verifyusernumber($data){
        $user_detail = array();
        if(!preg_match('/^[0-9]{10}+$/', $data['mobile'])) {
            $msg =" Invalid Phone Number";
            $return_array = array(
                'message'=> $msg ,
            );
            return $return_array ;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $return_array = array(
                'message'=> 'email is not valid'
            );
            return $return_array ;
        }

        // if(!preg_match('/^[A-Z0-9]{15}+$/',$data['gst_number'])){
        //     return array('message' => 'please enter correct gst number');
        // }
        if(!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',$data['gst_number'])){
            return array('message' => 'please enter correct GST Number');
        }

        if(empty($data['airline_id'])){
            return array("message" => "Please send airline id.");
        }

        if(!empty($data['airline_id']) && ($data['airline_id'] == 5)){
            if(empty($data['tour_id'])){
                return array('message' => 'Please send Tour ID.');
            } if(substr($data['tour_id'],0,2) != "UK"){
                return array('message' => 'Please correct your Tour ID');
            } if(!preg_match("/^[A-Z0-9]{3,14}$/", substr($data['tour_id'],2))){
                return array('message' => "Please enter correct Tour ID");
            }
            // Check tour id not mapped with different gst number
            $res = Yii::$app->db->createCommand("Select * FROM tbl_customer where tour_id = '".$data['tour_id']."'")->queryOne();
            if(!empty($res['gst_number']) && ($res['gst_number'] != $data['gst_number'])){
                return array("message" => "Different GST number mapped with this Tour Id.");
            }
            // Check corporate id not mapped with different tour id
            
        }

        $checkBoth = Yii::$app->db->createCommand("select * from tbl_customer where mobile ='".$data['mobile']."' and email ='".$data['email']."'")->queryOne();
        if(!empty($checkBoth)){
            return false;
        } else {
            // $checkNumber = Yii::$app->db->createCommand("select * from tbl_customer where mobile ='".$data['mobile']."'")->queryOne();
            // if(!empty($checkNumber)){
            //     return array("message" => 'Mobile number exist, use another Mobile number.');
            // }

            $checkEmail = Yii::$app->db->createCommand("select * from tbl_customer where email ='".$data['email']."'")->queryOne();
            if(!empty($checkEmail)){
                return array("message" => 'Email Id exist, use another Email Id.');
            }
        }
    }

    
}
