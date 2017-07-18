<?php

namespace Authentication\SAML;

use CCR\MailWrapper;
use \Exception;

class XDSamlAuthentication
{
    protected $_as = null;

    protected $_sources = null;
    protected $_samlConfig = null;
    protected $_isConfigured = null;
    protected $_allowLocalAccessViaFederation = true;

    public function __construct()
    {
        $this->_sources = \SimpleSAML_Auth_Source::getSources();
        try {
            $this->_allowLocalAccessViaFederation = strtolower(\xd_utilities\getConfiguration('authentication', 'allowLocalAccessViaFederation')) === "false" ? false: true;
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

    public function isSamlConfigured()
    {
        //TODO: Make this more robust by taking into account if the IDP MetaData does not exist.
        // look at getLoginLink for details on getting idp info
        if (is_null($this->_isConfigured)) {
            $this->_isConfigured = count($this->_sources) > 0 ? true : false;
        }
        return $this->_isConfigured;
    }

    public function getXdmodAccount()
    {
        $samlAttrs = $this->_as->getAttributes();
        if (!isset($samlAttrs["username"])) {
            $thisUserName = null;
        } else {
            $thisUserName = !empty($samlAttrs['username'][0]) ? $samlAttrs['username'][0] : null;  # code...
        }
        if ($this->_as->isAUthenticated() && !empty($thisUserName)) {
            $xdmodUserId = \XDUser::userExistsWithUsername($thisUserName);
            if ($xdmodUserId !== INVALID) {
                return \XDUser::getUserByID($xdmodUserId);
            } elseif ($this->_allowLocalAccessViaFederation && isset($samlAttrs['email_address'])) {
                $xdmodUserId = \XDUser::userExistsWithEmailAddress($samlAttrs['email_address'][0]);
                if ($xdmodUserId === AMBIGUOUS) {
                    return "AMBIGUOUS";
                }
                if ($xdmodUserId !== INVALID) {
                    return \XDUser::getUserByID($xdmodUserId);
                }
            }
            $emailAddress = !empty($samlAttrs['email_address'][0]) ? $samlAttrs['email_address'][0] : NO_EMAIL_ADDRESS_SET;
            $personId = \DataWarehouse::getPersonIdByUsername($thisUserName);
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
                    null,
                    $personId
                );
            } catch (Exception $e) {
                return "EXISTS";
            }
      
            $newUser->setUserType(FEDERATED_USER_TYPE);
            try {
                $newUser->saveUser();
            } catch(Exception $e) {
                error_log('User creation failed: ' . $e->getMessage());
                return false;
            }
            try {
                self::notifyAdminOfNewUser($newUser, $samlAttrs, ($personId != -2));
                return $newUser;
            } catch (Exception $e) {
                error_log("Error notifying " . $newUser->getEmailAddress() . ": " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

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
                    'en' => 'Federation'
                );
            }
            return array(
                    'url' => $this->_as->getLoginUrl(),
                    'organization' => $orgDisplay,
                    'icon' => $icon
                );
        } else {
            return false;
        }
    }
    private function notifyAdminOfNewUser($user, $samlAttributes, $linked, $error = false)
    {
        $userEmail = $user->getEmailAddress();

        $body = "The following person has had an account created on XDMoD:\n\n" .
        "Person Details ----------------------------------\n\n" .
        "\nName:                     " . $user->getFormalName(true) .
        "\nUserame:                  " . $user->getUsername() .
        "\nE-Mail:                   " . $userEmail ;

        if(count($samlAttributes) != 0) {
            $body = $body . "\n\n" .
                "Additional SAML Attributes ----------------------------------\n\n" .
                print_r($samlAttributes, true);
        }

        if ($error) {
            $subject = "[xdmod] Error Creating federated user";
        } else {
            $subject = "[xdmod] New " . ($linked ? "linked": "unlinked") . " federated user created";
        }

        $properties = array(
            'body'=>$body,
            'subject'=>$subject,
            'fromAddress'=>$userEmail,
            'fromName'=>$user->getFormalName()
        );

        if ($userEmail != NO_EMAIL_ADDRESS_SET) {
            $properties['ifReplyAddress'] = \xd_utilities\getConfiguration('mailer', 'sender_email');
        }

        MailWrapper::sendMail($properties);
    }
}
