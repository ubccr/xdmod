<?php

namespace CCR;

class MailWrapper
{

    public static function initPHPMailer($fromEmail = null, $fromName = null)
    {
        $mail = new \PHPMailer(true);
        $mail->isSendMail();
        $address =  \xd_utilities\getConfiguration('mailer', 'sender_email');
        $mail->Sender = $address;

        if($fromEmail !== null) {
            $address = $fromEmail;
            $name = $fromName;
        } else {
            $name = \xd_utilities\getConfiguration('general', 'title');
        }

        try {
            $mail->setFrom($address, $name);
        } catch(phpmailerException $e) {
            error_log($e->getMessage());
            throw $e;
        }

        return $mail;
    }
}
