<?php
    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use app\models\EmployeeRole;
    use yii\helpers\ArrayHelper;
    use kartik\file\FileInput;
    use app\models\AirportOfOperation;
    use app\models\CityOfOperation;
    use kartik\select2\Select2;

    /* @var $this yii\web\View */
    /* @var $model app\models\Employee */
    /* @var $form yii\widgets\ActiveForm */

    $roleId = Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
?>
<div class="employee-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'mobile')->textInput(['type' => 'number','minlength'=>10,'maxlength' => 10]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <div id="password">
        <?= $form->field($model, 'password')->passwordInput(['minlength'=>4,'maxlength' => 12]) ?>
    </div>

    <?= $form->field($model, 'fk_tbl_employee_id_employee_role')->dropDownList(ArrayHelper::map(EmployeeRole::getEmployeeRoles(),'id_employee_role','role'),['prompt'=>'-Choose a Role-','onchange'=>'show_hide_password(this);']) ?>

    <?php 
        if(!empty($model->employee_profile_picture)){
            echo $form->field($model, 'employee_profile_picture')->widget(FileInput::classname(), [
                'options' => ['accept' => 'image/*','style'=>'width:300px'],
                'pluginOptions' => [
                    'showUpload' => false,
                    'browseLabel' => '',
                    'removeLabel' => '',
                    'initialPreview'=>[
                        Html::img($profile, ['class'=>'file-preview-image', 'alt'=>'', 'title'=>'','style'=>'width:300px;height:250px;']),
                    ],
                    'initialCaption'=>"Employee Profile Picture",
                    'overwriteInitial'=>true
                ]
            ]);
        }else{
            echo $form->field($model, 'employee_profile_picture')->widget(FileInput::classname(), [
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
    <?= $form->field($model, 'adhar_card_number')->textInput(['type' => 'number','minlength'=>12,'maxlength' => 12]) ?>
    <?php
        if(isset($model->document_id_proof)){
            echo $form->field($model, 'document_proof')->widget(FileInput::classname(), [
                'options' => ['accept' => 'image/*','style'=>'width:300px'],
                'pluginOptions' => [
                    'showUpload' => false,
                    'browseLabel' => '',
                    'removeLabel' => '',
                    'initialPreview'=>[
                        Html::img($document, ['class'=>'file-preview-image', 'alt'=>'', 'title'=>'','style'=>'width:300px;height:250px;']),
                    ],
                    'initialCaption'=>"Employee Document",
                    'overwriteInitial'=>true
                ]
            ]);
        }else{
            echo $form->field($model, 'document_proof')->widget(FileInput::classname(), [
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

    <?php isset($model->status) ? $model->status : 1;  ?>
    <?= $form->field($model, 'status')->radioList(array('0'=>'Disable','1'=>'Enable')); ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
    <?php //$roleid = $model->fk_tbl_employee_id_employee_role; ?>
</div>
<script type="text/javascript">
    // function show_hide_password(el)
    // {
    //     console.log(el);

    //     if(el.value==1 || el.value==2 || el.value==3 || el.value==4 || el.value==7 || el.value == 9 || el.value ==10|| el.value ==8 || el.value ==11 ){
    //         $('#password').show();
    //     }else{
    //         $('#password').hide();
    //     }
    //     if(el.value ==16){
    //         $('#tman_access').show();
    //         //$('#airport').hide();
    //     }else{
    //         $('#tman_access').hide(); 
    //         $('#airport').show();
    //     }
    // }
    
    // $(document).ready(function(){
    //     $.ajax({
    //         type: "get",
    //         url: "index.php?r=super-subscription/get-country-code",
    //         success: function(data) {
    //             $("#country_code").html(data)
    //         }
    //     });
    // });
</script>