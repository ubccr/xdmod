<?php

namespace ETL\DataEndpoint;

use Configuration\XdmodConfiguration;

class ConfigurationFileEndpoint extends JsonFile implements iStructuredFile, iComplexDataRecords
{

    const ENDPOINT_NAME = 'configurationfile';

    public function parse()
    {
        $config = XdmodConfiguration::factory($this->path, CONFIG_DIR, $this->logger);
        $tmpFile = tempnam(sys_get_temp_dir(), 'etl-xdmod-config-json');

        @file_put_contents($tmpFile, $config->toJson());

        // Close the handle opened on the original file during endpoint verification so
        // that parse() reads the merged configuration rather than the original file.
        $this->disconnect();

        $originalPath = $this->path;
        $this->path = $tmpFile;

        $retVal = parent::parse();

        unlink($tmpFile);
        $this->path = $originalPath;

        return $retVal;
    }
}
