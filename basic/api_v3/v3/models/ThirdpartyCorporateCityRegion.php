<?php

namespace app\api_v3\v3\models;

use Yii;
use app\models\ThirdpartyCorporate;
use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceCity;
use app\models\CityOfOperation;
use app\models\ThirdpartyCorporateDiscountPriceRegion;
/**
 * This is the model class for table "tbl_thirdparty_corporate_city_region".
 *
 * @property integer $thirdparty_corporate_city_id
 * @property integer $thirdparty_corporate_id
 * @property integer $city_region_id
 * @property string $created_on
 * @property string $modified_on
 */
class ThirdpartyCorporateCityRegion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_thirdparty_corporate_city_region';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['thirdparty_corporate_id', 'city_region_id', 'created_on'], 'required'],
            [['thirdparty_corporate_id', 'city_region_id'], 'integer'],
            [['created_on', 'modified_on'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'thirdparty_corporate_city_id' => 'Thirdparty Corporate City ID',
            'thirdparty_corporate_id' => 'Thirdparty Corporate ID',
            'city_region_id' => 'City Region ID',
            'created_on' => 'Created On',
            'modified_on' => 'Modified On',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAirport()
    {
        return $this->hasOne(ThirdpartyCorporateLuggagePriceCity::className(), ['thirdparty_corporate_city_id' => 'thirdparty_corporate_city_id']);
    }

    public function getThirdpartyCorporateName($id){
        $c_name = ThirdpartyCorporate::findOne($id);
        return $c_name['corporate_name'];
    }

    public function getCityName($id) {
        $city_name = CityOfOperation::findOne($id);
        return isset($city_name['region_name']) ? $city_name['region_name'] : "";
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDiscountRegion()
    {
        return $this->hasOne(ThirdpartyCorporateDiscountPriceRegion::className(), ['thirdparty_corporate_region_id' => 'thirdparty_corporate_city_id']);
    }
}
