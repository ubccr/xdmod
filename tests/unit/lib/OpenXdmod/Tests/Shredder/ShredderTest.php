<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use Exception;
use OpenXdmod\Shredder;

/**
 * Shredder test class.
 */
class ShredderTest extends \PHPUnit\Framework\TestCase
{

    protected $db;

    public function setup(): void
    {
        $this->db = new NullDB();
    }

    public function testUnknownShredder()
    {
        $this->expectException(Exception::class);
        Shredder::factory('unknown', $this->db);
    }
}
