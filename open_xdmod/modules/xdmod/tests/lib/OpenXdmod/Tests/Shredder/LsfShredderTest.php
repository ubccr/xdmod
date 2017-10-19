<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;

/**
 * LSF shredder test class.
 */
class LsfShredderTest extends \PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('lsf', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Lsf', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredder($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Lsf')
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
                '"JOB_FINISH" "9.11" 1389683139 404717 22025 33882739 128 1389642318 0 0 1389642771 "anonuser" "regular" "span[ptile=16]" "" "" "hostlogin4-ib" "/scratch/anonuser/abcde/abcde0040" "" "%J.out" "%J.err" "1389642318.404717" 0 8 "16*host1201-ib" "16*host1234-ib" "16*host1235-ib" "16*host1236-ib" "16*host1257-ib" "16*host1270-ib" "16*host1301-ib" "16*host1303-ib" 64 60.0 "abcde0040" "#!/bin/tcsh;#;# LSF batch script to run an MPI application;#;#BSUB -a poe                  # set parallel operating environment;#BSUB -P anonproject            # project code;#BSUB -W 12:00               # wall-clock time (hrs:mins);#BSUB -n 128                  # number of tasks in job         ;#BSUB -R ""span[ptile=16]""    # run 16 MPI tasks per node (1=max memory choice);#BSUB -J abcde0040            # job name;#BSUB -o %J.out        # output file name in which %J is replaced by the job ID;#BSUB -e %J.err        # error file name in which %J is replaced by the job ID;#BSUB -q regular             # queue; #run the executable; pwd;date;date >> out.time;mpirun.lsf /u/home/anonuser/bin/app > out.app;date >> out.time;date" 5793844.230000 679.060000 966960 0 -1 0 0 3026323 0 0 0 0 -1 0 0 0 12529329 6498459 -1 "" "anonproject" 0 128 "" "" 0 10725376 0 "" "" "" "" 0 "" 0 "" -1 "/anonproject/anonuser" "" "" "" -1 "" "" 6160 "" 1389642771 "" "" 0 8 host1201-ib 1306 0 1287486 242 0 host1234-ib 1291 0 643768 66 0 host1235-ib 1297 0 643765 63 0 host1236-ib 1299 0 643770 59 0 host1257-ib 1296 0 643756 67 0 host1270-ib 1297 0 643763 58 0 host1301-ib 1292 0 643773 57 0 host1303-ib 1295 0 643763 67 0 43200 0 8688640 "select[((poe>0&&nrt_windows>0&&tmp>1)) && (type == local)] order[nidx] span[ptile=16] " "" -1 "" -1 0 "" 0 0 "" 40368 "/scratch/anonuser/abcde/abcde0040" 1 "18338657682652659712" 1 ""',
                array(
                    'event_type' => 'JOB_FINISH',
                    'version_number' => '9.11',
                    'event_time' => '1389683139',
                    'job_id' => '404717',
                    'user_id' => '22025',
                    'options' => '33882739',
                    'num_processors' => '128',
                    'submit_time' => '1389642318',
                    'begin_time' => '0',
                    'term_time' => '0',
                    'start_time' => '1389642771',
                    'user_name' => 'anonuser',
                    'queue' => 'regular',
                    'res_req' => 'span[ptile=16]',
                    'depend_cond' => '',
                    'pre_exec_cmd' => '',
                    'from_host' => 'hostlogin4-ib',
                    'cwd' => '/scratch/anonuser/abcde/abcde0040',
                    'in_file' => '',
                    'out_file' => '%J.out',
                    'err_file' => '%J.err',
                    'job_file' => '1389642318.404717',
                    'num_asked_hosts' => '0',
                    'asked_hosts' => array(),
                    'num_ex_hosts' => '8',
                    'exec_hosts' => array('host1201-ib', 'host1234-ib', 'host1235-ib', 'host1236-ib', 'host1257-ib', 'host1270-ib', 'host1301-ib', 'host1303-ib'),
                    'j_status' => '64',
                    'host_factor' => '60.0',
                    'job_name' => 'abcde0040',
                    'command' => '#!/bin/tcsh;#;# LSF batch script to run an MPI application;#;#BSUB -a poe                  # set parallel operating environment;#BSUB -P anonproject            # project code;#BSUB -W 12:00               # wall-clock time (hrs:mins);#BSUB -n 128                  # number of tasks in job         ;#BSUB -R "span[ptile=16]"    # run 16 MPI tasks per node (1=max memory choice);#BSUB -J abcde0040            # job name;#BSUB -o %J.out        # output file name in which %J is replaced by the job ID;#BSUB -e %J.err        # error file name in which %J is replaced by the job ID;#BSUB -q regular             # queue; #run the executable; pwd;date;date >> out.time;mpirun.lsf /u/home/anonuser/bin/app > out.app;date >> out.time;date',
                    'ru_utime' => '5793844.230000',
                    'ru_stime' => '679.060000',
                    'ru_maxrss' => '966960',
                    'ru_ixrss' => '0',
                    'ru_ismrss' => '-1',
                    'ru_idrss' => '0',
                    'ru_isrss' => '0',
                    'ru_minflt' => '3026323',
                    'ru_majflt' => '0',
                    'ru_nswap' => '0',
                    'ru_inblock' => '0',
                    'ru_oublock' => '0',
                    'ru_ioch' => '-1',
                    'ru_msgsnd' => '0',
                    'ru_msgrcv' => '0',
                    'ru_nsignals' => '0',
                    'ru_nvcsw' => '12529329',
                    'ru_nivcsw' => '6498459',
                    'ru_exutime' => '-1',
                    'mail_user' => '',
                    'project_name' => 'anonproject',
                    'exit_status' => '0',
                    'max_num_processors' => '128',
                    'login_shell' => '',
                    'time_event' => '',
                    'idx' => '0',
                    'max_rmem' => '10725376',
                    'max_rswap' => '0',
                    'in_file_spool' => '',
                    'command_spool' => '',
                    'rsv_id' => '',
                    'sla' => '',
                    'except_mask' => '0',
                    'additional_info' => '',
                    'exit_info' => '0',
                    'warning_action' => '',
                    'warning_time_period' => '-1',
                    'charged_saap' => '/anonproject/anonuser',
                    'license_project' => '',
                    'app' => '',
                    'post_exec_cmd' => '',
                    'runtime_estimation' => '-1',
                    'job_group_name' => '',
                    'requeue_evalues' => '',
                    'options2' => '6160',
                    'resize_notify_cmd' => '',
                    'last_resize_time' => '1389642771',
                    'rsv_id_2' => '',
                    'job_description' => '',
                    'submit_ext_num' => '0',
                    'options3' => '8',
                    'bsub_w' => 'host1201-ib',
                    'num_host_rusage' => '1306',
                    'effective_res_req' => '0',
                    'total_provisional_time' => '1287486',
                    'run_time' => '242',
                    'unknown1' => '0',
                    'unknown2' => 'host1234-ib',
                    'unknown3' => '1291',
                    'unknown4' => '0',
                    'unknown5' => '643768',
                    'unknown6' => '66',
                    'unknown7' => '0',
                    'unknown8' => 'host1235-ib',
                    'unknown9' => '1297',
                    'unknown10' => '0',
                    'unknown11' => '643765',
                    'unknown12' => '63',
                    'unknown13' => '0',
                    'unknown14' => 'host1236-ib',
                    'unknown15' => '1299',
                    'unknown16' => '0',
                    'unknown17' => '643770',
                    'unknown18' => '59',
                    'unknown19' => '0',
                    'unknown20' => 'host1257-ib',
                    'unknown21' => '1296',
                    'unknown22' => '0',
                    'unknown23' => '643756',
                    'unknown24' => '67',
                    'unknown25' => '0',
                    'unknown26' => 'host1270-ib',
                    'unknown27' => '1297',
                    'unknown28' => '0',
                    'unknown29' => '643763',
                    'unknown30' => '58',
                    'unknown31' => '0',
                    'unknown32' => 'host1301-ib',
                    'unknown33' => '1292',
                    'unknown34' => '0',
                    'unknown35' => '643773',
                    'unknown36' => '57',
                    'unknown37' => '0',
                    'unknown38' => 'host1303-ib',
                    'unknown39' => '1295',
                    'unknown40' => '0',
                    'unknown41' => '643763',
                    'unknown42' => '67',
                    'unknown43' => '0',
                    'unknown44' => '43200',
                    'unknown45' => '0',
                    'unknown46' => '8688640',
                    'unknown47' => 'select[((poe>0&&nrt_windows>0&&tmp>1)) && (type == local)] order[nidx] span[ptile=16] ',
                    'unknown48' => '',
                    'unknown49' => '-1',
                    'unknown50' => '',
                    'unknown51' => '-1',
                    'unknown52' => '0',
                    'unknown53' => '',
                    'unknown54' => '0',
                    'unknown55' => '0',
                    'unknown56' => '',
                    'unknown57' => '40368',
                    'unknown58' => '/scratch/anonuser/abcde/abcde0040',
                    'unknown59' => '1',
                    'unknown60' => '18338657682652659712',
                    'unknown61' => '1',
                    'unknown62' => '',
                    'walltime' => 5794523.29,
                    'resource_name' => 'testresource',
                    'node_list' => 'host1201-ib,host1234-ib,host1235-ib,host1236-ib,host1257-ib,host1270-ib,host1301-ib,host1303-ib',
                )
            ),
            array(
                '"JOB_FINISH" "9.11" 1389683139 404717 22025 33882739 128 '
                . '1389642318 0 0 1389642771 "anonuser" "regular" '
                . '"span[ptile=16]" "" "" "hostlogin4-ib" '
                . '"/scratch/anonuser/abcde/abcde0040" "" "%J.out" "%J.err" '
                . '"1389642318.404717" 0 8 "host1" "host1" "host2" "host2" '
                . '"host3" "host3" "host4" "host4" 64 60.0 "abcde0040" '
                . '"#!/bin/tcsh;#BSUB -a poe;#BSUB -P anonproject;#BSUB -W '
                . '12:00;#BSUB -n 128;#BSUB -R ""span[ptile=16]"";#BSUB -J '
                . 'abcde0040;#BSUB -o %J.out;#BSUB -e %J.err;#BSUB -q '
                . 'regular;pwd;date;date >> out.time;mpirun.lsf '
                . '/u/home/anonuser/bin/app > out.app;date >> out.time;date" '
                . '5793844.230000 679.060000 966960 0 -1 0 0 3026323 0 0 0 0 '
                . '-1 0 0 0 12529329 6498459 -1 "" "anonproject" 0 128 "" "" 0 '
                . '10725376 0 "" "" "" "" 0 "" 0 "" -1 "/anonproject/anonuser" '
                . '"" "" "" -1 "" "" 6160 "" 1389642771 "" "" 0 8 host1201-ib '
                . '1306 0 1287486 242 0 host1234-ib 1291 0 643768 66 0 '
                . 'host1235-ib 1297 0 643765 63 0 host1236-ib 1299 0 643770 59 '
                . '0 host1257-ib 1296 0 643756 67 0 host1270-ib 1297 0 643763 '
                . '58 0 host1301-ib 1292 0 643773 57 0 host1303-ib 1295 0 '
                . '643763 67 0 43200 0 8688640 '
                . '"select[((poe>0&&nrt_windows>0&&tmp>1)) && (type == local)] '
                . 'order[nidx] span[ptile=16] " "" -1 "" -1 0 "" 0 0 "" 40368 '
                . '"/scratch/anonuser/abcde/abcde0040" 1 '
                . '"18338657682652659712" 1 ""',
                array(
                    'event_type' => 'JOB_FINISH',
                    'version_number' => '9.11',
                    'event_time' => '1389683139',
                    'job_id' => '404717',
                    'user_id' => '22025',
                    'options' => '33882739',
                    'num_processors' => '128',
                    'submit_time' => '1389642318',
                    'begin_time' => '0',
                    'term_time' => '0',
                    'start_time' => '1389642771',
                    'user_name' => 'anonuser',
                    'queue' => 'regular',
                    'res_req' => 'span[ptile=16]',
                    'depend_cond' => '',
                    'pre_exec_cmd' => '',
                    'from_host' => 'hostlogin4-ib',
                    'cwd' => '/scratch/anonuser/abcde/abcde0040',
                    'in_file' => '',
                    'out_file' => '%J.out',
                    'err_file' => '%J.err',
                    'job_file' => '1389642318.404717',
                    'num_asked_hosts' => '0',
                    'asked_hosts' => array(),
                    'num_ex_hosts' => '4',
                    'exec_hosts' => array('host1', 'host2', 'host3', 'host4'),
                    'j_status' => '64',
                    'host_factor' => '60.0',
                    'job_name' => 'abcde0040',
                    'command' => '#!/bin/tcsh;#BSUB -a poe;#BSUB -P '
                    . 'anonproject;#BSUB -W 12:00;#BSUB -n 128;#BSUB -R '
                    . '"span[ptile=16]";#BSUB -J abcde0040;#BSUB '
                    . '-o %J.out;#BSUB -e %J.err;#BSUB -q '
                    . 'regular;pwd;date;date >> out.time;mpirun.lsf '
                    . '/u/home/anonuser/bin/app > out.app;date >> '
                    . 'out.time;date',
                    'ru_utime' => '5793844.230000',
                    'ru_stime' => '679.060000',
                    'ru_maxrss' => '966960',
                    'ru_ixrss' => '0',
                    'ru_ismrss' => '-1',
                    'ru_idrss' => '0',
                    'ru_isrss' => '0',
                    'ru_minflt' => '3026323',
                    'ru_majflt' => '0',
                    'ru_nswap' => '0',
                    'ru_inblock' => '0',
                    'ru_oublock' => '0',
                    'ru_ioch' => '-1',
                    'ru_msgsnd' => '0',
                    'ru_msgrcv' => '0',
                    'ru_nsignals' => '0',
                    'ru_nvcsw' => '12529329',
                    'ru_nivcsw' => '6498459',
                    'ru_exutime' => '-1',
                    'mail_user' => '',
                    'project_name' => 'anonproject',
                    'exit_status' => '0',
                    'max_num_processors' => '128',
                    'login_shell' => '',
                    'time_event' => '',
                    'idx' => '0',
                    'max_rmem' => '10725376',
                    'max_rswap' => '0',
                    'in_file_spool' => '',
                    'command_spool' => '',
                    'rsv_id' => '',
                    'sla' => '',
                    'except_mask' => '0',
                    'additional_info' => '',
                    'exit_info' => '0',
                    'warning_action' => '',
                    'warning_time_period' => '-1',
                    'charged_saap' => '/anonproject/anonuser',
                    'license_project' => '',
                    'app' => '',
                    'post_exec_cmd' => '',
                    'runtime_estimation' => '-1',
                    'job_group_name' => '',
                    'requeue_evalues' => '',
                    'options2' => '6160',
                    'resize_notify_cmd' => '',
                    'last_resize_time' => '1389642771',
                    'rsv_id_2' => '',
                    'job_description' => '',
                    'submit_ext_num' => '0',
                    'options3' => '8',
                    'bsub_w' => 'host1201-ib',
                    'num_host_rusage' => '1306',
                    'effective_res_req' => '0',
                    'total_provisional_time' => '1287486',
                    'run_time' => '242',
                    'unknown1' => '0',
                    'unknown2' => 'host1234-ib',
                    'unknown3' => '1291',
                    'unknown4' => '0',
                    'unknown5' => '643768',
                    'unknown6' => '66',
                    'unknown7' => '0',
                    'unknown8' => 'host1235-ib',
                    'unknown9' => '1297',
                    'unknown10' => '0',
                    'unknown11' => '643765',
                    'unknown12' => '63',
                    'unknown13' => '0',
                    'unknown14' => 'host1236-ib',
                    'unknown15' => '1299',
                    'unknown16' => '0',
                    'unknown17' => '643770',
                    'unknown18' => '59',
                    'unknown19' => '0',
                    'unknown20' => 'host1257-ib',
                    'unknown21' => '1296',
                    'unknown22' => '0',
                    'unknown23' => '643756',
                    'unknown24' => '67',
                    'unknown25' => '0',
                    'unknown26' => 'host1270-ib',
                    'unknown27' => '1297',
                    'unknown28' => '0',
                    'unknown29' => '643763',
                    'unknown30' => '58',
                    'unknown31' => '0',
                    'unknown32' => 'host1301-ib',
                    'unknown33' => '1292',
                    'unknown34' => '0',
                    'unknown35' => '643773',
                    'unknown36' => '57',
                    'unknown37' => '0',
                    'unknown38' => 'host1303-ib',
                    'unknown39' => '1295',
                    'unknown40' => '0',
                    'unknown41' => '643763',
                    'unknown42' => '67',
                    'unknown43' => '0',
                    'unknown44' => '43200',
                    'unknown45' => '0',
                    'unknown46' => '8688640',
                    'unknown47' => 'select[((poe>0&&nrt_windows>0&&tmp>1)) && (type == local)] order[nidx] span[ptile=16] ',
                    'unknown48' => '',
                    'unknown49' => '-1',
                    'unknown50' => '',
                    'unknown51' => '-1',
                    'unknown52' => '0',
                    'unknown53' => '',
                    'unknown54' => '0',
                    'unknown55' => '0',
                    'unknown56' => '',
                    'unknown57' => '40368',
                    'unknown58' => '/scratch/anonuser/abcde/abcde0040',
                    'unknown59' => '1',
                    'unknown60' => '18338657682652659712',
                    'unknown61' => '1',
                    'unknown62' => '',
                    'walltime' => 5794523.29,
                    'resource_name' => 'testresource',
                    'node_list' => 'host1,host2,host3,host4',
                )
            ),
        );
    }
}
