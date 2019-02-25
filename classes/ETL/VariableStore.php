<?php
/**
 * The VariableStore class maintains a list of variable key and value pairs that are meant to be
 * substituted in various places including SQL queries, ETL configurations, etc. Variables are case
 * sensitive and are specified in the source similar to shell variables of the form ${VARIABLE}.
 * Note that variables in the store do not have the ${}, only the variable name.
 *
 * In our ETL implementation, variables defined on the command line take precedence over those
 * defined in configuration files, which in turn take precedence over those set by actions
 * themselves. To support this, once a variable has been set it cannot be overwritten by using
 * standard assignment - the override() method must be used. This allows us to set high precedence
 * variables early in the action lifetime and not accidentally override them.
 *
 * NOTE: NULL values are not supported - setting a variable to NULL removes the variable from the
 * store.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2018-04-27
 */

namespace ETL;

use Log;
use \CCR\Loggable;

// Extending stdClass allows us to use this class with when a general class is used.

class VariableStore extends Loggable
{
    /**
     * Associative array of variables and values.
     * @var array
     */

    private $variables = array();

    /**
     * Regex used to identify variables in the source string.
     * @var string
     */

    private $variableRegex = '/\${([^}]+)}/';

    /**
     * Initialize the variable store with the values, if specified.
     *
     * @param mixed $store An existing VariableStore object, Traversable, or associative array of
     *   variable = value pairs used to initialize this store.
     */

    public function __construct($store = null, Log $logger = null)
    {
        parent::__construct($logger);

        if ( null === $store ) {
            return;
        } elseif ( is_array($store) || $store instanceof \stdClass || $store instanceof VariableStore || $store instanceof \Traversable ) {
            foreach ( $store as $variable => $value ) {
                $this->$variable = $value;
            }
        } else {
            $this->logAndThrowException(sprintf(
                "%s::%s() Expected array or VariableStore, got %s",
                get_class($this),
                __FUNCTION__,
                gettype($store)
            ));
        }
    }  // __construct()

    /**
     * Clear the variables in the store
     */

    public function clear()
    {
        $this->variables = array();
    }

    /**
     * Set a variable in the store.  If a variable should be overwritten use overwrite() instead.
     * Setting a variable to a NULL value will unset the variable.  If the variable is already set,
     * do not overwrite and issue a warning. This is done so that variables set early in a process
     * (such as ETL) are not blindly changed causing unexpected results.  We issue a warning because
     * it may be the case that the developer is expecting a value to be updated and want to alert
     * them that it is not.
     *
     * @param string $var The name of the variable to set
     * @param scalar $value The value of the variable
     *
     * @return VariableStore This object
     *
     * @throw Exception If the value is not a scalar
     */

    public function __set($var, $value)
    {
        if ( null === $value ) {
            unset($this->variables[$var]);
        } elseif ( ! is_scalar($value) ) {
            $this->logAndThrowException(sprintf("Value for %s must be scalar, %s provided", $var, gettype($value)));
        } elseif ( ! array_key_exists($var, $this->variables) ) {
            $this->variables[$var] = $value;
        } else {
            list($file, $line) = $this->getCallerInfo();
            $this->logger->notice(sprintf(
                "(%s) Attempt to overwrite %s ('%s') with '%s' in %s line %d",
                get_class($this),
                $var,
                $this->variables[$var],
                $value,
                $file,
                $line
            ));
        }

        return $this;
    }  // __set()

    /**
     * Generic getter method for variables.
     *
     * @param string $var The name of the variable to retrieve
     *
     * @return scalar The value of the variable, or NULL if the variable doesn't exist.
     */

    public function __get($var)
    {
        if ( array_key_exists($var, $this->variables) ) {
            return $this->variables[$var];
        }

        return null;
    }  // __get()

    /**
     * Return TRUE if the variable exists and is not NULL.
     *
     * @param string $var The name of the variable to retrieve
     *
     * @return bool TRUE if the variable exists and is not NULL, FALSE otherwise.
     */

    public function __isset($var)
    {
        return ( array_key_exists($var, $this->variables) && null !== $this->variables[$var] );
    }  // __isset()

    /**
    * Set a variable overwriting the existing value. Setting a variable to a NULL value will unset
    * the variable.
    *
    * @param string $var The name of the variable to set
    * @param scalar $value The new value of the variable
    *
    * @return VariableStore This object
    */

    public function overwrite($var, $value)
    {
        if ( null === $value ) {
            unset($this->variables[$var]);
        } elseif ( ! is_scalar($value) ) {
            $this->logAndThrowException(sprintf("Value for %s must be scalar, %s provided", $var, gettype($value)));
        } else {
            $this->variables[$var] = $value;
        }

        return $this;
    }  // overwrite()

    /**
     * Convienence function for adding the specified variables and values to the map. If the
     * variables already exist they will be skipped unless $overwrite = TRUE. Note that setting a
     * variable to a value of NULL will unset the variable from the map.
     *
     * @param array $map Associative array of variable names and values
     * @param bool $overwrite TRUE to overwrite existing values.
     *
     * @return VariableStore This object to support method chaining
     */

    public function add(array $map, $overwrite = false)
    {
        foreach ( $map as $variable => $value ) {
            if ( $overwrite ) {
                $this->overwrite($variable, $value);
            } else {
                $this->$variable = $value;
            }
        }
        return $this;
    }  // add()

    /**
    * @return array The variables and values as an associative array.
    */

    public function toArray()
    {
        return $this->variables;
    }  // toArray()

    /* ------------------------------------------------------------------------------------------
     * @return A string representation of the VariableStore suitable for debugging output.
     * ------------------------------------------------------------------------------------------
     */

    public function toDebugString()
    {
        $map = $this->variables;
        ksort($map);

        return implode(
            ', ',
            array_map(
                function ($k, $v) {
                    return "$k='$v'";
                },
                array_keys($map),
                $map
            )
        );
    }  // toDebugString()

    /**
     * Perform variable substitution on a string replacing any variables in the string that match
     * those in the VariableStore with their associated values.  Variables in the string are
     * identified using the ${} wrapper (e.g., ${VARIABLE}) and are case sensitive.
     *
     * NOTE: Map keys with a value of NULL are ignored.
     * NOTE: Variables/macros are case INSENSITIVE.
     *
     * @param string $string Source string containing variables
     * @param string $exceptionPrefix An prefix for the exception message. Exceptions are only
     *   thrown if the prefix is specified and the logger has been initialized.
     * @param array $substitutionDetails An optional array that, if present, will be populated
     *   with the macros that were substituted and those that were not substituted:
     *
     *   'substituted'   => An array of variables that were substituted
     *   'unsubstituted' => An array of variables that were present in the string but not
     *                      substituted
     *
     * @return string The string with variables substituted.
     *
     * @throws Exception if the logger has been initialized, $exceptionPrefix is non-NULL, and
     *   there are unsubstituted macros found in the string.
     */

    public function substitute(
        $string,
        $exceptionPrefix = null,
        array &$substitutionDetails = null
    ) {

        // Can't do anything with NULL or ""

        if ( empty($string) ) {
            return $string;
        }

        $exceptionForUnusedVariables = (null !== $this->logger && null != $exceptionPrefix);
        $trackDetails = ( null !== $substitutionDetails );

        // If we are not tracking the variables that have or have not been substituted, simply
        // perform a string replacement.

        if ( ! $exceptionForUnusedVariables && ! $trackDetails ) {
            foreach ( $this->variables as $k => $v ) {
                if ( null === $v ) {
                    continue;
                }
                $string = str_ireplace('${' . $k . '}', $v, $string);
            }
        } else {

            $substitutionDetails = array(
                'unsubstituted' => array(),
                'substituted'   => array()
            );

            // Perform the substitution and track variables that have been substituted

            array_map(
                function ($v, $k) use (&$string, &$substitutionDetails) {
                    if ( null === $v ) {
                        return;
                    }
                    $search = '${' . $k . '}';
                    if ( false !== stripos($string, $search) ) {
                        $substitutionDetails['substituted'][] = $k;
                    }
                    $string = str_ireplace($search, $v, $string);
                },
                $this->variables,
                array_keys($this->variables)
            );

            // If there are any variables left in the string, track them as unsubstituted.

            $matches = array();
            if ( 0 !== preg_match_all($this->variableRegex, $string, $matches ) ) {
                $substitutionDetails['unsubstituted'] = next($matches);
            }

            $substitutionDetails['unsubstituted'] = array_unique($substitutionDetails['unsubstituted']);

            if ( $exceptionForUnusedVariables && 0 != count($substitutionDetails['unsubstituted']) ) {
                list($file, $line) = $this->getCallerInfo();
                $this->logAndThrowException(sprintf(
                    "%s: %s in string '%s' at %s line %d",
                    ( null !== $exceptionPrefix ? $exceptionPrefix : "Undefined macros found" ),
                    implode(', ', $substitutionDetails['unsubstituted']),
                    $string,
                    $file,
                    $line
                ));
            }

        }

        return $string;

    }  // substitute()
}  // VariableStore
