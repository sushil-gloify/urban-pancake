<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\AirportOfOperation;
use app\models\ThirdpartyCorporate;

/* @var $this yii\web\View */
/* @var $model app\api_v3\v3\models\ThirdpartyCorporateAirports */
/* @var $form yii\widgets\ActiveForm */
?>

<?php 
    $airports = ArrayHelper::map(AirportOfOperation::find()->all(), 'airport_name_id', 'airport_name');
    $corporates = ArrayHelper::map(ThirdpartyCorporate::find()->all(), 'thirdparty_corporate_id', 'corporate_name');
?>
<div class="thirdparty-corporate-airports-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'thirdparty_corporate_id')->hiddenInput(['value'=> $model->thirdparty_corporate_id])->label(false);?>

    <?= $form->field($model, 'airport_id')->hiddenInput(['value' => $model->airport_id])->label(false);?>

    <?= $form->field($model, 'thirdparty_corporate_id')->dropDownList($corporates,['prompt'=>"-----Select-----", 'disabled' => true])->label('Thirdparty Corporate') ?>

    <?= $form->field($model, 'airport_id')->dropDownList($airports,['prompt'=>"-----Select-----", 'disabled' => true])->label('Airport') ?>

    <?= $form->field($priceModel, 'bag_price')->textInput() ?>

    <!-- <?= $form->field($model, 'created_on')->textInput() ?>

    <?= $form->field($model, 'modified_on')->textInput() ?> -->

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
