<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;

/**
 * UGE shredder test class.
 */
class UgeShredderTest extends \PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('uge', $this->db);

        $this->assertInstanceOf('\OpenXdmod\Shredder\Uge', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Uge')
            ->disableOriginalConstructor()
            ->setMethods(array('insertRow', 'getResourceConfig'))
            ->getMock();

        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with($row);

        $shredder
            ->method('getResourceConfig')
            ->willReturn(array());

        $shredder->setLogger(\Log::singleton('null'));

        $shredder->setResource('testresource');

        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return array(
            array(
                'myqueue:testhost:jdoe:jdoe:mpi-mem-test:22947:sge:0'
                . ':1310761621000:1311095354000:1311095414000:0:0:60:60.050000'
                . ':0.080000:104188.000000:0:0:0:0:28001:0:0:0.000000:24:0:0:0'
                . ':41:549:NONE:defaultdepartment:mype:1:0:60.130000:7.335452'
                . ':0.000044:-l h_cpu=36000,h_rt=36000,qname=myqueue'
                . ',s_cpu=36000,s_rt=36000 -pe mype 1:0.000000:NONE'
                . ':189153280.000000:0:0',
                array(
                    'qname' => 'myqueue',
                    'hostname' => 'testhost',
                    'groupname' => 'jdoe',
                    'owner' => 'jdoe',
                    'job_name' => 'mpi-mem-test',
                    'job_number' => '22947',
                    'account' => 'sge',
                    'priority' => '0',
                    'submission_time' => 1310761621,
                    'start_time' => 1311095354,
                    'end_time' => 1311095414,
                    'failed' => '0',
                    'exit_status' => '0',
                    'ru_wallclock' => '60',
                    'ru_utime' => '60.050000',
                    'ru_stime' => '0.080000',
                    'ru_maxrss' => '104188.000000',
                    'ru_ixrss' => '0',
                    'ru_ismrss' => '0',
                    'ru_idrss' => '0',
                    'ru_isrss' => '0',
                    'ru_minflt' => '28001',
                    'ru_majflt' => '0',
                    'ru_nswap' => '0',
                    'ru_inblock' => '0.000000',
                    'ru_oublock' => '24',
                    'ru_msgsnd' => '0',
                    'ru_msgrcv' => '0',
                    'ru_nsignals' => '0',
                    'ru_nvcsw' => '41',
                    'ru_nivcsw' => '549',
                    'project' => 'NONE',
                    'department' => 'defaultdepartment',
                    'granted_pe' => 'mype',
                    'slots' => '1',
                    'task_number' => '0',
                    'cpu' => '60.130000',
                    'mem' => '7.335452',
                    'io' => '0.000044',
                    'category' => '-l h_cpu=36000,h_rt=36000,qname=myqueue,s_cpu=36000,s_rt=36000 -pe mype 1',
                    'iow' => '0.000000',
                    'pe_taskid' => 'NONE',
                    'maxvmem' => '189153280.000000',
                    'arid' => '0',
                    'ar_submission_time' => '0',
                    'resource_list_h_cpu' => '36000',
                    'resource_list_h_rt' => '36000',
                    'resource_list_qname' => 'myqueue',
                    'resource_list_s_cpu' => '36000',
                    'resource_list_s_rt' => '36000',
                    'resource_list_slots' => '1',
                    'clustername' => 'testresource',
                ),
            ),
        );
    }
}
