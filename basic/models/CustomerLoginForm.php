<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class CustomerLoginForm extends Model
{
    public $mobile_number;
    //public $password;
    //public $rememberMe = true;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['mobile_number'], 'required'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }

    public function customerDetail()
    {
        //$password=Yii::$app->getSecurity()->validatePassword($password, $this->password);
        if(!empty($_POST['id_customer'])){
            $airline_id = isset($_POST['airline_id']) ? $_POST['airline_id'] : "";
            if($airline_id){
                return Yii::$app->db->createCommand("SELECT c.id_customer,c.fk_role_id,c.name,c.document,c.customer_profile_picture,c.email,c.mobile,c.address_line_1,c.address_line_2,c.area,c.pincode,c.id_proof_verification,c.email_verification,c.mobile_number_verification,oc.client_id,oc.client_secret,c.fk_tbl_customer_id_country_code,c.acc_verification,c.status,c.tour_id,(SELECT customerId FROM tbl_corporate_employee_airline_mapping where fk_airline_id =5 and fk_corporate_employee_id = '".$_POST['id_customer']."') as customerId FROM tbl_customer c INNER JOIN oauth_clients oc ON oc.user_id = c.id_customer  WHERE c.mobile='".$_POST['mobile']."' AND c.fk_tbl_customer_id_country_code='".$_POST['id_country_code']."' and c.id_customer ='".$_POST['id_customer']."'")->queryOne();
            } else {
                return Yii::$app->db->createCommand("SELECT c.id_customer,c.fk_role_id,c.name,c.document,c.customer_profile_picture,c.email,c.mobile,c.address_line_1,c.address_line_2,c.area,c.pincode,c.id_proof_verification,c.email_verification,c.mobile_number_verification,oc.client_id,oc.client_secret,c.fk_tbl_customer_id_country_code,c.acc_verification,c.status,c.tour_id,'0' as customerId  FROM tbl_customer c INNER JOIN oauth_clients oc ON oc.user_id = c.id_customer  WHERE c.mobile='".$_POST['mobile']."' AND c.fk_tbl_customer_id_country_code='".$_POST['id_country_code']."' and c.id_customer ='".$_POST['id_customer']."'")->queryOne();
            }
            // return Yii::$app->db->createCommand("SELECT c.id_customer,c.fk_role_id,c.name,c.document,c.customer_profile_picture,c.email,c.mobile,c.address_line_1,c.address_line_2,c.area,c.pincode,c.id_proof_verification,c.email_verification,c.mobile_number_verification,oc.client_id,oc.client_secret,c.fk_tbl_customer_id_country_code,c.acc_verification,c.status,c.tour_id FROM tbl_customer c INNER JOIN oauth_clients oc ON oc.user_id = c.id_customer  WHERE c.mobile='".$_POST['mobile']."' AND c.fk_tbl_customer_id_country_code='".$_POST['id_country_code']."' and c.id_customer ='".$_POST['id_customer']."'")->queryOne();
        } else {
            return Yii::$app->db->createCommand("SELECT c.id_customer,c.fk_role_id,c.name,c.document,c.customer_profile_picture,c.email,c.mobile,c.address_line_1,c.address_line_2,c.area,c.pincode,c.id_proof_verification,c.email_verification,c.mobile_number_verification,oc.client_id,oc.client_secret,c.fk_tbl_customer_id_country_code,c.acc_verification,c.status,c.tour_id FROM tbl_customer c INNER JOIN oauth_clients oc ON oc.user_id = c.id_customer  WHERE c.mobile='".$_POST['mobile']."' AND c.fk_tbl_customer_id_country_code='".$_POST['id_country_code']."'")->queryOne();
        }
    }
}
