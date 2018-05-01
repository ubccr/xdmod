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
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2018-04-27
 */

namespace ETL;

use Exception;

// Extending stdClass allows us to use this class with when a general class is used.

class VariableStore extends \stdClass
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

    private $variableRegex = '/(\$\{.+\})/';

    /**
     * Initialize the variable store with the values, if specified.
     *
     * @param mixed $store An existing VariableStore object, Traversable, or associative array of
     *   variable = value pairs used to initialize this store.
     */

    public function __construct($store = null)
    {
        if ( null === $store ) {
            return;
        } elseif ( is_array($store) || $store instanceof \stdClass || $store instanceof \Traversable ) {
            foreach ( $store as $variable => $value ) {
                $this->$variable = $value;
            }
        } else {
            throw new \Exception(sprintf(
                "%s::%s() Expected array or VariableStore, got %s",
                get_class($this),
                __FUNCTION__,
                gettype($store)
            ));
        }
    }  // __construct()

    /**
     * Set a variable in the store. If the variable is already set, do not overwrite it but simply return.
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
        if ( array_key_exists($var, $this->variables) && null !== $this->variables[$var] ) {
            return $this;
        } elseif ( ! is_scalar($value) ) {
            throw new Exception(sprintf("Value for %s must be scalar, %s provided", $var, get_type($value)));
        }

        $this->variables[$var] = $value;
        return $this;
    }  // __set()

    /**
     * Set a variable overwriting the existing value.
     *
     * @param string $var The name of the variable to set
     * @param scalar $value The new value of the variable
     *
     * @return VariableStore This object
     */

    public function overwrite($var, $value)
    {
        if ( ! is_scalar($value) ) {
            throw new Exception(sprintf("Value for %s must be scalar, %s provided", $var, get_type($value)));
        }

        $this->variables[$var] = $value;
        return $this;
    }  // overwrite()

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
     * @return array The variables and values as an associative array.
     */

    public function toArray()
    {
        return $this->variables;
    }  // toArray()

    /**
     * Perform variable substitution on a string replacing any variables in the string that match
     * those in the VariableStore with their associated values.  Variables in the string are
     * identified using the ${} wrapper (e.g., ${VARIABLE}) and are case sensitive.
     *
     * NOTE: Map keys with a value of NULL are ignored.
     *
     * @param string $string Source string containing variables
     * @param Loggable $logger If this value is non-nulll then an exception will be thrown if there
     *   are any unsubstituted variables present in the string.
     * @param string $exceptionPrefix An optional prefix for the exception message
     * @param array $substitutionDetails An optional array that, if present, will be populated
     *   with the macros that were substituted and those that were not substituted:
     *
     *   'substituted'   => An array of variables that were substituted
     *   'unsubstituted' => An array of variables that were present in the string but not
     *                      substituted
     *
     * @return The string with variables substituted.
     *
     * @throws Exception if $logger is not NULL and there are unsubstituted macros found in the
     *   string.
     */

    public function substitute(
        $string,
        Loggable $logger = null,
        $exceptionPrefix = null,
        array $substitutionDetails = null
    ) {

        // Can't do anything with NULL or ""

        if ( empty($string) ) {
            return $string;
        }

        $exceptionForUnusedVariables = ( null !== $logger );
        $trackDetails = ( null !== $substitutionDetails );

        // If we are not tracking the variables that have or have not been substituted, simply
        // perform a string replacement.

        if ( ! $exceptionForUnusedVariables && ! $trackDetails ) {
            foreach ( $this->variables as $k => $v ) {
                if ( null === $v ) {
                    continue;
                }
                $string = str_replace('${' . $k . '}', $v, $string);
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
                    if ( false !== strpos($string, $search) ) {
                        $substitutionDetails['substituted'][] = $k;
                    }
                    $string = str_replace($search, $v, $string);
                },
                $this->variables,
                array_keys($this->variables)
            );

            // If there are any variables left in the string, track them as unsubstituted.

            $matches = array();
            if ( 0 !== preg_match_all($this->variableRegex, $string, $matches ) ) {
                $substitutionDetails['unsubstituted'] = array_shift($matches);
            }

            $substitutionDetails['unsubstituted'] = array_unique($substitutionDetails['unsubstituted']);

            if ( $exceptionForUnusedVariables && 0 != count($substitutionDetails['unsubstituted']) ) {
                $logger->logAndThrowException(
                    ( null !== $exceptionPrefix ? $exceptionPrefix . ": " : "Undefined macros found: " )
                    . implode(", ", $substitutionDetails['unsubstituted'])
                );
            }

        }

        return $string;

    }  // substitute()
}  // VariableStore
