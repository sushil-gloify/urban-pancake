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

$this->title = 'Subscriber Employees';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="employee-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Employee', ['/super-subscription/create-employee'], ['class' => 'btn btn-success']) ?>
        
    </p>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute'=>'employee_profile_picture',
                'label' => 'Profile Picture',
                'format' => 'html',
                'content' => function ($model) {
                    $url = Yii::$app->params['site_url']."uploads/employee_documents/".$model->employee_profile_picture;
                    return Html::img($url, ['alt'=>'','width'=>'100','height'=>'100']);
                },
            ],
            [
                'attribute'=>'name',
                'value' => function ($model) {
                    return ucwords($model->name);
                },
            ],
            'mobile',
            'email',
            [
                'attribute'=>'fk_tbl_employee_id_employee_role',
                'value' => function ($model) {
                    return ($model->fk_tbl_employee_id_employee_role) ? $model->fkTblEmployeeIdEmployeeRole->role : $model->fkTblEmployeeIdCorporateEmployeeRole->role_name;
                },
            ],
            [
                'attribute'=>'status',
                'value' => function ($model) {
                    return $model->status == 1? 'Enabled':'Disabled';
                },
                'filter'=>array('1'=>'Enabled','0'=>'Disabled')
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width:100px;'],
                'header' => '<span style="color:#3c8dbc;">Action</span>',
                'template' => '{view}  {delete}',
                'buttons' => [
                    'view' => function($url, $model) {
                        return (Yii::$app->Common->getAssignEmployee($model->id_employee) == "") ? Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, ['title' => Yii::t('app', 'view')]) : "";
                    },
                    'delete' => function($url, $model) {
                        return (Yii::$app->Common->getAssignEmployee($model->id_employee) == "") ? Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, ['title' => Yii::t('app', 'delete')]) : "";
                    }
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'view') {
                        $url ='index.php?r=super-subscription/employee-view&id='.$model->id_employee;
                        return $url;
                    }
                    
                    if ($action === 'delete') {
                        $url ='index.php?r=super-subscription/employee-delete&id='.$model->id_employee;
                        return $url;
                    }
                }
            ]
        ],
    ]); ?>
</div>
