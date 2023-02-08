<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Order */

$this->title = $model->id_order;
$this->params['breadcrumbs'][] = ['label' => 'Orders', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-view">


   <!--  <p>
        <?= Html::a('Update', ['update', 'id' => $model->id_order], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id_order], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p> -->

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_order',
            'order_number',
             [
                'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                'label'=>'Region',
                'format' => 'raw',
                'value' => function ($model) { 
                    return $model->getRegionName($model->fk_tbl_airport_of_operation_airport_name_id);
                },
            ],
            [
                'attribute'=>'fk_tbl_airport_of_operation_airport_name_id',
                'label'=>'Airport',
                'format' => 'raw',
                'value' => function ($model) { 
                    return $model->getAirportName($model->fk_tbl_airport_of_operation_airport_name_id);
                },
            ],
            'sector',
            'weight',
            'ticket',
            'airline_name',
            'flight_number',
            'departure_time',
            'arrival_time',
            'meet_time_gate',
            'other_comments:ntext',
            'travel_person',
            'fk_tbl_order_status_id_order_status',
            'order_date',
            'fk_tbl_order_id_pick_drop_location',
            'no_of_units',
            'fk_tbl_order_id_slot',
            'service_type',
            'round_trip',
            'fk_tbl_order_id_customer',
            'payment_method',
            'payment_transaction_id',
            'payment_status',
            'invoice_number',
            'allocation',
            'enable_cod',
            'date_created',
            'date_modified',
        ],
    ]) ?>

</div>
