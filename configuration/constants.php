<?php

/**
 * Globally-defined PHP constants go here...
 */

$currentDir = dirname(__FILE__);

/**
 * APPLICATION_ENV can be used to select the correct configuration file
 * entry for tools that need to distinguish between development and production
 * settings on the fly.
 *
 * dev = development, prod = production
 */

define('APPLICATION_ENV', 'dev');

/**
 * USER CLASS
 */

define('VALID', 0);
define('ACTIVE', true);
define('INACTIVE', false);
define('INVALID', '-1p');
define('AMBIGUOUS', '-2p');
define('NO_MAPPING', '-3p');
define('NO_EMAIL_ADDRESS_SET', 'no_email_address_set');
define('SSO_USER_TYPE', 5);
define('DEMO_USER_TYPE', 4);
define('UNKNOWN_USER_TYPE', -1);

/**
 * SECURITY LIBRARY
 */

define('STATUS_LOGGED_IN', 0);
define('STATUS_MANAGER_ROLE', 1);
define('SAB_MEMBER', 2);
define('STATUS_CENTER_DIRECTOR_ROLE', 3);

/**
 * Directories
 */

define('CONFIG_DIR', $currentDir);
define('BASE_DIR', dirname($currentDir));

// Open XDMoD bin directory
define('BIN_DIR', '__XDMOD_BIN_PATH__');

// Open XDMoD data directory, e.g. /usr/share/xdmod or PREFIX/share
define('DATA_DIR', BASE_DIR);
define('LOG_DIR', BASE_DIR.'/logs');
define('HTML_DIR', BASE_DIR.'/html');

/**
 * Template directories
 */
define('TEMPLATE_DIR', BASE_DIR.'/templates');
define('EMAIL_TEMPLATE_DIR', BASE_DIR.'/email_templates');

/**
 * ADDITIONAL CONFIGURATION FILES
 */

define('CONFIG_PORTAL_SETTINGS', CONFIG_DIR.'/portal_settings.ini');

/**
 * COMMON ERROR CODES
 */

define('SUCCESS', 0);
define('ERROR_GENERAL', 1);
define('ERROR_FILE_NOT_FOUND', 2);

/**
 * Design-mechanism
 */

define('FAILOVER', 0);
define('EXCLUSIVE', 1);

/**
 * SEARCH-based CONSTANTS
 */

define('FORMAL_NAME_SEARCH', 0);
define('USERNAME_SEARCH', 1);

/**
 * REST
 */

// Flag for consulting DB for data
define('REST_DB', 0);

// Flag for consulting a look-up table for data
define('REST_LUT', 1);
define('REST_PARSE_OK', 0);
define('REST_PARSE_FAIL', 1);
define('REST_BASE_DIRECTORY', $currentDir.'/../classes/REST/');
define('REST_DEFAULT_FORMAT', 'json');
define('REST_DEFAULT_RAW_FORMAT', 'png');
define('REST_REALM', 0);
define('REST_CATEGORY', 1);
define('REST_ACTION', 2);
define('REST_VALID_REQUEST', 0);
define('REST_AUTH_NEEDED', 1);
define('REST_AUTH_OK', 2);
define('REST_INVALID_TOKEN', 3);
define('REST_INVALID_STRUCTURE', 4);
define('REST_NO_MAPPING', 5);
define('NO_ENUMERATION', 1);
define('TYPE_ENUM', 0);
define('TYPE_DATE', 1);

// PKI-RELATED
define('EXCEPTION_PKI', 10);


/**
 * CHARACTER LIMITS
 */

define('CHARLIM_USERNAME', '200');
define('CHARLIM_PASSWORD', '20');
define('CHARLIM_FIRST_NAME', '50');
define('CHARLIM_LAST_NAME', '50');
define('CHARLIM_EMAIL', '200');

/**
 * ROLES
 */

define('ROLE_ID_MANAGER', 'mgr');
define('ROLE_ID_USER', 'usr');
define('ROLE_ID_CENTER_DIRECTOR', 'cd');
define('ROLE_ID_CENTER_STAFF', 'cs');
define('ROLE_ID_PROGRAM_OFFICER', 'po');
define('ROLE_ID_PRINCIPAL_INVESTIGATOR', 'pi');
define('ROLE_ID_CAMPUS_CHAMPION', 'cc');
define('ROLE_ID_DEVELOPER', 'dev');
define('ROLE_ID_PUBLIC', 'pub');

/**
 * CONTROLLER-SPECIFIC
 */

define('OPERATION_DEF_BASE_PATH', $currentDir.'/../html/controllers/');

/**
 * ARGUMENT COMPLIANCE PATTERNS (for controllers)
 */

define('RESTRICTION_NUMERIC_POS', '/^[0-9]{1,10}$/');
define('RESTRICTION_YES_NO', '/^[ny]$/');
define('RESTRICTION_NON_EMPTY', '/^.+$/');
define('RESTRICTION_RID', '/^[0-9]+|[0-9]{10,19}|[a-zA-z0-9]{64}$/');
define('RESTRICTION_UID', '/^(\-?)[0-9]+$/');
define('RESTRICTION_USERNAME', '/^[a-zA-Z0-9@.\-_+\']{2,'.CHARLIM_USERNAME.'}$/');
define('RESTRICTION_PASSWORD', '/^.{5,60}$/');
define('RESTRICTION_FIRST_NAME', '/^.{1,'.CHARLIM_FIRST_NAME.'}$/');
define('RESTRICTION_LAST_NAME', '/^.{1,'.CHARLIM_LAST_NAME.'}$/');
define('RESTRICTION_ROLE', '/^[a-z]{2,3}$/');
define('RESTRICTION_ROLES_DELIM', '/^[a-z]{2,}(;[a-z]{2,})*$/');
define('RESTRICTION_PASSWORD_FLAG', '/^[ny]$/');
define('RESTRICTION_ACTIVE_FLAG', RESTRICTION_PASSWORD_FLAG);
define('RESTRICTION_ASSIGNMENT', '/^[0-9]+$/');
define('RESTRICTION_GROUP', '/^[0-9]+$/');
define('RESTRICTION_FIELD_OF_SCIENCE', '/^[0-9]+$/');
define('RESTRICTION_CHART_TITLE', '/^.{1,}$/');
define('RESTRICTION_CHART_DETAILS', '/^.{0,}$/');
define('RESTRICTION_CHART_DATE_DESC', '/^.{1,}$/');
define('RESTRICTION_CHART_TYPE', '/^.{1,}$/');
define('RESTRICTION_CHART_MODULE', '/^.{1,}$/');
define('RESTRICTION_OPERATION', '/^[_a-z]+$/');
define('RESTRICTION_SEARCH_MODE', '/^\bformal_name\b|\busername\b$/');
define('RESTRICTION_INSTITUTION', '/^(-)?[0-9]+$/');

// Associated with 'id' in modw.daterange
define('RESTRICTION_DATE_RANGE_ID', '/^[0-9]{1,2}$/');

/**
 * Colors
 */

define('COLORS', file_get_contents(CONFIG_DIR.'/colors1.json'));

/**
 * The latest version of Open XDMoD currently available to the public.
 * This value must be updated on xdmod.ccr.buffalo.edu whenever a new
 * version of Open XDMoD is released.
 */

define('OPEN_XDMOD_VERSION', '_BUILD_DEFINED_OPEN_XDMOD_VERSION_');

/**
 * Default Module Name
 **/
define('DEFAULT_MODULE_NAME', 'xdmod');
