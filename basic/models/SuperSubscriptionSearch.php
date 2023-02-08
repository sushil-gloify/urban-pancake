<?php

namespace app\models;
use Yii;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\SuperSubscription;

/**
 * SuperSubscriptionSearch represents the model behind the search form of `app\models\SuperSubscription`.
 */
class SuperSubscriptionSearch extends SuperSubscription
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subscription_id', 'subscription_pincode'], 'integer'],
            [['subscriber_name', 'register_address', 'subscription_area', 'primary_contact', 'secondary_contact', 'primary_email', 'secondary_email', 'subscriber_logo', 'subscriber_status', 'create_date'], 'safe'],
            [['subscription_GST'], 'number'],
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
        $query = SuperSubscription::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['subscription_id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'subscription_id' => $this->subscription_id,
            'subscription_pincode' => $this->subscription_pincode,
            'subscription_GST' => $this->subscription_GST,
            'create_date' => $this->create_date,
        ]);

        $query->andFilterWhere(['like', 'subscriber_name', $this->subscriber_name])
            ->andFilterWhere(['like', 'register_address', $this->register_address])
            ->andFilterWhere(['like', 'subscription_area', $this->subscription_area])
            ->andFilterWhere(['like', 'primary_contact', $this->primary_contact])
            ->andFilterWhere(['like', 'secondary_contact', $this->secondary_contact])
            ->andFilterWhere(['like', 'primary_email', $this->primary_email])
            ->andFilterWhere(['like', 'secondary_email', $this->secondary_email])
            ->andFilterWhere(['like', 'subscriber_logo', $this->subscriber_logo])
            ->andFilterWhere(['like', 'subscriber_status', $this->subscriber_status]);

        return $dataProvider;
    }
}
