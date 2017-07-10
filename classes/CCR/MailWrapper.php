<?php

namespace CCR;

use Xdmod\EmailTemplate;

class MailWrapper
{

    public static function initPHPMailer($properties)
    {
        $mail = new \PHPMailer(true);
        $mail->isSendMail();
        $address =  \xd_utilities\getConfiguration('mailer', 'sender_email');
        $mail->Sender = $address;
        $mail->Body = $properties['body'];
        $mail->Subject = $properties['subject'];

        for($i = 0; $i < count($properties['toAddress']); $i += 1) {
            $mail->addAddress($properties['toAddress'][$i]['address']);
        }

        if(!empty($properties['fromAddress'])) {
            $address = $properties['fromAddress'];
            $name = $properties['fromName'];
        } else {
            $name = \xd_utilities\getConfiguration('general', 'title');
        }

        if(!empty($properties['ifReplyAddress'])) {
            $mail->addReplyTo($address, $name);
        }

        if(!empty($properties['bcc'])) {
            $target_addresses = \xd_security\assertParameterSet('target_addresses');
            $bcc_emails = explode(',', $target_addresses);
            foreach($bcc_emails as $b) {
                $mail->addBCC($b);
            }
        }

        if(!empty($properties['attachment'])) {
            for($i = 0; $i < count($properties['attachment']); $i += 1) {
                $mail->addAttachment($properties['attachment'][$i]['fileName'], $properties['attachment'][$i]['attachment_file_name'], $properties['attachment'][$i]['encoding'], $properties['attachment'][$i]['type'], $properties['attachment'][$i]['disposition']);
            }
        }

        try {
            $mail->setFrom($address, $name);
        } catch(phpmailerException $e) {
            error_log($e->getMessage());
            throw $e;
        }

        return $mail;
    }

    public function sendMail($properties) {
        $mail = MailWrapper::initPHPMailer($properties);
        if($mail->send()) {
            return true;
        } else {
            return false;
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
     * of this software, see getSiteTitle.
     *
     * @return string The product name for this instance of XDMoD.
     */
    public static function getProductName()
    {
        $name = 'Open XDMoD';
        try {
            if (\xd_utilities\getConfiguration('features', 'xsede') == 'on') {
                $name = 'XDMoD';
            }
        } catch (Exception $e) {
        }
        return $name;
    }

    /**
     * Get the name of this instance of XDMoD.
     *
     * This refers to the title of this instance of XDMoD. For the name of this
     * software product, see getProductName.
     *
     * @return string The custom title of this site if configured.
     *                Otherwise, "Open XDMoD".
     */
    public static function getSiteTitle()
    {
        $title = 'Open XDMoD';
        try {
            $title = \xd_utilities\getConfiguration('general', 'title');
        } catch (Exception $e) {
        }
        return $title;
    }

    public function sendTemplate($templateType, $prop, $properties)
    {
        $template = new EmailTemplate($templateType);
        $template->apply($prop);
        $properties['body'] = MailWrapper::decideTemplate($templateType, $template);
        MailWrapper::sendmail($properties);
    }

    public function decideTemplate($templateType, $templates)
    {
        if($templateType === 'password_reset') {
            return $templates->getContents();

        /**
-       * Gets used by reports built and sent via the Report Generator, as
-       * well as those reports built and sent via the report scheduler
-       */
        } elseif($templateType === 'custom_report') {
            return $templates->getContents();

        /**
-       * Gets used by reports built and sent via XDComplianceReport
-       */
        } elseif($templateType === 'compliance_report') {
            return $templates->getContents();
        }
    }
}
