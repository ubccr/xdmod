<?php

declare(strict_types=1);

namespace Access\Controller;

use Access\Security\Helpers\Tokens;
use Authentication\SAML\XDSamlAuthentication;
use CCR\DB;
use Exception;
use Models\Services\Acls;
use Models\Services\Realms;
use Models\Realm;
use OpenXdmod\Assets;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use xd_security\SessionSingleton;
use XDUser;

class HomeController extends BaseController
{
    const REQUIRED_SAML_SETTINGS = [
        'idp' => [
            'entityId',
            'singleSignOnService' => [
                'url',
                'binding'
            ],
            'singleLogoutService' => [
                'url',
                'binding'
            ]
        ],
        'sp' => [
            'entityId',
            'assertionConsumerService' => [
                'url',
                'binding'
            ],
            'singleLogoutService' => [
                'url',
                'binding'
            ]
        ]
    ];
    private $parameters;

    public function __construct(LoggerInterface $logger, Environment $twig, Tokens $tokenHelper, ContainerBagInterface $parameters)
    {
        parent::__construct($logger, $twig, $tokenHelper);
        $this->parameters = $parameters;
    }

    /**
     * This route serves XDMoD
     *
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/', name: 'xdmod_home', methods: ['GET', 'OPTIONS'])]
    public function index(Request $request): Response
    {

        if ($request->getMethod() === 'OPTIONS') {
            // We don't need to send anything back for a CORS pre-flight
            return new Response();
        }

        $session = $request->getSession();
        $returnTo = $session->get('_security.main.target_path');
        if (!empty($returnTo)) {
            $returnTo = urldecode($returnTo);
            $url = $this->generateUrl('xdmod_home');
            $this->logger->warning('redirecting to', ["$returnTo"]);
            $session->set('_security.main.target_path', null);
            $response = new RedirectResponse("$returnTo");
            return $response;
        }
        $user = $this->getXDUser($session);

        $session->set('xdUser', $user->getUserID());

        $realms = array_reduce(Realms::getRealms(), function ($carry, Realm $item) {
            $carry [] = $item->getName();
            return $carry;
        }, []);

        $features = $this->getFeatures();

        $isSSOConfigured = false;
        $ssoLoginLink = [
            'organization' => [
                'en' => 'Test Organization',
                'icon' => ''
            ]
        ];
        $ssoSettings = $this->getParameter('sso');
        try {
            $auth = new XDSamlAuthentication();
            $ssoLoginLink = $auth->getLoginLink();
            $isSSOConfigured = true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [$e]);
        }

        try {
            $db = DB::factory('database');
            $personInfo = $db->query(
                'SELECT first_name, last_name FROM modw.person p WHERE p.id = :person_id',
                [':person_id' => $user->getPersonID()]
            );
        } catch (\Exception $e) {
            $personInfo = [
                [
                    'first_name' => 'Unknown',
                    'last_name' => 'Unknown'
                ]
            ];
        }

        // JupyterHub Config
        try {
            $jupyterHubURL = \xd_utilities\getConfiguration('jupyterhub', 'url');
            $jupyterIsEnabled = !empty($jupyterHubURL);
        } catch (\Exception $e) {
            $jupyterIsEnabled = false;
            $jupyterHubURL = '';
        }


        $params = [
            'user' => $user,
            'person_name' => sprintf('%s, %s', $personInfo[0]['last_name'], $personInfo[0]['first_name']),
            'title' => \xd_utilities\getConfiguration('general', 'title'),
            'keywords' => 'xdmod, xsede, analytics, metrics on demand, hpc, visualization, statistics, reporting, auditing, nsf, resources, resource providers',
            'description' => 'XSEDE Metrics on Demand (XDMoD) is a comprehensive auditing framework for XSEDE, the follow-on to NSF\'s TeraGrid program. XDMoD provides detailed information on resource utilization and performance across all resource providers.',
            'extjs_path' => 'gui/lib',
            'extjs_version' => 'extjs',
            'rest_token' => $user->getToken(),
            'colors' => json_encode(json_decode(file_get_contents(CONFIG_DIR . '/colors1.json'), true)),
            'rest_url' => sprintf(
                '%s%s',
                \xd_utilities\getConfiguration('rest', 'base'),
                \xd_utilities\getConfiguration('rest', 'version')
            ),
            'realms' => $realms,
            'tech_support_recipient' => \xd_utilities\getConfiguration('general', 'tech_support_recipient'),
            'xdmod_portal_version' => \xd_versioning\getPortalVersion(),
            'xdmod_portal_version_short' => \xd_versioning\getPortalVersion(true),
            'disabled_menus' => json_encode(Acls::getDisabledMenus($user, $realms)),
            'ORGANIZATION_NAME' => 'organization_name',
            'ORGANIZATION_NAME_ABBREV' => 'organization_abbrev',
            'captcha_site_key' => $this->getCaptchaSiteKey($user),
            'xdmod_features' => json_encode($features),
            'timezone' => date_default_timezone_get(),
            'isCenterDirector' => $user->hasAcl('cd'),
            'is_logged_in' => !$user->isPublicUser(),
            'is_public_user' => $user->isPublicUser(),
            'user_dashboard' => isset($features['user_dashboard']) && filter_var($features['user_dashboard'], FILTER_VALIDATE_BOOLEAN),
            'all_user_roles' => json_encode($user->enumAllAvailableRoles()),
            'raw_data_realms' => json_encode($this->getRawDataRealms($user)),
            'use_center_logo' => false,
            'asset_paths' => Assets::generateAssetTags('portal'),
            'profile_editor_init_flag' => $this->getProfileEditorInitFlag($user),
            'no_script_message' => $this->getNoScriptMessage('XDMoD requires JavaScript, which is currently disabled in your browser.'),
            'org_name' => ORGANIZATION_NAME,
            'is_sso_configured' => $isSSOConfigured,
            'sso_login_link' => json_encode($ssoLoginLink),
            'sso_show_local_login' => $ssoSettings['show_local_login'],
            'sso_direct_link' => $ssoSettings['direct_link'],
            'jupyter_is_enabled' => $jupyterIsEnabled,
            'jupyter_hub_url' => $jupyterHubURL
        ];

        $logoData = $this->getLogoData();
        if ($logoData !== null) {
            list($logoWidth, $imgData) = $logoData;
            $params['use_center_logo'] = true;
            $params['logo_width'] = $logoWidth;
            $params['img_data'] = $imgData;
        }

        return $this->render('index.html.twig', $params);
    }


    /**
     * @param $user
     * @return array
     */
    private function getRawDataRealms($user): array
    {
        return array_map(
            function ($item) {
                return $item['name'];
            },
            \DataWarehouse\Access\RawData::getRawDataRealms($user)
        );
    }

    public function getCaptchaSiteKey(XDUser $user)
    {
        $result = '';

        if ($user->isPublicUser()) {
            $captchaSiteKey = \xd_utilities\getConfiguration('mailer', 'captcha_public_key');
            $captchaSecret = \xd_utilities\getConfiguration('mailer', 'captcha_private_key');
            if ('' !== $captchaSiteKey && '' !== $captchaSecret) {
                $result = $captchaSiteKey;
            }
        }

        return $result;
    }


    public function getLogoData()
    {
        try {
            $logo = \xd_utilities\getConfiguration('general', 'center_logo');
            $logo_width = \xd_utilities\getConfiguration('general', 'center_logo_width');

            $logo_width = intval($logo_width);

            if (strlen($logo) > 0 && $logo[0] !== '/') {
                $logo = __DIR__ . '/' . $logo;
            }

            if (file_exists($logo)) {
                $img_data = base64_encode(file_get_contents($logo));
                return [
                    $logo_width,
                    $img_data
                ];
            }
        } catch (Exception $e) {
        }

        return null;
    }

    private function getProfileEditorInitFlag(XDUser $user)
    {
        $profile_editor_init_flag = '';
        $usersFirstLogin = ($user->getCreationTimestamp() == $user->getUpdateTimestamp() && !$user->isPublicUser());

        // If the user logging in is an XSEDE/Single Sign On user, they may or may not have
        // an e-mail address set. The logic below assists in presenting the Profile Editor
        // with the appropriate (initial) view
        $userEmail = $user->getEmailAddress();
        $userEmailSpecified = ($userEmail != NO_EMAIL_ADDRESS_SET && !empty($userEmail));
        if ($user->isSSOUser() === true || $usersFirstLogin) {

            // NOTE: $_SESSION['suppress_profile_autoload'] will be set only upon update of the user's profile (see respective REST call)
            $session = SessionSingleton::getSession();
            $suppressProfileAutoload = $session->get('suppress_profile_autoload');
            if ($usersFirstLogin && $userEmailSpecified && (!isset($suppressProfileAutoload) && $user->getUserType() != 50)) {
                // If the user is logging in for the first time and does have an e-mail address set
                // (due to it being specified in the XDcDB), welcome the user and inform them they
                // have an opportunity to update their e-mail address.

                $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_CHANGE';

            } elseif ($usersFirstLogin && !$userEmailSpecified) {
                // If the user is logging in for the first time and does *not* have an e-mail address set,
                // welcome the user and inform them that he/she needs to set an e-mail address.

                $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_NEEDED';

            }
        }
        if (!$userEmailSpecified) {
            // Regardless of whether the user is logging in for the first time or not, the lack of
            // an e-mail address requires attention
            $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.EMAIL_NEEDED';
        }

        return $profile_editor_init_flag;
    }

    public function getNoScriptMessage($message, $exception_message = '', $include_structure_tags = false)
    {

        if (!empty($exception_message)) {
            $exception_message = '<br><br><span style="color: #888">(' . $exception_message . ')</span>';
        }

        $message = '<center>' .
            '<br>' .
            '<img src="gui/images/xdmod_main.png">' .
            '<br><br>' .
            $message .
            $exception_message .
            '</center>';

        if ($include_structure_tags) {
            $message = '<html><body>' . $message . '</body></html>';
        }

        return $message;
    }

    /**
     * SSO is considered setup
     * @return bool
     */
    private function isSSOSetup(array $ssoSettings): bool
    {
        return $this->validate(
            self::REQUIRED_SAML_SETTINGS,
            $ssoSettings
        );
    }

    /**
     * Validates the provided $settings against the $required structure. This function only validates that
     * keys are present and have non-empty values.
     *
     * @param array $required
     * @param array $settings
     * @return bool
     */
    private function validate(array $required, array $settings): bool
    {
        foreach ($required as $key => $values) {
            // We need to account for PHP's wonderful dual-index arrays, and since $settings is expected
            // to be indexed by string we translate the $required indexes to their string counterpart here.
            if (is_numeric($key) && is_string($values)) {
                $key = $values;
            }

            // the following logic goes something like:
            //   If:
            //     -     The required key exists in $settings
            //     - AND The required key is a string
            //     - AND The value for the given key in $settings is non-empty
            //  - OR -
            //   If:
            //     -     The required key exists in $settings
            //     - AND the $required values are an array ( aka, we must go deeper )
            //     - AND and it's value in $settings is non-empty
            //     - AND the validation of the levels below this one are valid
            // THEN continue the validation
            // ELSE it's invalid
            if (array_key_exists($key, $settings) && is_string($values) && !empty($settings[$key]) ||
                (array_key_exists($key, $settings) && is_array($values) && !empty($settings[$key]) && $this->validate($values, $settings[$key]))) {
                continue;
            }
            return false;
        }
        // If we've gotten this far then the settings must be valid.
        return true;
    }
}

