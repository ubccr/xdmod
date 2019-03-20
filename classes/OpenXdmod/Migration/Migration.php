<?php
/**
 * Abstract base class for Open XDMoD migrations.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration;

use Configuration\Configuration;

abstract class Migration
{

    /**
     * Version before migration.
     *
     * @var string
     */
    protected $currentVersion;

    /**
     * Version to migrate to.
     *
     * @var string
     */
    protected $newVersion;

    /**
     * An empty default Configuration object.
     *
     * @var Configuration
     */
    protected $config;

    /**
     * Logger object.
     *
     * @var \Log
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param string $currentVersion The current Open XDMoD version.
     * @param string $newVersion The version to migrate to.
     */
    public function __construct($currentVersion, $newVersion)
    {
        $this->currentVersion = $currentVersion;
        $this->newVersion     = $newVersion;


        $this->logger = \Log::singleton('null');

        $this->config = new Configuration(
            ".",
            CONFIG_DIR,
            $this->logger
        );

    }

    /**
     * Set the logger.
     *
     * @param Logger $logger The logger instance.
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Execute the migration.
     */
    abstract public function execute();
}
