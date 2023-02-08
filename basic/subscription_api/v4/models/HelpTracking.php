<?php

namespace app\subscription_api\v4\models;

use Yii;
use yii\base\Model;
use app\api_v3\v3\models\CorporateEmployeeAirport;
use yii\data\ActiveDataProvider;
use app\models\Order;
use app\models\Customer;
use app\subscription_api\v4\models\TicketsTopic;

class HelpTracking extends  \yii\db\ActiveRecord
{
      /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_help_tracking';
    }

    /**
     * @return array the validation rules.
     */

    public function rules()
    {
        return [
            [['assistant_comment', 'assistant_id','ticket_number', 'status'], 'required'],
            
        ];
    }

     /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ticket_id' => 'Ticket Id',
            'parent_id' => 'Parent Id',
            'ticket_number' => 'Ticker Number',
            'topic_name' => 'Topic Name',
            'assistant_id' => 'Assistant ID',
            'assistant_comment' => 'Assistant Comment',
            'customer_comment'=>'Customer Comment',
            'images_files'=>'Images File',
            'docs_files'=>'Docs FIles',
            'videos'=>'videos',
            'order_id'=>'Order ID',
            'status'=>'Status',
            'created_at'=>'Created At',
            'updated_at'=>'Updated At',
            'customer_id'=>'customer_id'

        ];
    }
    public function search($params)
    {
        $employeeId = Yii::$app->user->identity->id_employee;
        $airportArray = array();
        
        $query = HelpTracking::find()->from('tbl_help_tracking h')
        ->leftjoin('tbl_order o','o.id_order = h.order_id')
        ->where("o.corporate_id IN (SELECT corporate_id FROM `tbl_corporate_user` where fk_tbl_employee_id = $employeeId) and h.parent_id = 0");

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'ticket_id' => SORT_DESC,
                ],
              ]
        ]);
        $this->load($params);
        $query->andFilterWhere(['like', 'h.ticket_number', $this->ticket_number])
              ->andFilterWhere(['like', 'h.status', $this->status]);

        //echo $query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql; die;
      
        return $dataProvider; 
     
    }
    public function getTicketMetaDetailsRelation()
    {
        return $this->hasMany(Order::className(), ['id_order' => 'order_id']);
    }
    public function getTicketMetaCustomerRelation()
    {
        return $this->hasMany(Customer::className(), ['id_customer' => 'customer_id']);
    }
    public function getTicketMetaTopicRelation()
    {
        return $this->hasMany(TicketsTopic::className(), ['topic_id' => 'topic_name']);
    }
   
   

   
   

}