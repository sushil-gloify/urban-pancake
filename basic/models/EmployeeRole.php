<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tbl_employee_role".
 *
 * @property integer $id_employee_role
 * @property string $role
 * @property integer $status
 *
 * @property Employee[] $employees
 * @property OrderStatus[] $orderStatuses
 */
class EmployeeRole extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_employee_role';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['role'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_employee_role' => 'Id Employee Role',
            'role' => 'Role',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployees()
    {
        return $this->hasMany(Employee::className(), ['fk_tbl_employee_id_employee_role' => 'id_employee_role']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatuses()
    {
        return $this->hasMany(OrderStatus::className(), ['fk_tbl_order_status_id_employee_role' => 'id_employee_role']);
    }

    public function getEmployeeRoles()
    {
        $roleId = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        if($roleId != 17){
            $roles = EmployeeRole::find()->where(['<', 'id_employee_role', 12])->orwhere(['IN','id_employee_role',array(16,21)])->all();
        } else {
            $roles = EmployeeRole::find()->where(['id_employee_role' => 18])->all();
        }
        return $roles;
    }

    /**
     * @inheritdoc
     */
    public function getCorporateRoles($role_type)
    {
        if($role_type == 1){
            $roles = EmployeeRole::find()->where(['in', "id_employee_role", [12,13,15]])->all();//
            return $roles;
        } 
        // else if ($role_type == 13){
            // $roles = EmployeeRole::find()->where(['id_employee_role'=>14])->all();
            // return $roles;
        // } 
        else{
            $roles = EmployeeRole::find()->andFilterWhere(['>', 'id_employee_role',$role_type])->andFilterWhere(['<', 'id_employee_role',16])->all();
            return $roles;
        }
    }

      public function getRegion()
    {
        $region=CityOfOperation::find()
            ->all();
            return $region;
    }
    public function getRegionKiosk()
    {
        $region=CityOfOperation::find()->where('region_id = 2')
            ->all();
            return $region;
    }
        public function getAirport($id)
    {
        $airport=AirportOfOperation::find()->where(['fk_tbl_city_of_operation_region_id'=>$id])
            ->all();
            return $airport;
    }

     public function getAirportCorporate()
    {
        $airport=AirportOfOperation::find()
            ->all();
            return $airport;
    }
    public function getAirportCorporateKiosk()
    {
        $airport=AirportOfOperation::find()->where('fk_tbl_city_of_operation_region_id = 2')
            ->all();
            return $airport;
    }
}
