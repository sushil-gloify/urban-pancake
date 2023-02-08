<?php
namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TblExport;

/**
 * EmployeeSearch represents the model behind the search form about `app\models\Employee`.
 */
class TableexportSearch extends TblExport
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idorderexport', 'start_date', 'end_date', 'path'],'safe'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['path'], 'string', 'max' => 255],
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
    public function search($params ,$emp_id)
    {
        $query = TblExport::find()->where(['=', 'id_employee', $emp_id]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['idorderexport' => SORT_DESC
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
            'idorderexport' => $this->idorderexport,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

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
