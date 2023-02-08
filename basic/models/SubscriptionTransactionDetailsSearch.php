<?php

namespace app\models;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\SubscriptionTransactionDetails;



/**
 * SubscriptionTransactionDetailsSearch represents the model behind the search form of `app\models\SubscriptionTransactionDetails`.
 */
class SubscriptionTransactionDetailsSearch extends SubscriptionTransactionDetails
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subscription_transaction_id', 'subscription_id', 'no_of_usages'], 'integer'],
            [['confirmation_number', 'payment_transaction_id', 'payment_status', 'payment_date', 'expire_date', 'create_date'], 'safe'],
            [['paid_amount', 'redemption_cost', 'subscription_cost'], 'number'],
        ];
        
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $id_employee = Yii::$app->user->identity->id_employee;
        $role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $result = Yii::$app->Common->get_super_subscription_details($id_employee);
        if(!empty($result)){
            $subscriber_id = $result['subscription_id'];
            $query = SubscriptionTransactionDetails::find();
            if($subscriber_id){
                $query = $query->where(['subscription_id'=> $subscriber_id])->orderby('subscription_transaction_id desc');
            }
            // add conditions that should always apply here
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort'=> ['defaultOrder' => ['subscription_transaction_id' => SORT_DESC]],
            ]);
            $this->load($params);
            if (!$this->validate()) {
                // uncomment the following line if you do not want to return any records when validation fails
                // $query->where('0=1');
                return $dataProvider;
            }
            if ( ! is_null($this->payment_date) && strpos($this->payment_date, '-') !== false ) { 
                $dates = explode(' - ', $this->payment_date);
                $date1 = date('Y-m-d', strtotime($dates[0])) . ' 00:00:00';
                $date2 = date('Y-m-d', strtotime($dates[1])) . ' 23:59:59';
                if($date1 == $date2){
                    $query->andFilterWhere(['=', 'payment_date', $date1]);
                }else if($date1 != $date2){
                    $query->andFilterWhere(['>=', 'payment_date', $date1]);
                    $query->andFilterWhere(['<=', 'payment_date', $date2]);
                }
            }

            // grid filtering conditions
            $query->andFilterWhere([
                'subscription_transaction_id' => $this->subscription_transaction_id,
                'subscription_id' => $this->subscription_id,
                'paid_amount' => $this->paid_amount,
                'redemption_cost' => $this->redemption_cost,
                'subscription_cost' => $this->subscription_cost,
                'no_of_usages' => $this->no_of_usages,
                //'payment_date' => $this->payment_date,
                'expire_date' => $this->expire_date,
                'create_date' => $this->create_date,
            ]);
            $query->andFilterWhere(['like', 'confirmation_number', $this->confirmation_number])
                ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
                ->andFilterWhere(['like', 'payment_status', $this->payment_status]);
                //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

            return $dataProvider;
        } else if(in_array($role_id,array(12,13,14,15))){
            $query = SubscriptionTransactionDetails::find();
            if(in_array($role_id,array(12,13,14,15))){
                $get_subscription_id = Yii::$app->db->createCommand("SELECT fk_subscription_id from tbl_subscription_user_mapping where fk_id_employee = ".$id_employee)->queryAll();
                $subscriber_id = array();
                if(!empty($get_subscription_id)){
                    foreach($get_subscription_id as $val){
                        $subscriber_id[] .= $val['fk_subscription_id'];
                    }
                }
                $query = $query->where(['IN','subscription_id',$subscriber_id]);
            }
            // add conditions that should always apply here
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort'=> ['defaultOrder' => ['subscription_transaction_id' => SORT_DESC]],
            ]);
            $this->load($params);
            if (!$this->validate()) {
                // uncomment the following line if you do not want to return any records when validation fails
                // $query->where('0=1');
                return $dataProvider;
            }
            // grid filtering conditions
            $query->andFilterWhere([
                'subscription_transaction_id' => $this->subscription_transaction_id,
                'subscription_id' => $this->subscription_id,
                'paid_amount' => $this->paid_amount,
                'redemption_cost' => $this->redemption_cost,
                'subscription_cost' => $this->subscription_cost,
                'no_of_usages' => $this->no_of_usages,
              //  'payment_date' => $this->payment_date,
                'expire_date' => $this->expire_date,
                'create_date' => $this->create_date,
            ]);

            if ( ! is_null($this->payment_date) && strpos($this->payment_date, '-') !== false ) { 
                $dates = explode(' - ', $this->payment_date);
                $date1 = date('Y-m-d', strtotime($dates[0])) . ' 00:00:00';
                $date2 = date('Y-m-d', strtotime($dates[1])) . ' 23:59:59';
                if($date1 == $date2){
                    $query->andFilterWhere(['=', 'payment_date', $date1]);
                }else if($date1 != $date2){
                    $query->andFilterWhere(['>=', 'payment_date', $date1]);
                    $query->andFilterWhere(['<=', 'payment_date', $date2]);
                }
            }

            $query->andFilterWhere(['like', 'confirmation_number', $this->confirmation_number])
                ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
                ->andFilterWhere(['like', 'payment_status', $this->payment_status]);

            return $dataProvider;
        } else if($role_id == 1){
            $query = SubscriptionTransactionDetails::find()->select("tbl_subscription_transaction_details.*")->join('RIGHT JOIN','tbl_super_subscription as ss','ss.subscription_id = tbl_subscription_transaction_details.subscription_id');
            if($subscriber_id){
                $query = $query->where(['subscription_id'=> $subscriber_id]);
            }

            // add conditions that should always apply here
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort'=> ['defaultOrder' => ['remaining_usages' => SORT_DESC,'subscription_transaction_id' => SORT_DESC]],
            ]);
            $this->load($params);
            if (!$this->validate()) {
                // uncomment the following line if you do not want to return any records when validation fails
                // $query->where('0=1');
                return $dataProvider;
            }
            // grid filtering conditions
            $query->andFilterWhere([
                'subscription_transaction_id' => $this->subscription_transaction_id,
                'subscription_id' => $this->subscription_id,
                'paid_amount' => $this->paid_amount,
                'redemption_cost' => $this->redemption_cost,
                'subscription_cost' => $this->subscription_cost,
                'no_of_usages' => $this->no_of_usages,
              //  'payment_date' => $this->payment_date,
                'expire_date' => $this->expire_date,
                'create_date' => $this->create_date,
            ]);
            if ( ! is_null($this->payment_date) && strpos($this->payment_date, '-') !== false ) { 
                $dates = explode(' - ', $this->payment_date);
                $date1 = date('Y-m-d', strtotime($dates[0])) .' 00:00:00';
                $date2 = date('Y-m-d', strtotime($dates[1])) .' 23:59:59';
                if($date1 == $date2){
                    $query->andFilterWhere(['=', 'payment_date', $date1]);
                }else if($date1 != $date2){
                    $query->andFilterWhere(['>=', 'payment_date', $date1]);
                    $query->andFilterWhere(['<=', 'payment_date', $date2]);
                }
            }
            $query->andFilterWhere(['like', 'confirmation_number', $this->confirmation_number])
                ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
                ->andFilterWhere(['like', 'payment_status', $this->payment_status]);
                
                //echo '<pre>'; print_r($query); //exit;
               




            return $dataProvider;
        } else {
            return false;
        }
    }
}
