<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Customer */

$this->title = "Corporate Employee " . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Corporate Employee', 'url' => ['corporate-employee']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="customer-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id_customer], ['class' => 'btn btn-primary']) ?>
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
            //'id_customer',
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
            [
                  'attribute' => 'document',
                  'format' => ['image',['width'=>'150','height'=>'150']],
                  'value' => $model->getImageUrl(2),
            ],
            //'mobile_number_verification',
            //'id_proof_verification',
            //'email_verification:email',
            //'status',
            //'date_created',
        ],
    ]) ?>

</div>
