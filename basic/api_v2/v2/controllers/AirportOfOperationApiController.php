<?php
namespace app\api_v2\v2\controllers;

use yii\web\Controller;
use linslin\yii2\curl;
use yii\web\Response; 
use yii\helpers\Json;
use yii\rest\ActiveController;
use app\models\CityOfOperation; 
use app\models\AirportOfOperation;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use app\models\EmployeeLoginForm;
use app\models\User;
use yii\filters\VerbFilter;
use app\components\SendOTP;
/**
 * Default controller for the `v2` module
 */
class AirportOfOperationApiController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */

    public $enableCsrfValidation = false;

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
    public function actionIndex()
    {
        return $this->render('index');
    }

    
/**
    * This method handles to disable csrf validation  
    * **/
   public function beforeAction($action)
   {
       $this->enableCsrfValidation = false;
       return parent::beforeAction($action);
   }

    public function actionRegion()
    {
    	$region= CityOfOperation::find()->all();
    	$airport= AirportOfOperation::find()->all();
    	if(!empty($region))
    	{
    		if(!empty($airport)) {
			echo Json::encode(array('response'=>array('region'=>$region,'airport'=>$airport),"status"=>"success"));
		}else{
		    	 echo Json::encode(['status'=>false, 'message' => 'Data not found']);
		}
	}else{
				echo Json::encode(['status'=>false, 'message' => 'Data not found']);
	}
    } 

    public  function actionRegionNew()
    {
      header('Access-Control-Allow-Origin: *');
      $region= CityOfOperation::find()->all();
      // $airport= AirportOfOperation::find()->all();
      //$region= CityOfOperation::find()->all();
      $query = new Query;
      $query->select([
              'tbl_airport_of_operation.airport_name AS airport_name',
              'tbl_airport_of_operation.airport_name_id',
              'tbl_airport_of_operation.airport_pincode',
              'CAST(tbl_airport_of_operation.airport_name_id AS SIGNED INTEGER)', 
              'tbl_airport_of_operation.fk_tbl_city_of_operation_region_id',  
              'tbl_airport_text_description.description as  description']
              )  
              ->from('tbl_airport_of_operation')
              ->join('LEFT JOIN', 
                  'tbl_airport_text_description',
                  'tbl_airport_text_description.fk_tbl_airport_id =tbl_airport_of_operation.airport_name_id'
              )
              ->orderBy('fk_tbl_city_of_operation_region_id','asc');
      $command = $query->createCommand();
      $data = $command->queryAll();
      $airport = $data;
      if(!empty($region))
      {
        if(!empty($airport)) {
      echo Json::encode(array('response'=>array('region'=>$region,'airport'=>$airport),"status"=>"success"));
    }else{
           echo Json::encode(['status'=>false, 'message' => 'Data not found']);
    }
  }else{
        echo Json::encode(['status'=>false, 'message' => 'Data not found']);
  }
    }



	public function actionVerifyotp()
	{
	        
	    	$model = new EmployeeLoginForm();
	        $_POST=User::datatoJson();
	        if (isset($_POST))
	        {
	            
	            $employee_detail=$model->employeeDetail();
	            if(!empty($employee_detail['employee']))
	            {
	                if(isset($_POST['device_id']) && isset($_POST['device_token']))
	                {
	                        Yii::$app->db->createCommand("UPDATE tbl_employee set device_id='".$_POST['device_id']."', device_token='".$_POST['device_token']."' where id_employee='".$employee_detail['employee']['id_employee']."'")->execute();
	                }
	                if(empty($employee_detail['employee']['client_id']))
	                {
	                    $client['client_id']=base64_encode($employee_detail['employee']['email']);
	                    $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($employee_detail['employee']['email']);
	                    $client['employee_id']=$employee_detail['employee']['id_employee'];
	                    User::addClient($client);
	                }
	
	               $request['country_code'] = 91;
	               $request['mobile'] = $_POST['mobile'];
	               $request['otp'] = $_POST['otp'];
	               $request['employee_detail'] = $employee_detail;
	               SendOTP::verifyBySendOtpEmployee($request);
	            }
	            else
	            {
	                echo Json::encode(['status'=>false, 'message'=>'Invalid Phone number','error'=>$model->geterrors()]);
	            }
	            
	        }
	        else
	        {
	            echo Json::encode(['status'=>false, 'message'=>'Please Enter your number','error'=>$model->geterrors()]);
	        }
	}
}
