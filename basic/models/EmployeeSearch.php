<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Employee;

/**
 * EmployeeSearch represents the model behind the search form about `app\models\Employee`.
 */
class EmployeeSearch extends Employee
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_employee', 'fk_tbl_employee_id_employee_role', 'mobile_number_verification', 'status'], 'integer'],
            [['name', 'employee_profile_picture', 'mobile', 'email', 'password', 'adhar_card_number', 'document_id_proof', 'date_created', 'date_modified'], 'safe'],
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
        $query = Employee::find()->where(['not in','fk_tbl_employee_id_employee_role',[8,12,13,14,15]]);

        $id_employee = Yii::$app->user->identity->id_employee;
        $result = Yii::$app->Common->get_super_subscription_details($id_employee);

        if(!empty($result)){
            $query = $query->orWhere(['fk_tbl_employee_id_employee_role' => '18'])->andWhere(['subscription_id' => $result->subscription_id]);
        }
        // add conditions that should always apply here
        // print_r($query);exit;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id_employee' => SORT_DESC
                ]
            ],
            // 'pagination' => [
            // 'route' => '...',
            // 'params' => $params
            // ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_employee' => $this->id_employee,
            'fk_tbl_employee_id_employee_role' => $this->fk_tbl_employee_id_employee_role,
            'mobile_number_verification' => $this->mobile_number_verification,
            'status' => $this->status,
            'date_created' => $this->date_created,
            'date_modified' => $this->date_modified,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'employee_profile_picture', $this->employee_profile_picture])
            ->andFilterWhere(['like', 'mobile', $this->mobile])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'adhar_card_number', $this->adhar_card_number])
            ->andFilterWhere(['like', 'document_id_proof', $this->document_id_proof]);

        return $dataProvider;
    }

    public function searchEmployee($params)
    {
       
        $result = Yii::$app->Common->get_super_subscription_details(Yii::$app->user->identity->id_employee);
        
        $query = Employee::find()->join('RIGHT JOIN','tbl_employee_allocation as ea','ea.employee_id = tbl_employee.id_employee')->join('LEFT JOIN','tbl_subscription_transaction_details as std','std.subscription_transaction_id = ea.subscription_transaction_id');
       
        if(!empty($result)){
            $query = $query->orWhere(['fk_tbl_employee_id_employee_role' => '18'])->andWhere(['subscription_id' => $result->subscription_id]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id_employee' => SORT_DESC
                ]
            ],

            // 'pagination' => [
            // 'route' => '...',
            // 'params' => $params
            // ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        // $query->joinWith(['relationEmpAllocation','right']);

        // grid filtering conditions
        $query->where(['std.subscription_id'=>$result->subscription_id]);
        $query->andFilterWhere([
            'id_employee' => $this->id_employee,
            'fk_tbl_employee_id_employee_role' => $this->fk_tbl_employee_id_employee_role,
            'mobile_number_verification' => $this->mobile_number_verification,
            'status' => $this->status,
            'date_created' => $this->date_created,
            'date_modified' => $this->date_modified,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'employee_profile_picture', $this->employee_profile_picture])
            ->andFilterWhere(['like', 'mobile', $this->mobile])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'adhar_card_number', $this->adhar_card_number])
            ->andFilterWhere(['like', 'document_id_proof', $this->document_id_proof]);
        $query->groupBy(['id_employee']);

        //echo $query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql;die;
        return $dataProvider;
    }
    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function corporateusersearch($params, $employee_id)
    {
        $employee_role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        if($employee_id == 1 || $employee_role == 9){
            $query = Employee::find()->where(['>', 'fk_tbl_employee_id_employee_role', 11]);
            
        }else{
            $query = Employee::find()->where(['created_by' => $employee_id]);
        }
        
        $query->joinWith('corporateUserRelation'); 
        // $query->andFilterWhere([
        //     'in','corporate_id',$this->corporate_id
        // ]);
    
        // add conditions that should always apply here
        // print_r($query);exit;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id_employee' => SORT_DESC
                ]
            ],
            // 'pagination' => [
            // 'route' => '...',
            // 'params' => $params
            // ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_employee' => $this->id_employee,
            'fk_tbl_employee_id_employee_role' => $this->fk_tbl_employee_id_employee_role,
            'mobile_number_verification' => $this->mobile_number_verification,
            'status' => $this->status,
            'date_created' => $this->date_created,
            'date_modified' => $this->date_modified,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'employee_profile_picture', $this->employee_profile_picture])
            ->andFilterWhere(['like', 'mobile', $this->mobile])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'adhar_card_number', $this->adhar_card_number])
            ->andFilterWhere(['like', 'document_id_proof', $this->document_id_proof])
            ->andFilterWhere(['in','corporate_id',$this->corporate_id]);
        $query->groupBy(['id_employee']);
        $query->orderBy(['id_employee'=>SORT_DESC]);
        return $dataProvider;
    }

    public function searchcorporate($params)
    {
        $query = Employee::find()->where(['fk_tbl_employee_id_employee_role'=>8]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_employee' => $this->id_employee,
            'fk_tbl_employee_id_employee_role' => $this->fk_tbl_employee_id_employee_role,
            'mobile_number_verification' => $this->mobile_number_verification,
            'status' => $this->status,
            'date_created' => $this->date_created,
            'date_modified' => $this->date_modified,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'employee_profile_picture', $this->employee_profile_picture])
            ->andFilterWhere(['like', 'mobile', $this->mobile])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'adhar_card_number', $this->adhar_card_number])
            ->andFilterWhere(['like', 'document_id_proof', $this->document_id_proof]);

        return $dataProvider;
    }
}
