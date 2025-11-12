<?php

declare(strict_types=1);

namespace CCR\Controller;

use CCR\Security\Helpers\Tokens;
use DateTime;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Twig\Environment;
use xd_security\SessionSingleton;
use XDUser;

/**
 * This controller provides basic functionality for the other Controllers such as authorization and various methods of
 * retrieving request parameters.
 */
class BaseController extends AbstractController
{
    private const USER_ATTRIBUTE_KEY = '_request_user';
    private const EXCEPTION_MESSAGE = 'An error was encountered while attempting to process the requested authorization procedure.';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var Tokens
     */
    protected $tokenHelper;

    /**
     * @param LoggerInterface $logger
     * @param Environment $twig
     * @param Tokens $tokenHelper
     */
    public function __construct(LoggerInterface $logger, Environment $twig, Tokens $tokenHelper)
    {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->tokenHelper = $tokenHelper;
    }


    /**
     * Will attempt to authorize the provided users' roles against the  provided array of role requirements.
     *
     * If the user is not authorized, an exception will be thrown. Otherwise, the function will simply return the
     * authorized user.
     *
     * @param Request $request    the current HTTP request object.
     * @param array $requiredAcls either an array of Acl objects or their equivalent string representations that are
     *                            required for access to a given feature.
     * @param bool $anyAcl        default false. If true then the requesting user will be considered authorized if there
     *                            is any overlap in the requirements and the users currently assigned acls. If false,
     *                            the requesting user will only be considered authorized if they have *all* of the
     *                            specified $requiredAcls.
     *
     * @return XDUser the currently logged in, authorized user.
     *
     * @throws UnauthorizedHttpException if no requirements are provided and there is no currently logged in user or if
     *                                   requirements are provided but not met by the public user.
     * @throws AccessDeniedHttpException if the currently logged in user is unable to fulfill the provided requirements.
     * @throws Exception if any of the values supplied within $requirements are not valid Acls objects or string
     *                   representations of Acl objects.
     */
    public function authorize(Request $request, array $requiredAcls = [], bool $anyAcl = false): XDUser
    {

        $user = $this->getXDUser($request->getSession());
        $this->logger->debug(
            sprintf(
                'Attempting to authorize user: %s (%s) with requirements: %s',
                $user->getUsername(),
                var_export($user->getAclNames(), true),
                var_export($requiredAcls, true)
            )
        );
        // If role requirements were not given, then the only check to perform
        // is that the user is not a public user.
        $isPublicUser = $user->isPublicUser();
        if (empty($requiredAcls) && $isPublicUser) {
            throw new UnauthorizedHttpException('xdmod', self::EXCEPTION_MESSAGE);
        }

        if ($anyAcl) {
            $authorized = count(array_intersect($user->getAclNames(), $requiredAcls)) > 0;
        } else {
            $authorized = $user->hasAcls($requiredAcls);
        }

        if (!$authorized && !$isPublicUser) {
            throw new AccessDeniedHttpException(self::EXCEPTION_MESSAGE);
        } elseif (!$authorized && $isPublicUser) {
            throw new UnauthorizedHttpException('xdmod', self::EXCEPTION_MESSAGE);
        }

        // Return the successfully-authorized user.
        return $user;
    }

    /**
     * Retrieve the XDMoD user from a request object.
     *
     * @param Request $request The request to retrieve a user from.
     * @return XDUser           The user who made the request.
     */
    protected function getUserFromRequest(Request $request)
    {
        return $request->attributes->get(BaseController::USER_ATTRIBUTE_KEY);
    }

    /**
     * @param Session $session
     * @return XDUser
     * @throws Exception
     */
    protected function getXDUser(Session $session): XDUser
    {
        $symfonyUser = $this->getUser();
        if (!isset($symfonyUser)) {
            if ($session->has('xdUser')) {
                $xdUser = XDUser::getUserByID($session->get('xdUser'));
            } elseif ($session->has('xdmod_token')) {
                $xdUser = XDUser::getUserByToken($session->get('xdmod_token'));
            } else {
                if (!$session->has('public_session_token')) {
                    $session->set('public_session_token', 'public-' . microtime(true) . '-' . uniqid());
                }
                $xdUser = XDUser::getPublicUser();
            }
        } else {
            $xdUser = XDUser::getUserByUserName($symfonyUser->getUserIdentifier());
        }

        if (!$xdUser->isPublicUser()) {
            $session->set('xdUser', $xdUser->getUserID());
        }
        return $xdUser;
    }

    /**
     * @param Request $request
     * @param string[] $failover_methods
     * @return XDUser
     * @throws \SessionExpiredException
     */
    protected function detectUser(Request $request, array $failover_methods = []): XDUser
    {
        $session = $request->getSession();
        try {
            $user = $this->getLoggedInUser($session);
        } catch (Exception $e) {
            if (count($failover_methods) == 0) {
                // Previously: Exception with 'Session Expired', No Logged In User code
                throw new \SessionExpiredException();
            }

            switch ($failover_methods[0]) {
                case XDUser::PUBLIC_USER:
                    if (
                        (isset($_REQUEST['public_user']) && $_REQUEST['public_user'] === 'true') ||
                        ($session->has('public_session_token'))
                    ) {
                        return XDUser::getPublicUser();
                    } else {
                        // Previously: Exception with 'Session Expired', No Public User code
                        throw new \SessionExpiredException($e->getMessage());
                    }
                    break;
                case XDUser::INTERNAL_USER:
                    try {
                        return $this->getInternalUser($request);
                    } catch (Exception $e) {
                        if (
                            isset($failover_methods[1])
                            && $failover_methods[1] == XDUser::PUBLIC_USER
                        ) {
                            if (
                                (isset($_REQUEST['public_user']) && $_REQUEST['public_user'] === 'true') ||
                                ($session->has('public_session_token'))
                            ) {
                                return XDUser::getPublicUser();
                            } else {
                                // Previously: Exception with 'Session Expired', No Public User code
                                throw new \SessionExpiredException();
                            }
                        } else {
                            // Previously: Exception with 'Session Expired', No Internal User code
                            throw new \SessionExpiredException();
                        }
                    }
                default:
                    // Previously: Exception with 'Session Expired', No Logged In User code
                    throw new \SessionExpiredException();
            }
        }

        return $user;
    }

    /**
     * Ported from libraries/security.php::getLoggedInUser, modified to use Symfony Session as opposed to the
     * SessionSingleton.
     *
     * @param Session $session
     *
     * @return XDUser
     *
     * @throws Exception if no 'xdUser' session parameter exists.
     * @throws Exception if unable to find a record in moddb.Users for the id present in the 'xdUser' session parameter.
     */
    protected function getLoggedInUser(Session $session): XDUser
    {
        // This is where the
        $sessionUserId = $session->get('xdUser');
        if (empty($sessionUserId)) {
            throw new Exception('Session Expired', 2);
        }
        $user = XDUser::getUserByID($sessionUserId);

        if ($user == NULL) {
            throw new Exception('User does not exist');
        }

        return $user;
    }


    /**
     * @param Request $request
     * @return XDUser
     * @throws Exception if there is no record in moddb.Users for the value of the user_id request param.
     * @throws Exception if there is no user_id request param.
     */
    protected function getInternalUser(Request $request): XDUser
    {
        $userId = $request->get('user_id');

        if (
            $request->server->has('REMOTE_ADDR')
            && $request->server->get('REMOTE_ADDR') == '127.0.0.1'
            && isset($userId)
        ) {
            $user = XDUser::getUserByID($userId);

            if ($user == NULL) {
                throw new Exception('Internal user does not exist');
            }
        } else {
            throw new Exception('Internal user not specified');
        }

        return $user;
    }


    /**
     * Attempt to get a parameter value from a request and filter it.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory If true, an exception will be thrown if
     *                            the parameter is missing from the request.
     * @param mixed $default The value to return if the parameter was not
     *                            specified and the parameter is not mandatory.
     * @param int $filterId The ID of the filter to use. See filter_var.
     * @param mixed $filterOptions The options to use with the filter.
     *                                The filter should be configured so that
     *                                it returns null if conversion is not
     *                                successful. See filter_var.
     * @param string $expectedValueType The expected type for the value.
     *                                    This is used purely for errors thrown
     *                                    when the parameter value is invalid.
     * @return mixed              If available and valid, the parameter value.
     *                            Otherwise, if it is missing and not mandatory,
     *                            the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value is not valid
     *                                 according to the given filter.
     */
    private function getParam(
        Request $request,
        string  $name,
        bool    $mandatory,
                $default,
        int     $filterId,
                $filterOptions,
        string  $expectedValueType,
        bool    $compressWhitespace = true
    )
    {
        // If the parameter was not present, throw an exception if it was
        // mandatory and return the default if it was not.
        // Attempt to extract the parameter value from the request.
        $value = $request->get($name);
        $originalValueType = get_debug_type($value);

        if ($value === null) {
            if ($mandatory) {
                throw new BadRequestHttpException("$name is a required parameter.");
            } else {
                return $default;
            }
        }


        // This is to accommodate the functionality from \xd_security\assertParameterSet that wasn't already provided
        // by this function.
        if ($expectedValueType === 'string' && $compressWhitespace) {
            $value = preg_replace('/\s+/', ' ', $value);
        }

        // Run the found parameter value through the given filter.
        $value = filter_var($value, $filterId, $filterOptions);
        $valueType = get_debug_type($value);

        if ($value === null ||
            ($originalValueType === 'array' && $value === false) ||
            ($expectedValueType === 'string' && $valueType !== 'string' && $value !== false) ||
            ($expectedValueType === 'Unix timestamp' && $valueType !== 'DateTime' && $value !== false) ||
            ($expectedValueType === 'ISO 8601 Date' && $valueType !== 'DateTime' && $value !== false) ||
            ($expectedValueType === 'integer' && $valueType !== 'int' && $value !== false) ||
            ($expectedValueType === 'float' && $valueType !== 'float' && $value !== false)
        ) {
            throw new BadRequestHttpException("Invalid value for $name. Must be a(n) $expectedValueType.");
        }

        // If the value is invalid, throw an exception.
        if ($value === false && $expectedValueType !== 'boolean' && $originalValueType !== 'bool') {
            // This happens when filtering a value doesn't match a regexp.
            throw new BadRequestHttpException("Invalid $name");
        }

        // Return the filtered value.
        return $value;
    }

    /**
     * Attempt to get an integer parameter value from a request.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as an integer. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to an integer.
     */
    protected function getIntParam(
        Request $request,
        string  $name,
        bool    $mandatory = false,
                $default = null
    )
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'default' => null,
                ],
            ],
            'integer'
        );
    }

    /**
     * Attempt to get a float parameter value from a request.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a float. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a float.
     */
    protected function getFloatParam(
        Request $request,
        string  $name,
        bool    $mandatory = false,
                $default = null
    )
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_VALIDATE_FLOAT,
            [
                'options' => [
                    'default' => null,
                ],
            ],
            'float'
        );
    }

    /**
     * Attempt to get a string parameter value from a request.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a string. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory.
     */
    protected function getStringParam(
        Request $request,
        string  $name,
        bool    $mandatory = false,
                $default = null,
        string  $pattern = null,
        bool    $compressWhitespace = true
    )
    {
        if (!isset($pattern)) {
            return $this->getParam(
                $request,
                $name,
                $mandatory,
                $default,
                FILTER_DEFAULT,
                [],
                'string',
                $compressWhitespace
            );
        } else {
            return $this->getParam(
                $request,
                $name,
                $mandatory,
                $default,
                FILTER_VALIDATE_REGEXP,
                ['options' => ['regexp' => $pattern]],
                'string',
                $compressWhitespace
            );
        }
    }

    protected function getEmailParam(Request $request, string $name, bool $mandatory = false, $default = null)
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_CALLBACK,
            ['options' => function ($value) {
                $validator = new EmailValidator();
                if ($validator->isValid($value, new RFCValidation())) {
                    return $value;
                }
                return null;
            }],
            'email',
            false
        );
    }

    /**
     * Attempt to get a boolean parameter value from a request.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a boolean. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a boolean.
     */
    protected function getBooleanParam(
        Request $request,
        string  $name,
        bool    $mandatory = false,
                $default = null
    )
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_CALLBACK,
            [
                'options' => function ($value) {
                    // Run the found parameter value through a boolean filter.
                    $filteredValue = filter_var(
                        $value,
                        FILTER_VALIDATE_BOOLEAN,
                        [
                            'flags' => FILTER_NULL_ON_FAILURE,
                        ]
                    );

                    // If the filter converted the string, return the boolean.
                    if ($filteredValue !== null) {
                        return $filteredValue;
                    }

                    // Check the value against 'y' for true and 'n' for false.
                    $lowercaseValue = strtolower($value);
                    if ($lowercaseValue === 'y') {
                        return true;
                    }
                    if ($lowercaseValue === 'n') {
                        return false;
                    }

                    // Return null if all conversion attempts failed.
                    return null;
                },
            ],
            'boolean'
        );
    }

    /**
     * Attempt to get a date parameter value from a request where it is
     * submitted as a Unix timestamp.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a DateTime. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a DateTime.
     */
    protected function getDateTimeFromUnixParam(
        Request $request,
        string  $name,
        bool    $mandatory = false,
                $default = null
    )
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_CALLBACK,
            [
                'options' => function ($value) {
                    $value_dt = \DateTime::createFromFormat('U', $value);
                    if ($value_dt === false) {
                        return null;
                    }
                    return $value_dt;
                },
            ],
            'Unix timestamp'
        );
    }

    /**
     * Attempt to get a date parameter value from a request where it is
     * submitted as a ISO 8601 (YYYY-MM-DD) date.
     *
     * @param Request $request The request to extract the parameter from.
     * @param string $name The name of the parameter.
     * @param bool $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a DateTime. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a DateTime.
     */
    protected function getDateFromISO8601Param(
        Request $request,
        string  $name,
        bool    $mandatory = false,
                $default = null
    )
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_CALLBACK,
            [
                'options' => function ($value) {
                    return self::filterDate($value);
                },
            ],
            'ISO 8601 Date'
        );
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function verifyCaptcha(Request $request)
    {
        $captchaSiteKey = '';
        $captchaSecret = '';
        try {
            $captchaSiteKey = \xd_utilities\getConfiguration('mailer', 'captcha_public_key');
            $captchaSecret = \xd_utilities\getConfiguration('mailer', 'captcha_private_key');
        } catch (\Exception $e) {
        }

        $user = $this->getUserFromRequest($request);

        if ('' !== $captchaSiteKey && '' !== $captchaSecret && !isset($user)) {
            $gCaptchaResponse = $request->get('g-recaptcha-response');
            if (!isset($gCaptchaResponse)) {
                throw new BadRequestHttpException('Recaptcha information not specified');
            }
            $recaptcha = new \ReCaptcha\ReCaptcha($captchaSecret);
            $resp = $recaptcha->verify($gCaptchaResponse, $_SERVER['REMOTE_ADDR']);
            if (!$resp->isSuccess()) {
                $errors = $resp->getErrorCodes();
                throw new BadRequestHttpException(sprintf('You must enter the words in the Recaptcha box properly. %s', print_r($errors, true)));
            }
        }
    }

    /**
     * @param string $section
     * @param string $key
     * @param $default
     * @return string|null
     */
    protected function getConfigValue(string $section, string $key, $default = null): ?string
    {
        try {
            $result = \xd_utilities\getConfiguration($section, $key);
        } catch (\Exception $e) {
            $result = $default;
        }
        return $result;
    }

    protected function getFeatures()
    {
        $features = \xd_utilities\getConfigurationSection('features');

        // Convert array values to boolean
        array_walk($features, function (&$v) {
            $v = ($v == 'on');
        });
        return $features;
    }

    /**
     * @param Request $request
     * @return \XDUser
     * @throws BadRequestHttpException if the provided token is empty, or there is not a provided token.
     * @throws \Exception if the user's token from the db does not validate against the provided token.
     */
    protected function authenticateToken($request)
    {
        // NOTE: While we prefer token's to be pulled from the 'Authorization' header, we also support a fallback lookup
        // to the request's query params.
        $authorizationHeader = $request->headers->get('Authorization');
        if (empty($authorizationHeader) || strpos($authorizationHeader, Tokens::HEADER_KEY) === false) {
            $rawToken = $request->get(Tokens::HEADER_KEY);
        } else {
            $rawToken = substr($authorizationHeader, strpos($authorizationHeader, Tokens::HEADER_KEY) + strlen(Tokens::HEADER_KEY) + 1);
        }
        if (empty($rawToken)) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                'No token provided.',
                null,
                0
            );
        }


        // We expect the token to be in the form /^(\d+).(.*)$/ so just make sure it at least has the required delimiter.
        $delimPosition = strpos($rawToken, Tokens::DELIMITER);
        if ($delimPosition === false) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                'Invalid token.'
            );
        }

        $userId = substr($rawToken, 0, $delimPosition);
        $token = substr($rawToken, $delimPosition + 1);

        return $this->tokenHelper->authenticate($userId, $token);
    }

    /**
     * Attempts to convert the provided $value into an instance of DateTime by using the provided $format. If $value is
     * unable to be converted into a valid DateTime or if warnings are generated during the process it will be filtered
     * and null returned.
     *
     * @param string $value the date to be validated against the provided $format. Ex: 2027-08-15
     * @param string $format the format to be used when converting the string $value to an instance of DateTime
     *
     * @return DateTime|null If the creation of a DateTime was successful without warning then an instance of DateTime
     * will be returned, else null;
     */
    private static function filterDate(string $value, string $format = 'Y-m-d'): ?DateTime
    {
        $dateTime = DateTime::createFromFormat($format, $value);

        $lastErrors = DateTime::getLastErrors();

        /* For PHP versions less than 8.2.0 $lastErrors will always be an array w/ the properties:
         * warning_count, warnings, error_count, and errors. For versions >= 8.2.0, it will return false if
         * there are no errors else it will return as it did pre-8.2.0.
         *
         * The below `if` statement takes this into account by ensuring that we specifically check for when
         * $value_dt is not false ( i.e. is a DateTime object ) but we do have 1 or more warnings which
         * indicates that the value of $value_dt is most likely not what it's expected to be.
         *
         * Example: parsing the date `2024-01-99` results in a $value_dt of:
         * DateTime('2024-04-08')
         * and a $lastError of:
         * [
         *     'warning_count' => 1,
         *     'warnings' => [
         *         10 => 'The parsed date was invalid'
         *     ],
         *     'error_count' => 0,
         *     'errors' => []
         * ]
         */
        if ($dateTime === false || (is_array($lastErrors) && $lastErrors['warning_count'] > 0)) {
            return null;
        }
        return $dateTime;
    }
}
