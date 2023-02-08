<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\VehicleType;
use app\models\Route;
use kartik\file\FileInput;
use kartik\datetime\DateTimePicker;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use kartik\select2\Select2;
use app\models\PickDropLocation;
use app\models\Slots;
use app\models\CorporateDetails;
use app\models\CountryCode;
use yii\helpers\Url;
use yii\web\View;
use app\api_v3\v3\models\State;
use app\api_v3\v3\models\DeliveryServiceType;
use app\models\EmployeeAirportRegion;
use app\models\PickDropSpotsType;
use app\models\BuildingRestriction;
use app\models\AirportOfOperation;
use app\models\CityOfOperation;
use app\models\EmployeeRole; 

$gst_percent = Yii::$app->params['gst_percent'];
$corporate_array = [];
$region_array = [];
$airport_array = [];
/* @var $this yii\web\View */
/* @var $model app\models\Vehicle */
/* @var $form yii\widgets\ActiveForm */
$script = <<< JS
$(".order-no_of_units").keyup(function() {
  var noOfluggage = $(".order-no_of_units").val();
  if(noOfluggage >=10){
    $("#noUnits").html('No of luggage Should less than 10 !!');
  }else{
    $("#noUnits").html('');
  }
});
$('.outstation_address').hide();
$('.city_pincode').hide();
$('.to_airport').hide();
$('.from_airport').hide();
$('#order-corporate_id').change(function(){
    $('#order-corporate_id').attr("disabled", true); 
    $('.corporate_id').val($(this).val());
});
$('#order-fk_tbl_airport_of_operation_airport_name_id').change(function(){
    $('#order-fk_tbl_airport_of_operation_airport_name_id').attr("disabled", true); 
    $('.region_id').val($(this).val());
});


$('#state-idstate').change(function(){
    $('#state-idstate').attr("disabled", true); 
    $('.state_id').val($(this).val());
});
$('#order-corporate_id').change(function(){
    $.ajax({
        type     : 'POST',
        data  :  { corporate_id:$(this).val()},
        cache    : true,
        url: 'index.php?r=employee/corporate-airport',
        success  : function(response) {
            console.log(response);
            $( "select#employee-airport" ).html( response );
        }
    });
});

$('.clsServiceType').change(function(){
    if($('.clsServiceType').val() == 1){
        $('.clsdetails').html('Drop Details');
        $('.clsdeparture').show();
        $('.clsarrival').hide();
        // $('.clspickupdetails').show();
        $('#pick_drop_detail').text('Pick Up Details');
        $('.to_airport').show();
        $('.from_airport').hide();
    }else if($('.clsServiceType').val() == 2){
        $('.clsdetails').html('Arrive Details');
        $('.clsdeparture').hide();
        $('.clsarrival').show();
        // $('.clspickupdetails').show();
        $('#pick_drop_detail').text('Drop Off Details');
        $('.to_airport').hide();
        $('.from_airport').show();
    }
})
$('.before').hide();
$('.after').hide();
$('.to_airport_12').hide();
$('.to_airport_3').hide();
$('.to_airport_6').hide();
$('.to_airport_2').hide();
$('.to_airport_9').hide();
$('#order-fk_tbl_order_id_slot').change(function(){
    if($('#order-fk_tbl_order_id_slot').val() == 4){
        $('.before').show();
        $('.after').hide();
        $('.to_airport_12').hide();
        $('.to_airport_3').hide();
        $('.to_airport_6').hide();
        $('.to_airport_2').hide();
        $('.to_airport_9').hide();
    }else if($('#order-fk_tbl_order_id_slot').val() == 5){
        $('.before').hide();
        $('.after').show();
        $('.to_airport_12').hide();
        $('.to_airport_3').hide();
        $('.to_airport_6').hide();
        $('.to_airport_2').hide();
        $('.to_airport_9').hide();
    }else if($('#order-fk_tbl_order_id_slot').val() == 1){
        $('.before').hide();
        $('.after').hide();
        $('.to_airport_12').show();
        $('.to_airport_3').hide();
        $('.to_airport_6').hide();
        $('.to_airport_2').hide();
        $('.to_airport_9').hide();
    }else if($('#order-fk_tbl_order_id_slot').val() == 2){
        $('.before').hide();
        $('.after').hide();
        $('.to_airport_12').hide();
        $('.to_airport_3').show();
        $('.to_airport_6').hide();
        $('.to_airport_2').hide();
        $('.to_airport_9').hide();
    }else if($('#order-fk_tbl_order_id_slot').val() == 3){
        $('.before').hide();
        $('.after').hide();
        $('.to_airport_12').hide();
        $('.to_airport_3').hide();
        $('.to_airport_6').show();
        $('.to_airport_2').hide();
        $('.to_airport_9').hide();
    }else if($('#order-fk_tbl_order_id_slot').val() == 7){
        $('.before').hide();
        $('.after').hide();
        $('.to_airport_12').hide();
        $('.to_airport_3').hide();
        $('.to_airport_6').hide();
        $('.to_airport_2').show();
        $('.to_airport_9').hide();
    }else if($('#order-fk_tbl_order_id_slot').val() == 9){
        $('.before').hide();
        $('.after').hide();
        $('.to_airport_12').hide();
        $('.to_airport_3').hide();
        $('.to_airport_6').hide();
        $('.to_airport_2').hide();
        $('.to_airport_9').show();
    }
});
$("#state-pincode, #ordermetadetails-pickuppincode, #ordermetadetails-droppincode, #ordermetadetails-pickuppersonnumber, #ordermetadetails-droppersonnumber").keypress(function (e) {
     //if the letter is not digit then display error and don't type anything
     if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
        //display error message
        $(".errmsg").html("Digits Only").show().fadeOut("slow");
        return false;
    }
});
var luggage_id_arr = [];
var outstation_array = [];
var array_obj;
array_obj = {};
array_obj["id_luggage_type"] = 0;
array_obj["id_weight_range"] = 0;
array_obj["extra_weight_id"] = 0;
array_obj["deleted_status"] = 0;
outstation_array.push(array_obj);
var luggage_array = {};
// 
var scntDiv = $('.clscls');
var i=2;
var co=0;
var add_luggage_count = 0;
$('.add_button').show();
var totalLuggages = $('.clsnoluggage').length;

var id = $('#orderitems-fk_tbl_order_items_id_luggage_type').val();
$('#employee-airport').change(function(){
    $('#employee-airport').attr("disabled", true); 
    $('.airport_id').val($(this).val());
    if(totalLuggages == 1)
    {
        get_weight_range(0, id);
    }
});
$('#order-fk_tbl_airport_of_operation_airport_name_id').change(function(){
    $('#employee-airport').attr("disabled", true); 
    // $('.airport_id').val($(this).val());
    if(totalLuggages == 1)
    {
        get_weight_range(0, id);
    }
});
$('#addScnt').on('click', function() {
    $('.add_button').hide();
    console.log(totalLuggages, 'lu');
    add_luggage_count++;
    var delivery_type = $('#order-delivery_type').val();
    // luggage_id_arr.splice( add_luggage_count, 1, 'deleted' );
    $.ajax({
        type     :'POST',
        data  :  {add_luggage_count:add_luggage_count,corporateId:0,luggageId:$('#orderitems-fk_tbl_order_items_id_luggage_type').val(),idd:i, delivery_type : delivery_type, totalLuggage : totalLuggages},
        cache    : true,
        url: 'index.php?r=employee/luggage-type1',
        success  : function(response) {
            $('.main_div').append(response);
        }
    });
    totalLuggages++;
    i++;
    co++;
    return false;
});

/*Address details same*/
$('#address_same').on('click', function() {
    if($('#address_same').is(':checked')){
        var passenger_name = $('#order-travell_passenger_name').val();
        var passenger_number = $('#order-travell_passenger_contact').val();
        
        $('#orderspotdetails-person_name').val(passenger_name);
        $('#orderspotdetails-person_mobile_number').val(passenger_number);    
    }else{
        $('#orderspotdetails-person_name').val('');
        $('#orderspotdetails-person_mobile_number').val('');
    }
    
});
if($('#address_same').is(':checked')){
    var passenger_name = $('#order-travell_passenger_name').val();
    var passenger_number = $('#order-travell_passenger_contact').val();
    
    $('#orderspotdetails-person_name').val(passenger_name);
    $('#orderspotdetails-person_mobile_number').val(passenger_number);    
}else{
    $('#orderspotdetails-person_name').val('');
    $('#orderspotdetails-person_mobile_number').val('');
}
$(document).ready(function(){
    $(document).on('click','.cls_delete',function(){
        $(this).parent('div').remove(); 
    });
});
function get_weight_range_luggage(class_count){
    var luggage_id = $('.luggage_'+class_count).val();
    var delivery_type = $('#order-delivery_type').val();
    $.ajax({
            type : 'POST',
            data : {luid:luggage_id,delivery_type: delivery_type },
            url  :'index.php?r=weight-range/weight-range-luggage-id-kiosk',
            success : function(data){
                var obj = {
                    "luggagetype":luggage_id,
                    "weight_id":0,
                    "extra_weight_id" : 0,
                    "item_price":0,
                    "luggage_price_type":"base",
                    "group_type" : 0,
                }
                var array = {
                    "id_luggage_type":luggage_id,
                    "id_weight_range":0,
                    "extra_weight_id" : 0,
                }
                outstation_array.splice( class_count, 1, array );
                luggage_id_arr.splice( class_count, 1, obj );
                $('.added_weight_id_'+class_count).html(data);
            }
    })
}
function search(nameKey, myArray, arrayIndex){
    var result = false;
    for (var i=0; i < myArray.length; i++) {
        var ids = parseInt(nameKey);
        if (myArray[i].luggagetype == nameKey && arrayIndex != i && i < arrayIndex) {
                return  myArray[i];
        }else if(ids == 2 && i < arrayIndex){
            return  myArray[i];
        }else if(ids == 3 && i < arrayIndex){
            return  myArray[i];
        }else if(ids == 6 && i < arrayIndex){
            return  myArray[i];
        }
    }
    return result;
}
function search_remove(nameKey, myArray, arrayIndex){
    var result = false;
    for (var i=0; i < myArray.length; i++) {
        if (myArray[i].luggagetype == nameKey && arrayIndex == i) {
            return  myArray[i];
        }
    }
    return result;
}
$('.above_weight_0').hide();
var total_item_price = 0;

function get_weight_range(class_count, luggage_id){
    $('.add_button').show();
    var city_id = $('#order-fk_tbl_airport_of_operation_airport_name_id').val();
    var order_transfer = $('#order-order_transfer').val();
    var delivery_type = $('#order-delivery_type').val();
    var stateId   = $('#state-idstate').val();
    var pincode = $('#state-pincode').val();
    var express_extra_charge = $('#order-extra_charges').val();
    var outstation_extra_charge = $('#order-outstation_extra_charges').val();

    var service_type = $('#order-service_type').val();
    var weight_id = $('.added_weight_id_'+class_count).val();
    var extra_weight_id = $('.above_weight_'+class_count).val();
    var luggage_id = $('.luggage_'+class_count).val();
    var airport_id = $('#employee-airport').val();
    var resultObject = search(luggage_id, luggage_id_arr, class_count);
    console.log(class_count, 'class_count');
    
    if(weight_id == 8){
        $('.above_weight_'+class_count).show();
    }else{
        $('.above_weight_'+class_count).hide();
        $('.above_weight_'+class_count).val('');
    }
    // if(delivery_type == 2){
        console.log(outstation_array.length);
        for (var i=0; i < outstation_array.length; i++) {
            //outstation_array[class_count] = {};
            outstation_array[class_count].id_luggage_type = luggage_id;
            outstation_array[class_count].id_weight_range = weight_id;
            if(extra_weight_id){
                outstation_array[class_count].extra_weight_id = extra_weight_id;
            }else{
                outstation_array[class_count].extra_weight_id = 0;
            }
            outstation_array[class_count].deleted_status = 0;
        }
        if(class_count == 0){
            luggage_array = {"airport_name_id" : airport_id, "order_transfer" : order_transfer, "city_id" : city_id, "delivery_type" : delivery_type, "pincode" : pincode, "stateId" : stateId,"insurance_price" : "0","express_extra_charge" : express_extra_charge, "outstation_extra_charge" : outstation_extra_charge, "luggage_items" : outstation_array, "service_type" : service_type, "outstation_id" : 0,"zone_city_id" : 0};
        }else{
            luggage_array = luggage_array;
        }
        console.log(luggage_array, 'luggage_array');
        if(document.getElementById('order-insurance_price').checked == true){
            luggage_array.insurance_price  = "1";
        }
        if(delivery_type == 1){
            $.ajax({
                type     :'POST',
                data  :  {luggage_array : luggage_array},
                cache    : true,
                // async: true,
                url: 'index.php?r=v3/calculation-api/bookingcalculation',
                beforeSend: function(){
                 $(".loaderTop").show();
               },
               complete: function(){
                 $(".loaderTop").hide();
               },
                success  : function(response) {
                    luggage_array = {"airport_name_id" : airport_id, "outstation_id" : response.outstation_id,"zone_city_id" : response.city_id, "order_transfer" : order_transfer, "city_id" : city_id, "delivery_type" : delivery_type, "pincode" : pincode, "stateId" : stateId,"insurance_price" : "0","express_extra_charge" : express_extra_charge, "outstation_extra_charge" : outstation_extra_charge, "luggage_items" : outstation_array, "service_type" : service_type};
                    console.log(response, 'response');
                    if(response.status == 'error'){
                        $("#bsModal3").modal('show');
                        $('#outstation_error').text(response.message);
                    }else{
                        if(outstation_array.length == 1){
                            var km_detection = response.extra_kilometer - 20;
                            if(response.extra_kilometer > 20){
                                $('#order-extr_kms').val(km_detection);
                                $('#order-extr_km').val(response.extra_kilometer);
                            }else{
                                $('#order-extr_km').val(response.extra_kilometer);
                                $('#order-extr_kms').val(0);
                            }
                            $('#order-outstation_charge').val(response.outstation_charge);
                            $('#order-extr_kilometer').val(response.extra_km_price);
                            $('#order-per_km_price').val(response.per_km_price);

                            var gst = (parseFloat(response.extra_km_price) + parseFloat(response.outstation_charge)) * ($gst_percent/100);

                            $('#order-luggagegst').val(gst.toFixed(2));
                        }
                        var totalLuggage = $('.clsnoluggage').length;
                        // console.log(totalLuggage, 'totalLuggage');
                        $('.order-no_of_units').val(totalLuggage);
                        $('.order-no_of_units').text(totalLuggage + ' Bags');
                        $('#order-totalprice').val(response.Base_Price);
                        var luggage_gst = response.Base_Price * ($gst_percent/100);
                        $('#order-service_tax_amount').val(luggage_gst.toFixed(2));
                             
                        // $('#order-extr_kms').val(response.extra_kilometer);        
                        $('#order-in_price').val(response.Insurance_Price);
                        $('#order-tax').val(response.Insurance_Tax);
                        
                        $('#order-luggage_price').val(response.Order_Amount);

                        $('.outstation_id').val(response.outstation_id);
                        $('.city_id').val(response.city_id);

                        $('#price_array').val(JSON.stringify(response.luggage_details));
                        // $(".errmsg").html(" ").hide();
                        // $(".errmsg").html("Pincode not exist").show();
                    }
                }
            });
        }    
}
$("#calculate").click(function(){
       $.ajax({
            type     :'POST',
            data  :  {luggage_array : luggage_array},
            cache    : true,
            // async: true,
            url: 'index.php?r=v3/calculation-api/bookingcalculation',
            beforeSend: function(){
             $(".loaderTop").show();
           },
           complete: function(){


             $(".loaderTop").hide();
           },
            success  : function(response) {
                // debugger;
                // luggage_array = {"airport_name_id" : airport_id, "outstation_id" : response.outstation_id,"zone_city_id" : response.city_id, "order_transfer" : order_transfer, "city_id" : city_id, "delivery_type" : delivery_type, "pincode" : pincode, "stateId" : stateId,"insurance_price" : "0","express_extra_charge" : express_extra_charge, "outstation_extra_charge" : outstation_extra_charge, "luggage_items" : outstation_array, "service_type" : service_type};
                console.log(response, 'response');
                if(response.status == 'error'){
                    $("#bsModal3").modal('show');
                    $('#outstation_error').text(response.message);
                }else{
                    // if(outstation_array.length == 1){
                    var km_detection = response.extra_kilometer - 20;
                    if(response.extra_kilometer > 20){
                        $('#order-extr_kms').val(km_detection);
                        $('#order-extr_km').val(response.extra_kilometer);
                    }else{
                        $('#order-extr_km').val(response.extra_kilometer);
                        $('#order-extr_kms').val(0);
                    }
                    $('#order-outstation_charge').val(response.outstation_charge);
                    $('#order-extr_kilometer').val(response.extra_km_price);
                    $('#order-per_km_price').val(response.per_km_price);

                    var gst = (parseFloat(response.extra_km_price) + parseFloat(response.outstation_charge)) * ($gst_percent/100);

                    $('#order-luggagegst').val(gst.toFixed(2));
                    // }
                    var totalLuggage = $('.clsnoluggage').length;
                    // console.log(totalLuggage, 'totalLuggage');
                    $('.order-no_of_units').val(totalLuggage);
                    $('.order-no_of_units').text(totalLuggage + ' Bags');
                    $('#order-totalprice').val(response.Base_Price);
                    var luggage_gst = response.Base_Price * ($gst_percent/100);
                    $('#order-service_tax_amount').val(luggage_gst.toFixed(2));
                         
                    // $('#order-extr_kms').val(response.extra_kilometer);        
                    $('#order-in_price').val(response.Insurance_Price);
                    $('#order-tax').val(response.Insurance_Tax);
                    
                    $('#order-luggage_price').val(response.Order_Amount);

                    $('.outstation_id').val(response.outstation_id);
                    $('.city_id').val(response.city_id);

                    $('#price_array').val(JSON.stringify(response.luggage_details));
                    // $(".errmsg").html(" ").hide();
                    // $(".errmsg").html("Pincode not exist").show();
                }
            }
        }); 
});
function removes(array, element) {
    const index = array.indexOf(element);
    array.splice(element, 1, {});
}
function remove_luggage(luggage_count){
    var luggage_id = $('.luggage_'+luggage_count).val();
    var remove = search_remove(luggage_id, luggage_id_arr, luggage_count);
    var delivery_type = $('#order-delivery_type').val();
    // if(delivery_type == 2){
        $('#add_luggage_count_'+luggage_count).remove();
        // totalLuggages--;
        outstation_array[luggage_count].deleted_status=1;
        if(document.getElementById('order-insurance_price').checked == true){
            luggage_array.insurance_price  = "1";
        }else{
            luggage_array.insurance_price  = "0";
        }
        $.ajax({
            type     :'POST',
            data  :  {luggage_array : luggage_array},
            cache    : true,
            //async: false,
            url: 'index.php?r=v3/calculation-api/bookingcalculation',
            beforeSend: function(){
             $(".loaderTop").show();
           },
           complete: function(){
             $(".loaderTop").hide();
           },
            success  : function(response) {
                console.log(response, 'de');
                var totalLuggage = $('.clsnoluggage').length;
                $('.order-no_of_units').val(totalLuggage);
                $('.order-no_of_units').text(totalLuggage + ' Bags'); 
                $('#order-totalprice').val(response.Base_Price);
                $('#order-service_tax_amount').val(response.Service_Tax);
                $('#order-in_price').val(response.Insurance_Price);
                $('#order-tax').val(response.Insurance_Tax);
                $('#order-outstation_charge').val(response.outstation_charge);
                $('#order-luggage_price').val(response.Order_Amount);
            }
        });
        if(document.getElementById('order-insurance_price').checked == true){
            $.ajax({
                type     :'POST',
                data  :  {luggage_array : luggage_array},
                cache    : true,
                async: false,
                url: 'index.php?r=v3/calculation-api/bookingcalculation',
                success  : function(response) {
                    $('#order-in_price').val(response.Insurance_Price);
                    $('#order-tax').val(response.Insurance_Tax);
                }
            });
        }
}
$('#order-insurance_price').on('click', function() {
    var price = $('#order-totalprice').val();
    var delivery_type = $('#order-delivery_type').val();
    var service_tax = $('#order-service_tax_amount').val();
    var insurance = $('#order-in_price').val();
    var in_tax = $('#order-tax').val();
    var ids = [];
    var insur = 0;

    //if(delivery_type == 2){
        if(document.getElementById('order-insurance_price').checked == true){
            luggage_array.insurance_price  = "1";
            $.ajax({
                type     :'POST',
                data  :  {luggage_array : luggage_array},
                cache    : true,
                // async: false,
                url: 'index.php?r=v3/calculation-api/bookingcalculation',
                beforeSend: function(){
                     $(".loaderTop").show();
                },
                complete: function(){
                     $(".loaderTop").hide();
                },
                success  : function(response) {
                    $('#order-luggage_price').val(response.Order_Amount);
                    $('#order-in_price').val(response.Insurance_Price);
                    $('#order-tax').val(response.Insurance_Tax);
                }
            });
        }else{
            luggage_array.insurance_price  = "0";
            $.ajax({
                type     :'POST',
                data  :  {luggage_array : luggage_array},
                cache    : true,
                // async: false,
                url: 'index.php?r=v3/calculation-api/bookingcalculation',
                beforeSend: function(){
                 $(".loaderTop").show();
               },
               complete: function(){
                 $(".loaderTop").hide();
               },
                success  : function(response) {
                    $('#order-luggage_price').val(response.Order_Amount);
                    $('#order-in_price').val(response.Insurance_Price);
                    $('#order-tax').val(response.Insurance_Tax);
                }
            });
        }
});
//if yes for extra weight 
$('#yes').click(function(){
   $('.bagdetails').show();
});
$('#no').click(function(){
   $('.bagdetails').hide();
});
 $('.clsradio').click(function(){
    var chk = $('#order-weight input:radio:checked');
    var vall = chk.attr('value');
    if(vall == 1){
         $('.bagdetails').show();
    }else{
         $('.bagdetails').hide();
    }
});
$("#order-extra_charges").keyup(function(){
   var extra_amount = $(this).val();
   var outstation_amount = $('#order-outstation_extra_charges').val();
   var outstation_extra_tax = outstation_amount * ($gst_percent/100);
   var extra_tax = extra_amount * ($gst_percent/100);
   var total = $('#order-totalprice').val();
   var tax   = $('#order-service_tax_amount').val();
   if(extra_amount && outstation_amount){
        $('#order-luggage_price').val(parseInt(total) + parseFloat(tax) + parseFloat(extra_amount) + parseFloat(extra_tax) + parseFloat(outstation_amount) + parseFloat(outstation_extra_tax));
   }else if(extra_amount && !outstation_amount){
        $('#order-luggage_price').val(parseInt(total) + parseInt(tax) + parseFloat(extra_amount) + parseFloat(extra_tax));
   }else{
        $('#order-luggage_price').val(parseInt(total) + parseInt(tax));
   }
   
});
$("#order-outstation_extra_charges").keyup(function(){
   var outstation_extra_charges = $(this).val();
   var outstation_extra_tax = outstation_extra_charges * ($gst_percent/100);
   var express_amount = $('#order-extra_charges').val();
   var express_extra_tax = express_amount * ($gst_percent/100);
   var total = $('#order-totalprice').val();
   var tax   = $('#order-service_tax_amount').val();
   if(outstation_extra_charges && express_amount){
        $('#order-luggage_price').val(parseInt(total) + parseFloat(tax) + parseFloat(outstation_extra_charges) + parseFloat(outstation_extra_tax) + parseFloat(express_amount) + parseFloat(express_extra_tax));
   }else if(outstation_extra_charges && !express_amount){
        $('#order-luggage_price').val(parseInt(total) + parseInt(tax) + parseFloat(outstation_extra_charges) + parseFloat(outstation_extra_tax));
   }else{
        $('#order-luggage_price').val(parseInt(total) + parseInt(tax));
   }
   
});

if(id){
    $.ajax({
        type : 'POST',
        data : {luid:id },
        url  :'index.php?r=weight-range/weight-range-luggage-id',
        success : function(data){
            $('#orderitems-fk_tbl_order_items_id_weight_range').html(data);
            var weight_id = $('#orderitems-fk_tbl_order_items_id_weight_range').val();
            var obj = {
                "luggagetype":id,
                "weight_id":weight_id,
                "item_price":0,
                "luggage_price_type":"base",
                "group_type" : 0,
            }
            var array = {
                "id_luggage_type":id,
                "id_weight_range":weight_id,
                "extra_weight_id" : 0,
                "deleted_status" : 0,
            }
            outstation_array.splice( 0, 1, array );
            // luggage_id_arr.splice( 0, 1, obj );
        }
    })
}
get_weight_range(0, id);

$('#orderspotdetails-fk_tbl_order_spot_details_id_pick_drop_spots_type').change(function(){
    var locationType = $(this).val();
    if(locationType == 2){
        $('.clshotel').show();
        $('.clshotell').hide(); 
        $('.clsbusiness').hide();
        $('.clsretail').hide();
    }else if(locationType == 3){
        $('.clshotel').hide();
        $('.clsbusiness').show();
        $('.clshotell').show();
        $('.clsretail').hide();
    }else if(locationType == 4){
        $('.clshotel').hide();
        $('.clshotell').show(); 
        $('.clsbusiness').hide();
        $('.clsretail').show();
    }else if(locationType == 1){
        $('.clshotel').hide();
        $('.clshotell').show(); 
        $('.clsbusiness').hide();
        $('.clsretail').hide();
    }
});
$('#orderitems-fk_tbl_order_items_id_luggage_type').change(function(){
    var id = $(this).val();
    var weight_id = $('#orderitems-fk_tbl_order_items_id_weight_range').val();
    var delivery_type = $('#order-delivery_type').val();
    if(id){
        $.ajax({
            type : 'POST',
            data : {luid:id, delivery_type : delivery_type },
            url  :'index.php?r=weight-range/weight-range-luggage-id-kiosk',
            success : function(data){
                $('#orderitems-fk_tbl_order_items_id_weight_range').html(data);

                if(id == 1 || id == 2 || id == 3 || id == 6){
                    var obj = {
                        "luggagetype":id,
                        "weight_id":weight_id,
                        "item_price":data,
                        "luggage_price_type":"base",
                        "group_type" : 1,
                    }
                }
                if(id == 4){
                    var obj = {
                        "luggagetype":id,
                        "weight_id":weight_id,
                        "item_price":data,
                        "luggage_price_type":"base",
                        "group_type" : 2,
                    }
                }
                if(id == 5){
                   var obj = {
                        "luggagetype":id,
                        "weight_id":weight_id,
                        "item_price":data,
                        "luggage_price_type":"base",
                        "group_type" : 3,
                    } 
                }
                luggage_id_arr.splice( 0, 1, obj );
            }
        })
    }
    array_obj = {};
    array_obj["id_luggage_type"] = id;
    array_obj["id_weight_range"] = 0;
    array_obj["extra_weight_id"] = 0;
    array_obj["express_extra_charge"] = 0;
    array_obj["outstation_extra_charge"] = 0; 
    array_obj["deleted_status"] = 0; 
    outstation_array.splice( $(this), 1, array_obj );
});
$('#orderspotdetails-fk_tbl_order_spot_details_id_contact_person_hotel').change(function(){
    if($(this).val()==4){
        $('.clshotell').show(); 

        $('.dropclshotell').show();
    }else{
       $('.clshotell').hide(); 

       $('.dropclshotell').hide();  
    }
});
$('.outstation_details').hide();
$('.calculate').hide();
$('.pincode1').hide();
$('.pincode3').hide();
$('.state1').hide();
$('.state2').hide();
$('.pincode4').hide();      
$('.pincode2').hide();
$('.pincode_validate1').hide();
$('.pincode_validate2').hide();
$('.validate').hide();
$('#order-service_type').change(function(){
    $('#order-service_type').attr("disabled", true); 
    var pincode = $('#state-pincode').val();
    var order_service = $('#order-service_type').val();
    var order_delivery = $('#order-delivery_type').val();
    var order_transfer = $('#order-order_transfer').val();
    $('.service_type').val($(this).val());

    if(order_delivery == 2){
        $('#orderspotdetails-pincode').val(pincode);

        $('#orderspotdetails-pincode').attr('readonly', true);
        // $('.validate').show();
        
        if(order_service  == 1){
            $('.pincode_validate1').hide();
            $('.pincode_validate2').show();

            $('.state2').hide();
            $('.pincode2').hide();
            $('.pincode1').show();

            $('.state1').show();

            $('#ordermetadetails-droppincode').val('');
            $('#ordermetadetails-pickuppincode').val(pincode);
            $('#ordermetadetails-droppincode').attr('readonly', false);
            $('#ordermetadetails-pickuppincode').attr('readonly', true);
        }else{
            $('.pincode_validate1').show();
            $('.pincode_validate2').hide();

            $('.state1').hide();
            $('.pincode1').hide();
            $('.state2').show();

            $('.pincode2').show();

            $('#ordermetadetails-droppincode').val(pincode);
            $('#ordermetadetails-droppincode').attr('readonly', true);
            $('#ordermetadetails-pickuppincode').val('');
            $('#ordermetadetails-pickuppincode').attr('readonly', false);
        }
    }else if(order_delivery == 1){
        if(order_delivery == 1 && order_transfer == 1){
            $('.validate').show();
            $('.pincode_validate1').show();
            $('.pincode_validate2').hide();
        }
        if(order_service  == 1){
            $('.pincode4').hide();
            $('.pincode3').show();
           
        }else{
            
            $('.pincode3').hide();
            $('.pincode4').show();
        }
    }else{
        $('#orderspotdetails-pincode').attr('readonly', false);
    }
});
$('#order-order_transfer').change(function(){
    $('#order-order_transfer').attr("disabled", true); 
    $('.order_transfer').val($(this).val());
    var order_transfer = $('#order-order_transfer').val();
    var order_type = $('#order-delivery_type').val();
    if(order_transfer == 1 && order_type == 2){
        $('.outstation_address').show();
        $('.clspickupdetails').hide();
        $('.city_pincode').hide();

        var newOptions = {
          "Select Service Type" : "0",
          "To City": "1",
          "From City": "2"
        };

        var el = $("#order-service_type");
        el.empty(); // remove old options
        $.each(newOptions, function(key,value) {
          el.append($("<option></option>")
             .attr("value", value).text(key));
        });

        $('.airport').hide();
        $('.region').show();
    }else if(order_transfer == 2 && order_type == 2){
        $('.city_pincode').hide();
        $('.outstation_address').hide();
        $('.clspickupdetails').show();

        var newOptions = {
          "Select Service Type" : "0",
          "To Airport": "1",
          "From Airport": "2"
        };

        var el = $("#order-service_type");
        el.empty(); // remove old options
        $.each(newOptions, function(key,value) {
          el.append($("<option></option>")
             .attr("value", value).text(key));
        });

        $('.airport').show();
        $('.region').hide();
    }else if(order_transfer == 1 && order_type == 1){
        $('.outstation_address').show();
        $('.clspickupdetails').hide();
        $('.city_pincode').show();

        var newOptions = {
          "Select Service Type" : "0",
          "To City": "1",
        };

        var el = $("#order-service_type");
        el.empty(); // remove old options
        $.each(newOptions, function(key,value) {
          el.append($("<option></option>")
             .attr("value", value).text(key));
        });

        $('.airport').hide();
        $('.region').show();
    }else if(order_transfer == 2 && order_type == 1){
        // $('.city_pincode').show();
        $('.city_pincode').hide();
        $('.outstation_address').hide();
        var newOptions = {
          "Select Service Type" : "0",
          "To Airport": "1",
          "From Airport": "2"
        };

        var el = $("#order-service_type");
        el.empty(); // remove old options
        $.each(newOptions, function(key,value) {
          el.append($("<option></option>")
             .attr("value", value).text(key));
        });

        $('.airport').show();
        $('.region').hide();
    }
}); 
$('#order-delivery_type').change(function(){
    $('#order-delivery_type').attr("disabled", true); 
    var order_transfer = $('#order-order_transfer').val();
    $('.delivery_type').val($(this).val());

    var weight_id  = $('#orderitems-fk_tbl_order_items_id_weight_range').val();
    var luggage_id = $('#orderitems-fk_tbl_order_items_id_luggage_type').val();
    var airport_id = $('#employee-airport').val();

    if($(this).val() == 2 && order_transfer == 1){
        
        $('.outstation_address').show();
        $('.clspickupdetails').hide();

        var newOptions = {
          "Select Service Type" : "0",
          "To City": "1",
          "From City": "2"
        };

        var el = $("#order-service_type");
        el.empty(); // remove old options
        $.each(newOptions, function(key,value) {
          el.append($("<option></option>")
             .attr("value", value).text(key));
        });

        $('.airport').hide();
        $('.region').show();
    }else if($(this).val() == 2 && order_transfer == 2){
        $('.outstation_address').hide();
        $('.clspickupdetails').show();
        $('.airport').show();
        $('.region').hide();

        var newOptions = {
          "Select Service Type" : "0",
          "To Airport": "1",
          "From Airport": "2"
        };

        var el = $("#order-service_type");
        el.empty(); // remove old options
        $.each(newOptions, function(key,value) {
          el.append($("<option></option>")
             .attr("value", value).text(key));
        });
    }else{
        $('.airport').show();
        $('.region').show();

        $('#orderspotdetails-pincode').val('');

        $('#orderspotdetails-pincode').attr('readonly', false);
    }
    if($(this).val() == 2){
        $('.delivery').show(); 
        $('.calculate').show();
        $('.outstation_details').show();
        $.ajax({
            url  :'index.php?r=weight-range/get-outstation-luggage',
            success : function(data){
                $('#orderitems-fk_tbl_order_items_id_luggage_type').html(data);
                $('.clsnoluggage').html(data);
            }
        });
        $('#order-totalprice').val(0);
        $('#order-service_tax_amount').val(0);
        $('#order-in_price').val(0);
        $('#order-tax').val(0);
        $('#order-outstation_charge').val(0);
        // $('#order-luggage_price').val(0);
    }else{
        // $('.order_transfer').hide();
        $('.delivery').show(); 

       $('.outstation_details').hide(); 
       $('#state-idstate').val('');
       $('#state-pincode').val('');
       $.ajax({
            url  :'index.php?r=weight-range/get-local-luggage',
            success : function(data){
                $('#orderitems-fk_tbl_order_items_id_luggage_type').html(data);
                $('.clsnoluggage').html(data);
            }
        });
    }
    var array = {
        "id_luggage_type":luggage_id,
        "id_weight_range":weight_id,
        "extra_weight_id" : 0,
        "deleted_status" : 0,
    }
    outstation_array.splice( 0, 1, array );
    // outstation_array = [];
    luggage_array = {"airport_name_id" : airport_id, "delivery_type" : $(this).val(),  "order_transfer" : order_transfer, "city_id" : 0, "pincode" : 0,"express_extra_charge" : 0, "outstation_extra_charge" : 0,  "stateId" : 0,"insurance_price" : "0", "luggage_items" : outstation_array, "service_type" : 0};
});
var extr_km_price = 0;
$("#order-city_pincode").change(function(){
    var pincode = $('#order-city_pincode').val(); 
    
    $('#orderspotdetails-pincode').val(pincode);
    var order_service = $('#order-service_type').val();
    
    // $('.drop_pincode').val(pincode);
    // $('.drop_pincode').attr('readonly', true);
    
});
$("#order-validate_pincode").change(function(){
    var order_service = $('#order-service_type').val();
    var order_type= $('#order-delivery_type').val();
    var pincode = $('#order-validate_pincode').val();
    
    var service_type = $('#order-service_type').val(); 
    var city_id = $('#order-fk_tbl_airport_of_operation_airport_name_id').val();
    var airport_id = $('#employee-airport').val();
    $.ajax({
        type: "post",
        dataType: 'json',
        url: "index.php?r=order-api/checkpincodeavailability",
        data: {pincode : pincode, service_type : service_type, city_id : city_id, airport_id : airport_id},
        async:false,
        success: function(data) {
            console.log(data);
            if(data == 1){ 
                $('#order-validate_pincode').attr('readonly', true);
                if(order_type == 1){
                    $('.pickup_pincode').val(pincode);
                    $('.pickup_pincode').attr('readonly', true);
                }else{
                    if(order_service == 1){
                        $('.drop_pincode').val(pincode);
                        $('.drop_pincode').attr('readonly', true);
                    }else{
                        $('.pickup_pincode').val(pincode);
                        $('.pickup_pincode').attr('readonly', true);
                    }
                }
            }else{
                alert('Sorry! Service not available for the selected area');
                $('#order-validate_pincode').val('');
                $('#order-validate_pincode').attr('readonly', false);
                
                return false;
            }                    
            
        }
    });
});
$("#state-pincode").change(function(){
    // $('#orderitems-fk_tbl_order_items_id_luggage_type').val(' ');
    var state_name = $("#state-idstate option:selected").text();

    var pincode = $('#state-pincode').val();

    var order_service = $('#order-service_type').val();
    var order_delivery = $('#order-delivery_type').val();
    if(order_delivery == 2){
        $('#orderspotdetails-pincode').val(pincode);

        $('#orderspotdetails-pincode').attr('readonly', true);
        if(order_service  == 1){
            // $('#ordermetadetails-droppincode').val('');

            $('#ordermetadetails-pickuppincode').val(pincode);

            // $('#ordermetadetails-droppincode').attr('readonly', false);

            $('#ordermetadetails-pickuppincode').attr('readonly', true);
        }else{
           $('#ordermetadetails-droppincode').val(pincode);

           $('#ordermetadetails-droppincode').attr('readonly', true);

           // $('#ordermetadetails-pickuppincode').val('');

           // $('#ordermetadetails-pickuppincode').attr('readonly', false);
        }
    }else{
        $('#orderspotdetails-pincode').attr('readonly', false);
    }
    $('#order-totalprice').val(0);
    $('#order-luggagegst').val(0);
    $('#order-service_tax_amount').val(0);
    $('#order-extr_km').val(0);
    $('#order-extr_kms').val(0);        
    $('#order-in_price').val(0);
    $('#order-tax').val(0);

    $('#order-outstation_charge').val(0);
    $('#order-luggage_price').val(0);
    $('#order-per_km_price').val(0);

    $('#order-extr_kilometer').val(0);

    $('.outstation_id').val(0);
    $('.city_id').val(0);

    $('#price_array').val(JSON.stringify(''));
});
$('.extra_charges').hide();
$('.outstation_extra_charges').hide();
$('#order-dservice_type').change(function(){
    if($(this).val()==3){
        $('.extra_charges').show(); 
        $('.outstation').hide();
        $('.outstation_extra_charges').show();
        $('#order-extra_charges').val(0);
        $('#order-outstation_extra_charges').val(0);
        var total_price =  $('#order-totalprice').val();
        var total_tax =  $('#order-service_tax_amount').val();
        var totals = parseFloat(total_tax) + parseInt(total_price);
        $('#order-luggage_price').val(totals);
    }else if($(this).val()==4){
        $('.extra_charges').show();
        $('.outstation').hide();
        $('.outstation_extra_charges').hide();
        $('#order-extra_charges').val(0);
        $('#order-outstation_extra_charges').val(0);
        var total_price =  $('#order-totalprice').val();
        var total_tax =  $('#order-service_tax_amount').val();
        var totals = parseFloat(total_tax) + parseInt(total_price);
        $('#order-luggage_price').val(totals);
    }else if($(this).val()==5){
        $('.extra_charges').show();
        $('.outstation').hide();
        $('.outstation_extra_charges').hide();
        $('#order-extra_charges').val(0);
        $('#order-outstation_extra_charges').val(0);
        var total_price =  $('#order-totalprice').val();
        var total_tax =  $('#order-service_tax_amount').val();
        var totals = parseFloat(total_tax) + parseInt(total_price);
        $('#order-luggage_price').val(totals);
    }else if($(this).val()==8){
        $('.extra_charges').show();
        $('.outstation').hide();
        $('.outstation_extra_charges').hide();
        $('#order-extra_charges').val(0);
        $('#order-outstation_extra_charges').val(0);
        var total_price =  $('#order-totalprice').val();
        var total_tax =  $('#order-service_tax_amount').val();
        var totals = parseFloat(total_tax) + parseInt(total_price);
        $('#order-luggage_price').val(totals);
    }else if($(this).val()==9){
        $('.express').hide();
        $('.outstation').show();
        $('.extra_charges').show();
        $('.outstation_extra_charges').hide();
        $('#order-extra_charges').val(0);
        $('#order-outstation_extra_charges').val(0);
        var total_price =  $('#order-totalprice').val();
        var total_tax =  $('#order-service_tax_amount').val();
        var totals = parseFloat(total_tax) + parseInt(total_price);
        $('#order-luggage_price').val(totals);
    }else{
       $('.extra_charges').hide();
       $('.outstation_extra_charges').hide();
       var total_price =  $('#order-totalprice').val();
       $('#order-extra_charges').val(0);
       $('#order-outstation_extra_charges').val(0);
       var total_tax =  $('#order-service_tax_amount').val();
       var totals = parseFloat(total_tax) + parseInt(total_price);
       $('#order-luggage_price').val(totals); 
    }
});
function isNumberKey(evt)
{
  var charCode = (evt.which) ? evt.which : evt.keyCode;
  if (charCode != 46 && charCode > 31 
    && (charCode < 48 || charCode > 57))
     return false;

  return true;
}
function locallocationType(evt){
    // console.log(evt.value); return false;
    var locationType = evt.value;
    if(locationType == 2){
        $('.clshotel').show();
        $('.clshotell').hide(); 
        $('.clsbusiness').hide();
        $('.clsretail').hide();
    }else if(locationType == 3){
        $('.clshotel').hide();
        $('.clsbusiness').show();
        $('.clshotell').show();
        $('.clsretail').hide();
    }else if(locationType == 4){
        $('.clshotel').hide();
        $('.clshotell').show(); 
        $('.clsbusiness').hide();
        $('.clsretail').show();
    }else if(locationType == 1){
        $('.clshotel').hide();
        $('.clshotell').show(); 
        $('.clsbusiness').hide();
        $('.clsretail').hide();
    }
}
function droplocationType(evt){
    // console.log(evt.value); return false;
    var locationType = evt.value;
    if(locationType == 2){
        $('.dropclshotel').show();
        $('.dropclshotell').hide(); 
        $('.dropclsbusiness').hide();
        $('.dropclsretail').hide();

    }else if(locationType == 3){
        $('.dropclshotel').hide();
        $('.dropclsbusiness').show();
        $('.dropclshotell').show();
        $('.dropclsretail').hide();

    }else if(locationType == 4){
        $('.dropclshotel').hide();
        $('.dropclshotell').show(); 
        $('.dropclsbusiness').hide();
        $('.dropclsretail').show();

    }else if(locationType == 1){
        
        $('.dropclshotel').hide();
        $('.dropclshotell').show(); 
        $('.dropclsbusiness').hide();
        $('.dropclsretail').hide();
    }
}
function pickupType(evt){
    // console.log(evt.value); return false;
    var dropType = evt.value;
    if(dropType == 2){
        $('.pickupclshotel').show();
        $('.pickupclshotell').hide(); 
        $('.pickupclsbusiness').hide();
        $('.pickupclsretail').hide();

    }else if(dropType == 3){

        $('.pickupclshotel').hide();
        $('.pickupclsbusiness').show();
        $('.pickupclshotell').show();
        $('.pickupclsretail').hide();

    }else if(dropType == 4){
    
        $('.pickupclshotel').hide();
        $('.pickupclshotell').show(); 
        $('.pickupclsbusiness').hide();
        $('.pickupclsretail').show();

    }else if(dropType == 1){
        $('.pickupclshotel').hide();
        $('.pickupclshotell').show(); 
        $('.pickupclsbusiness').hide();
        $('.pickupclsretail').hide();
    }
}
$('#order-totalprice').val(399);
$('#order-service_tax_amount').val(19.95);
$('#order-luggage_price').val(418.95);
$('.clsbuilding').click(function () {
    if($(this).val() == 4){
        if($(this).is(":checked")) {
        $('.clsothercomments').show();
        }else{
            $('.clsothercomments').val(""); 
          $('.clsothercomments').hide();  
        }  
    }
});

JS;
$this->registerJs($script,View::POS_END,'JS');

$this->title = 'Create General Order';
$this->params['breadcrumbs'][] = ['label' => 'Corporate'/*, 'url' => ['index']*/];
$this->params['breadcrumbs'][] = $this->title;
$service_type = [1 => 'To Airport',2 => 'From Airport'];
$delivery_type = [1 => 'Local',2 => 'Outstation'];
$transfer_type = [1 => 'City Transfer',2 => 'Airport Transfer'];
// $dservice_type = [0 => 'Select Delivery',  8=> 'Express'];
$dservice_type = [7=>'Normal Delivery', 1 => 'Repairs',2 => 'Reverse Pick Up',3=>'Express - Outstation', 4 => 'Express - Fragile', 5 => 'Outstation- Fragile', 6=>'Normal - Fragile',  8=> 'Express', 9=>'Outstation'];
// $dservice_type = DeliveryServiceType::find()
//             ->select(['id_delivery_type','delivery_category'])
//             ->where(['status'=>1, 'order_type' => 1])
//             ->all();
// $dservice_type = ArrayHelper::map($dservice_type,'id_delivery_type','delivery_category');
$hotel_type = [1=>'Reception',2=>'Travel Desk',3=>'Concierge',4=>'Meeting Somebody'];
$sector_type = ['north'=>'North','south'=>'South','east'=>'East','west'=>'West']; 
$pickLocation = PickDropLocation::find()
                ->select(['id_pick_drop_location','location_name'])
                ->all();
$pickLocation = ArrayHelper::map($pickLocation,'id_pick_drop_location','location_name');
$SlotsType = Slots::find()
            ->select(['id_slots','time_description'])
            ->where(['slot_type'=>0])
            ->all();
$SlotsType = ArrayHelper::map($SlotsType,'id_slots','time_description');

$State = State::find()
            ->select(['idState','stateName'])
            ->where(['status'=> 'Active'])
            ->all();
$State = ArrayHelper::map($State,'idState','stateName');

$pickUpPlace = PickDropSpotsType::find()
                ->select(['id_pick_drop_spots_type','spot_name'])
                ->all();
$pickUpPlace = ArrayHelper::map($pickUpPlace,'id_pick_drop_spots_type','spot_name');

$id_employee = Yii::$app->user->identity->id_employee;
if($id_employee){
    $airport =  EmployeeAirportRegion::find()
            ->select(['fk_tbl_airport_of_operation_airport_name_id'])
            ->where(['fk_tbl_employee_id' => $id_employee])
            ->all();
    if($airport){
        $corporate_array = [];
        $region_array = [];
        $airport_array = [];

        foreach ($airport as $key => $row) {
            if($row->fk_tbl_airport_of_operation_airport_name_id == 3){
                array_push($corporate_array,19);

                array_push($region_array,1);

                array_push($airport_array,3);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 7){
                array_push($corporate_array,20);

                array_push($region_array,2);

                array_push($airport_array,7);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 8){
                array_push($corporate_array,30);

                array_push($region_array,3);

                array_push($airport_array,8);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 9){
                array_push($corporate_array,30);

                array_push($region_array,3);

                array_push($airport_array,9);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 10){
                array_push($corporate_array,31);

                array_push($region_array,4);

                array_push($airport_array,10);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 11){
                array_push($corporate_array,31);

                array_push($region_array,4);

                array_push($airport_array,11);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 12 || $row->fk_tbl_airport_of_operation_airport_name_id == 13 || $row->fk_tbl_airport_of_operation_airport_name_id == 14){
                array_push($corporate_array,37);

                array_push($region_array,5);

                array_push($airport_array,12);
                array_push($airport_array,13);
                array_push($airport_array,14);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 15 || $row->fk_tbl_airport_of_operation_airport_name_id == 16 || $row->fk_tbl_airport_of_operation_airport_name_id == 17){
                array_push($corporate_array,43);

                array_push($region_array,6);

                array_push($airport_array,15);
                array_push($airport_array,16);
                array_push($airport_array,17);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 18 || $row->fk_tbl_airport_of_operation_airport_name_id == 19 || $row->fk_tbl_airport_of_operation_airport_name_id == 20){
                array_push($corporate_array,44);

                array_push($region_array,7);

                array_push($airport_array,18);
                array_push($airport_array,19);
                array_push($airport_array,20);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 21 || $row->fk_tbl_airport_of_operation_airport_name_id == 22 || $row->fk_tbl_airport_of_operation_airport_name_id == 23){
                array_push($corporate_array,45);

                array_push($region_array,8);

                array_push($airport_array,21);
                array_push($airport_array,22);
                array_push($airport_array,23);
            }else if($row->fk_tbl_airport_of_operation_airport_name_id == 24 || $row->fk_tbl_airport_of_operation_airport_name_id == 25 || $row->fk_tbl_airport_of_operation_airport_name_id == 26){
                array_push($corporate_array,46);

                array_push($region_array,9);

                array_push($airport_array,24);
                array_push($airport_array,25);
                array_push($airport_array,26);
            }


            // array_push($corporate_array,$row->fk_tbl_airport_of_operation_airport_name_id);
        }
        // print_r($corporate_array);
        // echo "<br/>";
        // print_r($region_array);
        // echo "<br/>";
        // print_r($airport_array);
        // exit;
        }

        // print_r($corporate_array);
        // echo "<br/>";
        // print_r($region_array);
        // echo "<br/>";
        // print_r($airport_array);
        // exit;
    }
    // if (in_array(19, $corporate_array))
    // {
    //   echo "Match found";
    // }
    // else
    // {
    //   echo "Match not found";
    // }exit;
    // echo "<pre>";print_r($corporate_array);exit;
    $CorporateName = CorporateDetails::find() 
               ->select(['corporate_detail_id','name'])->where([
                    'corporate_detail_id' => $corporate_array
                ])->orderBy(['corporate_detail_id' => SORT_DESC])->all();
    $CorporateName = ArrayHelper::map($CorporateName,'corporate_detail_id','name');

    $RegionName = CityOfOperation::find() 
               ->select(['region_id','region_name'])->where([
                    'region_id' => $region_array,
                ])->orderBy(['region_id' => SORT_DESC])->all();
    $RegionName = ArrayHelper::map($RegionName,'region_id','region_name');

    $AirportName = AirportOfOperation::find() 
               ->select(['airport_name_id','airport_name'])->where([
                    'airport_name_id' => $airport_array,
                ])->orderBy(['airport_name_id' => SORT_DESC])->all();
    $AirportName = ArrayHelper::map($AirportName,'airport_name_id','airport_name');
    // if(count($airport) > 1){
    //     $CorporateName = CorporateDetails::find() 
    //                ->select(['corporate_detail_id','name'])->where([
    //                     'corporate_detail_id' => [19,20,28,29],
    //                 ])->orderBy(['corporate_detail_id' => SORT_DESC])->all();
    //     $CorporateName = ArrayHelper::map($CorporateName,'corporate_detail_id','name');

    //     $RegionName = CityOfOperation::find() 
    //                ->select(['region_id','region_name'])->where([
    //                     'region_id' => [1,2,3],
    //                 ])->all();
    //     $RegionName = ArrayHelper::map($RegionName,'region_id','region_name');

    //     $AirportName = AirportOfOperation::find() 
    //                ->select(['airport_name_id','airport_name'])->where([
    //                     'airport_name_id' => [3,7,8,9],
    //                 ])->all();
    //     $AirportName = ArrayHelper::map($AirportName,'airport_name_id','airport_name');
    // }else if($airport[0]['fk_tbl_airport_of_operation_airport_name_id'] == 3){
    //     $CorporateName = CorporateDetails::find() 
    //                ->select(['corporate_detail_id','name'])->where(['name' => 'Carter_blr'])->all();
    //     $CorporateName = ArrayHelper::map($CorporateName,'corporate_detail_id','name');

    //     $RegionName = CityOfOperation::find() 
    //                ->select(['region_id','region_name'])->where([
    //                     'region_id' => [1],
    //                 ])->all();
    //     $RegionName = ArrayHelper::map($RegionName,'region_id','region_name');

    //     $AirportName = AirportOfOperation::find() 
    //                ->select(['airport_name_id','airport_name'])->where([
    //                     'airport_name_id' => [3],
    //                 ])->all();
    //     $AirportName = ArrayHelper::map($AirportName,'airport_name_id','airport_name');
    // }else if($airport[0]['fk_tbl_airport_of_operation_airport_name_id'] == 7){
    //     $CorporateName = CorporateDetails::find() 
    //                ->select(['corporate_detail_id','name'])->where(['name' => 'Carter_hyd'])->all();
    //     $CorporateName = ArrayHelper::map($CorporateName,'corporate_detail_id','name');

    //     $RegionName = CityOfOperation::find() 
    //                ->select(['region_id','region_name'])->where([
    //                     'region_id' => [2],
    //                 ])->all();
    //     $RegionName = ArrayHelper::map($RegionName,'region_id','region_name');

    //     $AirportName = AirportOfOperation::find() 
    //                ->select(['airport_name_id','airport_name'])->where([
    //                     'airport_name_id' => [7],
    //                 ])->all();
    //     $AirportName = ArrayHelper::map($AirportName,'airport_name_id','airport_name');
    // }else if($airport[0]['fk_tbl_airport_of_operation_airport_name_id'] == 7){
    //     $CorporateName = CorporateDetails::find() 
    //                ->select(['corporate_detail_id','name'])->where(['name' => 'Carter_hyd'])->all();
    //     $CorporateName = ArrayHelper::map($CorporateName,'corporate_detail_id','name');

    //     $RegionName = CityOfOperation::find() 
    //                ->select(['region_id','region_name'])->where([
    //                     'region_id' => [2],
    //                 ])->all();
    //     $RegionName = ArrayHelper::map($RegionName,'region_id','region_name');

    //     $AirportName = AirportOfOperation::find() 
    //                ->select(['airport_name_id','airport_name'])->where([
    //                     'airport_name_id' => [7],
    //                 ])->all();
    //     $AirportName = ArrayHelper::map($AirportName,'airport_name_id','airport_name');
    // }
//}
/*$airport=AirportOfOperation::find()
            ->select(['airport_name_id','airport_name'])
            ->all();
$airportType = ArrayHelper::map($airport,'airport_name_id','airport_name');*/

/*$region=CityOfOperation::find()
            ->all();
$regionType = ArrayHelper::map($region,'id','region_name');*/
//echo '<pre>';print_r($customer_details);exit;

?>

<script type="text/javascript">
$(document).ready(function() {
    $("#state-pincode").change(function() {
        var state = $("#state-idstate option:selected").text();
        var pincode = $('#state-pincode').val();
        $.ajax({
            type: 'POST',
            data: {
                state: state,
                pincode: pincode
            },
            cache: true,
            url: 'index.php?r=v3/calculation-api/getstatepincode',
            success: function(response) {
                if (response.state_status == false) {
                    $("#bsModal4").modal('show');
                    $('#state_error').text(response.state_message);
                    return false;
                } else {
                    $('#state-pincode').attr("disabled", true);
                }
            }
        });
    });
});
</script>
<link href="css/bootstrap.min.css" rel="stylesheet">
<!-- <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>   -->
<script type="text/javascript" src="js/moment.min.js"></script>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script src="js/bootstrap-datetimepicker.min.js"></script>
<style>
.loaderTop {
    background: none repeat scroll 0 0 black;
    position: fixed;
    display: none;
    opacity: 0.5;
    z-index: 1000001; // or, higher
    left: 0;
    top: 0;
    height: 100%;
    width: 100%;

}

.errmsg {
    color: red;
}

.loader {
    position: absolute;
    ;
    border: 16px solid #f3f3f3;
    border-radius: 50%;
    top: 35%;
    left: 44%;
    border-top: 16px solid #3498db;
    width: 80px;
    height: 80px;
    -webkit-animation: spin 2s linear infinite;
    /* Safari */
    animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
    0% {
        -webkit-transform: rotate(0deg);
    }

    100% {
        -webkit-transform: rotate(360deg);
    }
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
<style type="text/css">
.field-order-insurance_price label {
    float: left;
    margin-right: 5px;
}

.field-order-insurance_price .control-label {
    float: none;
    cursor: pointer;
}

.payment {
    display: -webkit-inline-box;
}

.payment .form-group {
    padding: 5px 10px 5px 5px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 20px;
    margin-right: 10px;
    cursor: pointer;
    background-color: #5CB85C;
    color: white;
}

.payment .form-group label {
    margin-bottom: 0;
    cursor: pointer;
}

@media (min-width: 768px) and (max-width: 1024px) {

    .main_div,
    .add_luggage {
        display: inline-block;
        width: 100%;
    }

    .clsdelete {
        margin-left: 200px !important;
    }

    .p-l0,
    .passenger_details {
        padding: 0;
    }

    .luggage_ipad {
        padding-left: 0px;
    }
}

.payment .form-group input {
    width: 20px;
    height: 16px;
    cursor: pointer;
}

.field-order-luggage_price label,
.field-order-luggage_price input {
    font-size: 20px;
}
</style>
<!-- Modal -->
<div class="modal fade" id="bsModal3" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="mySmallModalLabel">Outstation Order</h4>
            </div>
            <div class="modal-body">
                <p id="outstation_error"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="bsModal4" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabels"
    aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="mySmallModalLabels">Local Order</h4>
            </div>
            <div class="modal-body">
                <p id="state_error"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="corporate_create_order-form">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="panel panel-primary">
        <div class="panel-heading">
            User Information
        </div>
        <div class="panel-body">
            <?php if($customer_details){ 
                $mobile = $customer_details['mobile'];
                $name = $customer_details['name'];
                $email = $customer_details['email'];
                $area = $customer_details['area'];
                $address = $customer_details['address_line_1'];
                $building_number = $customer_details['building_number'];
                $landmark = $customer_details['landmark'];
                $id_customer = $customer_details['id_customer'];
                $country_code = $customer_details['fk_tbl_customer_id_country_code'];

                $model['o']->travell_passenger_name = $name;
                $model['o']->fk_tbl_order_id_country_code = $country_code;
                $model['o']->travell_passenger_contact = $mobile;
                $model['osd']->area = $area;
                $model['osd']->address_line_1 = $address;
                $model['osd']->building_number = $building_number;
                $model['osd']->landmark = $landmark;
            }else{ 
                $mobile = '';
                $name = '';
                $email = '';
                $id_customer = '';
             }?>
            <div class="row">
                <div class="col-md-4">
                    <label>Name:</label>
                    <?php echo $name; ?>
                </div>
                <div class="col-md-4">
                    <label>Mobile:</label>
                    <?php echo $mobile; ?>
                </div>
                <div class="col-md-4">
                    <label>Email:</label>
                    <?php echo $email; ?>
                </div>

            </div>
        </div>
    </div>
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?php /* ?>
    <div class="panel panel-primary">
        <div class="panel-heading">
            User Information
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model['o'], 'travell_passenger_name')->textInput(['maxlength' => true])->label('Mobile Number'); ?>
                    <?= $form->field($model['o'], 'travell_passenger_name')->textInput(['maxlength' => true])->label('Name'); ?>
                    <?= $form->field($model['o'], 'travell_passenger_name')->textInput(['maxlength' => true])->label('Address (Registered)'); ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model['o'], 'travell_passenger_name')->textInput(['maxlength' => true])->label('Email'); ?>
                </div>

            </div>
        </div>
    </div>
    <?php */ ?>
    <input type="hidden" name="cutomer_mobile" value="<?php echo $mobile; ?>">
    <input type="hidden" name="travel_email" value="<?php echo $email; ?>">
    <div class="panel panel-primary">
        <div class="panel-heading">
            Order Details
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-3">

                    <?= $form->field($model['o'], 'delivery_type')->dropDownList($delivery_type,[
                            'onchange'=>'
                                $.post( "index.php?r=employee/delivery-type&id='.'"+$(this).val(),
                                    function( data ) {
                                        $( "select#order-dservice_type" ).html( data );

                                    }
                                );
                            ',
                            'prompt'=>'Select Delivery Type', 'required'=>'required'])->label('Order Type'); ?>
                    <input type="hidden" name="Order[delivery_type]" class="delivery_type">
                </div>
                <div class="col-sm-3">

                    <?= $form->field($model['o'], 'corporate_id')->dropDownList($CorporateName,
                    [
                        'prompt' => 'Select Corporate',
                        'class' => 'form-control corporate_id',
                        'onchange'=>'
                                $.post( "index.php?r=employee/region&id='.'"+$(this).val(),
                                    function( data ) {
                                        $( "select#order-fk_tbl_airport_of_operation_airport_name_id" ).html( data );
                                    }
                                );
                            '
                    ]
                    )->label('Corporate List'); ?>
                    <input type="hidden" name="Order[corporate_id]" class="corporate_id">
                </div>
                <div class="col-sm-3 order_transfer">

                    <?= $form->field($model['o'], 'order_transfer')->dropDownList($transfer_type,['prompt'=>'Select Order Transfer'])->label('Order Transfer'); ?>
                    <input type="hidden" name="Order[order_transfer]" class="order_transfer">

                </div>

                <div class="col-sm-3">
                    <?= $form->field($model['o'], 'service_type')->dropDownList($service_type,
                      [

                        'prompt'=>'Select Service Type',
                         "required"=>"required",
                        'class' =>'clsServiceType form-control',
                                'onchange'=>'
                                    $.get( "'.Url::toRoute('select-slot-time').'", { serviceTypeID: $(this).val(), order_date:$("#order-order_date").val(),order_type:$("#order-delivery_type").val(), order_transfer : $("#order-order_transfer").val()  } )
                                        .done(function( data ) {
                                            $( "#'.Html::getInputId($model['o'], 'fk_tbl_order_id_slot').'" ).html( data );
                                        }
                                    );
                                    $.get( "'.Url::toRoute('select-dep-arr-date').'", { serviceTypeID: $("#order-service_type").val(), order_date:$("#order-order_date").val()  } )
                                            .done(function( data ) {

                                                var selservice = $("#order-service_type").val();
                                                if(selservice == "1"){
                                                    $( "#'.Html::getInputId($model['o'], 'departure_date').'" ).html( data );
                                                }else{
                                                    $( "#'.Html::getInputId($model['o'], 'arrival_date').'" ).html( data );
                                                }
                                            }
                                        );
                                    $.get( "'.Url::toRoute('select-luggage-offers').'", { airport_id:$("#employee-airport").val()  } )
                                        .done(function( data ) {console.log(data);
                                            $( "#order_offers").html( data );
                                        }
                                    );
                                ' 

                      ]); ?>
                    <input type="hidden" name="Order[service_type]" class="service_type">
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 col-md-3">

                    <?php
                   echo $form->field($model['o'], 'order_date')->widget(DatePicker::classname(), [
                        'name' => 'date_11',
                        'options' => ['placeholder' => 'Select Event Date', 'readOnly'=> true],
                        'value'=>'',
                        'pluginOptions' => [
                            'todayHighlight' => true,
                            'todayBtn' => true,
                            'format' => 'yyyy-mm-dd',
                            'startDate' => date('Y-m-d'),
                            'autoclose' => true,
                        ],
                        'pluginEvents' => [
                                    "changeDate" => 'function(e) {  
                                        console.log(e.target);
                                        $.get( "'.Url::toRoute('select-dep-arr-date').'", { serviceTypeID: $("#order-service_type").val(), order_date:$("#order-order_date").val()  } )
                                                .done(function( data ) {

                                                    var selservice = $("#order-service_type").val();
                                                    if(selservice == "1"){
                                                        $( "#'.Html::getInputId($model['o'], 'departure_date').'" ).html( data );
                                                    }else{
                                                        $( "#'.Html::getInputId($model['o'], 'arrival_date').'" ).html( data );
                                                    }
                                                }
                                            );
                                        $.get( "'.Url::toRoute('select-slot-time').'", {  serviceTypeID: $("#order-service_type").val(), order_date:$("#order-order_date").val(),order_type:$("#order-delivery_type").val(), order_transfer : $("#order-order_transfer").val()  } )
                                                .done(function( data ) {
                                                    console.log(data);
                                                    $( "#'.Html::getInputId($model['o'], 'fk_tbl_order_id_slot').'" ).html( data );
                                                }
                                            );
                                     }',
                                ],
                    ]);
            ?>

                </div>



                <div class="col-sm-3">
                    <!--  <?= $form->field($model['o'], 'no_of_units')->textInput(['maxlength' => true])->label('No of Luggage'); ?>
           <div style="color:red" id="noUnits"></div> -->

                    <?= $form->field($model['o'], 'fk_tbl_order_id_slot')->dropDownList([
                                    'promptt'=>'Select...'])->label('PickUp Time'); ?>

                </div>
                <div class="col-sm-12 col-md-3">
                    <p class="to_airport_12"><b>Luggage at</b> :- 12 PM Onwards</p>
                    <p class="to_airport_3"><b>Luggage at</b> :- 3 PM Onwards</p>
                    <p class="to_airport_6"><b>Luggage at</b> :- 6 PM Onwards</p>
                    <p class="to_airport_2"><b>Luggage at</b> :- 2 AM Onwards</p>
                    <p class="to_airport_9"><b>Luggage at</b> :- 9 AM Onwards</p>
                    <p class="before"><b>Luggage at</b> :- Same day delivery between 4 PM - 11 PM</p>
                    <p class="after"><b>Luggage at</b> :- Next day delivery between 7 AM - 12 PM</p>
                </div>
            </div>
            <div class="row">
                <input type="hidden" name="outstation_id" class="outstation_id" value="">
                <input type="hidden" name="city_id" class="city_id" value="">
                <input type="hidden" name="name" value="<?php echo $name; ?>">
                <input type="hidden" name="id_customer" value="<?php echo $id_customer; ?>">

                <div class="col-sm-3 region">
                    <?= $form->field($model['o'], 'fk_tbl_airport_of_operation_airport_name_id')->dropDownList($RegionName,[
                    'onchange'=>'
                        $.post( "index.php?r=employee/airport&id='.'"+$(this).val(),
                            function( data ) {
                                $( "select#employee-airport" ).html( data );

                            }
                        );
                        $.post( "index.php?r=employee/get-state-region&id='.'"+$(this).val(),
                            function( data ) {
                                $( "select#state-idstate" ).html( data );

                            }
                        );
                    '
                    ])->label('Region/City'); ?>
                    <input type="hidden" name="Order[fk_tbl_airport_of_operation_airport_name_id]" class="region_id">
                </div>

                <div class="col-sm-3 airport">
                    <input type="hidden" name="email" value="<?php echo $email;?>">
                    <?= $form->field($employee_model, 'airport')->dropDownList($AirportName,[
            'onchange'=>'
                $.post( "index.php?r=employee/get-state&id='.'"+$(this).val(),
                    function( data ) {
                        $( "select#state-idstate" ).html( data );

                    }
                );
            ',
        ])->label('Airport'); ?>
                    <input type="hidden" name="Employee[airport]" class="airport_id">
                </div>
                <div class="col-sm-3 delivery">
                    <?= $form->field($model['o'], 'dservice_type')->dropDownList($dservice_type,[])->label('Delivery Service Type'); ?>
                </div>
                <div class="col-sm-3 city_pincode">
                    <label class="pincode3">Drop Pincode</label>
                    <label class="pincode4">Pickup Pincode</label>
                    <?= $form->field($model['o'], 'city_pincode')->textInput(['maxlength' => 6, 'onkeypress' => 'return isNumberKey(event)'])->label(false); ?>
                </div>
                <div class="col-sm-3 extra_charges pull-right">
                    <label class="express">Express Extra Charges</label>
                    <label class="outstation">Outstation Extra Charges</label>
                    <?= $form->field($model['o'], 'extra_charges')->textInput(['maxlength' => 6, 'value' => 0, 'onkeypress' => 'return isNumberKey(event)'])->label(false); ?>
                </div>
                <div class="col-sm-3 outstation_extra_charges pull-right">
                    <?= $form->field($model['o'], 'outstation_extra_charges')->textInput(['maxlength' => 6, 'value' => 0, 'onkeypress' => 'return isNumberKey(event)'])->label('Outstation Extra Charges'); ?>
                </div>

                <div class="col-sm-3">
                    <?php   echo $form->field($model['o'], 'luggagePrice')->hiddenInput([])->label(false); ?>
                </div>

            </div>
            <div class="row">
                <!-- <div class="col-sm-4 col-md-3">
            <?= $form->field($model['o'], 'delivery_type')->dropDownList($delivery_type,['prompt'=>'Select Delivery Type', 'required'=>'required'])->label('Delivery Type'); ?>
        </div> -->
                <div class="outstation_details">
                    <div class="col-sm-4 col-md-3">
                        <label class="state1">Pickup State</label>
                        <label class="state2">Drop State</label>
                        <?= $form->field($model['sta'], 'idState')->dropDownList($State,['prompt'=>'Select State'])->label(false); ?>
                        <input type="hidden" name="State[idState]" class="state_id">
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <label class="pincode1">Pickup Pincode</label>
                        <label class="pincode2">Drop Pincode</label>
                        <?= $form->field($model['sta'], 'pincode')->textInput(['maxlength' => 6,"onchange"=>"checkStatePincode();"])->label(false); ?>
                        <span class="errmsg"></span>
                    </div>
                </div>
                <div class="col-sm-4 col-md-3 validate">
                    <label class="pincode_validate1">Pickup Pincode</label>
                    <label class="pincode_validate2">Drop Pincode</label>
                    <?= $form->field($model['o'], 'validate_pincode')->textInput(['maxlength' => 6, 'class' => 'form-control pincode_validation'])->label(false); ?>
                    <span class="errmsg"></span>
                </div>
            </div>


            <div class="row">
                <div class="col-sm-3">
                    <!-- <?= $form->field($model['o'], 'fk_tbl_order_id_slot')->dropDownList($SlotsType,['prompt'=>'select pickup Time'])->label('PickUp Time'); ?> -->


                </div>

            </div>
        </div>
    </div>



    <div class="panel panel-primary lgdetails">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">Luggage Details</div>
                <div class="col-md-6">Price Details</div>
            </div>
        </div>
        <div class="panel-body clscls">
            <div class="row">
                <div class="col-md-6 main_div">
                    <div class="luggages col-md-12">
                        <div class="col-sm-4 luggage_ipad">
                            <?php //echo $form->field($model['oi'], 'fk_tbl_order_items_id_luggage_type')->dropDownList([])->label('Luggage Type'); ?>
                            <div class="form-group field-orderitems-fk_tbl_order_items_id_luggage_type"><label
                                    class="control-label" for="orderitems-fk_tbl_order_items_id_luggage_type">Luggage
                                    Type </label><select id="orderitems-fk_tbl_order_items_id_luggage_type"
                                    class="form-control clsnoluggage form-control clsnoluggage luggage_0"
                                    name="OrderItems[0][fk_tbl_order_items_id_luggage_type]" required="required">
                                    <?php 
                                $rows=\app\models\LuggageType::find()
                                    ->select(['id_luggage_type','luggage_type'])
                                    ->where(['corporate_id'=>0])
                                    ->andWhere(['status'=>1])
                                    ->all();
                                // echo "<option value=''>Select Luggage Type</option>";
                                if(count($rows)>0){
                                    foreach($rows as $row){
                                      echo "<option value='$row->id_luggage_type'>$row->luggage_type</option>";
                                    }
                                }else{
                                    echo "<option value=''>No Luggage</option>";
                                }
                                $extra_weight = '';
                                $extra_weight .= "<option value=''>Select Extra</option>";
                                for ($i=6; $i < 20; $i++) { 
                                    $extra_weight .= "<option value='$i'>$i</option>";
                                }
                           ?>
                                </select></div>

                        </div>
                        <div class="col-sm-4">
                            <div class="form-group field-orderitems-fk_tbl_order_items_id_weight_range"><label
                                    class="control-label">Luggage Weight </label><select
                                    id="orderitems-fk_tbl_order_items_id_weight_range"
                                    onchange="get_weight_range(0, 1);"
                                    class="form-control clsnoweight added_weight_id_0"
                                    name="OrderItems[0][fk_tbl_order_items_id_weight_range]"
                                    required="required"></select></div>

                        </div>
                        <div class="col-sm-3" style='display: block;padding-top: 25px;'><select
                                class='form-control above_weight_0 clsnoweight' onchange="get_weight_range(0, 1);"
                                name="OrderItems[0][item_weight]" id='extra_weight1'> <?php echo $extra_weight; ?>
                            </select></div>
                        <input type="hidden" id="price_array" name="price_array" value="" />
                        <div class="col-sm-1 add_button" style="margin-top:35px;"><a href="#" class="addScnt"
                                id="addScnt"><span class="glyphicon glyphicon-plus"></span></a></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="col-md-6">
                        <label>Price Calculation - <span class="order-no_of_units"></span></label>
                        <div id="order_offers"></div>
                        <?= $form->field($model['o'], 'no_of_units')->hiddenInput(['maxlength' => true, 'readonly' => true, 'class' => 'form-control pull-right order-no_of_units', 'style' => 'width: 47%'])->label(false); ?>
                    </div>
                    <div class="col-md-6 calculate">
                        <button type="button" id="calculate">Click Here To Calculate!</button>
                    </div>
                    <div class="col-md-12">
                        <label>Fragile Bag Insurance: INR 8 - Other Bags Insurance: INR 4</label>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'totalPrice')->textInput(['maxlength' => true,'required'=>'required', 'readonly' => true])->label('Luggage Price'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'service_tax_amount')->textInput(['maxlength' => true,'required'=>'required', 'readonly' => true])->label('Luggage GST'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'outstation_charge')->textInput(['maxlength' => true, 'readonly' => true])->label('Conveyance Charge for order'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'extr_km')->textInput(['maxlength' => true, 'readonly' => true])->label('Actual Extra Kilometer'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'extr_kms')->textInput(['maxlength' => true, 'readonly' => true])->label('Extra Kilometer(Excluding 20KM)'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'extr_kilometer')->textInput(['maxlength' => true, 'readonly' => true])->label('Extra Kilometer Price'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'per_km_price')->textInput(['maxlength' => true, 'readonly' => true])->label('Per Kilometer Charge'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'luggageGST')->textInput(['maxlength' => true, 'readonly' => true])->label('GST (Conveyance/Extra Kilometer)'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'in_price')->textInput(['maxlength' => true, 'readonly' => true])->label('Insurance'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'tax')->textInput(['maxlength' => true, 'readonly' => true])->label('Insurance GST'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['o'], 'insurance_price')->checkbox(array('label'=>''))->label('Additional Insurance'); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['o'], 'luggage_price')->textInput(['maxlength' => true, 'style' => 'form-inline','required'=>'required', 'readonly' => true, 'class' => 'form-control pull-right', 'style' => 'width: 70%;font-size: 26px;padding: 0 10px;'])->label('Total Amount'); ?>
                    </div>
                    <div class="col-md-12 payment">
                        <div class="form-group">
                            <label>
                                <input type="radio" checked="checked" name="OrderPaymentDetails[payment_type]"
                                    value="cash" />Cash</label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="OrderPaymentDetails[payment_type]" value="Card" />Card</label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="OrderPaymentDetails[payment_type]"
                                    value="COD" />Razorpay</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
    <div class="panel panel-primary outstation_address">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">Pickup Details</div>
                <div class="col-md-6">Drop Details</div>
            </div>
        </div>
        <div class="panel-body">
            <div class="row ">
                <div class="col-md-6">

                    <div class="col-md-6">
                        <?= $form->field($model['om'], 'pickupLocationType')->dropDownList($pickUpPlace,['', 'onchange' => 'pickupType(this)'])->label('Location Type'); ?>
                        <span id="error_order-travell_passenger_name" style="display:none"></span>
                    </div>
                    <div class="pickupclshotell col-md-4">
                        <?= $form->field($model['om'], 'pickupPersonName')->textInput(['maxlength' => true])->label('Name'); ?>
                    </div>


                    <div class="pickupclshotell col-md-4">
                        <?= $form->field($model['om'], 'pickupPersonNumber')->textInput(['maxlength' => 10])->label('Mobile Number'); ?>
                    </div>


                    <div class="pickupclshotel" style="display:none">
                        <div class="col-md-12">
                            <?= $form->field($model['om'], 'pickupHotelType')->dropDownList($hotel_type,
                                  [])->label('Type'); ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($model['om'], 'PickupHotelName')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($model['om'], 'pickupBookingConfirmation')->fileInput(['accept' => 'image/*', 'id' => 'booking_confirmation_file'])->label('Booking Confirmation') ?>
                        </div>
                    </div>
                    <div class="pickupclsretail" style="display:none">
                        <div class="col-md-4">
                            <?= $form->field($model['om'], 'pickupMallName')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model['om'], 'pickupStoreName')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model['om'], 'pickupInvoice')->fileInput(['accept' => 'image/*'])->label('Invoice') ?>
                        </div>
                    </div>
                    <div class="col-md-12 pickupclsbusiness" style="display:none">
                        <?= $form->field($model['om'], 'pickupBusinessName')->textInput(['maxlength' => true]); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['om'], 'pickupBuildingNumber')->textInput(['maxlength' => true])->label('Building Name and Others'); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['om'], 'pickupPersonAddressLine1')->textInput(['maxlength' => true])->label('Address Line1'); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['om'], 'pickupPersonAddressLine2')->textInput(['maxlength' => true])->label('Address Line2'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['om'], 'pickupArea')->textInput(['maxlength' => true])->label('Area'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['om'], 'pickupPincode')->textInput(['maxlength' => 6, 'class' => 'pickup_pincode form-control'])->label('Pincode'); ?>
                        <span class="errmsg"></span>
                    </div>
                </div>
                <div class="col-md-6">

                    <div class="col-md-6">
                        <?= $form->field($model['om'], 'dropLocationType')->dropDownList($pickUpPlace,['', 'onchange' => 'droplocationType(this)'])->label('Location Type'); ?>
                        <span id="error_order-travell_passenger_name" style="display:none"></span>
                    </div>
                    <div class="dropclshotell col-md-4">
                        <?= $form->field($model['om'], 'dropPersonName')->textInput(['maxlength' => true])->label('Name'); ?>
                    </div>


                    <div class="dropclshotell col-md-4">
                        <?= $form->field($model['om'], 'dropPersonNumber')->textInput(['maxlength' => 10])->label('Mobile Number'); ?>
                    </div>


                    <div class="dropclshotel" style="display:none">
                        <div class="col-md-12">
                            <?= $form->field($model['om'], 'dropHotelType')->dropDownList($hotel_type,
                              [])->label('Type'); ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($model['om'], 'dropHotelName')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($model['om'], 'dropBookingConfirmation')->fileInput(['accept' => 'image/*', 'id' => 'booking_confirmation_file'])->label('Booking Confirmation') ?>
                        </div>
                    </div>
                    <div class="dropclsretail" style="display:none">
                        <div class="col-md-4">
                            <?= $form->field($model['om'], 'dropMallName')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model['om'], 'dropStoreName')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model['om'], 'dropInvoice')->fileInput(['accept' => 'image/*'])->label('Invoice') ?>
                        </div>
                    </div>
                    <div class="col-md-12 dropclsbusiness" style="display:none">
                        <?= $form->field($model['om'], 'dropBusinessName')->textInput(['maxlength' => true]); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['om'], 'dropBuildingNumber')->textInput(['maxlength' => true])->label('Building Name and Others'); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['om'], 'dropPersonAddressLine1')->textInput(['maxlength' => true])->label('Address Line1'); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['om'], 'dropPersonAddressLine2')->textInput(['maxlength' => true])->label('Address Line2'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['om'], 'droparea')->textInput(['maxlength' => true])->label('Area'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['om'], 'dropPincode')->textInput(['maxlength' => 6, 'class' => 'form-control drop_pincode'])->label('Pincode'); ?>
                    </div>


                </div>
            </div>
        </div>
    </div>
    <div class="panel panel-primary clspickupdetails">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">Passenger Details</div>
                <div class="col-md-6">Location Details</div>
            </div>
        </div>
        <div class="panel-body">
            <div class="row ">
                <div class="col-md-6">
                    <?= $form->field($model['o'], 'travell_passenger_name')->textInput(['maxlength' => true])->label('Passenger Name'); ?>
                    <div class="col-md-6" style="padding-left: 0;">
                        <?php //$model['o']->fk_tbl_order_id_country_code = 95;
                                echo $form->field($model['o'], 'fk_tbl_order_id_country_code')->widget(Select2::classname(),[
                                          'data' => CountryCode::getcountrycodes1(),
                                          'size' => Select2::MEDIUM,
                                          'options' => ['placeholder' => 'Select country', 'multiple' => false,'style'=>'width:300px'],
                                          'pluginOptions' => ['allowClear' => true],
                                      ])->label('Select Country');
                        ?>
                    </div>
                    <div class="col-md-6 p-l0">
                        <?= $form->field($model['o'], 'travell_passenger_contact')->textInput(['maxlength' => true])->label('Passenger Number'); ?>
                    </div>

                    <?= $form->field($model['o'], 'flight_number')->textInput(['maxlength' => true])->label('Filght Number'); ?>
                    <div class="clsdeparture">
                        <?= $form->field($model['o'], 'departure_date')->dropDownList(['prompt'=>'Select...'])->label('Departure Date'); ?>
                        <div class="form-group" style="position: relative;">
                            <label class="control-label" for="order-departure_time">Departure Time</label>
                            <input class="form-control" type="text" name="Order[departure_time]"
                                id="order-departure_time" />
                        </div>
                    </div>
                    <div class="clsarrival" style="display: none;">
                        <?= $form->field($model['o'], 'arrival_date')->dropDownList(['prompt'=>'Select...'])->label('Arrival Date'); ?>
                        <div class="form-group" style="position: relative;">
                            <label class="control-label" for="order-arrival_time">Arrival Time</label>
                            <input class="form-control" type="text" name="Order[arrival_time]"
                                id="order-arrival_time" />
                        </div>
                    </div>
                    <div class="form-group" style="position: relative;">
                        <label class="control-label" for="order-meet_time_gate">Gate meet Time</label>
                        <input class="form-control" type="text" name="Order[meet_time_gate]" id="order-meet_time_gate"
                            style="font-size: 20px;" />
                    </div>
                </div>
                <div class="col-md-6 passenger_details">

                    <div class="form-group col-md-6">
                        <input type="checkbox" name="address_same" id="address_same" checked="checked">&nbsp;<label>Same
                            As Passenger Details</label>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['osd'], 'fk_tbl_order_spot_details_id_pick_drop_spots_type')->dropDownList($pickUpPlace,['', 'onchange' => 'locallocationType(this);'])->label('Location Type'); ?>
                        <span id="error_order-travell_passenger_name" style="display:none"></span>
                    </div>
                    <div class="clshotell col-md-4">
                        <?= $form->field($model['osd'], 'person_name')->textInput(['maxlength' => true])->label('Name'); ?>
                    </div>


                    <div class="clshotell col-md-4">
                        <?= $form->field($model['osd'], 'person_mobile_number')->textInput(['maxlength' => true])->label('Mobile Number'); ?>
                    </div>


                    <div class="clshotel" style="display:none">
                        <div class="col-md-12">
                            <?= $form->field($model['osd'], 'fk_tbl_order_spot_details_id_contact_person_hotel')->dropDownList($hotel_type,
                              [])->label('Type'); ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($model['osd'], 'hotel_name')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($model['osd'], 'booking_confirmation_file')->fileInput(['accept' => 'image/*', 'id' => 'booking_confirmation_file'])->label('Booking Confirmation') ?>
                        </div>
                    </div>
                    <div class="clsretail" style="display:none">
                        <div class="col-md-4">
                            <?= $form->field($model['osd'], 'mall_name')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model['osd'], 'store_name')->textInput(['maxlength' => true]); ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model['mi'], 'invoice')->fileInput(['accept' => 'image/*'])->label('Invoice') ?>
                        </div>
                    </div>
                    <div class="col-md-12 clsbusiness" style="display:none">
                        <?= $form->field($model['osd'], 'business_name')->textInput(['maxlength' => true]); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['osd'], 'building_number')->textInput(['maxlength' => true])->label('Building Name and Others'); ?>
                    </div>
                    <div class="col-md-12">
                        <?= $form->field($model['osd'], 'address_line_1')->textInput(['maxlength' => true])->label('Address'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['osd'], 'area')->textInput(['maxlength' => true])->label('Area'); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model['osd'], 'pincode')->textInput(['maxlength' => true,'required'=>'required'])->label('Pincode'); ?>
                    </div>

                    <div class="col-md-12">
                        <?= $form->field($model['osd'], 'landmark')->textInput(['maxlength' => true])->label('Landmark'); ?>
                    </div>
                    <div class="col-md-12">
                        <b>Building Restriction</b>
                        <?php 
                        $model['osd']->building_restriction = 5;
                        $mail_building_restrictions = BuildingRestriction::find()
                                            ->select(['id_building_restriction','restriction'])
                                            ->all(); 
                        $listData=ArrayHelper::map($mail_building_restrictions,'id_building_restriction','restriction');
                        // echo Html::checkboxList('OrderSpotDetails[building_restriction]',[],$listData,
                        //  ['itemOptions'=>['class' => 'clsbuilding']]
                        // );
                        echo $form->field($model['osd'], 'building_restriction')->checkboxList($listData,['itemOptions'=>['class' => 'clsbuilding']])->label(false);
                    ?>
                    </div>
                    <div class="clsothercomments col-sm-3" style="display:none;">
                        <?= $form->field($model['osd'], 'other_comments')->textarea(['maxlength' => true, 'style'=>'margin: 0px -279.549px 0px 0px; width: 535px; height: 146px;'])->label('Other Comments'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group clsbtnsubmit pull-right">
        <?= Html::submitButton($model['o']->isNewRecord ? 'Create' : 'Update', ['class' => $model['o']->isNewRecord ? 'btn btn-success' : 'btn btn-primary', 'style'=>'font-size: 20px;']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
</div>


<script type="text/javascript">
$("#order-meet_time_gate").on("dp.change", function(e) {
    $slot_id = $("#order-fk_tbl_order_id_slot").val();
    if ($slot_id == 1 || $slot_id == 2 || $slot_id == 3) {
        var currenttime = e.target.value;
        var order_date = $("#order-order_date").val();
        var departure_date = $("#order-departure_date").val();
        if ($slot_id == 1) {

            var orderdatetime = new Date(order_date + ' 07:00 AM');
            console.log(orderdatetime);
            console.log(currenttime);
            var meet_date_time = new Date(departure_date + ' ' + currenttime);
            console.log(meet_date_time);
            var hours = Math.abs(orderdatetime - meet_date_time) / 36e5;
            var hours1 = Math.abs(orderdatetime) / 36e5;
            if (hours > 20) {
                alert(
                    'Your selected time is before arrival of luggage at the airport OR after the departure time. Enter Gate1 time accordingly. Inconvenience regretted. Thank you'
                    );
            }
            if (hours1 < 5) {
                alert(
                    'Your selected time is before arrival of luggage at the airport OR after the departure time. Enter Gate1 time accordingly. Inconvenience regretted. Thank you'
                    );
            }
        }
        var addhrto_currenttime = moment(e.target.value, ["h:mm A"]).add(1, 'hours').format("LT");

        $('#order-departure_time').val(addhrto_currenttime);
    } else {
        var currenttime = e.target.value;
        console.log(e.target.value);
        var sub30minto_currenttime = moment(e.target.value, ["h:mm A"]).add(-30, 'minutes').format("LT");
        console.log(sub30minto_currenttime);
        $('#order-arrival_time').val(sub30minto_currenttime);
    }
});
$("#order-departure_time").on("dp.change", function(e) {
    $slot_id = $("#order-fk_tbl_order_id_slot").val();
    if ($slot_id == 1 || $slot_id == 2 || $slot_id == 3) {
        var currenttime = e.target.value;
        var subhrto_currenttime = moment(e.target.value, ["h:mm A"]).add(-1, 'hours').format("LT");
        $('#order-meet_time_gate').val(subhrto_currenttime);
    }
});
$("#order-arrival_time").on("dp.change", function(e) {
    $slot_id = $("#order-fk_tbl_order_id_slot").val();
    if ($slot_id == 4 || $slot_id == 5) {
        var currenttime = e.target.value;
        console.log(currenttime);
        var add30mins_currenttime = moment(e.target.value, ["h:mm A"]).add(30, 'minutes').format("LT");
        console.log(add30mins_currenttime);
        $('#order-meet_time_gate').val(add30mins_currenttime);
    }
});
$(document).ready(function() {
    $("#order-fk_tbl_order_id_slot").change(function() {
        //setmeettime();
        $slot_id = $("#order-fk_tbl_order_id_slot").val();
        $.ajax({
            type: "post",
            dataType: 'json',
            url: "index.php?r=employee/selected-slot",
            data: "id_slot=" + $slot_id,
            success: function(data) {
                //alert(data);
                console.log(data.response.meet_time_gate);
                if ($slot_id == 1 || $slot_id == 2 || $slot_id == 3 || $slot_id == 7 ||
                    $slot_id == 9) {
                    $('#order-meet_time_gate').val(data.response.meet_time_gate);
                    $('#order-departure_time').val(data.response.departure_time);
                } else {
                    $('#order-meet_time_gate').val(data.response.meet_time_gate);
                    $('#order-arrival_time').val(data.response.arrival_time);
                }
            }
        });
    });
    $("#order-city_pincode").change(function() {
        $pincode = $('#order-city_pincode').val();
        $service_type = $('#order-service_type').val();
        $city_id = $('#order-fk_tbl_airport_of_operation_airport_name_id').val();
        $airport_id = $('#employee-airport').val();
        $data = [];
        $dataObj = {};
        $dataObj['pincode'] = $pincode;
        $dataObj['service_type'] = $service_type;
        $dataObj['airport_id'] = $airport_id;
        $dataObj['city_id'] = $city_id;
        $data.push($dataObj);
        $pincodeval = false;

        if ($pincode != '') {
            $.ajax({
                type: "post",
                dataType: 'json',
                url: "index.php?r=order-api/checkpincodeavailability",
                data: {
                    pincode: $pincode,
                    service_type: $service_type,
                    city_id: $city_id,
                    airport_id: $airport_id
                },
                async: false,
                success: function(data) {
                    console.log(data);
                    if (data == 1) {
                        $('#order-city_pincode').attr('readonly', true);
                        $pincodeval = true;
                        $('.drop_pincode').val($pincode);
                        $('.drop_pincode').attr('readonly', true);
                    } else {
                        alert('Sorry! Service not available for the selected area');
                        $('#order-city_pincode').val('');
                        $('#order-city_pincode').attr('readonly', false);

                        return false;
                        $pincodeval = false;
                    }

                }
            });
            return $pincodeval;
        } else {
            return true;
        }
    });
});

function formvalidate() {
    var file = document.getElementById("booking_confirmation_file").files[0];

    var fileType = file.type;
    var ValidImageTypes = ["image/gif", "image/jpeg", "image/png"];
    if ($.inArray(fileType, ValidImageTypes) < 0) {
        // invalid file type code goes here.
        alert('Please select a valid image file');
        return false;
    }
    return true;
}

function setmeettime() {
    $('#order-meet_time_gate').datetimepicker({
        minDate: moment({
            h: 21
        }),
        maxDate: moment({
            h: 23
        })
    });
}

//$('#order-meet_time_gate').val('11:45 PM');
$('#order-meet_time_gate').datetimepicker({
    format: 'LT'
});
// .on('dp.change', function () {
//     var time = $('#order-meet_time_gate').val();
//     if(time <= '12:00 PM'){
//         $('a.btn[data-action="decrementMinutes"]').removeAttr('data-action').attr('disabled', true);
//     }else if (time > '12:00 PM'){
//         console.log('in');
//         //$('a.btn[data-action="decrementMinutes"]').attr('data-action').attr('disabled', false);
//     }
// });

$('#order-departure_time').datetimepicker({
    format: 'LT'
});

$('#order-arrival_time').datetimepicker({
    format: 'LT'
});



function checkpincode() {
    $pincode = $('#orderspotdetails-pincode').val();
    $service_type = $('#order-service_type').val();
    $city_id = $('#order-fk_tbl_airport_of_operation_airport_name_id').val();
    $airport_id = $('#employee-airport').val();
    $data = [];
    $dataObj = {};
    $dataObj['pincode'] = $pincode;
    $dataObj['service_type'] = $service_type;
    $dataObj['airport_id'] = $airport_id;
    $dataObj['city_id'] = $city_id;
    $data.push($dataObj);
    $pincodeval = false;
    console.log($data);

    if ($pincode != '') {
        $.ajax({
            type: "post",
            dataType: 'json',
            url: "index.php?r=order-api/checkpincodeavailability",
            data: {
                data: $data
            },
            async: false,
            success: function(data) {
                console.log(data);
                if (data.status == true) {
                    $pincodeval = true;
                } else {
                    alert('Sorry! Service not available for the selected area');
                    $pincodeval = false;
                }

            }
        });

        return $pincodeval;
    } else {
        return true;
    }
}

function selectState(){
   var cop_id = $("#order-corporate_id").val();
   var order_transfer = $("#order-order_transfer").val();
   var airport_id = $("#employee-airport").val();
   var city_id = $("#order-fk_tbl_airport_of_operation_airport_name_id").val();
   var type = (city_id) ? "city" : "airport";
   if(cop_id){
        $.ajax({
            type: "post",
            dataType: 'json',
            url: "index.php?r=employee/selected-state",
            data: {
                airport_id : airport_id, city_id : city_id, type : type
            },
            success: function(data) {
                $("#state-idstate").html(data);
            }
        });
   }
};

$("#ordermetadetails-pickuppincode").change(function(){
    var serviceType = $("#order-service_type").val();
    var orderType = $("#order-order_transfer").val();
    var pickuppincode = $("#ordermetadetails-pickuppincode").val();
    // var droppincode = $("#ordermetadetails-droppincode").val();
    var cityId = $("#order-fk_tbl_airport_of_operation_airport_name_id").val();
    if(serviceType == 2){
        $.ajax({
            type: "post",
            dataType: 'json',
            url: "index.php?r=employee/check-mhl-pincode",
            data: {pincode : pickuppincode, region : cityId},
            async:false,
            success: function(data) {
                console.log(data.status)
                if(data.status == 0){
                    $("#ordermetadetails-pickuppincode").prop("readonly",false);
                    $("#ordermetadetails-pickuppincode").val("");
                    alert(data.message);
                } else {
                    $("#ordermetadetails-pickuppincode").prop("readonly",true);
                }
            }
        });
    }  else {
        $("#ordermetadetails-pickuppincode").prop("readonly",true);
    }
});

$("#ordermetadetails-droppincode").change(function(){
    var serviceType = $("#order-service_type").val();
    var orderType = $("#order-order_transfer").val();
    // var pickuppincode = $("#ordermetadetails-pickuppincode").val();
    var droppincode = $("#ordermetadetails-droppincode").val();
    var cityId = $("#order-fk_tbl_airport_of_operation_airport_name_id").val();
    if(serviceType == 1){
        $.ajax({
            type: "post",
            dataType: 'json',
            url: "index.php?r=employee/check-mhl-pincode",
            data: {pincode : droppincode, region : cityId},
            async:false,
            success: function(data) {
                console.log(data)
                if(data.status == 0){
                    $("#ordermetadetails-droppincode").prop("readonly",false);
                    $("#ordermetadetails-droppincode").val("");
                    alert(data.message);
                } else {
                    $("#ordermetadetails-droppincode").prop("readonly",true);
                }
            }
        });
    }  else {
        $("#ordermetadetails-droppincode").prop("readonly",true);
    }
});


function checkStatePincode(){
    var pincode = $("#state-pincode").val();
    var stateId = $("#state-idstate").val();
    $.ajax({
        type: "post",
        dataType: 'json',
        url: "index.php?r=employee/check-mhl-pincode",
        data: {pincode : pincode, region : stateId, type : "state"},
        async:false,
        success: function(data) {
            console.log(data.status)
            if(data.status == 0){
                $("#state-pincode").prop("readonly",false);
                $("#state-idstate").prop("disabled",false);
                $("#state-pincode").val("");
                alert(data.message);
            } else {
                $("#state-pincode").prop("readonly",true);
                $("#state-idstate").prop("disabled",true);
            }
        }
    });

}

$("#orderspotdetails-pincode").change(function(){
    var OrderType = $("#order-delivery_type").val();
    var OrderTransfer = $("#order-order_transfer").val();
    var airportId = $("#employee-airport").val();
    var pincode = $("#orderspotdetails-pincode").val();

    if((OrderType == 1) && (OrderTransfer == 2)){
        $.ajax({
            type : "post",
            dataType : 'json',
            url : "index.php?r=employee/check-mhl-pincode",
            data : {pincode : pincode, region : airportId, type : "airport"},
            async : false,
            success : function (data){
                if(data.status == 0){
                    $("#orderspotdetails-pincode").prop("readonly",false);
                    $("#orderspotdetails-pincode").val("");
                    alert(data.message);
                } else {
                    $("#orderspotdetails-pincode").prop("readonly",true);
                }
            }
        });
    }

});

</script>
