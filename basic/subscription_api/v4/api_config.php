<?php

namespace app\subscription_api\v4;

/**
 * v2 module definition class
 */
class api_config extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\subscription_api\v4\controllers';
    public $modelNamespace = 'app\subscription_api\v4\models';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
