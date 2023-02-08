<?php

namespace app\subscription_api\v4\models;

use Yii;
use yii\base\Model;
use app\api_v3\v3\models\CorporateEmployeeAirport;
use yii\data\ActiveDataProvider;
use app\subscription_api\v4\models\TicketsTopic;

class TicketHistory extends  \yii\db\ActiveRecord
{
      /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_ticket_history';
    }

    /**
     * @return array the validation rules.
     */

    public function rules()
    {
        return [
            [['history_id', 'ticket_id','assistant_id', 'role_id','log_description'], 'required'],
            
        ];
    }

     /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'history_id' => 'History Id',
            'ticket_id' => 'Ticket Id',
            'assistant_id' => 'Assistant Id',
            'role_id' => 'Role Id',
            'log_description' => 'Log Description',
            'created_date' => 'Created Date',
            'customer_id'=>'customer id'

        ];
    }
   
   
   

   
   

}