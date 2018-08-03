<?php

namespace Rest\Controllers;

use CCR\Log;
use CCR\MailWrapper;
use Models\Services\Acls;
use Models\Services\Organizations;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use Rest\Utilities\Authentication;
use XDUser;

/**
 * Class AuthenticationControllerProvider
 *
 * This class is responsible for maintaining the authentication routes for the
 * REST stack.
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
class AuthenticationControllerProvider extends BaseControllerProvider
{
    const ADMIN_NOTIFICATION_EMAIL = <<<EML

User Organization Update --------------------------------
Name:             %s
Username:         %s
E-Mail:           %s
Old Organization: %s
New Organization: %s
EML;

    const ACL_EMAIL_ADDITION = <<<EML
Old Acls:         %s
New Acls:         %s
EML;


    const USER_NOTIFICATION_EMAIL = <<<EML

Greetings,

This email is to notify you that XDMoD has detected a change in your organization affiliation. We
have taken steps to ensure that this is accurately reflected in our systems. If you have any questions
or concerns please contact us @ %s.

Thank You,

XDMoD
EML;

    const EMAIL_EXCEPTION_MSG = <<<TXT

There was an unexpected error while attempting to send an email.

To:               %s
Old Organization: %s
New Organization: %s
Original Acls:    %s
New Acls:         %s

Exception:
  Code:           %s
  Message:        %s
  Stack Trace:
    %s
TXT;


    private static $CENTER_ROLES = array('cd', 'cs');

    private $emailLogger;

    private $fileLogger;

    /**
     * AuthenticationControllerProvider constructor.
     *
     * @param array $params
     *
     * @throws \Exception if there is a problem retrieving email addresses from configuration files.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);

        $this->emailLogger = Log::factory(
            'Authentication-email',
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

        $this->fileLogger = Log::factory(
            'Authentication-exceptions-file',
            array(
                'file' => LOG_DIR . '/authentication-exceptions.log',
                'db' => false,
                'console' => false,
                'mail' => false
            )
        );
    }


    /**
     * @see aBaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        $root = $this->prefix;
        $controller->post("$root/login", '\Rest\Controllers\AuthenticationControllerProvider::login');
        $controller->post("$root/logout", '\Rest\Controllers\AuthenticationControllerProvider::logout');
    }

    /**
     * Provide the user with an authentication token.
     *
     * The authentication check has already occurred in middleware when this
     * function is called, so it does not perform any authentication work.
     *
     * @param Request $request that will be used to retrieve the user
     * @param Application $app used to facilitate json encoding the response.
     * @return \Symfony\Component\HttpFoundation\JsonResponse which contains a
     *                         token and the users full name if the login
     *                         attempt is successful.
     * @throws \Exception if the user could not be found or if their account
     *                   is disabled.
     */
    public function login(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $token = \XDSessionManager::recordLogin($user);

        $this->syncUserOrganization($user);

        return $app->json(array(
            'success' => true,
            'results' => array('token' => $token, 'name' => $user->getFormalName())
        ));
    }

    /**
     * Attempt to log out the user identified by the provided token.
     *
     * @param Request $request that will be used to retrieve the token.
     * @param Application $app that will be used to facilitate the json
     *                         encoding of the response.
     * @return \Symfony\Component\HttpFoundation\JsonResponse indicating
     *                         that the user has been successfully logged
     *                         out.
     */
    public function logout(Request $request, Application $app)
    {
        $authInfo = Authentication::getAuthenticationInfo($request);
        \XDSessionManager::logoutUser($authInfo['token']);

        return $app->json(array(
            'success' => true,
            'message' => 'User logged out successfully'
        ));
    }

    /**
     * Ensure that the provided user's organization is in sync with their person organization. If
     * their organization has changed then remove any elevated organization related access and notify
     * the user of the steps that have been taken. Also notify the admins so that they will have some
     * visibility in to any additional steps that maybe required.
     *
     * @param XDUser $user
     *
     * @throws \Exception if there is a problem updating the provided users organization.
     */
    private function syncUserOrganization(XDUser $user)
    {

        $userProfileOrganization = $user->getOrganizationID();
        $datawarehouseUserOrganization = Organizations::getOrganizationForUser($user->getUserID());

        if ($userProfileOrganization !== $datawarehouseUserOrganization) {
            $userOrganizationName = Organizations::getNameById($userProfileOrganization);
            $currentOrganizationName = Organizations::getNameById($datawarehouseUserOrganization);

            $originalAcls = $user->getAcls(true);

            if (count(array_intersect($originalAcls, self::$CENTER_ROLES)) > 0) {

                $otherAcls = array_diff($originalAcls, self::$CENTER_ROLES);

                // Make sure that they at least have 'usr'
                if (empty($otherAcls)) {
                    $otherAcls = array('usr');
                }

                // Update the user w/ their new set of acls.
                foreach($otherAcls as $aclName) {
                    $acl = Acls::getAclByName($aclName);
                    $user->addAcl($acl);
                }

                $this->emailLogger->notice(
                    sprintf(
                        self::ADMIN_NOTIFICATION_EMAIL . self::ACL_EMAIL_ADDITION,
                        $user->getFormalName(),
                        $user->getUsername(),
                        $user->getEmailAddress(),
                        $userOrganizationName,
                        $currentOrganizationName,
                        json_encode($originalAcls),
                        json_encode($otherAcls)
                    )
                );
            }

            $user->setOrganizationId($datawarehouseUserOrganization);
            $user->saveUser();
            try {
                MailWrapper::sendMail(
                    array(
                        'subject' => 'XDMoD User: Organization Update',
                        'body' => sprintf(
                            self::USER_NOTIFICATION_EMAIL,
                            \xd_utilities\getConfiguration('mailer', 'sender_email')
                        ),
                        'toAddress' => $user->getEmailAddress(),
                        'fromAddress' => \xd_utilities\getConfiguration('general', 'tech_support_recipient'),
                        'fromName' => '',
                        'replyAddress' => \xd_utilities\getConfiguration('mailer', 'sender_email')
                    )
                );
            } catch (\Exception $e) {
                $this->fileLogger->err(
                    sprintf(
                        self::EMAIL_EXCEPTION_MSG,
                        $user->getEmailAddress(),
                        $userOrganizationName,
                        $currentOrganizationName,
                        json_encode($originalAcls),
                        json_encode($otherAcls),
                        $e->getCode(),
                        $e->getMessage(),
                        $e->getTraceAsString()
                    )
                );
            }
        }
    }
}
