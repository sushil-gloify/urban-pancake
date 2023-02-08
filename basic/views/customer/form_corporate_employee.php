<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\file\FileInput;
use yii\helpers\ArrayHelper;
use yii\web\View;
use app\models\BuildingRestriction;
use app\models\Airlines;
$script = <<< JS
$('.clsbuilding').each(function () {

    if($(this).is(":checked") && $(this).val() == 4) {
        $('.clsothercomments').show();
    }
});

$('.clsbuilding').click(function () {
    if($(this).val() == 4){
        if($(this).is(":checked")) {
            $('.clsothercomments').show();
        }else{
            $('.clsothercomments').val(""); 
            $('.clsothercomments').hide();  
        }  
    }
});
JS;
$this->registerJs($script,View::POS_END,'JS');
$model_airline = new airlines;
$airline_list = airlines::find()->where(['status' => '1'])->all();
$airlines =ArrayHelper::map($airline_list,'airline_id','airline_name');

?>
<div class="customer-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype'=>'multipart/form-data']]); ?>
        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'mobile')->textInput(['minlength' => true,'maxlength' => true]) ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <div class="clsothercomments" style="display:none;">
        <?= $form->field($model, 'other_comments')->textInput(['maxlength' => true]) ?>
    </div>
    <?php
        if(!empty($model->customer_profile_picture)){
                echo $form->field($model, 'customer_profile_picture')->widget(FileInput::classname(), [
                    'options' => ['accept' => 'image/*','style'=>'width:300px'],
                        'pluginOptions' => [
                            'showUpload' => false,
                            'browseLabel' => '',
                            'removeLabel' => '',
                            'initialPreview'=>[
                                Html::img($profile, ['class'=>'file-preview-image', 'alt'=>'', 'title'=>'','style'=>'width:300px;height:250px;']),
                                
                            ],
                            'initialCaption'=>"Customer Profile Picture",
                            'overwriteInitial'=>true
                        ]
                    ]);
        }else{
            echo $form->field($model, 'customer_profile_picture')->widget(FileInput::classname(), [
                        'options' => ['accept' => 'image/*','style'=>'width:300px'],
                            'pluginOptions' => [
                                'showUpload' => false,
                                'browseLabel' => '',
                                'removeLabel' => '',
                                'overwriteInitial'=>true
                            ]
                        ]);
        }
        ?>

        <?= $form->field($model, 'gst_number')->textInput(['minlength' => true, 'maxlength' => true,"style"=>"text-transform:uppercase",'pattern'=>"^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}+$",'title'=>"Invalid GST Number."])->label('GST Number') ?>

        <?= $form->field($model, 'acc_verification')->radioList(array('0'=>'Not Verified','1'=>'Verified','2'=>'Rejected')); ?>

        <?= $form->field($model, 'status')->radioList(array('0'=>'Disable','1'=>'Enable')); ?>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>


</diV>