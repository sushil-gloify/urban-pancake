<?php

use yii\helpers\Html;
use yii\widgets\DetailView; 
use app\components\Common;


$this->title = 'Tickets Detail';
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['tickets-details']];
$this->params['breadcrumbs'][] = $this->title;
/*   echo '<pre>';
print_r($model['ticketMetaTopicRelation']);
die;      */

?>
<div class="airlines-view">

<h1><?= Html::encode($this->title) ?></h1>

<div class="panel panel-primary">
    <div class="panel-heading">
        Order Details
    </div>
    <div class="panel-body">
        <div class="row">
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Order No</b> : <?= $order_details['order_number'] ?></h5>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Date of Service</b> : <?= date('d-m-Y', strtotime($order_details['order_date'])); ?></h5>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Date of Booking</b> : <?= date('d-m-Y', strtotime($order_details['date_created'])); ?></h5>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Service Type</b> :<?php $service_type = ($order_details['service_type']==1)?'To Airport':'From Airport'; ?> <?= $service_type ?></h5>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Slot</b> : <?php echo date('h:i A', strtotime($order_details['slot_start_time'])).' - '.date('h:i A', strtotime($order_details['slot_end_time'])); ?></h5>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Order Status</b> : <?= $order_details['order_status'] ?></h5>
        </div>
        
      </div>      
      <div class="row">
        <?php if($order_details['round_trip'] == 1) { ?>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <h5><b>Round Trip Order</b></h5>
            </div>
            <div class="col-md-6 col-sm-4 col-xs-12">
                <h5><b>Link To Related Order</b> : <?= Html::a('Click Here to view Related order', ['order/update', 'id' => $order_details['related_order_id']], ['class' => 'profile-link','target'=>"_blank"]) ?></h5>
            </div>
        <?php } ?>
      </div>

      <div class="row">        
        <div class="col-md-4 col-sm-8 col-xs-12">
        <h5><b>Signature Images</b></h5>
        <?php if($order_details['signature1'] != '' || $order_details['signature1'] != NULL){ ?>
            <b>Location Signature : </b>
            <a href="#" class="pop"><img src="<?php echo Yii::$app->params['site_url'].'uploads/signatures/'.$order_details['signature1'];?>" class="img-thumbnail1" alt="Signiture" width="100px" height="100px"></a><br>

        <?php } if($order_details['signature2'] != '' || $order_details['signature2'] != NULL){ ?>
            <b>Delivery Signature : </b>
            <a href="#" class="pop"><img src="<?php echo Yii::$app->params['site_url'].'uploads/signatures/'.$order_details['signature2'];?>" class="img-thumbnail1" alt="Signiture" width="100px" height="100px"></a>
        <?php } ?>
        </div>
      </div>

    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        Customer Details
    </div>
    <div class="panel-body">
        <div class="row">
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Customer Name</b> : <?= $order_details['customer_name'] ?></h5>
        </div>
        <div class="col-md-4 col-sm-4 col-xs-12">
            <h5><b>Customer Email</b> : <?= $order_details['customer_email'] ?></h5>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
           <h5><b>Contact</b> : <?= $order_details['customer_mobile'] ?></h5>
        </div>
        <?php if($order_details['customer_id_proof']){ ?>
        <div class="col-md-3 col-sm-4 col-xs-12">
           <h5><b>Customer Document</b> : <a href="#" class="pop"><img src="<?php echo Yii::$app->params['site_url'].Yii::$app->params['customer_document'].$order_details['customer_id_proof']; ?>" class="img-thumbnail" alt="Porter Image" width="50" height="50"></a></h5>
        </div>
        <?php } ?>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <?php $id_proof_verification = ($order_details['id_proof_verification'] == 1) ? 'Verified' : 'Not verified' ?>
           <h5><b>Customer Id Proof</b> : <?= $id_proof_verification ?></h5>
        </div>
      </div> 
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        Traveller Details
    </div>
    <div class="panel-body">
        <div class="row">
                <div class="col-md-6 col-sm-4 col-xs-12">
                    <h5><b>Person Name</b> : <?= $order_details['travell_passenger_name']; ?></h5>
                </div>
                <div class="col-md-6 col-sm-4 col-xs-12">
                    <h5><b>Person Contact</b> : <?= $order_details['travell_passenger_contact']; ?></h5>
                </div>
                <?php if($order_details['service_type'] == 1){ ?>
                    <div class="col-md-6 col-sm-4 col-xs-12">
                        <h5><b>Gate1 Departure Time</b> : <?= $order_details['departure_time']; ?></h5>
                    </div>
                    <div class="col-md-6 col-sm-4 col-xs-12">
                        <h5><b>Gate1 Departure Date</b> : <?= $order_details['departure_date']; ?></h5>
                    </div>
                <?php } ?>
                <?php if($order_details['service_type'] == 2){ ?>
                    <div class="col-md-6 col-sm-4 col-xs-12">
                        <h5><b>Gate1 Arrival Time</b> : <?= $order_details['arrival_time']; ?></h5>
                    </div>
                    <div class="col-md-6 col-sm-4 col-xs-12">
                        <h5><b>Gate1 Arrival Date</b> : <?= $order_details['arrival_date']; ?></h5>
                    </div>
                <?php } ?>
                <div class="col-md-6 col-sm-4 col-xs-12">
                    <h5><b>Gate1 Meeting Time</b> : <?= $order_details['meet_time_gate']; ?></h5>
                </div>
            </div>

            <div class="row">
                <?php if($order_details['ticket'] != '' && $order_details['ticket'] != null) { ?>
                <div class="col-md-3 col-sm-4 col-xs-12">
                    <h5><b>Ticket</b> : <a href="#" class="pop"><img src="<?php echo Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'customer_ticket/'.$order_details['ticket']?>" class="img-thumbnail1" alt="Flight Ticket" width="100" height="100"></a></h5>
                </div>
                <div class="col-md-3 col-sm-4 col-xs-12">
                    <h5><b>Flight verification</b> : <?= ($order_details['flight_verification']==0) ? 'Not Verified' : 'Verified' ; ?></h5>
                </div>
                <div class="col-md-6 col-sm-4 col-xs-12">
                    <h5><b>Flight Ticket Number</b> : <?= $order_details['flight_number']; ?></h5>
                </div>
                <?php } ?>        
            </div>

            <div class="row">
                <?php if($order_details['someone_else_document'] != '' && $order_details['someone_else_document'] != null) { ?>
                <div class="col-md-3 col-sm-4 col-xs-12">
                    <h5><b>Someone else</b> : <a href="#" class="pop"><img src="<?php echo Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'customer_documents/'.$order_details['someone_else_document']?>" class="img-thumbnail1" alt="Someone Else Document" width="100" height="100"></a></h5>
                </div>
                <div class="col-md-3 col-sm-4 col-xs-12">
                    <h5><b>Someone else Document verification</b> : <?= ($order_details['someone_else_document_verification'] == 0) ? 'Not Verified' : 'Verified'; ?></h5>
                </div>
                <?php } ?>        
            </div>
            
    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        <?= $order_details['service_type']== 1 ? 'Pickup Details' : 'Drop Off Summary' ;?>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3 col-sm-4 col-xs-12">
            <h4><b>Type</b> : <?= $order_details['spot_name'] ?></h4>
            </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    <label>Meeting Person</label> : <?= $order_details['location_contact_name']; ?>
                </div>
                
                <div class="form-group col-sm-6">
                    <label>Meeting Contact</label> : <?= $order_details['location_contact_number']; ?>
                </div>    
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    <label>Address Line 1</label> : <?= $order_details['location_address_line_1']; ?>
                </div>
                <!-- <div class="form-group col-sm-6">
                    <label>Address Line 2</label> : <?= $order_details['location_address_line_2']; ?>
                </div> -->
                 <div class="form-group col-sm-6">
                    <label>Landmark</label> : <?= $order_details['landmark']; ?>
                </div>
                <div class="form-group col-sm-6">
                    <label>Building Name and Number</label> : <?= $order_details['building_number']; ?>
                </div>
                <div class="form-group col-sm-6">
                    <label>Area</label> : <?= $order_details['location_area']; ?>
                </div>
                <div class="form-group col-sm-6">
                    <label>Pincode</label> : <?= $order_details['location_pincode']; ?>
                </div>
                <?php if($order_details['id_pick_drop_spots_type'] ==2){ ?>
                <div class="form-group col-sm-6">
                    <label>Hotel Name</label> : <?= $order_details['hotel_name']; ?>
                </div>
                
                <div class="form-group col-sm-6">
                    <label>Meeting Contact Person</label> :<?= Html::radioList('id_contact_person_hotel',$order_details['id_contact_person_hotel'],[1=>'Reception',2=>'Travel Desk', 3=>'Concierge', 4=>'Meeting Somebody'], ['class' => 'form-control col-lg-6 input-sm radio', 'readonly'=>'readonly']); ?>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-6">
                    <h5><b>Hotel booking Confirmation file</b></h5>
                    <div class="container">
                    <?php if(!empty($order_details['booking_confirmation_file'])){ ?>
                    <a href="#" class="pop"><img src="<?php echo Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'order_images/'.$order_details['booking_confirmation_file'] ?>" class="img-thumbnail1" alt="<?php echo $order_details['booking_confirmation_file'] ?>" width="100px" height="100px"></a>
                    <?php  } else{ ?>
                    <p>No images Found</p>
                    <?php } ?>
                    </div>
                </div>   
                <div class="form-group col-sm-4">
                           <h5><b>Hotel booking verification</b> : <?= ($order_details['hotel_booking_verification'] == 0) ? 'Not Verified' : 'Verified'; ?></h5>
                </div>
                <?php } ?>

                <?php if($order_details['id_pick_drop_spots_type'] ==3){ ?>
                <div class="form-group col-sm-6">
                    <label>Business Name</label> : <?= $order_details['business_name']; ?>
                </div>
                <div class="form-group col-sm-6">
                    <label>Business Contact Number</label> : <?= $order_details['business_contact_number']; ?>
                </div>
                <?php } ?>
                <?php if($order_details['id_pick_drop_spots_type'] ==4){ ?>
                <div class="form-group col-sm-6">
                    <label>Mall Name</label> : <?= $order_details['mall_name']; ?>
                </div>
                <div class="form-group col-sm-6">
                    <label>Store Name</label> : <?= $order_details['store_name']; ?>
                </div>
                
                <?php } ?>
            </div>

            <div class="row">
                <div class="form-group col-sm-6">
                    <label>Building Restrictions</label> : <?= $order_details['building_restriction'] == '' ? 'No Restrictions' : $order_details['building_restriction']; ?>
                    <br/><label>Other Comments</label> : <?= $order_details['other_comments'] == '' ? '-' : $order_details['other_comments']; ?>
                </div>  
            </div>

            
        <?php if($order_details['id_pick_drop_spots_type'] ==4){ ?>
        <div class="row">
        <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>Retail/Mall Invoices</b></h5>
            <div class="container">
            <?php if(!empty($order_details['mall_invoices'])){
            $invoice_url =Yii::$app->params['site_url'].Yii::$app->params['upload_path'].'customer_order_invoices/';
            foreach($order_details['mall_invoices'] as $invoice){ ?>
              <a href="#" class="pop"><img src="<?php echo $invoice_url.$invoice['invoice']?>" class="img-thumbnail1" alt="<?php echo $invoice['invoice'] ?>" width="100px" height="100px"></a>
            <?php } } else{ ?>
            <center>No images Found</center>
            <?php } ?>
            </div>
        </div>    
        </div>
        <?php } ?>
        
    </div>
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        Customer Order Status
    </div>
    <div class="panel-body">
        <div class="table-responsive">          
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Date</th>
                <th>To-Status</th>
              </tr>
            </thead>
            <?php $i=1; foreach($order_history as $orderstatus){ ?>
            <tbody>
              <tr>
                <td><?= $orderstatus['date_created'] ?></td>
                <td><?= $orderstatus['to_order_status_name'] ?></td>
              </tr>
            </tbody>
            <?php $i++; } ?>
          </table>
        </div>
    </div>  
</div>
<div class="panel panel-primary">
    <div class="panel-heading">
        Enable COD/Card On Delivery
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3 col-sm-4 col-xs-12">
            <h5><b>COD/Card On Delivery</b> : <?= ($order_details['enable_cod'] ==0) ? 'Disable' : 'Enable' ?></h5>
        </div>
        
        </div>

      </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
       Ticket Details
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6 col-sm-4 col-xs-12">
                <h5><b>Ticket Number</b> : <?= $model['ticket_number']; ?></h5>
            </div>
            <div class="col-md-6 col-sm-4 col-xs-12">
                <h5><b>Ticket Topic</b> : <?= $model['ticketMetaTopicRelation'][0]['topic_name']; ?></h5>
            </div>
            <div class="col-md-6 col-sm-4 col-xs-12">
                <h5><b>Customer Comment </b> :<?= $model['customer_comment']; ?></h5>
            </div>
            <div class="col-md-6 col-sm-4 col-xs-12">
                <h5><b>Ticket Status</b> : <?= $model->status; ?></h5>
            </div>
            <div class="col-md-6 col-sm-4 col-xs-12">
                <h5><b>Created_time</b> : <?= $model->created_date; ?></h5>
            </div>
        </div>
        <div class="panel-heading">
            Add Comment
        </div>
        <div class="panel-body">
            <div class="row">
                <?= Html::beginForm(['helpassistance-api/addcomment', 'id' => $_GET['id']], 'post', ['enctype' => 'multipart/form-data']) ?>
                <?= Html::textInput('assistnce_id',Yii::$app->user->identity->id_employee,['class'=>'form-control','type'=>'hidden','rows'=>8]); ?>
                     
                    <div class="col-sm-6" >
                        <div class="form-group">
                            <label>Enter Comment</label> : <?= Html::textarea('cc_query',"",['class'=>'form-control','rows'=>8]); ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <select class="form-control" name="tickets_status">
                                <option value=""> --- Select Status -----</option>
                                <option value="open">Open</option>
                                <option value="inprogress">Inprogress</option>
                                <option value="close">Closed</option>
                            </select>
                        </div>
                    </div><br/>
                    <div class="form-group">
                        <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
                    </div>
                <?= Html::endForm() ?>
            </div>
        </div>
        <div class="panel-body">
            <div class="panel-heading">
                Assistant Comment History
            </div>
            <div class="container">
                <?php 
                    $commentResult = Yii::$app->db->createCommand('SELECT * from tbl_help_tracking E where E.parent_id = "'.$model['ticket_id'].'" order by created_date desc')->queryAll();
                    $table = "<table class='table'><thead><tr><th>Modified By</th><th>Assistant Comment</th><th>Status</th><th>Change Date</th></tr></thead>";
                    if(!empty($commentResult)){
                        foreach($commentResult as $val){
                            $emp_detail = Yii::$app->db->createCommand('SELECT name from tbl_employee E where E.id_employee = "'.$val['assistant_id'].'"')->queryAll();
                   
                            $table .= "<tr><td>".ucwords($emp_detail[0]['name'])."</td><td>".$val['assistant_comment']."</td><td>".$val['status']."</td><td>".date('Y-m-d h:i:s',strtotime($val['created_date']))."</td></tr>";
                        }
                    } else {
                        $table .= "<tr><td colspan='4' class='text-center'>No Modification </td></tr>";
                    }
                    
                    $table .= "</tbody></table>";
                    echo $table;
                ?>
            </div>
            <!-- Order History Details Here -->

        </div> 
        <div class="panel-body">
            <!-- Order History Details Here -->
            <div class="container">
                <?php 
                    $HistResult = Yii::$app->db->createCommand('SELECT * from tbl_ticket_history EH where EH.ticket_id = "'.$model['ticket_id'].'" and assistant_id is not null order by created_date desc')->queryAll();
                    $table = "<table class='table'><thead><tr><th>Modified By</th><th>Modified Field</th><th>Change Date</th></tr></thead>";
                    if(!empty($HistResult)){
                        foreach($HistResult as $value){
                            $emp_detail = Yii::$app->db->createCommand('SELECT name from tbl_employee E where E.id_employee = "'.$value['assistant_id'].'"')->queryAll();
                   
                            $table .= "<tr><td>".ucwords($emp_detail[0]['name'])."</td><td>".$value['log_description']."</td><td>".date('Y-m-d h:i:s',strtotime($value['created_date']))."</td></tr>";
                        }
                    } else {
                        $table .= "<tr><td colspan='3' class='text-center'>No Modification </td></tr>";
                    }
                    $table .= "</tbody></table>";
                    echo $table;
                ?>
            </div>
            <!-- Order History Details Here -->

        </div>    
    </div>
</div>
<div class="modal fade" id="imagemodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">              
      <div class="modal-body">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <img src="" class="imagepreview" style="width: 100%;" >
      </div>
    </div>
  </div>
</div>
</div>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript">
    $(function() {
        $('.pop').on('click', function() {
            $('.imagepreview').attr('src', $(this).find('img').attr('src'));
            $('#imagemodal').modal('show');   
        });     
});
</script>