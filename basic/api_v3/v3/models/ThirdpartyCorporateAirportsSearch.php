<?php

namespace app\api_v3\v3\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\api_v3\v3\models\ThirdpartyCorporateAirports;

/**
 * ThirdpartyCorporateAirportsSearch represents the model behind the search form about `app\api_v3\v3\models\ThirdpartyCorporateAirports`.
 */
class ThirdpartyCorporateAirportsSearch extends ThirdpartyCorporateAirports
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['thirdparty_corporate_airport_id', 'thirdparty_corporate_id', 'airport_id'], 'integer'],
            [['created_on', 'modified_on'], 'safe'],
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
    public function search($params, $id)
    {
        $query = ThirdpartyCorporateAirports::find()->where(['thirdparty_corporate_id' => $id]);

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
        $query->joinWith(['airport']);

        // grid filtering conditions
        $query->andFilterWhere([
            'thirdparty_corporate_airport_id' => $this->thirdparty_corporate_airport_id,
            'thirdparty_corporate_id' => $this->thirdparty_corporate_id,
            'airport_id' => $this->airport_id,
            'created_on' => $this->created_on,
            'modified_on' => $this->modified_on,
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
    public function corporatesearch($params, $id)
    {
        $query = ThirdpartyCorporateAirports::find()->where(['thirdparty_corporate_id' => $id]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['airport_id' =>SORT_ASC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $query->joinWith(['discountAirport']);

        // grid filtering conditions
        $query->andFilterWhere([
            'thirdparty_corporate_airport_id' => $this->thirdparty_corporate_airport_id,
            'thirdparty_corporate_id' => $this->thirdparty_corporate_id,
            'airport_id' => $this->airport_id,
            'created_on' => $this->created_on,
            'modified_on' => $this->modified_on,
        ]);

        return $dataProvider;
    }
}
