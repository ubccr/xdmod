<?php

use CCR;

class MailWrapper
{

    public static function initPHPMailer($sender)
    {
        $mail = new PHPMailer(true);
        $mail->isSendMail();
        $mail->Sender = $sender;

        return $mail;
    }
}
