<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace ComponentTests;

use CCR\DB;
use Exception;
use Models\Services\Realms;

/**
 * @group XDMoD-shredder
 * Test the xdmod-slurm-helper executable.
 */
class SlurmHelperTest extends BaseTest
{
    /**
     * Test artifacts path.
     */
    const TEST_GROUP = 'component/slurm_helper';

    /**
     * Database handle for `mod_shredder`.
     * @var \CCR\DB\PDODB
     */
    private static $dbh;

    /**
     * Maximum id in `shredded_job_slurm` table before tests are run.
     * @var int
     */
    private static $maxSlurmJobId;

    /**
     * Maximum id in `shredded_job` table before tests are run.
     * @var int
     */
    private static $maxShreddedJobId;

    /**
     * Test xdmod-slurm-helper with various sacct commands.
     *
     * @dataProvider sacctCommandProvider
     *
     * @param string $sacctOutputType Output type to simulate.
     * @param int $sacctExitStatus Exit status to simulate.
     * @param string $outputRegex Regular expression that output should match.
     * @param int $exitStatus Exit status expected from slurm helper.
     */
    public function testSlurmHelper(
        $sacctOutputType,
        $sacctExitStatus,
        $stdoutRegex,
        $stderrRegex,
        $exitStatus
    ) {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $result = $this->executeSlurmHelper($sacctOutputType, $sacctExitStatus);
        if ($exitStatus !== $result['exit_status']) {
            echo var_export($result, true). "\n";
        }
        $this->assertEquals($exitStatus, $result['exit_status']);
        $this->assertMatchesRegularExpression($stdoutRegex, $result['stdout']);
        $this->assertMatchesRegularExpression($stderrRegex, $result['stderr']);
    }

    /**
     * Execute the slurm helper.
     *
     * Uses a fake sacct script to simulate different types of output and exit
     * status codes.
     *
     * @param string $outputType The output type that will be simulated.
     * @param integer $exitStatus The exit status that will be simulated.
     */
    private function executeSlurmHelper($outputType, $exitStatus)
    {
        $process = proc_open(
            'xdmod-slurm-helper -q -r frearson',
            array(
                0 => array('file', '/dev/null', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ),
            $pipes,
            null,
            array(
                'PATH' => realpath(__DIR__ . '/../scripts') . ':' . getenv('PATH'),
                'XDMOD_SACCT_OUTPUT_TYPE' => $outputType,
                'XDMOD_SACCT_EXIT_STATUS' => $exitStatus,
            )
        );

        if (!is_resource($process)) {
            throw new Exception('Failed to create xdmod-slurm-helper subprocess');
        }

        $stdout = stream_get_contents($pipes[1]);

        if ($stdout === false) {
            throw new Exception('Failed to get subprocess STDOUT');
        }

        $stderr = stream_get_contents($pipes[2]);

        if ($stderr === false) {
            throw new Exception('Failed to get subprocess STDERR');
        }

        $exitStatus = proc_close($process);

        return array(
            'exit_status' => $exitStatus,
            'stdout' => $stdout,
            'stderr' => $stderr,
        );
    }

    public function sacctCommandProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'sacct');
    }

    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        static::$dbh = DB::factory('shredder');
        static::$maxSlurmJobId = static::$dbh->query('SELECT COALESCE(MAX(shredded_job_slurm_id), 0) AS id FROM shredded_job_slurm')[0]['id'];
        static::$maxShreddedJobId = static::$dbh->query('SELECT COALESCE(MAX(shredded_job_id), 0) AS id FROM shredded_job')[0]['id'];
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::$dbh->execute('DELETE FROM shredded_job_slurm WHERE shredded_job_slurm_id > :id', array('id' => static::$maxSlurmJobId));
        static::$dbh->execute('DELETE FROM shredded_job WHERE shredded_job_id > :id', array('id' => static::$maxShreddedJobId));
    }
}
