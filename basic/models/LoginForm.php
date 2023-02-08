<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;
/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $forgotemail;
    public $rememberMe = true;
    public $password2;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
            ['forgotemail', 'isexist'],
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
        if(!empty($this->_user)){
            $this->checkaccesstoken($this->_user);
        }
        
        return $this->_user;
    } 

     public function isexist($attribute, $params)
    { 
        if($this->forgotemail != ''){
            $user = User::findByUsername($this->forgotemail); 
            if ($user) { 
                return $user;
            }else{
                $this->addError($attribute, 'Incorrect Email.');
            }
        }else{
            $this->addError($attribute, 'Email Cannot be Empty.');
        }
    }

    public function checkaccesstoken($data){
        $employee_detail= Yii::$app->db->createCommand("select oc.*  
        from oauth_clients oc
        where oc.employee_id='".$data['id_employee']."'")->queryOne();
       
        if(empty($employee_detail))
        {
            $client['client_id']=base64_encode($data['email'].$data['id_employee']);
            $client['client_secret']=Yii::$app->getSecurity()->generatePasswordHash($data['email']);
            $client['employee_id']=$data['id_employee'];
            User::addClient($client);
        }
    }
    public function getClients($id_employee){
        $employee_detail = array();
        $employee_detail= Yii::$app->db->createCommand("select oc.*  
        from oauth_clients oc
        where oc.employee_id='".$id_employee."'")->queryOne();
        return $employee_detail;
    }


    
}
