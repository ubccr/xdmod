<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests;

use Exception;
use Xdmod\HostListParser;

/**
 * HostListParser test class.
 */
class HostListTest extends \PHPUnit\Framework\TestCase
{

    private $parser;

    public function setup(): void
    {
        $this->parser = new HostListParser();

        if (isset($GLOBALS['LOG_DEBUG']) && $GLOBALS['LOG_DEBUG']) {
            $logger = \CCR\Log::singleton(
                'test',
                array(
                    'consoleLogLevel' => \CCR\Log::DEBUG,
                    'mail' => false,
                    'db' => false,
                    'file' => false
                )
            );
            $this->parser->setLogger($logger);
        }
    }

    /**
     * @dataProvider hostListProvider
     */
    public function testHostListParsing($hostList, array $hosts)
    {
        $this->assertEquals(
            $hosts,
            $this->parser->expandHostList($hostList)
        );

    }

    public function testNestedBracketsException()
    {
        $this->expectExceptionMessageMatches("/^Nested brackets/");
        $this->expectException(Exception::class);
        $this->parser->expandHostList('host[01-10[01-10]]');
    }

    public function testUnbalancedBracketsException()
    {
        $this->expectExceptionMessageMatches("/^Unbalanced brackets/");
        $this->expectException(Exception::class);
        $this->parser->expandHostList('host[01-10');
    }

    public function testMaxSizeException()
    {
        $this->expectExceptionMessageMatches("/^Results too large$/");
        $this->expectException(Exception::class);
        $this->parser->expandHostList('host[000000001-999999999]');
    }

    public function hostListProvider()
    {
        return array(
            array(
                '',
                array()
            ),
            array(
                ' ',
                array()
            ),
            array(
                '       ',
                array()
            ),
            array(
                ',',
                array()
            ),
            array(
                ' , ',
                array()
            ),
            array(
                'd07n04s01 ',
                array(
                    'd07n04s01',
                )
            ),
            array(
                'd07n04s01',
                array(
                    'd07n04s01',
                )
            ),
            array(
                'd07n04s01,d07n05s02',
                array(
                    'd07n04s01',
                    'd07n05s02',
                )
            ),
            array(
                'm27n07s[01-02]',
                array(
                    'm27n07s01',
                    'm27n07s02',
                )
            ),
            array(
                'k13n01s[01-02],k13n04s02',
                array(
                    'k13n01s01',
                    'k13n01s02',
                    'k13n04s02',
                )
            ),
            array(
                'k11n35s02,k16n26s[01-02]',
                array(
                    'k11n35s02',
                    'k16n26s01',
                    'k16n26s02',
                )
            ),
            array(
                'd16n[05,13-14]',
                array(
                    'd16n05',
                    'd16n13',
                    'd16n14',
                )
            ),
            array(
                'cpn-p28-[07-09,11-17]',
                array(
                    'cpn-p28-07',
                    'cpn-p28-08',
                    'cpn-p28-09',
                    'cpn-p28-11',
                    'cpn-p28-12',
                    'cpn-p28-13',
                    'cpn-p28-14',
                    'cpn-p28-15',
                    'cpn-p28-16',
                    'cpn-p28-17',
                )
            ),
            array(
                'cpn-p28-[07-09,12-13,15-16,18,20,23]',
                array(
                    'cpn-p28-07',
                    'cpn-p28-08',
                    'cpn-p28-09',
                    'cpn-p28-12',
                    'cpn-p28-13',
                    'cpn-p28-15',
                    'cpn-p28-16',
                    'cpn-p28-18',
                    'cpn-p28-20',
                    'cpn-p28-23',
                )
            ),
            array(
                'd07n04s01,d07n05s01,d07n06s02,d07n07s[01-02],d07n08s02,d07n09s02,d07n10s[01-02],d07n11s01',
                array(
                    'd07n04s01',
                    'd07n05s01',
                    'd07n06s02',
                    'd07n07s01',
                    'd07n07s02',
                    'd07n08s02',
                    'd07n09s02',
                    'd07n10s01',
                    'd07n10s02',
                    'd07n11s01',
                )
            ),
            array(
                'd07n09s[01-02],d07n10s[01-02],d09n28s01,d15n[25-27,29-30]',
                array(
                    'd07n09s01',
                    'd07n09s02',
                    'd07n10s01',
                    'd07n10s02',
                    'd09n28s01',
                    'd15n25',
                    'd15n26',
                    'd15n27',
                    'd15n29',
                    'd15n30',
                )
            ),
            array(
                'd07n09s02,d09n33s01,d13n15,d14n[09,11-14,18,23]',
                array(
                    'd07n09s02',
                    'd09n33s01',
                    'd13n15',
                    'd14n09',
                    'd14n11',
                    'd14n12',
                    'd14n13',
                    'd14n14',
                    'd14n18',
                    'd14n23',
                )
            ),
        );
    }
}
