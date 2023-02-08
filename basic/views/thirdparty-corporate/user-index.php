<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\EmployeeRole;
use app\models\Vehicle;
use app\api_v3\v3\models\CorporateUser;

/* @var $this yii\web\View */
/* @var $searchModel app\models\EmployeeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Thirdparty User List';
$this->params['breadcrumbs'][] = $this->title;

$employee_role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
?>
<style type='text/css'>
 .more-data{ display:none}
 /* #readMore{ display:block} */
</style>
<div class="employee-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
    <?php if($employee_role == 9){ ?>
    <p>
        <?= Html::a('Create Third Party Employee', ['employee/create-third-party-employee-sales'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php }else{ ?>
    <p>
        <?= Html::a('Create Third Party Employee', ['employee/create-third-party-employee'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php } ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            // 'employee_profile_picture',
            'mobile',
            "email:email",
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
                'attribute' => 'Access Token',
                'header' => 'Access Token',
                'format' => 'raw',
                'value' => function ($model){
                    return CorporateUser::getTokenName($model->id_employee);
                }
            ],
            // [
            //     'attribute' => 'Access Token',
            //     'header' => 'Access Token',
            //     'value' => function ($model){ 
            //         $result = CorporateUser::getTokenName($model->id_employee);
            //         if(!empty($result) && ($result != " - ")){
            //             return substr($result,0,100)."...<div class='more-data'>$result</div><a href=javascript:void(0); id='readMore'>Read More</a>";
            //         } else {
            //             return '-';
            //         }
            //     },
            //     'format' => 'raw',
            // ],
            [
                'attribute'=>'airport',
                'header'=>'Airport',
                'format' => 'raw',
                'value' => function ($model) { 
                    return Vehicle::getThirdEmpAirportName($model->id_employee);
                    //return Order::getAirportName($model->region_id);
                }, 
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width:100px;'],
                'header' => '<span style="color:#3c8dbc;">Action</span>',
                'template' => '{update} {delete}',
                'buttons' => [
                    'update' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-pencil"></span>',
                          $url = Url::toRoute(['thirdparty-corporate/update-user','id'=>$model->id_employee ]), 
                          [ 'title' => Yii::t('app', 'Update'), 'class'=>'', ])
                        );
                    },

                    'delete' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-trash"></span>',
                          $url = Url::toRoute(['thirdparty-corporate/delete-users','id'=>$model->id_employee ]), 
                          [ 'title' => Yii::t('app', 'Delete'), 'class'=>'', 'data' => [
                                'confirm' => 'Are you sure you want to delete this user?',
                                'method' => 'post',
                            ]])
                        );
                    },
                ]
            ],
        ],
    ]); ?>
</div>
