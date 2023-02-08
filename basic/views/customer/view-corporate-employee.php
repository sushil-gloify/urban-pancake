<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Customer */

$this->title = ucwords($model->name);
$this->params['breadcrumbs'][] = ['label' => 'Corporate Employee', 'url' => ['corporate-employee']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="customer-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update-corporate-employee', 'id' => $model->id_customer], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id_customer], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            'mobile',
            'email:email',
            //'address:ntext',
            //'document',
            [
                  'attribute' => 'customer_profile_picture',
                  'format' => ['image',['width'=>'150','height'=>'150']],
                  'value' => $model->getImageUrl(1),
            ],
            // [
            //       'attribute' => 'document',
            //       'format' => ['image',['width'=>'150','height'=>'150']],
            //       'value' => $model->getImageUrl(2),
            // ],
            //'mobile_number_verification',
            [
                'attribute' => 'acc_verification',
                'value' => function ($model) {
                    return ($model->acc_verification == 1) ? "Verified" : (($model->acc_verification == 2) ? "Rejected" : 'Unverified');
                }
            ],
            [
                'attribute' => 'status',
                'value' => function ($model){
                    return ($model->status == 1) ? 'Enable' : "Disable";
                }
            ]
        ],
    ]) ?>

</div>

<div class="panel-body">
    <!-- Customer History Details Here -->
    <div class="container">
        <?php 
            $HistResult = Yii::$app->Common->getCustomerHistoryAllDetails($_GET['id']);
            $table = "<table class='table'><thead><tr><th>Modified By</th><th>Modified Field</th><th>Description</th><th>Change Date</th></tr></thead>";
            if(!empty($HistResult)){
                foreach($HistResult as $value){
                    $table .= "<tr><td>".ucwords($value['edit_by_name'])."</td><td>".$value['module_name']."</td><td>".$value['description']."</td><td>".date('Y-m-d h:i:s',strtotime($value['edit_date']))."</td></tr>";
                }
            } else {
                $table .= "<tr><td colspan='3' class='text-center'>No Modification </td></tr>";
            }
            $table .= "</tbody></table>";
            echo $table;
        ?>
    </div>
    <!-- Customer History Details Here -->
</div>
