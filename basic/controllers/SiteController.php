<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use app\components\AccessRule;
use yii\web\Controller;
use app\models\Order;
use app\models\ThirdpartyCorporate;
use yii\common;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\TaxValues;
use app\models\ContactForm;
use app\models\User;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout','index'],
                'rules' => [
                    [
                        'actions' => ['login','error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout','index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['about','contact'],
                        'allow' => true,
                        'roles' => [1],
                    ],
                    /*[
                        'actions' => ['about','logout'],
                        'allow' => true,
                        'roles' => [2],
                    ],*/
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $model['tv'] = new TaxValues();
        $today = date('Y-m-d');
        $tommorow = date('Y-m-d', strtotime('+1 day', time()));

        $currentOrdersCount = Order::find()->where("order_date='".$today."' OR order_date='".$tommorow."'")->count();
        $currentOrdersCount = ($currentOrdersCount) ? $currentOrdersCount : 0;
        $flexibleOrdersCount = Order::find()->where("corporate_id=0 AND (meet_time_gate IS NOT NULL OR meet_time_gate != '') AND (flight_number IS NOT NULL OR flight_number != '') AND (order_status='Confirmed' OR order_status='Arrival into airport warehouse' OR order_status='Assigned' OR order_status='Open')")->count();
        $flexibleOrdersCount = ($flexibleOrdersCount) ? $flexibleOrdersCount : 0;
        $rescheduleOrdersCount = Order::find()->where("(reschedule_luggage= 1) AND (order_status='Confirmed' OR order_status='Arrival into airport warehouse' OR order_status='Assigned' OR order_status='Out for delivery at customer location' OR order_status='Out for delivery at gate 1' OR order_status='Open')")->count();
        $rescheduleOrdersCount = ($rescheduleOrdersCount) ? $rescheduleOrdersCount : 0;
        $undeliveryOrdersCount = Order::find()->where("order_status='Undelivered'")->count();
        $undeliveryOrdersCount = ($undeliveryOrdersCount) ? $undeliveryOrdersCount : 0;
        $refundOrdersCount = Order::find()->where("modified_amount > 0")->count();
        $refundOrdersCount = ($refundOrdersCount) ? $refundOrdersCount : 0;
        $allOrdersCount = Order::find()->joinWith('orderMetaDetailsRelation')->from('tbl_order t')->where(['t.deleted_status' => 0])->count();
        $allOrdersCount = ($allOrdersCount) ? $allOrdersCount : 0;
        $data = [
          'currentOrdersCount' => $currentOrdersCount,
          'flexibleOrdersCount' => $flexibleOrdersCount,
          'rescheduleOrdersCount' => $rescheduleOrdersCount,
          'undeliveryOrdersCount' => $undeliveryOrdersCount,
          'refundOrdersCount' => $refundOrdersCount,
          'allOrdersCount' => $allOrdersCount,
        ];
        return $this->render('index',['model'=>$model, 'data' => $data]);
    }

     public function actionIndex1()
    {
        //print_r(Yii::$app->session);exit;
        echo 1;
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
   {
       if (!Yii::$app->user->isGuest) {
           return $this->goHome();
       }
       $model = new LoginForm();
       if ($model->load(Yii::$app->request->post()) && $model->login()) {

           $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
          
           // if($role_id == 9){
           //    $urlPage = (in_array($role_id, [9]) ? '/employee/sales-dashboard' : '');
           //    return $this->redirect([$urlPage]);
           // }
           // $urlPage = (in_array($role_id, [8]) ? '/employee/corporate-dashboard' : '');
           // $kioskUrlPage = (in_array($role_id, [10]) ? '/employee/kiosk-dashboard' : '');

           // $corporatekioskUrlPage = (in_array($role_id, [11]) ? '/employee/corporate-kiosk-dashboard' : '');
           // if(){

           // }else{

           // }
           $corporateUrl = Yii::$app->Common->redirectUrl($role_id);
          
           // $thirdpartycorporateUrl = Yii::$app->Common->thirdpartyredirectUrl($role_id);
           // print_r($redirectUrl);exit;
           if($corporateUrl == ""){ 
               // $copId =  Yii::$app->user->identity->id_employee;
               
               // $cId = \app\models\CorporateDetails::find()
               //         ->select(['corporate_detail_id'])
               //         ->where(['employee_id'=>$copId])
               //         ->one();
                       // print_r($cId);exit;
               // if(empty($cId)){
               //     Yii::$app->user->logout();
               //     return $this->redirect(['site/login']);
               // }else{
                //    return $this->redirect([$corporateUrl]);
               // }
                return $this->goBack();
           }else{
                return $this->redirect([$corporateUrl]);
           }
       }
       return $this->render('login', [
           'model' => $model,
       ]);
   }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        //return $this->goHome();
        return $this->redirect(['site/login']);
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }
 
    /**
     * Displays about page.
     *
     * @return string
     */ 
    public function actionAbout() 
    {
        return $this->render('about');
    }
 
     public function actionForgotPassword() 
    {
      $model = new LoginForm(); 
      if ($model->load(Yii::$app->request->post())) {
        //print_r($_POST);exit; 
        $email=$_POST['LoginForm']['forgotemail'];
        $encrypted_string=\app\models\User::encryptIt($email); 

        $hash_key=$encrypted_string;
        $access_key=strtotime(date('His')).rand(10,100);

        $url=Yii::$app->params['site_url'].'/index.php?r=site/create-forgot-password&hash_key='.$hash_key.'&access_key='.$access_key; 

        $forgot_pass= new \app\models\ForgotPasswordLinkValidation();
        $forgot_pass->key=$access_key;
        $forgot_pass->status=0;
        $forgot_pass->save();
        Yii::$app->session->setFlash('success', "Password reset link has been sent to your email");

        $data = $url;
        \app\models\User::sendemail($_POST['LoginForm']['forgotemail'],"Reset Password Request from Caterx",'resetpassword',$data);
        return $this->redirect(['site/login']);
      }
        return $this->renderAjax('forgotpassword', [
           'model' => $model,
       ]);
    }


    public function actionTcpdf()
    {
        return $this->render('tcpdf');
                        
    }

    public function actionCheckemailvalidation(){
      $email=$_POST['email'];
      $data = \app\models\User::findByUsername($email);
      if($data){
        return 1;
      }else{
        return 0;
      }
    }


public function actionCreateForgotPassword($hash_key,$access_key){
      //print_r($hash_key);
    $model= new \app\models\LoginForm(); 
    $decrypted_string=\app\models\User::decryptIt($hash_key); 
    $user= \app\models\User::find()->where(['email'=>$decrypted_string, 'status' => 1])->asArray()->one(); 
    $forgot_pass= \app\models\ForgotPasswordLinkValidation::find()->where(['key'=>$access_key ])->one();
    //print_r($user);exit;
      if(!empty($_POST)){  
        if($_POST['LoginForm']['password'] == $_POST['LoginForm']['password2']){
          $forgot_pass= \app\models\ForgotPasswordLinkValidation::find()->where(['key'=>$access_key])->one();
          $forgot_pass->status=1;
          $forgot_pass->save(false);
          return $this->redirect(['site/login']);
        }
      }
       return $this->render('createForgotPassword', [
          'model'=>$model,
           'user' => $user,
           'access_key'=>$access_key,
           'forgot_pass'=>$forgot_pass,
           'decrypted_string'=>$decrypted_string,

       ]);
    }  
}
