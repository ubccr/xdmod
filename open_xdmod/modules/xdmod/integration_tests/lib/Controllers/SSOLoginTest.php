<?php

namespace IntegrationTests\Controllers;

class SSOLoginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * used to test that SSO logins work correctly and the correct user information
     * is reported
     *
     * @dataProvider loginsProvider
     */
    public function testLogin($ssoSettings, $expected)
    {
        $helper = new \TestHarness\XdmodTestHelper();
        $helper->authenticateSSO($ssoSettings);

        $response = $helper->get('index.php');
        $this->assertEquals(200, $response[1]['http_code']);

        $matches = array();
        preg_match_all('/^(CCR\.xdmod.[a-zA-Z_\.]*) = ([^=;]*);?$/m', $response[0], $matches);
        $jsvariables = array_combine($matches[1], $matches[2]);

        foreach($expected as $varname => $varvalue)
        {
            $this->assertEquals($varvalue, $jsvariables[$varname]);
        }
    }

    public function loginsProvider()
    {
        return array(
            array(
                array(
                    'itname' => 'alpsw',
                    'firstName' => 'Alpine',
                    'lastName' => 'Swift',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'alpsw'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                )
            )
        );
    }
}
