<?php

namespace IntegrationTests;

use Models\Services\Realms;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $XDMOD_REALMS;

    public static function setUpBeforeClass()
    {
        if(!isset(self::$XDMOD_REALMS)) {
            $xdmod_realms = array();
            $rawRealms = Realms::getRealms();
            foreach($rawRealms as $item) {
                array_push($xdmod_realms, strtolower($item->name));
            }
            self::$XDMOD_REALMS = $xdmod_realms;
        }
    }

    public static function getRealms()
    {
        if(!isset(self::$XDMOD_REALMS)) {
            $xdmod_realms = array();
            $rawRealms = Realms::getRealms();
            foreach($rawRealms as $item) {
                array_push($xdmod_realms, strtolower($item->name));
            }
            return $xdmod_realms;
        }
    }
}
