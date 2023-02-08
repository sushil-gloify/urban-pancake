<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\URL;
use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CustomerSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Corporate Customer';
$this->params['breadcrumbs'][] = $this->title;
$employee_id = Yii::$app->user->identity->id_employee;
?>
<div class="customer-index">
    <div class="">
        <span class="pull-right">
            <?php 
                $gridColumns = [
                    [
                    'attribute' => "customerId",
                        'header' => "Corporate ID",
                        'value' => function ($model){
                            return $model->customerId;
                        }
                    ],
                    [
                        'attribute' => 'name',
                        'value' => function ($model){
                            return ucwords($model->name);
                        }
                    ],
                    [
                        'attribute' => 'gst_number',
                        'value' => function ($model){
                            return strtoupper($model->gst_number);
                        }
                    ],
                    [
                        'attribute' => 'tour_id',
                        'value' => function ($model){
                            return !empty($model->tour_id) ? strtoupper($model->tour_id) : "-";
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'value' => function ($model){
                            return ($model->status == 1) ? 'Enable' : 'Disable';
                        },
                        'filter' => array("1"=>"Enable" , "0" => "Disabale"),
                    ],
                    [
                        'attribute' => 'acc_verification',
                        'header' => 'Account Verification',
                        'value' => function ($model) { 
                            return ($model->acc_verification == 1 ? "Verified" : ($model->acc_verification == 0 ? "Not Verified" : "Rejected" ));
                        },
                        'filter'=>array("0"=>"Not Verified","1"=>"Verified","2"=>"Rejected"),
                    ],
                    [
                        'attribute' => 'date_created',
                        'value' => function ($model) {
                            return !empty($model->date_created) ? date('Y-m-d',strtotime($model->date_created)) : '-';
                        }
                    ],
                ];

                User::downloadExportDataCorpCust($dataProvider,$gridColumns,'Corporate_customer');  
            ?>
        </span>
    </div>

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?php //echo Html::a('Create Corporate Customer', ['create-corporate-employee'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout'=>"{items}\n{summary}\n{pager}",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => "customerId",
                'header' => "Corporate ID",
                'value' => function ($model){
                    return $model->customerId;
                }

            ],
            [
                'attribute' => 'name',
                'value' => function ($model){
                    return ucwords($model->name);
                }
            ],
            [
              'attribute' => 'mobile',
                'value' => function ($model) { 
                    $country_code = $model->getcontrycode($model->fk_tbl_customer_id_country_code);
                    return $country_code.'-'.$model->mobile;
                },  
            ],
            'email:email',
            [
                'attribute' => 'gst_number',
                'value' => function ($model){
                    return strtoupper($model->gst_number);
                }
            ],
            [
                'attribute' => 'tour_id',
                'value' => function ($model){
                    return !empty($model->tour_id) ? strtoupper($model->tour_id) : "-";
                }
            ],
            [
                'attribute' => 'status',
                'value' => function ($model){
                    return ($model->status == 1) ? 'Enable' : 'Disable';
                },
                'filter' => array("1"=>"Enable" , "0" => "Disabale"),
            ],
            [
                'attribute' => 'acc_verification',
                'header' => 'Account Verification',
                'value' => function ($model) { 
                    return ($model->acc_verification == 1 ? "Verified" : ($model->acc_verification == 0 ? "Not Verified" : "Rejected" ));
                },
                'filter'=>array("0"=>"Not Verified","1"=>"Verified","2"=>"Rejected"),
            ],
            [
                'attribute' => 'date_created',
                'value' => function ($model) {
                    return !empty($model->date_created) ? date('Y-m-d',strtotime($model->date_created)) : '-';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width:90px;'],
                'header'=>'Actions',
                'template' => '{view} {update} ',//{delete}

                'buttons' => [
                    'update' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-pencil"></span>',
                          $url = Url::toRoute(['customer/update-corporate-employee','id'=>$model->id_customer ]), 
                          [ 'title' => Yii::t('app', 'update'), 'class'=>'', ])
                        );
                    },

                    'view' => function ($url, $model) {
                        return (
                          Html::a('<span class="glyphicon glyphicon-eye-open"></span>',
                          $url = Url::toRoute(['customer/view-corporate-employee','id'=>$model->id_customer ]), 
                          [ 'title' => Yii::t('app', 'view'), 'class'=>'', ])
                        );
                    },
                ],
            ]
        ],
    ]); ?>
</div>