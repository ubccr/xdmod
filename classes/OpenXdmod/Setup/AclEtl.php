<?php namespace OpenXdmod\Setup;

class AclEtl
{
    private $section;
    private $configFile;
    private $logLevel = 'quiet';

    public function __construct(array $options)
    {
        if (isset($options['section'])) {
            $this->section = $options['section'];
        } else {
            throw new \Exception('You must provide a section');
        }

        if (isset($options['config_file'])) {
            $this->configFile = $options['config_file'];
        }

        if (isset($options['log_level'])) {
            $this->logLevel = $options['log_level'];
        }
    }

    public function execute()
    {
        // Validation / Default Values
        $etlScript = implode(
            DIRECTORY_SEPARATOR,
            array(
                BASE_DIR,
                'tools',
                'etl',
                'etl_overseer.php'
            )
        );

        $params = array(
            $etlScript,
            '-p',
            $this->section,
            '-v',
            $this->logLevel
        );

        if(null !== $this->configFile){
            $params[] = '-c';
            $params[] = $this->configFile;
        }

        $cmd = implode(' ', array_map('escapeshellcmd', $params));
        if ($this->logLevel !== 'quiet') {
            fwrite(STDOUT, "Command: $cmd\n");
        }

        $pipes = array();

        $process = proc_open(
            $cmd,
            array(
                0 => array('file', '/dev/null', 'r'), // STDIN : Script doesn't need STDIN
                1 => array('pipe', 'w'),              // STDOUT
                2 => array('pipe', 'w')               // STDERR
            ),
            $pipes
        );

        if ( false === is_resource($process) ) {
            throw new \Exception(
                sprintf("Failed to create subprocess '%s': %s", $cmd, (null !== error_get_last() ? error_get_last() : "") )
            );
        }

        $stdOut = stream_get_contents($pipes[1]);
        if (false === $stdOut) {
            throw new \Exception("Failed to get STDOUT for %s", $cmd);
        }

        $stdErr = stream_get_contents($pipes[2]);
        if (false === $stdErr) {
            throw new \Exception("Failed to get STDERR for %s", $cmd);
        }

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \Exception($stdErr);
        }

        if ($this->logLevel !== 'quiet') {
            fwrite(STDOUT, "$stdOut\n");
        }
    }
}
