<?php

namespace ComponentTests;

use TestHarness\TestFiles;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    const PUBLIC_USER_NAME = 'Public User';
    const PUBLIC_ACL_NAME = 'pub';

    const CENTER_DIRECTOR_USER_NAME = 'centerdirector';
    const CENTER_DIRECTOR_ACL_NAME = 'cd';

    const CENTER_STAFF_USER_NAME = 'centerstaff';
    const CENTER_STAFF_ACL_NAME = 'cs';

    const PRINCIPAL_INVESTIGATOR_USER_NAME = 'principal';
    const PRINCIPAL_INVESTIGATOR_ACL_NAME = 'pi';

    const NORMAL_USER_USER_NAME = 'normaluser';
    const NORMAL_USER_ACL = 'usr';

    const INVALID_ID = -999;
    const INVALID_ACL_NAME = 'babbaganoush';

    // Used when creating users to test all possible combinations of the ACLs
    const DEFAULT_CENTER = 1;
    const DEFAULT_USER_TYPE = 3;

    const MIN_USERS = 1;
    const MAX_USERS = 1000;
    const DEFAULT_TEST_USER_NAME = "test";
    const DEFAULT_EMAIL_ADDRESS_SUFFIX = "@example.com";

    /**
     * @var TestFiles
     */
    private $testFiles = null;

    public function getTestFiles()
    {
        if ( ! isset($this->testFiles) ) {
            $this->testFiles = new TestFiles(__DIR__ . '/../');
        }
        return $this->testFiles;
    }

    /**
     * Recursively filter out any elements matching a key in $keyList. Note that only keys with
     * scalar values are filtered, keys with array values are traversed.
     *
     * @param  array $keyList The list of keys to remove
     * @param  array $input The input array being filtered.
     * @return array The filtered array with specified keys removed
     */

    protected function array_filter_keys_recursive(array $keyList, array $input)
    {
        $tmpArray = array();
        foreach ($input as $key => &$value)
        {
            if (is_array($value)) {
                $value = $this->array_filter_keys_recursive($keyList, $value);
            } elseif ( ! in_array($key, $keyList) ) {
                continue;
            }
            $tmpArray[$key] = $value;
        }
        return $tmpArray;
    }
}
