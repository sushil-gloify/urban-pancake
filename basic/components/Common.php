<?php

namespace app\components;

use Yii;
use yii\helpers\Html;
use yii\base\Component;
use app\api_v3\v3\models\FinserveTransactionDetails;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use Razorpay\Api\Api;
use yii\db\Query;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use app\models\Employee;
use app\models\User;
use app\models\Order;
use app\models\LuggageType;
use app\models\CorporateCategory;
use app\models\CityOfOperation;
use app\models\ThirdpartyCorporate;
use app\models\CorporateDetails;
use app\models\AirportOfOperation;
use app\models\OrderPaymentDetails;
use app\api_v3\v3\models\OrderEditHistory;
use app\api_v3\v3\models\CorporateUser;
use app\api_v3\v3\models\CorporateEmployeeAirport;
use app\api_v3\v3\models\CorporateEmployeeRegion;
use app\models\PickDropLocation;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use kartik\mpdf\Pdf;
use app\models\OrderModificationDetails;
use app\models\SuperSubscription;
use app\models\SubscriptionPaymentLinkDetails;
use app\models\SubscriptionTokenMap;
use app\models\SubscriptionTransactionDetails;
use app\models\EmployeeAllocation;
use app\models\TempFinserveTransactionDetailsSearch;
use app\models\TempFinserveTransactionDetails;
use app\models\Customer;
use app\models\CustomerEditHistory;
date_default_timezone_set("Asia/Kolkata");
include(Yii::$app->basePath.'/vendor/kartik-v/mpdf/mpdf.php');

class Common extends Component {

    /**
     * To get Mysql Date Time format
     */
    public static function mysqlDate($date = FALSE) {
        if ($date) {
            return date('Y-m-d', strtotime($date));
        } else {
            return date('Y-m-d');
        }
    }
  
  /**
     * To get default Date
     */
    public function displayDate1($date = False) {
        if ($date) {
            return date('d/m/Y', strtotime($date));
        } 
        return date('d/m/Y');
    }

    /**
     * To get Mysql Date Time format
     */
    public function displayDateTime1($dateTime) {
        if ($dateTime) {
            return date('d-m-Y h:i:s a', strtotime($dateTime));
        }else{
            return '';
        }
    }
    /**
     * To get Mysql Date Time format
     */
    public function display12HrsTime($dateTime) {
        if ($dateTime) {
            return date('d-M-Y h:i:s a', strtotime($dateTime));
        }else{
            return '';
        }
    }
    

    /**
     * To get Mysql Date Time format
     */
    public function customDate($date) {
        if ($date) {
            return date('d-m-Y', strtotime($date));
        } else {
            return '';
        }
    }

    /**
     * To get default Date
     */
    public function displayDate2($date) {
        if ($date == '0000-00-00') {
            return '';
        }

        return date('d-m-Y', strtotime($date));
    }

    /**
     * To get default Date
     */
    public function displayDate4($date) {
        if ($date == '0000-00-00') {
                return '';
        }
        
        //return date('d-m-Y H:i:s', strtotime($date));
        return date('d-M-Y', strtotime($date));
    }
 
    public  function genarateOrderConfirmationPdf($order_details, $template_name,$type=NULL)
    { 
        ob_start();
        $path = ($type == "order_modification") ? (Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_modification_pdf_path']) : (Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path']);

        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details));

        $data = ob_get_clean();

        try
        {
            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=0,$default_font='',$mgl=0,$mgr=0,$mgt=8,$mgb=8,$mgh=9,$mgf=0, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;

            $html2pdf->writeHTML($data);

            /*this footer will be added into the last of the page , if you want to display in all of the pages then cut this footer
            line and paster above the $html2pdf->writeHTML($data);,then footer will render in all pages*/

            $html2pdf->SetFooter('<div style="width:100%;padding: 16px; text-align: center;background: #2955a7;color: white;font-size: 15px;position: absolute;bottom: 0px;font-style:normal;font-weight:200;">Luggage Transfer Simplified</div>');

            $html2pdf->Output($path."order_".$order_details['order_details']['order']['order_number'].".pdf",'F');

            /*Preparing file path and folder path for response */
            if($type == "order_modification"){
                $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_modification_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
                $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_modification_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
            } else {
                $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
                $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
            }
            //print_r($path);exit;
            return $order_pdf;

        }
        catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    public  function genarateOrderConfirmationThirdpartyCorporatePdf($order_details, $template_name)
    { 
        ob_start();
        $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];

        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details));

        $data = ob_get_clean();

        try
        {
            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=0,$default_font='',$mgl=0,$mgr=0,$mgt=8,$mgb=8,$mgh=9,$mgf=0, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;

            $html2pdf->writeHTML($data);

            /*this footer will be added into the last of the page , if you want to display in all of the pages then cut this footer
            line and paster above the $html2pdf->writeHTML($data);,then footer will render in all pages*/

            $html2pdf->SetFooter('<div style="width:100%;padding: 16px; text-align: center;background: #2955a7;color: white;font-size: 15px;position: absolute;bottom: 0px;font-style:normal;font-weight:200;">Luggage Transfer Simplified</div>');

            $html2pdf->Output($path."order_".$order_details['order_details']['order']['order_number'].".pdf",'F');

            /*Preparing file path and folder path for response */
            $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
            $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
            //print_r($path);exit;
            return $order_pdf;

        }
        catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    public function calculate_insurance($luggage_type) {

        if($luggage_type == 1 || $luggage_type == 2 || $luggage_type == 3 || $luggage_type == 5 || $luggage_type == 6){
            $insurance_price = 4;
        }
        elseif($luggage_type == 4) {
            $insurance_price = 8;
        }
        else {
            $insurance_price = 0;
        }

        return $insurance_price;
    }
     
    
    /**
     * To get default Date
     */
    public function displayDateWithTime($date) {
        if ($date == '0000-00-00 00:00:00') {
            return '';
        }
        //return date('d-m-Y H:i:s', strtotime($date));
        return date('d-M-Y h:i:s', strtotime($date));
    }

    /**
     * To get default Date
     */
    public function displayDate3($date = False) {
        if ($date) {
            return date('d M Y', strtotime($date));
        }
        return FALSE;
    }

    /**
     * To get Mysql Date Time format
     */
    public function mysqlDateTime($date = FALSE) {
        if ($date) {
            return date('Y-m-d H:i:s', strtotime($date));
        }
        return date('Y-m-d H:i:s');
    }
    /**
     * @Method        : Dynamic Years
     * @Params        :
     * @author        : Infant Kishore K
     * @created       : 8/3/2016
     * @Modified by   :
     * @modified      :
     * @Comment       : Dynamic Years
     */
    public static function getYearsArray()
    {
        for($yearNum = Yii::$app->params['YearFrom']; $yearNum <= Yii::$app->params['YearTo']; $yearNum++){
            $years[$yearNum] = $yearNum;
        }
 
        return $years;
    }

    /**
     * To get Mysql Date Time format
     */
    public function redirectUrl($role_id = FALSE) {
        if ($role_id) {
          if(in_array($role_id, [10])){
            $urlPage = '/employee/corporate-dashboards';
          }elseif(in_array($role_id, [11])){
            $urlPage = 'employee/corporate-dashboards'; 
          }elseif(in_array($role_id, [12])){
            $urlPage = '/employee/corporate-dashboards';
          }elseif(in_array($role_id, [13])){
            $urlPage = '/employee/corporate-dashboards';
          }elseif(in_array($role_id, [14])){
            $urlPage = '/employee/corporate-dashboards';
          }elseif(in_array($role_id, [15])){
            $urlPage = '/employee/corporate-dashboards';
          }elseif(in_array($role_id, [9])){
            $urlPage = '/employee/sales-dashboard';
          }elseif(in_array($role_id, [8])){
            $urlPage =  '/employee/corporate-dashboard';
          }elseif(in_array($role_id, [17])){
            $urlPage = '/super-subscription/super-subscriber-dashboard';
          }
          else{
            $urlPage = '';
          }
          return $urlPage;
        }else{
            return '';
        }
    }


    /**
     * To get Mysql Date Time format
     */
    public function dashboardTitle($role_id = FALSE) {
        if ($role_id) {
          if(in_array($role_id, [10])){
            $title = 'Welcome To Kiosk Dashboard';
          }elseif(in_array($role_id, [11])){
            $title = 'Welcome To Corporate Kiosk Dashboard'; 
          }elseif(in_array($role_id, [12])){
            $title = 'Welcome To Corporate Super Admin Dashboard';
          }elseif(in_array($role_id, [13])){
            $title = 'Welcome To Corporate Admin Dashboard';
          }elseif(in_array($role_id, [14])){
            $title = 'Welcome To Corporate Kiosk Dashboard';
          }elseif(in_array($role_id, [15])){
            $title = 'Welcome To Corporate Customer care Dashboard';
          }elseif(in_array($role_id, [17])){
            $title = 'Welcome To Super Subscriber Dashboard';
          }
          return $title;
        }else{
            return '';
        }
    }


    /**
     * To get sevice type
     */
    public function getServiceType($id_order){
       $order_details = Order::find()->where(['id_order' => $id_order])->one();
       if($order_details){
          if($order_details->order_transfer == 1){
            if($order_details->service_type == 1){
              return 'To City';
            }else{
              return 'From City';
            }
          }else{
            if($order_details->service_type == 1){
              return 'To Airport';
            }else{
              return 'From Airport';
            }
          }
       }

    }

    public function selectedSlot($id_slot, $order_date, $delivery_type)
    {
        if(!empty($id_slot) && !empty($order_date)){
          if($delivery_type == 1){
            if($id_slot == 1)
            {
                $response['delivery_time'] = '13:00';
                $response['delivery_date'] = $order_date;
            }
            if($id_slot == 2)
            {
                $response['delivery_time'] = '17:00';
                $response['delivery_date'] = $order_date;
            }
            if($id_slot == 3)
            {
                $response['delivery_time'] = '21:00';
                $response['delivery_date'] = $order_date;
            }
            if($id_slot ==4)
            {
                $response['delivery_time'] = '23:55';
                $response['delivery_date'] = $order_date;
            }
            if($id_slot ==5)
            {
                $response['delivery_time'] = '14:00';
                $response['delivery_date'] = $order_date; //date change
            }
            if($id_slot ==7)
            {
                $response['delivery_time'] = '02:00';
                $response['delivery_date'] = $order_date; //date change
            }
            if($id_slot ==9)
            {
                $response['delivery_time'] = '10:00';
                $response['delivery_date'] = $order_date; //date change
            }
          }else{
            
            $delivery_date = date('Y-m-d', strtotime("+3 days", strtotime(date($order_date))));

            if($id_slot ==1)
            {
                $response['delivery_time'] = '13:00';
                $response['delivery_date'] = $delivery_date;
            }
            if($id_slot ==2)
            {
                $response['delivery_time'] = '17:00';
                $response['delivery_date'] = $delivery_date;
            }
            if($id_slot ==3)
            {
                $response['delivery_time'] = '21:00';
                $response['delivery_date'] = $delivery_date;
            }
            if($id_slot ==4)
            {
                $response['delivery_time'] = '23:55';
                $response['delivery_date'] = $delivery_date;
            }
            if($id_slot ==5)
            {
                $response['delivery_time'] = '14:00';
                $response['delivery_date'] = $delivery_date; //date change
            }
            if($id_slot ==7)
            {
                $response['delivery_time'] = '02:00';
                $response['delivery_date'] = $delivery_date; //date change
            }
            if($id_slot ==9)
            {
                $response['delivery_time'] = '10:00';
                $response['delivery_date'] = $delivery_date; //date change
            }
          }
          return $response;
        }

    }


    /**
    * To save order edited by history
    */
    public function ordereditHistory($data){
        if($data){
          $updateHistory = new OrderEditHistory();
          $updateHistory->fk_tbl_order_id = $data['order_id'];
          $updateHistory->description = $data['description'];
          $updateHistory->edited_by_employee_id = $data['employee_id'];
          $updateHistory->module_name = $data['module_name'];
          $updateHistory->edited_by_employee_name = $data['employee_name'];
          $updateHistory->created_on = date('Y-m-d H:i:s');

          $updateHistory->save();

          return true;
        }else{
          return false;
        }
    }

    /**
    * To get order edited history
    */
    public function getOrderHistoryDetaails($module_name, $id_order){
        $order_edit_details = OrderEditHistory::find()->where(['module_name' => $module_name, 'fk_tbl_order_id' => $id_order])->orderBy(['id_order_edit' => SORT_DESC])->limit(1)->one();
        if($order_edit_details){
          return $order_edit_details;
        }else{
          return '';
        }
    }

    /**
     * To get corporate ID
     */
    public function getCorporateId($employeeId = FALSE) {
        if ($employeeId) {
            $corporate_details = CorporateUser::find()->where(['fk_tbl_employee_id' => $employeeId])->one();
            return ($corporate_details) ? $corporate_details->corporate_id : '';
        }else{
            return '';
        }
    }

    /**
     * To get corporate ID
     */
    public function getAirportIds($employeeId = FALSE) {
        if ($employeeId) {
            $empairport= CorporateEmployeeAirport::find()->where(['fk_tbl_employee_id'=>$employeeId])->all();
            $empairport=ArrayHelper::map($empairport,'id_employee_airport','fk_tbl_airport_id');

            return ($empairport) ? $empairport : '';
        }else{
            return '';
        }
    }

    /**
     * To get corporate ID
     */
    public function getRegionIds($employeeId = FALSE) {
        if ($employeeId) {
            $empregion= CorporateEmployeeRegion::find()->where(['fk_tbl_employee_id'=>$employeeId])->all();
            $empregion=ArrayHelper::map($empregion,'id_employee_region','fk_tbl_region_id');

            return ($empregion) ? $empregion : '';
        }else{
            return '';
        }
    }

    /**
     * To get corporate region
     */
    public function getCorporateRegion($regionId = FALSE) {
        if ($regionId) {
            $region=CityOfOperation::find()->where(['IN', 'region_id', $regionId])->all();

            return ($region) ? $region : '';
        } else if(empty($regionId)){
            $region=CityOfOperation::find()->all();

            return ($region) ? $region : '';
        }else{
            return '';
        }
    }

    /**
     * To create razorpay link
     */
    public function createRazorpayLink($travel_email, $customer_number, $luggage_price, $id_order, $role_id){
        // $api = new Api('rzp_test_VSvN3uILIxekzY', 'Flj35MJPZTJZ0WiTBlynY14k'); 
        // $api = new Api('rzp_live_LthnWTU5SuA0Hg', 'I0gtgdsboSlhf3ptUiUp5uef');
        $new_order_details = Order::getorderdetails($id_order);
        $api = new Api(Yii::$app->params['razorpay_api_key'],Yii::$app->params['razorpay_secret_key']);

        $time = strtotime('now');
        $endTime = strtotime(date("H:i", strtotime('+480 minutes', $time)));
        $post_amount = round($luggage_price) * 100;
        $string_amount = "$post_amount";
        $amount_payment_link = str_replace(".", "", $string_amount);
        if(($new_order_details['order']['corporate_type'] == 3) || ($new_order_details['order']['corporate_type'] == 4) || ($new_order_details['order']['corporate_type'] == 5)){
            $res = "";//$this->getSetBank($new_order_details['order']['corporate_id']);
            if(empty($res)){
                $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $customer_number)));
            } else {
                $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $customer_number),'reminder_enable'=>true, "options" => $res['options']));
            }
        } else {
            $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for corporate kiosk orders', 'amount' => $amount_payment_link,'currency' => 'INR', 'customer' => array('email' => $travel_email, 'contact' => $customer_number)));
        }

        $expire_date = date("Y-m-d h:i:s",$payment_link_details['expire_by']); 
        $amount = number_format($payment_link_details->amount_paid / 100, 2);

        $finserve_payment_details = new FinserveTransactionDetails();
        $finserve_payment_details->invoice_id = $payment_link_details->id;
        $finserve_payment_details->customer_id = $payment_link_details->customer_id;
        $finserve_payment_details->order_id = $id_order;
        $finserve_payment_details->payment_id = $payment_link_details->payment_id;

        $finserve_payment_details->transaction_status = $payment_link_details->status;
        $finserve_payment_details->expiry_date = $expire_date;
        $finserve_payment_details->paid_date  = $payment_link_details->paid_at;
        $finserve_payment_details->amount_paid = $amount;
        $finserve_payment_details->total_order_amount = $amount;

        $finserve_payment_details->payment_type_status  = 1;
        $finserve_payment_details->short_url = $payment_link_details->short_url;

        $finserve_payment_details->description = $payment_link_details->description;
        $finserve_payment_details->order_type  = 'create-corporate-kiosk';
        //$finserve_payment_details->notes = $payment_link_details->id;
        $finserve_payment_details->created_by = $role_id;
        if($finserve_payment_details->save()){
            $finserve_payment_details->finserve_number = 'FP'.date('mdYHis').$finserve_payment_details->id_finserve;
            $finserve_payment_details->save(false);
        }
    }

    /**
     * Function to Get the Airport id 
     */
    public function getAirportName($id){

        if(!empty($id)){
            $name = AirportOfOperation::find()
                        ->select(['airport_name'])
                        ->where(['airport_name_id' => $id])
                        ->one();
            return $name->airport_name;    
        }else{
            echo Json::encode(['status'=>false,'message'=>'Airport is required']);exit;
        }
    }
    /**
     * Function to Get the luggage type 
     */
    public function getLuggageName($id){

        if(!empty($id)){
            $name = LuggageType::find()
                        ->select(['luggage_type'])
                        ->where(['id_luggage_type' => $id])
                        ->one();
            return $name->luggage_type;    
        }else{
            echo Json::encode(['status'=>false,'message'=>'Luggage Type is required']);exit;
        }
    }

    /**
     * Function to Get the city id 
     */
    public function getCityName($id){

        if(!empty($id)){
            $name = CityOfOperation::find()
                        ->select(['region_name'])
                        ->where(['id' => $id])
                        ->one();
            return $name->region_name;
        }else{
            echo Json::encode(['status'=>false,'message'=>'City is required']);exit;
        }

    }

    /**
     * Function to Get the corporate name 
     */
    public function getCorporateName($id){

        if(!empty($id)){
            $corporate_name = ThirdpartyCorporate::find()->where(['fk_corporate_id' => $id])->one();
            return $corporate_name->corporate_name;
        }else{
            echo Json::encode(['status'=>false,'message'=>'Corporate is required']);exit;
        }

    }

    /**
     * Function to get the state name 
     */
    public function getStateName($airport_id,$city_id,$state_id){
        if(empty($state_id)){
            echo Json::encode(['status'=>false,'message'=>'State Name is required']);exit;    
        }else{
            $state_name = State::find()
                        ->select(['stateName'])
                        ->where(['city_id'=>$city_id])
                        ->andWhere(['airport_id'=>$airport_id])
                        ->andWhere(['idState' => $state_id])
                        ->one();
            if(!empty($state_name)){
                return $state_name->stateName;
            }else{
                echo Json::encode(['status'=>false,'message'=>'State Name not found']);exit;    
            }
        }
    }

    /**
     * Function to check user access 
     */
    public function checkUserAccess($created_by,$userId, $role_id){
        // $id_employee = Employee::find()
        //                 ->select(['id_employee', 'fk_tbl_employee_id_employee_role'])
        //                 ->where(['LIKE','name',$created_by])
        //                 ->one();
        
        if(empty($created_by) && empty($userId)){
            return false;    
        }else if($role_id == 12 || $role_id == 15 || $role_id == 13){
            return true;
        }else{
           if(!empty($created_by)){
                $id_employee = Employee::find()
                ->select(['id_employee', 'fk_tbl_employee_id_employee_role'])
                ->where(['LIKE','name',$created_by])
                ->one();
                if(!empty($id_employee)){
                    if($id_employee['id_employee'] == $userId){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return true;    
                }
            }else{
                return false;
            }
           
        }
    }


    /**
     * To get corporate airports
     */
    public function getCorporateAirports($airportId = FALSE) {
        if ($airportId) {
            $airports = AirportOfOperation::find()->where(['IN', 'airport_name_id', $airportId])->all();

            return ($airports) ? $airports : '';
        }else if (empty($airportId)) {
            $airports = AirportOfOperation::find()->all();

            return ($airports) ? $airports : '';
        }else{
            return '';
        }
    }

    /**
     * To get corporate airports
     */
    public function getCorporates($corporateId = FALSE) {
        if ($corporateId) {
            $corporate_details = ThirdpartyCorporate::find()->where(['thirdparty_corporate_id' => $corporateId])->one();
            
            return ($corporate_details) ? $corporate_details : '';
        }else{
            return '';
        }
    }

    /**
     * Function to Get the corporate name 
     */
    public function getThirdCorporateId($id){

        if(!empty($id)){
            $corporate_name = ThirdpartyCorporate::find()->where(['fk_corporate_id' => $id])->one();
            return $corporate_name->thirdparty_corporate_id;
        }else{
            echo Json::encode(['status'=>false,'message'=>'Corporate is required']);exit;
        }

    }

    /**
     * To get corporate airports
     */
    public function getCorporatesDropDown($corporateId = FALSE) {
        if ($corporateId) {
            $corporate_details = ThirdpartyCorporate::find()->where(['thirdparty_corporate_id' => $corporateId])->all();
            
            return ($corporate_details) ? $corporate_details : '';
        }else{
            return '';
        }
    }

    /**
     * To get getCorporateType
     */
    public function getCorporateType($corporateId = FALSE) {
        if ($corporateId) {
            $corporates = CorporateDetails::find()->where(['corporate_detail_id' => $corporateId])->one();
            
            return ($corporates) ? $corporates->corporate_type : '';
        }else{
            return '';
        }
    }

    /**
     * To get corporate sms details
     */
    public function getCorporateSms($orderId, $smsType, $employeeName = false) {
        //print_r($orderId);exit;
        if($orderId){
            $orderDetails= Order::getorderdetails($orderId);
            $corporateId = ($orderDetails['order']['corporate_id']) ? $orderDetails['order']['corporate_id'] : '';
            $numberOfBags = ($orderDetails['order']['no_of_units']) ? $orderDetails['order']['no_of_units'] : '';
            $serviceType = ($orderDetails['order']['service_type']) ? $orderDetails['order']['service_type'] : '';
            $orderNumber = ($orderDetails['order']['order_number']) ? $orderDetails['order']['order_number'] : '';
            $flightNumber = ($orderDetails['order']['flight_number']) ? $orderDetails['order']['flight_number'] : '';
            $travelPerson = ($orderDetails['order']['travel_person']) ? $orderDetails['order']['travel_person'] : '';
            $assignedPerson = ($orderDetails['order']['assigned_person']) ? $orderDetails['order']['assigned_person'] : '';
            $travelPerson = ($orderDetails['order']['travel_person']) ? $orderDetails['order']['travel_person'] : '';
            $customerName = ($orderDetails['order']['customer_name']) ? $orderDetails['order']['customer_name'] : 'Corporate';
            $orderDet=Order::find()->where(['id_order'=>$orderDetails['order']['id_order']])->with(['orderSpotDetails','fkTblOrderIdCustomer'])->one();
            $address_details = ($orderDet) ? $orderDet->orderSpotDetails->address_line_1 : '';
            $country_code = !empty($orderDetails['order']['c_country_code']) ? $orderDetails['order']['c_country_code'] : (!empty($orderDetails['corporate_details']['countrycode']['country_code']) ? $orderDetails['corporate_details']['countrycode']['country_code'] : '91');
            $order_date = ($orderDetails['order']['order_date']) ? date("Y-m-d", strtotime($orderDetails['order']['order_date'])) : '';
            $date_created = ($orderDetails['order']['date_created']) ? date("Y-m-d", strtotime($orderDetails['order']['date_created'])) : '';

            $slot_start_time = date('h:i a', strtotime($orderDetails['order']['slot_start_time']));
            $slot_end_time = date('h:i a', strtotime($orderDetails['order']['slot_end_time']));
            $slot_scehdule = $slot_start_time.' To '.$slot_end_time;
            $order_transfer = ($orderDetails['order']['order_transfer']) ? $orderDetails['order']['order_transfer'] : "";

            //print_r($orderDetails);exit;
            $service = ($order_transfer == 1) ? (($serviceType == 1) ? "To City" : "From City") : (($serviceType == 1) ? "To Airport" : "From Airport");
            // if($serviceType == 1){
            //     $service = 'To Airport';
            // }else{
            //     $service = 'From Airport';
            // }

            if($smsType == 'order_confirmation'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order for '.$numberOfBags.' is confirmed and your reference number is #'.$orderNumber.'-CarterX';

                //     $travellingPassenger = 'Dear Customer, Your Order #'.$orderNumber.' placed by '.$customerName.' for '.$flightNumber.' - '.$numberOfBags.' via CarterX is confirmed. For all service related queries  contact our customer support on +91-9110635588.  -CarterX';

                //     $locationContact = 'Dear Customer, Your Order #'.$orderNumber.' placed by '.$customerName.' for '.$numberOfBags.' via CarterX is confirmed. For all service related queries  contact our customer support on +91-9110635588.  -CarterX';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' placed on is confirmed. Local deliveries will be made on the same day if bags are picked before 3pm. Outstation transfer will be delivered before 3 days at the most.Thanks carterx.in';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' is confirmed. Local deliveries will be made on the same day for bags received before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588-CarterX';

                //     $locationContact = '';
                // }   
                

                $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $travellingPassenger = $bookingCustomer;
                $locationContact = '';

            }
            if($smsType == "order_confirmation_mhl"){
                $corporate_name = ucwords($orderDetails['corporate_details']['name']);
                $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.' placed on '.$date_created.' by '.$corporate_name.' is confirmed for service on '.$order_date.'. Thanks carterx.in';
                $travellingPassenger = $bookingCustomer;
            }
            if($smsType == 'order_cancelation'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.' has been cancelled. Thank you for choosing us.-CarterX';

                    $locationContact = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$customerName.' has been cancelled. Thank you for choosing us.-CarterX';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. We look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $travellingPassenger = 'Dear Customer, Your Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' has been cancelled.  We look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $locationContact = 'Dear Customer, Your Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' has been cancelled.  We look forward to serving you soon. Thank you for choosing us.-CarterX';
                }   
            }

            if($smsType == 'order_open_for_pickup'){
                // if($serviceType == 1){
                //     $bookingCustomer = '';

                //     $travellingPassenger = 'Dear Customer, Your  Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.' is open. Our executive '.$employeeName.' is assigned to pick the order at '.$address_details.'. For all service related queries  contact our customer support on +91-9110635588. -CarterX';

                //     $locationContact = 'Dear Customer, Your  Order #'.$orderNumber.' placed by '.$customerName.' is open. Our executive '.$employeeName.' is assigned to pick the order at '.$address_details.'. For all service related queries  contact our customer support on +91-9110635588. -CarterX';             
                // }else{
                //     $bookingCustomer = '';

                //     $travellingPassenger = 'Dear Customer, Your Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' is open. Our executive '.$employeeName.' is allocated to pick the order at the Airport.Thanks carterx.in';

                //     $locationContact = 'Dear Customer, Your Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' is open. Our executive '.$employeeName.' is allocated to pick the order at the Airport.Thanks carterx.in';
                // }   
                
                $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for pickup by '.$employeeName.' for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for pickup by '.$employeeName.' for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $locationContact = $travellingPassenger;

            }

            if($smsType == 'order_your_location_next' && $corporateId != 7){
                if($serviceType == 1){
                    $bookingCustomer = '';

                    $travellingPassenger = 'Dear Customer, Your  Order #'.$orderNumber.' placed by '.$customerName.' is is our next location for pick up. Please ensure the bags are properly packed, locked and ready for Pick up . -CarterX';

                    $locationContact = 'Dear Customer, Your  Order #'.$orderNumber.' placed by '.$customerName.' is is our next location for pick up. Please ensure the bags are properly packed, locked and ready for Pick up . -CarterX';             
                }else{
                    $bookingCustomer = '';

                    $travellingPassenger = 'Hello, Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.' is our next location for delivery. -CarterX';

                    $locationContact = 'Hello, Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.' is our next location for delivery.  -CarterX';
                }   
            }

            if($smsType == 'order_delivered'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been successfully delivered. We thank you for choosing us and look forward to serving you soon. -CarterX';

                //     $travellingPassenger = 'Dear Customer, Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.'  has been successfully delivered. We thank you for choosing us and look forward to serving you soon. Thanks carterx.in';

                //     $locationContact = 'Hello Order #'.$orderNumber.' reference '.$flightNumber.'  placed by '.$customerName.'  has been delivered. We thank you for choosing us and look forward to serving you soon. Thanks carterx.in';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been delivered. We thank you for choosing us and look forward to serving you soon. -CarterX';

                //     $travellingPassenger = 'Dear Customer,  Order #'.$orderNumber.'  FOR '.$flightNumber.' placed by '.$customerName.'  has been delivered. We thank you for choosing us and look forward to serving you soon.  -CarterX';

                //     $locationContact = 'Dear Customer,  Order #'.$orderNumber.'  FOR '.$flightNumber.' placed by '.$customerName.'  has been delivered. We thank you for choosing us and look forward to serving you soon.  -CarterX';
                // }   
                $bookingCustomer ='Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' has been delivered. We thank you for choosing us and Look forward to serving you soon. Carter X carterx.in ';
                $travellingPassenger = $bookingCustomer;
                $locationContact = $bookingCustomer;
            }

            if($smsType == 'order_open_for_deliverey'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is ready for Delivery.  Our Executive '.$employeeName.' is allocated to Deliver your order.  -CarterX';

                //     $travellingPassenger = 'Dear Customer Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' is ready  for Delivery at '.$slot_scehdule.'. Our Executive '.$employeeName.' is allocated to Deliver your order.  -CarterX';

                //     $locationContact = '';             
                // }else{
                //     $bookingCustomer = '';

                //     $travellingPassenger = 'Dear Customer, Order #'.$orderNumber.'  for '.$flightNumber.' is ready  for Delivery at '.$address_details.'. Our executive '.$employeeName.' is allocated to Deliver your order.  -CarterX';

                //     $locationContact = 'Dear Customer, Order #'.$orderNumber.'  for '.$flightNumber.' is ready  for Delivery at '.$address_details.'. Our executive '.$employeeName.' is allocated to Deliver your order.  -CarterX';
                // }   
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$employeeName.'. Thanks carterx.in';
                    $travellingPassenger = $bookingCustomer;
                    $locationContact = ''; 
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$employeeName.'. Thanks carterx.in';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$employeeName.'. Thanks carterx.in';

                    $locationContact =  $travellingPassenger; 
                }
            }

            if($smsType == 'order_pickup_no_modification' && $corporateId != 7){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' has been picked up. -CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.' for '.$numberOfBags.' has been picked. -CarterX';

                    $locationContact = '';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.'  has been picked up. Local deliveries will be made on the same day for bags that are picked-up before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX';

                    $travellingPassenger = 'Dear Customer, Your order #'.$orderNumber.'  refernce '.$flightNumber.' placed by '.$customerName.' has been picked up. Local deliveries will be made on the same day for bags that are picked-up before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX';

                    $locationContact = 'Dear Customer, Your order #'.$orderNumber.'  refernce '.$flightNumber.' placed by '.$customerName.' has been picked up. Local deliveries will be made on the same day for bags that are picked-up before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX';
                }   
            }

            if($smsType == 'order_pickup_with_modification' && $corporateId != 7){
                if($serviceType == 1){
                    $bookingCustomer = '';

                    $travellingPassenger = '';

                    $locationContact = '';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been picked up with Order Modification.Details of modifications are available on the CarterX Interface . Local deliveries will be made on the same day for bags that are picked-up before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX';

                    $travellingPassenger = 'Dear Customer, Your  Order #'.$orderNumber.'  for '.$flightNumber.' has been picked up with Order Modification.Details of the modification are available under Manage Orders on www.carterx.in. Local deliveries will be made on the same day for bags that are picked-up before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX';

                    $locationContact = 'Dear Customer, Your  Order #'.$orderNumber.'  for '.$flightNumber.' has been picked up with Order Modification.Details of the modification are available under Manage Orders on www.carterx.in. Local deliveries will be made on the same day for bags that are picked-up before 3pm. Outstation Deliveries will be based on connectivity and distance to the delivery location. For all delivery related queries contact our customer support at +91-9110635588.-CarterX';
                }   
            }

            if($smsType == 'order_cancel_no_response_at_pickup' && $corporateId != 7){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer Order #'.$orderNumber.', for pickup of bags has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.-CarterX';

                    $travellingPassenger = 'Dear Customer, #'.$orderNumber.' for '.$flightNumber.' placed by '.$customerName.', has been  Cancelled as per our cancellation policy citing NO RESPONSE. Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.-CarterX';

                    $locationContact = 'Hello Order #'.$orderNumber.' reference '.$flightNumber.' placed by '.$customerName.', the status has been changed to Cancelled as per our cancellation policy citing NO RESPONSE. Carter was waiting at '.$address_details.' for your response. Number of attempts were made to make contact.We are a time based service and have moved to the next location. Please call customer care 9110635588 within 15 minutes to allow us to try and reallocate the order. Thanks carterx.in';             
                }else{
                    $bookingCustomer = 'Dear Customer Order #'.$orderNumber.', has been Cancelled as per our cancellation policy citing NO RESPONSE.  Please call our customer support at +91-9110635588 for us re-initiate the pick-up. - CarterX';

                    $travellingPassenger = 'Dear Customer, Your Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.', has been Cancelled as per our cancellation policy citing NO RESPONSE. Please call our customer support at +91-9110635588 for us re-initiate the pick-up. - CarterX';

                    $locationContact = '';
                }   
            }

            if($smsType == 'order_delivery_no_response'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, Order #'.$orderNumber.' the status has been pushed to UNDELIVERED as per our terms & conditions due to NO RESPONSE at airport. Multiple attempts were made to make contact. Please call customer care at +91-9110635588. -CarterX';

                //     $travellingPassenger = 'Dear Customer, Order #'.$orderNumber.' the status has been pushed to UNDELIVERED as per our terms & conditions due to NO RESPONSE at airport. Multiple attempts were made to make contact. Please call customer care at +91-9110635588. -CarterX';

                //     $locationContact = '';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, Order #'.$orderNumber.' has been  UNDELIVERED as per our terms & conditions due to NO RESPONSE/RETURNED BACK TO '.$customerName.' at '.$address_details.'. Multiple attempts were made to make contact. Please call our customer support at +91-9110635588 for further assistance. -CarterX';

                //     $travellingPassenger = 'Dear Customer, Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE/RETURNED BACK TO '.$customerName.' at '.$address_details.'. Multiple attempts were made to make contact. Please call our customer support at +91-9110635588 for further assistance. -CarterX';

                //     $locationContact = 'Dear Customer, Order #'.$orderNumber.'  for '.$flightNumber.' placed by '.$customerName.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE/RETURNED BACK TO '.$customerName.' at '.$address_details.'. Multiple attempts were made to make contact. Please call our customer support at +91-9110635588 for further assistance. -CarterX';
                // }   

               // if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' is Undelivered. Kindly reach out to our customer care on 919110635588. Carter X carterx.in';
                    $travellingPassenger = $bookingCustomer;
                    $locationContact = '';   
                // }else{
                //     $bookingCustomer = 'Dear '.$travelPerson.', your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' is Undelivered. Kindly reach out to our customer care on 919110635588. Carter X carterx.in';
                //     $travellingPassenger = $bookingCustomer;
                //     $locationContact =  $bookingCustomer; 
                // }

            }
            $cCode= ($orderDetails['order']['c_country_code']) ? $orderDetails['order']['c_country_code'] : '91';
            $customer_number = $cCode.$orderDetails['order']['customer_mobile'];
            $traveller_number = $cCode.$orderDetails['order']['travell_passenger_contact'];
            $location_contact = $cCode.$orderDetails['order']['location_contact_number'];

            //print_r($travellingPassenger);exit;

            // Here add primary contact number for SMS @Bj
            $primary_sms_content = $bookingCustomer;
            if($orderDetails['corporate_details']['default_contact']){
                    $primary_user_contacts = $orderDetails['corporate_details']['countrycode']['country_code'].$orderDetails['corporate_details']['default_contact'];
                    User::sendsms($primary_user_contacts,$primary_sms_content);
            }
            $pickupPersonNumber = $country_code.$orderDetails['order']['pickupPersonNumber'];
            $dropPersonNumber = $country_code.$orderDetails['order']['dropPersonNumber'];
            if($pickupPersonNumber == $dropPersonNumber){
                User::sendsms($pickupPersonNumber,$bookingCustomer);
            } else {
                User::sendsms($pickupPersonNumber,$bookingCustomer);
                User::sendsms($dropPersonNumber,$bookingCustomer);
            }
            // Here add primary contact number for SMS @Bj


        if ($customer_number == $traveller_number){
            if($traveller_number == $location_contact){
              User::sendsms($customer_number,$bookingCustomer);
            }else{
              User::sendsms($customer_number,$bookingCustomer);
              User::sendsms($location_contact,$bookingCustomer);
            }
                
          }else{
            if($traveller_number == $location_contact){
              User::sendsms($customer_number,$bookingCustomer);
              User::sendsms($traveller_number,$bookingCustomer);
            }else{
              User::sendsms($customer_number,$bookingCustomer);
              User::sendsms($traveller_number,$bookingCustomer);
              User::sendsms($location_contact,$bookingCustomer);
            }

          }


         // if (($customer_number == $traveller_number) && ($traveller_number == $location_contact)){
         //        User::sendsms($customer_number,$bookingCustomer);
         //  }else{
         //   if(!empty($bookingCustomer) && $customer_number){
         //        User::sendsms($customer_number,$bookingCustomer);
         //    }
         //    if(!empty($travellingPassenger) && $traveller_number){
         //        User::sendsms($traveller_number,$travellingPassenger);
         //    }
         //    if(!empty($locationContact) && $location_contact){
         //        User::sendsms($location_contact,$locationContact);
         //    }

         //  }

            
            return true;
        }
    }

    /**
     * To get corporate sms details
     */
    public function getGeneralKioskSms($orderId, $smsType, $employeeName = false) {
      //print_r($smsType);exit;
        $primary_sms_content = "";
        if($orderId){
            $orderDetails= Order::getorderdetails($orderId);
             
            $corporateId = ($orderDetails['order']['corporate_id']) ? $orderDetails['order']['corporate_id'] : '';
            $numberOfBags = ($orderDetails['order']['no_of_units']) ? $orderDetails['order']['no_of_units'] : '';
            $serviceType = ($orderDetails['order']['service_type']) ? $orderDetails['order']['service_type'] : '';
            $orderNumber = ($orderDetails['order']['order_number']) ? $orderDetails['order']['order_number'] : '';
            $flightNumber = ($orderDetails['order']['flight_number']) ? $orderDetails['order']['flight_number'] : '';
            $travelPerson = ($orderDetails['order']['travel_person']) ? $orderDetails['order']['travel_person'] : '';
            $assignedPerson = ($orderDetails['order']['assigned_person']) ? $orderDetails['order']['assigned_person'] : '';
            
            $order_date = ($orderDetails['order']['order_date']) ? date("Y-m-d", strtotime($orderDetails['order']['order_date'])) : '';
            $date_created = ($orderDetails['order']['date_created']) ? date("Y-m-d", strtotime($orderDetails['order']['date_created'])) : '';
            $modified_amount = ($orderDetails['order']['modified_amount']) ? $orderDetails['order']['modified_amount'] : '';
            $amount_paid = ($orderDetails['order']['amount_paid']) ? $orderDetails['order']['amount_paid'] : '';
            $travelPerson = ($orderDetails['order']['travel_person']) ? $orderDetails['order']['travel_person'] : '';
            $customerName = ($orderDetails['order']['customer_name']) ? $orderDetails['order']['customer_name'] : '';
            $orderDet=Order::find()->where(['id_order'=>$orderDetails['order']['id_order']])->with(['orderSpotDetails','fkTblOrderIdCustomer'])->one();
            
            if($orderDet){
              if($orderDet->orderSpotDetails){
                $address_details = $orderDet->orderSpotDetails->address_line_1;
              }else{
                $address_details = '';
              }
            }else{
              $address_details = '';
            }

            if($modified_amount > 0){
              $pending_amount = $modified_amount;
            }else{
              $pending_amount = '';
            }
            $modification_amount = 

            $slot_start_time = date('h:i a', strtotime($orderDetails['order']['slot_start_time']));
            $slot_end_time = date('h:i a', strtotime($orderDetails['order']['slot_end_time']));
            $slot_scehdule = $slot_start_time.' To '.$slot_end_time;

            if($serviceType == 1){
                $service = 'To Airport';
            }else{
                $service = 'From Airport';
            }

            if($smsType == 'order_confirmation'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags is confirmed. Order Value: '.$amount_paid.' All receipts for cash/Card/razorpay transactions will be sent on successful delivery. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. All orders need to be identified before the order is delivered at the terminal at the airport. Meet with carterX personnel is Mandatory before the passenger enters the terminal. Outstation timelines: upto 3 days.-CarterX';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags placed by '.$customerName.' is confirmed. Order Value: '.$amount_paid.' All receipts for cash/Card/razorpay transactions will be sent on successful delivery.  Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. Meet with carterX personnel is Mandatory before the passenger enters the terminal. Outstation timelines: upto 3 days.-CarterX';

                //     $locationContact = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags placed by '.$customerName.' is confirmed. Order Value: '.$amount_paid.' All receipts for cash/Card/razorpay transactions will be sent on successful delivery.  Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. Meet with carterX personnel is Mandatory before the passenger enters the terminal. Outstation  timelines: upto 3 days.-CarterX';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags placed is confirmed. Order Value: '.$amount_paid.'  Luggage/package/items will have to be identified by travelling passenger on arrival at the airport terminal. meet with with CarterX personnel at the terminal is MANDATORY. Delivery Timeline for outstation bookings: upto 3 days. Meet with CarterX personnel at the terminal is MANDATORY. All receipts for cash/Card/razorpay transactions will be sent on successful delivery. -CarterX';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$customerName.'  for '.$numberOfBags.' bags placed is confirmed. Order Value: '.$amount_paid.'  Luggage/package/items will have to be identified by travelling passenger on arrival at the airport terminal. Meet with with CarterX personnel at the terminal is MANDATORY. Delivery Timeline for outstation bookings: upto 3 days.All receipts for cash/Card/razorpay transactions will be sent on successful delivery. -CarterX';

                //     $locationContact = '';
                // }  
                
                $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $travellingPassenger = $bookingCustomer;
                $locationContact = '';
            }

            if($smsType == 'yet_to_be_confirmed'){
                $order_transfer = $orderDetails['order']['order_transfer'];
                if($order_transfer == 1){
                    $service = '';
                    $cityTransfer = 'City transfer';
                } else {
                    $cityTransfer = '';
                }
                $bookingCustomer = 'Dear Customer, your '.$cityTransfer.' Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' is yet to be for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $travellingPassenger = $bookingCustomer;
                $locationContact = '';
            }

            if($smsType == 'order_cancelation'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Please login to your account at www.carterx.in for any applicable refund details. Look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Please login to your account at www.carterx.in for any applicable refund details. Look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $locationContact = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Please login to your account at www.carterx.in for any applicable refund details. Look forward to serving you soon. Thank you for choosing us.-CarterX';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' placed on '.$date_created.'  for service on '.$order_date.' has been cancelled. Details of refund if applicable, can be found under My Trips. We look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' placed on '.$date_created.'  for service on '.$order_date.' has been cancelled. Details of refund if applicable, can be found under My Trips. We look forward to serving you soon. Thank you for choosing us.-CarterX';

                    $locationContact = 'Dear Customer, your Order #'.$orderNumber.' placed on '.$date_created.'  for service on '.$order_date.' has been cancelled. Details of refund if applicable, can be found under My Trips. We look forward to serving you soon. Thank you for choosing us.-CarterX';
                }   
            }
            if($smsType == 'order_open_for_pickup'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is open.Please keep your bags packed and ready for pick up and note that 15 minutes is allocated to pick your order at your location. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. '.$employeeName.'  is allocated to pick the order.-CarterX';

                //     $travellingPassenger = 'Dear Custlomer, your  Order #'.$orderNumber.' placed by '.$customerName.' is open. Please keep your bags packed and ready for pick up and note that 15 minutes is allocated to pick your order at your location. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. '.$employeeName.' is allocated to pick the order. -CarterX';

                //     $locationContact = 'Hello,  Order #'.$orderNumber.' placed by '.$customerName.' is open.  Please keep your bags packed and ready for pick up and note that 15 minutes is allocated to pick your order at your location. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. '.$employeeName.' is allocated to pick the order.-CarterX';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is open.  Luggage/package/items will have to be identified by travelling passenger on arrival at the airport terminal. Meet with with CarterX personnel at the terminal is MANDATORY.'.$employeeName.' is allocated to pick-up your order at arrivals at the Airport.-CarterX';

                //     $travellingPassenger = 'Dear Customer, your  Order #'.$orderNumber.' placed by '.$customerName.' is open.  Luggage/package/items will have to be identified by travelling passenger on arrival at the airport terminal. Meet with with CarterX personnel at the terminal is MANDATORY. '.$employeeName.' is allocated to pick the order at arrivals at the Airport.-CarterX';

                //     $locationContact = 'Dear Customer, your  Order #'.$orderNumber.' placed by '.$customerName.' is open.  Luggage/package/items will have to be identified by travelling passenger on arrival at the airport terminal. Meet with with CarterX personnel at the terminal is MANDATORY. '.$employeeName.' is allocated to pick the order at arrivals at the Airport.-CarterX';
                // } 
              $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for pickup by '.$employeeName.' for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for pickup by '.$employeeName.' for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
                $locationContact = $travellingPassenger;
                
             
            }
            
            if($smsType == 'order_your_location_next'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is our next location for pick up service. Please ensure the bags are properly packed, locked and ready for Pick up service. -CarterX';

                    $travellingPassenger = '';

                    $locationContact = 'Hello, Order #'.$orderNumber.' placed by '.$customerName.'  is our next location for pick up service. Please ensure the bags are packed properly, locked and ready for Pick up service.-CarterX';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is our next location for Delivery. -CarterX';

                    $travellingPassenger = '';

                    $locationContact = 'Dear Customer, your Order #'.$orderNumber.' is our next location for Delivery. -CarterX';
                }   
            }

            if($smsType == 'order_cancel_no_response_at_pickup'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer Order #'.$orderNumber.', for pickup of bags has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.-CarterX';

                    $travellingPassenger = 'Dear Custlomer, your Order #'.$orderNumber.' placed by '.$customerName.', has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.           -CarterX';

                    $locationContact = 'Hello Order #'.$orderNumber.' placed by '.$customerName.', has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.-CarterX';             
                }else{
                    $bookingCustomer = 'Dear Customer Order #'.$orderNumber.', has been Cancelled as per our cancellation policy citing NO RESPONSE. Multiple attempts were made to make contact. Please call customer care +91-9110635588 within 30 minutes to allow us to try and reallocate the order. -CarterX';

                    $travellingPassenger = 'Dear customer, your Order #'.$orderNumber.' placed by '.$customerName.', been Cancelled as per our cancellation policy citing NO RESPONSE. Multiple attempts were made to make contact. Please call customer care +91-9110635588 within 30 minutes to allow us to try and reallocate the order. -CarterX';

                    $locationContact = '';
                }   
            }

            if($smsType == 'order_pickup_no_modification'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags has been picked  Order Value: '.$amount_paid.'. Luggage/package/items will have to be identified by travelling passenger before entering the airport terminal. Meet with CarterX personnel before entering the terminal is MANDATORY -CarterX';

                    $travellingPassenger = 'Dear Custlomer, your Order #'.$orderNumber.' placed by '.$customerName.' for '.$numberOfBags.' bags has been picked up.  Order Value: '.$amount_paid.'. Luggage/package/items will have to be identified by travelling partner before entering the airport terminal. Meet with CarterX personnel before entering the terminal is MANDATORY-CarterX';

                    $locationContact = '';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags has been picked Outstation delivery timelines: upto 3 days.-CarterX';

                    $travellingPassenger = 'Hello Order #'.$orderNumber.' placed by '.$customerName.' for '.$numberOfBags.' bags.  has been picked Outstation delivery timelines: upto 3 days.-CarterX';

                    $locationContact = '';
                }   
            }
            if($smsType == 'order_pickup_with_modification'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been picked up with Order Modification of '.$pending_amount.'.Details and payment options available under My Trips.Luggage/package/items will have to be identified by travelling passenger before entering the airport premises. Meet with CarterX personnel before entering the terminal is MANDATORY-CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.'  has been picked up with Order Modification of '.$pending_amount.'. Details and payment options available under My Trips. Luggage/package/items will have to be identified by travelling passenger before entering the airport terminal. Meet with CarterX personnel before entering the terminal is MANDATORY-CarterX';

                    $locationContact = '';             
                }else{
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.'  for '.$numberOfBags.' bags has been picked up with Order Modification of '.$pending_amount.'. Details and payment options available under My Trips.  Outstation delivery timelines: upto 3 days.Payment needs to be made before delivery. -CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags has been picked up with Order Modification of '.$pending_amount.'. Details and payment options available under My Trips.  Outstation delivery timelines: upto 3 days.  Payment needs to be made before delivery. -CarterX';

                    $locationContact = '';
                }   
            }
            if($smsType == 'order_open_for_deliverey'){
                // if($pending_amount){
                //   $amount = $pending_amount;
                // }else{
                //   $amount = $amount_paid;
                // }
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is  scheduled for Delivery at Airport. Luggage/package/items will have to be identified by travelling passenger before entering the airport terminal. Meet with CarterX personnel before entering the terminal is MANDATORY. Amount due for the order '.$amount.'. Please make the payment immediately to avoid delays at delivery. '.$employeeName.' is allocated to Deliver your order. -CarterX';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$customerName.' is  scheduled for Delivery at Airport . Luggage/package/items will have to be identified by travelling passnegr before entering the airport terminal. Meet with CarterX personnel before entering the terminal is MANDATORY. Amount due for the order '.$amount.'. Please make the payment immediately to avoid delays at delivery. '.$employeeName.' is allocated to Deliver your order. -CarterX';

                //     $locationContact = '';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' is  scheduled for Delivery at '.$address_details.'.  Payment due for the order is '.$amount.'. Please make the payment immediately for smoother delivery experience. '.$employeeName.' is allocated to Deliver your order. -CarterX +91-9110635588';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' is  scheduled for Delivery at '.$address_details.'.  Payment due for the order is '.$amount.'". Please make the payment immediately for smoother delivery experience. '.$employeeName.' is allocated to Deliver your order.-CarterX +91-9110635588';

                //     $locationContact = 'Dear Customer, your Order #'.$orderNumber.' is scheduled for Delivery at '.$address_details.'.  Payment due for the order is '.$amount.'. Please make the payment immediately for smoother delivery experience. '.$employeeName.' is allocated to Deliver your order.-CarterX +91-9110635588';
                // }   

                //if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$employeeName.'. Thanks carterx.in';
                    $travellingPassenger = $bookingCustomer;
                    $locationContact = ''; 
                // }else{
                //     $bookingCustomer = 'Dear '.$travelPerson.', your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$employeeName.'. Thanks carterx.in';
                //     $travellingPassenger = 'Dear '.$travelPerson.', your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$employeeName.'. Thanks carterx.in';

                //     $locationContact =  $travellingPassenger; 
                // }
            }

            if($smsType == 'order_delivered'){

                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been delivered. We thank you for choosing us and look forward to serving you soon. Detailed Invoice will be sent to registered email address.-CarterX';

                //     $travellingPassenger = 'Hello Order #'.$orderNumber.' placed by '.$customerName.'  has been delivered. We thank you for choosing us and look forward to serving you soon.-CarterX';

                //     $locationContact = 'Hello Order #'.$orderNumber.' placed by '.$customerName.' has been delivered. We thank you for choosing us and look forward to serving you soon.-CarterX';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been delivered. We thank you for choosing us and look forward to serving you soon. Detailed Invoice will be sent to registered email address. -CarterX';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' has been delivered. We thank you for choosing us and look forward to serving you soon. Detailed Invoice will be sent to registered email address.-CarterX';

                //     $locationContact = 'Dear Customer, your Order #'.$orderNumber.' has been delivered. We thank you for choosing us and look forward to serving you soon. Detailed Invoice will be sent to registered email address.-CarterX';
                // }   

                $bookingCustomer ='Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' has been delivered. We thank you for choosing us and Look forward to serving you soon. Carter X carterx.in ';
                $travellingPassenger = $bookingCustomer;
                $locationContact = $bookingCustomer;
            }

            if($smsType == 'order_delivered_no_response'){
                // if($serviceType == 1){
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at Airport. Multiple attempts were made to make contact. Please call customer care at 9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.-CarterX';

                //     $travellingPassenger = 'Dear Customer, your Order #'.$orderNumber.' by '.$customerName.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at Airport. Multiple attempts were made to make contact. Please call customer care at 9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.-CarterX';

                //     $locationContact = '';             
                // }else{
                //     $bookingCustomer = 'Dear Customer, Order #'.$orderNumber.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at '.$address_details.'. Multiple attempts were made to make contact. Please call customer care at +91-9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.  -CarterX';

                //     $travellingPassenger = 'Hello, Order #'.$orderNumber.' placed by '.$customerName.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at '.$address_details.'. Multiple attempts were made to make contact. Please call customer care at +91-9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.  -CarterX';

                //     $locationContact = 'Hello, Order #'.$orderNumber.' placed by '.$customerName.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at '.$address_details.'. Multiple attempts were made to make contact. Please call customer care at +91-9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.  -CarterX';
                // }  
                //if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' is Undelivered. Kindly reach out to our customer care on 919110635588. Carter X carterx.in';
                    $travellingPassenger = $bookingCustomer;
                    $locationContact = '';   
                // }else{
                //     $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' is Undelivered. Kindly reach out to our customer care on 919110635588. Carter X carterx.in';
                //     $travellingPassenger = $bookingCustomer;
                //     $locationContact =  $bookingCustomer; 
                // } 
            }
            if($smsType == 'order_confirmation_voluntary_reschdule'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #'.$orderNumber.' has been rescheduled to #Order67890 which is confirmed for service to "the airport or display location" on "date of service". -CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #ORD12345placed by "booking customer" has been rescheduled to #Order67890 which is confirmed for service to "the airport or display location" on "date of service". -CarterX';

                    $locationContact = 'Dear Customer, your Order #ORD12345placed by "booking customer" has been rescheduled to #Order67890 which is confirmed for service to "the airport or display location" on "date of service". -CarterX';             
                }else{
                    $bookingCustomer = '';

                    $travellingPassenger = '';

                    $locationContact = '';
                }   
            }
            if($smsType == 'order_confirmation_forced_reschdule'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, your Order #ORD12345 has been FORCED rescheduled to #Order67890 which is confirmed for service to "the airport or display location" on "date of service". -CarterX';

                    $travellingPassenger = 'Dear Customer, your Order #ORD12345 placed by "booking customer" has been FORCED rescheduled to #Order67890 which is confirmed for service to "the airport or display location" on "date of service". -CarterX';

                    $locationContact = 'Dear Customer, your Order #ORD12345 placed by "booking customer" has been FORCED rescheduled to #Order67890 which is confirmed for service to "the airport or display location" on "date of service". -CarterX';             
                }else{
                    $bookingCustomer = '';

                    $travellingPassenger = '';

                    $locationContact = '';
                }   
            }
            if($smsType == 'order_delivery_no_response_reschdule'){
                if($serviceType == 1){
                    $bookingCustomer = 'Dear Customer, Order #ORD12345   has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at Airport. Multiple attempts were made to make contact. Please call customer care at 9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.Thanks carterx.in';

                    $travellingPassenger = 'Dear Customer, Order #ORD12345 placed by "booking customer"   has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at Airport. Multiple attempts were made to make contact. Please call customer care at 9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot.Thanks carterx.in';

                    $locationContact = '';             
                }else{
                    $bookingCustomer = '';

                    $travellingPassenger = '';

                    $locationContact = '';
                }   
            }

            $cCode= ($orderDetails['order']['c_country_code'])? $orderDetails['order']['c_country_code']:'91';
            $customer_number = $cCode.$orderDetails['order']['customer_mobile'];
            $traveller_number = $cCode.$orderDetails['order']['travell_passenger_contact'];
            $location_contact = $cCode.$orderDetails['order']['location_contact_number'];

            // Here add primary contact number for SMS @Bj
            $primary_sms_content = $bookingCustomer;
            if($orderDetails['corporate_details']['default_contact']){
                    $primary_user_contacts = $orderDetails['corporate_details']['countrycode']['country_code'].$orderDetails['corporate_details']['default_contact'];
                    User::sendsms($primary_user_contacts,$primary_sms_content);
            }
            $pickupPersonNumber = $orderDetails['order']['c_country_code'].$orderDetails['order']['pickupPersonNumber'];
            $dropPersonNumber = $orderDetails['order']['c_country_code'].$orderDetails['order']['dropPersonNumber'];
            if(!empty($pickupPersonNumber) && !empty($dropPersonNumber)){
                if($pickupPersonNumber == $dropPersonNumber){
                    User::sendsms($pickupPersonNumber,$bookingCustomer);
                } else {
                    User::sendsms($pickupPersonNumber,$bookingCustomer);
                    User::sendsms($dropPersonNumber,$bookingCustomer);
                }
            }
            // Here add primary contact number for SMS @Bj


          if ($customer_number == $traveller_number){
            if($traveller_number == $location_contact){
              User::sendsms($customer_number,$bookingCustomer);
            }else{
              User::sendsms($customer_number,$bookingCustomer);
              User::sendsms($location_contact,$bookingCustomer);
            }
                
          }else{
            if($traveller_number == $location_contact){
              User::sendsms($customer_number,$bookingCustomer);
              User::sendsms($traveller_number,$bookingCustomer);
            }else{
              User::sendsms($customer_number,$bookingCustomer);
              User::sendsms($traveller_number,$bookingCustomer);
              User::sendsms($location_contact,$bookingCustomer);
            }

          }
          //    if (($customer_number == $traveller_number) && ($traveller_number == $location_contact)){
          //       User::sendsms($customer_number,$bookingCustomer);
          // }else{
          //  if(!empty($bookingCustomer) && $customer_number){
          //       User::sendsms($customer_number,$bookingCustomer);
          //   }
          //   if(!empty($travellingPassenger) && $traveller_number){
          //       User::sendsms($traveller_number,$travellingPassenger);
          //   }
          //   if(!empty($locationContact) && $location_contact){
          //       User::sendsms($location_contact,$locationContact);
          //   }

          // }
           
            return true;
        }
    }

    public function generateCityTransferSms($orderId, $smsType,$empname=''){
    //print_r($orderId);exit;
        $primary_sms_content = "";
      if($orderId){
        $orderDetails= Order::getorderdetails($orderId);
        if($orderDetails['order']['related_order_id']>0){
          $rescheduleOrders= Order::getorderdetails($orderDetails['order']['related_order_id']);
          $relatedOrderId = $rescheduleOrders['order_number'];
          $pickupPincode = $rescheduleOrders['pickupPincode'];  
          $order_date = ($rescheduleOrders['order_date']) ? date("Y-m-d", strtotime($rescheduleOrders['order_date'])) : '';
        }else{
          $pickupPincode = $orderDetails['order']['pickupPincode'];  
          $order_date = ($orderDetails['order']['order_date']) ? date("Y-m-d", strtotime($orderDetails['order']['order_date'])) : '';
        } 
         
         
        $corporateId = ($orderDetails['order']['corporate_id']) ? $orderDetails['order']['corporate_id'] : '';
        $numberOfBags = ($orderDetails['order']['no_of_units']) ? $orderDetails['order']['no_of_units'] : '';
        $serviceType = ($orderDetails['order']['service_type']) ? $orderDetails['order']['service_type'] : '';
        $orderNumber = ($orderDetails['order']['order_number']) ? $orderDetails['order']['order_number'] : '';
        $flightNumber = ($orderDetails['order']['flight_number']) ? $orderDetails['order']['flight_number'] : '';
        $travelPerson = ($orderDetails['order']['travel_person']) ? $orderDetails['order']['travel_person'] : '';
        $assignedPerson = ($orderDetails['order']['assigned_person']) ? $orderDetails['order']['assigned_person'] : '';
        
        
        $date_created = ($orderDetails['order']['date_created']) ? date("Y-m-d", strtotime($orderDetails['order']['date_created'])) : '';
        $modified_amount = ($orderDetails['order']['modified_amount']) ? $orderDetails['order']['modified_amount'] : '';
        $amount_paid = ($orderDetails['order']['amount_paid']) ? $orderDetails['order']['amount_paid'] : '';
        $travelPerson = ($orderDetails['order']['travel_person']) ? $orderDetails['order']['travel_person'] : '';
        $customerName = ($orderDetails['order']['customer_name']) ? $orderDetails['order']['customer_name'] : '';
        $orderDet=Order::find()->where(['id_order'=>$orderDetails['order']['id_order']])->with(['orderSpotDetails','fkTblOrderIdCustomer'])->one();
        
        if($orderDet){
          if($orderDet->orderSpotDetails){
            $address_details = $orderDet->orderSpotDetails->address_line_1;
          }else{
            $address_details = '';
          }
        }else{
          $address_details = '';
        }

      
        if($modified_amount > 0){
          $pending_amount = $modified_amount;
        }else if($modified_amount < 0){
          $pending_amount = abs($modified_amount);
        }else{
          $pending_amount = '';
        }

        if($serviceType == 1){
                $service = 'To City';
            }else{
                $service = 'From City';
            }

        $slot_start_time = date('h:i a', strtotime($orderDetails['order']['slot_start_time']));
        $slot_end_time = date('h:i a', strtotime($orderDetails['order']['slot_end_time']));
        $slot_scehdule = $slot_start_time.' To '.$slot_end_time;
        $bookingCustomer=0;
        $travellingPassenger=0;
        $locationContact=0;


        if($smsType=='RazorPay'){
          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags is yet to be confirmed. Payment link has sent to the registered email and mobile number. Order will be automated to confirmed once payment is completed.'.PHP_EOL.'CarterX';
          $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' for '.$numberOfBags.' bags is yet to be confirmed. Payment link has sent to the registered email and mobile number. Order will be automated to confirmed once payment is completed.'.PHP_EOL.'CarterX';
          $locationContact_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' for '.$numberOfBags.' bags is yet to be confirmed. Payment link has sent to the registered email and mobile number. Order will be automated to confirmed once payment is completed.'.PHP_EOL.'CarterX';
          $bookingCustomer=1;
          $travellingPassenger=1;
          $locationContact=1;
        }
        if($smsType=='OrderConfirmation'){ 
          // $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags is confirmed. Order Value: '.$amount_paid.' All receipts for cash/Card/razorpay transactions will be sent on successful payment. If payment is made by the corporate house/alliance partners the receipt will be sent directly to them. Complete address will be taken/confirmed by our executives before the pick up. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. Outstation timelines: upto 3 days.'.PHP_EOL.'CarterX';
          // $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags placed by '.$orderDetails['order']['customer_name'].' is confirmed. Order Value: '.$amount_paid.' All receipts for cash/Card/razorpay transactions will be sent on successful payment.  Security Declaration is MANDATORY. Complete address will be taken/confirmed by our executives before the pick up. Outstation timelines: upto 3 days.'.PHP_EOL.'CarterX';
          // $locationContact_smsContent = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.' bags placed by '.$orderDetails['order']['customer_name'].' is confirmed. Order Value: '.$amount_paid.' All receipts for cash/Card/razorpay transactions will be sent on successful payment.  Security Declaration is MANDATORY. Complete address will be taken/confirmed by our executives before the pick up. Outstation timelines: upto 3 days.'.PHP_EOL.'CarterX';

           $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' is confirmed for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';

             

           $travellingPassenger_smsContent = $bookingCustomer_smsContent;
           $locationContact_smsContent=$bookingCustomer_smsContent;


          $bookingCustomer=1;
          $travellingPassenger=1;
          $locationContact=1;
        }
        if($smsType=='OrderCancelation'){
          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Please login to your account at www.carterx.in for any applicable refund details based on the refund policy. Look forward to serving you soon. Thank you for choosing us.'.PHP_EOL.'CarterX';
          $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Please login to your account at www.carterx.in for any applicable refund details based on the refund policy. Look forward to serving you soon. Thank you for choosing us.'.PHP_EOL.'CarterX';
          $locationContact_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been cancelled. Please login to your account at www.carterx.in for any applicable refund details based on the refund policy. Look forward to serving you soon. Thank you for choosing us.'.PHP_EOL.'CarterX';
          $bookingCustomer=1;
          $travellingPassenger=1;
          $locationContact=1;
        }
        if($smsType=='OpenForPickup'){
           $assigned_person = $empname;
          // $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' is open for pick up at '.$pickupPincode.'. Please keep your bags packed and ready for pick up and note that 15 minutes is allocated to pick your order at your location. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. We are a slot based service and pickup will be made on the slot previously selected. Outstation timelines are based on the distance contact the porter allocated for more information.  '.$assigned_person.'  is allocated to pick the order.'.PHP_EOL.'CarterX';
          // $travellingPassenger_smsContent = 'Dear Customer, your  Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' is open for pick up at '.$pickupPincode.'.  Please keep your bags packed and ready for pick up and note that 15 minutes is allocated to pick your order at your location. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. We are a slot based service and pickup will be made on the slot previously selected. Outstation timelines are based on the distance contact the porter allocated for more information. '.$assigned_person.' is allocated to pick the order.'.PHP_EOL.'CarterX';
          // $locationContact_smsContent = 'Dear Customer, your  Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' is open for pick up at '.$pickupPincode.'.  Please keep your bags packed and ready for pick up and note that 15 minutes is allocated to pick your order at your location. Security Declaration is MANDATORY. Please keep the same filled before we arrive to pick the order. We are a slot based service and pickup will be made on the slot previously selected. Outstation timelines are based on the distance contact the porter allocated for more information. '.$assigned_person.' is allocated to pick the order. '.PHP_EOL.'CarterX';

          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for pickup by '.$assigned_person.' for service on '.$order_date.' between '.$slot_scehdule.'. Thanks carterx.in';
          $travellingPassenger_smsContent = $bookingCustomer_smsContent;
           $locationContact_smsContent=$bookingCustomer_smsContent;


          $bookingCustomer=1;
          $travellingPassenger=1;
          $locationContact=1;
        }
        if($smsType=='YourLocationNext'){
          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' is our next location for pick up service. Please ensure the bags are properly packed, locked and ready for Pick up service.'.PHP_EOL.'CarterX'; 
          $locationContact_smsContent = 'Hello, Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].'  is our next location for pick up service. Please ensure the bags are packed properly, locked and ready for Pick up service.'.PHP_EOL.'CarterX';
          $bookingCustomer=1; 
          $locationContact=1;
        }
        if($smsType=='OrderCancelation_Noresponse'){
          $bookingCustomer_smsContent = 'Dear Customer Order #'.$orderNumber.', for pickup of bags has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order. '.PHP_EOL.'CarterX';
          $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].', has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.'.PHP_EOL.'CarterX';
          $locationContact_smsContent = 'Hello Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].', has been Cancelled as per our cancellation policy citing NO RESPONSE.  Multiple attempts were made to make contact and we have proceeded away to the next location. Please call our customer support on  +91-9110635588 within 15 minutes for  us to try and reallocate the order.'.PHP_EOL.'CarterX';
          $bookingCustomer=1;
          $travellingPassenger=1;
          $locationContact=1;
        }
        if($smsType=='PickedUp_NoModification'){
          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' for '.$numberOfBags.'  bags has been picked successfully.'.PHP_EOL.'CarterX';
          $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' for  '.$numberOfBags.' bags has been picked up successfully.'.PHP_EOL.'CarterX'; 
          $bookingCustomer=1;
          $travellingPassenger=1; 
        }
        if($smsType=='PickedUp_WithModification'){ 
          if($modified_amount > 0){
            $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.'  has been picked up with Order Modification of '.$pending_amount.'. Details and payment options available under My Trips. Payment will need to be made in full before dispatch for delivery, your immediate action is appreciated. Please ignore this sms if your order has been placed by a corporate/alliance partner. To know more call +919110635588 '.PHP_EOL.'CarterX';
            $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.'  has been picked up with Order Modification of '.$pending_amount.'. Details and payment options available under My Trips. Payment will need to be made in full before dispatch for delivery, your immediate action is appreciated Please ignore this sms if your order has been placed by a corporate/alliance partner. To know more call +919110635588'.PHP_EOL.'CarterX';
          }else if($modified_amount < 0){
            $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been picked up with Order Modification of '.$pending_amount.'. Your Amount will be refunded within 7 working days. To know more call +919110635588 '.PHP_EOL.'CarterX';

            $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been picked up with Order Modification of '.$pending_amount.'. Your Amount will be refunded within 7 working days. To know more call +919110635588 '.PHP_EOL.'CarterX';
          }

           
          $bookingCustomer=1;
          $travellingPassenger=1; 
        }
        if($smsType=='OpenForDelivery'){
           $assigned_person = $empname;
          // $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' is dispatched for Delivery. We are a slot based service and delivery will be made on the slot previously selected. Outstation timelines are based on the distance contact the porter allocated for more information. '.$assigned_person.'  is allocated to Deliver your order.'.PHP_EOL.'CarterX';
          // $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' is  dispatched for Delivery. We are a slot based service and delivery will be made on the slot previously selected. Outstation timelines are based on the distance contact the porter allocated for more information. '.$assigned_person.'  is allocated to Deliver your order.'.PHP_EOL.'CarterX';

           $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' has been allocated for delivery by '.$assigned_person.'. Thanks carterx.in';
            $travellingPassenger_smsContent = $bookingCustomer_smsContent;
           $locationContact_smsContent=$bookingCustomer_smsContent;


          $bookingCustomer=1;
          $travellingPassenger=1; 
        }
        if($smsType=='OrderDelivered'){
          // $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been delivered. We thank you for choosing us and look forward to serving you soon. '.PHP_EOL.'CarterX';
          // $travellingPassenger_smsContent = 'Hello Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].'  has been delivered. We thank you for choosing us and look forward to serving you soon.'.PHP_EOL.'CarterX';
          // $locationContact_smsContent = 'Hello Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].'  has been delivered. We thank you for choosing us and look forward to serving you soon.'.PHP_EOL.'CarterX';

          $bookingCustomer_smsContent ='Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' has been delivered. We thank you for choosing us and Look forward to serving you soon. Carter X carterx.in ';

           $travellingPassenger_smsContent = $bookingCustomer_smsContent;
           $locationContact_smsContent=$bookingCustomer_smsContent;

          $bookingCustomer=1;
          $travellingPassenger=1;
          $locationContact=1;
        }
        if($smsType=='order_delivered_no_response'){
          // $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at delivery location. Multiple attempts were made to make contact. Please call customer care at +9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot. '.PHP_EOL.'CarterX';
          // $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' has been UNDELIVERED as per our terms & conditions due to NO RESPONSE at delivery location. Multiple attempts were made to make contact. Please call customer care at +9110635588 within 30 minutes to allow us to try and reallocate the order without charge. Extra charges will apply to redirect order back to your location on another delivery slot. '.PHP_EOL.'CarterX'; 


          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' '.$service.'  placed on '.$date_created.' by '.$customerName.' on '.$order_date.' is Undelivered. Kindly reach out to our customer care on 919110635588. Carter X carterx.in';

          $travellingPassenger_smsContent = $bookingCustomer_smsContent;
           $locationContact_smsContent=$bookingCustomer_smsContent;
          $bookingCustomer=1;
          $travellingPassenger=1; 
        }

        if($smsType=='OrderConfirmation_VoluntaryReschedule'){
          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been rescheduled to #'.$relatedOrderId.' which is confirmed for service to '.$pickupPincode.' on '.$order_date.'. '.PHP_EOL.'CarterX';

          $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' has been rescheduled to #'.$relatedOrderId.' which is confirmed for service to '.$pickupPincode.' on '.$order_date.'.'.PHP_EOL.'CarterX';  

          $locationContact_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' has been rescheduled to #'.$relatedOrderId.' which is confirmed for service to '.$pickupPincode.' on '.$order_date.' . '.PHP_EOL.'CarterX';

          $bookingCustomer=1;
          $travellingPassenger=1; 
          $locationContact=1;
        }

        if($smsType=='OrderConfirmation_ForcedReschedule'){
          $bookingCustomer_smsContent = 'Dear Customer, your Order #'.$orderNumber.' has been rescheduled to #'.$relatedOrderId.' which is confirmed for service to '.$pickupPincode.' on '.$order_date.'. '.PHP_EOL.'CarterX';
          $travellingPassenger_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' has been rescheduled to #'.$relatedOrderId.' which is confirmed for service to '.$pickupPincode.' on '.$order_date.'.'.PHP_EOL.'CarterX';  
          $locationContact_smsContent = 'Dear Customer, your Order #'.$orderNumber.' placed by '.$orderDetails['order']['customer_name'].' has been rescheduled to #'.$relatedOrderId.' which is confirmed for service to '.$pickupPincode.' on '.$order_date.'. '.PHP_EOL.'CarterX';

          $bookingCustomer=1;
          $travellingPassenger=1; 
          $locationContact=1;
        }


        $orderContactDetails = \app\api_v3\v3\models\OrderMetaDetails::find()->where(['orderId'=>$orderId])->one();
        $customer_number = $orderDetails['order']['c_country_code'].$orderDetails['order']['customer_mobile'];
        
        $traveller_number = $orderDetails['order']['c_country_code'].$orderContactDetails->pickupPersonNumber;
        $location_contact = $orderDetails['order']['c_country_code'].$orderContactDetails->dropPersonNumber;
         
        
        // Here add primary contact number for SMS @Bj
        $primary_sms_content = $bookingCustomer_smsContent;
        if($orderDetails['corporate_details']['default_contact']){
                $primary_user_contacts = $orderDetails['corporate_details']['countrycode']['country_code'].$orderDetails['corporate_details']['default_contact'];
                User::sendsms($primary_user_contacts,$primary_sms_content);
        }
        $pickupPersonNumber = $orderDetails['order']['c_country_code'].$orderDetails['order']['pickupPersonNumber'];
        $dropPersonNumber = $orderDetails['order']['c_country_code'].$orderDetails['order']['dropPersonNumber'];
        if($pickupPersonNumber == $dropPersonNumber){
            User::sendsms($pickupPersonNumber,$bookingCustomer);
        } else {
            User::sendsms($pickupPersonNumber,$bookingCustomer);
            User::sendsms($dropPersonNumber,$bookingCustomer);
        }
        // Here add primary contact number for SMS @Bj
        if ($customer_number == $traveller_number){
            if($traveller_number == $location_contact){
              User::sendsms($customer_number,$bookingCustomer_smsContent);
            }else{
              User::sendsms($customer_number,$bookingCustomer_smsContent);
              User::sendsms($location_contact,$bookingCustomer_smsContent);
            }
                
          }else{
            if($traveller_number == $location_contact){
              User::sendsms($customer_number,$bookingCustomer_smsContent);
              User::sendsms($traveller_number,$bookingCustomer_smsContent);
            }else{
              User::sendsms($customer_number,$bookingCustomer_smsContent);
              User::sendsms($traveller_number,$bookingCustomer_smsContent);
              User::sendsms($location_contact,$bookingCustomer_smsContent);
            }

          }
         // if (($customer_number == $traveller_number) && ($traveller_number == $location_contact)){
         //        User::sendsms($customer_number,$bookingCustomer_smsContent);
         //  }else{
         //    if($bookingCustomer){
         //        User::sendsms($customer_number,$bookingCustomer_smsContent);
         //    }
         //    if($travellingPassenger){
         //        User::sendsms($traveller_number,$travellingPassenger_smsContent);
         //    }
         //    if($locationContact){
         //        User::sendsms($location_contact,$locationContact_smsContent);
         //    }

         //  }
            
        
        return true;
      }
    }
    /**
     * To get Mysql Date Time format
     */
    public function mysqlDateTimeUpdate() {
        return date('Y-m-d H:i:s');
    }

    /**
     * To format date time
     */
    public static function formatDateTime($createdOn) {
        if ($createdOn != "0000-00-00 00:00:00") {
            echo $createdOn;
        } else {
            echo '';
        }
    }
    
    /*
     * To unlink the file if exists 
     */

    public static function unlinkExistedFile($path, $fileName = FALSE) {
        if (file_exists($path . $fileName)) {
            if (is_file($path . $fileName)) {
                unlink($path . $fileName);
            }
        }
    }
  

    /**
     * To print the array variable
     * @param type $str
     */
    public function printR($str) {
        print '<pre>';
        print_r($str);
        print '</pre>';
    }
     /* Common Uplaod  */
    public static function commonUpload($model, $path, $attribute) {
        $file = UploadedFile::getInstance($model, $attribute);

        if ($file) {
            $file->name = \Yii::$app->Common->getFileName($file->name);
            $model->$attribute = $file->name;
            $ext = end((explode(".", $file->name)));
            $ext = Yii::$app->Common->getExtension($file->name);
            $filePath = $path;
            $path = $filePath . $file->name;

            if ($file->saveAs($path)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * To get file name
     * @param type $fileName
     * @return type
     */
    public static function getFileName($fileName) {
        $ext = Yii::$app->Common->getExtension($fileName);
        return date('Ymdhis') . '-' . Yii::$app->Common->removeSpecialCharacter($fileName,
                        $ext);
        //return date('Ymdhis').'-'.$fileName;
    }
   
    /**
     * To remove special charaters in a content and replace with hyphens
     * */
    public static function removeSpecialCharacter($string, $ext) {
        $string = str_replace('.' . $ext, '', $string); // Replaces all spaces with hyphens.
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '-', $string); // Removes special chars.
        $string = preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.

        return $string . '.' . $ext;
    }

   
    /**
     * To get file extension
     * @param type $fileName
     * @return type
     */
    public static function getExtension($fileName) {
        $file = explode('.', $fileName);
        return end($file);
    }

    /**
     * To generate random key
     */
    public static function getRandomKey() {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X-%04X%04X%04X', mt_rand(32768, 49151),
                mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    /**
     * To get nominate approvers by projectId
     */
    public static function getSerialNum() {
        $digits = 3;
        $today = date('ymdHi');
        $randNum = str_pad(rand(0, pow(10, $digits) - 1), $digits, '0',
                STR_PAD_LEFT);
        return ($today . 'MAG' . $randNum);
    }

    /**
     * To truncate string
     * @param type $string
     * @param type $length
     * @param type $dots
     * @return type
     */
    public static function truncateChars($string, $length, $dots = "...") {
        return (strlen($string) > $length) ? substr($string, 0,
                        $length - strlen($dots)) . $dots : $string;
    }

    /**
     * This method handles to explode an string to array
     */
    public static function explodeBy($separator, $data) {
        $explode = explode($separator, $data);
        return $explode;
    }

    /**
     * This function used to limt the large texts
     * @param type $trimLength
     * @param type $string
     * @return type
     */
    public static function getLessContent($trimLength, $string) {
        $length = strlen($string);
        if ($length > $trimLength) {
            $count = 0;
            $prevCount = 0;
            $array = explode(" ", $string);
            foreach ($array as $word) {
                $count = $count + strlen($word);
                $count = $count + 1;
                if ($count > ($trimLength - 3)) {
                    return substr($string, 0, $prevCount) . "...";
                }
                $prevCount = $count;
            }
        } else {
            return $string;
        }
    }

    /**
     * To redirect using javascript
     */
    public static function redirect($url) {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }

    /**
     * To create secret URL and check xxx
     */
    public static function createSecretUrl($varId) {
        return hash_hmac('sha1', $varId . Yii::$app->user->id,
        Yii::$app->params['urlSecretKey']) . '-' . $varId;
    }

    /**
     * To check the secret url on view page
     * http://stackoverflow.com/questions/5387755/how-to-generate-unique-order-id-just-to-show-touser-with-actual-order-id
     */
    public static function checkSecretUrlVerification($varIdCheck) {
        if (!strstr($varIdCheck, '-'))
                throw new NotFoundHttpException('The requested page does not exist.');

        list($hash, $originalId) = explode('-', $varIdCheck);

        if (hash_hmac('sha1', $originalId . Yii::$app->user->id,
                        Yii::$app->params['urlSecretKey']) === $hash) {
            return $originalId;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * To encript password
     */
    public static function getEncriptedPwd($str) {
        return md5($str);
    }

    /**
     * To get range
     */
    public static function getRange($from, $end) {
        $range = range($from, $end);
        //print_r($range);
        //return $range;
        foreach ($range as $values) {
            $retVal[$values] = $values;
        }
        return $retVal;
    }

   
      /*
     * To generate months
     */
    public static function getMonthsArray() {
        for ($monthNum = 1; $monthNum <= 12; $monthNum++) {
            $months[$monthNum] = date('F', mktime(0, 0, 0, $monthNum, 1));
        }
        return $months;
    }

    /**
     * To get the active menu
     */
    public function activeMenu($activePage) {
        $url = Yii::$app->request->pathInfo;
        if ($url == $activePage) {
            $activeClass = TRUE;
        } else {
            $activeClass = FALSE;
        }
        return $activeClass;
    }   
    
     /**
     * To get month based from date
     */
    public function getMonth($date) {
        if ($date) {
           $result = strtotime($date);
           $month = date('M',$result);
           return $month;
        }
        return FALSE;
    }
      /**
     * To get date based from date
     */
    public function getDate($date) {
        if ($date) {
           $result = strtotime($date);
           $date = date('d',$result);
           return $date;
        }
        return FALSE;
    }
    
    public function createThumbImage($name, $tmpName, $thumbPath, $ext){
        
        $img_dirlarge= $thumbPath;
            //Clean the Filename
        //$img = explode('.', $_FILES['photo_name']['name']);
        //Thumbnail file
        $image_filePath= $tmpName;
        //Rename the thumbnail Image
        $krowAvatar= $name;//'ProfilePicLarge.'.$img[1];
        $img_thumbLarge = $img_dirlarge . $krowAvatar;
        //String lower case
        //$extension = strtolower($img[1]);
        $extension = strtolower($ext);
        //Find the height and width of the image
        list($gotwidth, $gotheight, $gottype, $gotattr)= getimagesize($image_filePath);
        //Find the image type
        //---------- To create thumbnail of image---------------
        if($extension=="jpg" || $extension=="jpeg" ){
        $src = @imagecreatefromjpeg($tmpName);
        }
        else if($extension=="png"){
        $src = @imagecreatefrompng($tmpName);
        }
        else{
        $src = imagecreatefromgif($tmpName);
        }
        //Get the height and width of uploaded image
        list($width,$height)=getimagesize($tmpName);
        //Set new width for image
        $newwidthLarge=200;
        //Set new height for image
        $newheightLarge=200;
    
        // or Calculate and scale it proportanly
        $newheightLarge=round(($height*$newwidthLarge)/$height);
        //Creating the thumbnail from true color
        $tmp=imagecreatetruecolor($newwidthLarge,$newheightLarge);
        //Enable image interlace property
        imageinterlace($tmp, 1);
        //Create a image with given dimension
        @imagecopyresampled($tmp,$src,0,0,0,0,$newwidthLarge,$newheightLarge, $width,$height);  
        //Put the image data to newly created Image
        return $createImageSave=imagejpeg($tmp,$img_thumbLarge,100);  
    }
        /**
         *  To get the album photos counnt 
         */
         public function getSeo($seo){

                 $str = strtolower($seo);
                 $seoTitle =  strtolower(str_replace(array('  ', ' '), '-', preg_replace('/[^a-zA-Z0-9 s]/', '', trim($str))));
                 return $seoTitle;
         }
         /*
          *This method return the pdf 
          **/
        public function getPdf($model){
            $mpdf=new mPDF();

            $mpdf->WriteHTML($model);

            $content = $mpdf->Output('', 'S');

            $content = chunk_split(base64_encode($content));
            $mailto = 'kishorebiit13@gmail.com';
            $from_name = 'Kishore';
            $from_mail = 'kishorebiit13@gmail.com';
            $replyto = 'kishorebiit13@gmail.com';
            $uid = md5(uniqid(time()));
            $subject = 'Alumni Registration Form';
            $message = 'Here is the attachement';
            $filename = 'filename.pdf';

            $header = "From: ".$from_name." <".$from_mail.">\r\n";
            $header .= "Reply-To: ".$replyto."\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
            $header .= "This is a multi-part message in MIME format.\r\n";
            $header .= "--".$uid."\r\n";
            $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
            $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $header .= $message."\r\n\r\n";
            $header .= "--".$uid."\r\n";
            $header .= "Content-Type: application/pdf; name=\"".$filename."\"\r\n";
            $header .= "Content-Transfer-Encoding: base64\r\n";
            $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
            $header .= $content."\r\n\r\n";
            $header .= "--".$uid."--";
            $is_sent = @mail($mailto, $subject, "", $header);

            return $mpdf->Output();
        }

        public function Checkpincodeavailability($pincode,$airport_name,$service_type,$city_name)
        {

            if($service_type==1){
                // $respincode=PickDropLocation::find()->where(['pincode'=>$pincode,'to_airport'=>1])->one();
                $respincode=PickDropLocation::find()->where(['pincode'=>$pincode,'to_airport'=>1])->all();
            }else if($service_type==2){
                // $respincode=PickDropLocation::find()->where(['pincode'=>$pincode,'from_airport'=>1])->one();
                $respincode=PickDropLocation::find()->where(['pincode'=>$pincode,'from_airport'=>1])->all();
            }

            if(!empty($respincode)){
                foreach($respincode as $value){
                    if($airport_name == $value['fk_tbl_airport_of_operation_airport_name_id']){
                        $status=true;
                    }else if ($city_name == $value['city_id']) {
                        $status=true;
                    }else if($airport_name != $value['fk_tbl_airport_of_operation_airport_name_id']){
                        $status=false;
                    }elseif ($city_name != $value['city_id']) {
                        $status=false;
                    }
                }
            }else{
                $status=false;
            }
            return $status;
        }


        /*
    ** Function to Get Nearest Pincode Id
    */
    public function Get_nearest_pincode_id($addressFrom, $addressTo, $unit = '') {
       // Google API key
      //print_r('expression');exit;
       $apiKey = 'AIzaSyD9d0O6GwKmtXDbKtmWFIV2nhXrSmIOvik';
       
       $api = file_get_contents("https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=".$addressFrom."&destinations=".$addressTo."&key=".$apiKey);
       $data = json_decode($api);
       
       $unit = strtoupper($unit);
       if($data->rows[0]->elements[0]->status == 'OK'){
           if($unit == "K"){
               $distance = ((int)$data->rows[0]->elements[0]->distance->value / 1000);
               return $distance;
           }else{
               //return round($miles, 2).' miles';
           }
       }else{
           return false;
       }
    }
   
    /**
     * To get sevice type @Bj (down changes)
     */
    public static function getPaymentType($id_order){
        $payment_details = OrderPaymentDetails::find()->where(['id_order' => $id_order])->one();
        if($payment_details){
          return $payment_details;
        } else {
            return "-";
        }
    }

    /**
    * To get order edited history mulitple rows
    */
    public function getOrderHistoryAllDetails($module_name, $id_order){
        $order_edit_details = OrderEditHistory::find()->where(['module_name' => $module_name, 'fk_tbl_order_id' => $id_order])->orderBy(['created_on' => SORT_DESC])->all();
        if($order_edit_details){
          return $order_edit_details;
        }else{
          return false;
        }
    }

    /**
     * To get getCorporateStringType
    */
    public function getCorporateTypeString($categoryTypeId = FALSE) {
        
        if ($categoryTypeId) {
            $corporates = Yii::$app->db->createCommand("SELECT corporate_label FROM tbl_corporate_category where id_corporate_category = '".$categoryTypeId."'")->queryColumn();

            return ($corporates[0]) ? $corporates[0] : '';
        }else{
            return '';
        }
    }

    // mapping corporate super admin with token
    public function mapEmptoToken($data){
        if(empty($data)){
            return false;
        } else {
            $CSA = Yii::$app->db->createCommand("Select id_employee from tbl_employee where fk_tbl_employee_id_employee_role = '12' and pk_corporate_detail_id_as_fk = '".$data['corporate_id']."'")->queryColumn();

            if($CSA){
                foreach($CSA as $value){
                    $corporate_user = new CorporateUser();
                    $corporate_user->fk_tbl_employee_id = $value;
                    $corporate_user->corporate_id   =  $data['thirdparty_corporate_id'];
                    $corporate_user->status   = 1;
                    $corporate_user->save();
                }
            }
            return true;
        }
    }

    public function getCorporateIds($employeeId = FALSE) {
        $corporate_id = array();
        if ($employeeId) {
            $corporate_details = CorporateUser::find()->where(['fk_tbl_employee_id' => $employeeId])->all();
            foreach($corporate_details as $value){
                $corporate_id[] = $value['corporate_id'];
            }
            return ($corporate_details) ? $corporate_id : '';
        }else{
            return '';
        }
    }

    public function getCorporatesAll($corporateId = FALSE) {
        $corporate_id = array();
        if ($corporateId) {
            $corporate_details = ThirdpartyCorporate::find()->select('fk_corporate_id')->where(['in','thirdparty_corporate_id',$corporateId])->all();
            if($corporate_details){
                foreach($corporate_details as $value){
                    $corporate_id[] = $value->fk_corporate_id;
                }
            }
            return ($corporate_id) ? $corporate_id : '';
        }else{
            return '';
        }
    }

    public function getAllCorporatesDropDown($corporateId = FALSE) {
        if ($corporateId) {
            $corporate_details = ThirdpartyCorporate::find()->where(['in','thirdparty_corporate_id',$corporateId])->all();
            
            return ($corporate_details) ? $corporate_details : '';
        }else{
            return '';
        }
    }
    
    // get slots time according to new functionality @bj
    /**
     * Function for get time slots according to new module 
     * Require Param : (array)$data
     * Return : (array) result
    */ 
    public function getTimeSlots($data){
        if(empty($data)){
            return false;
        } else {
            $cityId = $data['city_id'];
            $transferType = $data['transfer_type'];
            $slotType = $data['slot_type'];
            $airportId = $data['airport_id'];
            if($data['transfer_type'] == 1){
                $transferType = "city transfer";
            } else {
                $transferType = "airport transfer";
            }

            if($data['slot_type'] == 1){
                $slotType = "arrival";
            } else {
                $slotType = "departure";
            }

            $result = Yii::$app->db->createCommand("Select s.id_slots,s.city_slots_id_fk,s.slot_name,CONCAT(s.slot_start_time,' - ',s.slot_start_time) as slot_time,s.delivery_description, s.delivery_time from tbl_city_slots cs left join tbl_slots s on s.city_slots_id_fk = cs.city_slots_id_pk where cs.city_id = '".$cityId."' and FIND_IN_SET('".$airportId."',fk_airport_id) and cs.transfer_type = '".$transferType."'  and  cs.slot_type = '".$slotType."' and cs.status = '1' and s.status = '1'")->queryall();
            
            if($result){
                return $result;
            } else {
                return false;
            }
        }
    }

    /**
     * Function for check
     * Require Param : corporate_id
     * Return : boolean true/false
    */ 
    public function getCheckCorporateSuperAdmin($corporateId){
        if(empty($corporateId)){
            return false;
        }
        $result = Yii::$app->db->createCommand("Select id_employee from tbl_employee where pk_corporate_detail_id_as_fk = '".$corporateId."'")->queryone();
        if(!empty($result)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function for send order sms to customer 
     * Require Params : (array)details, order number
     * Return : boolean true 
    */ 
    public function sendOrderSms($details,$orderNumber=NULL,$contactDetails=NULL){
        $startTime = isset($details['order_sms_time_start']) ? date('h:i A', strtotime($details['order_sms_time_start'])) : '';
        $endTime = isset($details['order_sms_time_end']) ? date('h:i A', strtotime($details['order_sms_time_end'])) : '';
        switch ($details['order_sms_title_id']) {
            case 1:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' is dispatched today. Your order will be delivered in '.$details['order_sms_days'].' days. We will send the next communication on the day of delivery. Thank you for choosing CarterX www.carterx.in';
                break;
            case 2:
                if(!empty($details['order_sms_days'])){
                    $sms_message = 'Dear Customer, your order# '.$orderNumber.' will be picked up in '.$details['order_sms_days'].' days. Thank you for choosing CarterX www.carterx.in';
                } else {
                    $sms_message = 'Dear Customer, your order# '.$orderNumber.' will be picked up. Your order will be picked between '.$startTime.' - '.$endTime.'. Thank you for choosing CarterX www.carterx.in';
                }
                break;
            case 3:
                if(!empty($details['order_sms_days'])){
                    $sms_message = 'Dear Customer, your order# '.$orderNumber.'  will be delivered in '.$details['order_sms_days'].' Days. Thank you for choosing CarterX www.carterx.in';
                } else {
                    $sms_message = 'Dear Customer, your order# '.$orderNumber.' is out for delivery. Your order will be delivered between '.$startTime.' - '.$endTime.'. Thank you for choosing CarterX www.carterx.in';
                }
                break;
            case 4:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' has currently reached our '.$details['order_sms_text'].' facility. Your orders will be delivered soon. Thank you for choosing CarterX www.carterx.in';
                break;
            case 5:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' has been delivered. Thank you for choosing CarterX www.carterx.in';
                break;
            case 6:
                $sms_message = 'Dear Customer, for your order# '.$orderNumber.' please be informed that '.$details['order_sms_extra_text'].'. Thank you for choosing CarterX www.carterx.in';
                break;
            case 7:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' will be picked up in '.$details['order_sms_days'].' Day​. Please be informed that '.$details['order_sms_extra_text'].'. Thank you for choosing CarterX www.carterx.in';
                break;
            case 8:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' will be picked up. Your order will be picked between '.$startTime.' - '.$endTime.'. Please be informed that '.$details['order_sms_extra_text'].'. Thank you for choosing CarterX www.carterx.in';
                break;
            case 9:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' will be delivered in '.$details['order_sms_days'].' Day. Please be informed that '.$details['order_sms_extra_text'].'. Thank you for choosing CarterX www.carterx.in';
                break;
            case 10:
                $sms_message = 'Dear Customer, your order# '.$orderNumber.' will be delivered. Your order will be delivered between '.$startTime.' - '.$endTime.'. Please be informed that '.$details['order_sms_extra_text'].' . Thank you for choosing CarterX www.carterx.in';
                break;
            default:
               echo "";
        }

        if(($details['order_sms_customer_mobile'] == $details['order_sms_traveller_passenger_mobile']) && ($details['order_sms_traveller_passenger_mobile'] == $details['order_sms_location_contact_mobile'])){

            User::sendsms($details['order_sms_customer_mobile'],$sms_message);
        
        } else if(($details['order_sms_customer_mobile'] == $details['order_sms_traveller_passenger_mobile']) && ($details['order_sms_traveller_passenger_mobile'] != $details['order_sms_location_contact_mobile'])){

            User::sendsms($details['order_sms_customer_mobile'],$sms_message);
            User::sendsms($details['order_sms_location_contact_mobile'],$sms_message);
        
        }  else if(($details['order_sms_customer_mobile'] == $details['order_sms_location_contact_mobile']) && ($details['order_sms_traveller_passenger_mobile'] != $details['order_sms_location_contact_mobile'])){
        
            User::sendsms($details['order_sms_customer_mobile'],$sms_message);
            User::sendsms($details['order_sms_traveller_passenger_mobile'],$sms_message);
        
        } else if(($details['order_sms_traveller_passenger_mobile'] == $details['order_sms_location_contact_mobile']) && ($details['order_sms_traveller_passenger_mobile'] != $details['order_sms_customer_mobile'])){
        
            User::sendsms($details['order_sms_customer_mobile'],$sms_message);
            User::sendsms($details['order_sms_traveller_passenger_mobile'],$sms_message);
        
        } else {
            isset($details['order_sms_customer_mobile']) ? User::sendsms($details['order_sms_customer_mobile'],$sms_message) : "";
            isset($details['order_sms_traveller_passenger_mobile']) ? User::sendsms($details['order_sms_traveller_passenger_mobile'],$sms_message) : "";
            isset($details['order_sms_location_contact_mobile']) ? User::sendsms($details['order_sms_location_contact_mobile'],$sms_message) : "";
        }

        if(isset($contactDetails['order_sms_pickup'])){
            User::sendsms($contactDetails['order_sms_pickup'],$sms_message);
        }

        if(isset($contactDetails['order_sms_dropup'])){
            User::sendsms($contactDetails['order_sms_dropup'],$sms_message);
        }

        return true;
    }

    /**
     * Function for check and get paymant status
     * Require Param : Order_id
     * Return : Paid, Not Paid
    */ 
    public function CheckOrderModificationPaymentStatus($order_id){
        if(empty($order_id)){
            return false;
        }
        $paymentTotal = Yii::$app->db->createCommand("Select *,(excess_weight_price+volumetric_weight_price+packing_value+express_charge_value+other_price) as sumOfAll,(CASE WHEN gst_percent > 0 THEN ((excess_weight_price+volumetric_weight_price+packing_value+express_charge_value+other_price) + (((excess_weight_price+volumetric_weight_price+packing_value+express_charge_value+other_price)*gst_percent) /100)) ELSE (excess_weight_price+volumetric_weight_price+packing_value+express_charge_value+other_price) END) as TotalPrice from tbl_order_modification_details where order_id = ".$order_id)->queryOne();

        if(isset($paymentTotal)){
            $transactionDetails = Yii::$app->db->createCommand("Select * from tbl_finserve_transaction_details ftd where ftd.order_id = '".$paymentTotal['order_id']."' and (ftd.short_url = '".$paymentTotal['razorpay_link']."' OR ftd.total_order_amount = '".$paymentTotal['TotalPrice']."') ")->queryOne();

            $orderOrderPayment = Yii::$app->db->createCommand("Select * from tbl_order_payment_details where id_order_payment_details ='".$paymentTotal['fk_id_order_payment_details']."' and value_payment_mode = 'Order Modification Amount' and id_order =".$order_id)->queryOne();
            
            if(!empty($orderOrderPayment)){
                if((strtolower($orderOrderPayment['payment_type']) == 'cash') || (strtolower($orderOrderPayment['payment_type']) == 'card') || (strtolower($orderOrderPayment['payment_type']) == 'na') || (strtolower($orderOrderPayment['payment_type']) == 'online payment')){
                    $status = (strtolower($orderOrderPayment['payment_status']) == 'success') ? 'Paid' : "Not paid";    
                } else {
                    if(!empty($transactionDetails)) {
                        $status = (strtolower($transactionDetails['transaction_status']) == 'paid') ? "Paid" : "Not Paid";
                    } else {
                        $status = "Not Paid";
                    }
                }
            } else if(!empty($transactionDetails)) {
                $status = (strtolower($transactionDetails['transaction_status']) == 'paid') ? "Paid" : "Not Paid";
            }
            return $status;
        }
    }

    /**
     * Function for genrate pdf for order modification price
     * Require Params : (array)order_details
     * Return : 
    */ 
    public  function genarateDemoPdf($order_details=null, $template_name="order_modification_amount_info") { 
        $order_details = Order::getorderdetails('24101');
        // echo "<pre>";print_r($order_details);die;
        $OrderModificationDetails = OrderModificationDetails::find()->where(['order_id' => '24101'])->one();

        $modification_info = array('modification_id' => isset($OrderModificationDetails['modification_id']) ? $OrderModificationDetails['modification_id'] : 0,
            'excess_weight' => isset($OrderModificationDetails['excess_weight']) ? $OrderModificationDetails['excess_weight'] : 0,
            'excess_weight_price' => isset($OrderModificationDetails['excess_weight_price']) ? $OrderModificationDetails['excess_weight_price'] : 0,
            'volumetric_weight' => isset($OrderModificationDetails['volumetric_weight']) ? $OrderModificationDetails['volumetric_weight'] : 0,
            'volumetric_weight_price' => isset($OrderModificationDetails['volumetric_weight_price']) ? $OrderModificationDetails['volumetric_weight_price'] : 0,
            'packing_value' => isset($OrderModificationDetails['packing_value']) ? $OrderModificationDetails['packing_value'] : 0,
            'express_charge_value' => isset($OrderModificationDetails['express_charge_value']) ? $OrderModificationDetails['express_charge_value'] : 0,
            'other_description' => isset($OrderModificationDetails['other_description']) ? $OrderModificationDetails['other_description'] : 0,
            'other_price' => isset($OrderModificationDetails['other_price']) ? $OrderModificationDetails['other_price'] : 0,
            'gst_percent' => isset($OrderModificationDetails['gst_percent']) ? $OrderModificationDetails['gst_percent'] : 0,
            'modification_date' => isset($OrderModificationDetails['modification_date']) ? $OrderModificationDetails['modification_date'] : 0,
        );
        ob_start();
        $path=$_SERVER['DOCUMENT_ROOT'].'/'.Yii::$app->params['order_pdf_path'];
        // $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];
        
        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details,'modify_info'=>$modification_info));
        $data = ob_get_clean();
        try {
            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=0,$default_font='',$mgl=0,$mgr=0,$mgt=8,$mgb=8,$mgh=9,$mgf=0, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;

            $html2pdf->writeHTML($data);

            $html2pdf->SetFooter('<div style="width:100%;padding: 16px; text-align: center;background: #2955a7;color: white;font-size: 15px;position: absolute;bottom: 0px;font-style:normal;font-weight:200;">Luggage Transfer Simplified</div>');

            $html2pdf->Output($path."order_modification".$order_details['order']['order_number'].".pdf",'F');

            /*Preparing file path and folder path for response */
            $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order']['order_number'].".pdf";
            $order_pdf['folder_path'] = $_SERVER['DOCUMENT_ROOT'].'/'.Yii::$app->params['order_pdf_path'].'order_modification'.$order_details['order']['order_number'].".pdf";
            return $order_pdf;

        } catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    /**
     * Function for getting thirdparty corporate token info 
     * Require Params : access-token
     * Return : (array)corporate 
    */
    public function getAccessTokenInfo($access_token){
        if(empty($access_token)){ 
            return false;
        }
        $corporate = ThirdpartyCorporate::find()->where(['access_token'=>$access_token])->asArray()->one();
        if(!$corporate){
            return false;
        }else{
            return $corporate;
        }
    }

    /**
     * Function for getting Conveyance Charge according to KM
     * Require Params : token,distance
     * Return :
     */ 
    public function getConveyanceCharge($token,$distance,$stateName=null,$cityName=null){
        $convayance_price = 0;
        $convayance_gst = 0;
        $total_convayance = 0;
        $gst = 0;

        $northStates = array('Jammu & Kashmir','Arunachal Pradesh','Assam','Manipur','Meghalaya','Mizoram','Nagaland','Tripura','Sikkim');
        $workingCity = array('Bangalore', 'Bengaluru','Telangana','Hyderabad','Delhi','New Delhi','East Delhi','West Delhi','North Delhi', 'Central Delhi','South Delhi','Noida','Mumbai','Navi Mumbai','Faridabad','Gurgaon','Ghaziabad','Gautam Buddha Nagar','Sikandrabad','Jewer','Achheja','Dadri','Bisara','Bishrakh','Dhoom','Maicha','Piyaoli','Nuh','Airoli','Ghansoli','Kopar','Khairane','Juhu Nagar','Vashi','Turbhe','Sanpada','Juinagar','Nerul','Darave','Dronagiri','Karave Nagar','CBD Belapur','Kharghar','Kamothe','New Panvel','Kalamboli','Ulwe','Taloja','Nerul Node-II','Thane','K.V.Rangareddy');
        $corporateInfo = $this->getAccessTokenInfo($token);
        $convayanceInfo = $this->getConveyancePriceList($corporateInfo);
        if(!empty($convayanceInfo)){
            if(in_array($stateName, $northStates)){
                $convayance_price = $convayanceInfo[8]['bag_price'];
                $convayance_gst = $convayanceInfo[8]['gst_price'];
                $total_convayance = $convayanceInfo[8]['total_price'];
                $gst = $convayanceInfo[8]['gst'];
            } else if(in_array($cityName, $workingCity)){
                $convayance_price = $convayanceInfo[7]['bag_price'];
                $convayance_gst = $convayanceInfo[7]['gst_price'];
                $total_convayance = $convayanceInfo[7]['total_price'];
                $gst = $convayanceInfo[7]['gst'];
            } else {
                switch ($distance){
                    case ($distance <= 75) :
                        $convayance_price = $convayanceInfo[0]['bag_price'];
                        $convayance_gst = $convayanceInfo[0]['gst_price'];
                        $total_convayance = $convayanceInfo[0]['total_price'];
                        $gst = $convayanceInfo[0]['gst'];
                        break;
                    case ($distance <= 130) :
                        $convayance_price = $convayanceInfo[1]['bag_price'];
                        $convayance_gst = $convayanceInfo[1]['gst_price'];
                        $total_convayance = $convayanceInfo[1]['total_price'];
                        $gst = $convayanceInfo[1]['gst'];
                        break;
                    case ($distance <= 200) :
                        $convayance_price = $convayanceInfo[2]['bag_price'];
                        $convayance_gst = $convayanceInfo[2]['gst_price'];
                        $total_convayance = $convayanceInfo[2]['total_price'];
                        $gst = $convayanceInfo[2]['gst'];
                        break;
                    case ($distance <= 300) :
                        $convayance_price = $convayanceInfo[3]['bag_price'];
                        $convayance_gst = $convayanceInfo[3]['gst_price'];
                        $total_convayance = $convayanceInfo[3]['total_price'];
                        $gst = $convayanceInfo[3]['gst'];
                        break;
                    case ($distance <= 400) :
                        $convayance_price = $convayanceInfo[4]['bag_price'];
                        $convayance_gst = $convayanceInfo[4]['gst_price'];
                        $total_convayance = $convayanceInfo[4]['total_price'];
                        $gst = $convayanceInfo[4]['gst'];
                        break;
                    case ($distance <= 500) :
                        $convayance_price = $convayanceInfo[5]['bag_price'];
                        $convayance_gst = $convayanceInfo[5]['gst_price'];
                        $total_convayance = $convayanceInfo[5]['total_price'];
                        $gst = $convayanceInfo[5]['gst'];
                        break;
                    case ($distance > 500) :
                        $convayance_price = $convayanceInfo[6]['bag_price'];
                        $convayance_gst = $convayanceInfo[6]['gst_price'];
                        $total_convayance = $convayanceInfo[6]['total_price'];
                        $gst = $convayanceInfo[6]['gst'];
                        break;
                    default :
                        $convayance_price = 0;
                        $convayance_gst = 0;
                        $total_convayance = 0;
                        $gst = 0;
                        break;
                }
            }
            $result = array(
                'convayance_price' => $convayance_price,
                'convayance_gst' => $convayance_gst,
                'total_convayance' => $total_convayance,
                'gst' => $gst
            );
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Function for getting Conveyance price from database according to corporate id
     * Require Params : distance
     * Return : (array)mainArray
     */
    public function getConveyancePriceList($corporateData){
        if(empty($corporateData)){
            return false; 
        } else {
            $result = Yii::$app->db->createCommand("select co.region_name,IF(tclpc.bag_price, tclpc.bag_price, 0) as bag_price from tbl_thirdparty_corporate_city_region tccr left join tbl_thirdparty_corporate_luggage_price_city tclpc on tclpc.thirdparty_corporate_city_id = tccr.thirdparty_corporate_city_id left join tbl_city_of_operation co on co.id = tccr.city_region_id where tccr.thirdparty_corporate_id = '".$corporateData['thirdparty_corporate_id']."'")->queryAll();// and  tclpc.thirdparty_corporate_city_id != ''
            if(!empty($result)){
                return $this->calculateConveyancePrices($corporateData,$result);
            } else {
                return false;
            }
        }
    }

    /**
     * Function for getting Calculated Conveyance price
     * Require Params : 
     * Return : (array)mainArray
     */
    public function calculateConveyancePrices($corporateInfo,$result){
        $mainArray = array();
        $gst = $corporateInfo['gst'];
        
        foreach($result as $value){
            $bag_price = $value['bag_price'];
            $bag_gst = ($value['bag_price'] * $gst) / 100;
            $total_bag_gst_price = $bag_price + $bag_gst;
            $mainArray[] = array(
                'region_name' => $value['region_name'],
                'bag_price' => $value['bag_price'],
                'gst' => $gst,
                'gst_price' => $bag_gst,
                'total_price' => $total_bag_gst_price
            );
        }
        return $mainArray;
    }

    /**
     * Function for getting Distance in KM or MILES
     * Require Params : toPincode, fromPincode, unit(KM for KM, NULL for MILES)
     * return : (number)
     */ 
    public function getDistance($fromPincode,$toPincode,$unit='KM'){
        $apiKey = isset(Yii::$app->params['api_key']) ? Yii::$app->params['api_key'] : 'AIzaSyD9d0O6GwKmtXDbKtmWFIV2nhXrSmIOvik';
        $api = file_get_contents("https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=".$fromPincode."&destinations=".$toPincode."&key=".$apiKey);
        $data = json_decode($api);
        $unit = strtoupper($unit);
        if($data->rows[0]->elements[0]->status == 'OK'){
            if($unit == "KM"){
                $distance = ((int)$data->rows[0]->elements[0]->distance->value / 1000);
                return $distance;
            }else{
                return $data->rows[0]->elements[0]->distance->value;
            }
        }else{
            return false;
        }
    }

    /**
     * Function for getting state and city name from pincode
     * Require Params : fromPincode
     * Return : (boolean)
    */
    public function getPostalData($fromPincode,$region=NULL){
        $name_array = array();
        $match_value = array();
        $nameStr = "";
        // $api_res = file_get_contents("http://postalpincode.in/api/pincode/".$fromPincode);
        // $data = json_decode($api_res);
        // if(!empty($data->PostOffice[0]) && ($data->Status == "Success")){
        //     foreach($data->PostOffice as $value){
        //         array_push($name_array,$value->Name,$value->Taluk,$value->Circle,$value->District,$value->State,$value->Country,$value->Region,$value->Division);
        //         $nameStr .= "'".$value->Name."','".$value->Taluk."','".$value->Circle."','".$value->District."','".$value->State."','".$value->Country."','".$value->Region."','".$value->Division."',";
        //     }
            $api_res = file_get_contents("https://api.worldpostallocations.com/pincode?postalcode=".$fromPincode."&countrycode=IN");
            $data = json_decode($api_res);
            if(!empty($data->status==1) && (!empty($data->result))){
                foreach($data->result as $value){
                    array_push($name_array,$value->postalLocation,$value->state,$value->district,$value->province); 
                    $nameStr .= "'".$value->postalLocation."','".$value->state."','".$value->district."','".$value->province."',";
                    $explod_data = explode(" ",$value->province);
                    if(!empty($explod_data)){
                        foreach($explod_data as $val){
                            array_push($name_array,$val);
                            $nameStr .="'".$val."',";
                        }
                    }
                }
            $nameStr = rtrim($nameStr,',');
            $regionInfo = Yii::$app->db->createCommand("Select synonyms_name from tbl_city_of_operation co left join tbl_city_synonyms cs ON cs.fk_city_id = co.id where co.region_name = '$region' and synonyms_name IN($nameStr)")->queryOne();

            for($i=0;$i<=count($name_array);$i++){
                $name = $name_array[$i];
                if(preg_match("/{$name}/i",$region)){
                    $match_value = ($name != 'NA') ? $name : '';
                } else if(strpos($region, $name)){
                    $match_value = ($name != 'NA') ? $name : '';
                }  else if(strtolower($name) == strtolower($region)){ 
                    $match_value = ($name != 'NA') ? $name : '';
                } else if(isset($regionInfo) && !empty($regionInfo['synonyms_name'])){
                    $match_value = ($regionInfo['synonyms_name'] != 'NA') ? $regionInfo['synonyms_name'] : '';
                }
                if(!empty($match_value)){
                    return true;
                } else if(($i == count($name_array)) && empty($match_value)){
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Function for getting state and city name from pincode
     * Require Params : fromPincode
     * Return : (array)
    */
    public function getPostalInfo($fromPincode){
        // $api_res = file_get_contents("http://postalpincode.in/api/pincode/".$fromPincode);
        // $data = json_decode($api_res);
        // if(!empty($data->PostOffice[0])){
        //     $info = array(
        //         'District' => $data->PostOffice[0]->District,
        //         'Region' => $data->PostOffice[0]->Region,
        //         'State' => $data->PostOffice[0]->State
        //     );
        //     return $info;
        // } else {
        //     return false;
        // }

        $api_res = file_get_contents("https://api.worldpostallocations.com/pincode?postalcode=".$fromPincode."&countrycode=IN");
        $data = json_decode($api_res);
        if(!empty($data->status==1)){
            
            $explod_data = explode(" ",$data->result[0]->province);

            $info = array(
                'District' => $data->result[0]->district,
                'Region' => $data->result[0]->province,
                'State' => $data->result[0]->state
            );
            return $info;
        } else {
            return false;
        }
    }

    /**
     * Function for send Tracking SMS to customer
     * Require Params : 
     * return : (Boolean)
    */
    public function sendTrackSms($details,$orderFor,$userType,$contactDetails=NULL){
        if(empty($details)){
            return false;
        } else {
            $map_url = "http://maps.google.com/maps?daddr=".$details['latitude'].','.$details['longitude']."&ll=";
            switch($orderFor){
                case "pickup":
                    $sms_content = "Dear Customer, your order# ".$details['order_number']." will be picked at Luggage Belt no: ".$details['numbers']." at Terminal: ".$details['terminal_number']." ".ucwords($details['flight_type']).". Meet of CarterX is compulsory before leaving the airport. You can track and find me on google maps with: ".$map_url.". Thank you for choosing CarterX www.carterx.in";
                    break;
                case "drop":
                    $sms_content = "Dear Customer, your order# ".$details['order_number']." will be delivered at Gate No: ".$details['numbers']." at Terminal: ".$details['terminal_number']." ".ucwords($details['flight_type'])." before entering the airport. Meet of CarterX is compulsory before entering the airport. You can track and find me on google maps with: ".$map_url.". Thank you for choosing CarterX www.carterx.in";
                    break;
                default:
                    $sms_content = "";
            }

            if(($details['customer_mobile'] == $contactDetails['travell_passenger_contact']) && ($contactDetails['travell_passenger_contact'] == $contactDetails['location_contact_number'])){

                User::sendsms($details['customer_mobile'],$sms_content);
            
            } else if(($details['customer_mobile'] == $contactDetails['travell_passenger_contact']) && ($contactDetails['travell_passenger_contact'] != $contactDetails['location_contact_number'])){
    
                User::sendsms($details['customer_mobile'],$sms_content);
                User::sendsms($contactDetails['location_contact_number'],$sms_content);
            
            }  else if(($details['customer_mobile'] == $contactDetails['location_contact_number']) && ($contactDetails['travell_passenger_contact'] != $contactDetails['location_contact_number'])){
            
                User::sendsms($details['customer_mobile'],$sms_content);
                User::sendsms($contactDetails['travell_passenger_contact'],$sms_content);
            
            } else if(($contactDetails['travell_passenger_contact'] == $contactDetails['location_contact_number']) && ($contactDetails['travell_passenger_contact'] != $details['customer_mobile'])){
            
                User::sendsms($details['customer_mobile'],$sms_content);
                User::sendsms($contactDetails['travell_passenger_contact'],$sms_content);
            
            } else {
                isset($details['customer_mobile']) ? User::sendsms($details['customer_mobile'],$sms_content) : "";
                isset($contactDetails['travell_passenger_contact']) ? User::sendsms($contactDetails['travell_passenger_contact'],$sms_content) : "";
                isset($contactDetails['location_contact_number']) ? User::sendsms($contactDetails['location_contact_number'],$sms_content) : "";
            }
    
            if(isset($contactDetails['pickupPersonNumber'])){
                User::sendsms($contactDetails['pickupPersonNumber'],$sms_content);
            }
    
            if(isset($contactDetails['dropPersonNumber'])){
                User::sendsms($contactDetails['dropPersonNumber'],$sms_content);
            }
            return true;
        }
    }

    /**
     * Function for getting city name on order list
    */
    public function getCity($id,$type){
        if(empty($id)){
            return '-';
        } else {
            if($type == 'city'){
                $result = Yii::$app->db->createCommand("SELECT region_name as city_name from tbl_city_of_operation where id = '".$id."'")->queryOne();
                return $result['city_name'];
            } else if($type == 'airport'){
                $result = Yii::$app->db->createCommand("SELECT co.region_name as city_name from tbl_airport_of_operation ao right Join tbl_city_of_operation co ON co.id = ao.fk_tbl_city_of_operation_region_id  where ao.airport_name_id = '".$id."'")->queryOne();
                return $result['city_name'];
            } else {
                return '-';
            }
        }
    }

    /**
     *  Function for getting Pickup and drop pincode on order list
    */
    public function getPincodes($orderId,$type,$serviceType,$transferType){
        if($transferType == 1){
            $info = \app\api_v3\v3\models\OrderMetaDetails::find()->where(['orderId'=>$orderId])->one();
            if($type == 'pick') {
                return  isset($info['pickupPincode']) ? $info['pickupPincode'] : "";
            } else {
                return  isset($info['dropPincode']) ? $info['dropPincode'] : "";
            }
        } else {
            $info = \app\models\OrderSpotDetails::find()->where(['fk_tbl_order_spot_details_id_order'=>$orderId])->one();
            if($type == 'pick'){
                return ($serviceType == 1) ? $info['pincode'] : "";
            } else if($type == 'drop'){
                return ($serviceType == 2) ? $info['pincode'] : "";
            } else {
                return "";
            }
        }
    }

    /**
     * Function for set noreply email according to city 
     * Require Params : $city_airport_id, $type{'region','airport'}
     * return : (array)
    */
    Public function setCcEmailId($city_airport_id, $type) {
        $blr_array = array('Bangalore');
        $hyd_array = array('Hyderabad');
        $mb_array = array('Mumbai','Navi Mumbai/Thane');
        $dl_array = array('New Delhi','Noida - NCR UP','Gurugram - NCR Haryana','Faridabad - NCR Haryana','Ghaziabad - NCR UP');
        if($type == 'region'){
            $region_name = Yii::$app->db->createCommand("select region_name from tbl_city_of_operation where id = '".$city_airport_id."'")->queryOne();
        } else if($type == 'airport'){
            $region_name = Yii::$app->db->createCommand("select region_name from tbl_airport_of_operation ao left join tbl_city_of_operation co ON co.id = ao.fk_tbl_city_of_operation_region_id where ao.airport_name_id = '".$city_airport_id."'")->queryOne();
        } else {
            $region_name = "";
        }

        switch($region_name['region_name']){
            case in_array($region_name['region_name'], $blr_array) :
                $set_cc  = array(Yii::$app->params['blr_email'],Yii::$app->params['customer_email']);
                break;
            case in_array($region_name['region_name'], $hyd_array) :
                $set_cc  = array(Yii::$app->params['hyd_email'],Yii::$app->params['customer_email']);
                break;
            case in_array($region_name['region_name'], $mb_array) :
                $set_cc  = array(Yii::$app->params['bom_email'],Yii::$app->params['customer_email']);
                break;
            case in_array($region_name['region_name'], $dl_array) :
                $set_cc  = array(Yii::$app->params['del_email'],Yii::$app->params['customer_email']);
                break;
            default : 
                $set_cc = array(Yii::$app->params['customer_email']);
        }
        return !empty($set_cc) ? $set_cc : array();
    }

    /**
     * Function for set GST Number according to city in invoice pdf
     * Require Params : $city_airport_id, $type{'region','airport'}
     * return : (String)
    */
    Public function setGstInvoice($city_airport_id, $type) {
        $blr_array = array('Bangalore');
        $hyd_array = array('Hyderabad');
        $mb_array = array('Mumbai','Navi Mumbai/Thane');
        $dl_array = array('New Delhi','Noida - NCR UP','Gurugram - NCR Haryana','Faridabad - NCR Haryana','Ghaziabad - NCR UP');
        if($type == 'region'){
            $region_name = Yii::$app->db->createCommand("select region_name from tbl_city_of_operation where id = '".$city_airport_id."'")->queryOne();
        } else if($type == 'airport'){
            $region_name = Yii::$app->db->createCommand("select region_name from tbl_airport_of_operation ao left join tbl_city_of_operation co ON co.id = ao.fk_tbl_city_of_operation_region_id where ao.airport_name_id = '".$city_airport_id."'")->queryOne();
        } else {
            $region_name = "Bangalore";
        }
        
        switch($region_name['region_name']){
            case in_array($region_name['region_name'], $blr_array) :
                $set_gst  = Yii::$app->params['blr_gst_no'];
                break;
            case in_array($region_name['region_name'], $hyd_array) :
                $set_gst  = Yii::$app->params['hyd_gst_no'];
                break;
            case in_array($region_name['region_name'], $mb_array) :
                $set_gst  = Yii::$app->params['bom_gst_no'];
                break;
            case in_array($region_name['region_name'], $dl_array) :
                $set_gst  = Yii::$app->params['del_gst_no'];
                break;
            default : 
                $set_gst = "29AAGCC8445A1ZP";
        }
        return !empty($set_gst) ? $set_gst : "";
    }

    /**
     * Function for cehck Cargo-Token status
     * Require Params : corporateId
     * return : (boolean)
    */
    public function checkCargoStatus($corporateId){
        $cragoTokenArr = Yii::$app->params['crago_tokens'];
        // $cragoTokenArr = Array('80d02bc08cab43143fabaacba2adf075','74270889b54d540ded3eb695aff807f7','8028943bb8cc1e2f7a88dc8bea8911c7','4c4b38f989db495468044326bb5c56a1','871ea6d9d31842f97908c14594d47ed7','aeba8e6ed1a094c6c3e36bea13aef0aa','046facea3b696a8c5d25da08ec38ac99','cc9de4d4d27c0dde21ffffdb21b89417','4f5ca4b3937a6e73e4eb19fcea2d4254','5ddefaba50d8eb1690d3c37dc6d1a56f','4fd30390492bf80d9c625bc5c22b21d1','c8d2b27d0f4402f40117f31f898bfccb','7f003bdf7516441de7700326f4af7625','256ce18d912c4e57df425ec047eb58ad','35f1fc63733c0f1bd018bdd00db0f471','7ca9a797efc399e51b2d90869108f17b','16db2d823b78734e9105386996f12b80','6b9375d765be72443d0c8f47a0639888','4243cecf38cedbe7297c9f97c3a21971','9c13289724f6a51f170f84ed88bbe119','cd63ad60018d31b2b2d723e15cf6f944','ba532dc74ca196b04aeaf764fabcb61f','aa6328ef87687ea6d0a78028eb37585a','79142d8e87c19a211de3e0b8261f4c6b','0d9bcc0ea2a8286885e890fdcb989192','551dd3c7742b47e900bc565f681f65d8','af55817f89c602b92a0d42e3c9b690ea','6f644f5cc3a55aa0516183d6dc8dd286','2ad79760bb459f46dbea3e077f37d23f','e99872197082005579d5e4127c4f67de','1c51ceafa2c27e51e1ad8083e139195c','6d9a48cdf24befef999b74c21a121b40','f50248d5d842dcdafe08c6b29ab4ca7b','0d47c23bdd3fa8ea29cfd89c6137dd6c','41f2d78581001a1e9b96c5be5f6f3183','89d34cf4efbc5eb34b86084bfd4394ac','d9e5422045d609507b25943cdd5cffdb','ab6c3fd0bb069d8ac04e9ffa8cb21ca5','11ea60609828a8f74335b030df773860','8a4eccd0fb71d26b899637866af4c42b','55831d99d090cefeb30a8fdf92f26ecf','c05e8cf8b23836b62ec12da260deeb22','dd209a51e4c5ac279eb5f60afe59cee3','ac31b9187f77b106459933b663520907','e197ae555c46947d3517ac27c7548adf','33fe1e8547df3daa853d72eb077e7d5e','1580e768cf6d4eafe2b4aee8688b683c','de97981a774fde3bca3956a0e419a3c3','d2e48b4c8ce34f2386b577c50da77aa4','d3a331352cd4df829fb15d519c0459e0','33233b1f3d84b1dbb1bae3d3723c5915','fd36d6125c1831a0220455784b3c46e2','bddc080a924ad7b929a496a7ecb7a02f','065f033981ffbbdca42252500961e651','aa5c56b0f1f7aedd0ad7436d2a9a3a88','422f5b6ca3f010f1d34ef421d0c18d3c','83d68eda2aa0e48d79c580f673cc921b','81f6023bf530fcc74c388533c9a87023','5ab15a3c6809782dbba73fe18e0da082');
        if(empty($corporateId)){
            return false;
        } else {
            $result = Yii::$app->db->createCommand("SELECT * FROM tbl_thirdparty_corporate tc LEFT JOIN tbl_corporate_details cd ON cd.corporate_detail_id = tc.fk_corporate_id where cd.corporate_detail_id = '".$corporateId."'")->queryOne();
            if(!empty($result)){
                return in_array($result['access_token'],$cragoTokenArr) ? true : false;
            } else {
                return false;
            }
        }
    }

    /**
     * Function for get actual delivery date
     * Require Params : order_id
     * return : (date)
    */
    public function ActualDeliveryDate($order_id){
        if(empty($order_id)){
            return false;
        } else {
            $result = Yii::$app->db->createCommand("select * from tbl_order_history where fk_tbl_order_history_id_order = '".$order_id."' and to_tbl_order_status_id_order_status = '18'  order by id_order_history desc")->queryOne();
            if(!empty($result)){
                return !empty($result['date_created']) ? date('Y-m-d h:i:s A',strtotime($result['date_created'])) : false;
            } else {
                return false;
            }
        }
    }

    /**
     * Function for get Corporate type name
     * Require Params : Corporate-type-id
     * return : (string)/(boolean)
    */
    public function getCorporateCategory($corporateTypeId){
        if(empty($corporateTypeId)){
            return false;
        } else {
            $result = Yii::$app->db->createCommand("select corporate_label from tbl_corporate_category where id_corporate_category = '".$corporateTypeId."'")->queryOne();
            return !empty($result) ? $result['corporate_label'] : false;
        }
    }

    /**
     * Function for get Expected delivery date time
     * Require Params : transfer_type(int),(int)service_type,(int)slot_id,(date)selected_date,(date:Y-m-d)arrival_departure_date,(time)arrival_departure_time
     * return : (array)delivery_status,delivery_date_time
    */
    public function getExpectedDeliveryDateTime($transfer_type,$service_type,$slot_id,$selected_date,$arrival_departure_date=NULL,$arrival_departure_time=NULL){
        $delivery_date_time = "";
        $delivery_status = "";
        $show_delivery_time = "";
        $delivery_date = "";
        $slot_info = Yii::$app->db->createCommand("select * from tbl_slots where id_slots = ".$slot_id)->queryOne();
        $slot_end_time = !empty($slot_info['slot_end_time']) ? $slot_info['slot_end_time'] : "00:00:00";
        if (($transfer_type == 1) && ($service_type == 1)) {
            if ($slot_id == 5) {
                $delivery_date = date('Y-m-d', strtotime($selected_date));
                $delivery_status = 'Before';
                $show_delivery_time = '14:00:00';
            } else if ($slot_id == 7) {
                $delivery_date = date('Y-m-d', strtotime($selected_date . '+1 day'));
                $show_delivery_time = '02:00:00';
                $delivery_status = 'After';
            } else if ($slot_id == 9) {
                $delivery_date = date('Y-m-d', strtotime($selected_date));
                $show_delivery_time = '10:00:00';
                $delivery_status = 'After';
            } else {
                $show_delivery_time = date("H:i:s", strtotime("+2 hours", strtotime($slot_end_time)));
                $delivery_date = date('Y-m-d', strtotime($selected_date));
                $delivery_status = 'After';
            }
        } else if (($transfer_type == 1) && ($service_type == 2)) {
            if ($slot_id === 5) {
                $delivery_date = date('Y-m-d', strtotime($selected_date . '+1 day'));
                $show_delivery_time = "14:00:00";
                $delivery_status = 'Before';
            } else {
                $show_delivery_time = "23:55:00";
                $delivery_date = $selected_date;
                $delivery_status = 'Before';
            }
        } else if (($transfer_type == 2) && ($service_type == 2)) {
            if ($slot_id == 5) {
                $delivery_date = date("Y-m-d", strtotime($selected_date. '+3 day'));
                $show_delivery_time = "14:00:00";
                $delivery_status = 'Before';
            } else {
                $delivery_date = date('Y-m-d H:i:s', strtotime($selected_date . '+3 day'));
                $show_delivery_time = "23:55:00";
                $delivery_status = 'Before';
            }
        } else if (($transfer_type == 2) && ($service_type == 1)) {
            if ($slot_id == 5) {
                $delivery_date = date('Y-m-d',strtotime($selected_date . '+3 day'));
                $show_delivery_time = '14:00:00';
                $delivery_status = 'After';
            } else if ($slot_id == 7) {
                $delivery_date = date("Y-m-d",strtotime($selected_date . '+3 day'));
                $show_delivery_time = '02:00:00';
                $delivery_status = 'After';
            } else if ($slot_id === 9) {
                $delivery_date = date("Y-m-d",strtotime($selected_date . '+3 day'));
                $show_delivery_time = '10:00:00';
                $delivery_status = 'After';
            } else {
                $show_delivery_time = date("H:i:s", strtotime("+2 hours", strtotime($slot_end_time)));
                $delivery_date = date("Y-m-d", strtotime($selected_date . '+3 day'));
                $delivery_status = 'After';
            }
        }
        $date_time = date("Y-m-d",strtotime($delivery_date))." ".date("H:i:s",strtotime($show_delivery_time));
        $delivery_date_time = !empty($delivery_date) ? date("Y-m-d H:i:s",strtotime($date_time)) : "";
        return array("delivery_status" => $delivery_status,"delivery_date_time" => $delivery_date_time);
    }

    public function getExpectedSubscriptionDeliveryDateTime($transferType=1, $serviceType=1, $date='2022-09-24',$time='06:45 am'){
        $delivery_date_time = "";
        $delivery_status = "";
        $show_delivery_time = "";
        $delivery_date = "";

        $deliverySlot = strtotime(date('Y-m-d H:i:s',strtotime($date.' '.$time)));
        echo date('Y-m-d',strtotime($date . ' +1 day'));
        echo "<br>".date("H:i:s",(strtotime($time) + (60*60*2)));

        echo "<br> +1 day, +2 hours ----".date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
        echo "<br>".date("Y-m-d H:i:s",$deliverySlot);
        die;


        $slotFirst_i = strtotime(date('Y-m-d H:i:s',strtotime($date.' 04:00 am')));
        $slotFirst_ii = strtotime(date('Y-m-d H:i:s',strtotime($date." 07:00 am")));

        $slotSecond_i = strtotime(date('Y-m-d H:i:s',strtotime($date." 07:00 am")));
        $slotSecond_ii = strtotime(date('Y-m-d H:i:s',strtotime($date." 11:00 am")));

        $slotThird_i = strtotime(date('Y-m-d H:i:s',strtotime($date." 11:00 am")));
        $slotThird_ii = strtotime(date('Y-m-d H:i:s',strtotime($date." 03:00 pm")));

        $slotFour_i = strtotime(date('Y-m-d H:i:s',strtotime($date." 03:00 pm")));
        $slotFour_ii = strtotime(date('Y-m-d H:i:s',strtotime($date." 07:00 pm")));

        $slotFive_i = strtotime(date('Y-m-d H:i:s',strtotime($date." 07:00 pm")));
        $slotFive_ii = strtotime(date('Y-m-d H:i:s',strtotime($date." 12:00 am")));

        $slotSix_i = strtotime(date('Y-m-d H:i:s',strtotime($date." 03:00 pm")));

        if (($transferType == 1) && ($serviceType == 1) && ($orderType == 1)) {
            switch(true){
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
                case (($slotFirst_i < $deliverySlot) && ($deliverySlot < $slotFirst_ii)) : 
                    $delivery_date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d',strtotime($date . ' +1 day')).' '.date("H:i:s",(strtotime($time) + (60*60*2)))));
                    break;
            }
        }
        
    }

    public  function genarateInvoicePdf($order_details, $template_name)
    { 
        define('YII_ENABLE_ERROR_HANDLER', false);
        define('YII_ENABLE_EXCEPTION_HANDLER', false);
        error_reporting("");
        ob_start();
        $path = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'];

        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details));

        $data = ob_get_clean();

        try
        {
            $file_name = "order_invoice_".time().'_'.$order_details['order_number'].".pdf";
            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;
            $html2pdf->writeHTML($data);
            $html2pdf->Output($path.$file_name,'F');
            /*Preparing file path and folder path for response */
            $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['express_pdf_path'].$file_name;
            $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'].$file_name;
            // print_r($order_pdf);exit;
            return $order_pdf;

        }
        catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    /**
     * Function create for getting pincodes details in array formate
     * 
    */
    public function getPincodeData($fromPincode,$region=NULL){
        $name_array = array();
        $match_value = array();
        $nameStr = "";
        // $api_res = file_get_contents("http://postalpincode.in/api/pincode/".$fromPincode);
        // $data = json_decode($api_res);
        // if(!empty($data->PostOffice[0]) && ($data->Status == "Success")){
        //     foreach($data->PostOffice as $value){
        //         array_push($name_array,$value->Name,$value->Taluk,$value->Circle,$value->District,$value->State,$value->Country,$value->Region,$value->Division);
        //         $nameStr .= "'".$value->Name."','".$value->Taluk."','".$value->Circle."','".$value->District."','".$value->State."','".$value->Country."','".$value->Region."','".$value->Division."',";
        //     }
            $api_res = file_get_contents("https://api.worldpostallocations.com/pincode?postalcode=".$fromPincode."&countrycode=IN");
            $data = json_decode($api_res);
            if(!empty($data->status==1) && (!empty($data->result))){
                foreach($data->result as $value){
                    array_push($name_array,$value->postalLocation,$value->state,$value->district,$value->province); 
                    $nameStr .= "'".$value->postalLocation."','".$value->state."','".$value->district."','".$value->province."',";
                    $explod_data = explode(" ",$value->province);
                    if(!empty($explod_data)){
                        foreach($explod_data as $val){
                            array_push($name_array,$val);
                            $nameStr .="'".$val."',";
                        }
                    }
                }
            $nameStr = rtrim($nameStr,',');
            return $name_array;
        } else {
            return false;
        }
    }

    /**
     * Function create for check pincodes are local or outstation
     * 
    */
    public function Checkpincodeordertype($pincode,$secondpincode,$airport_name,$service_type,$city_name) {
        $firstPincodeName = $this->getPincodeData($pincode);
        $secondPincodeName = $this->getPincodeData($secondpincode);
        $serviceCity = array("Bangalore","Bengaluru","Bangalore South","Bangalore North","Bangalore East","Bangalore West",'Telangana','Hyderabad','Delhi','New Delhi','East Delhi','West Delhi','North Delhi', 'Central Delhi','South Delhi','Noida','Mumbai','Navi Mumbai','Faridabad','Gurgaon','Ghaziabad','Gautam Buddha Nagar','Sikandrabad','Jewer','Achheja','Dadri','Bisara','Bishrakh','Dhoom','Maicha','Piyaoli','Nuh','Airoli','Ghansoli','Kopar','Khairane','Juhu Nagar','Vashi','Turbhe','Sanpada','Juinagar','Nerul','Darave','Dronagiri','Karave Nagar','CBD Belapur','Kharghar','Kamothe','New Panvel','Kalamboli','Ulwe','Taloja','Nerul Node-II','Thane','K.V.Rangareddy');

        if(!empty($firstPincodeName) && !empty($secondPincodeName)){
            if(array_intersect($serviceCity,$firstPincodeName)){
                if(array_intersect($firstPincodeName,$secondPincodeName)){
                    $status = true;
                } else {
                    $status = false;
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    public function getSetBank($corporate_id){
        $instruments = array();
        $code = array();
        $bankInfo = array();
        $sequenceStr = array();
        
        if(!empty($corporate_id)){
            $method_info = Yii::$app->db->createCommand("select `bd`.`bank_short_name`,`cb`.`method_id`,`cb`.`fk_card_id` from `tbl_bank_details` `bd` left join `tbl_customize_bank` `cb` on `cb`.`fk_bank_id` = `bd`.`bank_id` where `cb`.`fk_token_id` = (select `tc`.`thirdparty_corporate_id` from `tbl_thirdparty_corporate` `tc` where `tc`.`fk_corporate_id` = ".$corporate_id.") and `cb`.`method_id` = 1")->queryall();

            $method_card_info = Yii::$app->db->createCommand("select `spm`.`method_short_name`,`spm`.`method_type`,`cb`.`method_id` from `tbl_supported_payment_method` `spm` left join `tbl_customize_bank` `cb` on `cb`.`fk_card_id` = `spm`.`method_id` where `cb`.`fk_token_id` = (select `tc`.`thirdparty_corporate_id` from `tbl_thirdparty_corporate` `tc` where `tc`.`fk_corporate_id` = ".$corporate_id.") and `cb`.`method_id` = 2")->queryall();

            $method_wallet_info = Yii::$app->db->createCommand("select `spm`.`method_short_name`,`spm`.`method_type`,`cb`.`method_id` from `tbl_supported_payment_method` `spm` left join `tbl_customize_bank` `cb` on `cb`.`fk_wallet_id` = `spm`.`method_id` where `cb`.`fk_token_id` = (select `tc`.`thirdparty_corporate_id` from `tbl_thirdparty_corporate` `tc` where `tc`.`fk_corporate_id` = ".$corporate_id.") and `cb`.`method_id` = 3")->queryall();

            $method_upi_info = Yii::$app->db->createCommand("select `spm`.`method_short_name`,`spm`.`method_type`,`cb`.`method_id` from `tbl_supported_payment_method` `spm` left join `tbl_customize_bank` `cb` on `cb`.`fk_upi_id` = `spm`.`method_id` where `cb`.`fk_token_id` = (select `tc`.`thirdparty_corporate_id` from `tbl_thirdparty_corporate` `tc` where `tc`.`fk_corporate_id` = ".$corporate_id.") and `cb`.`method_id` = 4")->queryall();
            $i = 0;
            if(!empty($method_info)){
                foreach($method_info as $value){
                    $val = ($value['fk_card_id'] == 1) ? array("credit") : (($value['fk_card_id'] == 2) ? array("debit") : array());
                    $cardCheck = ($value['fk_card_id'] == 3) ? array("credit","debit") : ((!empty($val)) ? $val : array());

                    $i++;
                    $instruments = array();
                    // if($value['netbanking'] ==1){
                        $net_banking = array(
                            "method" => "netbanking",
                            "banks" => array(
                                $value['bank_short_name']
                            )
                        );
                        array_push($instruments,$net_banking);
                    // }
                    if(!empty($cardCheck)){
                        $card_banking = array(
                            "method" => "card",
                            "issuers" => array(
                                $value['bank_short_name']
                            ),
                            "types" => $cardCheck

                        );
                        array_push($instruments,$card_banking);
                    }
                    // if($value['upi']==1){
                    //     $upi_banking = array(
                    //         "method" => "upi",
                    //         "banks" => array(
                    //             $value['bank_short_name']
                    //         ),
                    //         "flows" => array("qr"),
                    //         "apps" => array("google_pay", "phonepe","bhim")
                    //     );
                    //     array_push($instruments,$upi_banking);
                    // }
                    // if($value['wallet']==1){
                    //     $wallet_banking = array(
                    //         "method" => "wallet",
                    //         "wallets" => array(
                    //             $value['bank_short_name']
                    //         )
                    //     );
                    //     array_push($instruments,$wallet_banking);
                    // }
                    $bankInfo[$value['bank_short_name']] = array(
                        "name" => "Pay using ".$value['bank_short_name'],
                        "instruments" => $instruments
                    );
                    $sequenceStr[] = 'block.'.$value['bank_short_name'];              
                }
            }
            if(!empty($method_card_info)){
                foreach($method_card_info as $value){
                    $i++;
                    $instruments = array();

                    if($value['method_type'] == "default"){
                        $card_banking = array(
                            "method" => "card",
                            "issuers" => array(
                                $value['bank_short_name']
                            ),
                            "types" => array($value['method_short_name'])
                        );
                    } else if($value['method_type'] == "network"){
                        $card_banking = array(
                            "method" => "card",
                            "networks" => array($value['method_short_name'])

                        );
                    }
                    array_push($instruments,$card_banking);
                    
                    $bankInfo[$value['method_short_name']] = array(
                        "name" => "Pay using ".$value['method_short_name'],
                        "instruments" => $instruments
                    );
                    $sequenceStr[] = 'block.'.$value['method_short_name'];              
                }
            }
            if(!empty($method_wallet_info)){
                foreach($method_wallet_info as $value){
                    $i++;
                    $instruments = array();
                
                    $wallet_banking = array(
                        "method" => "wallet",
                        "wallets" => array(
                            $value['method_short_name']
                        )
                    );
                    array_push($instruments,$wallet_banking);

                    $bankInfo[$value['method_short_name']] = array(
                        "name" => "Pay using ".$value['method_short_name'],
                        "instruments" => $instruments
                    );
                    $sequenceStr[] = 'block.'.$value['method_short_name'];              
                }
            }
            if(!empty($method_upi_info)){
                foreach($method_upi_info as $value){
                    $i++;
                    $instruments = array();

                    $upi_banking = array(
                        "method" => "upi",
                        // "flows" => array("qr"),
                        "apps" => array($value['method_short_name'])
                    );
                    array_push($instruments,$upi_banking);

                    $bankInfo[$value['method_short_name']] = array(
                        "name" => "Pay using ".$value['method_short_name'],
                        "instruments" => $instruments
                    );

                    $sequenceStr[] = 'block.'.$value['method_short_name'];              
                }
            }

            $stringArr = array(
                "options" => array(
                    "checkout" => array(
                        "config" => array(
                            "display" => array(
                                "blocks" => $bankInfo,
                                "hide" => array(
                                    array(
                                        "method" => "upi"
                                    ),
                                    array(
                                        "method" => "netbanking"
                                    ),
                                    array(
                                        "method" => "card"
                                    ),
                                    array(
                                        "method" => "wallet"
                                    )          
                                ),
                                "sequence" => $sequenceStr,
                                "preferences" => array(
                                    "show_default_blocks" => false
                                )
                            )
                        )
                    )
                )
            );//echo "<pre>";print_r($stringArr);die;
            return $stringArr;
        } else {
            return false;
        }

        
    }

    /**
     * Function for send Common SMS
     * Required Params : order_id, sms_title, sms_sent_to(false for customer, true for employees, 2 for all)
    */
    public function commonSmsSend($order_id,$sms_title,$sms_sent_to=false){
        if(!empty($order_id)){
            $orderDetails= Order::getorderdetails($order_id);

            $order_number = $orderDetails['order']['order_number'];
            $order_transfer = ($orderDetails['order']['order_transfer'] == 1) ? 'City Transfer' : 'Airport Transfer';
            $order_service = ($orderDetails['order']['service_type'] == 1) ? 'Local' : 'Outstation';
            $order_delivery = ($orderDetails['order']['delivery_type'] == 1) ? ('To '.(($orderDetails['order']['order_transfer'] == 1) ? 'City' : 'Airport')) : ('From '.(($orderDetails['order']['order_transfer'] == 1) ? 'City' : 'Airport'));
            
            $country_code = ($orderDetails['order']['c_country_code']) ? $orderDetails['order']['c_country_code'] : (($orderDetails['corporate_details']['countrycode']['country_code']) ? $orderDetails['corporate_details']['countrycode']['country_code'] : "91");

            $traveller_number = ($orderDetails['order']['travell_passenger_contact']) ? $country_code.$orderDetails['order']['travell_passenger_contact'] : "";
            $customer_number = ($orderDetails['order']['customer_mobile']) ? $country_code.$orderDetails['order']['customer_mobile'] : "";
            $pickupPersonNumber = ($orderDetails['order']['pickupPersonNumber']) ? $country_code.$orderDetails['order']['pickupPersonNumber'] : "";
            $dropPersonNumber = ($orderDetails['order']['dropPersonNumber']) ? $country_code.$orderDetails['order']['dropPersonNumber'] : "";
            $location_contact_number = ($orderDetails['order']['location_contact_number']) ? $country_code.$orderDetails['order']['location_contact_number'] : "";

            $default_contact = ($orderDetails['corporate_details']['default_contact']) ? $country_code.$orderDetails['corporate_details']['default_contact'] : '';
            $contact_number1 = ($orderDetails['corporate_details']['contact_number1']) ? $country_code.$orderDetails['corporate_details']['contact_number1'] : '';
            $contact_number2 = ($orderDetails['corporate_details']['contact_number2']) ? $country_code.$orderDetails['corporate_details']['contact_number2'] : '';
            $contact_number3 = ($orderDetails['corporate_details']['contact_number3']) ? $country_code.$orderDetails['corporate_details']['contact_number3'] : '';

            switch($sms_title){
                case "cancelled_with_refund" :
                    $message_content = "Dear Customer Order #".$order_number." is cancelled with refund. The Refund will be initiated in up to 7 working days. Thank you CarterX.in";
                    break;
                
                case "delivered_with_refund" :
                    $message_content = "Dear Customer Order #".$order_number." is Delivered with refund. The Refund will be initiated in up to 7 working days. Thank you CarterX.in";
                    break;
                
                default:
                    $message_content = "";
            }

            if($sms_sent_to == 2){
                // Sent SMS content to all (emplouyees + customers)
                ($default_contact) ? User::sendsms($default_contact,$message_content) : '';
                ($contact_number1) ? User::sendsms($contact_number1,$message_content) : '';
                ($contact_number2) ? User::sendsms($contact_number2,$message_content) : '';
                ($contact_number3) ? User::sendsms($contact_number3,$message_content) : '';
                ($traveller_number) ? User::sendsms($traveller_number,$message_content) : '';
                ($customer_number) ? User::sendsms($customer_number,$message_content) : '';
                ($pickupPersonNumber) ? User::sendsms($pickupPersonNumber,$message_content) : '';
                ($dropPersonNumber) ? User::sendsms($dropPersonNumber,$message_content) : '';
                ($location_contact_number) ? User::sendsms($location_contact_number,$message_content) : '';

            } else if($sms_sent_to == true){
                // Sent SMS content to only Employees
                ($default_contact) ? User::sendsms($default_contact,$message_content) : '';
                ($contact_number1) ? User::sendsms($contact_number1,$message_content) : '';
                ($contact_number2) ? User::sendsms($contact_number2,$message_content) : '';
                ($contact_number3) ? User::sendsms($contact_number3,$message_content) : '';

            } else {
                // Sent SMS content to only Customers
                ($traveller_number) ? User::sendsms($traveller_number,$message_content) : '';
                ($customer_number) ? User::sendsms($customer_number,$message_content) : '';
                ($pickupPersonNumber) ? User::sendsms($pickupPersonNumber,$message_content) : '';
                ($dropPersonNumber) ? User::sendsms($dropPersonNumber,$message_content) : '';
                ($location_contact_number) ? User::sendsms($location_contact_number,$message_content) : '';
            }
        }
    }

    /**
     * Function for upload image file
     * Required Params : source_url:-image temp name, destination_url:- destination url, quality
    */
    public function compress_image($source_url, $destination_url, $quality) {
        $info = getimagesize($source_url);
        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg($source_url);

        elseif ($info['mime'] == 'image/gif')
            $image = imagecreatefromgif($source_url);

        elseif ($info['mime'] == 'image/png')
            $image = imagecreatefrompng($source_url);

        imagejpeg($image, $destination_url, $quality);
        return $destination_url;
    }

    /**
     * Function for getting super subscriber details from employee tbl
     * Required Params : employee_id
    */
    public function get_super_subscription_details($id_employee){
        if(empty($id_employee)){
            return false;
        } else {
            $details = SuperSubscription::find()
                        ->from("tbl_super_subscription as supersub")
                        ->leftJoin('tbl_employee as emp',"(emp.email = supersub.primary_email) OR (emp.email = supersub.secondary_email)")
                        ->where(['emp.id_employee'=>$id_employee,'emp.fk_tbl_employee_id_employee_role'=>17])->one();
            return $details;
        }
    }

    /**
     * Function for set razorpay payment method for super subscription
     * Require Param : subscription_id
    */ 
    public function getSetBankOption($subscription_id){
        $instruments = array();
        $code = array();
        $bankInfo = array();
        $sequenceStr = array();
        
        if(!empty($subscription_id)){
            $method_info = Yii::$app->db->createCommand("select `bd`.`bank_short_name`,`cb`.`method_id`,`cb`.`fk_card_id` from `tbl_bank_details` `bd` left join `tbl_subscription_payment_restriction` `cb` on `cb`.`fk_bank_id` = `bd`.`bank_id` where `cb`.`subscription_id` =  ".$subscription_id." and `cb`.`method_id` = 1")->queryall();

            $method_card_info = Yii::$app->db->createCommand("select `spm`.`method_short_name`,`spm`.`method_type`,`cb`.`method_id` from `tbl_supported_payment_method` `spm` left join `tbl_subscription_payment_restriction` `cb` on `cb`.`fk_card_id` = `spm`.`method_id` where `cb`.`subscription_id` = ".$subscription_id." and `cb`.`method_id` = 2")->queryall();

            $method_wallet_info = Yii::$app->db->createCommand("select `spm`.`method_short_name`,`spm`.`method_type`,`cb`.`method_id` from `tbl_supported_payment_method` `spm` left join `tbl_subscription_payment_restriction` `cb` on `cb`.`fk_wallet_id` = `spm`.`method_id` where `cb`.`subscription_id` = ".$subscription_id." and `cb`.`method_id` = 3")->queryall();

            $method_upi_info = Yii::$app->db->createCommand("select `spm`.`method_short_name`,`spm`.`method_type`,`cb`.`method_id` from `tbl_supported_payment_method` `spm` left join `tbl_subscription_payment_restriction` `cb` on `cb`.`fk_upi_id` = `spm`.`method_id` where `cb`.`subscription_id` = ".$subscription_id." and `cb`.`method_id` = 4")->queryall();

            $i = 0;
            if(!empty($method_info)){
                foreach($method_info as $value){
                    $val = ($value['fk_card_id'] == 1) ? array("credit") : (($value['fk_card_id'] == 2) ? array("debit") : array());
                    $cardCheck = ($value['fk_card_id'] == 3) ? array("credit","debit") : ((!empty($val)) ? $val : array());

                    $i++;
                    $instruments = array();
                    // if($value['netbanking'] ==1){
                        $net_banking = array(
                            "method" => "netbanking",
                            "banks" => array(
                                $value['bank_short_name']
                            )
                        );
                        array_push($instruments,$net_banking);
                    // }
                    if(!empty($cardCheck)){
                        $card_banking = array(
                            "method" => "card",
                            "issuers" => array(
                                $value['bank_short_name']
                            ),
                            "types" => $cardCheck

                        );
                        array_push($instruments,$card_banking);
                    }
                    $bankInfo[$value['bank_short_name']] = array(
                        "name" => "Pay using ".$value['bank_short_name'],
                        "instruments" => $instruments
                    );
                    $sequenceStr[] = 'block.'.$value['bank_short_name'];              
                }
            }
            if(!empty($method_card_info)){
                foreach($method_card_info as $value){
                    $i++;
                    $instruments = array();

                    if($value['method_type'] == "default"){
                        $card_banking = array(
                            "method" => "card",
                            "issuers" => array(
                                $value['bank_short_name']
                            ),
                            "types" => array($value['method_short_name'])
                        );
                    } else if($value['method_type'] == "network"){
                        $card_banking = array(
                            "method" => "card",
                            "networks" => array($value['method_short_name'])

                        );
                    }
                    array_push($instruments,$card_banking);
                    
                    $bankInfo[$value['method_short_name']] = array(
                        "name" => "Pay using ".$value['method_short_name'],
                        "instruments" => $instruments
                    );
                    $sequenceStr[] = 'block.'.$value['method_short_name'];              
                }
            }
            if(!empty($method_wallet_info)){
                foreach($method_wallet_info as $value){
                    $i++;
                    $instruments = array();
                
                    $wallet_banking = array(
                        "method" => "wallet",
                        "wallets" => array(
                            $value['method_short_name']
                        )
                    );
                    array_push($instruments,$wallet_banking);

                    $bankInfo[$value['method_short_name']] = array(
                        "name" => "Pay using ".$value['method_short_name'],
                        "instruments" => $instruments
                    );
                    $sequenceStr[] = 'block.'.$value['method_short_name'];              
                }
            }
            if(!empty($method_upi_info)){
                foreach($method_upi_info as $value){
                    $i++;
                    $instruments = array();

                    $upi_banking = array(
                        "method" => "upi",
                        // "flows" => array("qr"),
                        "apps" => array($value['method_short_name'])
                    );
                    array_push($instruments,$upi_banking);

                    $bankInfo[$value['method_short_name']] = array(
                        "name" => "Pay using ".$value['method_short_name'],
                        "instruments" => $instruments
                    );

                    $sequenceStr[] = 'block.'.$value['method_short_name'];              
                }
            }

            $stringArr = array(
                "options" => array(
                    "checkout" => array(
                        "config" => array(
                            "display" => array(
                                "blocks" => $bankInfo,
                                "hide" => array(
                                    array(
                                        "method" => "upi"
                                    ),
                                    array(
                                        "method" => "netbanking"
                                    ),
                                    array(
                                        "method" => "card"
                                    ),
                                    array(
                                        "method" => "wallet"
                                    )          
                                ),
                                "sequence" => $sequenceStr,
                                "preferences" => array(
                                    "show_default_blocks" => false
                                )
                            )
                        )
                    )
                )
            );
            return $stringArr;
        } else {
            return false;
        }
    }

    /**
     * Function for create razorpay payment link
     * Require Param : subscription_id, razorpay_option(boolean),total_amount
    */ 
    public function createSubscriptionPaymentLink($subscription_id,$razorpay_option,$total_amount,$unit,$transaction_id){
        $expire_date = date('Y-m-d', strtotime('+1 year'));
        $api = new Api(Yii::$app->params['razorpay_api_key'],Yii::$app->params['razorpay_secret_key']);
        $result = SubscriptionPaymentLinkDetails::find()->where(['payment_subscription_id' => $subscription_id,'payment_status' => 'zero'])->one();
        $getResult = SubscriptionTransactionDetails::find()->where(['subscription_id' => $subscription_id,'payment_status'=>'not paid'])->all();
        $total_count =count($getResult);  
        $subscription_info = Yii::$app->db->createCommand("Select * from tbl_super_subscription where subscription_id = ".$subscription_id)->queryOne();
       
        $post_amount = round($total_amount) * 100;
        $string_amount = "$post_amount";
        $amount_payment_link = str_replace(".", "", $string_amount);
        $today = date('Y-m-d'.' 23:59:59');
        $expire_by = strtotime(date('Y-m-d H:i:s',strtotime($today.'+15 days')));
        $order_id = "Order_".$transaction_id; 
        $payment_invoice_id =  "INV_".$transaction_id; 
       
      
        if(!empty($razorpay_option)){
            if(!empty($result)){
                $result->payment_short_link =  $transaction_id;
                $result->payment_invoice_id = $payment_invoice_id;
                $result->payment_order_id = $order_id;
                $result->payment_subscription_id = $subscription_id;
                $result->payment_unit = $unit;
                $result->payment_amount = $total_amount;
                $result->payment_status = $razorpay_option;
                //$result->payment_link_expire_at = isset($expire_by) ? date("Y-m-d H:i:s",$expire_by) : NULL;
                $result->payment_link_create_date = isset($today) ?  $today : NULL;
                $result->razorpay_option = $razorpay_option;
                $result->save(false);
            }
            
            if(isset($getResult)) {
                $seprateAmount = round($total_amount / $unit);
                foreach($getResult as $sub){
                    $spot_detail = Yii::$app->db->createCommand("UPDATE tbl_subscription_transaction_details set payment_invoice_id='".$payment_invoice_id."', payment_transaction_id='".$transaction_id."', paid_amount='".$seprateAmount."',
                    payment_status='". $razorpay_option."' ,expire_date='".$expire_date."', remaining_balance='".$seprateAmount."' where subscription_transaction_id='".$sub['subscription_transaction_id']."'")->execute();
                }
            }

            $model = new Order();
            $model->scenario = 'create-invoice';
            $total_cost = 0;
            $gst_cost = 0;
            $total_gst_cost = 0;
            $total_cost = $unit *  $subscription_info['subscription_cost'];
            $gst_cost = ($total_cost * $subscription_info['subscription_GST']) / 100;
            $total_gst_cost = $total_cost + $gst_cost;

            $invoice_array=array(
                'number_of_useage'=>$subscription_info['no_of_usages'],
                'redemption_cost'=> $subscription_info['redemption_cost'],
                'subscription_cost'=> $subscription_info['subscription_cost'],
                'subscription_gst'=> $subscription_info['subscription_GST'],
                'gst_cost'=> $gst_cost,
                'units'=> $unit,
                'total_subscription_cost'=> sprintf('%0.2f',$total_cost),
                'total_gst_cost'=>$total_gst_cost,
                'total_subscription_gst_cost'=> sprintf('%0.2f', $gst_cost),
                'subscription_id' => $subscription_id
            );
        
            $_POST['Customer']['email'] = $subscription_info['primary_email'];

            // $attachment_det = $this->genaratesubscriptionInvoicePdf($invoice_array,'super_subscription_payment_pdf');
            // User::sendemailsubscriptionattachment($_POST['Customer']['email'],"Subscription Purchase - Cinfirmation #".$subscription_id."",'super_subscription_payment_pdf',$invoice_array, $attachment_det);
        }
        return true;
    }

    public function createZeroSubscriptionPayment($subscription_id,$razorpay_option,$total_amount,$unit){
        $expire_date = date('Y-m-d', strtotime('+1 year'));
        $pay_model = new SubscriptionPaymentLinkDetails;
        $pay_model->payment_short_link = NULL;
        $pay_model->payment_invoice_id = NULL;
        $pay_model->payment_order_id = NULL;
        $pay_model->payment_subscription_id = $subscription_id;
        $pay_model->payment_unit = $unit;
        $pay_model->payment_amount = $total_amount;
        $pay_model->payment_status = 'zero';
        $pay_model->payment_link_expire_at = NULL;
        $pay_model->payment_link_create_date = NULL;
        $pay_model->razorpay_option = false;
        $pay_model->save(false);

        for($i = 0; $i < $unit; $i++){
            $random_string = $this->getrandomalphanum(6);
            
            $subscription_info = Yii::$app->db->createCommand("Select * from tbl_super_subscription where subscription_id = ".$subscription_id)->queryOne();
            $gst_cost = ($subscription_info['subscription_cost'] * $subscription_info['subscription_GST']) / 100;
            $remain=$subscription_info['subscription_cost']+$gst_cost;
            if($subscription_info['subscription_cost'] == 0){
                $query = Yii::$app->db->createCommand("insert into tbl_subscription_transaction_details (subscription_id,confirmation_number,payment_invoice_id,paid_amount,redemption_cost,subscription_cost,no_of_usages,remaining_usages,payment_status,expire_date,remaining_balance,balence_value) VALUES ('".$subscription_id."','".$random_string."','".NULL."','0','".$subscription_info['redemption_cost']."','".$subscription_info['subscription_cost']."','".$subscription_info['no_of_usages']."','".$subscription_info['no_of_usages']."','paid','".$expire_date."','".$remain."','0')")->execute();
            } else {
                $query = Yii::$app->db->createCommand("insert into tbl_subscription_transaction_details (subscription_id,confirmation_number,payment_invoice_id,paid_amount,redemption_cost,subscription_cost,no_of_usages,remaining_usages,payment_status,expire_date,remaining_balance,balence_value) VALUES ('".$subscription_id."','".$random_string."','".NULL."','0','".$subscription_info['redemption_cost']."','".$subscription_info['subscription_cost']."','".$subscription_info['no_of_usages']."','".$subscription_info['no_of_usages']."','not paid','".$expire_date."','".$remain."','0')")->execute();
            }
        }    
        
        return true;
    }

    /**
     * Function for create random alphanumeric string
    */
    public function getrandomalphanum($n) {
        // $n = 4;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
      
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
      
        return $randomString;
    }

    /**
     * Function for getting thirparty token list which is mapped with super subscriber
    */
    public function getCorporateTokenIds($subscription_id = FALSE) {
        $corporate_id = array();
        if (!empty($subscription_id)) {
            $corporate_details = SubscriptionTokenMap::find()->where(['subscription_id' => $subscription_id])->all();
            foreach($corporate_details as $value){
                $corporate_id[] = $value['thirdparty_token_id'];
            }
            return ($corporate_details) ? $corporate_id : [];
        }else{
            return [];
        }
    }

    /**
     * Function for getting mapped confirmation number List to subscriber
    */
    public function updateConfirmationNumber($confirmation_id,$terminal_no,$bag_count,$delivery_type){
        if(empty($confirmation_id)){
            return false;
        } else {
            $getResult = SubscriptionTransactionDetails::find()->where(['subscription_transaction_id' => $confirmation_id])->one();
            if(!empty($getResult)){
                // if($delivery_type == 1){
                //     if($terminal_no == 1){// for International
                //         $getResult->remaining_usages = ($getResult->remaining_usages - ($bag_count * 2));
                //         $getResult->save(false);
                //     } else if($terminal_no == 2){ // for Domestic
                //         $getResult->remaining_usages = ($getResult->remaining_usages - ($bag_count * 1));
                //         $getResult->save(false);
                //     }
                // } if($delivery_type == 2){
                //     if($getResult->remaining_usages > $bag_count){
                //         $getResult->remaining_usages = ($getResult->remaining_usages - $bag_count);
                //     } else {
                //         $getResult->remaining_usages = ($getResult->remaining_usages - $getResult->remaining_usages);
                //     }
                //     $getResult->save(false);
                // }
                if($getResult->remaining_usages > $bag_count){
                    $remaining_usages = ($getResult->remaining_usages - $bag_count);
                    $getResult->remaining_usages = $remaining_usages;
                } else {
                    $remaining_usages = ($getResult->remaining_usages - $getResult->remaining_usages);
                    $getResult->remaining_usages = $remaining_usages;
                }
                $getResult->save(false);
                return $remaining_usages;
            } else {
                return false;
            }
        }

    }

    /**
     * Function for getting mapped employies list
    */
    public function getAssignEmployee($subscription_transaction_id){
        if(empty($subscription_transaction_id)){
            return false;
        } else {
            $result = Yii::$app->db->createCommand("SELECT * FROM tbl_employee_allocation emp_all LEFT JOIN tbl_employee emp on emp.id_employee = emp_all.employee_id where emp_all.subscription_transaction_id = ".$subscription_transaction_id)->queryall();
            if(!empty($result)){
                return ucwords($result[0]['name']);
            } else {
                return false;
            }
        }
    }

    /**
     * Function for check confirmation no to employee
    */
    public function checkEmployConfNo($employee_id,$confirmation_id){
        $result = EmployeeAllocation::find()->where(["employee_id" => $employee_id,"subscription_transaction_id" => $confirmation_id])->one();
        if(!empty($result)){
            return true; 
        } else {
            return false;
        }
    }

    /**
     * Function for get confirmation details through confirmation id
    */
    public function getConfirmationDetails($confirmation_id){
        $result = SubscriptionTransactionDetails::find()->where(['subscription_transaction_id' => $confirmation_id])->one();
        if(!empty($result)){
            return $result;
        } else {
            return false;
        }

    }

    /**
     * 
    */
    public function getUsedUsages($order_id){

        return true;
    }

    /**
     * Function for create subscriber order payment link
    */
    public function createSubscriberRazorpayLink($travel_email, $customer_number, $luggage_price, $confirmation_id, $role_id,$transaction_id,$pay_status){
        $api = new Api(Yii::$app->params['razorpay_api_key'],Yii::$app->params['razorpay_secret_key']);
        $time = strtotime('now');
        $endTime = strtotime(date("H:i", strtotime('+480 minutes', $time)));
        $post_amount = round($luggage_price) * 100;
        $string_amount = "$post_amount";
        $amount_payment_link = str_replace(".", "", $string_amount);
        $invoice_id = $transaction_id;

        $subscriber_info = Yii::$app->db->createCommand("Select * from `tbl_super_subscription` super_sub left join `tbl_subscription_transaction_details` sub_tran on sub_tran.subscription_id = super_sub.subscription_id where sub_tran.subscription_transaction_id =" . $confirmation_id)->queryone();

        $customer_info = Yii::$app->db->createCommand("Select * from tbl_customer where mobile = '".$customer_number."' and email ='".$travel_email."' and fk_id_employee != ''")->queryone();

        // if(!empty($subscriber_info['razorpay_status'])){
        //     $res = $this->getSetBankOption($subscriber_info['subscription_id']);
        //     if(empty($res)){
        //         $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for super subscriber employee', 'amount' => $amount_payment_link,'currency' => 'INR',"prefill" => array("name" => 'bhanu',"email" => 'bhanu@gloify.com',"contact" => '7697644835') ));//'customer' => array('email' => $travel_email, 'contact' => $customer_number)
        //     } else {
        //         $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for super subscriber employee', 'amount' => $amount_payment_link,'currency' => 'INR','reminder_enable'=>true, "options" => $res['options'],"prefill" => array("name" => 'bhanu',"email" => 'bhanu@gloify.com',"contact" => '7697644835')));
        //     }
        // } else {
        //     $payment_link_details  = $api->invoice->create(array('type' => 'link', 'description' => 'Payment link for super subscriber employee', 'amount' => $amount_payment_link,'currency' => 'INR',"prefill" => array("name" => 'bhanu',"email" => 'bhanu@gloify.com',"contact" => '7697644835') ));
        // }

        $payment_link_details = $api->payment->fetch($transaction_id);

        date('Y-m-d', strtotime(date('Y-m-d'). ' + 5 days'));
        $expire_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d'). ' + 10 days'));
        $amount = number_format($payment_link_details->amount / 100, 2);

        $finserve_payment_details = new TempFinserveTransactionDetailsSearch();
        $finserve_payment_details->invoice_id = ($payment_link_details->invoice_id) ? $payment_link_details->invoice_id : $payment_link_details->id;
        $finserve_payment_details->customer_id = ($payment_link_details->customer_id) ? $payment_link_details->customer_id : "";
        $finserve_payment_details->order_id = 0;//$id_order;
        $finserve_payment_details->payment_id = ($payment_link_details->payment_id) ? $payment_link_details->payment_id : "";

        $finserve_payment_details->transaction_status = $pay_status;
        $finserve_payment_details->expiry_date = "";
        $finserve_payment_details->paid_date  = date("Y-m-d H:i:s",$payment_link_details->created_at);
        $finserve_payment_details->amount_paid = $luggage_price;
        $finserve_payment_details->total_order_amount = $luggage_price;

        $finserve_payment_details->payment_type_status  = 1;
        $finserve_payment_details->short_url = ($payment_link_details->invoice_id) ? $payment_link_details->invoice_id : $payment_link_details->id;

        $finserve_payment_details->description = ($payment_link_details->description) ? $payment_link_details->description : "";
        $finserve_payment_details->order_type  = 'create-super-subscriber-employee';
        //$finserve_payment_details->notes = $payment_link_details->id;
        $finserve_payment_details->created_by = $role_id;
        $finserve_payment_details->save(false);
        // if($finserve_payment_details->save(false)){
        //     $finserve_payment_details->finserve_number = 'FP'.date('mdYHis').$finserve_payment_details->id_finserve;
        //     $finserve_payment_details->save(false);
        // }

        return array("transaction_id" => $finserve_payment_details->id_finserve, "razorpay_link" => $transaction_id);
    }

    /**
     * Function for update subscriber payement transaction
    */
    public function updateSubscriptionTransaction($order_id,$transaction_id,$short_link){
        // update tbl_temp_fineserve_transaction_details
        // update tbl_order_payment_details
        // update tbl_fineserve_transaction_details
        $api = new Api(Yii::$app->params['razorpay_api_key'], Yii::$app->params['razorpay_secret_key']);
        $result =  Yii::$app->db->createCommand("Select * from tbl_temp_finserve_transaction_details where id_finserve = '".$transaction_id."' and short_url = '".$short_link."'")->queryOne();
        if(!empty($result)){
            // $invoiceResult = $api->invoice->fetch($result['invoice_id']);
            $invoiceResult = $api->payment->fetch($result['invoice_id']);
            $finserve_payment_details = new FinserveTransactionDetails();
            $finserve_payment_details->invoice_id = $result->invoice_id;
            $finserve_payment_details->customer_id = isset($invoiceResult->customer_id) ? $invoiceResult->customer_id : "";
            $finserve_payment_details->order_id = $order_id;
            $finserve_payment_details->payment_id = $invoiceResult->id;
            $finserve_payment_details->transaction_status = $result->transaction_status;
            $finserve_payment_details->expiry_date = isset($result->expiry_date) ? date("Y-m-d H:i:s", strtotime($result->expiry_date)) : "";
            $finserve_payment_details->paid_date  = $result->paid_date;
            $finserve_payment_details->amount_paid = ($invoiceResult->amount)/100;
            $finserve_payment_details->total_order_amount = ($invoiceResult->amount)/100;
            $finserve_payment_details->payment_type_status  = 1;
            $finserve_payment_details->short_url = $invoiceResult->id;
            $finserve_payment_details->description = $result->description;
            $finserve_payment_details->order_type  = 'create-super-subscriber-order';
            $finserve_payment_details->created_by = $role_id;
            if($finserve_payment_details->save(false)){
                $finserve_payment_details->finserve_number = 'FP'.date('mdYHis').$finserve_payment_details->id_finserve;
                $finserve_payment_details->save(false);
            }

            $order_payment_details = OrderPaymentDetails::find()->where(['id_order'=>$order_id])->one();
            if(!empty($order_payment_details)){
                $order_payment_details->payment_type = "Online Payment";
                $order_payment_details->payment_status = 'Success';
                $order_payment_details->save(false);
            } else {
                $order_payment_details = new OrderPaymentDetails();
                $order_payment_details->id_order = $order_id;
                $order_payment_details->payment_type = "Online Payment";
                $order_payment_details->id_employee = 0;
                $order_payment_details->payment_status = 'Success';
                $order_payment_details->amount_paid = ($invoiceResult->amount)/100;
                $order_payment_details->value_payment_mode = 'Order Amount';
                $order_payment_details->date_created= date('Y-m-d H:i:s');
                $order_payment_details->date_modified= date('Y-m-d H:i:s');
                $order_payment_details->save(false);
            }
            return true;
        } else {
            return false;
        }
    }

    public function getOutstationCalculation($tokenNo, $confirmation_id, $bagCount=1, $travelType=2, $pincode1="", $pincode2=""){
        $distance = round($this->Get_nearest_pincode_id($pincode1,$pincode2,'K'));
        $subscriptionInfo = Yii::$app->db->createCommand("SELECT * FROM `tbl_subscription_transaction_details` `std` WHERE `subscription_transaction_id` = ".$confirmation_id)->queryOne();
        $OutstationPriceList = $this->getConveyanceCharge($tokenNo,$distance);
        $total_convayance = $OutstationPriceList['total_convayance']; // total convayance price with gst :1260 
        $subscription_cost = $subscriptionInfo['subscription_cost']; // subscription cost : 1000 #46788
        $gst_percent = $subscriptionInfo['gst_percent']; // subscription gst percent : 12
        $total_subscription = round($subscription_cost + (($subscription_cost * $gst_percent) / 100)); // total subscription cost : 1120 #52403
        $no_of_usages = $subscriptionInfo['no_of_usages']; // no of usages : 10 #20
        $gst_per_usages_cost = $total_subscription / $no_of_usages; // per usages cost : 112 #2620
        $per_usages_cost = $subscription_cost / $no_of_usages; //without gst per usages cost
        $overall_remain_usages = $subscriptionInfo['remaining_usages']; //after used remaining usages

        // per order usages according to total_convayance price
        if($travelType == 1){ // for international travel
            $convayance_usages = $this->getSplitUsages($total_convayance / $gst_per_usages_cost); // spend usages according to convayance cost for domestics travel : 12 #1
            $exhaust_usages = ($convayance_usages + $bagCount) * 2; // exhaust usages with no of bags : 13 #2
            $remain_usages = $this->getSplitUsages($overall_remain_usages - $exhaust_usages); // remaining usages : -3 #18 abs()
            $price_breakup = array(
                "subscription_cost" => round($subscription_cost,2),
                "gst_percent" => round($gst_percent,2),
                "convayance_cost_gst" => round($total_convayance,2),
                "subscription_cost_gst" => round($total_subscription,2),
                "no_of_usages" => $no_of_usages,
                "gst_per_usages_cost" => round($gst_per_usages_cost,2),
                "per_usages_cost" => round($per_usages_cost,2),
                "remaining_usages" =>$overall_remain_usages,
                "convayance_usages" => $convayance_usages,
                "exhaust_usages" => $exhaust_usages,
                "remain_usages" => $remain_usages,
            );
            if($remain_usages > 0){ // for +ve usages
                return array(
                    "convayance_usages" => $convayance_usages,
                    "exhaust_usages" => $exhaust_usages,
                    "remain_usages" => $remain_usages,
                    "conveyance_price" => 0,
                    "gst_price" => 0,
                    "gst_conveyance_price" => 0,
                    "price_breakup" => $price_breakup
                );
            } else if($remain_usages < 0){ // for -ve usages
                $gst_conveyance_price = abs($remain_usages) * $gst_per_usages_cost;
                $conveyance_price = abs($remain_usages) * $per_usages_cost;
                return array(
                    "convayance_usages" => $convayance_usages,
                    "exhaust_usages" => $exhaust_usages,
                    "remain_usages" => $remain_usages,
                    "conveyance_price" => round($conveyance_price,2),
                    "gst_price" => round($gst_conveyance_price,2) - round($conveyance_price,2),
                    "gst_conveyance_price" => round($gst_conveyance_price),
                    "price_breakup" => $price_breakup
                );
            } else {
                return array(
                    "convayance_usages" => 0,
                    "exhaust_usages" => 0,
                    "remain_usages" => 0,
                    "conveyance_price" => 0,
                    "gst_price" => 0,
                    "gst_conveyance_price" => 0,
                    "price_breakup" => $price_breakup
                );
            }
        } else if($travelType == 2){ // for domestic travel
            $convayance_usages = $this->getSplitUsages($total_convayance / $gst_per_usages_cost); // spend usages according to convayance cost for domestics travel : 12 #1
            $exhaust_usages = $convayance_usages + $bagCount; // exhaust usages with no of bags : 13 #2
            $remain_usages = $this->getSplitUsages($overall_remain_usages - $exhaust_usages); // remaining usages : -3 #18 abs()
            $price_breakup = array(
                "subscription_cost" => round($subscription_cost,2),
                "gst_percent" => round($gst_percent,2),
                "convayance_cost_gst" => round($total_convayance,2),
                "subscription_cost_gst" => round($total_subscription,2),
                "no_of_usages" => $no_of_usages,
                "gst_per_usages_cost" => round($gst_per_usages_cost,2),
                "per_usages_cost" => round($per_usages_cost,2),
                "remaining_usages" =>$overall_remain_usages,
                "convayance_usages" => $convayance_usages,
                "exhaust_usages" => $exhaust_usages,
                "remain_usages" => $remain_usages,
            );
            if($remain_usages > 0){ // for +ve usages
                return array(
                    "convayance_usages" => $convayance_usages,
                    "exhaust_usages" => $exhaust_usages,
                    "remain_usages" => $remain_usages,
                    "conveyance_price" => 0,
                    "gst_price" => 0,
                    "gst_conveyance_price" => 0,
                    "price_breakup" => $price_breakup
                );
            } else if($remain_usages < 0){ // for -ve usages
                $gst_conveyance_price = abs($remain_usages) * $gst_per_usages_cost;
                $conveyance_price = abs($remain_usages) * $per_usages_cost;
                return array(
                    "convayance_usages" => $convayance_usages,
                    "exhaust_usages" => $exhaust_usages,
                    "remain_usages" => $remain_usages,
                    "conveyance_price" => round($conveyance_price,2),
                    "gst_price" => round($gst_conveyance_price,2) - round($conveyance_price,2),
                    "gst_conveyance_price" => round($gst_conveyance_price,2),
                    "price_breakup" => $price_breakup
                );
            } else {
                return array(
                    "convayance_usages" => 0,
                    "exhaust_usages" => 0,
                    "remain_usages" => 0,
                    "conveyance_price" => 0,
                    "gst_price" => 0,
                    "gst_conveyance_price" => 0,
                    "price_breakup" => $price_breakup
                );
            }
        }
    }

    // get round figure usages
    public function getSplitUsages($value){
        $num = number_format($value, 1, ".", ",");
        $intpart = floor( $num );
        $fraction = $num - $intpart;
        if((0.1 < $fraction) && ($fraction < 0.9)){
            return $intpart + 1;
        } else {
            return $intpart;
        }
    }

    //generate pdf for subscription
    public  function genaratesubscriptionInvoicePdf($order_details, $template_name)
    { 
        ob_start();
       // $path = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'];
       $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];

       // $path = Yii::$app->params['document_root'].'/assets'.'/';
        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details));

        $data = ob_get_clean();
        try
        {
            $file_name = "subscription_Cinfirmation_".time().".pdf";

            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;
            $html2pdf->writeHTML($data);
            $html2pdf->Output($path.$file_name,'F');
            /*Preparing file path and folder path for response */
            //$order_pdf['path'] = Yii::$app->params['site_url'].'assets/'.$file_name;
            $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['express_pdf_path'].$file_name;
            
            //$order_pdf['folder_path'] = Yii::$app->params['document_root'].'/assets'.'/'.$file_name;
            $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].$file_name;
            
            //$order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'].$file_name;
            // print_r($order_pdf);exit;
          
            return $order_pdf;

        }
        catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    public function generatepurchaseinvoicepdf($order_details, $template_name)
    { 
        $file_name = "subscription_invoice_".time().'_'.$order_details['transection_id'].".pdf";

        ob_start();
        //local 
        $path=Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'];
        //$path = Yii::$app->params['document_root'].'/assets'.'/';
        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details));

        $data = ob_get_clean();

        try
        {
            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=16,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;
            


            $html2pdf->writeHTML($data);

            /*this footer will be added into the last of the page , if you want to display in all of the pages then cut this footer
            line and paster above the $html2pdf->writeHTML($data);,then footer will render in all pages*/
            $html2pdf->SetFooter('<div style="padding: 10px;text-align: center;background: #2955a7;color: white;font-size: 15px;position: relative;bottom: 0px;font-style:normal;font-weight:200;">
            Luggage Transfer Simplified
            </div>');
           $html2pdf->Output($path.$file_name,'F');
            //$html2pdf->Output($path."order_".$order_details['order_details']['order']['order_number'].".pdf",'F');

            /*Preparing file path and folder path for response */
            //$order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['order_pdf_path'].'order_'.$order_details['order_details']['order']['order_number'].".pdf";
            $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['order_pdf_path'].$file_name;
            //$order_pdf['folder_path'] = Yii::$app->params['document_root'].'/assets'.'/'.$file_name;
            //print_r($path);exit;

            //local testing
            //$order_pdf['path'] = Yii::$app->params['site_url'].'assets/'.$file_name;
            $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['express_pdf_path'].$file_name;
          

            return $order_pdf;

        }
        catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    /**
     * Function used for send subscription module sms 
     * Required param : {confirmation_number, subscription_name,pay_amount,refund_amount,paid_amount }
    */
    public function subscriptionSmsSent($title,$data,$sentTo){
        switch($title){
            case "purchase_of_subscription" : 
                $sms_content = 'Dear Customer, Purchase of Subscription#'.$data['confirmation_number'].', "'.$data['subscription_name'].'" has been successful. Details of the subscription has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in';
                break;
                
            case "exhaustion_of_subscription" : 
                $sms_content = 'Dear Customer, Subscription#'.$data['confirmation_number'].', "'.$data['subscription_name'].'" usage value if '.$data['pay_amount'].'. Please purchase subscriptions to enjoy CarterX fastrack assistance at airports. Details of the subscription has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in';
                break;

            case "cancellations_with_refund" : 
                $sms_content = "Dear Customer, Subscription#".$data['confirmation_number']." refund charge of ".$data['refund_amount']." has been credited. Details of the service usage has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in";
                break;

            case "cancellations_without_refund" : 
                $sms_content = "Dear Customer, Subscription#".$data['confirmation_number']." cancellation charges are non refundable as per service terms. Details of the service usage has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in";
                break;

            case "validate_activate_subscription_additional" : 
                $sms_content = "Dear Customer, Subscription#".$data['confirmation_number']." additional charge of ".$data['paid_amount']." has been paid for the service usage booked. Details of the service usage has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in";
                break;

            case "validate_activate_subscription_redemption" : 
                $sms_content = "Dear Customer, Subscription#".$data['confirmation_number']." redemption charge of ".$data['paid_amount']." has been paid for the service. Details of the service usage has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in";
                break;

            case "validate_activate_subscription_confirmation" : 
                $sms_content = 'Dear Customer, Purchase of Subscription#'.$data['confirmation_number'].', "'.$data['subscription_name'].'" has been used. Details of the service usage has been sent to your email address. We appreciate your business and look forward to serving you soon. Thank you carterx.in';
                break;
            default:
            $sms_content = "";

            
        }
        $mobile =array();
        foreach($sentTo as $key =>$value){
           
            array_push($mobile , $value);
        }
        for($i =0; $i < count($mobile); $i++){

            User::sendsms('91'.$mobile[$i],$sms_content);
        }
        // User::sendsms($sentTo,$sms_content);
    }

    public  function genarateSubscriptionInvoicePdfTemp($order_details, $template_name,$file_name)
    { 
        define('YII_ENABLE_ERROR_HANDLER', false);
        define('YII_ENABLE_EXCEPTION_HANDLER', false);
        error_reporting("");
        ob_start();
        $path = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'];
        echo Yii::$app->view->render("@app/mail/".$template_name,array('data' => $order_details));//die;
        $data = ob_get_clean();
        try {
            $html2pdf = new \mPDF($mode='MODE_UTF8',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P');
            $html2pdf->setDefaultFont('dejavusans');
            $html2pdf->showImageErrors = false;
            $html2pdf->writeHTML($data);
            $html2pdf->Output($path.$file_name,'F');
            /*Preparing file path and folder path for response */
            $order_pdf['path'] = Yii::$app->params['site_url'].Yii::$app->params['express_pdf_path'].$file_name;
            $order_pdf['folder_path'] = Yii::$app->params['document_root'].'basic/web/'.Yii::$app->params['express_pdf_path'].$file_name;
            return $order_pdf;

        } catch(HTML2PDF_exception $e) {
            echo JSON::encode(array('status' => false, 'message' => $e));
        }
    }

    public function getSubscriptionDetails($confirmation_id){
        if(!empty($confirmation_id)){
            $confirmation_number = Yii::$app->db->createCommand("SELECT std.remaining_balance,std.balence_value,std.subscription_id,std.subscription_transaction_id,std.confirmation_number,std.no_of_usages,std.gst_percent,std.remaining_usages,std.payment_date,std.expire_date,std.redemption_cost,std.subscription_cost,std.paid_amount,ss.subscriber_name,ss.primary_email,ss.secondary_email,ss.primary_contact,ss.secondary_contact,ss.primary_contact,ss.secondary_contact,ss.primary_email,ss.secondary_email,ss.subscription_cost as s_subscription_cost FROM `tbl_subscription_transaction_details` as std  LEFT JOIN `tbl_super_subscription` as ss ON ss.subscription_id = std.subscription_id  WHERE `subscription_transaction_id` = '".$confirmation_id."'")->queryone();
            return $confirmation_number;
        } else {
            return array();
        }
    }

    public function getCorporateCustomerId($customer_id, $airline_id){
        if(!empty($customer_id)) {
            $result = Yii::$app->db->CreateCommand("Select tceam.customerId,fk_airline_id from tbl_customer c left join tbl_corporate_employee_airline_mapping tceam ON tceam.fk_corporate_employee_id = c.id_customer where c.id_customer = '".$customer_id."' ")->queryAll();//and tceam.fk_airline_id = '".$airline_id."'
            if(!empty($result)) {
                foreach($result as $val) {
                    // if($val['fk_airline_id'] == 42){//Id of 'CarterX' airline
                    //     return $val['customerId'];
                    // } else 
                    if($val['fk_airline_id'] == $airline_id){
                        return $val['customerId'];
                    }
                }
                return false;
            }    
        } else {
            return false;
        }
    }

    /**
     * Function for check mapped airline to customer
    */
    public function checkMappedAirline($customerId){
        if(empty($customerId)){
            return array();
        } else {
            $mainArr = array();
            $checkCorporateUser = Yii::$app->db->createCommand("SELECT UPPER(airline_name) as airline_name,airline_id FROM tbl_corporate_employee_airline_mapping ceam LEFT JOIN tbl_airlines a ON a.airline_id = ceam.fk_airline_id where ceam.fk_corporate_employee_id = '".$customerId."'")->queryAll();
            $nameArr = array();
            $idArr = array();
            if(!empty($checkCorporateUser)){
                foreach($checkCorporateUser as $value){
                    array_push($nameArr, $value['airline_name']);
                    array_push($idArr, $value['airline_id']);
                }
                $mainArr = array("airline_name" => $nameArr,"airline_id" => $idArr);
                return !empty($mainArr) ? $mainArr : array();
            } else {
                return array();
            }
        }
    }

    public function emailhiddenformat($email){
        $new_mail = "";
        $new_mails ="";
        $last = explode(".", $email);
        $mail_part1 = explode("@", $email);
        $mail_part2 = substr($mail_part1[0],3); // Sub string after fourth character.
        $new_mail = substr($mail_part1[0],0,3); // Add first four character part.
        $new_mails = substr($mail_part1[1],0,3);
        $new_mails .= str_repeat("*", strlen($new_mails))."."; // Replace *. And add @
      
        $new_mail .= str_repeat("*", strlen($mail_part2))."@".$new_mails; // Replace *. And add @
        
        $new_mail .= $last[1]; 
        return $new_mail;
    }

    public function mobilehiddenformat($mobile){
        return $result = str_repeat("*", strlen(substr($mobile, 0, 6))).substr($mobile, 6, 4);
    }

    /**
     * Function for get/set customer Id
     * Parameters : customer-id, type {1 for customer,......}
    */
    public function createCustomerId($id_customer,$type=1){
        $date = date('ymd'); // current date
        $res = Yii::$app->db->createCommand("SELECT name FROM tbl_customer where id_customer = '".$id_customer."'")->queryOne()['name'];
        if(!empty($res)){
           $string = strtoupper(substr(trim($res," "),0,2));
        } else {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            for ($i = 0; $i < 2; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }
            $string = $randomString;
        }

        if($type == 1){ // Normal customer
            $userType = "CUST";
        } else if($type == 2){ // subscriber customer
            $userType = "SUBS";
        } else if($type == 3){ // corporate customer
            $userType = "CORP";
        }

        return $userType.$date.$string.sprintf("%05d", $id_customer);
    }
    
    public function updatebalencevalue($remaining_value ,$balence_value ,$id){
        $spot_detail = Yii::$app->db->createCommand("UPDATE tbl_subscription_transaction_details set  remaining_balance='".$remaining_value."',
        balence_value='". $balence_value."'  where subscription_transaction_id='".$id."'")->execute();
    }
        
    public function updateCustomerId(){
        $res = Yii::$app->db->createCommand("SELECT * FROM tbl_customer")->queryAll();
        if(!empty($res)){
            foreach($res as $val){
                $type = ($val['fk_role_id'] == 19) ? 3 : (($val['fk_id_employee'] != "") ? 2 : 1);
                $cust_id = $this->createCustomerId($val['id_customer'],$type);
                Yii::$app->db->CreateCommand("UPDATE tbl_customer SET unique_id = '".$cust_id."' where id_customer = '".$val['id_customer']."'")->execute();
            }
        }
    }

    /**
     * Function For manage logs for customer edit status
    */
    public function updateEditCustHistory($data){
        if($data){
            $custHistroy = new CustomerEditHistory;
            $custHistroy->customer_id = $data['customer_id'];
            $custHistroy->description = $data['description'];
            $custHistroy->module_name = $data['module_name'];
            $custHistroy->edit_by = $data['edit_by'];
            $custHistroy->edit_by_name = $data['edit_by_name'];
            $custHistroy->edit_date = date("Y-m-d H:i:s");
            $custHistroy->save(false);
        } else {
            return false;
        }
    }

    /**
     * Function For Get logs of edit customer details
    */
    public function getCustomerHistoryAllDetails($cust_id){
        $customer_edit_details = CustomerEditHistory::find()->where(['customer_id' => $cust_id])->orderBy(['edit_date' => SORT_DESC])->all();
        if($customer_edit_details){
          return $customer_edit_details;
        }else{
          return false;
        }
    }

    /**
     * Function For update subscription tbl for set status
    */
    public function setSubscriptionStatus($subscription_transaction_id,$usages=NULL){
        if($subscription_transaction_id){
            $res = Yii::$app->db->CreateCommand("SELECT * FROM tbl_subscription_transaction_details where subscription_transaction_id = ".$subscription_transaction_id)->queryOne();
            if(!empty($res)){
                Yii::$app->db->CreateCommand("UPDATE tbl_subscription_transaction_details SET add_usages_status = 'enable' WHERE subscription_transaction_id =".$subscription_transaction_id)->execute();
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function updatecustomercorporate($id_customer,$corporate_id){
        $checkBoth = Customer::find()->where(['id_customer' => $id_customer])->One(); // get customer details from Id customer 
        if(!empty($checkBoth)){
            $customerID =$checkBoth['customerId'];
            if(!empty($customerID)){
                $customerID =  $customerID .','.$corporate_id;

            }else{
                $customerID = $corporate_id;

            }
            $spot_detail = Yii::$app->db->createCommand("UPDATE tbl_customer set customerId='".$customerID."' WHERE id_customer =".$id_customer)->execute();
            return true;
        
         
        }
        
    }

    /**
     * Function for getting total purchase subscription count
    */
    public function getPurchessCount($subscription_id){
        if(empty($subscription_id)){
            return 0;
        } else {
            $res = Yii::$app->db->createCommand("SELECT count(*) as total FROM tbl_subscription_transaction_details where subscription_id = ".$subscription_id)->queryAll()[0];
            return ($res['total']) ? $res['total'] : 0;
        }
    }

    public function setOrderStatus($id_order){
        if(empty($id_order)){
            return false;
        } else {
            $result = Order::getorderdetails($id_order);
            echo "<pre>";print_r($result);die;

        }
    }
    public function ticketnumber($id_order,$ticketid){
        if(isset($id_order)){
        $orderdetail = Yii::$app->db->CreateCommand("SELECT order_number FROM tbl_order where id_order = ".$id_order)->queryOne();
        
        $ord_numb = substr($orderdetail['order_number'] ,0,5);
        $ticket_num ="T$ord_numb$ticketid";
        return $ticket_num;
        }
    }

    public function getNameOfId($topic_id ,$id_customer ,$order_id){
        $result = Yii::$app->db->createCommand("select topic_name from tbl_tickets_topic where topic_id = '".$topic_id."'")->queryone();
        $order_detail = Yii::$app->db->createCommand("select order_number from tbl_order where id_order = '".$order_id."'")->queryone();
        $customer_detail = Yii::$app->db->createCommand("select name from tbl_customer where id_customer = '".$id_customer."'")->queryone();

        return array(
            'topic_name'=> $result['topic_name'],
            'order_number'=> $order_detail['order_number'],
            'name'=> $customer_detail['name'],
        );
    }
}
?>