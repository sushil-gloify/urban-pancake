<?php

use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model app\models\Customer */

$this->title = 'Update Corporate Customer: ' . ucwords($model->name);
$this->params['breadcrumbs'][] = ['label' => 'Corporate Customer', 'url' => ['corporate-employee']];
$this->params['breadcrumbs'][] = ['label' => ucwords($model->name), 'url' => ['view-corporate-employee', 'id' => $model->id_customer]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="customer-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('form_corporate_employee', [
        'model' => $model,
        'document' =>$document,
        'profile' => $profile,
    ]) ?>

</div>
