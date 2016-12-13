<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;

/**
 * PBS shredder test class.
 */
class SlurmShredderTest extends \PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('slurm', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Slurm', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs(array($this->db))
            ->setMethods(array('insertRow'))
            ->getMock();

        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with($row);

        $shredder->setLogger(\Log::singleton('null'));

        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return array(
            array(
                '4103947|4103947|ub-hpc|general-compute|anon|anon|918273|unknown|192837|2015-06-26T13:57:00|2015-06-26T13:57:00|2015-06-28T02:55:50|2015-07-01T02:55:50|3-00:00:00|1:0|TIMEOUT|32|256|256|3000Mc|||3-00:00:00|d07n07s02,d07n19s02,d07n25s01,d07n28s01,d07n29s01,d07n31s02,d07n38s02,d09n04s[01-02],d09n05s02,d09n06s01,d09n07s01,d09n08s[01-02],d09n09s01,d09n11s[01-02],d09n13s01,d09n14s01,d09n15s01,d09n16s01,d09n17s01,d09n24s01,d09n25s02,d09n28s01,d09n35s01,d09n38s02,d13n[03,05,07-08,16]|1AbC-2-3',
                array(
                    'job_id' => '4103947',
                    'job_id_raw' => '4103947',
                    'job_array_index' => -1,
                    'cluster_name' => 'ub-hpc',
                    'partition_name' => 'general-compute',
                    'account_name' => 'anon',
                    'group_name' => 'anon',
                    'gid_number' => '918273',
                    'user_name' => 'unknown',
                    'uid_number' => '192837',
                    'submit_time' => 1435327020,
                    'eligible_time' => 1435327020,
                    'start_time' => 1435460150,
                    'end_time' => 1435719350,
                    'elapsed' => 259200,
                    'exit_code' => '1:0',
                    'state' => 'TIMEOUT',
                    'nnodes' => '32',
                    'ncpus' => '256',
                    'req_cpus' => '256',
                    'req_mem' => '3000Mc',
                    'req_gres' => '',
                    'req_tres' => '',
                    'timelimit' => 259200,
                    'node_list' => 'd07n07s02,d07n19s02,d07n25s01,d07n28s01,d07n29s01,d07n31s02,d07n38s02,d09n04s[01-02],d09n05s02,d09n06s01,d09n07s01,d09n08s[01-02],d09n09s01,d09n11s[01-02],d09n13s01,d09n14s01,d09n15s01,d09n16s01,d09n17s01,d09n24s01,d09n25s02,d09n28s01,d09n35s01,d09n38s02,d13n[03,05,07-08,16]',
                    'job_name' => '1AbC-2-3',
                ),
            ),
            array(
                '4107166|4107166|ub-hpc|general-compute|dspumpkins|dspumpkins|103116|hanx|102216|2015-06-26T19:51:25|2015-06-26T19:51:26|2015-06-28T15:14:49|2015-07-01T13:17:53|2-22:03:04|0:0|CANCELLED by 102216|3|36|36|48000Mn|||3-00:00:00|k10n26s01,k13n36s01,k13n37s01|100-Floors-of-Frights',
                array(
                    'job_id' => '4107166',
                    'job_id_raw' => '4107166',
                    'job_array_index' => -1,
                    'cluster_name' => 'ub-hpc',
                    'partition_name' => 'general-compute',
                    'account_name' => 'dspumpkins',
                    'group_name' => 'dspumpkins',
                    'gid_number' => '103116',
                    'user_name' => 'hanx',
                    'uid_number' => '102216',
                    'submit_time' => 1435348285,
                    'eligible_time' => 1435348286,
                    'start_time' => 1435504489,
                    'end_time' => 1435756673,
                    'elapsed' => 252184,
                    'exit_code' => '0:0',
                    'state' => 'CANCELLED by 102216',
                    'nnodes' => '3',
                    'ncpus' => '36',
                    'req_cpus' => '36',
                    'req_mem' => '48000Mn',
                    'req_gres' => '',
                    'req_tres' => '',
                    'timelimit' => 259200,
                    'node_list' => 'k10n26s01,k13n36s01,k13n37s01',
                    'job_name' => '100-Floors-of-Frights',
                ),
            ),
            array(
                '4107219|4107219|ub-hpc|general-compute|anonacct|anongroup|456123|anonuser|10101010|2015-06-26T20:22:22|2015-06-26T20:22:22|2015-06-30T12:04:04|2015-07-01T02:04:53|14:00:49|0:0|COMPLETED|4|48|48|3000Mc||cpu=48,mem=144000M,node=4|14:20:00|k07n07s01,k08n02s01,k08n05s01,k08n13s01|anonjobname',
                array(
                    'job_id' => '4107219',
                    'job_id_raw' => '4107219',
                    'job_array_index' => -1,
                    'cluster_name' => 'ub-hpc',
                    'partition_name' => 'general-compute',
                    'account_name' => 'anonacct',
                    'group_name' => 'anongroup',
                    'gid_number' => '456123',
                    'user_name' => 'anonuser',
                    'uid_number' => '10101010',
                    'submit_time' => 1435350142,
                    'eligible_time' => 1435350142,
                    'start_time' => 1435665844,
                    'end_time' => 1435716293,
                    'elapsed' => 50449,
                    'exit_code' => '0:0',
                    'state' => 'COMPLETED',
                    'nnodes' => '4',
                    'ncpus' => '48',
                    'req_cpus' => '48',
                    'req_mem' => '3000Mc',
                    'req_gres' => '',
                    'req_tres' => 'cpu=48,mem=144000M,node=4',
                    'timelimit' => 51600,
                    'node_list' => 'k07n07s01,k08n02s01,k08n05s01,k08n13s01',
                    'job_name' => 'anonjobname',
                ),
            ),
            array(
                '5692623|5692623|ub-hpc||ccr|na|0|xms|987654321|2016-08-01T06:01:08|Unknown|2016-08-01T07:04:09|2016-08-01T07:04:09|00:00:00|0:0|COMPLETED|0|0|0|0n|gpu:2|||cpn-k16-35-01,cpn-k16-37-01|allocation',
                array(
                    'job_id' => '5692623',
                    'job_id_raw' => '5692623',
                    'job_array_index' => -1,
                    'cluster_name' => 'ub-hpc',
                    'partition_name' => '',
                    'account_name' => 'ccr',
                    'group_name' => 'na',
                    'gid_number' => '0',
                    'user_name' => 'xms',
                    'uid_number' => '987654321',
                    'submit_time' => 1470031268,
                    'eligible_time' => null,
                    'start_time' => 1470035049,
                    'end_time' => 1470035049,
                    'elapsed' => 0,
                    'exit_code' => '0:0',
                    'state' => 'COMPLETED',
                    'nnodes' => '0',
                    'ncpus' => '0',
                    'req_cpus' => '0',
                    'req_mem' => '0n',
                    'req_gres' => 'gpu:2',
                    'req_tres' => '',
                    'timelimit' => null,
                    'node_list' => 'cpn-k16-35-01,cpn-k16-37-01',
                    'job_name' => 'allocation',
                ),
            ),
        );
    }
}
