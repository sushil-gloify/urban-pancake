<?php

namespace app\api_v3\v3\models;

use app\models\Employee;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tbl_corporate_user".
 *
 * @property int $id_corporate_user
 * @property int $fk_tbl_employee_id
 * @property int $corporate_id
 * @property int $status 1 - Active, 2 - Inactive
 * @property string $created_on
 * @property string $modified_on
 */
class CorporateUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_corporate_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_tbl_employee_id', 'corporate_id', 'status'], 'required'],
            [['fk_tbl_employee_id', 'corporate_id', 'status'], 'integer'],
            [['created_on', 'modified_on'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_corporate_user' => 'Id Corporate User',
            'fk_tbl_employee_id' => 'Fk Tbl Employee ID',
            'corporate_id' => 'Corporate ID',
            'status' => 'Status',
            'created_on' => 'Created On',
            'modified_on' => 'Modified On',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorporateUserRelation()
    {
        return $this->hasOne(Employee::className(), ['id_employee' => 'fk_tbl_employee_id']);
    }

    public function getTokenName($id){
        $Tokens = Yii::$app->db->createCommand("SELECT tc.thirdparty_corporate_id as corporate_id,tc.corporate_name as corporate_name from tbl_thirdparty_corporate tc left join tbl_corporate_user cu ON cu.corporate_id = tc.thirdparty_corporate_id WHERE cu.fk_tbl_employee_id=$id")->queryAll();
        
        $accessToken = ArrayHelper::map($Tokens,'corporate_id','corporate_name');
        if($accessToken){
                $str=""; 
                foreach ($accessToken as $key => $value) {
                    $str .=$value."<br/>" ;
                }
                return $str;
        }else{
            return " - ";
        }
    }
}
