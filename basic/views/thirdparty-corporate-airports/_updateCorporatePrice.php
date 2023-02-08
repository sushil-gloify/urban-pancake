<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\api_v3\v3\models\ThirdpartyCorporateAirports */

$this->title = 'Set Price';
$this->params['breadcrumbs'][] = ['label' => 'Thirdparty Corporate Airports', 'url' => ['thirdparty-corporate/index']];
$this->params['breadcrumbs'][] = ['label' => $model->thirdparty_corporate_airport_id, 'url' => ['view', 'id' => $model->thirdparty_corporate_airport_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="thirdparty-corporate-airports-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_formCorporatePrice', [
        'model' => $model,
        'priceModel' => $priceModel,
    ]) ?>

</div>
