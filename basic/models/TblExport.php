<?php

namespace app\models;
use Yii;

/**
 * This is the model class for table "tbl_tax_values".
 *
 * @property integer $tax_id
 * @property string $name
 * @property string $certification
 * @property integer $value
 * @property string $created
 * @property string $updated
 */
class TblExport extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_orderexport';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idorderexport', 'start_date', 'end_date', 'path'], 'required'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['path'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'idorderexport' => 'Export id',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'path' => 'Iilename',
            'status' => 'Status',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    
}
