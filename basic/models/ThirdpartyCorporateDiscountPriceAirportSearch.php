<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\ThirdpartyCorporateDiscountPriceAirport;

/**
 * ThirdpartyCorporateDiscountPriceAirportSearch represents the model behind the search form of `app\models\ThirdpartyCorporateDiscountPriceAirport`.
 */
class ThirdpartyCorporateDiscountPriceAirportSearch extends ThirdpartyCorporateDiscountPriceAirport
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'thirdparty_corporate_airport_id', 'status'], 'integer'],
            [['bag_price'], 'number'],
            [['created_on', 'modified_on'], 'safe'],
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
        $query = ThirdpartyCorporateDiscountPriceAirport::find();

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
            'id' => $this->id,
            'thirdparty_corporate_airport_id' => $this->thirdparty_corporate_airport_id,
            'bag_price' => $this->bag_price,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'modified_on' => $this->modified_on,
        ]);

        return $dataProvider;
    }
}
