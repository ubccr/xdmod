<?php

namespace CCR;

use Xdmod\EmailTemplate;

class MailWrapper
{

    public static function initPHPMailer($properties)
    {
        $mail = new \PHPMailer(true);
        $mail->isSendMail();
        $address = \xd_utilities\getConfiguration('mailer', 'sender_email');
        $mail->Sender = $address;
        $mail->addCustomHeader('Sender', $address);
        $mail->Body = $properties['body'];

        if(!empty($properties['subject'])) {
            $prefix = '';
            try {
                $prefix = \xd_utilities\getConfiguration('mailer', 'subject_prefix');
                if(!empty($prefix)){
                    $prefix = $prefix . ': ';
                }
            }
            catch(\Exception $e){
                // Do nothing, the configuration option
                // does not exist;
            }
            $mail->Subject = $prefix . $properties['subject'];
        } else {
            throw new \Exception('There is no subject');
        }

        MailWrapper::addAddresses($mail, $properties);

        if(!empty($properties['fromAddress'])) {
            $address = $properties['fromAddress'];
            $name = $properties['fromName'];
        } else {
            $name = \xd_utilities\getConfiguration('general', 'title');
        }

        if(!empty($properties['replyAddress'])) {
            $mail->addReplyTo($properties['replyAddress'], $name);
        }

        if(!empty($properties['attachment'])) {
            foreach($properties['attachment'] as $entry) {
                $mail->addAttachment($entry['fileName'], $entry['attachment_file_name'], $entry['encoding'], $entry['type'], $entry['disposition']);
            }
        }

        if(!empty($properties['ishtml'])) {
            $mail->isHTML($properties['ishtml']);
        }

        try {
            $mail->setFrom($address, $name);
        } catch(phpmailerException $e) {
            error_log($e->getMessage());
            throw $e;
        }

        return $mail;
    }

    /**
     * Composes an email, then sends it and checks if email did/didn't send
     *
     * Throws Exception if send() returns false
     */
    public static function sendMail($properties) {
        $mail = MailWrapper::initPHPMailer($properties);
        if(!$mail->send()){
            throw new \Exception($mail->ErrorInfo);
        }
    }

    /**
     * Get the maintainer's signature to use for emails.
     *
     * @return string The maintainer's signature if one has been configured.
     *                Otherwise, the organization will be used.
     */
    public static function getMaintainerSignature()
    {
        $signature = ORGANIZATION_NAME;
        try {
            $configSignature = \xd_utilities\getConfiguration('general', 'maintainer_email_signature');
            if (!empty($configSignature)) {
                $signature = $configSignature;
            }
        } catch (Exception $e) {
        }
        return $signature;
    }

    /**
     * Get the product name for this instance of XDMoD.
     *
     * This refers to the name of this software. For the name of this instance
     * of this software, see 'title' in config file.
     *
     * @return string The product name for this instance of XDMoD.
     */
    public static function getProductName()
    {
        $name = \xd_utilities\getConfiguration('general', 'title');
        try {
            if (\xd_utilities\getConfiguration('features', 'xsede') == 'on') {
                $name = 'XDMoD';
            }
        } catch (Exception $e) {
        }
        return $name;
    }

    /**
     * Adds addresses to which mail is being sent given as either a string or an array
     *
     * Checks first to see if debug mode is on
     */
    public function addAddresses($mail, $properties)
    {
        if(\xd_utilities\getConfiguration('general', 'debug_mode') == 'on') {
            $mail->addAddress(\xd_utilities\getConfiguration('general', 'debug_recipient'));
        } else {
            if(is_string($properties['toAddress'])) {
                if(!empty($properties['toName'])) {
                    $mail->addAddress($properties['toAddress'], $properties['toName']);
                } else {
                    $mail->addAddress($properties['toAddress']);
                }
            } elseif(is_array($properties['toAddress'])) {
                foreach($properties['toAddress'] as $entry) {
                    if(!empty($entry['name'])) {
                        $mail->addAddress($entry['address'], $entry['name']);
                    } else {
                        $mail->addAddress($entry['address']);
                    }
                }
            }
        }

        if(!empty($properties['bcc'])) {
            $bcc_emails = explode(',', $properties['bcc']);
            foreach($bcc_emails as $b) {
                $mail->addBCC($b);
            }
        }
    }

    public function sendTemplate($templateType, $properties)
    {
        $template = new EmailTemplate($templateType);
        $template->apply($properties);

        $properties['body'] = $template->getContents();
        MailWrapper::sendMail($properties);
    }
}
