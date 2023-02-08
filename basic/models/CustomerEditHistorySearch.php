<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CustomerEditHistory;

/**
 * CustomerEditHistorySearch represents the model behind the search form of `app\models\CustomerEditHistory`.
 */
class CustomerEditHistorySearch extends CustomerEditHistory
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_edit_id', 'customer_id', 'edit_by'], 'integer'],
            [['description', 'module_name', 'edit_by_name', 'edit_date'], 'safe'],
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
        $query = CustomerEditHistory::find();

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
            'customer_edit_id' => $this->customer_edit_id,
            'customer_id' => $this->customer_id,
            'edit_by' => $this->edit_by,
            'edit_date' => $this->edit_date,
        ]);

        $query->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'module_name', $this->module_name])
            ->andFilterWhere(['like', 'edit_by_name', $this->edit_by_name]);

        return $dataProvider;
    }
}
