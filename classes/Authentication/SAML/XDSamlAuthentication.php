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
            $personId = \DataWarehouse::getPersonIdFromPII($thisSystemUserName, $samlAttrs['organization']);

            $userOrganization = $this->getOrganizationId($samlAttrs, $personId);

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
     * Retrieves the organization_id that this User should be associated with. There is one
     * configuration property that affects the return value of this function,
     * `force_default_organization`. It does so in the following ways:
     *
     * - If the `force_default_organization` property in `portal_settings.ini` === 'on'
     *   - Then the users organization is determined by the run time constant
     *     `ORGANIZATION_NAME` ( which is derived from the `organization.json` configuration
     *     file. ) This value will be used to find a corresponding record in the
     *     `modw.organization` table via the `name` column.
     * - If SAML has been provided with an `organization` property.
     *   - Then the users organization will be determined by attempting to find a record in
     *     the `modw.organization` table that has a `name` column that matches the provided
     *     value.
     *   - If unable to identify an organization in the previous step and a personId has been
     *     supplied, attempt to retrieve the organization_id for this person via the
     *     `modw.person.id` column.
     * - If we were able to identify which `person` this user should be associated with
     *   - then look up which organization they are associated via the `modw.person.id` column.
     * - and finally, if none of the other conditions are satisfied, return the Unknown organization
     *   ( i.e. -1 )
     *
     * The default setting for an OpenXDMoD installation is:
     *   - `force_default_organization="on"`
     * @param array $samlAttrs the saml attributes returned for this user
     * @param int   $personId  the id property for the Person this user has been associated with.
     * @return int the id of the organization that a new SSO user should be associated with.
     * @throws Exception if there is a problem retrieving an organization_id
     */
    public function getOrganizationId($samlAttrs, $personId)
    {
        $techSupportRecipient = \xd_utilities\getConfiguration('general', 'tech_support_recipient');

        $forceDefaultOrganization = null;
        try {
            $forceDefaultOrganization = \xd_utilities\getConfiguration('sso', 'force_default_organization') === 'on';
        } catch (Exception $e) {
            $this->notify(
                $techSupportRecipient,
                $techSupportRecipient,
                '',
                $techSupportRecipient,
                'Error retrieving XDMoD configuration property "force_default_organization" form portal_settings.ini',
                $e->getMessage()
            );
        }

        if ($forceDefaultOrganization) {
            return Organizations::getIdByName(ORGANIZATION_NAME);
        } elseif ($personId !== -1 ) {
            return Organizations::getOrganizationIdForPerson($personId);
        } elseif(!empty($samlAttrs['organization'])) {
            return Organizations::getIdByName($samlAttrs['organization'][0]);
        }
        return -1;
    }

    /**
     * Retrieves the login url we want to use with this authentication provider.
     *
     * @param string $returnTo the URI to redirect to after auth.
     *
     * @return the login URL or false if no provider is configured
     */
    public function getLoginURL($returnTo)
    {
        if ($this->_as) {
            return $this->_as->getLoginURL($returnTo);
        }
        return false;
    }

    /**
     * Retrieves display information for the SSO login button.
     *
     * @return mixed An array containing the name of the organization (eg. Twitter),
     * and an icon (eg. A logo with the Twitter icon + 'Sign in with Twitter' ). false if none found.
     */
    public function getLoginLink()
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
        // We only notify admins iff we were unable to identify which organization the user should
        // be associated with ( i.e. we had to assign them to the 'Unknown' organization ).
        if ($user->getOrganizationID() === -1) {
            $techSupportRecipient = \xd_utilities\getConfiguration('general', 'tech_support_recipient');
            $senderEmail = \xd_utilities\getConfiguration('mailer', 'sender_email');

            $userEmail = $user->getEmailAddress()
                ? $user->getEmailAddress()
                : $samlAttributes['email_address'][0];

            $userOrganization = $user->getOrganizationID();

            $emailBody = sprintf(
                self::BASE_ADMIN_EMAIL,
                $user->getFormalName(true),
                $user->getUsername(),
                $userEmail,
                $userOrganization,
                Organizations::getNameById($userOrganization),
                json_encode($samlAttributes, JSON_PRETTY_PRINT)
            );

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
