<?php
/**
 * Misc. utility functions.
 *
 * @author Ryan Gentner
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace xd_utilities;

use Exception;

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
 * Resolve a token?
 */
function tokenResolver($input)
{
    $user = \xd_security\getLoggedInUser();

    $mappings = array(
        "username" => $user->getUsername()
    );

    $output = $input;

    foreach ($mappings as $find => $replace) {
        $output = str_replace("<$find>", $replace, $output);
    }

    $output = mysql_escape_string($output);

    return $output;
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
 * Power cube?
 */
function power_cube($arr, $minLength = 1)
{
    $pp = power_set($arr, $minLength);

    foreach ($pp as $key => $value) {
        if (count($value) <= 0) { continue; }
        $pp_copy = $pp;
        unset($pp_copy[$key]);

        $value_string = implode(",",$value);

        foreach ($pp_copy as $pp_copy_el) {
            $el_value = implode(",",$pp_copy_el);
            if (string_begins_with($el_value, $value_string)) {
                unset($pp[$key]);
                break;
            }
        }
    }

    return $pp;
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
 * Power permutations?
 */
function power_perms($arr, $minLength = 1)
{
    $power_set = power_set($arr, $minLength);

    $result = array();

    foreach ($power_set as $set) {
        $perms = perms($set);
        $result = array_merge($result,$perms);
    }

    return $result;
}

/**
 * Power set?
 */
function power_set($in, $minLength = 1)
{
    $count   = count($in);
    $members = pow(2, $count);

    $return = array();

    for ($i = 0; $i < $members; $i++) {
        $b = sprintf("%0".$count."b",$i);
        $out = array();
        for ($j = 0; $j < $count; $j++) {
            if ($b{$j} == '1') { $out[] = $in[$j]; }
        }
        if (count($out) >= $minLength) {
            $return[] = $out;
        }
    }

    return $return;
}

/**
 * Factorial function.
 *
 * @param int $int Integer.
 *
 * @return int
 */
function factorial($int)
{
    if ($int < 2) {
        return 1;
    }

    for ($f = 2; $int-1 > 1; $f *= $int--);

    return $f;
}

/**
 * Permutations?
 */
function perm($arr, $nth = null)
{
    if ($nth === null) {
        return perms($arr);
    }

    $result = array();
    $length = count($arr);

    while ($length--) {
        $f = factorial($length);
        $p = floor($nth / $f);
        $result[] = $arr[$p];
        array_delete_by_key($arr, $p);
        $nth -= $p * $f;
    }

    $result = array_merge($result, $arr);

    return $result;
}

/**
 * Permutation helper function?
 */
function perms($arr)
{
    $p = array();

    for ($i = 0; $i < factorial(count($arr)); $i++) {
        $p[] = perm($arr, $i);
    }

    return $p;
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
    \xd_domdocument\createElement($dom, $nodeRoot, "code",  $code);
    \xd_domdocument\createElement($dom, $nodeRoot, "reason",  $message);

    return true;
}

/**
 * Print a message, then "delete" it.
 */
function printAndDelete($message)
{
    $message_length = strlen($message);

    print $message;
    print str_repeat(chr(8) , $message_length);

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
 * A temporary shim function to use while our supported PHP version is < 5.4.8
 *
 * @param mixed $value to be filtered
 * @param int $filter the type of filter to apply
 * @param mixed $options the options to be supplied to the filter
 * @return bool|mixed false if the value is logically false, else the results of
 * \filter_var($value, $filter, $options)
 */
function filter_var($value, $filter = FILTER_DEFAULT, $options = null)
{
    return ( false === $value ? false : \filter_var($value, $filter, $options) );
}
