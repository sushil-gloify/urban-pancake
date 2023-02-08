<?php

namespace app\api_v3\v3\models;

use Yii;
use app\models\AirportOfOperation;
use app\models\ThirdpartyCorporate;

use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceAirport;
use app\models\ThirdpartyCorporateDiscountPriceAirport;

/**
 * This is the model class for table "tbl_thirdparty_corporate_airports".
 *
 * @property integer $thirdparty_corporate_airport_id
 * @property integer $thirdparty_corporate_id
 * @property integer $airport_id
 * @property string $created_on
 * @property string $modified_on
 */
class ThirdpartyCorporateAirports extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_thirdparty_corporate_airports';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['thirdparty_corporate_id', 'airport_id', 'created_on'], 'required'],
            [['thirdparty_corporate_id', 'airport_id'], 'integer'],
            [['created_on', 'modified_on'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'thirdparty_corporate_airport_id' => 'Thirdparty Corporate Airport ID',
            'thirdparty_corporate_id' => 'Thirdparty Corporate ID',
            'airport_id' => 'Airport ID',
            'created_on' => 'Created On',
            'modified_on' => 'Modified On',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAirport()
    {
        return $this->hasOne(ThirdpartyCorporateLuggagePriceAirport::className(), ['thirdparty_corporate_airport_id' => 'thirdparty_corporate_airport_id']);
    }

    public function getAirportName($id) {
        $airportName = AirportOfOperation::findOne($id);
        return $airportName['airport_name'];
    }

    public function getThirdpartyCorporateName($id){
        $c_name = ThirdpartyCorporate::findOne($id);
        return $c_name['corporate_name'];
    }

    public function getDiscountAirport()
    {
        return $this->hasOne(ThirdpartyCorporateDiscountPriceAirport::className(), ['thirdparty_corporate_airport_id' => 'thirdparty_corporate_airport_id']);
    }
}
