<?php
/* ==========================================================================================
 * Utilitiy class containing various helper methods useful during the ETL process.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-01-12
 * ==========================================================================================
 */

namespace ETL;

use \Exception;
use \stdClass;

class Utilities
{
    // EtlConfiguration object
    private static $etlConfig = null;

    /* ------------------------------------------------------------------------------------------
     * Set the ETL configuration object in the utilities class. This should occur once upon
     * initialization.
     *
     * @param $config EtlConfiguration object
     * ------------------------------------------------------------------------------------------
     */

    public static function setEtlConfig(EtlConfiguration $config)
    {
        self::$etlConfig = $config;
    }  // setEtlConfig

    /* ------------------------------------------------------------------------------------------
     * Perform variable substitution on a stringusing the variable map. The variable map keys are the
     * names of the variables WITHOUT the ${} wrapper, these will be added during the replace
     * operation.
     *
     * @param $string Target string containing variables
     * @param $map Associative array containing the variable mappings.  The keys are the names of the
     *   variables WITHOUT the ${} wrapper.
     * @param $substitutedVariables An optional array that will be populated with the list of
     *   variables names were substituted
     * @param $unsubstitutedVariables An optional array that will be populated with the list of
     *   variables names were NOT substituted
     *
     * @return The string with variables substituted.
     * ------------------------------------------------------------------------------------------
     */

    public static function substituteVariables(
        $string,
        array $map,
        array &$substitutedVariables = null,
        array &$unsubstitutedVariables = null )
    {

        // If we are not tracking the variables that have or have not been substituted, simply
        // perform a string replacement.

        if ( null === $substitutedVariables && null == $unsubstitutedVariables ) {
            foreach ( $map as $k => $v ) {
                $string = str_replace('${' . $k . '}', $v, $string);
            }
        } else {

            $localSubstituted = array();
            $localUnsubstituted = array();

            // Track variables that have been substituted

            array_map(function ($v, $k) use (&$string, &$localSubstituted) {
                    $search = '${' . $k . '}';
                    if ( false !== strpos($string, $search) ) {
                        $substituted[] = $k;
                    }
                    $string = str_replace($search, $v, $string);
                }, $map, array_keys($map));

            // If there are any variables left in the string, track them as unsubstituted.

            if ( null !== $substitutedVariables ) {
                $substitutedVariables = $localSubstituted;
            }

            if ( null !== $unsubstitutedVariables ) {
                $matches = array();
                if ( 0 !== preg_match_all('/(\$\{.+\})/', $string, $matches ) ) {
                    $localUnsubstituted = array_shift($matches);
                }
                $unsubstitutedVariables = $localUnsubstituted;
            }

        }  // else ( null === $substitutedVariables && null == $unsubstitutedVariables )

        return $string;

    }  // substituteVariables()

    /* ------------------------------------------------------------------------------------------
     * Process a macro file. A macro is simply a text fragment containing markers that will be
     * replaced with values. Markers are identified using bash variable syntax (e.g., ${macro}) and
     * are replaced by values specified in the configuration. The macro configuration is comprised of
     * an object with the folling properties:
     *
     * name: The name of the macro. A macro of this name in the input string will be replaced by the
     *   generated value.
     * file: The file containing the macro string. Variables defined in the args section will be
     *   replaced.
     * args: An object where the properties are markers and their values are the replacement
     *   values. If no args are provided the contents of the file will simply be included.
     *
     * For example:
     *
     * {
     *   "name": "my_macro",
     *   "file": "macro_file.sql"
     *   "args: {
     *     "statistic": "walltime",
     *     "start": "${:AGGREGATION_UNIT_START_TS}"
     *     "end": "${:AGGREGATION_UNIT_END_TS}"
     *   }
     * }
     *
     * Will take the contents of macro_file.sql and replace "${statistic}" with "walltime", "${start}"
     * with "${:AGGREGATION_UNIT_START_TS}", etc.
     *
     * @param $string The string containing macros to be replaced
     * @param $config A class containing the macro configuration
     *
     * @return The string with all instances of the specified macro expanded
     *
     * @throw Exception if required properties are not provided.
     * ------------------------------------------------------------------------------------------
     */

    public static function processMacro($string, stdClass $config)
    {
        if ( null === self::$etlConfig ) {
            $msg = __CLASS__ . ": ETL configuration object not set";
            throw new Exception($msg);
        }

        $paths = self::$etlConfig->getPaths();

        if ( ! isset($paths->macro_dir) ) {
            $msg = __CLASS__ . ": ETL configuration paths.macro_dir is not set";
            throw new Exception($msg);
        } else if ( ! is_dir($paths->macro_dir) ) {
            $msg = __CLASS__ . ": ETL configuration paths.macro_dir '{$paths->macro_dir}' is not a directory";
            throw new Exception($msg);
        }

        $requiredProperties = array("name", "file");
        $missingProperties = array();

        // Verify requred options

        foreach ( $requiredProperties as $p ) {
            if ( ! isset($config->$p) || null === $config->$p || "" == $config->$p ) {
                $missingProperties[] = $p;
            }
        }

        if ( 0 != count($missingProperties) ) {
            $msg = __CLASS__ . ": Required properties not provided: " . implode(", ", $missingProperties);
            throw new Exception($msg);
        }

        // Read in a macro file, substitute keys for values, and return the macro

        $filename = $paths->macro_dir . "/" . $config->file;
        if ( ! is_file($filename) ) {
            $msg = __CLASS__ . ": Cannot load macro file '$filename'";
            throw new Exception($msg);
        } else if ( 0 == filesize($filename) ) {
            // No use processing an empty macro
            return;
        }

        if ( false === ($macro = @file_get_contents($filename)) ) {
            $msg = __CLASS__ . ": Error reading macro file '$filename'";
            throw new Exception($msg);
        }

        // Strip comments from macro

        $stripped = array();

        foreach ( explode("\n", $macro) as $line ) {
            if ( 0 === strpos($line, "--") || 0 === strpos($line,  "#") ) {
                continue;
            }
            $stripped[] = $line;
        }

        $macro = @implode("\n", $stripped);

        // Replace macro arguments

        if ( isset($config->args) && count($config->args) > 0 ) {
            $macro = self::substituteVariables($macro, (array) $config->args);
        }

        $string = self::substituteVariables($string, array( $config->name => $macro ));

        return $string;

    }  // processMacro()

    /* ------------------------------------------------------------------------------------------
     * The filter_var() function in php 5.3 is broken and returns NULL when validating
     * bool(false). This was fixed in PHP 5.4.8. See https://bugs.php.net/bug.php?id=49510 and
     * http://php.net/manual/en/function.filter-var.php and
     * http://stackoverflow.com/questions/9132274/php-validation-booleans-using-filter-var
     *
     * @param $value The value to filter.
     * @param $filter The ID of the filter to apply.
     * @param $options Associative array of options or bitwise disjunction of flags.
     *
     * @return Returns the filtered data, or NULL if the filter fails.
     * ------------------------------------------------------------------------------------------
     */

    public static function filterBooleanVar($value, $filter = FILTER_VALIDATE_BOOLEAN, $options = FILTER_NULL_ON_FAILURE)
    {
        return ( false === $value ? false : filter_var($value, $filter, $options) );
    }  // filterBooleanVar()

}  // class Utilities
