<?php

namespace Authentication\SAML;

use CCR\MailWrapper;
use \Exception;
use CCR\Log;
use Models\Services\Organizations;

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

    private $logger = null;

    private $emailLogger = null;

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
        $this->emailLogger = Log::factory(
            'XDSamlAuthentication-email',
            array(
                'file' => false,
                'db' => false,
                'console' => false,
                'mail' => true,
                'emailTo' => \xd_utilities\getConfiguration('general', 'tech_support_recipient'),
                'emailFrom' => \xd_utilities\getConfiguration('mailer', 'sender_email'),
                'emailSubject' => 'XDMoD SSO: Additional Actions Necessary'
            )
        );
        $this->_sources = \SimpleSAML_Auth_Source::getSources();
        try {
            $this->_allowLocalAccessViaSSO = strtolower(\xd_utilities\getConfiguration('authentication', 'allowLocalAccessViaFederation')) === "false" ? false: true;
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

        $this->_defaultOrganizationName = \xd_utilities\getConfiguration('sso', 'default_organization_name');
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

            $samlOrganization = $samlAttrs['organization'];
            if ($this->_forceDefaultOrganization) {
                $userOrganization = Organizations::getIdByName($this->_defaultOrganizationName);
            } elseif (!empty($samlOrganization)) {
                $userOrganization = Organizations::getIdByName($samlOrganization[0]);
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
            self::notifyAdminOfUserWithUnknownOrganization($newUser, $samlAttrs, ($personId != UNKNOWN_USER_TYPE));
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
     * Sends an email notifying XDMoD admin of new account.
     *
     * @param \XDUser $user The newly minted XDMoD user
     * @param array $samlAttributes SAML attributes associated with this user
     * @param boolean $linked whether Single Sign On user is linked to this account
     * @param boolean $error whether or not we had issues creating Single Sign On user
     * @throws Exception
     */
    private function notifyAdminOfUserWithUnknownOrganization($user, $samlAttributes, $linked, $error = false)
    {
        $body =
        "\n\nPerson Details ----------------------------------\n\n" .
        "\nName:                     " . $user->getFormalName(true) .
        "\nUsername:                 " . $user->getUsername() .
        "\nE-Mail:                   " . $user->getEmailAddress() .
        "\nOrganization:             " . $user->getOrganizationId();

        $userOrganization = $user->getOrganizationId();

        // If userOrganization is null -or- if it's the UNKNOWN organization
        // -and-
        // we have an organization_id provided by the samlAttributes
        // -then-
        // this is an error case and we need to notify the admins that there will
        // be additional setup required.
        $unableToFindOrganization =
            (!isset($userOrganization) || $userOrganization === -1) &&
            (isset($samlAttributes['organization_id']) || isset($samlAttributes['organization']));

        if ($unableToFindOrganization) {
            $body .=
                "\n\nNOTES: -----------------------------\n\n" .
                "Unable to identify Users Organization. Additional Setup Required\n";

        }

        if (count($samlAttributes) != 0) {
            $body = $body . "\n\n" .
                "Additional SAML Attributes ----------------------------------\n\n" .
                json_encode($samlAttributes, JSON_PRETTY_PRINT);
        }
        if ($error) {
            $this->logger->err("Error Creating Single Sign On user" . $body);
        } else {
            $this->logger->notice("New " . ($linked ? "linked": "unlinked") . " Single Sign On user created" . $body);
        }

        if ($unableToFindOrganization) {
            if (\xd_utilities\getConfiguration('sso', 'email_admin_sso_unknown_org') === 'on') {
                $this->emailLogger->notice("Additional SSO User Action Required". $body);
            }

            $emailAddress = $user->getEmailAddress()
                ? $user->getEmailAddress()
                : $samlAttributes['email_address'][0];

            if (!isset($emailAddress)) {
                $this->logger->err("Unable to determine email address for new SSO User." . $body);
                return;
            }

            $this->notifyUserOfUnknownOrganization($emailAddress);
        }
    }

    public function notifyUserOfUnknownOrganization($emailAddress)
    {
        try {

            MailWrapper::sendMail(
                array(
                    'subject' => 'XDMoD SSO User: Additional Actions Required',
                    'body' => "Greetings,\n\nThis email is notify you that XDMoD was unable to determine which organization to associate you with. Administrative Users have been notified that additional setup will be required. You may not have full access until this additional setup is complete.\n\nThank you,\n\nXDMoD SSO User Creation",
                    'toAddress' => $emailAddress,
                    'fromAddress' => \xd_utilities\getConfiguration('general', 'tech_support_recipient'),
                    'fromName' => '',
                    'replyAddress' => \xd_utilities\getConfiguration('mailer', 'sender_email')
                )
            );
        } catch (Exception $e) {
            $this->logger->err("There was an error sending a notification email to new SSO User: $emailAddress");
        }
    }
}
