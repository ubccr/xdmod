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
        if ($this->configFile === null) {
            $this->configFile = implode(
                DIRECTORY_SEPARATOR,
                array(
                    CONFIG_DIR,
                    'etl',
                    'etl.json'
                )
            );
        }

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
            '-c',
            $this->configFile,
            '-p',
            $this->section,
            '-v',
            $this->logLevel
        );

        $cmd = implode(' ', array_map('escapeshellcmd', $params));
        if ($this->logLevel !== 'quiet') {
            fwrite(STDOUT, "Command: $cmd\n");
        }

        $output = shell_exec($cmd);
        if ($this->logLevel !== 'quiet') {
            fwrite(STDOUT, "$output\n");
        }
    }
}
