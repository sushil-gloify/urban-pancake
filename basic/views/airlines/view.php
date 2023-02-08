<?php

use yii\helpers\Html;

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\subscription_api\models\Airlines */

$this->title = $model->airline_id;
$this->params['breadcrumbs'][] = ['label' => 'Airlines', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="airlines-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->airline_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->airline_id], [
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
            'airline_id',
            'airline_name',
            [
                'attribute'=>'status',
                'value' => function ($model) {
                    return $model->status == 1? 'Enabled':'Disabled';
                },
                'filter'=>array('1'=>'Enabled','0'=>'Disabled')
            ],
            'created_on',
            'modified_on',
        ],
    ]) ?>

</div>
