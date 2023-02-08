<?php

namespace app\models;

use Yii;
use app\api_v3\v3\models\ThirdpartyCorporateAirport;
/**
 * This is the model class for table "tbl_thirdparty_corporate_discount_price_airport".
 *
 * @property int $id
 * @property int $thirdparty_corporate_airport_id fk_from_tbl_thirdparty_corporate_airports
 * @property float $bag_price
 * @property int $status 1=enable,0=disable
 * @property string $created_on
 * @property string $modified_on
 */
class ThirdpartyCorporateDiscountPriceAirport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_thirdparty_corporate_discount_price_airport';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['thirdparty_corporate_airport_id', 'bag_price', 'status', 'created_on'], 'required'],
            [['thirdparty_corporate_airport_id', 'status'], 'integer'],
            [['bag_price'], 'number'],
            [['created_on', 'modified_on'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'thirdparty_corporate_airport_id' => 'Thirdparty Corporate Airport ID',
            'bag_price' => 'Bag Price',
            'status' => 'Status',
            'created_on' => 'Created On',
            'modified_on' => 'Modified On',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThirdpartyCorporateDiscountAirport()
    {
        return $this->hasOne(ThirdpartyCorporateAirports::className(), ['thirdparty_corporate_airport_id' => 'thirdparty_corporate_airport_id']);
    }
}
