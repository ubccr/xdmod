<?php

namespace IntegrationTests;

use \TestHarness\Utilities;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $XDMOD_REALMS;

    public static function setUpBeforeClass()
    {
        self::$XDMOD_REALMS = Utilities::getRealmsToTest();
    }

    public static function getRealms()
    {
        return Utilities::getRealmsToTest();
    }
}
