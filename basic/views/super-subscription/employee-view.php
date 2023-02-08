<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
//use lo\modules\noty\assets\GrowlAsset;
//use lo\modules\noty\layers\Noty;


/* @var $this yii\web\View */
/* @var $model app\models\Employee */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Employees', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>


<?php /*echo Wrapper::widget([
    'layerClass' => 'lo\modules\noty\assets\GrowlAsset',
]);*/ ?>
<?php //echo Wrapper::widget([
    //'layerClass' => 'lo\modules\noty\layers\Noty',
//]); ?>
<div class="employee-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            //'id_employee',
            'fk_tbl_employee_id_employee_role',
            'name',
            'employee_profile_picture',
             [
                  'attribute' => 'employee_profile_picture',
                  'format' => ['image',['width'=>'150','height'=>'150']],
                  'value' => $model->getImageUrl(1),
              ],
            'mobile',
            'email:email',
            //'password',
            'adhar_card_number',
            //'document_id_proof',
            [
                  'attribute' => 'document_id_proof',
                  'format' => ['image',['width'=>'150','height'=>'150']],
                  'value' => $model->getImageUrl(2),
            ],
            //'mobile_number_verification',
            //'status',
            //'date_created',
            //'date_modified',
        ],
    ]) ?>

</div>
