<?php

namespace app\api_v3\v3\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegion;

/**
 * ThirdpartyCorporateCityRegionSearch represents the model behind the search form about `app\api_v3\v3\models\ThirdpartyCorporateCityRegion`.
 */
class ThirdpartyCorporateCityRegionSearch extends ThirdpartyCorporateCityRegion
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['thirdparty_corporate_city_id', 'thirdparty_corporate_id', 'city_region_id'], 'integer'],
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
        $query = ThirdpartyCorporateCityRegion::find()->where(['thirdparty_corporate_id' => $id]);;

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
            'thirdparty_corporate_city_id' => $this->thirdparty_corporate_city_id,
            'thirdparty_corporate_id' => $this->thirdparty_corporate_id,
            'city_region_id' => $this->city_region_id,
            'created_on' => $this->created_on,
            'modified_on' => $this->modified_on,
        ]);

        return $dataProvider;
    }

    public function discountsearch($params, $id)
    {
        $query = ThirdpartyCorporateCityRegion::find()->where(['thirdparty_corporate_id' => $id]);;

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
        $query->joinWith('discountRegion');

        // grid filtering conditions
        $query->andFilterWhere([
            'thirdparty_corporate_city_id' => $this->thirdparty_corporate_city_id,
            'thirdparty_corporate_id' => $this->thirdparty_corporate_id,
            'city_region_id' => $this->city_region_id,
            'created_on' => $this->created_on,
            'modified_on' => $this->modified_on,
        ]);

        return $dataProvider;
    }
}
