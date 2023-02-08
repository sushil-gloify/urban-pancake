<?= $this->render('layouts/mail_header') ?>
    <?php
        $body_title = "Ticket Number #".$data['ticket_number'];
        $customer_name = $data['user_name'];
    ?>
    <div style=" padding: 12px; background: white;border: 1px solid;border-color: #d8d6d6;margin-top:15px;font-family:sans-serif;font-size:14px;line-height:20px;">
        <div style="font-weight: bold;"> 
            <?= $body_title?>
        </div>
    </div>
    <div style="padding: 12px;background: white;border: 1px solid;border-color: #d8d6d6;margin: 8px 0px;font-family:sans-serif;font-size:14px;line-height:20px;">
        <div>Dear <b><?= $customer_name;?></b> </div>
        <div>
            <p>We are confirming that we received a new ticket from you with the concern with <b><?= $data['topic_name'];?></b>
                Your New Ticket number is <b><?= $data['ticket_number'];?></b>  for your order number # <b><?= $data['order_number'];?></b>.
            </p>
            <p>Ticket Number : <b><?= $data['ticket_number'];?></b> </p>
            <p>Ticket Concern : <b><?= $data['topic_name'];?></b> </p>

            <p>Ticket Message : <?= $data['concern'];?></p>
            <p>We try to reply to all tickets as soon as possible, you can view the status of your ticket here: <a href="www.carterx.in">www.carterx.in</a></p>
            <p>You will receive an e-mail notification when our staff replies  to your tickets.</p>


        <p>Thankyou for choosing us</p>
        <p><a href="www.carterx.in">www.carterx.in</a></p>
        </div>
    </div>
<?= $this->render('layouts/mail_footer') ?>