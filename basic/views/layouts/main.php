<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
$role_name_array = array(1=>"Super Admin",2=>"Admin",3=>"Admin at the gate",4=>"Customer Care",5=>"PorterX",6=>"Porter",7=>"Customer As Admin",8=>"Corporate",9=>"Sales",10=>"Kiosk",11=>"Flyporter Corporate Kiosk",12=>"Corporate Super Admin",13=>"Corporate Admin",14=>"Corporate Kiosk",15=>"Corporate Customer Care",16=>"T-Man",17=>"Super Subscriber",18=>"Super SUbscriber Employee",21=>"Help Assistance");
$role_id = isset(Yii::$app->user->identity->fk_tbl_employee_id_employee_role) ? Yii::$app->user->identity->fk_tbl_employee_id_employee_role : '';

$role_name = isset(Yii::$app->user->identity->name) ? Yii::$app->user->identity->name : '';
if(!empty($role_id)){
    $role_name = "(".$role_name_array[$role_id].' - '.ucwords($role_name).")";
} else {
    $role_name = "";
}



?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script src="js/jquery.min.js"></script>
    <link href="css/lightgallery.css" rel="stylesheet">
    <?php $this->head() ?>
    <style type="text/css">
        @media all and (max-width: 480px) {
            .navbar-nav {
                margin-top: 0 !important;
                background-color: white;
            }
            .navbar-toggle {
                margin-top: 28px;
            }
            .navbar-inverse .navbar-nav > li > a:hover, .navbar-inverse .navbar-nav > li > a:focus, .logout:hover {
                color: #337ab7 !important;
            }
        }
        @media (min-width: 768px) and (max-width: 991px) {
            .main_class{
                padding-top: 20%;
            }
        }
        .container{
            width: 100% !important;
        }
    </style>
</head>
<body>
    <div  class="loaderTop" style="" >
<div  class="loader" ></div>
</div>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
   
    if(Yii::$app->user->isGuest)
    {
       NavBar::begin([
            //'brandLabel' => Html::img('@web/image/freightlogo.png',['height'=>'30px']),
            'brandLabel' => 'Carter',
            'brandUrl' => Yii::$app->homeUrl,
            'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
            'role' => 'navigation',
            ]
        ]);
        echo Nav::widget([

                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => [
                
                [
                        'label' => 'Login',
                        'url' => ['site/login'],
                        'visible' => Yii::$app->user->isGuest
                ],      
            

                [
                            // 'label' => 'Logout (' . Yii::$app->user->identity['user_name']. ')',
                            'label' => 'Logout'.$role_name,
                            'url' => ['/site/logout'],
                            'linkOptions' => ['data-method' => 'post'],
                            'visible' => !Yii::$app->user->isGuest

                ],
                ]
        ]);
        NavBar::end();

    } else { 
        $role_id=Yii::$app->user->identity->fk_tbl_employee_id_employee_role;
        if($role_id == 7){
             NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => [
                    ['label' => 'Orders', 'url' => ['/order/user-orders']],
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }elseif($role_id == 9){
            NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                  
                    ['label' => 'Corporates', 'url' => ['/employee/corporate-list']],
                    '<li class="divider"></li>',
                    [
                        'label' => 'Third Party Corporates',
                        'url' => ['/thirdparty-corporate/index']
                    ],
                    
                    '<li class="divider"></li>',
                    [
                        'label' => 'Third Party Users',
                        'url' => ['/thirdparty-corporate/users-list']
                    ],
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }elseif($role_id == 4){
           NavBar::begin([
                'brandLabel' => 'Customer Care',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],

            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                  
                     
                     [
                        'label' => 'Manage Orders',
                        'items' => [
                            [
                                    'label' => 'Orders', 'url' => ['/order/index'],
                            ],
                            '<li class="divider"></li>',

                            [
                            'label' => 'MHL orders',
                            'url' => ['/order/mhl-order']
                            ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'Kiosk Made Orders',
                                'url' => ['/order/corporate-order']
                            ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'Frontend Customer Orders',
                                'url' => ['/order/normal-order']
                            ],                                                
                        ],
                    ],
                   
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);  
        }elseif($role_id == 10){
            NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/employee/kiosk-dashboard']],                            
                    [
                        'label' => 'Create Orders',
                        'items' => [
                            [
                                    'label' => 'Create Corporate Order',
                                    'url' => ['/employee/create-corporate-order-kiosk'],
                            ],
                             '<li class="divider"></li>',
                            [
                                'label' => 'Create General Order',
                                'url' => ['/employee/search-customer'],
                            ],   
                        ],
                    ],
                    ['label' => 'Orders Export', 'url' => ['/order/export']],
                    
                    ['label' => 'Orders', 
                    'items' => [
                        [
                            'label' => 'All Order',
                            'url' => ['/order/kiosk-orders'],
                        ], 
                        '<li class="divider"></li>', 
                        [
                            'label' => 'MHL Order',
                            'url' => ['/order/kiosk-mhlorders'],
                        ], 
                        '<li class="divider"></li>', 
                        [
                            'label' => 'Kiosk Made Orders',
                            'url' => ['/order/kioskmadecorporateorder']
                        ],
                    ],
                    ],
                    ['label' => 'All Orders', 'url' => ['/order/all-kiosk-orders']],
                    [
                        'label' => 'Allocations',
                        'items' => [
                            [
                                    'label' => 'Pending order Allocations',
                                    'url' => ['/order/pending-order-allocation'],
                            ],
                             '<li class="divider"></li>',
                            [
                                    'label' => 'Porter Allocations',
                                    'url' => ['/vehicle-slot-allocation/index'],
                            ],
                            '<li class="divider"></li>',
                            
                            [
                                    'label' => 'PorterX Allocations',
                                    'url' => ['porterx-allocation/index'],
                            ],
                             '<li class="divider"></li>',
                            ],
                    ],
                   
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }elseif($role_id == 11){
            NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/employee/kiosk-dashboard']],                            
                    [
                        'label' => 'Create Orders',
                        'items' => [
                            [
                                    'label' => 'Create General Order',
                                    'url' => ['/employee/search-customer'],
                            ],                                                   
                            ],
                    ],
                    ['label' => 'Orders Export', 'url' => ['/order/export']],
                    ['label' => 'Manage Orders', 'url' => ['/order/corporate-kiosk-orders']],
                    
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }elseif ($role_id == 8) { 
            NavBar::begin([
                //'brandLabel' => 'Carter',
                'brandLabel' => Html::img('@web/images/logo_carter.png',['height'=>'45px']),
                'brandUrl' =>['/employee/corporate-dashboard'],
                'brandOptions'=>['style'=>'height: auto;padding: 4px 15px;'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                    'style'=>'height: 13%;',
                ],
            ]); 
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/employee/corporate-dashboard']],
                    ['label' => 'My Orders', 'url' => ['/order/corporate-orders']],
                    ['label' => 'Orders Export', 'url' => ['/order/export']],
                    // ['label' => 'T-man Orders', 'url' => ['/tman-orders/corporate-tman-order-index']],
                    ['label' => 'My Account', 'url' => ['/employee/corporate-view']],
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }elseif($role_id == 12 || $role_id == 13 || $role_id == 14 || $role_id == 15){
            NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/employee/corporate-superadmin-dashboard']],   
                    ['label' => 'Corporate Customer', 'url' => ['/customer/coprorate-customer']],
                    (!($role_id === 14 || $role_id === 15)) ? ['label' => 'Users List', 'url' => ['/thirdparty-corporate/users-list']] : '',
                    ['label' => 'Orders Export', 'url' => ['/order/export']],
                    (($role_id != 15) && ($role_id != 14)) ? [
                        'label' => 'Create Orders',
                        'items' => [
                            [
                                'label' => 'Create General Order',
                                'url' => ['/employee/search-customer'],
                            ]                   
                        ],
                    ] : '',
                    ($role_id == 14) ? [
                        'label'=> 'Create Orders',
                        'items'=> [
                            [
                                'label' => 'Create General Order',
                                'url' => ['/employee/search-customer'],
                            ],                                
                            [
                                'label' => 'Create MHL Corporate Order',
                                'url' => ['/employee/create-corporate-order'],
                            ],
                        ],
                    ] : '',
                    ['label' => 'Change Password', 'url' => ['/employee/change-password']],
                    ['label' => 'Manage Orders', 'url' => ['/order/corporate-kiosk-orders']],
                    ['label' => 'Subscription Details','url' => ['/super-subscription/subscription-details']] ,
                   
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }else if($role_id == 1){
            NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/site/index']],
                   // ['label' => 'Orders', 'url' => ['/order/index']],
                    [
                        'label' => 'Orders',
                        'items' => [
                            [
                                'label' => 'All Orders',
                                'url' => ['/order/index']
                            ],
                            '<li class="divider"></li>',

                            [
                            'label' => 'MHL orders',
                            'url' => ['/order/mhl-order']
                            ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'Kiosk Made Orders',
                                'url' => ['/order/corporate-order']
                            ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'Frontend Customer Orders',
                                'url' => ['/order/normal-order']
                            ],
                            
                        ]
                    ],
                    ['label' => 'Subscription Details', 'url' => ['/super-subscription/subscription-details']],
                    // ['label' => 'Airlines', 'url' => ['/airlines/index']],
                    // ['label' => 'T-Man Orders', 'url' => ['/tman-orders/index']],
                    // ['label' => 'Orders Export', 'url' => ['/order/export']],
                    // ['label' => 'T-Man Orders', 'url' => ['/tman-orders/index']],
                    ['label' => 'Employees', 'url' => ['/employee/index']],
                    [
                        'label' => 'Corporate',
                        // 'url' => ['/employee/corporate-list'] 
                        'items' => [
                            [
                            'label' => 'Corporates',
                            'url' => ['/employee/corporate-list']
                            ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'T-Man Corporates',
                                'url' => ['create-airline/index']
                            ], 
                                
                            '<li class="divider"></li>',
                            [
                                'label' => 'Third Party Corporates',
                                'url' => ['/thirdparty-corporate/index']
                            ], 
                            '<li class="divider"></li>',
                            [
                                'label' => 'Third Party Users',
                                'url' => ['/thirdparty-corporate/users-list']
                            ],
                            // '<li class="divider"></li>',
                            // [
                            //     'label' => 'Corporate Employees',
                            //     'url' => ['/customer/corporate-employee']
                            // ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'Super Subscription',
                                'url' => ['/super-subscription/index']
                            ],
                            // '<li class="divider"></li>',
                            // [
                            //     'label' => 'Subscription Details',
                            //     'url' => ['/super-subscription/subscription-details']
                            // ] 
                        ]
                    ],
                    
                    ['label' => 'Customers', 'url' => ['/customer/index']],
                    ['label' => 'Vehicles', 'url' => ['/vehicle/index']],
                    ['label' => 'Vehicle-Driver-Map', 'url' => ['/labour-vehicle-allocation/index']],
                    //['label' => 'Allocations', 'url' => ['/vehicle-slot-allocation/index']],
                    // [
                    //     'label' => 'Settings',
                    //     'items' => [
                    //         [
                    //         'label' => 'City Operation',
                    //         'url' => ['/city/index']
                    //         ],
                    //         '<li class="divider"></li>',
                    //         [
                    //             'label' => 'Airport Operation',
                    //             'url' => ['/airport/index']
                    //         ], 
                                
                    //         '<li class="divider"></li>',
                    //         [
                    //             'label' => 'City Slots Mapping',
                    //             'url' => ['/city-slots/index']
                    //         ],
                    //     ]
                    // ],
                    [
                        'label' => 'Allocations',
                        'items' => [
                            [
                                    'label' => 'Pending order Allocations',
                                    'url' => ['/order/pending-order-allocation'],
                            ],
                                '<li class="divider"></li>',
                            [
                                    'label' => 'Porter Allocations',
                                    'url' => ['/vehicle-slot-allocation/index'],
                            ],
                            '<li class="divider"></li>',
                            
                            [
                                    'label' => 'PorterX Allocations',
                                    'url' => ['porterx-allocation/index'],
                            ],
                                '<li class="divider"></li>',
                                [
                                    'label' => 'Group',
                                    'url' => ['order-group/assign-group-porterx'],
                            ],
                                '<li class="divider"></li>',               
                            
                            ],
                    ],
                    [
                        'label' => 'Preferences',
                        'items' => [
                                [
                                        'label' => 'Porter Tracking',
                                        'url' => ['/employee/porter-location'],
                                ],
                                    '<li class="divider"></li>',
                                [
                                        'label' => 'Manage Pincode',
                                        'url' => ['/pick-drop-location/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Whitelist Customers',
                                        'url' => ['/whitelist-customer/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Manage Offers',
                                        'url' => ['/luggage-offers/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Manage City Offers',
                                        'url' => ['/city-luggage-offers/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Manage City Group Offers',
                                        'url' => ['/city-group-offers/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Manage Corporate Price Details',
                                        'url' => ['/corporate-luggage-price/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Manage Group Offers',
                                        'url' => ['/group-offers/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Manage Promo Codes',
                                        'url' => ['/promo-codes/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Bulk Import',
                                        'url' => ['/background-csv-import/create'],  
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Create Invoice',
                                        'url' => ['/order/create-invoice'],  
                                ],
                                '<li class="divider"></li>',
                                [
                                    'label' => 'Airline',
                                    'url' => ['/airlines/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                    'label' => 'Orders Export',
                                    'url' => ['/order/export'],
                                ],
                            ],
                    ],
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout ' . $role_name ,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }else if($role_id == 17){
            NavBar::begin([
                'brandLabel' => 'Carter',
                'brandUrl' =>['/super-subscription/super-subscriber-dashboard'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/super-subscription/super-subscriber-dashboard']],
                    ['label' => 'Employees', 'url' => ['/super-subscription/employees-list']],
                    ['label' => 'Purchase Subscription', 'url' => ['/super-subscription/purchase-subscription']],
                    ['label' => 'Subscription Details', 'url' => ['/super-subscription/subscription-details']],
                    ['label' => 'Create Orders', 'url' => ['/super-subscription/search-employee']],
                    ['label' => 'Manage Orders', 'url' => ['/super-subscription/manage-order']],
                    ['label' => 'Orders Export', 'url' => ['/order/export']],
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout ' . $role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
        }else if($role_id == 18){
            NavBar::begin([
                'brandLabel' => 'Carter',
                'brandUrl' =>['/super-subscription/super-subscriber-dashboard'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ]
            ]);
        }else if($role_id == 21){
            NavBar::begin([
                'brandLabel' => 'Carter',
                'brandUrl' =>['/helpassistance-api/ticket-dashboard'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right','style'=>'margin-top: 16px'],
                'items' => [
                    ['label' => 'Ticket Details', 'url' => ['/v4/helpassistance-api/tickets-details']],

                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]

                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout '.$role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ]
            ]);
        }else{
            NavBar::begin([
                'brandLabel' => 'Carter',
                //'brandUrl' => Yii::$app->homeUrl,
                'brandUrl' =>['site/login'],
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => [
                    ['label' => 'Home', 'url' => ['/site/index']],
                    //['label' => 'Orders', 'url' => ['/order/index']],
                    // [
                    //     'label' => 'Orders', 
                    //     'items' => [
                    //         [
                    //         'label' => 'Orders',
                    //         'url' => ['/order/index']
                    //         ] 
                    //     ]
                    // ],
                    ['label' => 'Orders', 'url' => ['/order/index']],
                    // ['label' => 'T-Man Orders', 'url' => ['/tman-orders/index']],
                    ['label' => 'Employees', 'url' => ['/employee/index']],
                    
                    [
                        'label' => 'Corporate',
                        // 'url' => ['/employee/corporate-list'] 
                        'items' => [
                            [
                            'label' => 'Corporates',
                            'url' => ['/employee/corporate-list']
                            ],
                            '<li class="divider"></li>',
                            [
                                'label' => 'T-Man Corporates',
                                'url' => ['create-airline/index']
                            ], 
                             
                            '<li class="divider"></li>',
                            [
                                'label' => 'Third Party Corporates',
                                'url' => ['/thirdparty-corporate/index']
                            ], 
                            '<li class="divider"></li>',
                            [
                                'label' => 'Third Party Users',
                                'url' => ['/thirdparty-corporate/users-list']
                            ],
                            [
                                'label' => 'Orders Export',
                                'url' => ['/order/export'],
                            ],
                        ]
                    ],
                   
                    ['label' => 'Customers', 'url' => ['/customer/index']],
                    ['label' => 'Vehicles', 'url' => ['/vehicle/index']],
                    ['label' => 'Vehicle-Driver-Map', 'url' => ['/labour-vehicle-allocation/index']],
                    //['label' => 'Allocations', 'url' => ['/vehicle-slot-allocation/index']],
                    [
                        'label' => 'Allocations',
                        'items' => [
                            [
                                    'label' => 'Pending order Allocations',
                                    'url' => ['/order/pending-order-allocation'],
                            ],
                             '<li class="divider"></li>',
                            [
                                    'label' => 'Porter Allocations',
                                    'url' => ['/vehicle-slot-allocation/index'],
                            ],
                            '<li class="divider"></li>',
                            
                            [
                                    'label' => 'PorterX Allocations',
                                    'url' => ['porterx-allocation/index'],
                            ],
                             '<li class="divider"></li>',
                             [
                                    'label' => 'Group',
                                    'url' => ['order-group/assign-group-porterx'],
                            ],
                             '<li class="divider"></li>',               
                            
                            ],
                    ],
                    [
                        'label' => 'Preferences',
                        'items' => [
                                [
                                        'label' => 'Porter Tracking',
                                        'url' => ['/employee/porter-location'],
                                ],
                                 '<li class="divider"></li>',
                                [
                                        'label' => 'Manage Pincode',
                                        'url' => ['/pick-drop-location/index'],
                                ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Whitelist Customers',
                                        'url' => ['/whitelist-customer/index'],
                                ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Manage Offers',
                                //         'url' => ['/luggage-offers/index'],
                                // ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Manage City Offers',
                                //         'url' => ['/city-luggage-offers/index'],
                                // ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Manage City Group Offers',
                                //         'url' => ['/city-group-offers/index'],
                                // ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Manage Corporate Price Details',
                                //         'url' => ['/corporate-luggage-price/index'],
                                // ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Manage Group Offers',
                                //         'url' => ['/group-offers/index'],
                                // ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Manage Promo Codes',
                                //         'url' => ['/promo-codes/index'],
                                // ],
                                // '<li class="divider"></li>',
                                // [
                                //         'label' => 'Bulk Import',
                                //         'url' => ['/background-csv-import/create'],  
                                // ],
                                '<li class="divider"></li>',
                                [
                                        'label' => 'Create Invoice',
                                        'url' => ['/order/create-invoice'],  
                                ],
                               
                            
                            ],
                    ],
                    Yii::$app->user->isGuest ? (
                        ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            // 'Logout (' . Yii::$app->user->identity->name . ')',
                            'Logout ' . $role_name,
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);       
        }
        NavBar::end();
    }
    ?>
    <div class="container main_class" style="width: 100%;">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container" style="width: 100%;">
        <p class="pull-left">&copy; CarterPorter Pvt limited </p>

        <!-- <p class="pull-right"><?= Yii::powered() ?></p> -->
        <!-- <p class="pull-right"><?= "Powered by PACE WISDOM SOLUTIONS"  ?></p> -->
    </div>
</footer>
<?php if (class_exists('yii\debug\Module')) {
    $this->off(\yii\web\View::EVENT_END_BODY, [\yii\debug\Module::getInstance(), 'renderToolbar']);
} ?>
<?php $this->endBody() ?>
</body>

<script src="https://cdn.jsdelivr.net/picturefill/2.3.1/picturefill.min.js"></script>
<script src="js/lightgallery-all.min.js"></script>
<script src="js/jquery.mousewheel.min.js"></script>


</html>
<?php $this->endPage() ?>
