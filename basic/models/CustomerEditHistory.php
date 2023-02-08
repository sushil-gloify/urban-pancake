<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tbl_customer_edit_history".
 *
 * @property int $customer_edit_id
 * @property int $customer_id
 * @property string $description
 * @property string $module_name
 * @property int $edit_by
 * @property string $edit_by_name
 * @property string $edit_date
 */
class CustomerEditHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_customer_edit_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_id', 'description', 'module_name', 'edit_by', 'edit_by_name'], 'required'],
            [['customer_id', 'edit_by'], 'integer'],
            [['edit_date'], 'safe'],
            [['description'], 'string', 'max' => 100],
            [['module_name', 'edit_by_name'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'customer_edit_id' => 'Customer Edit ID',
            'customer_id' => 'Customer ID',
            'description' => 'Description',
            'module_name' => 'Module Name',
            'edit_by' => 'Edit By',
            'edit_by_name' => 'Edit By Name',
            'edit_date' => 'Edit Date',
        ];
    }
}
