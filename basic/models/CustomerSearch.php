<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Customer;
use app\models\CorporateEmployeeAirlineMapping;

/**
 * CustomerSearch represents the model behind the search form about `app\models\Customer`.
 */
class CustomerSearch extends Customer
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_customer', 'mobile_number_verification', 'email_verification', 'status','fk_role_id'], 'integer'],
            [['customerId','name', 'mobile', 'email', 'document', 'date_of_birth', 'date_created','id_proof_verification','tour_id'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
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
        $query = Customer::find();
        $query->orderBy('id_customer DESC');

        if($params['r'] == "customer/corporate-employee"){
            $query->where(['fk_role_id' => 19]);
            $query->orderBy('id_customer DESC');
        }

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(100*($params['page']-1));
                $query->limit(100);
            }
        } else {
            $query->offset(0);
            $query->limit(100);
        }
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'update_date' => SORT_DESC,
                    // 'update_status' => SORT_DESC,
                    'date_created' => SORT_DESC
                ]
            ],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_customer' => $this->id_customer,
            'mobile_number_verification' => $this->mobile_number_verification,
            'email_verification' => $this->email_verification,
            'status' => $this->status,
            'id_proof_verification' => $this->id_proof_verification,
            'date_of_birth' => $this->date_of_birth,
            'date_created' => $this->date_created,
        ]);
        if(!empty($this->fk_role_id) && ($this->fk_role_id == 19)){
            $query->andWhere(['fk_role_id' => $this->fk_role_id]);
            $query->orderBy('id_customer DESC');
        } else if(!empty($this->fk_role_id) && ($this->fk_role_id != 19)){
            $query->andWhere(['!=', 'fk_role_id', '19']);
            $query->orderBy('id_customer DESC');
        }

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'mobile', $this->mobile])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'tour_id', $this->tour_id])
            ->andFilterWhere(['like', 'document', $this->document])
            ->andFilterWhere(['like', 'customerId', $this->customerId]);

      //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

        return $dataProvider;
    }

    public function corporateSearch($params)
    {
        $id_meployee = Yii::$app->user->identity->id_employee;
        $airlineArray = array();
        $airlineIds = "";
        $airlineId = Yii::$app->db->createCommand("SELECT tc.airline_id FROM tbl_corporate_user cu left JOIN tbl_thirdparty_corporate tc on tc.thirdparty_corporate_id = cu.corporate_id where cu.fk_tbl_employee_id = '".$id_meployee."'")->queryAll();
        
        foreach($airlineId as $value){
            array_push($airlineArray,$value['airline_id']);
            $airlineIds .= $value['airline_id'].",";
        }

        if(!empty($airlineArray)){
            $customerArr = array();
            $customer_ids = Yii::$app->db->createCommand("SELECT fk_corporate_employee_id FROM tbl_corporate_employee_airline_mapping where fk_airline_id IN (".rtrim($airlineIds,',').")")->queryAll();

            if(!empty($customer_ids)){
                foreach($customer_ids as $val){
                    array_push($customerArr,$val['fk_corporate_employee_id']);
                }
            }

        }
        // if(!empty($customerArr)){
            $query = Customer::find();
            $query->where(['fk_role_id' => 19]);
            $query->where(['in','id_customer',$customerArr]);
            $query->orderBy('id_customer DESC');

            if (isset($params['page'])) {
                if (!empty($params['page'])) {
                    $query->offset(100*($params['page']-1));
                    $query->limit(100);
                }
            } else {
                $query->offset(0);
                $query->limit(100);
            }
            // add conditions that should always apply here

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'sort' => [
                    'defaultOrder' => [
                        'update_date' => SORT_DESC,
                        // 'update_status' => SORT_DESC,
                        'date_created' => SORT_DESC
                    ]
                ],
            ]);

            $this->load($params);
            if (!$this->validate()) {
                // uncomment the following line if you do not want to return any records when validation fails
                // $query->where('0=1');
                return $dataProvider;
            }

            // grid filtering conditions
            $query->andFilterWhere([
                'id_customer' => $this->id_customer,
                'mobile_number_verification' => $this->mobile_number_verification,
                'email_verification' => $this->email_verification,
                'status' => $this->status,
                'id_proof_verification' => $this->id_proof_verification,
                'date_of_birth' => $this->date_of_birth,
                'date_created' => $this->date_created,
                
                // 'tour_id' => $this->tour_id,
            ]);

            $query->andFilterWhere(['like', 'name', $this->name])
                ->andFilterWhere(['like', 'mobile', $this->mobile])
                ->andFilterWhere(['like', 'email', $this->email])
                ->andFilterWhere(['like', 'tour_id', $this->tour_id])
                ->andFilterWhere(['like', 'document', $this->document])
                ->andFilterWhere(['like', 'customerId', $this->customerId]);
       
            return $dataProvider;
       
    }
}
