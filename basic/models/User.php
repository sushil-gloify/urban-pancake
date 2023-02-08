<?php

namespace app\models;
use yii\db\ActiveRecord;

use Yii;
use yii\base\Model;
use yii\web\Response;
use kartik\export\ExportMenu;
use yii\helpers\Json;
class User extends ActiveRecord implements \yii\web\IdentityInterface
{
    //public $id;
    //public $username;
    //public $password;
    public $authKey;
    public $accessToken;
    

    /*private static $users = [
        '100' => [
            'id' => '100',
            'username' => 'admin',
            'password' => 'admin',
            'authKey' => 'test100key',
            'accessToken' => '100-token',
        ],
        '101' => [
            'id' => '101',
            'username' => 'demo',
            'password' => 'demo',
            'authKey' => 'test101key',
            'accessToken' => '101-token',
        ],
    ];*/

    public static function tableName() 
    { 
        return 'tbl_employee'; 
    }
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $user = self::find()->where(['id_employee' => $id])->one();
        // if(!count($user)){
        if(!$user){
            return null;
        }
        return new static($user);
        //return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $user= self::find()->where(['email' => $username, 'status' => 1])->one();
        // if(!count($user)){
        if(!$user){
            return null;
        }
        return new static($user);
/*
        foreach (self::$users as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;*/
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id_employee;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === sha1($password);
    }

    public function addClient($data){
        //print_r($data);exit;
        $data_inserted = Yii::$app->db->createCommand()->insert('oauth_clients',$data)->execute();
        /* $data_selected = Yii::$app->db->createCommand("SELECT * from oauth_clients where client_secret='".$data['client_secret']."'")->execute();
         print_r($data_selected);
        print_r($data);
        print_r($data_inserted);exit;*/
    }

    public static function datatoJson(){
        //Yii::$app->response->format = Response::FORMAT_JSON;
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        return $data;
    } 

    public static function sendemail($recipient,$subject,$msg,$data)
    {
        Yii::$app->mailer->compose($msg,['data'=>$data])
                 //->setFrom('somebody@domain.com')
                 //->setFrom('carterxapp@gmail.com')
                 ->setFrom('noreply@carterporter.in')
                 //->sendmailattachment('../uploads')
                 ->setTo($recipient)
                 ->setSubject($subject)
                 //->setTextBody($msg)
                 ->send();
    }

    public static function sendMHLemail($recipient,$subject,$msg,$data,$set_CC=NULL)
    {
        $setCc = isset($set_CC) ? Yii::$app->params['customer_email'] : "customercare@carterporter.in";
        Yii::$app->mailer->compose($msg,['data'=>$data])
        ->setFrom('noreply@carterporter.in')
        ->setTo($recipient)
        ->setSubject($subject)
        ->setCc($setCc)
        ->send();
    }

    public static function sendconfirmationemail($recipient,$subject,$msg,$data)
    {
        $EmailRes = ($data['order_details']['order']['order_transfer'] == 1) ? Yii::$app->Common->setCcEmailId($data['order_details']['order']['city_id'], 'region') : Yii::$app->Common->setCcEmailId($data['order_details']['order']['airport_id'], 'airport');
         
        $setCC = !empty($data['order_details']['corporate_details']['default_email']) ? array_merge($EmailRes,array($data['order_details']['corporate_details']['default_email'])) : array(Yii::$app->params['customer_email']);
        Yii::$app->mailer->compose($msg,['data'=>$data])
            //->setFrom('somebody@domain.com')
            //->setFrom('carterxapp@gmail.com')
            ->setFrom('noreply@carterporter.in')
            //->sendmailattachment('../uploads')
            ->setTo($recipient)
            ->setCc($setCc)
            ->setSubject($subject)
            //->setTextBody($msg)
            ->send();
    }

    /*Express Email*/
    public static function sendemailexpressattachment($recipient,$subject,$msg,$data, $attachment_det,$setCC="",$status=false)
    {
        if(empty($status) && !empty($setCC)){
            $EmailRes = ($data['order_details']['order']['order_transfer'] == 1) ? Yii::$app->Common->setCcEmailId($data['order_details']['order']['city_id'], 'region') : Yii::$app->Common->setCcEmailId($data['order_details']['order']['airport_id'], 'airport');
            
            $setCC = !empty($data['order_details']['corporate_details']['default_email']) ? array_merge($EmailRes,array($data['order_details']['corporate_details']['default_email'])) : array(Yii::$app->params['customer_email']);

            Yii::$app->mailer->compose($msg,['data'=>$data])
                //->setFrom('somebody@domain.com')
                //->setFrom('carterxapp@gmail.com')
                ->setFrom('noreply@carterporter.in')
                ->attach($attachment_det['folder_path'])
                //->setAttachment($attachment_det['folder_path'])
                ->setTo($recipient)
                ->setCc($setCC)
                ->setSubject($subject)
                //->setTextBody($msg)
                ->send();
        } else if(!empty($status) && !empty($setCC)){
            Yii::$app->mailer->compose($msg,['data'=>$data])
                ->setFrom('noreply@carterporter.in')
                ->attach($attachment_det['folder_path'])
                ->setTo($recipient)
                ->setCc($setCC)
                ->setSubject($subject)
                ->send();
        } else {
            Yii::$app->mailer->compose($msg,['data'=>$data])
                ->setFrom('noreply@carterporter.in')
                ->attach($attachment_det['folder_path'])
                ->setTo($recipient)
                ->setSubject($subject)
                ->send();
        }
    }

    /*Subscription Email*/
    public static function sendemailsubscriptionattachment($recipient,$subject,$msg,$data, $attachment_det,$setCC="")
    {
       // $EmailRes = ($data['order_details']['order']['order_transfer'] == 1) ? Yii::$app->Common->setCcEmailId($data['order_details']['order']['city_id'], 'region') : Yii::$app->Common->setCcEmailId($data['order_details']['order']['airport_id'], 'airport');        
        //$setCC =  array(Yii::$app->params['customer_email']);
        Yii::$app->mailer->compose($msg,['data'=>$data])
            //->setFrom('somebody@domain.com')
            //->setFrom('carterxapp@gmail.com')
            ->setFrom('noreply@carterporter.in')
            ->attach($attachment_det['folder_path'])
            //->setAttachment($attachment_det['folder_path'])
            ->setTo($recipient)
            //->setCc($setCC)
            ->setSubject($subject)
            //->setTextBody($msg)
            ->send();
    }

    public static function sendEmailExpressMultipleAttachment($recipient,$subject,$msg,$data, $attachment_det,$setCC="")
    {
        $EmailRes = ($data['order_details']['order']['order_transfer'] == 1) ? Yii::$app->Common->setCcEmailId($data['order_details']['order']['city_id'], 'region') : Yii::$app->Common->setCcEmailId($data['order_details']['order']['airport_id'], 'airport');
         
        $setCC = !empty($data['order_details']['corporate_details']['default_email']) ? array_merge($EmailRes,array($data['order_details']['corporate_details']['default_email'])) : array(Yii::$app->params['customer_email']);
        
        Yii::$app->mailer->compose($msg,['data'=>$data])
            //->setFrom('somebody@domain.com')
            //->setFrom('carterxapp@gmail.com')
            ->setFrom('noreply@carterporter.in')
            ->attach($attachment_det['folder_path'])
            ->attach($attachment_det['second_path'])
            //->setAttachment($attachment_det['folder_path'])
            ->setTo($recipient)
            ->setCc($setCC)
            ->setSubject($subject)
            //->setTextBody($msg)
            ->send();
    }

    public static function sendemailasattachment($recipient,$subject,$msg,$data, $attachment_det)
    {
        foreach ($data as $key => $value) {
            if($value['order']['corporate_type'] == 1)
            {
                Yii::$app->mailer->compose($msg,['data'=>$data])
                     //->setFrom('somebody@domain.com')
                     //->setFrom('carterxapp@gmail.com')
                     ->setFrom('noreply@carterporter.in')
                     ->attach($attachment_det['folder_path'])
                     //->setAttachment($attachment_det['folder_path'])
                     ->setTo($recipient)
                     ->setSubject($subject)
                     //->setTextBody($msg)
                     ->send();
            }else{
                Yii::$app->mailer->compose($msg,['data'=>$data])
                     //->setFrom('somebody@domain.com')
                     //->setFrom('carterxapp@gmail.com')
                     ->setFrom('noreply@carterporter.in')
                     //->setAttachment($attachment_det['folder_path'])
                     ->setTo($recipient)
                     ->setSubject($subject)
                     //->setTextBody($msg)
                     ->send();
            }break;
        }
        
    }

    public static function sendsms($mobile,$msg)
    {
        //Your authentication key
        $authKey = "155266AOcgrvwy9Qoy5937abe3";

        //Multiple mobiles numbers separated by comma
        /*$country_code = 91;
        $mobileNumber = $country_code.$mobile;*/
        $mobileNumber = $mobile;

        //Sender ID,While using route4 sender id should be 6 characters long.
        $senderId = "CPortr";

        //Your message to send, Add URL encoding here.
        $message = urlencode($msg);

        //Define route 
        $route = 04;

        //$country=0; //0 for international numbers
        //Prepare you post parameters
        $postData = array(
            'authkey' => $authKey,
            'mobiles' => $mobileNumber,
            'message' => $message,
            'sender' => $senderId,
            'route' => $route,
            //'country' =>$country,
        );

        //API URL
        $url="https://control.msg91.com/api/sendhttp.php";

        // init the resource
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
            //,CURLOPT_FOLLOWLOCATION => true
        ));


        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


        //get response
        $output = curl_exec($ch);

        //Print error if any
        if(curl_errno($ch))
        {
            echo 'error:' . curl_error($ch);
        }

        curl_close($ch);

        //echo $output;
    }



/*
* For CSV download option for all GridViews Page...
*/
   public function downloadExportData($dataProvider,$gridColumns,$dyFileName) 
   {
        echo ExportMenu::widget([
            'dataProvider' => $dataProvider,
            'columns' => $gridColumns,
            'fontAwesome' => true,
            'showConfirmAlert'=>false,
            'filename'=>$dyFileName,
            'exportConfig'=>[ ExportMenu::FORMAT_TEXT => false,
            ExportMenu::FORMAT_PDF => false,ExportMenu::FORMAT_HTML=> false,ExportMenu::FORMAT_EXCEL=> false,ExportMenu::FORMAT_EXCEL_X=> false],
            'target'=>[ExportMenu::TARGET_SELF],
            'dropdownOptions' => [
                'label' => 'Export CSV',
                'class' => 'btn btn-default'
            ]
        ]);
    }

 /*
* For CSV download option for all GridViews Page...
*/
   public function downloadExportDataCorp($dataProvider,$gridColumns,$dyFileName) 
   {
        echo ExportMenu::widget([
            'dataProvider' => $dataProvider,
            'columns' => $gridColumns,
            'fontAwesome' => true,
            'showConfirmAlert'=>false,
            'filename'=>$dyFileName,
            'exportConfig'=>[ ExportMenu::FORMAT_TEXT => false,
            ExportMenu::FORMAT_PDF => false,ExportMenu::FORMAT_HTML=> false,ExportMenu::FORMAT_EXCEL=> false,ExportMenu::FORMAT_EXCEL_X=> false],
            'target'=>[ExportMenu::TARGET_SELF],
            'dropdownOptions' => [
                'label' => 'Export Corporate CSV',
                'class' => 'btn btn-default'
            ]
        ]);
    }

    /*
    * For CSV download option for all GridViews Page...
    */
   public function downloadExportDataCorpCust($dataProvider,$gridColumns,$dyFileName) 
   {
        echo ExportMenu::widget([
            'dataProvider' => $dataProvider,
            'columns' => $gridColumns,
            'fontAwesome' => true,
            'showConfirmAlert'=>false,
            'filename'=>$dyFileName,
            'exportConfig'=>[ ExportMenu::FORMAT_TEXT => false,
            ExportMenu::FORMAT_PDF => false,ExportMenu::FORMAT_HTML=> false,ExportMenu::FORMAT_EXCEL=> false,ExportMenu::FORMAT_EXCEL_X=> false],
            'target'=>[ExportMenu::TARGET_SELF],
            'dropdownOptions' => [
                'label' => 'Export Corporate Customer CSV',
                'class' => 'btn btn-default'
            ]
        ]);
    }

    /*
    * For CSV download option for all GridViews Page...
    */
   public function downloadExportInsuranceData($dataProvider,$gridColumns,$dyFileName) 
   {
        echo ExportMenu::widget([
            'dataProvider' => $dataProvider,
            'columns' => $gridColumns,
            'fontAwesome' => true,
            'showConfirmAlert'=>false,
            'filename'=>$dyFileName,
            'exportConfig'=>[ ExportMenu::FORMAT_TEXT => false,
            ExportMenu::FORMAT_PDF => false,ExportMenu::FORMAT_HTML=> false,ExportMenu::FORMAT_EXCEL=> false,ExportMenu::FORMAT_EXCEL_X=> false],
            'target'=>[ExportMenu::TARGET_SELF],
            'dropdownOptions' => [
                'label' => 'Export Insurance CSV',
                'class' => 'btn btn-default'
            ]
        ]);
    }

    public function encryptIt( $q ) { 
     $cryptKey  = 'carterporterencryption'; 

        $qEncoded = Yii::$app->getSecurity()->hashData($q ,$cryptKey);
        return $qEncoded;

    }

    public function decryptIt( $q ) {
        $cryptKey  = 'carterporterencryption';  
        $qDecoded = Yii::$app->getSecurity()->validateData($q, $cryptKey);
        return $qDecoded;


    }
    /* send invoice attatchment */ 
    public static function sendemailInvoiceattachmenttouser($recipient,$subject,$msg,$data, $attachment_det,$setCC="")
    {
        
        
        $message = Yii::$app->mailer->compose($msg,['data'=>$data])
        ->setFrom('noreply@carterporter.in')
        ->setTo($recipient)
        ->setSubject($subject);
        foreach ($attachment_det as $file) {
            $filename =  $file['folder_path'];
            
            $message->attach($filename);
        }
    
        $message->send();
    
    }

    public static function sendemailInvoiceattachment($recipient,$subject,$msg,$data, $attachment_det,$setCC="")
    {
        $setCC =  array_merge($recipient['email'],array(Yii::$app->params['customer_email']));
        $cc =array_unique($setCC);
        $message = Yii::$app->mailer->compose($msg,['data'=>$data])
        ->setFrom('noreply@carterporter.in')
        ->setTo($cc)
        ->setSubject($subject);
        foreach ($attachment_det as $file) {
            $filename =  $file['folder_path'];
            
            $message->attach($filename);
        }
    
        $message->send();
    
    }
    public static function sendcnfemail($recipient,$subject,$msg,$data, $attachment_det,$setCC)
    {
        //echo '<pre>';
        //print_r($attachment_det); exit;
        $cc =array_unique($setCC);
        $message = Yii::$app->mailer->compose($msg,['data'=>$data])
        ->setFrom('noreply@carterporter.in')
        ->setTo($recipient)
        ->setCc($cc)
        ->setSubject($subject)
        ->attach($attachment_det['folder_path']);
        $message->send();
    
    }
    public static function sendticketemail($recipient,$subject,$msg,$data,$setCC)
    {
        //echo '<pre>';
        //print_r($attachment_det); exit;
        //$cc =array_unique($setCC);
        $message = Yii::$app->mailer->compose($msg,['data'=>$data])
        ->setFrom('noreply@carterporter.in')
        ->setTo($recipient)
        ->setCc($cc)
        ->setSubject($subject);
       // ->attach($attachment_det['folder_path']);
        $message->send();
    
    }
  
  
    


//public function downloadExportInsuranceData($dataProvider,$gridColumns,$dyFileName) 
//   {
//	echo ExportMenu::widget([
//		'dataProvider' => $dataProvider,
//		'columns' => $gridColumns,
//		'fontAwesome' => true,
//		'showConfirmAlert'=>false,
//		'filename'=>$dyFileName,
//		'exportConfig'=>[ ExportMenu::FORMAT_TEXT => false,
//		ExportMenu::FORMAT_PDF => false,ExportMenu::FORMAT_HTML=> false,ExportMenu::FORMAT_EXCEL=> false,ExportMenu::FORMAT_EXCEL_X=> false],
//		'target'=>[ExportMenu::TARGET_SELF],
//		'dropdownOptions' => [
//		'label' => 'Export Insurance CSV',
//		'class' => 'btn btn-default'
//		]
//	]);
//    }
//
//

    public static function sendSubscriberEmailWithMultipleAttachment($recipient,$subject,$msg,$data, $attachment_det,$setCC="",$status = false){
        if(($status=='false') && (!empty($setCC))){
            Yii::$app->mailer->compose($msg,['data'=>$data])
                ->setFrom('noreply@carterporter.in')
                ->attach($attachment_det['folder_path'])
                ->attach($attachment_det['second_path'])
                ->setTo($recipient)
                ->setCc($setCC)
                ->setSubject($subject)
                ->send();
        } else if(($status == true) && (!empty($setCC))){
            $EmailRes = ($data['order_details']['order']['order_transfer'] == 1) ? Yii::$app->Common->setCcEmailId($data['order_details']['order']['city_id'], 'region') : Yii::$app->Common->setCcEmailId($data['order_details']['order']['airport_id'], 'airport');
            
            $setCC = !empty($data['order_details']['corporate_details']['default_email']) ? array_merge($EmailRes,array($data['order_details']['corporate_details']['default_email'])) : array(Yii::$app->params['customer_email']);
            
            Yii::$app->mailer->compose($msg,['data'=>$data])
                ->setFrom('noreply@carterporter.in')
                ->attach($attachment_det['folder_path'])
                ->attach($attachment_det['second_path'])
                ->setTo($recipient)
                ->setCc($setCC)
                ->setSubject($subject)
                ->send();
        } else {
            Yii::$app->mailer->compose($msg,['data'=>$data])
                ->setFrom('noreply@carterporter.in')
                ->attach($attachment_det['folder_path'])
                ->attach($attachment_det['second_path'])
                ->setTo($recipient)
                ->setSubject($subject)
                ->send();
        }
    }
}
