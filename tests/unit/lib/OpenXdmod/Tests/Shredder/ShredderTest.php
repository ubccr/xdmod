<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;
use PHPUnit\Framework\TestCase;

/**
 * Shredder test class.
 */
class ShredderTest extends TestCase
{

    protected $db;

    public function setUp(): void
    {
        $this->db = new NullDB();
    }

    public function testUnknownShredder()
    {
        $this->expectException(\Exception::class);
        Shredder::factory('unknown', $this->db);
    }
}
