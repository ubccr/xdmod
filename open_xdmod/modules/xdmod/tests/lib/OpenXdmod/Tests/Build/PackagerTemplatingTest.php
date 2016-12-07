<?php
namespace OpenXdmod\Test\Build;

ini_set('memory_limit', -1);

use CCR\Log;
use OpenXdmod\Build\Packager;
use TestHelpers\TestHelper;

class PackagerTemplatingTest extends \PHPUnit_Framework_TestCase
{

    public function __construct()
    {
        $conf = array(
            'file' => false,
            'mail' => false,
            'db' => false,
            'consoleLogLevel' => LOG::DEBUG
        );
        $this->logger = Log::factory('packager-templating-test', $conf);
    }

    public function testPackagerTemplatingOfFiles()
    {
        $cwd = getcwd();
        $packager = $this->getPackager();

        // Go ahead and create the package.
        $packager->createPackage();

        // Extract information from the packager that we'll need moving forward.
        $destDir = TestHelper::unlockProperty($packager, 'destDir')
                 ->getValue($packager);
        $packageName = TestHelper::unlockProperty($packager, 'packageName')
                     ->getValue($packager);
        $replacementValues = TestHelper::unlockProperty($packager, 'replacementValues')
                           ->getValue($packager);

        // First find out if we have template files in the CONFIG_DIR
        chdir(CONFIG_DIR);

        $before = array();
        foreach($replacementValues as $replacementKey => $replacementValue) {
            $grepArgs = array(
                'grep',
                '-irn',
                $replacementKey,
                '.'
            );

            $cmd = implode(' ', array_map('escapeshellarg', $grepArgs));
            $output = $this->executeCommand($cmd, null);
            $before []= count($output);
        }

        // Count how many times a file with one of the template keys was found.
        $templateCount = array_reduce($before, function ($carry, $item) {
            $carry += $item;
            return $carry;
        }, 0);

        // We expect to find at least one.
        $this->assertNotFalse($templateCount > 0);

        // Change to the destination directory
        chdir($destDir);

        // untar the module
        $untarArgs = array(
            'tar',
            'xvzf',
            "$packageName.tar.gz"
        );

        $cmd = implode(' ', array_map('escapeshellarg', $untarArgs));
        $this->executeCommand($cmd);

        // change to the configuration directory of the newly untarred package
        // so that we can execute some greps from there.
        $configurationDir = implode(DIRECTORY_SEPARATOR, array('.', $packageName, 'configuration'));
        chdir($configurationDir);

        foreach($replacementValues as $replacementKey => $replacementValue) {
            $grepArgs = array(
                'grep',
                '-irn',
                $replacementKey,
                '.'
            );

            $cmd = implode(' ', array_map('escapeshellarg', $grepArgs));
            $output = $this->executeCommand($cmd, 1);

            // We expect each one to be empty.
            $this->assertEquals(array(), $output);
        }

        // Change to the build directory in preparation for removing the
        // exploded module contents.
        chdir($destDir);

        $rmArgs = array(
            'rm',
            '-rf',
            './'.$packageName
        );
        $cmd = implode(' ', array_map('escapeshellarg', $rmArgs));
        $this->executeCommand($cmd);

        // Change the current working directory back to where we were at the
        // beginning of the script.
        chdir($cwd);
    }

    /**
     * Retrieve an instance of Packager that is configured to package the
     * provided $module and logs it's messages at the provided $logLevel
     *
     * @param string  $module   the module to build.
     * @param integer $logLevel the log level the packager's logger should
     * operate.
     *
     * @return Packager a fully configured instance of Packager
     **/
    private function getPackager($module = 'xdmod', $logLevel = LOG::DEBUG)
    {
        $runTests = false;
        $clone = false;
        $extractAssets = false;

        $conf = array(
            'file' => false,
            'mail' => false,
            'db' => false,
            'consoleLogLevel' => $logLevel
        );

        $logger = Log::factory('xdmod-packager', $conf);

        // Create a new Packager instance for the 'xdmod' module.
        $packager = Packager::createFromModuleName($module);

        // Configure the packager
        $packager->setLogger($logger);
        $packager->setRunTests($runTests);
        $packager->setGitClone($clone);
        $packager->setExtractAssets($extractAssets);

        return $packager;
    }

    private function executeCommand($command, $expectedReturnVar = 0)
    {
        $output    = array();
        $returnVar = 0;

        $this->logger->debug("Executing command: $command");

        exec($command . ' 2>&1', $output, $returnVar);

        if ($expectedReturnVar !== null && $returnVar != $expectedReturnVar) {
            $msg = "Command exited with non-zero return status:\n"
                 . "command = $command\noutput =\n" . implode("\n", $output);
            throw new \Exception($msg);
        }

        if (count($output) > 0) {
            $this->logger->debug('BEGIN: command output');

            foreach ($output as $line) {
                $this->logger->debug($line);
            }

            $this->logger->debug('END: command output');
        }

        return $output;
    }
}
