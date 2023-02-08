<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\EmployeeRole;
use app\models\Vehicle;

/* @var $this yii\web\View */
/* @var $searchModel app\models\EmployeeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Employees';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="employee-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Employee', ['create'], ['class' => 'btn btn-success']) ?>
        
    </p>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            'employee_profile_picture',
            'mobile',

            [
                'attribute'=>'fk_tbl_employee_id_employee_role',
                'value' => function ($model) {
                    return ($model->fk_tbl_employee_id_employee_role) ? $model->fkTblEmployeeIdEmployeeRole->role : $model->fkTblEmployeeIdCorporateEmployeeRole->role_name;
                },
                'filter' => array('1' => "Super Admin", '2' => "Admin", '4' => "Customer Care", '5' => "PorterX", '6' => "Porter", '7' => "Customer As Admin", '9' => "sales", '10' => "kiosk", '14' => "Corporate Kiosk", '17' => "Super Subscription", '18' => "Super Subscription Employee")
            ],
            [
                'attribute'=>'status',
                'value' => function ($model) {
                    return $model->status == 1? 'Enabled':'Disabled';
                },
                'filter'=>array('1'=>'Enabled','0'=>'Disabled')
            ],
            //'status',
            //'id_employee',
            //'fk_tbl_employee_id_employee_role',
            // 'email:email',
            // 'password',
            // 'adhar_card_number',
            // 'document_id_proof',
            // 'mobile_number_verification',
            // 'fk_tbl_airport_of_operation_airport_name_id',
            // 'date_created',
            // 'date_modified',

             [
                'attribute'=>'airport',
                'header'=>'Airport',
                'format' => 'raw',
                'value' => function ($model) { 
                    return Vehicle::getEmpAirportName($model->id_employee);
                    //return Order::getAirportName($model->region_id);
                }, 
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width:100px;'],
                'header' => '<span style="color:#3c8dbc;">Action</span>',

            ],
        ],
    ]); ?>
</div>
