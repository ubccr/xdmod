<?php
/**
 * Security related functions.
 */

namespace xd_security;

/**
 * @param array $failover_methods
 *
 * @return \XDUser
 */
function detectUser($failover_methods = array())
{

    // - Attempt to get a logged in user
    // - Should a logged in user not exist, inspect $failover_methods to
    //   determine the next kind of user to fetch
    try {
        $user = getLoggedInUser();
    } catch (\Exception $e) {
        if (count($failover_methods) == 0) {
            // Previously: Exception with 'Session Expired', No Logged In User code
            throw new \SessionExpiredException(); 
        }

        switch ($failover_methods[0]) {
            case \XDUser::PUBLIC_USER:
                if (
                    isset($_REQUEST['public_user'])
                    && $_REQUEST['public_user'] === 'true'
                ) {
                    return \XDUser::getPublicUser();
                } else {
                    // Previously: Exception with 'Session Expired', No Public User code
                    throw new \SessionExpiredException();
                }
                break;
            case \XDUser::INTERNAL_USER:
                try {
                    return getInternalUser();
                } catch (\Exception $e) {
                    if (
                        isset($failover_methods[1])
                        && $failover_methods[1] == \XDUser::PUBLIC_USER
                    ) {
                        if (
                            isset($_REQUEST['public_user'])
                            && $_REQUEST['public_user'] === 'true'
                        ) {
                            return \XDUser::getPublicUser();
                        } else {
                            // Previously: Exception with 'Session Expired', No Public User code
                            throw new \SessionExpiredException();
                        }
                    } else {
                        // Previously: Exception with 'Session Expired', No Internal User code
                        throw new \SessionExpiredException();
                    }
                }
                break;
            default:
                // Previously: Exception with 'Session Expired', No Logged In User code
                throw new \SessionExpiredException();
                break;
        }
    }

    return $user;
}

/**
 * This is merely to check if a dashboard user has logged in (and not
 * make use of the respective XDUser object)
 *
 * @return \XDUser
 */
function assertDashboardUserLoggedIn()
{
    try {
        return getDashboardUser();
    } catch (SessionExpiredException $see) {
        // TODO: Refactor generic catch block below to handle specific exceptions,
        //       which would allow this block to be removed.
        throw $see;
    } catch (\Exception $e) {
        \xd_controller\returnJSON(array(
            'success' => false,
            'status'  => $e->getMessage(),
        ));
        exit;
    }
}

/**
 * @return \XDUser An instance of XDUser pertaining to the dashboard
 *     user.
 *
 * @throws \Exception If:
 *     - The session variable pertaining to the dashboard user does not
 *       exist.
 *     - The user_id stored in the session variable does not map to a
 *       valid XDUser.
 *     - The user does not have manager privileges.
 */
function getDashboardUser()
{
    if (!isset($_SESSION['xdDashboardUser'])) {
        throw new \SessionExpiredException('Dashboard session expired');
    }

    $user = \XDUser::getUserByID($_SESSION['xdDashboardUser']);

    if ($user == NULL) {
        throw new \Exception('User does not exist');
    }

    if ($user->isManager() == false) {
        throw new \Exception('Permissions do not allow you to access the dashboard');
    }

    return $user;
}

/**
 * @return \XDUser
 *
 * @throws \Exception
 */
function getLoggedInUser()
{

    if (!isset($_SESSION['xdUser'])) {
        throw new \SessionExpiredException();
    }

    $user = \XDUser::getUserByID($_SESSION['xdUser']);

    if ($user == NULL) {
        throw new \Exception('User does not exist');
    }

    return $user;
}

/**
 * @return \XDUser
 *
 * @throws \Exception
 */
function getInternalUser()
{

    if (
        isset($_SERVER['REMOTE_ADDR'])
        && $_SERVER['REMOTE_ADDR'] == '127.0.0.1'
        && isset($_REQUEST['user_id'])
    ) {
        $user = \XDUser::getUserByID($_REQUEST['user_id']);

        if ($user == NULL) {
            throw new \Exception('Internal user does not exist');
        }
    } else {
        throw new \Exception('Internal user not specified');
    }

    return $user;
}

/**
 * @param array $requirements
 * @param string $session_variable
 */
function enforceUserRequirements($requirements, $session_variable = 'xdUser')
{
    $returnData = array();

    if (in_array(STATUS_LOGGED_IN, $requirements)) {
        if (!isset($_SESSION[$session_variable])) {
            throw new \SessionExpiredException();
        }

        $user = \XDUser::getUserByID($_SESSION[$session_variable]);

        if ($user == NULL) {
            $returnData['status']     = 'user_does_not_exist';
            $returnData['success']    = false;
            $returnData['totalCount'] = 0;
            $returnData['message']    = 'user_does_not_exist';
            $returnData['data']       = array();
            \xd_controller\returnJSON($returnData);
        }

        // Manager subsumes 'Science Advisory Board Member' role
        if ($user->isManager()) {
            \xd_utilities\remove_element_by_value($requirements, SAB_MEMBER);
        }

        if (in_array(SAB_MEMBER, $requirements)) {

            // This user must be a member of the Science Advisory Board
            if (!$user->hasAcl('sab')) {
                $returnData['status']     = 'not_sab_member';
                $returnData['success']    = false;
                $returnData['totalCount'] = 0;
                $returnData['message']    = 'not_sab_member';
                $returnData['data']       = array();
                \xd_controller\returnJSON($returnData);
            }
        }

        if (in_array(STATUS_MANAGER_ROLE, $requirements)) {
            if (!($user->isManager())) {
                $returnData['status']     = 'not_a_manager';
                $returnData['success']    = false;
                $returnData['totalCount'] = 0;
                $returnData['message']    = 'not_a_manager';
                $returnData['data']       = array();
                \xd_controller\returnJSON($returnData);
            }
        }

        if (in_array(STATUS_CENTER_DIRECTOR_ROLE, $requirements)) {
            if (!$user->hasAcl(ROLE_ID_CENTER_DIRECTOR)) {
                $returnData['status']     = 'not_a_center_director';
                $returnData['success']    = false;
                $returnData['totalCount'] = 0;
                $returnData['message']    = 'not_a_center_director';
                $returnData['data']       = array();
                \xd_controller\returnJSON($returnData);
            }
        }
    }
}

/**
 * Ensures that all of the $_REQUEST[keys] in $required_params conform
 * to their respective patterns (e.g. $required_params
 * = array('uid' => RESTRICTION_UID) : $_REQUEST['uid'] has to comply
 * with the pattern in RESTRICTION_UID
 *
 * If $enforce_all is set to 'false', then secureCheck will return an
 * integer indicating how many of the params qualify (this is used for
 * cases in which at least one parameter is required, but not all)
 *
 * @param array $required_params
 * @param string $m
 * @param bool $enforce_all
 */
function secureCheck(&$required_params, $m, $enforce_all = true)
{

    // ${'_'.$m}['param'] <-- should be working, but doesn't inside this
    //                        function

    $qualifyingParams = 0;

    if ($m == 'GET')     { $param_array = $_GET; }
    if ($m == 'POST')    { $param_array = $_POST; }
    if ($m == 'REQUEST') { $param_array = $_REQUEST; }

    foreach ($required_params as $param => $pattern) {
        if (!isset($param_array[$param])) {
            if ($enforce_all) { return false; }
            if (!$enforce_all) { continue; }
        }

        $param_array[$param]
            = preg_replace('/\s+/', ' ', $param_array[$param]);

        if (preg_match($pattern, $param_array[$param]) == 0) {
            if ($enforce_all) { return false; }
            if (!$enforce_all) { continue; }
        }

        $qualifyingParams++;
    }

    if ($enforce_all) { return true; }
    if (!$enforce_all) { return $qualifyingParams; }
}

/**
 * @param array $requiredParams
 */
function assertParametersSet($requiredParams = array())
{
    foreach ($requiredParams as $k => $v) {
        if (!is_int($k)) {

            // $k represents the name of the param
            // $v represents the format of the value that param must conform
            //    to (a regex)
            $param_name = $k;
            $pattern    = $v;
        } else {

            // $v represents the name of the param
            $param_name = $v;
            $pattern    = '/.*/';
        }

        assertParameterSet($param_name, $pattern);
    }
}

/**
 * Provides a checkstop when a required argument has not been supplied
 * in a web request (using GET or POST).
 *
 * @param string $param_name Parameter name.
 * @param string $pattern Pattern parameter must match.
 * @param bool $compress_whitespace True if any whitespace in the
 *     parameter value should be replaced with a single space
 *     (default: true).
 *
 * @return string The parameter value.
 */
function assertParameterSet(
    $param_name,
    $pattern = '/.*/',
    $compress_whitespace = true
) {
    if (!isset($_REQUEST[$param_name])) {
        \xd_response\presentError("'$param_name' not specified.");
    }

    $param_value = $_REQUEST[$param_name];

    if ($compress_whitespace) {
        $param_value = preg_replace('/\s+/', ' ', $param_value);
    }

    $match = preg_match($pattern, $param_value);

    if ($match === false) {
        \xd_response\presentError("Failed to assert '$param_name'.");
    } elseif ($match == 0) {
        \xd_response\presentError("Invalid value specified for '$param_name'.");
    }

    return $param_value;
}

/**
 * Assert that a request parameter is set and is also a valid email address.
 *
 * @param string $param_name Parameter name.
 * @return string The parameter value.
 */
function assertEmailParameterSet($param_name)
{
    if (!isset($_REQUEST[$param_name])) {
        \xd_response\presentError("'$param_name' not specified.");
    }

    $param_value = $_REQUEST[$param_name];

    if (!isEmailValid($param_value)) {
        \xd_response\presentError("Failed to assert '$param_name'.");
    }

    return $param_value;
}

/**
 * Determine if an email address is valid.
 *
 * @param string $email Email address to validate.
 * @return bool True if the email address is valid.
 */
function isEmailValid($email)
{
    $validator = new \Egulias\EmailValidator\EmailValidator();
    return $validator->isValid($email);
}
