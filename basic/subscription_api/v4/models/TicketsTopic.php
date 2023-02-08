<?php

namespace app\subscription_api\v4\models;

use Yii;
use yii\base\Model;
use app\api_v3\v3\models\CorporateEmployeeAirport;
use app\subscription_api\v4\models\HelpTracking;
use app\models\User;



class TicketsTopic extends  \yii\db\ActiveRecord
{
      /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tickets_topic';
    }

    /**
     * @return array the validation rules.
     */

    public function rules()
    {
        return [
            [['topic_id', 'topic_name', 'status'], 'required'],
            
        ];
    }

     /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'topic_id' => 'Topic ID',
            'topic_name' => 'Topic Name',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function gettopics()
    {
        header('Access-Control-Allow-Origin: *');
        $topic =Yii::$app->db->createCommand("SELECT `a`.* 
        FROM `tbl_tickets_topic` `a` 
        where a.status =1 ")->queryAll();
       
        $return_array =array(
            'topic'=>$topic,
        );
        return $return_array;
    }

    public static function createticket($data){
      $return_array = array();
        if(isset($data)){
            
            $topic_id =$data['topic_id'];
            $file_name =json_encode($data['file_name']);
            $comment =json_encode($data['comment']);
            $order_id =$data['order_id'];
            $customer_id =$data['id_customer'];
            $log_desc ="Ticket created by customer";
            $customer_email ="shikha@gloify.com";
            
            $date = date('Y-m-d H:i:s');
            Yii::$app->db->createCommand("insert into tbl_help_tracking (topic_name,parent_id,images_files,customer_comment,order_id,status,created_date,customer_id)
             values($topic_id,'0',$file_name,$comment,$order_id,'pendding','".$date."',$customer_id)")->execute();
            $ticketid = Yii::$app->db->getLastInsertID();
            if(isset($ticketid)){
                $ticket_number = Yii::$app->Common->ticketnumber($order_id,$ticketid);
                $name_ndetail = Yii::$app->Common->getNameOfId($topic_id,$customer_id,$order_id);
                Yii::$app->db->createCommand('INSERT INTO  tbl_ticket_history(ticket_id, customer_id,log_description) 
                VALUES ('.$ticketid.','.$customer_id.',"'.$log_desc.'")')->execute();

                $spot_detail = Yii::$app->db->createCommand("UPDATE tbl_help_tracking set ticket_number='".$ticket_number."' WHERE ticket_id =".$ticketid)->execute();
                
                $mail_data =array(
                    'user_name'=>$name_ndetail['name'],
                    'ticket_number'=>$ticket_number,
                    'topic_name'=>ucwords($name_ndetail['topic_name']),
                    'concern'=>$comment,
                    'order_number'=>$name_ndetail['order_number'],
                );
                User::sendticketemail($customer_email,"Ticket Submission Confirmed Ticket Number #".strtoupper($ticket_number),'ticket_cnf_email',$mail_data,"");
               
            }
            $return_array=array(
                'status'=>false,
                "msg"=>'Record inserted successfully'
            );
            return $return_array;
        }
    }
           
   



   

}