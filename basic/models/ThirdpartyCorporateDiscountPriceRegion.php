<?php

namespace app\models;

use Yii;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegion;
/**
 * This is the model class for table "tbl_thirdparty_corporate_discount_price_region".
 *
 * @property int $id
 * @property int $thirdparty_corporate_region_id
 * @property float $bag_price
 * @property int $status 1=enable,0=disable
 * @property string $created_on
 * @property string|null $modified_on
 */
class ThirdpartyCorporateDiscountPriceRegion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_thirdparty_corporate_discount_price_region';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['thirdparty_corporate_region_id', 'bag_price'], 'required'],
            [['thirdparty_corporate_region_id', 'status'], 'integer'],
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
            'thirdparty_corporate_region_id' => 'Thirdparty Corporate Region ID',
            'bag_price' => 'Bag Price',
            'status' => 'Status',
            'created_on' => 'Created On',
            'modified_on' => 'Modified On',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThirdpartyCorporateDiscountCity()
    {
        return $this->hasOne(ThirdpartyCorporateCityRegion::className(), ['thirdparty_corporate_city_id' => 'thirdparty_corporate_region_id']);
    }
}
