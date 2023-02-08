<?= $this->render('layouts/mail_header') ?>
<?php
    // $hiddenMobile = str_repeat("*", strlen(substr($data['mobile'], 0, 6))).substr($data['mobile'], 6, 4);
    switch($data['fk_airline_id']){
        case 1:
            $url = Yii::$app->params['indigo_url'];
            break;
        case 2:
            $url = Yii::$app->params['airindia_url'];
            break;
        case 3:
            $url = Yii::$app->params['spicejet_url'];
            break;
        case 5:
            $url = Yii::$app->params['vistara_url'];
            break;
        case 6:
            $url = Yii::$app->params['airasia_url'];
            break;
        case 9:
            $url = Yii::$app->params['akasa_url'];
            break;
        case 42:
            $url = Yii::$app->params['carterx_url'];
        
        Default:
            $url = "www.carterx.in";
    }

?>
<!-- <div style=" padding: 12px; background: white;border: 1px solid;border-color: #d8d6d6;margin-top:15px;font-family:sans-serif;font-size:14px;line-height:20px;">
    
</div> -->
<div style="padding: 12px;background: white;border: 1px solid;border-color: #d8d6d6;margin: 8px 0px;font-family:sans-serif;font-size:14px;line-height:20px;">
    <div>Dear <b><?= ucwords($data['name']); ?></b>
    </div>
    <div>
        <p>
            Welcome to <b>CarterX!</b> Your Corporate ID is <b>"<?= $data['customerId']; ?>"</b>. Login to <?= $url; ?> with your registered Corporate ID and OTP will be sent to your <b>mobile number</b> registered with us. This feature will be mandatory for all users with a CarterX account who use a password to log in.
        </p>
        <p>
            <b>The details of the registration are:</b>
            <table>
                <tr>
                    <th style="text-align:left;">Corporate ID</th><td><?= ": ".$data['customerId']; ?></td>
                </tr><tr>
                    <th style="text-align:left;">Name</th><td><?= ": ".ucwords($data['name']); ?></td>
                </tr><tr>
                    <th style="text-align:left;">Mobile Number</th><td><?= ": ".$data['mobile']; ?></td>
                </tr><tr>
                    <th style="text-align:left;">Email Address</th><td><?= ": ".strtolower($data['email']); ?></td>
                </tr><tr>
                    <th style="text-align:left;">GST No</th><td><?= ": ".$data['gst_number']; ?></td>
                </tr><tr>
                    <th style="text-align:left;">Tour Id</th><td><?= ": ".$data['tour_id']; ?></td>
                </tr>
            </table>
        </p>
        <p>
            Thank you for choosing us! <?= $url; ?>
        </p>
    </div>
</div>
<?= $this->render('layouts/mail_footer') ?>