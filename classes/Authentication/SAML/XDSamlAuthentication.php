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

    const BASE_ADMIN_EMAIL = <<<EML

Person Details -----------------------------------
Name:              %s
Username:          %s
E-Mail:            %s

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
        if ($this->isSamlConfigured()) {
            try {
                $authSource = \xd_utilities\getConfiguration('authentication', 'source');
            } catch (Exception $e) {
                $authSource = null;
            }
            if (!is_null($authSource) && array_search($authSource, $this->_sources) !== false) {
                $this->_as = new \SimpleSAML\Auth\Simple($authSource);
            } else {
                $this->_as = new \SimpleSAML\Auth\Simple($this->_sources[0]);
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

        if ($this->_as->isAuthenticated()) {
            $userName = $samlAttrs['username'][0];

            $xdmodUserId = \XDUser::userExistsWithUsername($userName);

            if ($xdmodUserId !== INVALID) {
                $user = \XDUser::getUserByID($xdmodUserId);
                $user->setSSOAttrs($samlAttrs);
                return $user;
            }

            // If we've gotten this far then we're creating a new user. Proceed with gathering the
            // information we'll need to do so.
            $emailAddress = isset($samlAttrs['email_address']) ? $samlAttrs['email_address'][0] : NO_EMAIL_ADDRESS_SET;
            $systemUserName = isset($samlAttrs['system_username']) ? $samlAttrs['system_username'][0] : $userName;
            $firstName = isset($samlAttrs['first_name']) ? $samlAttrs['first_name'][0] : 'UNKNOWN';
            $middleName = isset($samlAttrs['middle_name']) ? $samlAttrs['middle_name'][0] : null;
            $lastName = isset($samlAttrs['last_name']) ? $samlAttrs['last_name'][0] : null;
            $personId = \DataWarehouse::getPersonIdFromPII($systemUserName, $samlAttrs['organization'][0]);

            // Attempt to identify which organization this user should be associated with. Prefer
            // using the personId if not unknown, then fall back to the saml attributes if the
            // 'organization' property is present, and finally defaulting to the Unknown organization
            // if none of the preceding conditions are met.
            $userOrganization = $this->getOrganizationId($samlAttrs, $personId);

            try {
                $newUser = new \XDUser(
                    $userName,
                    null,
                    $emailAddress,
                    $firstName,
                    $middleName,
                    $lastName,
                    array(ROLE_ID_USER),
                    ROLE_ID_USER,
                    $userOrganization,
                    $personId,
                    $samlAttrs
                );
            } catch (Exception $e) {
                throw new Exception('An account is currently configured with this information, please contact an administrator.');
            }

            $newUser->setUserType(SSO_USER_TYPE);

            try {
                $newUser->saveUser();
            } catch (Exception $e) {
                $this->logger->err('User creation failed: ' . $e->getMessage());
                throw $e;
            }

            $this->handleNotifications($newUser, $samlAttrs, ($personId != UNKNOWN_USER_TYPE));

            return $newUser;
        }
        throw new \DataWarehouse\Query\Exceptions\AccessDeniedException('Authentication failure.');
    }

    /**
     * Retrieves the organization_id that this User should be associated with.
     *
     * - If we were able to identify which `person` this user should be associated with
     *   - then look up which organization they are associated via the `modw.person.id` column.
     * - If SAML has been provided with an `organization` property.
     *   - Then the users organization will be determined by attempting to find a record in
     *     the `modw.organization` table that has a `name` column that matches the provided
     *     value. If no records are found then the 'Unknown' organization ( -1 ) is returned.
     * - and finally, if none of the other conditions are satisfied, return the Unknown organization
     *   ( i.e. -1 )
     *
     * @param array $samlAttrs the saml attributes returned for this user
     * @param int   $personId  the id property for the Person this user has been associated with.
     * @return int the id of the organization that a new SSO user should be associated with.
     * @throws Exception if there is a problem retrieving an organization_id
     */
    public function getOrganizationId($samlAttrs, $personId)
    {
        if ($personId !== -1 ) {
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
     */
    private function handleNotifications(XDUser $user, $samlAttributes, $linked)
    {
        if ($user->getOrganizationID() !== -1) {
            // Only send email if we were unable to identify an organization for the user.
            return;
        }

        $subject = sprintf(
            'New %s Single Sign On User Created',
            ($linked ? 'Linked' : 'Unlinked')
        );

        $body = sprintf(
            self::BASE_ADMIN_EMAIL,
            $user->getFormalName(true),
            $user->getUsername(),
            $user->getEmailAddress(),
            json_encode($samlAttributes, JSON_PRETTY_PRINT)
        );

        try {
            MailWrapper::sendMail(
                array(
                    'subject' => $subject,
                    'body' => $body,
                    'toAddress' => \xd_utilities\getConfiguration('general', 'tech_support_recipient')
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
