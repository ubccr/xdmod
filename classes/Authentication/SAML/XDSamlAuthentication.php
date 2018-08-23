<?php

namespace Authentication\SAML;

use CCR\MailWrapper;
use \Exception;
use CCR\Log;
use Models\Services\Organizations;
use XDUser;

class XDSamlAuthentication
{
    /**
     * The selected auth source
     *
     * @var \SimpleSAML_Auth_Simple
     */
    protected $_as = null;

    /**
     * Enumerated potential auth sources
     *
     * @var array
     */
    protected $_sources = null;

    /**
     * Whether or not SAML is configured. Defaults to false.
     *
     * @var boolean
     */
    protected $_isConfigured = false;

    /**
     * Whether or not we allow Single Sign On users local access. Defaults to true.
     *
     * @var boolean
     */
    protected $_allowLocalAccessViaSSO = true;

    /**
     * The name of the organization to be used when `force_default_organization` is true.
     *
     * @var string
     */
    protected $_defaultOrganizationName;

    /**
     * Always use the organization identified by `default_organization_name` when assigning an
     * organization to a new SSO user.
     *
     * @var bool
     */
    protected $_forceDefaultOrganization;

    /**
     * Controls when the admins are notified when an organization cannot be identified for a new SSO
     * user.
     *
     * @var bool
     */
    protected $_emailAdminForUnknownUserOrganization;

    const BASE_ADMIN_EMAIL = <<<EML

Person Details -----------------------------------
Name:              %s
Username:          %s
E-Mail:            %s
Organization ID:   %s
Organization Name: %s

SAML Attributes ----------------------------------
%s

Notes: -------------------------------------------

Unable to Identify Users Organization.

Additional Setup Required.
EML;

    const USER_EMAIL_SUBJECT = 'XDMoD SSO User: Additional Actions Required';

    const USER_EMAIL_BODY = <<<EML

Greetings,

This email is notify you that XDMoD was unable to determine which organization to associate you with.
Administrative Users have been notified that additional setup will be required. You may not have full
access until this additional setup is complete.

Thank you,

XDMoD SSO User Creation
EML;

    private $logger = null;

    public function __construct()
    {
        $this->logger = Log::factory(
            'XDSamlAuthentication',
            array(
                'file' => false,
                'db' => true,
                'mail' => false,
                'console' => false
            )
        );

        $this->_sources = \SimpleSAML_Auth_Source::getSources();
        try {
            $this->_allowLocalAccessViaSSO = strtolower(\xd_utilities\getConfiguration('authentication', 'allowLocalAccessViaFederation')) === "false" ? false : true;
        } catch (Exception $e) {
        }
        if ($this->isSamlConfigured()) {
            try {
                $authSource = \xd_utilities\getConfiguration('authentication', 'source');
            } catch (Exception $e) {
                $authSource = null;
            }
            if (!is_null($authSource) && array_search($authSource, $this->_sources) !== false) {
                $this->_as = new \SimpleSAML_Auth_Simple($authSource);
            } else {
                $this->_as = new \SimpleSAML_Auth_Simple($this->_sources[0]);
            }
        }

        $this->_defaultOrganizationName = ORGANIZATION_NAME;
        $this->_forceDefaultOrganization = \xd_utilities\getConfiguration('sso', 'force_default_organization') === 'on';
        $this->_emailAdminForUnknownUserOrganization = \xd_utilities\getConfiguration('sso', 'email_admin_sso_unknown_org') === 'on';
    }

    /**
     * Tells us whether or not we have properly set up SAML authentication sources.
     *
     * @return boolean true if we have 1 or more auth sources. false otherwise
     */
    public function isSamlConfigured()
    {
        $this->_isConfigured = count($this->_sources) > 0 ? true : false;
        return $this->_isConfigured;
    }

    /**
     * Attempts to find a valid XDMoD user associated with the attributes we receive from SAML
     *
     * @return mixed a valid XDMoD user if we have one, false otherwise
     * @throws Exception
     */
    public function getXdmodAccount()
    {
        $samlAttrs = $this->_as->getAttributes();
        if (!isset($samlAttrs["username"])) {
            $thisUserName = null;
        } else {
            $thisUserName = !empty($samlAttrs['username'][0]) ? $samlAttrs['username'][0] : null;
        }
        if (!isset($samlAttrs["system_username"])) {
            $thisSystemUserName = $thisUserName;
        } else {
            $thisSystemUserName = !empty($samlAttrs['system_username'][0]) ? $samlAttrs['system_username'][0] : null;
        }
        if ($this->_as->isAuthenticated() && !empty($thisUserName)) {
            $xdmodUserId = \XDUser::userExistsWithUsername($thisUserName);
            if ($xdmodUserId !== INVALID) {
                return \XDUser::getUserByID($xdmodUserId);
            } elseif ($this->_allowLocalAccessViaSSO && isset($samlAttrs['email_address'])) {
                $xdmodUserId = \XDUser::userExistsWithEmailAddress($samlAttrs['email_address'][0]);
                if ($xdmodUserId === AMBIGUOUS) {
                    return "AMBIGUOUS";
                }
                if ($xdmodUserId !== INVALID) {
                    return \XDUser::getUserByID($xdmodUserId);
                }
            }
            $emailAddress = !empty($samlAttrs['email_address'][0]) ? $samlAttrs['email_address'][0] : NO_EMAIL_ADDRESS_SET;
            $personId = \DataWarehouse::getPersonIdByUsername($thisSystemUserName);

            /* - If the `force_default_organization` property in `portal_settings.ini` === 'on'
             *   - Then the users organization is determined by the run time constant
             *     `ORGANIZATION_NAME` ( which is derived from the `organization.json` configuration
             *     file. ) This value will be used to find a corresponding record in the
             *     `modw.organization` table via the `name` column.
             * - If SAML has been provided with an `organization` property.
             *   - Then the users organization will be determined by attempting to find a record in
             *     the `modw.organization` table that has a `name` column that matches the provided
             *     value.
             * - If we were able to identify which `person` this user should be associated with
             *   - then look up which organization they are associated with and associate their
             *     User record with it as well.
             * - and finally, if none of the other <> are satisfied, associate this user with the
             *   Unknown organization ( i.e. -1 )
             *
             * The default settings in an OpenXDMoD installation are:
             *   - `force_default_organization="on"`
             *   - `email_admin_sso_unknown_org="on"`
             */
            if ($this->_forceDefaultOrganization) {
                $userOrganization = Organizations::getIdByName($this->_defaultOrganizationName);
            } elseif (!empty($samlAttrs['organization'])) {
                $userOrganization = Organizations::getIdByName($samlAttrs['organization'][0]);
            } elseif ($personId !== -1) {
                $userOrganization = Organizations::getOrganizationIdForPerson($personId);
            } else {
                $userOrganization = -1;
            }

            if (!isset($samlAttrs["first_name"])) {
                $samlAttrs["first_name"] = array("UNKNOWN");
            }
            if (!isset($samlAttrs["middle_name"])) {
                $samlAttrs["middle_name"] = array(null);
            }
            if (!isset($samlAttrs["last_name"])) {
                $samlAttrs["last_name"] = array("UNKNOWN");
            }
            try {
                $newUser = new \XDUser(
                    $thisUserName,
                    null,
                    $emailAddress,
                    $samlAttrs["first_name"][0],
                    $samlAttrs["middle_name"][0],
                    $samlAttrs["last_name"][0],
                    array(ROLE_ID_USER),
                    ROLE_ID_USER,
                    $userOrganization,
                    $personId
                );
            } catch (Exception $e) {
                return "EXISTS";
            }
            $newUser->setUserType(SSO_USER_TYPE);
            try {
                $newUser->saveUser();
            } catch (Exception $e) {
                $this->logger->err('User creation failed: ' . $e->getMessage());
                return false;
            }

            $this->handleNotifications($newUser, $samlAttrs, ($personId != UNKNOWN_USER_TYPE));

            return $newUser;
        }
        return false;
    }

    /**
     * Retrieves the login url we want to use with this authentication provider.
     *
     * @param string $returnTo the URI to redirect to after auth. default is null.
     *
     * @return mixed An array containing a login link + redirect, the name of the organization (eg. Twitter),
     * and an icon (eg. A logo with the Twitter icon + 'Sign in with Twitter' ). false if none found.
     */
    public function getLoginLink($returnTo = null)
    {
        if ($this->isSamlConfigured()) {
            $idpAuth = \SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler()->getList();
            $orgDisplay = "";
            $icon = "";
            foreach ($idpAuth as $idp) {
                if (!empty($idp['OrganizationDisplayName'])) {
                    $orgDisplay = $idp['OrganizationDisplayName'];
                }
                if (!empty($idp['icon'])) {
                    $icon = $idp['icon'];
                }
            }
            if ($orgDisplay === "") {
                $orgDisplay = array(
                    'en' => 'Single Sign On'
                );
            }
            return array(
                'url' => $this->_as->getLoginUrl($returnTo),
                'organization' => $orgDisplay,
                'icon' => $icon
            );
        } else {
            return false;
        }
    }

    /**
     * Determine / handle any notifications that may be required as a part of this SSO User -> XDMoD
     * User operation.
     *
     * @param XDUser $user the XDMoD User instance to be used during notification.
     * @param array $samlAttributes the attributes that we received via SAML for this user.
     * @param boolean $linked whether or not we were able to link the SSO User with an XDMoD Person.
     * @throws Exception if there is a problem with notifying the user.
     * @throws Exception if there is a problem retrieving the name for the users organization.
     */
    private function handleNotifications(XDUser $user, $samlAttributes, $linked)
    {

        $userEmail = $user->getEmailAddress()
            ? $user->getEmailAddress()
            : $samlAttributes['email_address'][0];

        $userOrganization = $user->getOrganizationID();
        $organizationFound = (!isset($userOrganization) || $userOrganization === -1);

        $emailBody = sprintf(
            self::BASE_ADMIN_EMAIL,
            $user->getFormalName(true),
            $user->getUsername(),
            $userEmail,
            $userOrganization,
            Organizations::getNameById($userOrganization),
            json_encode($samlAttributes, JSON_PRETTY_PRINT)
        );

        $techSupportRecipient = \xd_utilities\getConfiguration('general', 'tech_support_recipient');
        $senderEmail = \xd_utilities\getConfiguration('mailer', 'sender_email');

        if (!$organizationFound && $this->_emailAdminForUnknownUserOrganization) {
            $subject = sprintf(
                'New %s Single Sign On User Created',
                ($linked ? 'Linked' : 'Unlinked')
            );

            $this->notify(
                $techSupportRecipient,
                $techSupportRecipient,
                '',
                $senderEmail,
                $subject,
                $emailBody
            );
        } elseif (!$organizationFound && !empty($userEmail)) {
            $this->notify(
                $userEmail,
                $techSupportRecipient,
                '',
                $senderEmail,
                self::USER_EMAIL_SUBJECT,
                self::USER_EMAIL_BODY
            );
        } elseif (empty($userEmail)) {
            $title = 'Unable to determine email address for new SSO User.';
            $this->logger->err(sprintf("%s\n\n%s", $title, $emailBody));
        }
    }

    /**
     * Attempt to generate / send a notification email with the specified parameters.
     *
     * @param string $toAddress    the address that the notification will be sent to.
     * @param string $fromAddress  the address that the notification will be sent from.
     * @param string $fromName     A name to be used when identifying the `$fromAddress`
     * @param string $replyAddress the address to be used when a user replies to the notification.
     * @param string $subject      the subject of the notification.
     * @param string $body         the body of the notification.
     *
     * @throws Exception if there is a problem sending the notification email.
     */
    public function notify($toAddress, $fromAddress, $fromName, $replyAddress, $subject, $body)
    {
        try {
            MailWrapper::sendMail(
                array(
                    'subject' => $subject,
                    'body' => $body,
                    'toAddress' => $toAddress,
                    'fromAddress' => $fromAddress,
                    'fromName' => $fromName,
                    'replyAddress' => $replyAddress
                )
            );
        } catch (Exception $e) {
            // log the exception so we have some persistent visibility into the problem.
            $errorMsgFormat = "[%s] %s\n%s";
            $errorMsg = sprintf(
                $errorMsgFormat,
                $e->getCode(),
                $e->getMessage(),
                $e->getTraceAsString()
            );

            $this->logger->err("Error encountered while emailing\n$errorMsg");

            throw $e;
        }
    }
}
