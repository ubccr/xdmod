<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;

/**
 * Shredder test class.
 */
class ShredderTest extends \PHPUnit_Framework_TestCase
{

    protected $db;

    public function setUp()
    {
        $this->db = new NullDB();
    }

    /**
     * @expectedException Exception
     */
    public function testUnknownShredder()
    {
        \OpenXdmod\Shredder::factory('unknown', $this->db);
    }
}
