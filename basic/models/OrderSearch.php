<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Order;
use app\api_v3\v3\models\OrderMetaDetails;
use app\models\EmployeeAirportRegion;
use app\api_v3\v3\models\CorporateEmployeeAirport;
use app\api_v3\v3\models\CorporateEmployeeRegion;
use app\models\SubscriptionTransactionDetails;

/**
 * OrderSearch represents the model behind the search form about `app\models\Order`.
 */
class OrderSearch extends Order
{
    /**
     * @inheritdoc
     */

    public $assigned_porter;
    public $assigned_porterx;
    public function rules()
    {
        return [
            [['fk_tbl_order_status_id_order_status', 'round_trip', 'allocation', 'enable_cod'], 'integer'],
            [['travel_person', 'id_order','related_order_id','fk_tbl_order_id_slot','fk_tbl_order_id_customer', 'travell_passenger_name','service_type','order_number', 'ticket', 'airline_name', 'dservice_type', 'flight_number', 'departure_time', 'arrival_time', 'meet_time_gate', 'other_comments',  'payment_method', 'payment_transaction_id', 'payment_status', 'invoice_number','order_status','sector_name', 'assigned_porter', 'assigned_porterx' ,'date_created', 'date_modified', 'delivery_date','sector','fk_tbl_airport_of_operation_airport_name_id','location', 'fk_tbl_order_id_pick_drop_location', 'order_transfer'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        // echo "<pre>";print_r(array_slice($params,0));die;
        $date1 = "";
        $date2 = "";
        $bookdate1 = "";
        $bookdate2 = "";
        $query = Order::find()->joinWith('orderMetaDetailsRelation')->from('tbl_order t')->where(['t.deleted_status' => 0]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        //$dataProvider->query->where('t.corporate_id = 2 ');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {

                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(100*($params['page']-1));
                $query->limit(100);
            }
        } else {
            $query->offset(0);
            $query->limit(100);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            //'t.travel_person' => $this->travel_person,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            // 't.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.order_transfer' => $this->order_transfer,
            't.date_modified' => $this->date_modified,
            
        ]);

        /*if ( ! is_null($this->order_date) && strpos($this->order_date, '/') !== false ) { 
            $date = explode('/', $this->order_date);
            print_r($date);exit;
            $query->andFilterWhere(['>=', 't.order_date', $date[0]]);
            $query->andFilterWhere(['<=', 't.order_date', $date[1]]);
        }*/
        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            
            $dates = explode(' - ', $this->order_date);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
        } else {
            $today = date('Y-m-d');
            $date1 = date("Y-m-d", strtotime("-6 months"));
            $date2 = date('Y-m-d',strtotime("+6 months"));
        }

        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);

            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
                        
        }

        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2 ." 23:59:59"]);
        }else {
            if($params['r'] == "order/index"){
                $query->andFilterWhere(['>=', 't.date_created', $date1 ." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.date_created', $date2 ." 23:59:59"]);
            } else {

            }
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
            //->orFilterWhere(['like', 'c6.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            //->andFilterWhere(['like', 'special_care', $this->special_care])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
     //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

        return $dataProvider; 
    }
    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchbyairport($params, $airport)
    {
        $query = Order::find()->from('tbl_order t')->joinwith('orderMetaDetailsRelation')->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            },'relatedOrder'=>function($q){
                                                $q->from('tbl_order c5');
                                            },'fkTblOrderCorporateId'=>function($q){
                                                $q->from('tbl_corporate_details c6');
                                            }])->where(['t.deleted_status' => 0]);


        // if (isset($params['page'])) {
        //     if (!empty($params['page'])) {
        //         $query->offset(100*($params['page']-1));
        //         $query->limit(100);
        //     }
        // } else {
        //     $query->offset(0);
        //     $query->limit(100);
        // }                                    
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        $dataProvider->query->where('t.fk_tbl_airport_of_operation_airport_name_id ='.$airport);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            //'t.travel_person' => $this->travel_person,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            // 't.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.date_modified' => $this->date_modified,
            
        ]);

        /*if ( ! is_null($this->order_date) && strpos($this->order_date, '/') !== false ) { 
            $date = explode('/', $this->order_date);
            print_r($date);exit;
            $query->andFilterWhere(['>=', 't.order_date', $date[0]]);
            $query->andFilterWhere(['<=', 't.order_date', $date[1]]);
        }*/
        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            $dates = explode(' - ', $this->order_date);

            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
            // if($date1 == $date2){
            //     if($this->order_number || $this->flight_number || $this->fk_tbl_order_id_customer || $this->fk_tbl_airport_of_operation_airport_name_id || $this->fk_tbl_order_id_pick_drop_location || $this->meet_time_gate || $this->fk_tbl_order_id_slot || $this->dservice_type || $this->id_order || $this->id_order || $this->travell_passenger_name || $this->order_status || $this->related_order_id || $this->sector_name){
            //             $query->andFilterWhere(['>=', 't.order_date', $date1]);
            //             $query->andFilterWhere(['<=', 't.order_date', $date2]);
            //     }else{
            //         $query->andFilterWhere(['>=', 't.order_date', $date1]);
            //         $query->andFilterWhere(['<=', 't.order_date', $date2]);
            //     }
            // }else if($date1 != $date2){
            //     $query->andFilterWhere(['>=', 't.order_date', $date1]);
            //     $query->andFilterWhere(['<=', 't.order_date', $date2]);
            // }else{
            //     $query->andFilterWhere(['>=', 't.order_date', $date1]);
            //     $query->andFilterWhere(['<=', 't.order_date', $date2]);
            // }

            
        }

        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);
            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
            // if($bookdate1 == $bookdate2){
            //     if($this->order_number || $this->flight_number || $this->fk_tbl_order_id_customer || $this->fk_tbl_airport_of_operation_airport_name_id || $this->fk_tbl_order_id_pick_drop_location || $this->meet_time_gate || $this->fk_tbl_order_id_slot || $this->dservice_type || $this->id_order || $this->id_order || $this->travell_passenger_name || $this->order_status || $this->related_order_id || $this->sector_name){
            //             $query->andFilterWhere(['>=', 't.date_created', $bookdate1]);
            //             $query->andFilterWhere(['<=', 't.date_created', $bookdate2]);
            //     }else{
            //         $query->andFilterWhere(['>=', 't.date_created', $bookdate1]);
            //         $query->andFilterWhere(['<=', 't.date_created', $bookdate2]);
            //     }
            // }else if($bookdate1 != $bookdate2){
            //     $query->andFilterWhere(['>=', 't.date_created', $bookdate1]);
            //     $query->andFilterWhere(['<=', 't.date_created', $bookdate2]);
            // }else{
            //     $query->andFilterWhere(['>=', 't.date_created', $bookdate1]);
            //     $query->andFilterWhere(['<=', 't.date_created', $bookdate2]);
            // }            
        }

        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2]);
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
            //->orFilterWhere(['like', 'c6.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            //->andFilterWhere(['like', 'special_care', $this->special_care])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
             ->andFilterWhere(['like', 'payment_status', $this->payment_status])
              ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);

        return $dataProvider; 
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchbydashboardkey($params, $key)
    {
        $today = date('Y-m-d');
        $tommorow = date('Y-m-d', strtotime('+1 day', time()));

        $query = Order::find()->from('tbl_order t')->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            },'relatedOrder'=>function($q){
                                                $q->from('tbl_order c5');
                                            },'fkTblOrderCorporateId'=>function($q){
                                                $q->from('tbl_corporate_details c6');
                                            }])->where(['t.deleted_status' => 0]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        if(isset($key) && $key == 'current'){ 
            $dataProvider->query->where("t.order_date='".$today."' OR t.order_date='".$tommorow."'");
        }else if(isset($key) && $key == 'flexible'){
            $dataProvider->query->where("t.corporate_id=0 AND (t.meet_time_gate IS NOT NULL OR t.meet_time_gate != '') AND (t.flight_number IS NOT NULL OR t.flight_number != '') AND (t.order_status='Confirmed' OR t.order_status='Arrival into airport warehouse' OR t.order_status='Assigned' OR t.order_status='Open')");
        }else if(isset($key) && $key == 'reschedule'){
            $dataProvider->query->where("(t.reschedule_luggage= 1) AND (t.order_status='Confirmed' OR t.order_status='Arrival into airport warehouse' OR t.order_status='Assigned' OR t.order_status='Out for delivery at customer location' OR t.order_status='Out for delivery at gate 1' OR t.order_status='Open')");
        }else if(isset($key) && $key == 'undelivered'){
            $dataProvider->query->where("t.order_status='Undelivered'");
        }else if(isset($key) && $key == 'refund'){
            $dataProvider->query->where("t.modified_amount > 0");
        }else{
            $dataProvider = '';
        }
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            //'t.travel_person' => $this->travel_person,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.date_modified' => $this->date_modified,
            
        ]);

        /*if ( ! is_null($this->order_date) && strpos($this->order_date, '/') !== false ) { 
            $date = explode('/', $this->order_date);
            print_r($date);exit;
            $query->andFilterWhere(['>=', 't.order_date', $date[0]]);
            $query->andFilterWhere(['<=', 't.order_date', $date[1]]);
        }*/
        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            $dates = explode('-', $this->order_date);

            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
            if($date1 == $date2){
                if($this->order_number || $this->flight_number || $this->fk_tbl_order_id_customer || $this->fk_tbl_airport_of_operation_airport_name_id || $this->fk_tbl_order_id_pick_drop_location || $this->meet_time_gate || $this->fk_tbl_order_id_slot || $this->dservice_type || $this->id_order || $this->id_order || $this->travell_passenger_name || $this->order_status || $this->related_order_id || $this->sector_name){
                        $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                        $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
                }else{
                    $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                    $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
                }
            }else if($date1 != $date2){
                $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
            }else{
                $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
            }

            
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
            //->orFilterWhere(['like', 'c6.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            //->andFilterWhere(['like', 'special_care', $this->special_care])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
             ->andFilterWhere(['like', 'payment_status', $this->payment_status])
              ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);

        return $dataProvider; 
    }


    public function userorderssearch($params)
    {
        $query = Order::find()->from('tbl_order t')->where(['c1.mobile'=>Yii::$app->user->identity->mobile])
                                            ->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            },'relatedOrder'=>function($q){
                                                $q->from('tbl_order c5');
                                            }]);

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(50*($params['page']-1));
                $query->limit(50);
            }
        } else {
            $query->offset(0);
            $query->limit(50);
        }
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([ 
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
      
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            //'t.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.date_modified' => $this->date_modified,
        ]);
        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            $query->andFilterWhere(['>=', 't.date_created', $date1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $date2." 23:59:59"]);

        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            //->andFilterWhere(['like', 'order_status', $this->order_status])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 'c1.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            ->andFilterWhere(['like', 't.order_date', $this->order_date])
            //->andFilterWhere(['like', 'special_care', $this->special_care])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
            //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;


        return $dataProvider;
    } 
    public function usercorporateorderssearch($params,$cid){

        $query = Order::find()->from('tbl_order t')->where(['c1.mobile'=>Yii::$app->user->identity->mobile])
                                            ->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            },'relatedOrder'=>function($q){
                                                $q->from('tbl_order c5');
                                            }]);

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(50*($params['page']-1));
                $query->limit(50);
            }
        } else {
            $query->offset(0);
            $query->limit(50);
        }
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        $dataProvider->query->where('t.corporate_id ='.$cid);
        $dataProvider->query->andWhere('t.deleted_status = 0');

        $user_id = Yii::$app->user->identity->id_employee;
        $corporate_id = \app\models\CorporateDetails::find()->where(['employee_id'=>$user_id])->one();
        $corMapId = \app\api_v3\v3\models\AirlineCorporateMapping::find()->where(['corporate_id'=>$corporate_id->corporate_detail_id])->one(); 
        if($corMapId){
            $corMapIdAll = \app\api_v3\v3\models\CreateAirline::find()->where(['airline_id'=>$corMapId->fk_airline_id])->one();
            $corMapIdAll = $corMapIdAll->corporate_id;
            $dataProvider->query->orwhere('t.corporate_id ='.$corMapIdAll);

        }  
        
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
            't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.date_modified' => $this->date_modified,
        ]);

        if ( ! is_null($this->order_date) && strpos($this->order_date, '/') !== false ) { 
            $date = explode('/', $this->order_date);
            $query->andFilterWhere(['>=', 't.order_date', $date[0]." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.order_date', $date[1]." 23:59:59"]);
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 'c2.description', $this->delivery_date])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 'c1.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);

        return $dataProvider;

    }

    public function usercorporatekioskorderssearch($params,$roleid, $user_corporate_id, $corporate_id = NULL){
        if(($roleid == 15) || $roleid == 12 || $roleid == 13 || $roleid == 14){
            $airportArray = array();
            $kioskAirportId = CorporateEmployeeAirport::find()->select(['fk_tbl_airport_id'])->where(['fk_tbl_employee_id'=> Yii::$app->user->id])->all();
            foreach($kioskAirportId as $value){
                array_push($airportArray,$value->fk_tbl_airport_id);
            }

            $cityArray = array();
            $kioskCityId = CorporateEmployeeRegion::find()->select(['fk_tbl_region_id'])->where(['fk_tbl_employee_id'=> Yii::$app->user->id])->all();
            foreach($kioskCityId as $value){
                array_push($cityArray,$value->fk_tbl_region_id);
            }

           $query = Order::find()->from('tbl_order t');
            if($cityArray){
                $query->orWhere(['in','city_id',$cityArray]);
            }
            if($airportArray){
                $query->orWhere(['in','fk_tbl_airport_of_operation_airport_name_id',$airportArray]);
                // changed here (add condition to update order from kiosk)@BJ 
            } 
            
            if($user_corporate_id){
                $query->andWhere(['in','corporate_id',$user_corporate_id]);
            }
            // echo $query->createCommand()->getRawSql();die;

            if($roleid == 14){
                $query->orWhere(['created_by' => $roleid]);
            }

        }
        
        else if($roleid == 11){
            $airportArray = array();
            $kioskAirportId = EmployeeAirportRegion::find()->select(['fk_tbl_airport_of_operation_airport_name_id'])->where(['fk_tbl_employee_id'=> Yii::$app->user->id])->all();
            foreach($kioskAirportId as $value){
                array_push($airportArray,$value->fk_tbl_airport_of_operation_airport_name_id);
            }

           $query = Order::find()->from('tbl_order t');
            if($airportArray){
                $query->orWhere(['in','fk_tbl_airport_of_operation_airport_name_id',$airportArray]);
                // changed here (add condition to update order from kiosk)@BJ 
            } else {
                $query->Where(['t.created_by'=>$roleid]);
            }
        } else{
            $query = Order::find()->from('tbl_order t')->where(['t.created_by'=>$roleid]);
        }

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        // $dataProvider->query->where('t.fk_tbl_airport_of_operation_airport_name_id = 7 OR t.fk_tbl_airport_of_operation_airport_name_id = 3 OR t.fk_tbl_airport_of_operation_airport_name_id = 8 OR t.fk_tbl_airport_of_operation_airport_name_id = 9 OR t.fk_tbl_airport_of_operation_airport_name_id = 10 OR t.fk_tbl_airport_of_operation_airport_name_id = 11');
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {

                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }
        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
            't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
           // 't.date_created' => $this->date_created,
            't.date_modified' => $this->date_modified,
        ]);

        if ( ! is_null($this->date_created) && strpos($this->date_created, ' - ') !== false ) { 
            $date = explode(' - ', $this->date_created);
            $query->andFilterWhere(['>=', 't.date_created', $date[0]." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $date[1]." 23:59:59"]);
        }

        if ( ! is_null($this->order_date) && strpos($this->order_date, '/') !== false ) { 
            $date = explode('/', $this->order_date);
            $query->andFilterWhere(['>=', 't.order_date', $date[0]." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.order_date', $date[1]." 23:59:59"]);
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 'c2.description', $this->delivery_date])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 'c1.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);

        return $dataProvider;

    }

    public function userkioskorderssearch($params,$roleid){
        // changed here (add condition to update order from kiosk)@BJ 
        $airportArray = array();
        $airport = "";
        $kioskName = trim(Yii::$app->user->identity->name," ");
        $today = date('Y-m-d');
        $date1 = date("Y-m-d", strtotime("-6 months"));
        $date2 = date('Y-m-d',strtotime("+6 months"));
        if($roleid == '10'){
            $kioskAirportId = EmployeeAirportRegion::find()->select(['fk_tbl_airport_of_operation_airport_name_id'])->where(['fk_tbl_employee_id'=> Yii::$app->user->id])->all();
            foreach($kioskAirportId as $value){
                array_push($airportArray,$value->fk_tbl_airport_of_operation_airport_name_id);
                $airport .= $value->fk_tbl_airport_of_operation_airport_name_id.',';
            }
        }

        if($airportArray){
            $query = Order::find()->from('tbl_order t')
                    ->where("t.fk_tbl_airport_of_operation_airport_name_id IN(".rtrim($airport,',').") OR (t.fk_tbl_airport_of_operation_airport_name_id IN(".rtrim($airport,',').") and t.created_by = ".$roleid.") OR ((t.created_by_name = '".$kioskName."') and (t.created_by = 10)) OR (t.order_transfer = 1)");
        } else {
            $query = Order::find()->from('tbl_order t')
                    ->where(['t.created_by'=>$roleid]);
        }
        
      

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        // $dataProvider->query->where('t.fk_tbl_airport_of_operation_airport_name_id = 7 OR t.fk_tbl_airport_of_operation_airport_name_id = 3 OR t.fk_tbl_airport_of_operation_airport_name_id = 8 OR t.fk_tbl_airport_of_operation_airport_name_id = 9 OR t.fk_tbl_airport_of_operation_airport_name_id = 10 OR t.fk_tbl_airport_of_operation_airport_name_id = 11');
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {

                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }
        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
            't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            //'t.date_created' => $this->date_created,
            't.date_modified' => $this->date_modified,
        ]);

        
        if ( ! is_null($this->date_created) && strpos($this->date_created, ' - ') !== false ) { 
            $date = explode(' - ', $this->date_created);
            $query->andFilterWhere(['>=', 't.date_created', $date[0]." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $date[1]." 23:59:59"]);
        } else {
            if($params['r'] == "order/kiosk-orders"){
                $query->andFilterWhere(['>=', 't.date_created', $date1." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.date_created', $date2." 23:59:59"]);
            }
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 'c2.description', $this->delivery_date])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 'c1.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
          // var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

        return $dataProvider;

    }

    public function porterxorders($params)
    {
        $query = Order::find()->from('tbl_order t')->where('(t.fk_tbl_order_status_id_order_status = 15 and t.service_type = 1) or (t.fk_tbl_order_status_id_order_status IN (1,2) and t.service_type = 2)')
                                                ->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'relatedOrder'=>function($q){
                                                $q->from('tbl_order c3');
                                            },'fkTblOrderCorporateId'=>function($q){
                                                $q->from('tbl_corporate_details c4');
                                            }]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'order_date' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            //'order_date' => $this->order_date,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            'allocation' => $this->allocation,
            'enable_cod' => $this->enable_cod,
            'date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            'date_modified' => $this->date_modified,
        ]);

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 't.ticket', $this->ticket])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 't.order_date', $this->order_date])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c3.order_status', $this->related_order_id])
            ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c4.name', $this->fk_tbl_order_id_customer]])
            //->orFilterWhere(['like', 'c4.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
           

        return $dataProvider;
    }

    public function allocationpendingorders($params, $airport)
    {

                // $q = Yii::$app->db->createCommand("SELECT br.id_order,br.order_group_name from tbl_order_group br WHERE br.status = 1")->queryAll();
                // $arr = [];
                // foreach ($q as $value) {
                //     $order = explode(',', $value['id_order']);
                //     foreach ($order as $value1) {
                //        echo $value1."--"; 
                //     }

                //     # code...
                // }
               // print_r($order[0]);
                // exit;
          
        // print_r($order);exit;
        /*$query = Order::find()->from('tbl_order t')->where('fk_tbl_order_status_id_order_status = 3 or fk_tbl_order_status_id_order_status = 9')
                                                ->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            }]);*/
        // $query = Order::find()->from('tbl_order t')->where('(t.fk_tbl_order_status_id_order_status IN (1,3,29) and t.fk_tbl_order_id_slot IN (1,2,3,7,9)) or (t.fk_tbl_order_status_id_order_status IN (1,2,29) and t.fk_tbl_order_id_slot IN (1,2,3,7,9) and t.order_transfer = 1) or (t.fk_tbl_order_status_id_order_status = 9 and t.fk_tbl_order_id_slot IN (4,5)) or (t.fk_tbl_order_status_id_order_status IN (2,29) and t.order_transfer = 1 and t.fk_tbl_order_id_slot IN (4,5))')

        $query = Order::find()->from('tbl_order t')->where('(t.fk_tbl_order_status_id_order_status IN (1,3,29) and t.fk_tbl_order_id_slot IN (1,2,3,7,9)) or (t.fk_tbl_order_status_id_order_status IN (1,2,29) and t.fk_tbl_order_id_slot IN (1,2,3,7,9) and t.order_transfer = 1) or (t.fk_tbl_order_status_id_order_status = 9 and t.fk_tbl_order_id_slot IN (4,5)) or (t.fk_tbl_order_status_id_order_status IN (2,29) and t.order_transfer = 1 and t.fk_tbl_order_id_slot IN (4,5)) or (t.fk_tbl_order_status_id_order_status IN (1,2,3,9,29) and (t.order_transfer = 1) and (t.airport_slot_time != "") or (t.fk_tbl_order_status_id_order_status IN (1,2,3,9,29) and t.airport_slot_time != ""))')
                                                ->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            },
                                            'ordergroup'=>function($q){
                                                $q->from('tbl_order_group c7');
                                            },
                                            'relatedOrder'=>function($q){
                                                $q->from('tbl_order c5');
                                            },'fkTblOrderCorporateId'=>function($q){
                                                $q->from('tbl_corporate_details c6');
                                            }]);


          // print_r($building_restriction );exit;
            // if($building_restriction){
            //     $order_details['building_restriction'] = explode(', ', $building_restriction[2]);
            //       print_r( $order_details['building_restriction'] );exit; 
            // }

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'order_date' => SORT_DESC,
                ],
              ]
        ]);
        if($airport){
            $dataProvider->query->where('t.fk_tbl_airport_of_operation_airport_name_id ='.$airport);
        }
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            //'meet_time_gate' => date('H:i', strtotime($this->meet_time_gate)),
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            //'order_date' => $this->order_date,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.date_modified' => $this->date_modified,
        ]);

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 't.ticket', $this->ticket])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 't.order_date', $this->order_date])
            ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
            //->orFilterWhere(['like', 'c6.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            //->andFilterWhere(['like', 'meet_time_gate', date('H:i', strtotime($this->meet_time_gate))])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);

        return $dataProvider;
    }

    public function searchbyorders($params, $oderid)
    {
        $query = Order::find()->from('tbl_order t')->joinwith(['fkTblOrderIdCustomer'=>function($q){
                                                $q->from('tbl_customer c1');
                                            },'fkTblOrderIdSlot'=>function($q){
                                                $q->from('tbl_slots c2');
                                            },'vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                                                $q->from('tbl_employee c3');
                                            },'porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                                                $q->from('tbl_employee c4');
                                            },'relatedOrder'=>function($q){
                                                $q->from('tbl_order c5');
                                            },'fkTblOrderCorporateId'=>function($q){
                                                $q->from('tbl_corporate_details c6');
                                            }])->where(['t.deleted_status' => 0]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        $dataProvider->query->where(['t.id_order'=>$oderid]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            //'t.travel_person' => $this->travel_person,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            //'fk_tbl_order_id_slot' => $this->fk_tbl_order_id_slot,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            //'fk_tbl_order_id_customer' => $this->fk_tbl_order_id_customer,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.date_created' => $this->date_created,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.date_modified' => $this->date_modified,
            
        ]);

        /*if ( ! is_null($this->order_date) && strpos($this->order_date, '/') !== false ) { 
            $date = explode('/', $this->order_date);
            print_r($date);exit;
            $query->andFilterWhere(['>=', 't.order_date', $date[0]]);
            $query->andFilterWhere(['<=', 't.order_date', $date[1]]);
        }*/
        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            $dates = explode('-', $this->order_date);

            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
            if($date1 == $date2){
                if($this->order_number || $this->flight_number || $this->fk_tbl_order_id_customer || $this->fk_tbl_airport_of_operation_airport_name_id || $this->fk_tbl_order_id_pick_drop_location || $this->meet_time_gate || $this->fk_tbl_order_id_slot || $this->dservice_type || $this->id_order || $this->id_order || $this->travell_passenger_name || $this->order_status || $this->related_order_id || $this->sector_name){
                        $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                        $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
                }else{
                    $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                    $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
                }
            }else if($date1 != $date2){
                $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
            }else{
                $query->andFilterWhere(['>=', 't.order_date', $date1." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.order_date', $date2." 23:59:59"]);
            }

            
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
           // ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
            //->orFilterWhere(['like', 'c6.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            //->andFilterWhere(['like', 't.order_date', $this->order_date])
            //->andFilterWhere(['like', 'special_care', $this->special_care])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
             ->andFilterWhere(['like', 'payment_status', $this->payment_status])
              ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);

        return $dataProvider; 
    }

    public function supersubscriberorderssearch($params,$roleid){
        $confNumberArray = array();
        $confNumber = "";
        $employeeId = Yii::$app->user->identity->id_employee;
        $result = Yii::$app->Common->get_super_subscription_details($employeeId);
        $today = date('Y-m-d');
        if($roleid == '17'){
            $confNumberId = SubscriptionTransactionDetails::find()->select(['subscription_transaction_id'])->where(['subscription_id'=> $result->subscription_id])->all();
           // echo '<pre>'; print_r($query);  die;

            if(!empty($confNumberId)){
                foreach($confNumberId as $value){
                    array_push($confNumberArray,$value->subscription_transaction_id);
                    $confNumber .= $value->subscription_transaction_id.',';
                }
           }
           
        }

        if($confNumberArray){
            $query = Order::find()->from('tbl_order t')
                    ->where("t.confirmation_number IN(".rtrim($confNumber,',').")");
        } else {
            return false;
            $query = Order::find()->from('tbl_order t')
                    ->where(['t.created_by'=>$roleid]);
        }

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {

                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }
        // grid filtering conditions
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
             't.service_type' => $this->service_type,
            't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.date_modified' => $this->date_modified,
        ]);
       
        if ( ! is_null($this->order_date) && strpos($this->order_date, ' - ') !== false ) { 
            $dates = explode(' - ', $this->order_date);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
        }
        
        if ( ! is_null($this->date_created) && strpos($this->date_created, ' - ') !== false ) { 
            $dates = explode(' - ', $this->date_created);

            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
                        
        }
       
        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2 ." 23:59:59"]);
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 'c2.description', $this->delivery_date])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 'c1.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
            //echo $query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql; die;

        return $dataProvider;

    }

    public function getmhlorder($params){
        $query = Order::find()->from('tbl_order t')
        ->where("`t`.corporate_id IN (select corporate_detail_id from tbl_corporate_details where corporate_type =2)");
        
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);
        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {

                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }
        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(100*($params['page']-1));
                $query->limit(100);
            }
        } else {
            $query->offset(0);
            $query->limit(100);
        }

        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.order_transfer' => $this->order_transfer,
            't.date_modified' => $this->date_modified,
            
        ]);

        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            
            $dates = explode(' - ', $this->order_date);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
        } 

        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);

            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
                        
        }

        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2 ." 23:59:59"]);
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
        ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
        ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
        ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
        ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
        ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
        ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
        ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
        ->andFilterWhere(['like', 't.order_status', $this->order_status])
        ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
        ->andFilterWhere(['like', 'other_comments', $this->other_comments])
        ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
        ->andFilterWhere(['like', 'payment_method', $this->payment_method])
        ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
      //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

        return $dataProvider; 

    }
    public function getmhlcorporateorder($params){
        
        $query = Order::find()->from('tbl_order t')
        ->where("`t`.corporate_id IN (select corporate_detail_id from tbl_corporate_details where corporate_type =1)");
        
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);
        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {
            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {
                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(100*($params['page']-1));
                $query->limit(100);
            }
        } else {
            $query->offset(0);
            $query->limit(100);
        }

        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            
            $dates = explode(' - ', $this->order_date);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
        } else {
            $today = date('Y-m-d');
            $date1 = date("Y-m-d", strtotime("-6 months"));
            $date2 = date('Y-m-d',strtotime("+6 months"));
        }

        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);

            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
                        
        }

        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2 ." 23:59:59"]);
        }
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.order_transfer' => $this->order_transfer,
            't.date_modified' => $this->date_modified,
            
        ]);

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
        ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
        ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
        ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
        ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
        ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
        ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
        ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
        ->andFilterWhere(['like', 't.order_status', $this->order_status])
        ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
        ->andFilterWhere(['like', 'other_comments', $this->other_comments])
        ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
        ->andFilterWhere(['like', 'payment_method', $this->payment_method])
        ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
      //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

        return $dataProvider; 

    }

    public function getnormalorder($params){
        $query = Order::find()->from('tbl_order t')
        ->where("`t`.corporate_id IN (select corporate_detail_id from tbl_corporate_details where NOT corporate_type =1 and NOT corporate_type =2)");
        
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);
        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {
            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {
                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(100*($params['page']-1));
                $query->limit(100);
            }
        } else {
            $query->offset(0);
            $query->limit(100);
        }

        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            
            $dates = explode(' - ', $this->order_date);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
        } else {
            $today = date('Y-m-d');
            $date1 = date("Y-m-d", strtotime("-6 months"));
            $date2 = date('Y-m-d',strtotime("+6 months"));
        }

        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);

            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
                        
        }

        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2 ." 23:59:59"]);
        }
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.order_transfer' => $this->order_transfer,
            't.date_modified' => $this->date_modified,
            
        ]);

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
        ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
        ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
        ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
        ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
        ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
        ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
        ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
        ->andFilterWhere(['like', 't.order_status', $this->order_status])
        ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
        ->andFilterWhere(['like', 'other_comments', $this->other_comments])
        ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
        ->andFilterWhere(['like', 'payment_method', $this->payment_method])
        ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
      //var_dump($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);die;

        return $dataProvider; 

    }
     
    public function getkioskmhlorder($params ,$roleid){
        $kioskName = trim(Yii::$app->user->identity->name," ");
        $today = date('Y-m-d');
        $query = Order::find()->from('tbl_order t')
                ->where(['t.created_by'=>$roleid])
                ->where("t.corporate_id IN (select corporate_detail_id from tbl_corporate_details where corporate_type =2)");
            // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
              ]
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {

                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travel_person' => $this->travel_person,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            't.service_type' => $this->service_type,
            't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.date_modified' => $this->date_modified,
        ]);

        
        if ( ! is_null($this->date_created) && strpos($this->date_created, ' - ') !== false ) { 
            $date = explode(' - ', $this->date_created);
            $query->andFilterWhere(['>=', 't.date_created', $date[0]." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $date[1]." 23:59:59"]);
        } else {
            if($params['r'] == "order/kiosk-orders"){
                $query->andFilterWhere(['>=', 't.date_created', $date1." 00:00:01"]);
                $query->andFilterWhere(['<=', 't.date_created', $date2." 23:59:59"]);
            }
        }

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
            ->andFilterWhere(['like', 'c2.description', $this->delivery_date])
            ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
            ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
            ->andFilterWhere(['like', 'c1.name', $this->fk_tbl_order_id_customer])
            ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
            ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
            ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
            ->andFilterWhere(['like', 't.order_status', $this->order_status])
            ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
            ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
            ->andFilterWhere(['like', 'other_comments', $this->other_comments])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
            ->andFilterWhere(['like', 'payment_status', $this->payment_status])
            ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
           //echo $query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql;die;

        return $dataProvider;
    }

    public function getkioskcorporateorder($params ,$roleid){
        $query = Order::find()->from('tbl_order t')
        ->where(['t.created_by'=>$roleid])
        ->where("t.corporate_id IN (select corporate_detail_id from tbl_corporate_details where corporate_type =1)");
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id_order' => SORT_DESC,
                ],
            ]
        ]);
        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_customer'])) {
            if (!empty($params['OrderSearch']['fk_tbl_order_id_customer'])) {
                $query->joinwith(['fkTblOrderIdCustomer'=>function($q){
                $q->from('tbl_customer c1');
                }]);
                $query->joinwith(['fkTblOrderCorporateId'=>function($q){
                $q->from('tbl_corporate_details c6');
                }]);
            }
        }
        if (isset($params['OrderSearch']['fk_tbl_order_id_slot'])) {

            if (!empty($params['OrderSearch']['fk_tbl_order_id_slot'])) {

                $query->joinwith(['fkTblOrderIdSlot'=>function($q){
                $q->from('tbl_slots c2');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porter'])) {

            if (!empty($params['OrderSearch']['assigned_porter'])) {

                $query->joinwith(['vehicleSlotAllocations.fkTblVehicleSlotAllocationIdEmployee'=>function($q){
                $q->from('tbl_employee c3');
                }]);
            }
        }
        if (isset($params['OrderSearch']['assigned_porterx'])) {

            if (!empty($params['OrderSearch']['assigned_porterx'])) {

                $query->joinwith(['porterxAllocations.porterxAllocationsIdEmployee'=>function($q){
                $q->from('tbl_employee c4');
                }]);
            }
        }
        if (isset($params['OrderSearch']['related_order_id'])) {

            if (!empty($params['OrderSearch']['related_order_id'])) {

                $query->joinwith(['relatedOrder'=>function($q){
                $q->from('tbl_order c5');
                }]);
            }
        }

        if (isset($params['page'])) {
            if (!empty($params['page'])) {
                $query->offset(100*($params['page']-1));
                $query->limit(100);
            }
        } else {
            $query->offset(0);
            $query->limit(100);
        }

        if ( ! is_null($this->order_date) && strpos($this->order_date, '-') !== false ) { 
            
            $dates = explode(' - ', $this->order_date);
            $date1 = date('Y-m-d', strtotime($dates[0]));
            $date2 = date('Y-m-d', strtotime($dates[1]));
            
        } else {
            $today = date('Y-m-d');
            $date1 = date("Y-m-d", strtotime("-6 months"));
            $date2 = date('Y-m-d',strtotime("+6 months"));
        }

        if ( ! is_null($this->date_created) && strpos($this->date_created, '-') !== false ) { 
            $dates = explode(' - ', $this->date_created);

            $bookdate1 = date('Y-m-d', strtotime($dates[0]));
            $bookdate2 = date('Y-m-d', strtotime($dates[1]));
                        
        }

        if(!empty($this->order_date) && (!empty($this->date_created))){
            $query->andWhere("(((t.order_date >= '".$date1."') and (t.order_date <= '".$date2."')) AND ((t.date_created >= '".$bookdate1."') and (t.date_created <= '".$bookdate2."')))");
        } else if(!empty($this->order_date) && empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.order_date', $date1]);
            $query->andFilterWhere(['<=', 't.order_date', $date2]);
        } else if(empty($this->order_date) && !empty($this->date_created)){
            $query->andFilterWhere(['>=', 't.date_created', $bookdate1." 00:00:01"]);
            $query->andFilterWhere(['<=', 't.date_created', $bookdate2 ." 23:59:59"]);
        }
        $query->andFilterWhere([
            't.id_order' => $this->id_order,
            't.departure_time' => $this->departure_time,
            't.arrival_time' => $this->arrival_time,
            't.meet_time_gate' => $this->meet_time_gate,
            't.travell_passenger_name' => $this->travell_passenger_name,
            't.fk_tbl_order_status_id_order_status' => $this->fk_tbl_order_status_id_order_status,
            't.fk_tbl_order_id_pick_drop_location' => $this->fk_tbl_order_id_pick_drop_location,
            't.no_of_units' => $this->no_of_units,
            't.service_type' => $this->service_type,
             't.dservice_type' => $this->dservice_type,
            't.round_trip' => $this->round_trip,
            't.allocation' => $this->allocation,
            't.enable_cod' => $this->enable_cod,
            't.sector' => $this->sector,
            't.location' => $this->location,
            't.fk_tbl_airport_of_operation_airport_name_id' => $this->fk_tbl_airport_of_operation_airport_name_id,
            't.order_transfer' => $this->order_transfer,
            't.date_modified' => $this->date_modified,
            
        ]);

        $query->andFilterWhere(['like', 't.order_number', $this->order_number])
        ->andFilterWhere(['like', 't.sector_name', $this->sector_name])
        ->andFilterWhere(['like', 't.airline_name', $this->airline_name])
        ->andFilterWhere(['like', 't.flight_number', $this->flight_number])
        ->andFilterWhere(['or',['like', 'c1.name', $this->fk_tbl_order_id_customer],['like', 'c6.name', $this->fk_tbl_order_id_customer]])
        ->andFilterWhere(['like', 'c2.time_description', $this->fk_tbl_order_id_slot])
        ->andFilterWhere(['like', 'c3.name', $this->assigned_porter])
        ->andFilterWhere(['like', 'c4.name', $this->assigned_porterx])
        ->andFilterWhere(['like', 't.order_status', $this->order_status])
        ->andFilterWhere(['like', 'c5.order_status', $this->related_order_id])
        ->andFilterWhere(['like', 'other_comments', $this->other_comments])
        ->andFilterWhere(['like', 't.travell_passenger_name', $this->travell_passenger_name])
        ->andFilterWhere(['like', 'payment_method', $this->payment_method])
        ->andFilterWhere(['like', 'payment_transaction_id', $this->payment_transaction_id])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'payment_status', $this->payment_status])
        ->andFilterWhere(['like', 'invoice_number', $this->invoice_number]);
      

        return $dataProvider; 
    }
    
}
