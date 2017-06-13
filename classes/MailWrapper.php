<?php

class MailWrapper
{

    public static function init()
    {
	$mail = new PHPMailer();
	$mail->isSendMail();
	$mail->Sender = 'fxdmod-bounces@ccr.buffalo.edu';

        return $mail;
    }
}

