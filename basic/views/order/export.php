<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
$this->title = 'Order Export List';
$this->params['breadcrumbs'][] = $this->title;
$client_id =$client['client_id'];
?>
<div class="city-of-operation-index">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'path',
                'header' => 'Filename',
                'value' => function($model) {
                    //return $model->path; 
                    return ($model->path == "") ? 'File generation is being processing' : $model->path; 
                }
            ],
            'start_date',
            'end_date',
            [
                'attribute' => 'status',
                'header' => 'Status',
                'value' => function($model) {
                    return ($model->status == 1) ? 'Success' : "Pending"; 
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width:100px;'],
                'header'=>'Actions',
                'template' => '{view}',//{delete}{update}
                'buttons' => [
                    'view' => function ($url, $model) { 
                        $file =str_replace('.csv','',$model->path);
                        $class="active";
                        $url='<a href="https://hyd.carterx.in/api/v1/downloadcsvfile/'.$file.'"><span class="glyphicon glyphicon-download".'.$class.'></span></a>';
                        if($model->status == 0){
                            $class="text-muted";
                        }else{
                            return (
                                $url
                           );
                        }
                        
                    },
                
                ]
            ],
        ],
    ]); ?>


</div>

