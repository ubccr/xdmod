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
        $mail->addAddress($properties['toAddress']);

        if($properties['fromAddress'] !== null) {
            $address = $properties['fromAddress'];
            $name = $properties['fromName'];
        } else {
            $name = \xd_utilities\getConfiguration('general', 'title');
        }

        if($properties['ifReplyAddress'] === true) {
            $mail->addReplyTo($address, $name);
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

    public static function passwordReset($user)
    {
        $username = $user->getUsername();

        $rid = md5($username . $user->getPasswordLastUpdatedTimestamp());

        $site_address
            = \xd_utilities\getConfigurationUrlBase('general', 'site_address');

        $resetUrl = "${site_address}password_reset.php?rid=$rid";

        $template = new EmailTemplate('password_reset');

        $template->apply(array(
            'first_name'           => $user->getFirstName(),
            'username'             => $username,
            'reset_url'            => $resetUrl,
            'maintainer_signature' => static::getMaintainerSignature(),
        ));

        return $template->getContents();
    }

    /**
     * Gets used by reports built and sent via the Report Generator, as
     * well as those reports built and sent via the report scheduler
     */
    public static function customReport($recipient_name, $frequency = '')
    {
        $frequency = trim($frequency);

        $frequency
            = !empty($frequency)
            ? ' ' . $frequency
            : $frequency;

        $template = new EmailTemplate('custom_report');

        $template->apply(array(
            'recipient_name'       => $recipient_name,
            'frequency'            => $frequency,
            'site_title'           => static::getSiteTitle(),
            'maintainer_signature' => static::getMaintainerSignature(),
        ));

        return array(
            'subject' => 'XDMoD Report',
            'message' => $template->getContents(),
        );
    }

    /**
     * Gets used by reports built and sent via XDComplianceReport
     */
    public static function complianceReport(
        $recipient_name,
        $additional_information = ''
    ) {
        $template = new EmailTemplate('compliance_report');

        $template->apply(array(
            'recipient_name'         => $recipient_name,
            'additional_information' => $additional_information,
            'maintainer_signature'   => static::getMaintainerSignature(),
        ));

        return array(
            'subject' => 'XDMoD Compliance Report',
            'message' => $template->getContents(),
        );
    }
}
