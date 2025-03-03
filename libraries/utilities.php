<?php
/**
 * Misc. utility functions.
 *
 * @author Ryan Gentner
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace xd_utilities;

use Exception;
use UnexpectedValueException;

/**
 * Global INI data.
 *
 * @var array|null
 */
$iniData = null;

/**
 * Parse the configuration file information for the requested section
 * and option.  Note that the configuration information is cached the
 * first time that this function is called unless the $cache is set to
 * false.
 *
 * @param $section Desired configuration section
 * @param $option Desired option within the section
 * @param $useCachedOptions Cache the parsed options file after the
 *     first call to this function.  Set to true by default.  Setting
 *     this to false will cause the file to be parsed again.
 *
 * @throws Exception if the section or option is not provided
 * @throws Exception if the configuration file could not be parsed
 * @throws Exception if the requested section is not found in the file
 * @throws Exception if the requested option is not found in the section
 *
 * @return mixed The requested configuration option.
 */
function getConfiguration($section, $option, $useCachedOptions = true)
{
    $sectionData = getConfigurationSection($section, $useCachedOptions);

    if (empty($option)) {
        $msg = "Configuration option not specified";
        throw new Exception($msg);
    }

    if (!array_key_exists($option, $sectionData)) {
        $msg = "Option '$option' does not exist in section '$section'";
        throw new Exception($msg);
    }

    return $sectionData[$option];
}

/**
 * Gets a base of a URL from the configuration.
 *
 * This function guarantees that the returned result ends with a slash.
 *
 * @see getConfiguration
 *
 * @param $section Desired configuration section
 * @param $option Desired option within the section
 * @param $useCachedOptions Cache the parsed options file after the
 *     first call to this function.  Set to true by default.  Setting
 *     this to false will cause the file to be parsed again.
 *
 * @throws Exception if the section or option is not provided
 * @throws Exception if the configuration file could not be parsed
 * @throws Exception if the requested section is not found in the file
 * @throws Exception if the requested option is not found in the section
 *
 * @return string The requested configuration option.
 */
function getConfigurationUrlBase($section, $option, $useCachedOptions = true)
{
    $configValue = getConfiguration($section, $option, $useCachedOptions);
    return ensure_string_ends_with($configValue, '/');
}

/**
 * Same as getConfiguration however it returns the whole section as
 * an associative array.
 *
 * @param $section Desired configuration section
 * @param $option Desired option within the section
 * @param $useCachedOptions Cache the parsed options file after the
 *     first call to this function.  Set to true by default.  Setting
 *     this to false will cause the file to be parsed again.
 *
 * @throws Exception if the section or option is not provided
 * @throws Exception if the configuration file could not be parsed
 * @throws Exception if the requested section is not found in the file
 *
 * @return array The requested configuration section.
 */
function getConfigurationSection($section, $useCachedOptions = true)
{
    global $iniData;

    if (empty($section)) {
        $msg = "Configuration section not specified";
        throw new Exception($msg);
    }

    if (null === $iniData || !$useCachedOptions) {
        $iniData = loadConfiguration();
    }

    // Verifying that the section exist.
    if (!array_key_exists($section, $iniData)) {
        $msg = "Undefined configuration section: '$section'";
        throw new Exception($msg);
    }

    return $iniData[$section];
}

function configurationSectionExists($section)
{
    try {
        $configSection = getConfigurationSection($section);
    } catch(\Exception $e) {
        return false;
    }

    return true;
}

/**
 * Load the configuration data.
 *
 * @return array
 */
function loadConfiguration()
{
    $iniFile = CONFIG_PORTAL_SETTINGS;
    $iniDir  = preg_replace('/\\.ini$/', '.d', $iniFile);

    if (!is_readable($iniFile)) {
        $msg = "Could not read settings file: " . $iniFile;
        throw new Exception($msg);
    }

    // Parse the main config file.
    $iniData = parse_ini_file($iniFile, true);

    if ($iniData === false) {
        $msg = "Failed to parse settings file: " . $iniFile;
        throw new Exception($msg);
    }

    // Merge partial config files.
    $filePaths = glob("$iniDir/*.ini");
    sort($filePaths);

    foreach ($filePaths as $partialFile) {
        if (!is_readable($partialFile)) {
            $msg = "Could not read settings file: " . $partialFile;
            throw new Exception($msg);
        }

        $partialData = parse_ini_file($partialFile, true);

        if ($partialData === false) {
            $msg = "Failed to parse settings file: " . $partialFile;
            throw new Exception($msg);
        }

        foreach ($partialData as $sectionName => $sectionData) {
            foreach ($sectionData as $key => $value) {
                $iniData[$sectionName][$key] = $value;
            }
        }
    }

    return $iniData;
}

/**
 * Clear cached configuration data.
 */
function clearConfigurationCache()
{
    global $iniData;
    $iniData = null;
}

/**
 * Quote an entity.
 */
function quote($entity)
{
    return "'$entity'";
}

/**
 * Remove an element from an array.
 */
function remove_element_by_value(&$array, $value)
{
    $index = array_search($value, $array);
    if (!is_bool($index)) {
        unset($array[$index]);
    }
}

/**
 * Check if a string begins with another string.
 *
 * @param string $string The string to check.
 * @param string $search The string that may or may not be at the
 *     beginning of the other string.
 *
 * @return bool
 */
function string_begins_with($string, $search)
{
    return (strncmp($string, $search, strlen($search)) == 0);
}

/**
 * Check if a string ends with another string.
 *
 * Based on: http://stackoverflow.com/a/834355
 *
 * @param  string $string The string to check.
 * @param  string $search The string that may or may not be at the
 *                        end of the other string.
 * @return bool           True if $search is at the end of $string.
 *                        Otherwise, false.
 */
function string_ends_with($string, $search)
{
    $searchLength = strlen($search);
    if ($searchLength === 0) {
        return true;
    }

    return substr($string, -$searchLength) === $search;
}

/**
 * Ensure a string ends with another string.
 *
 * If the given string ends with the given ending, it will be returned as is.
 * Otherwise, the given string will be returned with the given ending
 * appended on.
 *
 * @param  string $string The string to check and possibly augment.
 * @param  string $ending The ending string to check for.
 * @return string         A string that ends with the given ending.
 */
function ensure_string_ends_with($string, $ending)
{
    if (!string_ends_with($string, $ending)) {
        $string .= $ending;
    }
    return $string;
}

/**
 * Delete an element of an array.
 */
function array_delete_by_key(&$array, $delete_key, $use_old_keys = false)
{
    unset($array[$delete_key]);

    if (!$use_old_keys) {
        $array = array_values($array);
    }

    return true;
}

/**
 * Remove a key in an array and return its value or a default if not present.
 *
 * @param  array  $a   The array to remove the key from.
 * @param  mixed  $key The key to remove from the array.
 * @param  mixed  $default (Optional) The default to return if the key is
 *                         not present. (Defaults to null.)
 * @return mixed       The value for the key or the given default if the
 *                     key was not present.
 */
function array_extract(array &$a, $key, $default = null) {
    $value = array_get($a, $key, $default);
    unset($a[$key]);
    return $value;
}

/**
 * Look up a key in an array and return its value or a default if not present.
 *
 * @param  array  $a   The array to look up the key in.
 * @param  mixed  $key The key to look up in the array.
 * @param  mixed  $default (Optional) The default to return if the key is
 *                         not present. (Defaults to null.)
 * @return mixed       The value for the key or the given default if the
 *                     key was not present.
 */
function array_get(array $a, $key, $default = null) {
    if (!array_key_exists($key, $a)) {
        return $default;
    }

    return $a[$key];
}

/**
 * Replace a key's value in an array and return its old value or a default if not present.
 *
 * @param  array  $a   The array in which the key's value will be replaced.
 * @param  mixed  $key The key for the value being replaced.
 * @param  mixed  $newValue The new value to insert into the array.
 * @param  mixed  $default (Optional) The default to return if the key was
 *                         not present. (Defaults to null.)
 * @return mixed       The old value for the key or the given default if the
 *                     key was not present.
 */
function array_replace_key_value(array &$a, $key, $newValue, $default = null) {
    $oldValue = array_get($a, $key, $default);
    $a[$key] = $newValue;
    return $oldValue;
}

/**
 * Locates a value for a parameter ($param) in a string ($haystack) with
 * the format  /param1=value/param2=value/.…
 * or param1=value&param2=value&…
 *
 * If no match is found, an empty string is returned
 */
function getParameterIn($param, $haystack)
{
    $num_matches = preg_match("/$param=(.+)/", $haystack, $matches);

    $param_value = '';

    if ($num_matches > 0) {
        $frags = explode('&', str_replace('/', '&', $matches[1]));
        $param_value = $frags[0];
    }

    return $param_value;
}

/**
 * Create an XML error message
 *
 * @param $dom Document object model that the error will be inserted into
 * @param $nodeRoot Root of the error node
 * @param $code Error code
 * @param $message Error message
 *
 * @returns true if successful
 */
function generateError($dom, $nodeRoot, $code, $message)
{
    \xd_domdocument\createElement($dom, $nodeRoot, "code", $code);
    \xd_domdocument\createElement($dom, $nodeRoot, "reason", $message);

    return true;
}

/**
 * Print a message, then "delete" it.
 */
function printAndDelete($message)
{
    $message_length = strlen($message);

    print $message;
    print str_repeat(chr(8), $message_length);

    return $message_length;
}

/**
 * Check for a center logo.
 *
 * @param bool $apply_css If true output CSS for the logo.
 *
 * @return bool
 */
function checkForCenterLogo($apply_css = true)
{
    $use_center_logo = false;

    try {
        $logo       = getConfiguration('general', 'center_logo');
        $logo_width = getConfiguration('general', 'center_logo_width');

        $logo_width = intval($logo_width);

        if (strlen($logo) > 0 && $logo[0] !== '/') {
            $logo = __DIR__ . '/' . $logo;
        }

        if (file_exists($logo)) {
            $use_center_logo = true;
            $img_data = base64_encode(file_get_contents($logo));
        }
    } catch(\Exception $e) {
    }

    if ($use_center_logo == true && $apply_css == true) {
        print <<<EOF
   <style type="text/css">
      .custom_center_logo {
         height: 25px;
         width: {$logo_width}px;
         background: url(data:image/png;base64,$img_data) right no-repeat;
      }
   </style>
EOF;
    }

    return $use_center_logo;
}

/**
 * A temporary shim function to use while our supported PHP version is < 5.4.8 because 5.3
 * incorrectly returns NULL in the following case:
 *
 * filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
 *
 * See
 * https://bugs.php.net/bug.php?id=49510 and
 * http://php.net/manual/en/function.filter-var.php and
 * http://stackoverflow.com/questions/9132274/php-validation-booleans-using-filter-var
 *
 * @param mixed $value to be filtered
 * @param int $filter the type of filter to apply
 * @param mixed $options the options to be supplied to the filter
 * @return bool|mixed false if the value is logically false, else the results of
 * \filter_var($value, $filter, $options)
 */

function filter_var($value, $filter = FILTER_DEFAULT, $options = null)
{
    return ( FILTER_VALIDATE_BOOLEAN == $filter && false === $value
             ? false
             : \filter_var($value, $filter, $options) );
}

/**
 * If the specified path is not already fully qualified (e.g., /var/tmp) then prepend the
 * specified base path to the path.
 *
 * @param $path A string containing a path
 * @param $base_bath A string containing the base path to be prepended to relative paths
 *
 * @return A fully qualified path, with the base path prepended to a relative path
 */

function qualify_path($path, $base_path)
{
    if ( 0 !== strpos($path, DIRECTORY_SEPARATOR) && null !== $base_path && "" != $base_path ) {
        $path = $base_path . DIRECTORY_SEPARATOR . $path;
    }

    return $path;
}

/**
 * Resolve instances of current (.) and parent (..) directory references as well as "//"
 * to a fully qualified path without these references. For example,
 * /var/www/share/tools/etl/../../../etc/etl.json resolves to /var/www/etc/etl/etl.  Only
 * fully qualified paths are resolved as relative paths may not be able to be fully
 * resolved (e.g., ../../../etc/etl.json cannot properly be resolved on it's own). This is
 * useful for making logs more human readable.
 *
 * PHP provides realpath() but this returns FALSE if the file does not yet exist which may
 * cause issues in a dynamic environment.
 */

function resolve_path($path)
{
    // If we don't limit to filly qualified paths then relative paths such as "../../foo"
    // are not properly resolved.

    if ( 0 !== strpos($path, DIRECTORY_SEPARATOR) ) {
        return $path;
    }

    $parts = explode(DIRECTORY_SEPARATOR, str_replace('//', '/', $path));
    $resolved = array();

    foreach ($parts as $part) {
        if ( '.' == $part ) {
            continue;
        }
        if ( '..' == $part ) {
            array_pop($resolved);
            continue;
        }
        $resolved[] = $part;
    }

    return implode(DIRECTORY_SEPARATOR, $resolved);
}  // resolve_path()

/**
 * Verify that an object contains all of the properties specified in the $propertyList
 *
 * @param stdClass $obj The object to examine
 * @param array $propertyList The list of required properties
 * @param array $missing Optional reference to an array that will contain a list of the
 *   missing properties.
 *
 * @return TRUE if the object contains all of the required properties, FALSE otherwise.
 */

function verify_required_object_properties($obj, array $propertyList, array &$missing = null)
{
    if ( ! is_object($obj) ) {
        throw new Exception(sprintf("First argument must be an object, %s given", gettype($obj)));
    }

    $missing = array();

    foreach ( $propertyList as $p ) {
        if ( ! isset($obj->$p) ) {
            $missing[] = $p;
        }
    }

    return 0 == count($missing);

}  // verify_required_object_properties()

/**
 * Verify the types of object properties, optionally skipping properties that are not
 * present in the object.  Property types must match the PHP is_*() methods (e.g.,
 * is_int(), is_object(), is_string()) and will generate a warning message a function
 * corresponding to the specified type does not exist.
 *
 * @param stdClass $obj The object to examine
 * @param array $typeList An associative array where the keys are property names and
 *   the values are property types.
 * @param array $messages Optional reference to an array that will contain a list of
 *   messages regarding the property types.
 * @param boolean $skipMissingProperties If set to FALSE, properties that are not present in
 *   the object generate an error. If set to TRUE missing properties are silently skipped,
 *
 * @return TRUE if all properties were present and their type checks passed, FALSE
 *   otherwise.
 */

function verify_object_property_types(
    $obj,
    array $propertyList,
    array &$messages = null,
    $skipMissingProperties = false
) {
    if ( ! is_object($obj) ) {
        throw new Exception(sprintf("First argument must be an object, %s given", gettype($obj)));
    }

    $messages = array();

    foreach ( $propertyList as $p => $type ) {
        if ( ! isset($obj->$p) ) {
            if ( ! $skipMissingProperties ) {
                $messages[] = sprintf("missing property '%s'", $p);
            }
            continue;
        }
        $func = 'is_' . $type;
        if ( ! function_exists($func) ) {
            $messages[] = sprintf("Unsupported type %s given for property '%s'", $type, $p);
        } elseif ( ! $func($obj->$p) ) {
            $messages[] = sprintf("'%s' must be a %s, %s given", $p, $type, gettype($obj->$p));
        }
    }

    return ( 0 == count($messages) );
}  // verify_object_property_types()

/**
 * If CAPTCHA settings are correct, validate a captcha
 */
function verify_captcha(){
    $captchaSiteKey = '';
    $captchaSecret = '';
    try {
        $captchaSiteKey = getConfiguration('mailer', 'captcha_public_key');
        $captchaSecret = getConfiguration('mailer', 'captcha_private_key');
    }
    catch(exception $e){
    }

    if ('' !== $captchaSiteKey && '' !== $captchaSecret && !isset($_SESSION['xdUser'])) {
        if (!isset($_POST['g-recaptcha-response'])){
            \xd_response\presentError('Recaptcha information not specified');
        }
        $recaptcha = new \ReCaptcha\ReCaptcha($captchaSecret);
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER["REMOTE_ADDR"]);
        if (!$resp->isSuccess()) {
            $errors = $resp->getErrorCodes();
            \xd_response\presentError('You must enter the words in the Recaptcha box properly.' . print_r($errors, 1));
        }
    }
}

/**
 * Create a temporary directory.
 *
 * PHP does not have the equivalent of "mktemp -d".
 *
 * @param $prefix string The prefix of the generated directory.
 *
 * @return string The path to the temporary directory.
 */
function createTemporaryDirectory($prefix = 'xdmod-tmp-dir-')
{
    $tmpDir = tempnam(sys_get_temp_dir(), $prefix);

    if ($tmpDir === false) {
        throw new UnexpectedValueException("Failed to create temporary file");
    }

    if (!unlink($tmpDir)) {
        throw new UnexpectedValueException("Failed to remove file '$tmpDir'");
    }

    if (!mkdir($tmpDir, 0700)) {
        throw new UnexpectedValueException("Failed to create directory '$tmpDir'");
    }

    return $tmpDir;
}
