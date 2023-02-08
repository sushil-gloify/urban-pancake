<?php

namespace app\subscription_api\v4\controllers;

use Yii;
use OAuth2;
/* For APi-start */
use linslin\yii2\curl;
use yii\web\Response;
use yii\helpers\Json;
use yii\rest\ActiveController;
/* For APi-end */

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use app\models\Order;
use app\models\User;

use app\subscription_api\v4\models\TicketsTopic;
use app\subscription_api\v4\models\HelpTracking;
use app\subscription_api\v4\models\TicketHistory;



class HelpassistanceApiController extends Controller
{
    
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

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
     
    public function actionTicketDashboard(){

        $id_employee = Yii::$app->user->identity->id_employee;
        $details = Yii::$app->Common->get_super_subscription_details($id_employee);
        return $this->render('super-subscriber-dashboard', [
           'supersubscriber_details' => $details
        ]);
    }

    public function actionTicketsDetails(){
        $searchModel = new HelpTracking();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=20;
        return $this->render('tickets-details',[
            'searchModel' => $searchModel, 
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
       // 'model' => $model,'order_details'=>$order_details,'order_price_break'=>$order_price_break,'waiting_details'=>$waiting_details,'order_history'=>$order_history,'cc_queries'=>$cc_queries,'order_payment_history'=>$order_payment_history]);
       $order_details = Order::getorderdetails($model[0]['order_id']);
       $order_history = Order::getOrderStatusHistory($model[0]['order_id']);

        return $this->render('view', [
            'model' => $model[0],'order_details'=> $order_details['order'],'topic_detail'=>$model[0]['ticketMetaTopicRelation'],'customer_detail'=>$model[0]['ticketMetaCustomerRelation'],'order_history'=> $order_history 
        ]);
        
    }

    public function actionAddcomment($id)
    {
        $history = new TicketHistory();
        $tickets = new HelpTracking();
        $assistant_id =Yii::$app->user->identity->id_employee;
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $date = date('Y-m-d H:i:s');
        $q = Yii::$app->db->createCommand("SELECT * from tbl_help_tracking br WHERE ticket_id = $id")->queryAll();
        $customer_email ="shikha@gloify.com";
        
        $log_desc="";
        if(!empty($_POST['cc_query']) && !empty($_POST['tickets_status'])){
            $log_desc ="Comment and status updated";
        }else if(empty($_POST['cc_query']) && !empty($_POST['tickets_status'])){
            $log_desc ="Status Changed to ".$_POST['tickets_status'];
        }else if(!empty($_POST['cc_query']) && empty($_POST['tickets_status'])){
            $log_desc ="Comment Updated";
        }
       
        if(isset($_POST) )
        {

            Yii::$app->db->createCommand("UPDATE tbl_help_tracking set status='".$_POST['tickets_status']."' where ticket_id='".$id."'")->execute();

            Yii::$app->db->createCommand('INSERT INTO  tbl_help_tracking(parent_id, assistant_id,assistant_comment,status,order_id) VALUES ('.$id.','.$assistant_id.',"'.$_POST['cc_query'].'","'.$_POST['tickets_status'].'" ,'.$q[0]['order_id'].')')->execute();

            Yii::$app->db->createCommand('INSERT INTO  tbl_ticket_history(ticket_id, assistant_id,role_id,log_description) VALUES ('.$id.','.$assistant_id.','.$role_id.',"'.$log_desc.'")')->execute();
            $name_ndetail = Yii::$app->Common->getNameOfId($q[0]['topic_name'],$q[0]['customer_id'],$q[0]['order_id']);

            $mail_data =array(
                'user_name'=>$name_ndetail['name'],
                'ticket_number'=>$q[0]['ticket_number'],
                'topic_name'=>ucwords($name_ndetail['topic_name']),
                'concern'=>$_POST['cc_query'],
                'order_number'=>$name_ndetail['order_number'],
            );
            User::sendticketemail($customer_email,"Ticket Submission Confirmed Ticket Number #".strtoupper($ticket_number),'ticket_cnf_email',$mail_data,"");
           
        

            return $this->redirect(['view', 'id' => $id]);
        }
        else
        {
            return $this->redirect(['view', 'id' => $id]);
        }

    }

    public static function actionGetTicketTopic(){
        header('Access-Control-Allow-Origin: *');
        $data = array();
        $data = TicketsTopic::gettopics();
        return Json::encode(['status'=>true, 'status_code'=>'200','result' => $data]);
     
    }

    public static function actionUploaddocument(){
        header('Access-Control-Allow-Origin: *');
        $data = array();
        
        return Json::encode(['status'=>true, 'status_code'=>'200','result' => $data]);
     
    }

    public static function actionCreateticket()
    {
        header('Access-Control-Allow-Origin: *');
        $result = array();
        $post = file_get_contents("php://input");
        $data = Json::decode($post, true);
        $result = TicketsTopic::createticket($data);
        return Json::encode(['status'=>true, 'result' => $result ,'status_code'=>'200']);
    }

    protected function findModel($id){
        if ($id!== null) {
            $model = HelpTracking::find()
            ->joinWith('ticketMetaTopicRelation')
            ->from('tbl_help_tracking t')->where(['t.ticket_id' => $id])->all();
            return $model;
        } 
        
       
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
  