<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Customer */

$this->title = 'Create Corporate Customer';
$this->params['breadcrumbs'][] = ['label' => 'Corporate Customer', 'url' => ['corporate-employee']];
$this->params['breadcrumbs'][] = $this->title;
$role_id = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
?>
<div class="customer-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('form_corporate_employee', [
        'model' => $model,
        'document' =>$document,
        'profile' => $profile,
    ]) ?>

</div>
