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
class PbsShredderTest extends \PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('pbs', $this->db);

        $this->assertInstanceOf('\OpenXdmod\Shredder\Pbs', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Pbs')
            ->disableOriginalConstructor()
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

            // PBS/TORQUE format.
            array(
                '12/21/2009 15:27:00;E;6.edge.ccr.buffalo.edu;user=jonesm group=ccrstaff jobname=impi-hybrid queue=ccr ctime=1261424924 qtime=1261424924 etime=1261424924 start=1261425023 owner=jonesm@edge.ccr.buffalo.edu exec_host=i02n33/7+i02n33/6+i02n33/5+i02n33/4+i02n33/3+i02n33/2+i02n33/1+i02n33/0+i02n32/7+i02n32/6+i02n32/5+i02n32/4+i02n32/3+i02n32/2+i02n32/1+i02n32/0+i02n31/7+i02n31/6+i02n31/5+i02n31/4+i02n31/3+i02n31/2+i02n31/1+i02n31/0+i02n30/7+i02n30/6+i02n30/5+i02n30/4+i02n30/3+i02n30/2+i02n30/1+i02n30/0 Resource_List.neednodes=4:ppn=8 Resource_List.nodect=4 Resource_List.nodes=4:ppn=8 Resource_List.pcput=144:00:00 Resource_List.walltime=04:00:00 session=5510 end=1261427220 Exit_status=0 resources_used.cput=00:00:00 resources_used.mem=15240kb resources_used.vmem=350936kb resources_used.walltime=00:36:37',
                array(
                    'job_id'                  => '6',
                    'host'                    => 'edge.ccr.buffalo.edu',
                    'queue'                   => 'ccr',
                    'user'                    => 'jonesm',
                    'groupname'               => 'ccrstaff',
                    'ctime'                   => '1261424924',
                    'qtime'                   => '1261424924',
                    'start'                   => '1261425023',
                    'end'                     => '1261427220',
                    'etime'                   => '1261424924',
                    'exit_status'             => '0',
                    'session'                 => '5510',
                    'jobname'                 => 'impi-hybrid',
                    'owner'                   => 'jonesm@edge.ccr.buffalo.edu',
                    'resources_used_vmem'     => 359358464,
                    'resources_used_mem'      => 15605760,
                    'resources_used_walltime' => 2197,
                    'resources_used_nodes'    => 4,
                    'resources_used_cpus'     => 32,
                    'resources_used_cput'     => 0,
                    'resource_list_nodes'     => '4:ppn=8',
                    'resource_list_neednodes' => '4:ppn=8',
                    'resource_list_pcput'     => 518400,
                    'resource_list_walltime'  => 14400,
                    'resource_list_nodect'    => '4',
                    'node_list'               => 'i02n33,i02n32,i02n31,i02n30',
                ),
            ),

            // TORQUE 5.0.1 format.
            array(
                '11/09/2014 23:59:51;E;6464684.anonhost;user=anonuser group=anongroup jobname=anonjob queue=default ctime=1415579756 qtime=1415579756 etime=1415579756 start=1415589841 owner=anon@n180 exec_host=n404/2+n404/3+n404/4+n404/5 Resource_List.mem=1993mb Resource_List.ncpus=1 Resource_List.neednodes=1:ppn=4:avx Resource_List.nodect=1 Resource_List.nodes=1:ppn=4:avx Resource_List.walltime=24:00:00 session=100966 end=1415599191 Exit_status=1 resources_used.cput=10:16:36 resources_used.mem=167864kb resources_used.vmem=622552kb resources_used.walltime=02:35:49 account=anonaccount',
                array(
                    'job_id'                  => '6464684',
                    'host'                    => 'anonhost',
                    'queue'                   => 'default',
                    'user'                    => 'anonuser',
                    'groupname'               => 'anongroup',
                    'ctime'                   => '1415579756',
                    'qtime'                   => '1415579756',
                    'start'                   => '1415589841',
                    'end'                     => '1415599191',
                    'etime'                   => '1415579756',
                    'exit_status'             => '1',
                    'session'                 => '100966',
                    'jobname'                 => 'anonjob',
                    'owner'                   => 'anon@n180',
                    'account'                 => 'anonaccount',
                    'resources_used_vmem'     => 637493248,
                    'resources_used_mem'      => 171892736,
                    'resources_used_walltime' => 9349,
                    'resources_used_nodes'    => 1,
                    'resources_used_cpus'     => 4,
                    'resources_used_cput'     => 36996,
                    'resource_list_nodes'     => '1:ppn=4:avx',
                    'resource_list_neednodes' => '1:ppn=4:avx',
                    'resource_list_walltime'  => 86400,
                    'resource_list_ncpus'     => '1',
                    'resource_list_nodect'    => '1',
                    'resource_list_mem'       => 2089811968,
                    'node_list'               => 'n404',
                ),
            ),

            // PBS Pro format.
            array(
                '01/26/2016 00:00:37;E;2994.pbs;user=anonuser group=anongroup project=_pbs_project_default jobname=anonjob queue=workq ctime=1453756297 qtime=1453756297 etime=1453756297 start=1453766281 exec_host=c1/0*13+c2/0*13+c3/0*13+c4/0*13+c5/0*13 exec_vnode=(c1:ncpus=13)+(c2:ncpus=13)+(c3:ncpus=13)+(c4:ncpus=13)+(c5:ncpus=13) Resource_List.ncpus=65 Resource_List.nodect=5 Resource_List.place=scatter:excl Resource_List.select=5:ncpus=13 Resource_List.walltime=00:02:43 session=17927 end=1453766437 Exit_status=0 resources_used.cpupercent=0 resources_used.cput=00:00:00 resources_used.mem=664kb resources_used.ncpus=65 resources_used.vmem=10704kb resources_used.walltime=00:00:00 run_count=1',
                array(
                    'job_id'                  => '2994',
                    'host'                    => 'pbs',
                    'queue'                   => 'workq',
                    'user'                    => 'anonuser',
                    'groupname'               => 'anongroup',
                    'ctime'                   => '1453756297',
                    'qtime'                   => '1453756297',
                    'start'                   => '1453766281',
                    'end'                     => '1453766437',
                    'etime'                   => '1453756297',
                    'exit_status'             => '0',
                    'session'                 => '17927',
                    'jobname'                 => 'anonjob',
                    'resources_used_vmem'     => 10960896,
                    'resources_used_mem'      => 679936,
                    'resources_used_walltime' => 0,
                    'resources_used_nodes'    => 5,
                    'resources_used_cpus'     => 65,
                    'resources_used_cput'     => 0,
                    'resource_list_walltime'  => 163,
                    'resource_list_ncpus'     => '65',
                    'resource_list_nodect'    => '5',
                    'node_list'               => 'c1,c2,c3,c4,c5',
                ),
            ),

            array(
                '01/26/2016 00:00:37;E;2995.pbs;user=anonuser group=anongroup project=_pbs_project_default jobname=anonjob queue=anonq ctime=1453756297 qtime=1453756297 etime=1453756297 start=1453766281 exec_host=c1/0-3+c2/0-7 Resource_List.ncpus=12 Resource_List.nodect=2 Resource_List.walltime=00:02:43 session=17927 end=1453766437 Exit_status=0 resources_used.cput=00:00:00 resources_used.mem=664kb resources_used.ncpus=12 resources_used.vmem=10704kb resources_used.walltime=00:00:00 run_count=1',
                array(
                    'job_id'                  => '2995',
                    'host'                    => 'pbs',
                    'queue'                   => 'anonq',
                    'user'                    => 'anonuser',
                    'groupname'               => 'anongroup',
                    'ctime'                   => '1453756297',
                    'qtime'                   => '1453756297',
                    'start'                   => '1453766281',
                    'end'                     => '1453766437',
                    'etime'                   => '1453756297',
                    'exit_status'             => '0',
                    'session'                 => '17927',
                    'jobname'                 => 'anonjob',
                    'resources_used_vmem'     => 10960896,
                    'resources_used_mem'      => 679936,
                    'resources_used_walltime' => 0,
                    'resources_used_nodes'    => 2,
                    'resources_used_cpus'     => 12,
                    'resources_used_cput'     => 0,
                    'resource_list_walltime'  => 163,
                    'resource_list_ncpus'     => '12',
                    'resource_list_nodect'    => '2',
                    'node_list'               => 'c1,c2',
                ),
            ),
        );
    }
}
