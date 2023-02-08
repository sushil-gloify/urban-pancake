<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\TaxValues;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\CorporateDetails */
/* @var $form yii\widgets\ActiveForm */
?>

<head>
   <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?php echo Url::base(); ?>/themes/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo Url::base(); ?>/themes/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?php echo Url::base(); ?>/themes/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo Url::base(); ?>/themes/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
  <link rel="stylesheet" href="<?php echo Url::base(); ?>/themes/_all-skins.min.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<?php
$role = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
$id_employee = Yii::$app->user->identity->id_employee;
?>
<!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3><?= $data['currentOrdersCount']; ?></h3>

              <p>Current orders</p>
            </div>
            <div class="icon">
              <i class="ion ion-bag"></i>
            </div>
            <a href="index.php?r=order/get-orders-by-dashboard&key=current" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!--  -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-light-blue">
            <div class="inner">
              <h3><?= $data['allOrdersCount']; ?></h3>

              <p>All Orders</p>
            </div>
            <div class="icon">
              <i class="ion ion-bag"></i>
            </div>
            <?php if($role == 1) { ?>
              <a href="index.php?r=order%2Findex" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
              <?php } else if($role == 7) {?>
                <a href="index.php?r=order%2Fuser-orders" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
              <?php } else if($role == 3) {?>
                <a href="index.php?r=orders%2Findex" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
              <?php } ?>
          </div>
        </div>
        <!--  -->
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3><?= $data['flexibleOrdersCount']; ?></h3>

              <p>Flexible Fields</p>
            </div>
            <div class="icon">
              <i class="ion ion-stats-bars"></i>
            </div>
            <a href="index.php?r=order/get-orders-by-dashboard&key=flexible" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3><?= $data['rescheduleOrdersCount']; ?></h3>

              <p>Reschedule Orders</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="index.php?r=order/get-orders-by-dashboard&key=reschedule" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3><?= $data['undeliveryOrdersCount']; ?></h3>

              <p>Undelivered Orders</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
            <a href="index.php?r=order/get-orders-by-dashboard&key=undelivered" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3><?= $data['refundOrdersCount']; ?></h3>

              <p>Refund Orders</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
            <a href="index.php?r=order/get-orders-by-dashboard&key=refund" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
      </div>
  </section>




<div class="panel panel-primary" style="display:none">
    <div class="panel-heading">
        Manage Tax(s)
    </div>

    <div class="panel-body">

            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model['tv'], 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model['tv'], 'value')->textInput(['rows' => 6]) ?>

            <?= Html::submitButton($model['tv']->isNewRecord ? 'Create' : 'Update', ['class' => $model['tv']->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>

            <?php ActiveForm::end(); ?>


    </div>
</div>