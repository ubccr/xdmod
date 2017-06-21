<?php

namespace CCR;

class MailWrapper
{

    public static function initPHPMailer($fromEmail = null, $fromName = '')
    {
        $mail = new \PHPMailer(true);
        $mail->isSendMail();
        $address = \xd_utilities\getConfiguration('mailer', 'sender_email');
        $name = \xd_utilities\getConfiguration('general', 'title');
        $mail->Sender = $address;

        if($fromEmail !== null) {
            $address = $fromEmail;
            $name = $fromName;
        }

        try {
            $mail->setFrom($address, $name);
        } catch(phpmailerException $e) {
            error_log($e->getMessage);
            return 'From address is invalid. ' . $e->getMessage;
        }

        return $mail;
    }
}
