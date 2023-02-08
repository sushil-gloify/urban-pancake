<?php

namespace app\models;
use yii\helpers\Json;
use yii\helpers\Html;
use app\models\OrderPaymentDetails;
use app\models\CorporateDetails;
use app\models\CcQueries;
use app\models\Customer;
use app\models\OrderGroup;
use app\models\ThirdpartyCorporate;
use app\api_v3\v3\models\ThirdpartyCorporateOrderMapping;
use app\api_v3\v3\models\DeliveryServiceType;
use app\api_v2\v2\models\OrderPromoCode;
use app\api_v3\v3\models\OrderMetaDetails;
use app\models\CityOfOperation;
use yii\helpers\ArrayHelper;
use app\models\PickDropLocation;
use app\models\OrderOffers;
use app\models\OrderSmsDetails;
use Yii;

/**
 * This is the model class for table "tbl_order".
 *
 * @property integer $id_order
 * @property string $order_number
 * @property string $ticket
 * @property string $airline_name
 * @property string $flight_number
 * @property string $departure_time
 * @property string $arrival_time
 * @property string $meet_time_gate
 * @property string $special_care
 * @property string $other_comments
 * @property integer $travel_person
 * @property integer $fk_tbl_order_status_id_order_status
 * @property string $order_date
 * @property integer $fk_tbl_order_id_pick_drop_location
 * @property integer $no_of_units
 * @property integer $fk_tbl_order_id_slot
 * @property integer $service_type
 * @property integer $round_trip
 * @property integer $fk_tbl_order_id_customer
 * @property string $payment_method
 * @property string $payment_transaction_id
 * @property string $payment_status
 * @property string $invoice_number
 * @property integer $allocation
 * @property integer $enable_cod
 * @property string $date_created
 * @property string $date_modified
 *
 * @property MallInvoices[] $mallInvoices
 * @property OrderStatus $fkTblOrderStatusIdOrderStatus
 * @property PickDropLocation $fkTblOrderIdPickDropLocation
 * @property Slots $fkTblOrderIdSlot
 * @property Customer $fkTblOrderIdCustomer
 * @property OrderHistory[] $orderHistories
 * @property OrderImages[] $orderImages
 * @property OrderItems[] $orderItems
 * @property OrderSpotDetails[] $orderSpotDetails
 * @property OrderTotal[] $orderTotals
 * @property OrderWaitingCharge[] $orderWaitingCharges
 * @property VehicleSlotAllocation[] $vehicleSlotAllocations
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
    */

    public $luggagePrice;
    public $tax;
    public $in_price;
    public $totalPrice;
    public $termcondition;
    PUBLIC $readdress;
    public $rescheduleType;
    public $hiddenServiceType;
    public $bag_range;
    public $group_order;
    public $order_group;
    public $bag_type;
    public $validate_pincode;
    // public $delivery_date;
    public $modified_amount_data;
    public $date_of_delivery;
    public $passive_tag;
    public $extra_charges;
    public $outstation_extra_charges;
    public $outstation_charge;
    // public $order_transfer;
    public $extr_kilometer;
    public $extr_km;
    public $extr_kms;
    public $per_km_price;
    public $luggageGST;
    public $city_pincode; 
    // public $city_id;
    
    public static function tableName()
    {
        return 'tbl_order';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
         //[['fk_tbl_airport_of_operation_airport_name_id'], 'required','message'=>'Region cannot be blank'],
            [['related_order_id','departure_time', 'arrival_time','meet_time_gate', 'order_date', 'date_created', 'date_modified','sector','weight','excess_bag_amount','location','airasia_receipt','created_by', 'order_transfer','pnr_number','flight_number'], 'safe'],
            // [['pnr_number'],'required'],
            [['other_comments','payment_mode_excess'], 'string'],
            [['corporate_id','order_date'], 'required' ,'on'=>'create_order'],
            [['corporate_id','order_date'], 'required' ,'on'=>'create_order_genral'],
            [['fk_tbl_order_id_slot'], 'required' ,'message'=>'Please select slot','on'=>'create_order_genral'],
            [['corporate_id','order_date', 'fk_tbl_airport_of_operation_airport_name_id', 'service_type', 'fk_tbl_order_id_slot'], 'required', 'on' => 'crate_corporate_general'],
            ['travell_passenger_name', 'email','message'=>'Please Enter Valid Email', 'on'=>'create-invoice'],
            [['order_number', 'luggage_price', 'modified_amount', 'payment_method', 'no_of_units', 'order_date', 'arrival_date', 'departure_date', 'payment_mode_excess', 'airline_name', 'location', 'travell_passenger_name'], 'required','message'=>'Fields cannot be blank.' ,'on'=>'create-invoice'],
            [['fk_tbl_order_id_slot'], 'required' ,'message'=>'Please select slot','on'=>'create_order'],
            [['travel_person', 'fk_tbl_order_status_id_order_status', 'fk_tbl_order_id_pick_drop_location', 'no_of_units', 'service_type', 'round_trip', 'fk_tbl_order_id_customer', 'corporate_id','allocation', 'enable_cod','fk_tbl_airport_of_operation_airport_name_id'], 'integer'],
            //[['travel_person'],'required'],
            [['order_number', 'ticket', 'airline_name', 'flight_number', 'payment_method', 'payment_transaction_id', 'payment_status', 'invoice_number'], 'string', 'max' => 255],
            [['fk_tbl_order_status_id_order_status'], 'exist', 'skipOnError' => true, 'targetClass' => OrderStatus::className(), 'targetAttribute' => ['fk_tbl_order_status_id_order_status' => 'id_order_status']],
            [['fk_tbl_order_id_pick_drop_location'], 'exist', 'skipOnError' => true, 'targetClass' => PickDropLocation::className(), 'targetAttribute' => ['fk_tbl_order_id_pick_drop_location' => 'id_pick_drop_location']],
            [['fk_tbl_order_id_slot'], 'exist', 'skipOnError' => true, 'targetClass' => Slots::className(), 'targetAttribute' => ['fk_tbl_order_id_slot' => 'id_slots']],
            [['pnr_number'],'string', 'min' => 6,'max' => 6],
            [['pnr_number'],'match','pattern' => '/^[A-Za-z0-9]+$/u'],
            [['flight_number'],'match','pattern' => '/^[A-Za-z0-9 -]+$/u'],
            [['travell_passenger_contact'],'number','min' => 10],
            [['travell_passenger_contact'],'match','pattern' => '/^[0-9]+$/u'],
            [['travell_passenger_name'],'match','pattern' => '/^[A-Za-z ]+$/u'],
            // [['flight_number'],'required','max'=> 6, 'min'=>6 ], 
            /*[['fk_tbl_order_id_customer'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::className(), 'targetAttribute' => ['fk_tbl_order_id_customer' => 'id_customer']],*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_order' => 'Id Order',
            'order_number' => 'Order Number',
            'ticket' => 'Ticket',
            'weight' => 'Weight',
            // 'excess_bag_amount' => 'Excess Bag Amount',
            'sector' => 'Sector',
            'airline_name' => 'Airline Name',
            'flight_number' => 'Flight Number',
            'pnr_number' => 'PNR Number',
            'departure_time' => 'Departure Time',
            'arrival_time' => 'Arrival Time',
            'meet_time_gate' => 'Meet Time Gate',
            'excess_bag_amount' => 'Amount collected by Porter',
            'payment_mode_excess' => 'Payment Mode',
            //'special_care' => 'Special Care',
            'other_comments' => 'Other Comments',
            'travel_person' => 'Travel Person',
            'fk_tbl_order_status_id_order_status' => 'Fk Tbl Order Status Id Order Status',
            'order_date' => 'Order Date',
            'fk_tbl_order_id_pick_drop_location' => 'Pick Drop Location',
            'fk_tbl_airport_of_operation_airport_name_id' =>'Region',
            'no_of_units' => 'No Of Units',
            'fk_tbl_order_id_slot' => 'Time Slot',
            'service_type' => 'Service Type',
            'round_trip' => 'Round Trip',
            'fk_tbl_order_id_customer' => 'Fk Tbl Order Id Customer',
            'payment_method' => 'Payment Method',
            'payment_transaction_id' => 'Payment Transaction ID',
            'payment_status' => 'Payment Status',
            'invoice_number' => 'Invoice Number',
            'allocation' => 'Allocation',
            'enable_cod' => 'Enable Cod',
            'date_created' => 'Date Created',
            'date_modified' => 'Date Modified',
        ];
    }

    public function getOrderMetaDetailsRelation()
    {
        return $this->hasMany(OrderMetaDetails::className(), ['orderId' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMallInvoices()
    {
        return $this->hasMany(MallInvoices::className(), ['fk_tbl_mall_invoices_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRelatedOrder()
    {
        return $this->hasOne(Order::className(), ['id_order' => 'related_order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblOrderStatusIdOrderStatus()
    {
        return $this->hasOne(OrderStatus::className(), ['id_order_status' => 'fk_tbl_order_status_id_order_status']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblOrderIdPickDropLocation()
    {
        return $this->hasOne(PickDropLocation::className(), ['id_pick_drop_location' => 'fk_tbl_order_id_pick_drop_location']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblOrderIdSlot()
    {
        return $this->hasOne(Slots::className(), ['id_slots' => 'fk_tbl_order_id_slot']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblOrderIdCustomer()
    {
        return $this->hasOne(Customer::className(), ['id_customer' => 'fk_tbl_order_id_customer']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkTblOrderCorporateId()
    {
        return $this->hasOne(CorporateDetails::className(), ['corporate_detail_id' => 'corporate_id']);
    }

    /**
     * @return \yii\db\ActiveQuerySELECT * FROM `tbl_order` WHERE 1
     */
    public function getOrderHistories()
    {
        return $this->hasMany(OrderHistory::className(), ['fk_tbl_order_history_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderImages()
    {
        return $this->hasMany(OrderImages::className(), ['fk_tbl_order_images_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItems::className(), ['fk_tbl_order_items_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderSpotDetails()
    {
        return $this->hasOne(OrderSpotDetails::className(), ['fk_tbl_order_spot_details_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTotals()
    {
        return $this->hasMany(OrderTotal::className(), ['fk_tbl_order_total_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderWaitingCharges()
    {
        return $this->hasMany(OrderWaitingCharge::className(), ['fk_tbl_order_waiting_charge_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicleSlotAllocations()
    {
        return $this->hasOne(VehicleSlotAllocation::className(), ['fk_tbl_vehicle_slot_allocation_id_order' => 'id_order']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPorterxAllocations()
    {
        return $this->hasOne(PorterxAllocations::className(), ['tbl_porterx_allocations_id_order' => 'id_order']);
    }


     /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderAirport()
    {
        return $this->hasOne(AirportOfOperation::className(), ['airport_name_id' => 'fk_tbl_airport_of_operation_airport_name_id']);
        //print_r($value);exit;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTravellercountrycode()
    {
        return $this->hasone(CountryCode::className(), ['id_country_code' => 'fk_tbl_order_id_country_code']);
    }

    public function getOrdergroup()
    {
        return $this->hasone(OrderGroup::className(), ['id_order' => 'id_order']);
    }

    public function getCorporaterName(){
        $name = \app\models\CorporateDetails::find()
                ->select(['name'])
                ->where(['corporate_detail_id'=>$this->corporate_id])
                ->one();
        return $name->name;        
    }

    public static function getCorporaterNameById($id){
        $name = \app\models\CorporateDetails::find()
                ->select(['name'])
                ->where(['corporate_detail_id'=>$id])
                ->one();

        if($name){
            return $name->name; 
        }else{
            return $id; 
        }
               
    }
    public function getEmailByAirlineId($id){
         $corporateMapping = \app\api_v3\v3\models\AirlineCorporateMapping::find()->where(['fk_airline_id'=>$id])->one();
         $email=[];
         if($corporateMapping){
            $email = \app\models\Employee::find()
                ->select(['email'])
                ->where(['id_employee'=>$corporateMapping->corporate_id])
                ->one();
         }
         

        if($email){
            return $email->email; 
        }else{
            return $id; 
        }
               
    }
    

    public static function getStationNameById($id){
        $name = \app\api_v3\v3\models\Stations::find()
                ->select(['station_name'])
                ->where(['station_id'=>$id])
                ->one();
        if($name){
            return $name->station_name;
        }else{
            return '';
        }
        
    }   


    public function getCustomerNumber($number){
        if($number){
            $number = \app\models\Customer::find()
                ->select(['mobile'])
                ->where(['id_customer'=>$number])
                ->one();
            return $number->mobile;
        }else{
            return $number = '';
        }
    }   

    public function getAssignedPerson($id){
        if($id){
            $spot = \app\models\OrderSpotDetails::find()
                ->select(['person_name'])
                ->where(['fk_tbl_order_spot_details_id_order'=>$id])
                ->one();
            return $spot['person_name'];
        }else{
            return $spot = '';
        }
    }

    public function orderTotal($id_order)
    {
        $items = Yii::$app->db->createCommand("SELECT OI.* FROM tbl_order_items OI WHERE OI.fk_tbl_order_items_id_order='".$id_order."'")->queryAll();
        //print_r($items);exit;
        $i=0;
        foreach($items as $item)
        {
            $extra_price=0;
            $extra_weight=0;
                                    
            $option = $item['fk_tbl_order_items_id_luggage_type'];
            switch ($option) {
                            case ($option == 1 ||$option == 2 ||$option == 3):
                                    $cabin_luggage = Yii::$app->db->createCommand("SELECT OI.*,WR.* FROM tbl_order_items OI JOIN tbl_weight_range WR ON (WR.id_weight_range = OI.fk_tbl_order_items_id_weight_range ) WHERE OI.fk_tbl_order_items_id_order='".$id_order."' AND (OI.fk_tbl_order_items_id_luggage_type=1 OR OI.fk_tbl_order_items_id_luggage_type=2 OR OI.fk_tbl_order_items_id_luggage_type=3)")->queryAll();
                                    $package_deals = Yii::$app->db->createCommand("SELECT PD.* FROM tbl_package_deals PD where PD.fk_tbl_weight_range_id_luggage_type=1 ")->queryAll();
                                    
                                    $cabin_luggage_count = count($cabin_luggage);
                                    //print_r($cabin_luggage_count);exit;
                                    switch($cabin_luggage_count)
                                    {
                                        case 1: 
                                                $base_price=$package_deals[0]['base_price']; 
                                                break;
                                        case 2: 
                                                $base_price=$package_deals[1]['base_price'];  
                                                break;                                                
                                        case 3: 
                                                $base_price=$package_deals[2]['base_price'];  
                                                break;  
                                        case 4: 
                                                $base_price=$package_deals[3]['base_price'];  
                                                break;  
                                        case 5: 
                                                $base_price=$package_deals[4]['base_price'];  
                                                break;  
                                        case 6: 
                                                $base_price=$package_deals[5]['base_price'];  
                                                break;                 
                                    }
                                    foreach ($cabin_luggage as $cabin) {
                                        if($cabin['item_weight'] > 20){
                                            $extra_weight += $cabin['item_weight'] - 20;
                                        }else{
                                            $extra_weight += 0;
                                        }
                                    }
                                    $extra_price = $extra_weight * 10;
                                    //$price[$i]= $base_price + $extra_price;
                                    $price[$i]= ['item_price'=>$base_price + $extra_price,'type'=>1];
                                    //$price[$i]= [$base_price + $extra_price,$option];
                                    break;
                            case 4:
                            
                                    if($item['item_weight']>3)
                                    {
                                        $extra_price = ($item['item_weight'] - 3)*100;
                                        //$price[$i] = 399 + $extra_price;
                                        $price[$i]= ['item_price'=>399 + $extra_price,'type'=>2];
                                        //$price[$i] = [399 + $extra_price,$option];
                                    }
                                    else
                                    {
                                        //$price[$i] = 399;
                                        $price[$i]= ['item_price'=>399 + $extra_price,'type'=>2];
                                        //$price[$i] = [399,$option];
                                    }
                                    break;
                            case 5:
                                    if($item['item_weight']>20)
                                    {
                                        $extra_price = ($item['item_weight'] - 20)*100;
                                        //$price[$i] = 599 + $extra_price;
                                        $price[$i]= ['item_price'=>599 + $extra_price,'type'=>3];
                                        //$price[$i] = [599 + $extra_price,$option];
                                    }
                                    else
                                    {
                                        //$price[$i] = 599;
                                        $price[$i]= ['item_price'=>599 + $extra_price,'type'=>3];
                                        //$price[$i] = [599,$option];
                                    }        
                                    break;
                        default:
                                    echo Json::encode(['status'=>false,'message'=>'Invalid Luggage Type']);
            }
            
            $i++;
        }
        //print_r($price);
        $filtered_type1 = array_filter($price, function($el) { return $el['type']==1; });
        $filtered_type_others = array_filter($price, function($el) { return $el['type']!=1; });
        $price_array[] = array_sum(array_column(array_unique($filtered_type1, SORT_REGULAR), 'item_price'));
        $price_array[] = array_sum(array_column($filtered_type_others, 'item_price'));
        $total_price = array_sum($price_array);
        //print_r($total_price);exit;
        return $total_price;
    }

    public static function getorderdetails($id_order)
    { 
        header('Access-Control-Allow-Origin: *'); 
        $order_details=Yii::$app->db->createCommand("SELECT o.id_order,o.delivery_date,o.city_id,o.corporate_type,o.delivery_type,o.discount_amount,o.order_transfer,o.corporate_id,o.related_order_id,o.fk_tbl_airport_of_operation_airport_name_id as airport_id,o.extra_weight_purched,o.no_of_units,o.order_number,o.created_by,o.created_by_name,o.service_type,o.fk_tbl_order_id_slot as id_slot,o.order_date,o.departure_time,o.departure_date,o.arrival_time,o.arrival_date,o.meet_time_gate,o.delivery_time,o.travel_person,o.reschedule_luggage,o.round_trip,o.travell_passenger_name,o.travell_passenger_contact,o.fk_tbl_order_status_id_order_status as id_order_status,o.order_status,o.corporate_price,o.sector,o.sector_name, o.express_extra_amount, o.outstation_extra_amount,o.weight,o.excess_bag_amount,o.payment_mode_excess,o.modified_amount,o.modified_amount_data, o.luggage_price,o.insurance_number,o.insurance_price,o.updated_insurance,o.amount_paid,o.service_tax_amount,o.flight_number,o.someone_else_document,o.someone_else_document_verification,o.ticket,o.airasia_receipt,o.flight_verification,o.enable_cod,o.signature1,o.signature2,o.payment_method,o.luggage_accepted,o.porter_modified_datetime, o.admin_modified_datetime,o.location,o.order_modified, o.admin_edit_modified, o.admin_modified_amount,o.dservice_type,o.date_created,o.other_comments,o.no_response,o.fk_tbl_order_id_customer,o.fk_tbl_airport_of_operation_airport_name_id as airport_id, o.fk_tbl_order_id_country_code as t_id_country_code,o.delivery_datetime,o.delivery_time_status,o.order_type_str,o.pnr_number,o.confirmation_number,o.pickup_dropoff_point,o.usages_used,o.airport_service,o.terminal_type,o.remaining_usages,o.extra_usages,o.refund_amount, tcc.country_code as traveler_country_code,s.slot_name,s.slot_start_time,s.slot_end_time,s.description,c.id_customer,c.name as customer_name,c.email as customer_email,c.mobile as customer_mobile,c.document as customer_id_proof, c.customer_profile_picture,c.id_proof_verification,c.address_line_1 as customer_address_line_1,c.address_line_2 as customer_address_line_2,c.area as customer_area,c.pincode as customer_pincode, ccc.country_code as c_country_code ,loc.location_name,sn.spot_name,spot.id_order_spot_details,om.dropPersonName,om.dropPersonNumber,om.pickupPersonAddressLine1,om.pickupPersonAddressLine2,om.droparea,om.dropPersonAddressLine1,om.dropPersonAddressLine2,om.dropPincode,om.pickupPersonName,om.pickupPersonNumber,om.pickupPincode, spot.fk_tbl_order_spot_details_id_pick_drop_spots_type as id_pick_drop_spots_type,spot.assigned_person,spot.person_name as location_contact_name, spot.person_mobile_number as location_contact_number, spot.landmark, spot.building_number, spot.address_line_1 as location_address_line_1,spot.address_line_2 as location_address_line_2,spot.area as location_area,spot.landmark,spot.building_number,spot.pincode as location_pincode,spot.hotel_name,spot.booking_confirmation_file,spot.hotel_booking_verification,spot.invoice_verification,spot.business_name,spot.business_contact_number,spot.mall_name,spot.store_name,spot.building_restriction,spot.other_comments, cph.id_contact_person_hotel ,cph.contact_person_name as hotel_contact_person,ZD.outstationCharge as outstationCharge,ZD.taxAmount as taxAmount, ZD.extraKilometer as extra_km_price,o.travell_passenger_email,o.corporate_customer_id FROM tbl_order o LEFT JOIN tbl_country_code tcc ON o.fk_tbl_order_id_country_code = tcc.id_country_code LEFT JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot LEFT JOIN tbl_customer c ON c.id_customer = o.fk_tbl_order_id_customer LEFT JOIN tbl_order_meta_details om ON om.orderId = o.id_order LEFT JOIN tbl_country_code ccc ON c.fk_tbl_customer_id_country_code = ccc.id_country_code LEFT JOIN tbl_pick_drop_location loc ON loc.id_pick_drop_location = o.fk_tbl_order_id_pick_drop_location LEFT JOIN tbl_order_spot_details spot ON spot.fk_tbl_order_spot_details_id_order = o.id_order LEFT JOIN tbl_order_zone_details ZD ON ZD.orderId = o.id_order LEFT JOIN tbl_contact_person_hotel cph ON spot.fk_tbl_order_spot_details_id_contact_person_hotel = cph.id_contact_person_hotel LEFT JOIN tbl_pick_drop_spots_type sn ON sn.id_pick_drop_spots_type = spot.fk_tbl_order_spot_details_id_pick_drop_spots_type WHERE o.id_order='".$id_order."'")->queryOne();

        if(!empty($order_details['building_restriction']) && $order_details['building_restriction'] != NULL && $order_details['building_restriction'] != ' '){
            $unserialized_building_restriction=implode(',',unserialize($order_details['building_restriction']));
            
           /* $building_restriction = Yii::$app->db->createCommand("SELECT br.id_building_restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")
            //->asArray()
            ->queryAll();

            if($building_restriction){

        #$order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'id_building_restriction'));
                 ##$order_details['building_restriction'] = $building_restriction;
        $order_details['building_restriction'] = array_column($building_restriction, 'id_building_restriction');
                 ##print_r($order_details['building_restriction']);exit;
            }/*else{
                $order_details['building_restriction'] = 'No Restrictions';
            }*/
            if($unserialized_building_restriction){
                $building_restriction = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")->queryAll(); 
            
                if($building_restriction){
                    $order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'restriction'));
                }
            }
        }
         $bagrangetype = Yii::$app->db->createCommand("SELECT o.excess_weight,o.bag_weight,o.bag_type,o.fk_tbl_order_items_id_order as id_order,o.barcode,o.passive_tag,o.id_order_item,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_order_items o WHERE 
            o.fk_tbl_order_items_id_order='".$id_order."'")->queryAll();
        // $bagrangetype=[];
        //       $bagweight = Yii::$app->db->createCommand("SELECT  * from tbl_bag_weight_type WHERE id_order='".$id_order."'")->queryAll();

        //       $bag_item = Yii::$app->db->createCommand("SELECT * from tbl_order_items  WHERE fk_tbl_order_items_id_order='".$id_order."'")->queryAll();
            //  foreach($bagweight as $key=>$b){
                
            // // $bagrangetype['bag_weight']="dasdads";

            //     array_push($bagrangetype,array('bag_weight'=>$bagweight[$key]['bag_weight'],'id_order'=>$bagweight[$key]['id_order'],'bag_type'=>$bagweight[$key]['bag_type'],'barcode'=>$bagweight[$key]['barcode'],'id_barcode'=>$bag_item[$key]['fk_tbl_order_items_id_barcode'],'id_order_item'=>$bag_item[$key]['id_order_item']));
                
            //  } 
        // $r = 

        // $bagrangetype = Yii::$app->db->createCommand("SELECT bag.bag_weight,bag.id_order,bag.bag_type,bag.barcode,o.id_order_item,o.barcode from tbl_bag_weight_type bag join tbl_order_items o on o.fk_tbl_order_items_id_order = bag.id_order WHERE bag.id_order='".$id_order."'")->queryAll();
         // $bagrangetype = Yii::$app->db->createCommand("SELECT bag.bag_weight,bag.id_order,bag.id_order,bag.bag_type,bag.barcode,o.id_order_item,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_bag_weight_type bag join tbl_order_items o on o.fk_tbl_order_items_id_order = bag.id_order WHERE bag.id_order='".$id_order."'")->queryAll();

        count($bagrangetype);
        $arrayRange = array();
        //array_push($arrayRage, array('one'=>'15'));
       /* $arr = ['one'=>'15','one'=>'15-20','one'=>'20-25','one'=>'25-30','one'=>'30-40'];
        foreach ($arr as $value) {
            array_push($arrayRange,$value);
        }*/

        array_push($arrayRange,array('one'=>'15'));
        array_push($arrayRange,array('one'=>'15-20'));
        array_push($arrayRange,array('one'=>'20-25'));
        array_push($arrayRange,array('one'=>'25-30'));
        array_push($arrayRange,array('one'=>'30-40'));
       
        //$arrayRange[] = (array)['one'=>'15','two'=>'15-20','three'=>'20-25','four'=>'25-30','five'=>'30-40'];
        

        //$r = array("<15,15-20,20-25,25-30,30-40");
        // if($bagrangetype){

        // }

         // $r= Yii::$app->db->createCommand("SELECT b.range,b.weight from tbl_weight b")->queryAll();
            
          
        $invoices = Yii::$app->db->createCommand("SELECT inv.id_mall_invoices,inv.invoice from tbl_mall_invoices inv WHERE fk_tbl_mall_invoices_id_order='".$id_order."'")->queryAll();
        
          // if(!empty($order_details['sector'])){
          

        // }
        
        $order_item_details = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.excess_weight,oi.fk_tbl_order_items_id_weight_range,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,oi.admin_new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$id_order."' ")->queryAll();

        $order_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_images/';
        if($order_details['reschedule_luggage']==1){
            $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."' OR oi.fk_tbl_order_images_id_order='".$order_details['related_order_id']."'")->queryAll();
        }else{
            $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."'")->queryAll();
        }
        /*code for display order_receipts in app 21/08/2017*/
        $order_receipt_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_receipts/';

        $order_receipt_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_receipt_image_url."',oi.payment_receipt) AS 'image_name',oi.id_order_payment_details,oi.payment_receipt FROM tbl_order_payment_details oi WHERE oi.id_order='".$id_order."'")->queryAll();
        /*code end for display order_receipts in app 21/08/2017*/
        $order_details['total_extra_km_price'] = Order::getExtraKmPrices($order_details['id_order']);
        $booking_data['order']=$order_details;
        // if(isset($order_details['order_transfer']) && $order_details['order_transfer'] == 1){
        //     $booking_data['order']['location_area'] = $booking_data['order']['droparea'];
        //     $booking_data['order']['location_contact_name'] = $booking_data['order']['dropPersonName'];
        //     $booking_data['order']['location_contact_number'] = $booking_data['order']['dropPersonNumber'];
        //     $booking_data['order']['location_address_line_1'] = $booking_data['order']['pickupPersonAddressLine1'];
        //     $booking_data['order']['location_pincode'] = $booking_data['order']['dropPincode'];

        //     $booking_data['order']['travell_passenger_name'] = $booking_data['order']['pickupPersonName'];
        //     $booking_data['order']['travell_passenger_contact'] = $booking_data['order']['pickupPersonNumber'];
        // }
        if(isset($order_details['corporate_id'])){
            $booking_data['corporate_details']=Order::getcorporatedetails($order_details['corporate_id']);
        }

        // if($order_details['corporate_id'] > 0){            
        //     $booking_data['corporate_details']=Order::getcorporatedetails($order_details['corporate_id']);
        //     //$CorporateDetails = Order::getcorporatedetails($order_details['corporate_id']);
        //     $booking_data['order']['customer_name']=$booking_data['corporate_details']['name'];
        //     $booking_data['order']['customer_email']=$booking_data['corporate_details']['default_email'];
        //     $booking_data['order']['customer_mobile']=$booking_data['corporate_details']['default_contact'];
        //     $booking_data['order']['c_country_code']=$booking_data['corporate_details']['countrycode']['country_code'];
        // }
        $booking_data['mall_invoices']=$invoices;
        $booking_data['order']['luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'deleted_status'));
        $booking_data['order']['deleted_luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==1; }),'deleted_status'));
        $booking_data['order']['total_luggage_weight'] = array_sum(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'item_weight'));
        $booking_data['order_items']=$order_item_details;
        $booking_data['order_images']=$order_item_images;
         // $booking_data['bagrangetype']=$r;  
        $booking_data['bagrangetype']=$bagrangetype; 
        $booking_data['bag_range'] = $arrayRange; 
        $booking_data['count'] =  count($bagrangetype);
        $booking_data['order_receipts']=$order_receipt_images;//code in 21/08/2017
        $booking_data['allocation_details']=Order::getAllocationdetails($id_order);
        $booking_data['porterx_details']=Order::getPorterxdetails($id_order);
        $booking_data['order']['picked_up_datetime']=Order::getPickedUpDateTime($id_order);
        return $booking_data;
    }

    
    public static function  getorderdetailsversiontwo($id_order)
    {
        header('Access-Control-Allow-Origin: *'); 
        $order_details=Yii::$app->db->createCommand("SELECT o.id_order,o.delivery_date,o.city_location,o.corporate_type,o.delivery_type,o.order_transfer,o.corporate_id,o.related_order_id,o.extra_weight_purched,o.no_of_units,o.order_number,o.service_type,o.fk_tbl_order_id_slot as id_slot,o.order_date,o.departure_time,o.departure_date,o.arrival_time,o.arrival_date,o.meet_time_gate,o.delivery_time,o.travel_person,o.reschedule_luggage,o.round_trip,o.express_extra_amount,o.outstation_extra_amount,o.travell_passenger_name,o.travell_passenger_contact,o.fk_tbl_order_status_id_order_status as id_order_status,o.order_status,o.corporate_price,o.sector,o.sector_name,o.weight,o.excess_bag_amount,o.payment_mode_excess,o.modified_amount,o.modified_amount_data, o.luggage_price,o.insurance_number,o.insurance_price,o.dservice_type,o.location,o.updated_insurance,o.amount_paid,o.service_tax_amount,o.flight_number,o.someone_else_document,o.someone_else_document_verification,o.ticket,o.airasia_receipt,o.flight_verification,o.enable_cod,o.signature1,o.signature2,o.payment_method,o.luggage_accepted,o.porter_modified_datetime, o.admin_modified_datetime,o.order_modified,o.date_created,o.other_comments,o.no_response,o.fk_tbl_order_id_customer,o.fk_tbl_airport_of_operation_airport_name_id as airport_id, o.fk_tbl_order_id_country_code as t_id_country_code, tcc.country_code as traveler_country_code,s.slot_name,s.slot_start_time,s.slot_end_time,s.description,c.id_customer,c.name as customer_name,c.email as customer_email,c.mobile as customer_mobile,c.document as customer_id_proof, c.customer_profile_picture,c.id_proof_verification,c.address_line_1 as customer_address_line_1,c.address_line_2 as customer_address_line_2,c.area as customer_area,c.pincode as customer_pincode, ccc.country_code as c_country_code ,loc.location_name,sn.spot_name,spot.id_order_spot_details,spot.fk_tbl_order_spot_details_id_pick_drop_spots_type as id_pick_drop_spots_type,spot.assigned_person,spot.person_name as location_contact_name, spot.person_mobile_number as location_contact_number, spot.landmark, spot.building_number, spot.address_line_1 as location_address_line_1,spot.address_line_2 as location_address_line_2,spot.area as location_area,spot.landmark,spot.building_number,spot.pincode as location_pincode,spot.hotel_name,spot.booking_confirmation_file,spot.hotel_booking_verification,om.dropPersonName,om.dropPersonNumber,om.pickupPersonAddressLine1,om.pickupBuildingNumber,om.dropBuildingNumber,om.pickupArea,om.dropPersonAddressLine1,om.dropPincode,om.pickupPersonName,om.pickupPersonNumber,om.pickupPincode,om.droparea,spot.invoice_verification,spot.business_name,spot.business_contact_number,spot.mall_name,spot.store_name,spot.building_restriction,spot.other_comments, cph.id_contact_person_hotel ,cph.contact_person_name as hotel_contact_person,o.travell_passenger_email,o.corporate_customer_id FROM tbl_order o LEFT JOIN tbl_country_code tcc ON o.fk_tbl_order_id_country_code = tcc.id_country_code LEFT JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot LEFT JOIN tbl_customer c ON c.id_customer = o.fk_tbl_order_id_customer LEFT JOIN tbl_country_code ccc ON c.fk_tbl_customer_id_country_code = ccc.id_country_code LEFT JOIN tbl_pick_drop_location loc ON loc.id_pick_drop_location = o.fk_tbl_order_id_pick_drop_location LEFT JOIN tbl_order_meta_details om ON om.orderId = o.id_order LEFT JOIN tbl_order_spot_details spot ON spot.fk_tbl_order_spot_details_id_order = o.id_order LEFT JOIN tbl_contact_person_hotel cph ON spot.fk_tbl_order_spot_details_id_contact_person_hotel = cph.id_contact_person_hotel LEFT JOIN tbl_pick_drop_spots_type sn ON sn.id_pick_drop_spots_type = spot.fk_tbl_order_spot_details_id_pick_drop_spots_type WHERE o.id_order='".$id_order."'")->queryOne();
        
        
        if(!empty($order_details['building_restriction']) && $order_details['building_restriction'] != NULL && $order_details['building_restriction'] != ' '){
            $unserialized_building_restriction=implode(',',unserialize($order_details['building_restriction']));
            
        /* $building_restriction = Yii::$app->db->createCommand("SELECT br.id_building_restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")
            //->asArray()
            ->queryAll();

            if($building_restriction){

        #$order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'id_building_restriction'));
                ##$order_details['building_restriction'] = $building_restriction;
        $order_details['building_restriction'] = array_column($building_restriction, 'id_building_restriction');
                ##print_r($order_details['building_restriction']);exit;
            }/*else{
                $order_details['building_restriction'] = 'No Restrictions';
            }*/

            $building_restriction = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")->queryAll(); 
            
            if($building_restriction){
                $order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'restriction'));
            }


        }
        $bagrangetype = Yii::$app->db->createCommand("SELECT o.excess_weight,o.bag_weight,o.fk_tbl_order_items_id_order as id_order,o.bag_type,o.barcode,o.passive_tag,o.id_order_item,o.excess_weight,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_order_items o WHERE 
            o.fk_tbl_order_items_id_order='".$id_order."' AND o.deleted_status = 0")->queryAll();
        // $bagrangetype=[];
        //       $bagweight = Yii::$app->db->createCommand("SELECT  * from tbl_bag_weight_type WHERE id_order='".$id_order."'")->queryAll();

        //       $bag_item = Yii::$app->db->createCommand("SELECT * from tbl_order_items  WHERE fk_tbl_order_items_id_order='".$id_order."'")->queryAll();
            //  foreach($bagweight as $key=>$b){
                
            // // $bagrangetype['bag_weight']="dasdads";

            //     array_push($bagrangetype,array('bag_weight'=>$bagweight[$key]['bag_weight'],'id_order'=>$bagweight[$key]['id_order'],'bag_type'=>$bagweight[$key]['bag_type'],'barcode'=>$bagweight[$key]['barcode'],'id_barcode'=>$bag_item[$key]['fk_tbl_order_items_id_barcode'],'id_order_item'=>$bag_item[$key]['id_order_item']));
                
            //  } 
        // $r = 

        // $bagrangetype = Yii::$app->db->createCommand("SELECT bag.bag_weight,bag.id_order,bag.bag_type,bag.barcode,o.id_order_item,o.barcode from tbl_bag_weight_type bag join tbl_order_items o on o.fk_tbl_order_items_id_order = bag.id_order WHERE bag.id_order='".$id_order."'")->queryAll();
        // $bagrangetype = Yii::$app->db->createCommand("SELECT bag.bag_weight,bag.id_order,bag.id_order,bag.bag_type,bag.barcode,o.id_order_item,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_bag_weight_type bag join tbl_order_items o on o.fk_tbl_order_items_id_order = bag.id_order WHERE bag.id_order='".$id_order."'")->queryAll();

        count($bagrangetype);
        $arrayRange = array();
        //array_push($arrayRage, array('one'=>'15'));
        /* $arr = ['one'=>'15','one'=>'15-20','one'=>'20-25','one'=>'25-30','one'=>'30-40'];
        foreach ($arr as $value) {
            array_push($arrayRange,$value);
        }*/

        array_push($arrayRange,array('one'=>'15'));
        array_push($arrayRange,array('one'=>'15-20'));
        array_push($arrayRange,array('one'=>'20-25'));
        array_push($arrayRange,array('one'=>'25-30'));
        array_push($arrayRange,array('one'=>'30-40'));
    
        //$arrayRange[] = (array)['one'=>'15','two'=>'15-20','three'=>'20-25','four'=>'25-30','five'=>'30-40'];
        

        //$r = array("<15,15-20,20-25,25-30,30-40");
        // if($bagrangetype){

        // }

        // $r= Yii::$app->db->createCommand("SELECT b.range,b.weight from tbl_weight b")->queryAll();
            
        
        $invoices = Yii::$app->db->createCommand("SELECT inv.id_mall_invoices,inv.invoice from tbl_mall_invoices inv WHERE fk_tbl_mall_invoices_id_order='".$id_order."'")->queryAll();
        
        // if(!empty($order_details['sector'])){
        

        // }
        $promocodes = Yii::$app->db->createCommand("SELECT op.promocode_text,op.promocode_value, op.promocode_type from tbl_order_promo_code op WHERE op.order_id='".$id_order."'")->queryAll();
        if($promocodes){
            foreach ($promocodes as $promo) {
                $order_details['promocode_text'] = $promo['promocode_text'];
                $order_details['promocode_value'] = $promo['promocode_value'];
                $order_details['promocode_type'] = $promo['promocode_type'];
            }
        }
        
        $order_item_details1 = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.excess_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,oi.admin_new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type,lto.group_type as GroupID FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$id_order."' AND oi.deleted_status = 0")->queryAll();
    
        $order_item_details  = [];
            foreach ($order_item_details1 as  $value) {
                if($value['item_weight'] == ''){
                    $value['item_weight'] = 0;
                }
                $value['new_luggage'] = 0;
                $value['offerPriceAtTheTimeOfBooking'] = 100;
                $value['subscequentBagPriceAtBooking'] = 100;
                array_push($order_item_details, $value);
            }
    
        $order_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_images/';
        if($order_details['reschedule_luggage']==1){
            $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."' OR oi.fk_tbl_order_images_id_order='".$order_details['related_order_id']."'")->queryAll();
        }else{
            $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."'")->queryAll();
        }
        /*code for display order_receipts in app 21/08/2017*/
        $order_receipt_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_receipts/';

        $order_receipt_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_receipt_image_url."',oi.payment_receipt) AS 'image_name',oi.id_order_payment_details,oi.payment_receipt FROM tbl_order_payment_details oi WHERE oi.id_order='".$id_order."'")->queryAll();
        /*code end for display order_receipts in app 21/08/2017*/
        
        

        $order_group = Order::getgroupname($order_details['id_order']);
        if($order_group){
            foreach ($order_group as $row) {
                $order_details['OrderGroupID'] = $row->order_group_name;
            }
        }
        $booking_data['order']=$order_details;
        if(isset($order_details['order_transfer']) && $order_details['order_transfer'] == 1){
            $booking_data['order']['location_area'] = $booking_data['order']['pickupArea'];
            $booking_data['order']['location_contact_name'] = $booking_data['order']['dropPersonName'];
            $booking_data['order']['location_contact_number'] = $booking_data['order']['dropPersonNumber'];
            $booking_data['order']['location_address_line_1'] = $booking_data['order']['pickupPersonAddressLine1'];
            $booking_data['order']['location_pincode'] = $booking_data['order']['dropPincode'];

            $booking_data['order']['travell_passenger_name'] = $booking_data['order']['pickupPersonName'];
            $booking_data['order']['travell_passenger_contact'] = $booking_data['order']['pickupPersonNumber'];
            $booking_data['order']['pickupBuilding_number'] = (isset($booking_data['order']['pickupBuildingNumber'])) ? $booking_data['order']['pickupBuildingNumber'] : '';
            $booking_data['order']['dropBuilding_number'] = (isset($booking_data['order']['dropBuildingNumber'])) ? $booking_data['order']['dropBuildingNumber'] : '';
        }
        if(empty($order_details['order_transfer'])){
            $booking_data['order']['order_transfer'] = "3";
        }
        if($order_details['corporate_id'] > 0){            
            $booking_data['corporate_details']=Order::getcorporatedetails($order_details['corporate_id']);
            //$CorporateDetails = Order::getcorporatedetails($order_details['corporate_id']);
            // $booking_data['order']['customer_name']=$booking_data['corporate_details']['name'];
            // $booking_data['order']['customer_email']=$booking_data['corporate_details']['default_email'];
            // $booking_data['order']['customer_mobile']=$booking_data['corporate_details']['default_contact'];
            // $booking_data['order']['c_country_code']=$booking_data['corporate_details']['countrycode']['country_code'];

            $thirdparty_details=Order::getthirdpartycorporatedetails($order_details['corporate_id']);
            if($order_details['corporate_type'] == 1 || $order_details['corporate_type'] == 2){
                $booking_data['order']['bag_limit']=10;
            }else{
                $booking_data['order']['bag_limit']=$thirdparty_details['bag_limit'];
            }
            $booking_data['order']['max_bag_weight']=$thirdparty_details['max_bag_weight'];
        }else{
            $booking_data['order']['max_bag_weight']=0;
        }
        $booking_data['order']['excess_weight_purchased'] = $booking_data['order']['weight'];
        $booking_data['order']['excess_weight'] = ($booking_data['order']['extra_weight_purched'] && $booking_data['order']['extra_weight_purched'] != null) ? $booking_data['order']['extra_weight_purched'] : 0;
        $booking_data['order']['total_weight'] = $booking_data['order']['max_bag_weight'] + $booking_data['order']['excess_weight'];

        $booking_data['order_mapping_details']=Order::getcityid($id_order);
        if($booking_data['order_mapping_details']){
            $booking_data['order']['stateId']= ($booking_data['order_mapping_details']['stateId']) ? $booking_data['order_mapping_details']['stateId'] : "";
            $booking_data['order']['cityId']=$booking_data['order_mapping_details']['cityId'];
        }

        $booking_data['mall_invoices']=$invoices;
        $booking_data['order']['luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'deleted_status'));
        $booking_data['order']['deleted_luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==1; }),'deleted_status'));
        $booking_data['order']['total_luggage_weight'] = array_sum(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'item_weight'));
        $booking_data['order_items']=$order_item_details;
        $booking_data['order_images']=$order_item_images;
        //$booking_data['order_items']['statuss'] = '9999';
        // $booking_data['bagrangetype']=$r;  
        $booking_data['bagrangetype']=$bagrangetype; 
        $booking_data['bag_range'] = $arrayRange; 
        $booking_data['count'] =  count($bagrangetype);
        $booking_data['order_receipts']=$order_receipt_images;//code in 21/08/2017
        $booking_data['allocation_details']=Order::getAllocationdetails($id_order);
        $booking_data['porterx_details']=Order::getPorterxdetails($id_order);
        $booking_data['order']['picked_up_datetime']=Order::getPickedUpDateTime($id_order);
        return $booking_data;
    }

    public static function getorderdetailshistory($id_order)
    {
        header('Access-Control-Allow-Origin: *'); 
        $order_details=Yii::$app->db->createCommand("SELECT o.id_order,o.corporate_type,o.corporate_id,o.related_order_id,o.no_of_units,o.order_number,o.service_type,o.fk_tbl_order_id_slot as id_slot,o.order_date,o.departure_time,o.departure_date,o.arrival_time,o.arrival_date,o.meet_time_gate,o.delivery_time,o.travel_person,o.reschedule_luggage,o.round_trip,o.travell_passenger_name,o.travell_passenger_contact,o.fk_tbl_order_status_id_order_status as id_order_status,o.order_status,o.corporate_price,o.sector,o.weight,o.excess_bag_amount,o.payment_mode_excess,o.modified_amount,o.modified_amount_data, o.luggage_price,o.insurance_number,o.insurance_price,o.updated_insurance,o.amount_paid,o.service_tax_amount,o.flight_number,o.someone_else_document,o.someone_else_document_verification,o.ticket,o.airasia_receipt,o.flight_verification,o.enable_cod,o.signature1,o.signature2,o.payment_method,o.luggage_accepted,o.porter_modified_datetime,o.admin_modified_datetime,o.order_modified,o.date_created,o.other_comments,o.no_response,o.fk_tbl_order_id_customer,o.fk_tbl_airport_of_operation_airport_name_id as airport_id, o.fk_tbl_order_id_country_code as t_id_country_code, tcc.country_code as traveler_country_code,s.slot_name,s.slot_start_time,s.slot_end_time,s.description,c.id_customer,c.name as customer_name,c.email as customer_email,c.mobile as customer_mobile,c.document as customer_id_proof, c.customer_profile_picture,c.id_proof_verification,c.address_line_1 as customer_address_line_1,c.address_line_2 as customer_address_line_2,c.area as customer_area,c.pincode as customer_pincode, ccc.country_code as c_country_code ,loc.location_name,sn.spot_name,spot.id_order_spot_details,spot.fk_tbl_order_spot_details_id_pick_drop_spots_type as id_pick_drop_spots_type,spot.assigned_person,spot.person_name as location_contact_name, spot.person_mobile_number as location_contact_number, spot.landmark, spot.building_number, spot.address_line_1 as location_address_line_1,spot.address_line_2 as location_address_line_2,spot.area as location_area,spot.landmark,spot.building_number,spot.pincode as location_pincode,spot.hotel_name,spot.booking_confirmation_file,spot.hotel_booking_verification,spot.invoice_verification,spot.business_name,spot.business_contact_number,spot.mall_name,spot.store_name,spot.building_restriction,spot.other_comments, cph.id_contact_person_hotel ,cph.contact_person_name as hotel_contact_person,o.travell_passenger_email,o.corporate_customer_id FROM tbl_order o LEFT JOIN tbl_country_code tcc ON o.fk_tbl_order_id_country_code = tcc.id_country_code LEFT JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot LEFT JOIN tbl_customer c ON c.id_customer = o.fk_tbl_order_id_customer LEFT JOIN tbl_country_code ccc ON c.fk_tbl_customer_id_country_code = ccc.id_country_code LEFT JOIN tbl_pick_drop_location loc ON loc.id_pick_drop_location = o.fk_tbl_order_id_pick_drop_location LEFT JOIN tbl_order_spot_details spot ON spot.fk_tbl_order_spot_details_id_order = o.id_order LEFT JOIN tbl_contact_person_hotel cph ON spot.fk_tbl_order_spot_details_id_contact_person_hotel = cph.id_contact_person_hotel LEFT JOIN tbl_pick_drop_spots_type sn ON sn.id_pick_drop_spots_type = spot.fk_tbl_order_spot_details_id_pick_drop_spots_type WHERE o.id_order='".$id_order."'")->queryOne();
        
        if(!empty($order_details['building_restriction']) && $order_details['building_restriction'] != NULL && $order_details['building_restriction'] != ' '){
            $unserialized_building_restriction=implode(',',unserialize($order_details['building_restriction']));
            
           /* $building_restriction = Yii::$app->db->createCommand("SELECT br.id_building_restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")
            //->asArray()
            ->queryAll();

            if($building_restriction){

        #$order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'id_building_restriction'));
                 ##$order_details['building_restriction'] = $building_restriction;
        $order_details['building_restriction'] = array_column($building_restriction, 'id_building_restriction');
                 ##print_r($order_details['building_restriction']);exit;
            }/*else{
                $order_details['building_restriction'] = 'No Restrictions';
            }*/

            $building_restriction = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")->queryAll(); 
            
            if($building_restriction){
                $order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'restriction'));
            }


        }
         $bagrangetype = Yii::$app->db->createCommand("SELECT o.excess_weight,o.bag_weight,o.fk_tbl_order_items_id_order as id_order,o.bag_type,o.barcode,o.passive_tag,o.id_order_item,o.excess_weight,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_order_items o WHERE 
            o.fk_tbl_order_items_id_order='".$id_order."'")->queryAll(); 
        // $bagrangetype=[];
        //       $bagweight = Yii::$app->db->createCommand("SELECT  * from tbl_bag_weight_type WHERE id_order='".$id_order."'")->queryAll();

        //       $bag_item = Yii::$app->db->createCommand("SELECT * from tbl_order_items  WHERE fk_tbl_order_items_id_order='".$id_order."'")->queryAll();
            //  foreach($bagweight as $key=>$b){
                
            // // $bagrangetype['bag_weight']="dasdads";

            //     array_push($bagrangetype,array('bag_weight'=>$bagweight[$key]['bag_weight'],'id_order'=>$bagweight[$key]['id_order'],'bag_type'=>$bagweight[$key]['bag_type'],'barcode'=>$bagweight[$key]['barcode'],'id_barcode'=>$bag_item[$key]['fk_tbl_order_items_id_barcode'],'id_order_item'=>$bag_item[$key]['id_order_item']));
                
            //  } 
        // $r = 

        // $bagrangetype = Yii::$app->db->createCommand("SELECT bag.bag_weight,bag.id_order,bag.bag_type,bag.barcode,o.id_order_item,o.barcode from tbl_bag_weight_type bag join tbl_order_items o on o.fk_tbl_order_items_id_order = bag.id_order WHERE bag.id_order='".$id_order."'")->queryAll();
         // $bagrangetype = Yii::$app->db->createCommand("SELECT bag.bag_weight,bag.id_order,bag.id_order,bag.bag_type,bag.barcode,o.id_order_item,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_bag_weight_type bag join tbl_order_items o on o.fk_tbl_order_items_id_order = bag.id_order WHERE bag.id_order='".$id_order."'")->queryAll();

        count($bagrangetype);
        $arrayRange = array();
        //array_push($arrayRage, array('one'=>'15'));
       /* $arr = ['one'=>'15','one'=>'15-20','one'=>'20-25','one'=>'25-30','one'=>'30-40'];
        foreach ($arr as $value) {
            array_push($arrayRange,$value);
        }*/

        array_push($arrayRange,array('one'=>'15'));
        array_push($arrayRange,array('one'=>'15-20'));
        array_push($arrayRange,array('one'=>'20-25'));
        array_push($arrayRange,array('one'=>'25-30'));
        array_push($arrayRange,array('one'=>'30-40'));
       
        //$arrayRange[] = (array)['one'=>'15','two'=>'15-20','three'=>'20-25','four'=>'25-30','five'=>'30-40'];
        

        //$r = array("<15,15-20,20-25,25-30,30-40");
        // if($bagrangetype){

        // }

         // $r= Yii::$app->db->createCommand("SELECT b.range,b.weight from tbl_weight b")->queryAll();
            
          
        $invoices = Yii::$app->db->createCommand("SELECT inv.id_mall_invoices,inv.invoice from tbl_mall_invoices inv WHERE fk_tbl_mall_invoices_id_order='".$id_order."'")->queryAll();
        
          // if(!empty($order_details['sector'])){
          

        // }
        $order_item_details1 = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.item_weight,oi.excess_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,oi.admin_new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type,lto.group_type as GroupID FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$id_order."'")->queryAll();
            $order_item_details  = [];
            foreach ($order_item_details1 as  $value) {
                $value['offerPriceAtTheTimeOfBooking'] = 100;
                $value['subscequentBagPriceAtBooking'] = 100;
                array_push($order_item_details, $value);
            }
       
        $order_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_images/';
        if($order_details['reschedule_luggage']==1){
            $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."' OR oi.fk_tbl_order_images_id_order='".$order_details['related_order_id']."'")->queryAll();
        }else{
            $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."'")->queryAll();
        }
        /*code for display order_receipts in app 21/08/2017*/
        $order_receipt_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_receipts/';

        $order_receipt_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_receipt_image_url."',oi.payment_receipt) AS 'image_name',oi.id_order_payment_details,oi.payment_receipt FROM tbl_order_payment_details oi WHERE oi.id_order='".$id_order."'")->queryAll();
        /*code end for display order_receipts in app 21/08/2017*/
        
        $order_promo = Order::getpromocodenames($id_order);
        if($order_promo){
            foreach ($order_promo as $promo) {
                $order_details['promocode_text'] = $promo->promocode_text;
                $order_details['promocode_value'] = $promo->promocode_value;
                $order_details['promocode_type'] = $promo->promocode_type;
            }
        }

        $order_group = Order::getgroupname($order_details['id_order']);
        if($order_group){
            foreach ($order_group as $row) {
                $order_details['OrderGroupID'] = $row->order_group_name;
            }
        }
        $booking_data['order']=$order_details;
        
        if($order_details['corporate_id'] > 0){            
            $booking_data['corporate_details']=Order::getcorporatedetails($order_details['corporate_id']);
            //$CorporateDetails = Order::getcorporatedetails($order_details['corporate_id']);
            $booking_data['order']['customer_name']=$booking_data['corporate_details']['name'];
            $booking_data['order']['customer_email']=$booking_data['corporate_details']['default_email'];
            $booking_data['order']['customer_mobile']=$booking_data['corporate_details']['default_contact'];
            $booking_data['order']['c_country_code']=$booking_data['corporate_details']['countrycode']['country_code'];
        }
        $booking_data['mall_invoices']=$invoices;
        $booking_data['order']['luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'deleted_status'));
        $booking_data['order']['deleted_luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==1; }),'deleted_status'));
        $booking_data['order']['total_luggage_weight'] = array_sum(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'item_weight'));
        $booking_data['order_items']=$order_item_details;
        $booking_data['order_images']=$order_item_images;
        //$booking_data['order_items']['statuss'] = '9999';
         // $booking_data['bagrangetype']=$r;  
         $booking_data['bagrangetype']=$bagrangetype; 
         $booking_data['bag_range'] = $arrayRange; 
        $booking_data['count'] =  count($bagrangetype);
        $booking_data['order_receipts']=$order_receipt_images;//code in 21/08/2017
        $booking_data['allocation_details']=Order::getAllocationdetails($id_order);
        $booking_data['porterx_details']=Order::getPorterxdetails($id_order);
        $booking_data['order']['picked_up_datetime']=Order::getPickedUpDateTime($id_order);
        return $booking_data;
    }

    public static function getcorporatedetails($corporate_id)
    {
        $details = CorporateDetails::find()->from('tbl_corporate_details t')
                                           ->joinwith(['countrycode'=>function($q){
                                                $q->from('tbl_country_code c1');
                                            }])
                                           ->where(['corporate_detail_id'=>$corporate_id])
                                           ->one();
        return $details;
    }

    public static function getcityid($id_order)
    {
        $order_mapping_details = ThirdpartyCorporateOrderMapping::find()
                                           ->where(['order_id'=>$id_order])
                                           ->one();
        return $order_mapping_details;
    }

    public static function getthirdpartycorporatedetails($corporate_id)
    {
        $thirdparty_details = ThirdpartyCorporate::find()
                                           ->where(['fk_corporate_id'=>$corporate_id])
                                           ->one();
        return $thirdparty_details;
    }

    public static function getcorporatedocuments($corporate_id)
    {
        $documents = CorporateDocuments::find()->where(['corporate_detail_id'=>$corporate_id])
                                           ->select('document')
                                           ->all();
        return $documents;
    }
    public static function getgroupname($order_id)
    {
        $group_name = OrderGroup::find()->where(['id_order'=>$order_id])
                                           ->select('order_group_name')
                                           ->all();
        return $group_name;
    }  

    public static function getpromocodenames($order_id)
    {
        $order_promo = OrderPromoCode::find()->where(['order_id'=>$order_id])
                                           ->select('promocode_text','promocode_value','promocode_type')
                                           ->all();
        return $order_promo;
    }
    public static function getcustomername($number)
    {
        $customer_name = Customer::find()->where(['mobile'=>$number])
                                           ->select('name')
                                           ->one();
        return $customer_name;
    }

    public static function getPickedUpDateTime($id_order)
    {
        $pickedHistory=OrderHistory::find()->where(['to_tbl_order_status_id_order_status'=>8])->one();
        return empty($pickedHistory) ? '' : $pickedHistory['date_created'];
    }

    public static function getOrderPrice($id_order)
    {
        $order_price_breakup = Yii::$app->db->createCommand("SELECT ot.* FROM tbl_order_total ot WHERE ot.fk_tbl_order_total_id_order='".$id_order."'")->queryAll();
        return $order_price_breakup;
    }

    public static function getOrderPrice1($id_order)
    {
        $order_price_breakup = Yii::$app->db->createCommand("SELECT ot.* FROM tbl_order_total ot WHERE ot.fk_tbl_order_total_id_order='".$id_order."'")->queryAll();
        $order_det = Order::find()->select(['id_order','luggage_price' ,'insurance_price', 'service_tax_amount'])->where(['id_order'=>$id_order])->one();
        $order_price_breakup1=[];
        if(!empty($order_price_breakup)){
            foreach ($order_price_breakup as $order_price) {
                if($order_price['code']== 'sub_order_amount'){
                    $order_price['price'] = $order_det['luggage_price'] - $order_det['insurance_price'] - $order_det['service_tax_amount'];
                    $order_price_breakup1[] = $order_price;
                }else{
                    $order_price_breakup1[] = $order_price;
                }
            }
        }
        return $order_price_breakup1; 
    }

    public static function getPaymentHistory($id_order)
    {
        $payments = OrderPaymentDetails::find()->where(['id_order'=>$id_order,'payment_status'=>['Success','Refunded']])->all();
        return $payments;
    }

    public static function getAllocationdetails($id_order)
    {
        header('Access-Control-Allow-Origin: *');
        $allocation = Yii::$app->db->createCommand("SELECT VS.fk_tbl_vehicle_slot_allocation_id_order as id_order,LV.fk_tbl_labour_vehicle_allocation_id_employee as id_employee_allocated,E.name as porter_name,E.employee_profile_picture,E.mobile as porter_contact FROM tbl_vehicle_slot_allocation VS INNER JOIN tbl_labour_vehicle_allocation LV ON LV.fk_tbl_labour_vehicle_allocation_id_vehicle = VS.fk_tbl_vehicle_slot_allocation_id_vehicle INNER JOIN tbl_employee E ON E.id_employee = VS.fk_tbl_vehicle_slot_allocation_id_employee WHERE VS.fk_tbl_vehicle_slot_allocation_id_order='".$id_order."'")->queryOne();
        if(empty($allocation))
        {
            $allocation_details = (object)[];
        }
        else
        {
            $allocation_details = $allocation;
        }
        //print_r($allocation_details);exit;
        return $allocation_details;
    }

    public static function getPorterxdetails($id_order)
    {
        $porterx_details = Yii::$app->db->createCommand("SELECT px.*,e.name as porterx_name,e.employee_profile_picture,e.mobile as porterx_contact FROM tbl_porterx_allocations px LEFT JOIN tbl_employee e ON e.id_employee = px.tbl_porterx_allocations_id_employee WHERE px.tbl_porterx_allocations_id_order=".$id_order)->queryOne();
        if(empty($porterx_details))
        {
            $poretx_det = (object)[];
        }
        else
        {
            $poretx_det = $porterx_details;
        }
        return $poretx_det;
    }

    public static function getOrderStatusHistory($id_order)
    {
        $order_history = Yii::$app->db->createCommand("SELECT oh.* FROM tbl_order_history oh WHERE oh.fk_tbl_order_history_id_order='".$id_order."'")->queryAll();
        return $order_history;
    }

    public static function getIsundelivered($id_order)
    {
        $order_history = Yii::$app->db->createCommand("SELECT oh.* FROM tbl_order_history oh WHERE oh.fk_tbl_order_history_id_order='".$id_order."'")->queryAll();
        $order_status = [];
        if(!empty($order_history)){
            foreach ($order_history as $OH) {
                $order_status[] = $OH['to_tbl_order_status_id_order_status'];
              }
          }
        if(in_array(23, $order_status)){
            return true;
        }else{
            return false;
        } 
    }

    public static function getInsuranceAmount($order_details_item)
    { 
         $insurance = 0;
         if($order_details_item){
            foreach ($order_details_item as $key => $value) {
                if($value['id_luggage_type'] == 1 || $value['id_luggage_type'] == 2 || $value['id_luggage_type'] == 3 || $value['id_luggage_type'] == 5 || $value['id_luggage_type'] == 6){
                    $insurance += 4;
                }else{
                    $insurance += 8;
                }
            }
        }
        return  $insurance;
    }


    public static function getIscancelled($id_order)
    {
        $order_history = Yii::$app->db->createCommand("SELECT oh.* FROM tbl_order_history oh WHERE oh.fk_tbl_order_history_id_order='".$id_order."'")->queryAll();
        $order_status = [];
        if(!empty($order_history)){
            foreach ($order_history as $OH) {
                $order_status[] = $OH['to_tbl_order_status_id_order_status'];
              }
          }
        if(in_array(21, $order_status)){
            return true;
        }else{
            return false;
        } 
    }

    // public function getCCQuries($id_order)
    // {
    //     header('Access-Control-Allow-Origin: *');
    //     $cc_queries = Yii::$app->db->createCommand("SELECT ccq.* FROM tbl_cc_queries ccq WHERE ccq.fk_tbl_cc_queries_id_order='".$id_order."' and iscomment = 0")->queryAll();
    //     return $cc_queries;
    // }

    public function getCCQuries($id_order)
    {
        header('Access-Control-Allow-Origin: *');
        $cc_queries = Yii::$app->db->createCommand("SELECT ccq.*,emp.name,emp.id_employee FROM tbl_cc_queries ccq join tbl_employee emp ON emp.id_employee = ccq.fk_tbl_cc_queries_id_employee WHERE ccq.fk_tbl_cc_queries_id_order='".$id_order."'")->queryAll();
        return $cc_queries;
    }

    public function getCComments($id_order)
    {
        header('Access-Control-Allow-Origin: *');
        $ccomments = CcQueries::find()->where(['iscomment' => 1, 'from_admin' => 0, 'fk_tbl_cc_queries_id_order'=>$id_order])
                                      ->orderby('date_created DESC')
                                      ->all();
        return $ccomments;
    }

    public function getCAComments($id_order)
    {
        header('Access-Control-Allow-Origin: *');
        $cacomments = CcQueries::find()->where(['iscomment' => 1, 'from_admin' => 1, 'fk_tbl_cc_queries_id_order'=>$id_order])
                                       ->orderby('date_created DESC')
                                       ->all();
        return $cacomments;
    }

    public static function getcustomerstatus($id_order_status, $service_type, $order_id)
    { 
        //header('Access-Control-Allow-Origin: *');
        $order = Order::find()->where('id_order = :id_order', [':id_order' => $order_id])->one();
        if($service_type==1){
            //$customer_status = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$id_order_status."' AND customer_status = 1 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
            if($order['reschedule_luggage'] == 1 && $order['fk_tbl_order_status_id_order_status'] != 20)
            {
                $customer_status = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$id_order_status."' AND reschedule_status = 1 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
            }
            else
            {
                $customer_status = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$id_order_status."' AND customer_status = 1 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
            }
            if($id_order_status==20){
                $previous_status_id = Yii::$app->db->createCommand("SELECT from_tbl_order_status_id_order_status from tbl_order_history where fk_tbl_order_history_id_order='".$order_id."' and to_tbl_order_status_id_order_status='".$id_order_status."'")->queryOne();
                $previous_status_det = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$previous_status_id['from_tbl_order_status_id_order_status']."' AND customer_status = 1 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
                $customer_status['previous_status_id']=$previous_status_det['customer_id_order_status'];
                $customer_status['previous_status_name']=$previous_status_det['status_name'];        
            }
        }else{
            //$customer_status = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$id_order_status."' AND customer_status_from_airport = 2 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
            if($order['reschedule_luggage'] == 1 && $order['fk_tbl_order_status_id_order_status'] != 20)
            {
                $customer_status = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$id_order_status."' AND reschedule_status_from_airport = 2 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
                //print_r($customer_status);exit;
            }
            else
            {
                $customer_status = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$id_order_status."' AND customer_status_from_airport = 2 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
            }
            if($id_order_status==20){
                $previous_status_id = Yii::$app->db->createCommand("SELECT from_tbl_order_status_id_order_status from tbl_order_history where fk_tbl_order_history_id_order='".$order_id."' and to_tbl_order_status_id_order_status='".$id_order_status."'")->queryOne();
                $previous_status_det = Yii::$app->db->createCommand("SELECT MAX(id_order_status) as customer_id_order_status,status_name FROM tbl_order_status WHERE id_order_status<='".$previous_status_id['from_tbl_order_status_id_order_status']."' AND customer_status_from_airport = 2 GROUP BY id_order_status ORDER BY id_order_status DESC")->queryOne();
                $customer_status['previous_status_id']=$previous_status_det['customer_id_order_status'];
                $customer_status['previous_status_name']=$previous_status_det['status_name'];        
            }
        }
        //print_r($customer_status);exit;
        return $customer_status;
    }

    public function randomnumbers($size)
    {
        $alpha_key = '';
        $keys = range('A', 'Z');

        for ($i = 0; $i < 2; $i++) {
            $alpha_key .= $keys[array_rand($keys)];
        }

        $length = $size - 2;

        $key = '';
        $keys = range(0, 9);

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
        return $alpha_key . $key;
    }

    /*function to check porterx assigned for an order or not*/
    public function is_porterx_assigned()
    {
        $porterx_assign=Yii::$app->db->createCommand("SELECT pa.* FROM tbl_porterx_allocations pa WHERE pa.tbl_porterx_allocations_id_order=".$this->id_order)->queryOne();
        if(empty($porterx_assign)){
            return 0;
        }else{
            return $porterx_assign['tbl_porterx_allocations_id'];
        }
    }

    public function getDocumentVerification()
    {
        $customer=Customer::find()->where(['mobile'=>$this->travell_passenger_contact])->one();
        if(!empty($customer)){
            return ($customer['id_proof_verification'] == 0 ?  Html::a('Not Verified',['/customer/update','id'=>$customer->id_customer]) : $customer['id_proof_verification'] == 1 ) ? Html::a('Verified',['/customer/update','id'=>$customer->id_customer]) : Html::a('Rejected',['/customer/update','id'=>$customer->id_customer]);
        }else{
            return 'Not Registered';
        }
    }


    /*update status history for a order*/
    public static function updatestatus($id_order,$status_id, $status_name)
    {
        $last_history = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order])
                                            ->orderby('id_order_history DESC ')
                                            ->one();
        $last_status_id = '';
        $last_status_name = '';
        if(!empty($last_history))
        {
            $last_status_id = $last_history['to_tbl_order_status_id_order_status'];
            $last_status_name = $last_history['to_order_status_name'];
        }
        $new_order_history = [ 'fk_tbl_order_history_id_order'=>$id_order,
                            'from_tbl_order_status_id_order_status'=>$last_status_id,
                            'from_order_status_name'=>$last_status_name,
                            'to_tbl_order_status_id_order_status'=>$status_id,
                            'to_order_status_name'=>$status_name,
                            'date_created'=> date('Y-m-d H:i:s')
                           ];
        $order_history = Yii::$app->db->createCommand()->insert('tbl_order_history',$new_order_history)->execute();
        return true;

    }


    /*get delivery date time*/
    public function getDeliverydate()
    {
        $last_history = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$this->id_order,'to_tbl_order_status_id_order_status'=>18])
                                            ->orderby('id_order_history DESC ')
                                            ->one();
        if(!empty($last_history))
        {
            return date('Y-m-d H:i:s', strtotime($last_history['date_created']));
        }else{
            return '-';
        }
    }

    public function getPassiveTags($id)
    {
        $passive_tag=OrderItems::find()->select('passive_tag')->where(['fk_tbl_order_items_id_order'=>$id])->all();
        //print_r($passive_tag);exit;
        $str='';
        if($passive_tag){
            foreach ($passive_tag as $key => $tag) {
                if($tag['passive_tag']){
                    $str .= $tag['passive_tag']."  " ; 
                     
                }
                
            }
        }
        return $str;
    }

    function getluggagecount()
    {
        $order_item_details = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.item_weight,oi.excess_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,oi.admin_new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$this->id_order."'")->queryAll();
        $lcount = 0;
        if(!empty($order_item_details)){
            $lcount = count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'deleted_status'));
            return $lcount;
        }
        return $lcount;
    }


    public static function getdropdatetime($id_order)
    {
        $deliverydetail = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order,'to_tbl_order_status_id_order_status'=>18])->one();
        if(!empty($deliverydetail))
        {
            return date("F j, Y, g:i a", strtotime($deliverydetail['date_created']));
        }else{
            return '-';
        }
    }
    public static function getdropdatetimeforreschdule($id_order)
    {
        $deliverydetail = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order,'to_tbl_order_status_id_order_status'=>20])->one();
        if(!empty($deliverydetail))
        {
            return date("F j, Y, g:i a", strtotime($deliverydetail['date_created']));
        }else{
            return '-';
        }
    }
    public static function getdropdatetimeforundelivered($id_order)
    {
        $deliverydetail = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order,'to_tbl_order_status_id_order_status'=>23])->one();
        if(!empty($deliverydetail))
        {
            return date("F j, Y, g:i a", strtotime($deliverydetail['date_created']));
        }else{
            return '-';
        }
    }


    public static function getpickdatetime($id_order)
    {
        $pickupdetail = Order::find()->where(['id_order'=>$id_order])->select('porter_modified_datetime')->one();
        if($pickupdetail['porter_modified_datetime'] != NULL)
        {
            return date("F j, Y, g:i a", strtotime($pickupdetail['porter_modified_datetime']));
        }else{
            return '-';
        }
    }

    public static function getpickdate($id_order)
    {
        $pickupdetail = Order::find()->where(['id_order'=>$id_order])->select('porter_modified_datetime')->one();
        if($pickupdetail['porter_modified_datetime'] != NULL)
        {
            return date("F j, Y", strtotime($pickupdetail['porter_modified_datetime']));
        }else{
            return '-';
        }
    }

    public static function checkpromocode($id_order)
    {
        $promocodes = OrderPromoCode::find()->where(['order_id'=>$id_order])->select('promocode_value')->one();
        if($promocodes['promocode_value'] != NULL)
        {
            return $promocodes['promocode_value'];
        }else{
            return '';
        }
    }

    public static function getpicktime($id_order)
    {
        $pickupdetail = Order::find()->where(['id_order'=>$id_order])->select('porter_modified_datetime')->one();
        if($pickupdetail['porter_modified_datetime'] != NULL)
        {
            return date("g:i a", strtotime($pickupdetail['porter_modified_datetime']));
        }else{
            return '-';
        }
    }

    public static function getdropdate($id_order)
    {
        $deliverydetail = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order,'to_tbl_order_status_id_order_status'=>18])->one();
        if(!empty($deliverydetail))
        {
            return date("F j, Y", strtotime($deliverydetail['date_created']));
        }else{
            return '-';
        }
    }

    public static function getdroptime($id_order)
    {
        $deliverydetail = OrderHistory::find()->where(['fk_tbl_order_history_id_order'=>$id_order,'to_tbl_order_status_id_order_status'=>18])->one();
        if(!empty($deliverydetail))
        {
            return date("g:i a", strtotime($deliverydetail['date_created']));
        }else{
            return '-';
        }
    }

    public function getLuggagePrice($order_id){
        $order_items = \app\models\OrderItems::find()
                ->select(['item_price'])
                ->where(['fk_tbl_order_items_id_order'=>$order_id, 'deleted_status' => 0])
                ->all();
        $count = 0;
        if($order_items){
            foreach ($order_items as $key => $value) {
                $count += $value['item_price'];
            }
        }
        return $count;        
    }


    public function getPreviousOrderIntialAmount($order_id){
         $pickupdetail = Order::find()->where(['id_order'=>$order_id])->select('amount_paid')->one(); 
         //print_r($pickupdetail);exit;
         $amount_paid=0;
        if($pickupdetail){ 
                $amount_paid = $pickupdetail->amount_paid; 
        }
       //print_r($amount_paid);exit;
        return $amount_paid;        
    }


    public function gettotalamountwithAmountmodification($prev_order_id,$order_id){
         $pickupdetail = Order::find()->where(['id_order'=>$prev_order_id])->select('modified_amount')->one(); 
         //print_r($pickupdetail->amount_paid);exit;
         $totalamount=0;
        if($pickupdetail){ 
            $neworder = Order::find()->where(['id_order'=>$order_id])->select('insurance_price,amount_paid')->one(); 
            $totalamount=$neworder->amount_paid+$neworder->insurance_price+$pickupdetail->modified_amount;
            //print_r($pickupdetail->modified_amount);exit;
              
        }
       //print_r($totalamount);exit;
        return $totalamount;        
    }

    public function getNumberBagAfterModification($order_id){
        $order_items = \app\models\OrderItems::find()
                ->select(['item_price'])
                ->where(['fk_tbl_order_items_id_order'=>$order_id, 'deleted_status' => 0])
                ->all();
        // echo "<pre>";print_r($order_items);exit;
        // $count = 0;
        if($order_items){
            $number_of_count = count($order_items);
        }else{
            $number_of_count = 0;
        }
        return $number_of_count;        
    }

    public function getOutstationPrice($order_id){
        $outstation_charge = \app\api_v3\v3\models\OrderZoneDetails::find()
                ->select(['outstationCharge'])
                ->where(['orderId'=>$order_id])
                ->one();
        
        return ($outstation_charge) ? $outstation_charge->outstationCharge : 0;        
    }

    public function getExtraKm($order_id){
        $extr_km = \app\api_v3\v3\models\OrderZoneDetails::find()
                ->select(['extraKilometer'])
                ->where(['orderId'=>$order_id])
                ->one();

        return ($extr_km) ? $extr_km->extraKilometer : 0;        
    }

    public function getExtraKmPrice($order_id){
        $state = \app\api_v3\v3\models\OrderZoneDetails::find()
                ->select(['stateId', 'extraKilometer'])
                ->where(['orderId'=>$order_id])
                ->one();
        if($state){
            $km_charge = \app\api_v3\v3\models\State::find()
                ->select(['extraKilometerPrice'])
                ->where(['idState'=>$state->stateId])
                ->one();

            $extr_km = ($state) ? $state->extraKilometer : 0;
            $km_charge = ($km_charge) ? $km_charge->extraKilometerPrice : 0;

            $extraKilometer = $extr_km * $km_charge;
        }else{
            $extraKilometer = '';   
        }
        

        return $extraKilometer;        
    }

    public static function getExtraKmPrices($order_id){
        $state = \app\api_v3\v3\models\OrderZoneDetails::find()
                ->select(['stateId', 'extraKilometer'])
                ->where(['orderId'=>$order_id])
                ->one();
        if($state){
            $km_charge = \app\api_v3\v3\models\State::find()
                ->select(['extraKilometerPrice'])
                ->where(['idState'=>$state->stateId])
                ->one();

            $extr_km = ($state) ? $state->extraKilometer : 0;
            $km_charge = ($km_charge) ? $km_charge->extraKilometerPrice : 0;

            $extraKilometer = $extr_km * $km_charge;
        }else{
            $extraKilometer = '';   
        }
        

        return $extraKilometer;        
    }

    /*
    ** Function to Calculate the Service Tax
    */

    public function Calculate_service_tax($baseprice) {
        $service =  (float)$baseprice * (Yii::$app->params['gst_percent']/100);
        return $service;
    }

     /*
    ** Function to Calculate the Service Tax
    */

     public function Calculate_coporate_service_tax($baseprice, $fk_corporate_id) {
         $service_tax = ThirdpartyCorporate::find()->where(['fk_corporate_id'=>$fk_corporate_id])->select('gst')->one();
         $service =  $baseprice * ($service_tax->gst/100);
        //  $service =  is_numeric($baseprice) * ($service_tax->gst/100);
         return $service;
     }

     /*
     ** Function to Calculate the Insurance Tax
     */
     
     public function Calculate_insurance_tax($insuranceprice) {
         $insurance_price_tax =  $insuranceprice * 0.18;
         return $insurance_price_tax;
     }


    public function unreadCount()
    {
        $role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        $from_admin = in_array($role_id, [1,2]) ? 0 : 1;
        $message_count = CcQueries::find()->select(['COUNT(*) AS count'])->where(['iscomment' => 1, 'from_admin' => $from_admin, 'fk_tbl_cc_queries_id_order'=>$this->id_order, 'is_read'=>0])->all();
        return $message_count[0]['count'];
    }


    /**
    * Function to check if order was in yet to be confirmed status
    * before assigning to porter or porterx
    */
    public static function checkIsYettoConfirmBeforeAssign($id_order)
    {
        $order_history = Order::getOrderStatusHistory($id_order);
        $pickupdetail = Order::find()->where(['id_order'=>$id_order])->select('fk_tbl_order_id_slot')->one();
        $slot_id = $pickupdetail['fk_tbl_order_id_slot'];
          foreach ($order_history as $OH) {
            $order_status[] = $OH['to_tbl_order_status_id_order_status'];
          }
          //print_r($order_status);exit();
          if(in_array(1, $order_status) && !in_array(8, $order_status))
          {
            if((!in_array(2, $order_status) && in_array($slot_id, [4,5])) || (!in_array(3, $order_status) && in_array($slot_id, [1,2,3,6]) ) )
            {
                return 1;
            }else{
                return 0;
            }
            
          } else {
            return 0;
          }
    }


    /**
    *function to get total amount paid
    *type 1 for razorpay, 2 for card, 3 for cash*/
    public static function getamountpaid($order_ids, $type)
    {
        $paytype = '';
        if($type==1)
        {
            $paytype = 'Online Payment';
        }else if($type==2)
        {
            $paytype = 'Card';
        }else if($type==3)
        {
            $paytype = 'Cash';
        }

        $totalpayment = OrderPaymentDetails::find()->where(['id_order'=>$order_ids,'payment_type'=>$paytype, 'payment_status'=>'Success'])->sum('amount_paid');
        if(empty($totalpayment))
        {
            return 'Rs. 0';
        }else{
            return 'Rs. '.round($totalpayment,2).'';
        }
    }

    public static function getamountpaidonline($order_ids,$type)
    {
        $paytype = 'Online Payment';
        $Paidarray = [];
        $amountpaaid = OrderPaymentDetails::find()->where(['id_order'=>$order_ids,'payment_type'=>$paytype, 'payment_status'=>'Success'])->all();
        if($amountpaaid){
            foreach ($amountpaaid as $paid) {
                $paid['amount_paid'] = 'Rs. '.$paid['amount_paid'];
                $Paidarray[] = $paid;
            }
        }
        return $Paidarray;
    }

    public function getRegionName($id){
        //rint_r($id);exit;
        $id = ($id) ? $id : 0;
        $region=Yii::$app->db->createCommand("SELECT c.region_name from tbl_city_of_operation c,tbl_airport_of_operation a WHERE a.airport_name_id=$id AND c.region_id=a.fk_tbl_city_of_operation_region_id ")->queryone();
        //print_r($region);exit;
        if($region)
        {
        return $region['region_name'];  
        }else
        {
            return "-"; 
        }
    }

    public function getAirportName($id){
        $id = ($id) ? $id : 0;
        $airport=Yii::$app->db->createCommand("SELECT a.airport_name from tbl_airport_of_operation a WHERE a.airport_name_id=$id")->queryone();
        //print_r($region['region_name']);exit;
        if($airport)
        {
        return $airport['airport_name'];  
        }else
        {
            return "-"; 
        }
    }

    public function getDeliveryName($id, $corporate_id){
        $dservice_type = ($id) ? $id : 0;
        $delivery_service = DeliveryServiceType::find()->where(['id_delivery_type'=>$dservice_type])->one();
        
        if($corporate_id){
            if($corporate_id == 19 || $corporate_id == 20 || $corporate_id == 30 || $corporate_id == 31 || $corporate_id == 214)
            {
                return $delivery_service['delivery_category']; 
            }else
            {
                return (($dservice_type == 1) ? "Repairs" :
                (($dservice_type == 2) ? "Reverse Pick Up" :
                (($dservice_type == 3) ? "Express - Outstation" :
                    (($dservice_type == 4) ? "Express - Fragile" :
                        (($dservice_type == 5) ? "Outstation- Fragile" :
                        (($dservice_type == 6) ? "Normal - Fragile" :
                            (($dservice_type == 7) ? "Normal Delivery" :
                                (($dservice_type == 8) ? "Express" :
                    (($dservice_type == 9) ? "Outstation" :
                    (($dservice_type == 10) ? "Oversized/Fragile" : "")))))))))
                );
            }    
        }else{
            return "-";
        }
        
    }

    public function getAmountCollected($id){
        $id = ($id) ? $id : 0;
        $orders=Yii::$app->db->createCommand("SELECT o.express_extra_amount,o.amount_paid  from tbl_order o WHERE o.id_order=$id")->queryone();
        //print_r($region['region_name']);exit;
        if($orders)
        {
            $express_extra_amount = $orders['express_extra_amount'];
            $amount_collected = $orders['express_extra_amount'] + $orders['amount_paid'] + $orders['express_extra_amount'] * (Yii::$app->params['gst_percent']/100);
            return $amount_collected;  
        }else
        {
            return "-"; 
        }
    }
    //public function getAreaName($id){
    //    $id = ($id) ? $id : 0;
    //    $area = Yii::$app->db->createCommand("SELECT s.area from tbl_order_spot_details s WHERE s.fk_tbl_order_spot_details_id_order=$id")->queryone();
    //    
    //    if($area)
    //    {
    //    return $area['area'];  
    //    }else
    //    {
    //        return "-"; 
    //    }
    //}


    public function getAreaName($id){
        $id = ($id) ? $id : 0;
        $area = Yii::$app->db->createCommand("SELECT s.area from tbl_order_spot_details s WHERE s.fk_tbl_order_spot_details_id_order=$id")->queryone();
        
        if($area)
        {
            return $area['area'];  
        }else
        {
            return "-"; 
        }
    }

    public function getGroupNameStatus($group_id){
        //print_r($group);exit;
        if($group_id){
            $group=Yii::$app->db->createCommand("SELECT g.order_group_name from tbl_order_group g WHERE g.status=1 AND g.order_group_id=$group_id ")->queryone();
            if($group)
            {
                return $group['order_group_name'];  
            }else
            {
                return "-"; 
            }
        }else{
            return "-";
        }
        
    }

    public function getOrdersById($order_id) 
    {
        header('Access-Control-Allow-Origin: *');
        //$luggage_types = LuggageType::find()->where(['corporate_id'=>0])->all();

        if($order_id){
            $query = (new \yii\db\Query())
                ->select(['t1.id_luggage_type','t1.luggage_type','t1.minimum_weight','t1.group_type','t1.base_price','t2.offer_price', 't3.subsequent_price','t4.base_price as order_base_price', 't4.offer_price as order_offer_price', 't5.subsequent_price as order_subsequent_price'])
                ->from('tbl_luggage_type t1')
                ->join('LEFT JOIN', 'tbl_luggage_offers t2', 't1.id_luggage_type=t2.luggage_type')
                ->join('LEFT JOIN', 'tbl_group_offers t3', 't1.group_type=t3.group_id')
                ->join('LEFT JOIN', 'tbl_order_offers t4', 't1.id_luggage_type=t4.luggage_type')
                ->join('LEFT JOIN', 'tbl_order_group_offer t5', 't1.group_type=t5.group_id')
                ->where(['t1.corporate_id' => 0])
                ->andWhere(['t4.order_id' => $order_id])
                ->andWhere(['t5.order_id' => $order_id]);
            $query = (new \yii\db\Query())
                ->select(['t1.id_luggage_type','t1.luggage_type','t1.minimum_weight','t1.group_type','t1.base_price', 't3.subsequent_price','t4.base_price as order_base_price', 't4.offer_price as order_offer_price', 't5.subsequent_price as order_subsequent_price'])
                ->from('tbl_luggage_type t1')
                ->join('LEFT JOIN', 'tbl_luggage_offers t2', 't1.id_luggage_type=t2.luggage_type')
                ->join('LEFT JOIN', 'tbl_group_offers t3', 't1.group_type=t3.group_id')
                ->join('LEFT JOIN', 'tbl_order_offers t4', 't1.id_luggage_type=t4.luggage_type')
                ->join('LEFT JOIN', 'tbl_order_group_offer t5', 't1.group_type=t5.group_id')
                ->where(['t1.corporate_id' => 0])
                ->andWhere(['t4.order_id' => $order_id])
                ->andWhere(['t5.order_id' => $order_id])
                ->groupBy(['t1.id_luggage_type']);
                //->andWhere(['t2.status' => 'enabled']);
            $command = $query->createCommand();
            $data = $command->queryAll();
            
        }
        //print_r($data);exit;
        //$luggage_types['weight_ranges'] = WeightRange::find()->where(['fk_tbl_weight_range_id_luggage_type'=>$luggage_types['id_luggage_type']])->all();
        //print_r($luggage_types);exit;
        return $data;
    }


    public function getOfferOrdersById($order_id) 
    {
        header('Access-Control-Allow-Origin: *');
        
        if($order_id){
            $query = (new \yii\db\Query())
                ->select(['t1.id_luggage_type','t1.luggage_type','t1.minimum_weight','t1.group_type','t1.base_price', 't3.subsequent_price','t4.base_price as order_base_price', 't4.offer_price as order_offer_price', 't5.subsequent_price as order_subsequent_price'])
                ->from('tbl_luggage_type t1')
                ->join('LEFT JOIN', 'tbl_luggage_offers t2', 't1.id_luggage_type=t2.luggage_type')
                ->join('LEFT JOIN', 'tbl_group_offers t3', 't1.group_type=t3.group_id')
                ->join('LEFT JOIN', 'tbl_order_offers t4', 't1.id_luggage_type=t4.luggage_type')
                ->join('LEFT JOIN', 'tbl_order_group_offer t5', 't1.group_type=t5.group_id')
                ->where(['t1.corporate_id' => 0])
                ->andWhere(['t4.order_id' => $order_id])
                //->andWhere(['t5.order_id' => $order_id])
                ->groupBy(['t1.id_luggage_type']);
                //->andWhere(['t2.status' => 'enabled']);
            $command = $query->createCommand();
            $data = $command->queryAll();
            //print_r($data);exit;
        }else{
            $data = '';
        }
        return $data;
    }

 

public function getOfferOrdersById2($order_id) 
{
    header('Access-Control-Allow-Origin: *');
    
    if($order_id){
         $query = (new \yii\db\Query())
            ->select(['t1.id_luggage_type','t1.luggage_type','t1.minimum_weight','t1.group_type','t1.base_price', 't3.subsequent_price','t4.base_price as order_base_price', 't4.offer_price as order_offer_price', 't5.subsequent_price as order_subsequent_price'])
            ->from('tbl_luggage_type t1')
            ->join('LEFT JOIN', 'tbl_luggage_offers t2', 't1.id_luggage_type=t2.luggage_type')
            ->join('LEFT JOIN', 'tbl_group_offers t3', 't1.group_type=t3.group_id')
            ->join('LEFT JOIN', 'tbl_order_offers t4', 't1.id_luggage_type=t4.luggage_type')
            ->join('LEFT JOIN', 'tbl_order_group_offer t5', 't1.group_type=t5.group_id')
            ->where(['t1.corporate_id' => 0])
            ->andWhere(['t4.order_id' => $order_id])
            ->andWhere(['t5.order_id' => $order_id])
            ->groupBy(['t1.id_luggage_type']);
            //->andWhere(['t2.status' => 'enabled']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        //print_r($data);exit;
    }else{
        $data = '';
    }
    return $data;
}

public function getCityOfferOrdersById2($order_id) 
{
    header('Access-Control-Allow-Origin: *');
    
    if($order_id){
         $query = (new \yii\db\Query())
            ->select(['t1.id_luggage_type','t1.luggage_type','t1.minimum_weight','t1.group_type','t1.base_price', 't3.subsequent_price','t4.base_price as order_base_price', 't4.offer_price as order_offer_price', 't5.subsequent_price as order_subsequent_price'])
            ->from('tbl_luggage_type t1')
            ->join('LEFT JOIN', 'tbl_city_luggage_offers t2', 't1.id_luggage_type=t2.luggage_type')
            ->join('LEFT JOIN', 'tbl_city_group_offers t3', 't1.group_type=t3.group_id')
            ->join('LEFT JOIN', 'tbl_order_offers t4', 't1.id_luggage_type=t4.luggage_type')
            ->join('LEFT JOIN', 'tbl_order_group_offer t5', 't1.group_type=t5.group_id')
            ->where(['t1.corporate_id' => 0])
            ->andWhere(['t4.order_id' => $order_id])
            ->andWhere(['t5.order_id' => $order_id])
            ->groupBy(['t1.id_luggage_type']);
            //->andWhere(['t2.status' => 'enabled']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        //print_r($data);exit;
    }else{
        $data = '';
    }
    return $data;
}



    /*
        To get the order group name based on order id
        @param order id
        @retuen group name
    */
    public function getOrderGroupName($order_id)
    {
        $order_group = OrderGroup::findOne(['id_order' => $order_id]);
            
        if($order_group) {
            if($order_group){
                return $order_group->order_group_name;
            }
        }
        return '';
    }

    public function getDateOfDelivery($slot_id,$arrival_date){
        if($slot_id==5){
            $arrival_date = strtotime("+1 day", strtotime($arrival_date));
            $arrival_date= date("Y-m-d h:i:s", $arrival_date);
        }else{
            $arrival_date='';
        }
    
        return $arrival_date;
    }


    public function getPincodeSector($pincode_id){
    
        $sector_name= PickDropLocation::find()->select('sector')->where(['id_pick_drop_location'=>$pincode_id])->one();
        $sector=$sector_name['sector'];
        if($sector){
            $sector_name=$sector;
        }else{
            $sector_name='';
        }
   
       return $sector_name;
    }

    public function getSectors(){
        $sector_array = array();
        $sector_names= PickDropLocation::find()->select('id_pick_drop_location, sector')->groupBy('sector')->asArray()->all();
        foreach ($sector_names as $key => $value) {
           //print_r($key);print_r($value);exit;
            $sample_key = $value['id_pick_drop_location'];
            $sector_array[$sample_key] = $value['sector'];
            # code...
        }
       return $sector_array;
    }

    public static function isItmanRealatedOrder($id){
        $tmanorder = \app\api_v3\v3\models\TmanOrders::find()->where(['id'=>$id])->one();
        //print_r($tmanorder);exit;
        if($tmanorder){
            return 1;
        }else{
            return 0;
        }
    }

    public function getTotalCollectedValue($order_id,$reschedule_luggage,$luggage_price,$corporate_type,$corporate_id){
        if($reschedule_luggage == 1){
            $corporateTypeArray = array(3,4,5);
            $ConveyanceCharge = self::getOutstationPrice($order_id); 
            $ExtraKmsCharged = self::getExtraKmPrice($order_id);
            $extra_km = self::getExtraKmPrice($order_id); 
            $ExtraKmsGST =  self::Calculate_service_tax($extra_km);
            $conveyance = self::getOutstationPrice($order_id);
            $ConveyanceGST = in_array($corporate_type, $corporateTypeArray) ? self::Calculate_coporate_service_tax($conveyance,$corporate_id) : self::Calculate_service_tax($conveyance);
            $initialamount = (float)$luggage_price+(float)$ConveyanceCharge+(float)$ConveyanceGST+(float)$ExtraKmsCharged+(float)$ExtraKmsGST;
            return $initialamount;
        } else {
            $order_payment_details = OrderPaymentDetails::find()->select('amount_paid')->where(['id_order'=>$order_id])->one();
            $initialamount_total = !empty($order_payment_details) ? $order_payment_details->amount_paid : 0;
            return $initialamount_total;
        }
    }

    public function getTotalModificationValue($order_id,$luggage_price,$modified_amount){
        if($modified_amount){
            $outstation = self::getOutstationPrice($order_id);
            $extra_km_charge = self::getExtraKmPrice($order_id);
            $outstation_gst = self::Calculate_service_tax($outstation);
            $extra_gst = self::Calculate_service_tax($extra_km_charge);
            $net_order_amount = (float)$luggage_price + (float)$outstation + (float)$extra_km_charge + (float)$outstation_gst + (float)$extra_gst;
            return $net_order_amount;
        } else {
            return 0;
        }
    }

    public function getCountSms($order_id){
        if(empty($order_id)){
            return 0;
        } else {
            $smsResult = OrderSmsDetails::find()->where(['order_sms_order_id' => $order_id])->all();
            $smsCount = isset($smsResult) ? count($smsResult) : 0;
            return $smsCount;
        }
    }

    public function getActualDeliveryDate($order_id){
        if(empty($order_id)){
            return "-";
        } else {
            $result = Yii::$app->db->createCommand("select * from tbl_order_history where fk_tbl_order_history_id_order = '".$order_id."' and to_tbl_order_status_id_order_status = '18'  order by id_order_history desc")->queryOne();
            if(!empty($result)){
                return !empty($result['date_created']) ? date('Y-m-d h:i:s A',strtotime($result['date_created'])) : "-";
            } else {
                return "-";
            }
        }
    }

    public static function getorderinfos($id_order)
    { 
        header('Access-Control-Allow-Origin: *'); 
        $order_details=Yii::$app->db->createCommand("SELECT o.id_order,o.delivery_date,o.city_id,o.corporate_type,o.delivery_type,o.discount_amount,o.order_transfer,o.corporate_id,o.related_order_id,o.fk_tbl_airport_of_operation_airport_name_id as airport_id,o.extra_weight_purched,o.no_of_units,o.order_number,o.created_by,o.created_by_name,o.service_type,o.fk_tbl_order_id_slot as id_slot,o.order_date,o.departure_time,o.departure_date,o.arrival_time,o.arrival_date,o.meet_time_gate,o.delivery_time,o.travel_person,o.reschedule_luggage,o.round_trip,o.travell_passenger_name,o.travell_passenger_contact,o.fk_tbl_order_status_id_order_status as id_order_status,o.order_status,o.corporate_price,o.sector,o.sector_name, o.express_extra_amount, o.outstation_extra_amount,o.weight,o.excess_bag_amount,o.payment_mode_excess,o.modified_amount,o.modified_amount_data, o.luggage_price,o.insurance_number,o.insurance_price,o.updated_insurance,o.amount_paid,o.service_tax_amount,o.flight_number,o.someone_else_document,o.someone_else_document_verification,o.ticket,o.airasia_receipt,o.flight_verification,o.enable_cod,o.signature1,o.signature2,o.payment_method,o.luggage_accepted,o.porter_modified_datetime, o.admin_modified_datetime,o.location,o.order_modified, o.admin_edit_modified, o.admin_modified_amount,o.dservice_type,o.date_created,o.other_comments,o.no_response,o.fk_tbl_order_id_customer,o.fk_tbl_airport_of_operation_airport_name_id as airport_id, o.fk_tbl_order_id_country_code as t_id_country_code,o.delivery_datetime,o.delivery_time_status, tcc.country_code as traveler_country_code,s.slot_name,s.slot_start_time,s.slot_end_time,s.description,c.id_customer,c.name as customer_name,c.email as customer_email,c.mobile as customer_mobile,c.document as customer_id_proof, c.customer_profile_picture,c.id_proof_verification,c.address_line_1 as customer_address_line_1,c.address_line_2 as customer_address_line_2,c.area as customer_area,c.pincode as customer_pincode, ccc.country_code as c_country_code ,loc.location_name,sn.spot_name,spot.id_order_spot_details,om.dropPersonName,om.dropPersonNumber,om.pickupPersonAddressLine1,om.pickupPersonAddressLine2,om.droparea,om.dropPersonAddressLine1,om.dropPersonAddressLine2,om.dropPincode,om.pickupPersonName,om.pickupPersonNumber,om.pickupPincode, spot.fk_tbl_order_spot_details_id_pick_drop_spots_type as id_pick_drop_spots_type,spot.assigned_person,spot.person_name as location_contact_name, spot.person_mobile_number as location_contact_number, spot.landmark, spot.building_number, spot.address_line_1 as location_address_line_1,spot.address_line_2 as location_address_line_2,spot.area as location_area,spot.landmark,spot.building_number,spot.pincode as location_pincode,spot.hotel_name,spot.booking_confirmation_file,spot.hotel_booking_verification,spot.invoice_verification,spot.business_name,spot.business_contact_number,spot.mall_name,spot.store_name,spot.building_restriction,spot.other_comments, cph.id_contact_person_hotel ,cph.contact_person_name as hotel_contact_person,ZD.outstationCharge as outstationCharge,ZD.taxAmount as taxAmount, ZD.extraKilometer as extra_km_price,o.travell_passenger_email,o.corporate_customer_id FROM tbl_order o LEFT JOIN tbl_country_code tcc ON o.fk_tbl_order_id_country_code = tcc.id_country_code LEFT JOIN tbl_slots s ON s.id_slots = o.fk_tbl_order_id_slot LEFT JOIN tbl_customer c ON c.id_customer = o.fk_tbl_order_id_customer LEFT JOIN tbl_order_meta_details om ON om.orderId = o.id_order LEFT JOIN tbl_country_code ccc ON c.fk_tbl_customer_id_country_code = ccc.id_country_code LEFT JOIN tbl_pick_drop_location loc ON loc.id_pick_drop_location = o.fk_tbl_order_id_pick_drop_location LEFT JOIN tbl_order_spot_details spot ON spot.fk_tbl_order_spot_details_id_order = o.id_order LEFT JOIN tbl_order_zone_details ZD ON ZD.orderId = o.id_order LEFT JOIN tbl_contact_person_hotel cph ON spot.fk_tbl_order_spot_details_id_contact_person_hotel = cph.id_contact_person_hotel LEFT JOIN tbl_pick_drop_spots_type sn ON sn.id_pick_drop_spots_type = spot.fk_tbl_order_spot_details_id_pick_drop_spots_type WHERE o.id_order='".$id_order."'")->queryOne();
        
        if(!empty($order_details)){
            if(!empty($order_details['building_restriction']) && $order_details['building_restriction'] != NULL && $order_details['building_restriction'] != ' '){
                $unserialized_building_restriction=implode(',',unserialize($order_details['building_restriction']));
                if($unserialized_building_restriction){
                    $building_restriction = Yii::$app->db->createCommand("SELECT br.restriction from tbl_building_restriction br WHERE br.id_building_restriction IN(".$unserialized_building_restriction.")")->queryAll(); 
                
                    if($building_restriction){
                        $order_details['building_restriction'] = implode(', ', array_column($building_restriction, 'restriction'));
                    }
                }
            }
            $bagrangetype = Yii::$app->db->createCommand("SELECT o.excess_weight,o.bag_weight,o.bag_type,o.fk_tbl_order_items_id_order as id_order,o.barcode,o.passive_tag,o.id_order_item,o.fk_tbl_order_items_id_barcode as id_barcode from tbl_order_items o WHERE 
                o.fk_tbl_order_items_id_order='".$id_order."'")->queryAll();

            count($bagrangetype);
            $arrayRange = array();

            array_push($arrayRange,array('one'=>'15'));
            array_push($arrayRange,array('one'=>'15-20'));
            array_push($arrayRange,array('one'=>'20-25'));
            array_push($arrayRange,array('one'=>'25-30'));
            array_push($arrayRange,array('one'=>'30-40'));

            $invoices = Yii::$app->db->createCommand("SELECT inv.id_mall_invoices,inv.invoice from tbl_mall_invoices inv WHERE fk_tbl_mall_invoices_id_order='".$id_order."'")->queryAll();
            
            $order_item_details = Yii::$app->db->createCommand("SELECT oi.excess_weight,oi.bag_weight,oi.bag_type,oi.id_order_item,oi.fk_tbl_order_items_id_order as id_order,oi.barcode,oi.fk_tbl_order_items_id_barcode as id_barcode,oi.passive_tag,oi.excess_weight,oi.fk_tbl_order_items_id_weight_range,oi.item_weight,oi.fk_tbl_order_items_id_luggage_type as id_luggage_type,oi.fk_tbl_order_items_id_weight_range as id_weight_range,oi.items_old_weight as old_item_weight,oi.fk_tbl_order_items_id_luggage_type_old as old_id_luggage_type,oi.item_price,oi.deleted_status,oi.new_luggage,oi.admin_new_luggage,lt.luggage_type,lto.luggage_type as old_luggage_type FROM tbl_order_items oi JOIN tbl_luggage_type lt ON lt.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type LEFT JOIN tbl_luggage_type lto ON lto.id_luggage_type = oi.fk_tbl_order_items_id_luggage_type_old WHERE oi.fk_tbl_order_items_id_order='".$id_order."' ")->queryAll();

            $order_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_images/';
            if($order_details['reschedule_luggage']==1){
                $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."' OR oi.fk_tbl_order_images_id_order='".$order_details['related_order_id']."'")->queryAll();
            }else{
                $order_item_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_image_url."',oi.image) AS 'image_name',oi.id_order_image,oi.image,oi.before_after_damaged FROM tbl_order_images oi WHERE oi.fk_tbl_order_images_id_order='".$id_order."'")->queryAll();
            }
            $order_receipt_image_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_receipts/';

            $order_receipt_images = Yii::$app->db->createCommand("SELECT CONCAT('".$order_receipt_image_url."',oi.payment_receipt) AS 'image_name',oi.id_order_payment_details,oi.payment_receipt FROM tbl_order_payment_details oi WHERE oi.id_order='".$id_order."'")->queryAll();
            $order_details['total_extra_km_price'] = Order::getExtraKmPrices($order_details['id_order']);
            $booking_data['order']=$order_details;
            if(isset($order_details['corporate_id'])){
                $booking_data['corporate_details']=Order::getcorporatedetails($order_details['corporate_id']);
            }

            $booking_data['mall_invoices']=$invoices;
            $booking_data['order']['luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'deleted_status'));
            $booking_data['order']['deleted_luggage_count']= count(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==1; }),'deleted_status'));
            $booking_data['order']['total_luggage_weight'] = array_sum(array_column(array_filter($order_item_details, function($el) { return $el['deleted_status']==0; }),'item_weight'));
            $booking_data['order_items']=$order_item_details;
            $booking_data['order_images']=$order_item_images;
            $booking_data['bagrangetype']=$bagrangetype; 
            $booking_data['bag_range'] = $arrayRange; 
            $booking_data['count'] =  count($bagrangetype);
            $booking_data['order_receipts']=$order_receipt_images;//code in 21/08/2017
            $booking_data['allocation_details']=Order::getAllocationdetails($id_order);
            $booking_data['porterx_details']=Order::getPorterxdetails($id_order);
            $booking_data['order']['picked_up_datetime']=Order::getPickedUpDateTime($id_order);
            return $booking_data;
        } else {
            return false;
        }
    }
}
